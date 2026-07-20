<x-layout>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">

    <x-slot:title>Prospector — Live Lead Radar</x-slot:title>

    <!-- Menu Toggle(Middle Left) -->
    <div class="sidebar-toggle" onclick="toggleSidebar()" title="Open Navigation">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="M9 18l6-6-6-6"></path>
        </svg>
    </div>

    <! --Left Sliding Sidebar -->
    <x-sidebar />

    <section class="dashboard" style="margin-left: 70px;">
        <div class="dash-head">
            <h2>Local Business Discovery</h2>
            <span class="scope-count" id="scopeCount">
                @if(session('results'))
                    Leads found: {{ count(session('results')) }}
                @else
                    <input type="number" id="businessLimitInput" name="limit" form="scanForm"
                           min="1" max="60" step="1"
                           value="{{ old('limit', session('last_limit', 20)) }}"
                           placeholder="How many businesses?" class="scope-limit-input"
                           style="width:180px; padding:6px 12px; border-radius:6px; border:1px solid #334155; background:rgba(255,255,255,0.03); color:#e2e8f0; font-size:0.85rem;">
                @endif
            </span>
        </div>

        <form action="{{ route('scan') }}" method="POST" class="console" id="scanForm" style="display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap;">
            @csrf
            <div class="console-field" style="flex:1 1 150px; min-width:0;">
                <label for="categoryInput">Business Category</label>
                <input type="text" id="categoryInput" name="category" placeholder="e.g. Logistics, Bakery" value="{{ old('category', session('last_category', '')) }}">
            </div>
            <div class="console-field" style="flex:1 1 150px; min-width:0;">
                <label for="locationInput">Location Target</label>
                <input type="text" id="locationInput" name="location" placeholder="e.g. Gulberg, Lahore" value="{{ old('location', session('last_location', '')) }}">
            </div>
            <button type="submit" class="btn btn-signal" id="scanBtn" style="flex-shrink:0;">
                <span class="mini-sweep" id="btnSweep"></span>
                <span id="scanBtnLabel">Run Scan</span>
            </button>
            <!-- Download CSV Button -->
            <button type="button" class="icon-btn" id="downloadBusinessesBtn" onclick="downloadCurrentSearchBusinessesCsv()" title="Download this search (CSV)" aria-label="Download this search as CSV">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M4 19h16"/></svg>
            </button>
        </form>

        <div id="resultsArea" data-enrich-url="{{ route('api.enrich') }}" data-initial-results="{{ json_encode(session('results') ?? []) }}">
            @if(session('error'))
                <div class="scan-alert" style="background:#7f1d1d; color:#fff; padding:12px; border-radius:6px; margin:1rem 0;">
                    ⚠️ {{ session('error') }}
                </div>
            @endif
            @if(session('info'))
                <div class="scan-alert" style="background:#164e63; color:#fff; padding:12px; border-radius:6px; margin:1rem 0;">
                    ℹ️ {{ session('info') }}
                </div>
            @endif

            <div class="scan-radar-loader" id="scanRadarLoader">
                <div class="scan-radar">
                    <div class="scan-radar-axis-h"></div>
                    <div class="scan-radar-axis-v"></div>
                    <div class="scan-radar-pulse pulse-1"></div>
                    <div class="scan-radar-pulse pulse-2"></div>
                    <div class="scan-radar-pulse pulse-3"></div>
                    <div class="scan-radar-sweep"></div>
                    <div class="scan-radar-target t1"></div>
                    <div class="scan-radar-target t2"></div>
                    <div class="scan-radar-target t3"></div>
                </div>
                <p class="scan-radar-status" id="scanRadarStatus">Scanning for businesses...</p>
            </div>

            <div class="ledger" id="ledgerWrap">
                <table>
                    <thead>
                        <tr>
                            <th>Business Name</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Web</th>
                            <th>Category</th>
                            <th>Scanned At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="resultsTableBody">
                        @if(session('results'))
                            @foreach (session('results') as $index => $business)
                                <tr>
                                    <td class="cell-name" data-label="Business Name">{{ $business['name'] }}</td>
                                    <td data-label="Address">{{ $business['address'] }}</td>
                                    <td class="cell-phone" data-label="Phone">{{ $business['phone'] }}</td>
                                    <td data-label="Web">
                                        @if($business['website'] !== 'No Website Listed')
                                            <a href="{{ $business['website'] }}" target="_blank" class="cell-link">Open URL</a>
                                        @else
                                            <span class="cell-unavailable">Unavailable</span>
                                        @endif
                                    </td>
                                    <td data-label="Category">{{ $business['category'] }}</td>
                                    <td class="cell-scanned" data-label="Scanned At">{{ $business['fetched_at'] ?? 'N/A' }}</td>
                                    <td data-label="Actions" style="display:flex; align-items:center; gap:8px;">
                                        <button class="reveal-btn" id="fetchBtn-{{ $index }}" data-business-name="{{ $business['name'] }}" data-business-website="{{ $business['website'] }}" onclick="enrichLeads({{ $index }})">Fetch</button>
                                        <a href="#" class="btn-delete" data-id="{{ $business['id'] ?? '' }}"
                                           style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; border: 1px solid #ef4444; color: #ef4444; background: rgba(239, 68, 68, 0.05); text-decoration: none; transition: all 0.2s; flex-shrink:0;"
                                           onmouseover="this.style.background='rgba(239, 68, 68, 0.2)';"
                                           onmouseout="this.style.background='rgba(239, 68, 68, 0.05)';"
                                           title="Delete Business">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.166 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                                <tr id="leadRow-{{ $index }}" class="nested-lead-row" style="display: none;">
                                    <td colspan="7"><div class="nested-lead-wrapper" id="leadWrapper-{{ $index }}"></div></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- ENRICH MODAL -->
    <x-slot:modalsAndDrawers>
          <div class="drawer-overlay" id="enrichModalOverlay" onclick="closeEnrichModal()"></div>
        <div class="drawer" id="enrichFieldsModal" style="max-width: 380px;">
            <div class="drawer-head">
                <span class="drawer-close" onclick="closeEnrichModal()">&times;</span>
                <h3>Select Details to Fetch</h3>
            </div>
            <div class="drawer-body">
                <p style="font-size: 0.85rem; color: #a1a1aa; margin-bottom: 12px;">
                    Email and Phone use Apollo credits. Uncheck what you don't need to save credits.
                </p>
                <label style="display:block; margin-bottom:10px;">
                    <input type="checkbox" id="fieldTitle" checked> Title
                </label>
                <label style="display:block; margin-bottom:10px;">
                    <input type="checkbox" id="fieldEmail" checked> Email
                </label>
                <label style="display:block; margin-bottom:10px;">
                    <input type="checkbox" id="fieldPhone" checked> Phone
                </label>
                <button class="btn btn-signal" style="width:100%; margin-top:10px;" onclick="confirmEnrich()">
                    Fetch Selected
                </button>
            </div>
        </div>

        <!-- ALL LEADS MODAL -->
        <div class="drawer-overlay" id="allLeadsOverlay" onclick="closeAllLeadsView()"></div>
        <div class="drawer" id="allLeadsModal" style="max-width: 640px;">
            <div class="drawer-head">
                <span class="drawer-close" onclick="closeAllLeadsView()">&times;</span>
                <h3 id="allLeadsTitle">All Leads</h3>
            </div>
            <div class="drawer-body" id="allLeadsBody"></div>
        </div>
    </x-slot:modalsAndDrawers>


    <script src="{{ asset('js/dashboard.js') }}"></script>
</x-layout>