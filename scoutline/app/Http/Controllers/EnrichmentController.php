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

    private const APOLLO_PAGE_SIZE = 4;
    private const MAX_PAGES = 10;

    public function enrich(Request $request)
    {
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

            // Disable contact fetching until you have credits
            $needsContact = false;

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

            Lead::create([
                'member_id'   => $memberId,
                'business_id' => $business->id ?? null,
                'domain'      => $domain ?? 'name_based',
                'leads'       => $leads,
            ]);

            $this->persistLeadsToHistory($memberId, $validated['name'], $validated['website'] ?? null, $leads);

            return response()->json(['leads' => $this->filterFields($leads, $fields)]);

        } catch (\Exception $e) {
            Log::error('Enrichment failed', ['message' => $e->getMessage()]);
            return response()->json(['leads' => [], 'error' => 'Something went wrong. Please try again.'], 500);
        }
    }

    private function getDomain(string $name, ?string $website, string $apiKey): ?string
    {
        if ($website) {
            $domain = $this->extractDomain($website);
            if ($domain) return $domain;
        }

        return $this->resolveDomainByName($name, $apiKey);
    }

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

    private function resolveDomainByName(string $name, string $apiKey): ?string
    {
        $name = trim($name);
        if (empty($name)) return null;

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post(self::APOLLO_ORG_SEARCH_URL, [
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

    private function searchAndEnrich(string $domain, string $apiKey, bool $needsContact): array
    {
        return $this->performPeopleSearch(['q_organization_domains' => $domain], $apiKey, $needsContact);
    }

    private function searchByCompanyName(string $companyName, string $apiKey, bool $needsContact): array
    {
        return $this->performPeopleSearch(['q_organization_name' => $companyName], $apiKey, $needsContact);
    }

    private function performPeopleSearch(array $searchParams, string $apiKey, bool $needsContact): array
    {
        $leads = [];
        $page = 1;

        do {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post(self::APOLLO_SEARCH_URL, array_merge($searchParams, [
                'page' => $page,
                'per_page' => self::APOLLO_PAGE_SIZE,
            ]));

            if (!$response->successful()) {
                break;
            }

            $data = $response->json();
            $people = $data['people'] ?? $data['contacts'] ?? [];

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

    private function matchPerson(?string $personId, string $apiKey): array
    {
        if (!$personId) return [];

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post(self::APOLLO_MATCH_URL, [
            'id' => $personId,
            'reveal_personal_emails' => true,
        ]);

        return $response->successful() ? ($response->json()['person'] ?? []) : [];
    }

    private function filterFields(array $leads, array $fields): array
    {
        return array_map(function ($lead) use ($fields) {
            return array_intersect_key($lead, array_flip($fields));
        }, $leads);
    }

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