<!-- Sidebar -->
<div class="sidebar" id="leftSidebar">
    <div class="sidebar-close" onclick="closeSidebar()" title="Close">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 6L6 18"/><path d="M6 6l12 12"/>
        </svg>
    </div>

    @php
        $operatorEmail = auth()->user()->email;
        $operatorInitials = strtoupper(substr($operatorEmail, 0, 2));
    @endphp

    <div class="sidebar-header">
        <div class="operator-row">
            <button type="button" class="operator-avatar" onclick="openProfileDrawer()" title="Edit profile" aria-label="Open your profile">
                {{ $operatorInitials }}
            </button>
            <div class="operator-meta" onclick="openProfileDrawer()">
                <div class="operator-eyebrow"><span class="status-dot"></span> Operator Online</div>
                <div class="operator-email" title="{{ $operatorEmail }}">{{ $operatorEmail }}</div>
            </div>
        </div>
    </div>

    <div class="sidebar-section" style="flex: 1; min-height: 0; display:flex; flex-direction:column;">
        <div class="sidebar-label">
            <span id="archiveLabel">Popular Categories</span>
            <button class="history-filter-btn" id="historyFilterBtn"
                    onclick="filterUserHistory('{{ auth()->id() }}', this)">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.5-6 8-6s8 2 8 6"/>
                </svg>
                Mine only
            </button>
        </div>

        <div class="archive-list" id="archiveList" data-history-url="{{ route('history.index') }}">
            @forelse(\App\Models\RecentSearch::mostSearchedCategories() as $stat)
                <div class="lead-card">
                    <div class="lead-name highlight-gold">{{ $stat->category }}</div>
                    <div class="lead-title">{{ $stat->search_count }} {{ $stat->search_count == 1 ? 'search' : 'searches' }}</div>
                </div>
            @empty
                <div class="archive-empty">No scans yet — run your first search to build the archive.</div>
            @endforelse
        </div>
    </div>

    <div class="sidebar-footer">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-terminate" style="width: 100%;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <path d="M16 17l5-5-5-5"/>
                    <path d="M21 12H9"/>
                </svg>
                Logout
            </button>
        </form>
    </div>
</div>

<!-- Profile Drawer (moved inside sidebar component) -->
<div class="drawer-overlay" id="profileDrawerOverlay" onclick="closeProfileDrawer()"></div>
<div class="drawer" id="profileDrawer" style="max-width: 380px;">
    <div class="drawer-head">
        <span class="drawer-close" onclick="closeProfileDrawer()">&times;</span>
        <h3>Your Profile</h3>
    </div>
    <div class="drawer-body">
        <div class="profile-avatar-lg">{{ $operatorInitials }}</div>

        <div class="profile-status" id="profileStatus"></div>

        <form id="profileForm" action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="profile-field">
                <label for="profileName">Name</label>
                <input type="text" id="profileName" name="name" value="{{ auth()->user()->name }}" required>
            </div>

            <div class="profile-field">
                <label for="profileEmail">Email</label>
                <input type="email" id="profileEmail" name="email" value="{{ auth()->user()->email }}" required>
                <small>Changing this may require re-verifying your email.</small>
            </div>

            <div class="profile-field">
                <label for="currentPassword">Current Password</label>
                <input type="password" id="currentPassword" name="current_password" required>
            </div>

            <div class="profile-field">
                <label for="profilePassword">New Password</label>
                <input type="password" id="profilePassword" name="password" placeholder="Leave blank to keep current password" autocomplete="new-password">
            </div>

            <div class="profile-field">
                <label for="profilePasswordConfirm">Confirm New Password</label>
                <input type="password" id="profilePasswordConfirm" name="password_confirmation" placeholder="Repeat new password" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-signal" style="width:100%; margin-top:6px;">
                Save Changes
            </button>
        </form>
    </div>
</div>