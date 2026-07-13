<x-layout>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <x-slot:title>Prospector — Live Lead Radar</x-slot:title>

    <x-slot:navActions>
        <button class="btn btn-ghost open-drawer-btn" onclick="toggleNavDrawer(true)">
            <span>☰</span> Navigation Menu
        </button>
    </x-slot:navActions>

    <section class="dashboard">
        
        <div class="dash-head">
            <h2>Local Business Discovery</h2>
            <span class="scope-count" id="scopeCount">
                @if(session('results'))
                    Scope active: {{ count(session('results')) }} leads found
                @else
                    Scope idle
                @endif
            </span>
        </div>

        <form action="{{ route('scan') }}" method="POST" class="console" id="scanForm">
            @csrf
            <div class="console-field">
                <label for="categoryInput">Business Category</label>
                <input type="text" id="categoryInput" name="category" placeholder="e.g. Logistics, Bakery, Clinics" value="{{ old('category', session('last_category', '')) }}">
            </div>
            <div class="console-field">
                <label for="locationInput">Location Target</label>
                <input type="text" id="locationInput" name="location" placeholder="e.g. Gulberg, Lahore" value="{{ old('location', session('last_location', '')) }}">
            </div>
            <button type="submit" class="btn btn-signal" id="scanBtn">
                <span class="mini-sweep" id="btnSweep"></span>
                <span id="scanBtnLabel">Run Scan</span>
            </button>
        </form>

        @if(session('success'))
            <div class="scan-status" id="scanStatus" style="display: flex; background: rgba(16, 185, 129, 0.1); border-color: #10b981;">
                <span id="scanMessage" style="color: #10b981;">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="scan-status" id="scanStatusError" style="display: flex; background: rgba(239, 68, 68, 0.1); border-color: #ef4444;">
                <span style="color: #ef4444;">{{ session('error') }}</span>
            </div>
        @endif

        <!-- data-enrich-url: passed from the View (MVC) so JS never hardcodes the endpoint -->
        <div id="resultsArea" data-enrich-url="{{ route('api.enrich') }}">
            <div class="ledger">
                @if (session('results'))
                    @if (count(session('results')) === 0)
                        <div class="empty-state">
                            <div class="eyebrow status-offline">0 Leads Extracted</div>
                            <p>No matching businesses were detected by the scan engine. Try searching a broader term like 'restaurant'.</p>
                        </div>
                    @else
                        <div style="overflow-x: auto; width: 100%;">
                            <table style="width: 100%; border-collapse: collapse; margin-top: 1rem; text-align: left;">
                                <thead style="background: rgba(255,255,255,0.05);">
                                    <tr>
                                        <th style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 13px; font-weight: 600;">Business Name</th>
                                        <th style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 13px; font-weight: 600;">Rating</th>
                                        <th style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 13px; font-weight: 600;">Address Target</th>
                                        <th style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 13px; font-weight: 600;">Phone Line</th>
                                        <th style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 13px; font-weight: 600;">Web Domain</th>
                                        <th style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 13px; font-weight: 600;">Intelligence Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (session('results') as $business)
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                            <!-- Google Places V1 maps Name structure inside displayName.text -->
                                            <td style="padding: 12px; font-size: 14px; color: #fff; font-weight: 500;">
                                                {{ $business['displayName']['text'] ?? 'Unknown Business' }}
                                            </td>
                                            
                                            <!-- Real-time Quality Rating display -->
                                            <td style="padding: 12px; font-size: 14px; color: #f59e0b;">
                                                {{ isset($business['rating']) ? '⭐ ' . $business['rating'] : '—' }}
                                            </td>

                                            <!-- Google Places V1 maps address to formattedAddress -->
                                            <td style="padding: 12px; font-size: 14px; color: #a1a1aa;">
                                                {{ $business['formattedAddress'] ?? 'No Address Data' }}
                                            </td>

                                            <!-- Google Places V1 maps phone line to nationalPhoneNumber -->
                                            <td style="padding: 12px; font-size: 14px; color: #60a5fa;">
                                                {{ $business['nationalPhoneNumber'] ?? 'Unavailable' }}
                                            </td>

                                            <!-- Google Places V1 maps website url to websiteUri -->
                                            <td style="padding: 12px; font-size: 14px;">
                                                @if(!empty($business['websiteUri']))
                                                    <a href="{{ $business['websiteUri'] }}" target="_blank" style="color: #10b981; text-decoration: underline;">Open URL</a>
                                                @else
                                                    <span style="color: #71717a;">No Web Footprint</span>
                                                @endif
                                            </td>

                                            <td style="padding: 12px;">
                                                <button
                                                    class="reveal-btn"
                                                    id="fetchBtn-{{ $loop->index }}"
                                                    data-business-name="{{ $business['displayName']['text'] ?? '' }}"
                                                    data-business-website="{{ $business['websiteUri'] ?? 'null' }}"
                                                    onclick="enrichLeads({{ $loop->index }})"
                                                >
                                                    Fetch Leads
                                                </button>
                                            </td>
                                        </tr>
                                        <tr id="leadRow-{{ $loop->index }}" class="nested-lead-row" style="display: none;">
                                            <td colspan="6">
                                                <div class="nested-lead-wrapper" id="leadWrapper-{{ $loop->index }}"></div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @else
                    <div class="empty-state">
                        <div class="eyebrow status-offline">Scope Offline</div>
                        <p>Enter a business category and location target above, then execute a scan sequence.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <x-slot:modalsAndDrawers>
        <div class="drawer-overlay" id="appDrawerOverlay" onclick="toggleNavDrawer(false)"></div>
        
        <div class="drawer" id="appNavDrawer">
            <div class="drawer-head">
                <span class="drawer-close" onclick="toggleNavDrawer(false)">&times;</span>
                <h3>Menu</h3>
                <div class="drawer-sub">OPERATOR: {{ auth()->user()->email }}</div>
            </div>
            
            <div class="drawer-body">
                <div class="field group-spacing">
                    <label>Navigation Links</label>
                    <a href="{{ url('/') }}" class="btn btn-ghost side-nav-link">
                        Home
                    </a>
                    <button class="btn btn-ghost side-nav-link" onclick="handleProfileAlert()">
                        Profile Details
                    </button>
                </div>

                <div class="field group-spacing">
                    <label>Recent Queries Archive</label>
                    
                    <div class="archive-list">
                        <div class="lead-card interactive-card" onclick="loadRecentQuery('Logistics', 'Gulberg, Lahore')">
                            <div class="lead-name highlight-gold">Logistics</div>
                            <div class="lead-title">Gulberg, Lahore</div>
                        </div>
                        
                        <div class="lead-card interactive-card" onclick="loadRecentQuery('Bakery', 'DHA Phase 5, Lahore')">
                            <div class="lead-name highlight-gold">Bakery</div>
                            <div class="lead-title">DHA Phase 5, Lahore</div>
                        </div>

                        <div class="lead-card interactive-card" onclick="loadRecentQuery('Dental Clinics', 'Johar Town, Lahore')">
                            <div class="lead-name highlight-gold">Dental Clinics</div>
                            <div class="lead-title">Johar Town, Lahore</div>
                        </div>
                    </div>
                </div>

                <div class="drawer-actions-divider">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-terminate">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="drawer-foot">
                Logged in as: {{ auth()->user()->name }}<br>
            </div>
        </div>
    </x-slot:modalsAndDrawers>

    <script src="{{ asset('js/dashboard.js') }}"></script>
</x-layout>