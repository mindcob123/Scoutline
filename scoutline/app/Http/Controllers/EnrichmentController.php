<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Business;
use App\Models\Lead;
use App\Models\RecentSearch;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnrichmentController extends Controller
{
    private const APOLLO_SEARCH_URL = 'https://api.apollo.io/api/v1/mixed_people/api_search'; // no credit
    private const APOLLO_MATCH_URL  = 'https://api.apollo.io/api/v1/people/match'; // 1 credit 
    private const APOLLO_ORG_SEARCH_URL = 'https://api.apollo.io/api/v1/mixed_companies/search'; // search by company name

    private const APOLLO_PAGE_SIZE = 10;
    private const MAX_PAGES = 10;
    //Main method that handles the lead enrichment request. Validates input, gets domain, searches Apollo, saves data and returns leads.
    public function enrich(Request $request)
    {
        set_time_limit(500);

        try {
            $validated = $request->validate([
                'name'    => 'required|string|max:255',
                'website' => 'nullable|string|max:255',
                'fields'  => 'nullable|array',
                'fields.*' => 'string|in:name,title,email,phone',
            ]);

            Log::info('Enrichment request received', [
                'name'    => $validated['name'],
                'website' => $validated['website'] ?? null,
            ]);

            $fields = $validated['fields'] ?? ['name', 'title', 'email', 'phone'];
            $apiKey = env('APOLLO_API_KEY');

            if (empty($apiKey)) {
                return response()->json(['leads' => [], 'error' => 'Apollo API key is not configured.'], 500);
            }

            $domain = $this->getDomain($validated['name'], $validated['website'] ?? null, $apiKey);

            // Toggle it off to disable contact fetching until you have credits
            $needsContact = true;

            $leads = [];

            if ($domain) {
                Log::info('Searching using domain', ['domain' => $domain]);
                $leads = $this->searchAndEnrich($domain, $apiKey, $needsContact);
            } else {
                Log::info('No domain found, trying direct search by company name');
                $leads = $this->searchByCompanyName($validated['name'], $apiKey, $needsContact);
            }

            if (empty($leads)) {
                return response()->json([
                    'leads' => [],
                    'error' => 'No employees found for this company. Try with a website if available.'
                ]);
            }

            // Save to cache
            $memberId = Auth::id();
            $business = Business::where('member_id', $memberId)
                ->where('website', $validated['website'] ?? null)
                ->latest()
                ->first();

            // Updated logic to prevent duplicate entry errors
            Lead::updateOrCreate(
                [
                  'member_id' => $memberId,
                  'domain'    => $domain ?? 'name_based',
                ],
                [
                 'business_id' => $business->id ?? null,
                 'leads'       => $leads,
                ]
                );

            $this->persistLeadsToHistory($memberId, $validated['name'], $validated['website'] ?? null, $leads);

            return response()->json(['leads' => $this->filterFields($leads, $fields)]);

        } catch (\Exception $e) {
            Log::error('Enrichment failed', ['message' => $e->getMessage()]);
            return response()->json(['leads' => [], 'error' => 'Something went wrong. Please try again.'], 500);
        }
    }
    // Domain Resolution Group
     // Determines the domain for the company. First tries to extract from website, if not available then resolves using company name.
    private function getDomain(string $name, ?string $website, string $apiKey): ?string
    {
        if ($website) {
            $domain = $this->extractDomain($website);
            if ($domain) return $domain;
        }

        return $this->resolveDomainByName($name, $apiKey);
    }

    
     // Extracts clean domain name from a full website URL. Removes http/https, www, and converts to lowercase.
    private function extractDomain(string $website): ?string
    {
        $website = trim($website);
        if (empty($website) || strtolower($website) === 'no website listed') {
            return null;
        }

        if (!preg_match('#^https?://#i', $website)) {
            $website = 'http://' . $website;
        }

        $host = parse_url($website, PHP_URL_HOST);
        return $host ? preg_replace('/^www\./i', '', strtolower($host)) : null;
    }
     // Uses Apollo API to search organization by company name and returns its primary domain.
    private function resolveDomainByName(string $name, string $apiKey): ?string
    {
        $name = trim($name);
        if (empty($name)) return null;

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(8)->post(self::APOLLO_ORG_SEARCH_URL, [
            'q_organization_name' => $name,
            'page' => 1,
            'per_page' => 3,
        ]);

        if (!$response->successful()) {
            Log::warning('Organization search failed', ['name' => $name]);
            return null;
        }

        $organizations = $response->json()['organizations'] ?? $response->json()['accounts'] ?? [];
        $org = $organizations[0] ?? null;

        $domain = $org['primary_domain'] ?? $org['domain'] ?? null;
        return $domain ? preg_replace('/^www\./i', '', strtolower($domain)) : null;
    }
    // Search & Enrichment Group. Searches for people using company domain.
    private function searchAndEnrich(string $domain, string $apiKey, bool $needsContact): array
    {
        return $this->performPeopleSearch(['q_organization_domains' => $domain], $apiKey, $needsContact);
    }
    // Searches for people using company name (fallback when domain is not available).
    private function searchByCompanyName(string $companyName, string $apiKey, bool $needsContact): array
    {
        return $this->performPeopleSearch(['q_organization_name' => $companyName], $apiKey, $needsContact);
    }

     // Core function that calls Apollo people search API with pagination. Collects people data and optionally enriches individual contacts.
    private function performPeopleSearch(array $searchParams, string $apiKey, bool $needsContact): array
    {
        $leads = [];
        $page = 1;

        do {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(8)->post(self::APOLLO_SEARCH_URL, array_merge($searchParams, [
                'page' => $page,
                'per_page' => self::APOLLO_PAGE_SIZE,
            ]));

            if (!$response->successful()) {
                Log::error('Apollo API call failed', [
                   'status' => $response->status(),
                   'body' => $response->body(), // This will show you if it's a 401, 429, or 400
                ]);
            break;
            }
            $data = $response->json();
            $people = $data['people'] ?? $data['contacts'] ?? [];
            Log::info('Apollo page result', [
                'page' => $page,
                'people_count' => count($people),
                'total_entries' => $data['pagination']['total_entries'] ?? null,
            ]);

            foreach ($people as $person) {
                $matched = $needsContact ? $this->matchPerson($person['id'] ?? null, $apiKey) : [];

                $name = trim(($matched['first_name'] ?? $person['first_name'] ?? '') . ' ' .
                             ($matched['last_name'] ?? $person['last_name'] ?? ''));

                $leads[] = [
                    'name'  => $name ?: 'Unknown',
                    'title' => $matched['title'] ?? $person['title'] ?? '',
                    'email' => 'Not available',
                    'phone' => 'Not available',
                ];
            }

            $page++;
        } while (!empty($people) && $page <= self::MAX_PAGES);

        return $leads;
    }
     // Calls Apollo match endpoint to get detailed information (email, phone etc.) for a specific person using their ID.
    private function matchPerson(?string $personId, string $apiKey): array
    {
        if (!$personId) return [];

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(8)->post(self::APOLLO_MATCH_URL, [
            'id' => $personId,
            'reveal_personal_emails' => true,
        ]);

        return $response->successful() ? ($response->json()['person'] ?? []) : [];
    }
    // Filters the leads array to return only the requested fields.
    private function filterFields(array $leads, array $fields): array
    {
        return array_map(function ($lead) use ($fields) {
            return array_intersect_key($lead, array_flip($fields));
        }, $leads);
    }

     // Updates the RecentSearch history for the user with the latest leads data.    
    private function persistLeadsToHistory(?int $memberId, string $businessName, ?string $businessWebsite, array $leads): void
    {
        if (!$memberId) return;

        $displayLeads = $this->filterFields($leads, ['name', 'title', 'email', 'phone']);

        $searches = RecentSearch::where('member_id', $memberId)->get(['id', 'results']);

        foreach ($searches as $search) {
            $results = $search->results ?? [];
            $changed = false;

            foreach ($results as &$biz) {
                if (($biz['name'] ?? null) === $businessName && 
                    ($biz['website'] ?? null) === $businessWebsite) {
                    $biz['leads'] = $displayLeads;
                    $changed = true;
                }
            }
            unset($biz);

            if ($changed) {
                $search->update(['results' => $results]);
            }
        }
    }
}