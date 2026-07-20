<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Business;
use App\Models\RecentSearch;

class LeadScanController extends Controller
{
// for most searched history 
    public function history(Request $request)
    {
        if ($request->boolean('mine')) {
            $searches = RecentSearch::where('member_id', Auth::id())
                ->latest()
                ->take(20)
                ->get(['id', 'category', 'location', 'results']);
 
            return response()->json([
                'mode' => 'mine',
                'searches' => $searches,
            ]);
        }
 
        $categories = RecentSearch::mostSearchedCategories();
 
        return response()->json([
            'mode' => 'popular',
            'categories' => $categories,
        ]);
    }

    // Fetching business from Google Maps
    public function scan(Request $request)
    {
        
        $validated = $request->validate([
            'category' => 'required|string',
            'location' => 'required|string',
            'limit' => 'nullable|integer|min:1|max:60',
        ]);

        $limit = $validated['limit'] ?? 20;

        // 1. DEDUPLICATION: Check if this search combination exists already, and already has
        // at least as many businesses saved as the user is asking for this time. If the user
        // is asking for more than we previously cached, we need a fresh scan instead.
        $existing = RecentSearch::where('category', $validated['category'])
                                ->where('location', $validated['location'])
                                ->latest()
                                ->first();

        $cacheHasAllIds = $existing && !empty($existing->results)
            && collect($existing->results)->every(fn ($lead) => !empty($lead['id'] ?? null));

        if ($existing && $cacheHasAllIds && count($existing->results) >= $limit) {
            $results = array_slice($existing->results, 0, $limit);

            session([
                'results'       => $results,
                'last_category' => $validated['category'],
                'last_location' => $validated['location'],
                'last_limit'    => $limit,
            ]);
            session()->save();

            return back()->with([
                'results' => $results,
                'last_category' => $validated['category'],
                'last_location' => $validated['location'],
                'last_limit' => $limit,
                'info' => 'Loaded from cache.'
            ]);
        }

        // 2. FRESH SCAN: Call Python service if no valid cache exists
        $pythonUrl = env('FASTAPI_URL', 'http://127.0.0.1:8001');
        $apiKey = env('GOOGLE_API_KEY');

        if (empty($apiKey)) {
            return back()->with('error', 'API key missing.');
        }

        $response = Http::post("{$pythonUrl}/api/scan", [
            'category' => $validated['category'],
            'location' => $validated['location'],
            'api_key' => $apiKey,
            'limit' => $limit,
        ]);

        if ($response->successful()) {
            $results = $response->json()['results'] ?? [];
            $memberId = Auth::id(); // Get the currently authenticated user's ID

            // Save individual businesses to 'businesses' table
            foreach ($results as &$lead) {
                $lead['fetched_at'] = $lead['fetched_at'] ?? now()->toDateTimeString();
                $business = Business::create([
                    'member_id' => $memberId,
                    'name'       => $lead['name'],
                    'address'    => $lead['address'],
                    'category'   => $lead['category'],
                    'phone'      => $lead['phone'],
                    'website'    => $lead['website'],
                    'fetched_at' => $lead['fetched_at'],
                ]);
                $lead['id'] = $business->id;
            }
            unset($lead); // good practice after a foreach-by-reference

            // Save to RecentSearch for the archive
            RecentSearch::create([
                'member_id' => $memberId,
                'category'  => $validated['category'],
                'location'  => $validated['location'],
                'results'   => $results,
            ]);

            // FIX: Persist the fresh scan results in the session so the exporter can access it!
            session([
                'results'       => $results,
                'last_category' => $validated['category'],
                'last_location' => $validated['location'],
                'last_limit'    => $limit,
            ]);

            return back()->with([
                'results' => $results,
                'last_category' => $validated['category'],
                'last_location' => $validated['location'],
                'last_limit' => $limit,
            ]);
        }

        return back()->with('error', 'Python service connection failed.');
    }
    public function destroyBusiness($businessId) {
    // 1. Delete from DB
    $business = Business::where('member_id', auth()->id())->findOrFail($businessId);
    $business->delete();

    // 2. Update the session (This fixes the UI bug)
    $results = session('results', []);
    $updatedResults = array_values(array_filter($results, function ($item) use ($businessId) {
        return ($item['id'] ?? null) != $businessId;
    }));
    session(['results' => $updatedResults]);

    // 3. Update the RecentSearch cache
    $recentSearches = RecentSearch::where('member_id', auth()->id())->get();
    foreach ($recentSearches as $search) {
        $search->results = array_values(array_filter($search->results, function ($item) use ($businessId) {
            return ($item['id'] ?? null) != $businessId;
        }));
        $search->save();
    }

    return response()->json(['success' => true]);
}}