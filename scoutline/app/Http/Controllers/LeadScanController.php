<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LeadScanController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'location' => 'required|string',
        ]);

        $pythonUrl = env('PYTHON_SERVICE_URL', 'http://127.0.0.1:8001');
        $apiKey = env('GEOAPIFY_API_KEY');

        if (empty($apiKey)) {
            // Fail clearly instead of silently sending an empty/invalid key.
            return back()->with('error', 'Geoapify API key is not configured on the server.');
        }

        $response = Http::post("{$pythonUrl}/api/scan", [
            'category' => $request->category,
            'location' => $request->location,
            'api_key' => $apiKey,
        ]);

        if ($response->successful()) {
            $responseData = $response->json();

            return back()->with([
                'success' => $responseData['message'] ?? 'Scan complete!',
                'results' => $responseData['results'] ?? [],
                // Saved so the form fields can show the last search instead of
                // resetting to hardcoded defaults on every successful scan.
                'last_category' => $request->category,
                'last_location' => $request->location,
            ]);
        }

        return back()->with('error', 'Could not connect to Python service.');
    }
}