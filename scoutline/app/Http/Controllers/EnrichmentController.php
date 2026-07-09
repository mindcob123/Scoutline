<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EnrichmentController extends Controller
{
    /**
     * Handle a lead enrichment request for a single business.
     *
     * FRONTEND CONTRACT (already wired in dashboard.js — do not change the
     * request/response shape without updating enrichLeads() as well):
     *
     * Request JSON:
     *   { "name": string, "website": string|null }
     *
     * Response JSON (200):
     *   {
     *     "leads": [
     *       { "name": string, "title": string, "email": string },
     *       ...
     *     ]
     *   }
     *
     *   An empty "leads" array is valid — it means no contacts were found.
     *   If "website" is null/empty, return an empty leads array immediately
     *   (Apollo needs a domain to search against).
     *
     * TODO (backend owner):
     *   - Validate the incoming website looks like a real domain.
     *   - Call the Apollo integration using that website/domain.
     *   - Consider caching by domain (e.g. in a `leads` table keyed on
     *     website) so re-enriching the same business doesn't burn another
     *     Apollo credit on every request.
     *   - Replace the dummy $leads array below with real results.
     */
    public function enrich(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website' => 'nullable|string|max:255',
        ]);

        if (empty($validated['website'])) {
            return response()->json(['leads' => []]);
        }

        // --- DUMMY DATA — remove once the real Apollo integration is in place ---
        $leads = [
            [
                'name' => 'Jane Doe (placeholder)',
                'title' => 'Operations Manager',
                'email' => 'jane@example.com',
            ],
        ];
        // --- end dummy data ---

        return response()->json([
            'leads' => $leads,
        ]);
    }
}