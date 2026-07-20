// GLOBAL STATE
let currentResults = [];
const fetchedLeadsByIdx = {};
let pendingEnrichIdx = null;
let currentAbortController = null;

// UTILITY FUNCTIONS
const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
const escapeHtml = (val) => String(val || '').replace(/[&<>"']/g, m => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
}[m]));

const slugify = (text) => String(text).toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '') || 'export';

const csvEscape = (val) => /[",\n]/.test(String(val)) 
    ? '"' + String(val).replace(/"/g, '""') + '"' 
    : String(val);

// CSV DOWNLOAD HELPERS
function triggerCsvDownload(filename, content) {
    const blob = new Blob([content], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    URL.revokeObjectURL(url);
}

function buildCsv(headers, rows) {
    return [
        headers.map(csvEscape).join(','),
        ...rows.map(row => row.map(csvEscape).join(','))
    ].join('\r\n');
}

// UI CONTROLLERS
function toggleSidebar() {
    const sidebar = document.getElementById('leftSidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    if (!sidebar) return;

    const isOpen = sidebar.classList.toggle('open');
    toggleBtn?.classList.toggle('is-hidden', isOpen);
    document.body.classList.toggle('sidebar-open', isOpen);
}

function closeSidebar() {
    document.getElementById('leftSidebar')?.classList.remove('open');
    document.body.classList.remove('sidebar-open');
    document.querySelector('.sidebar-toggle')?.classList.remove('is-hidden');
}

function openProfileDrawer() {
    document.getElementById('profileDrawerOverlay')?.classList.add('active');
    document.getElementById('profileDrawer')?.classList.add('open');
}

function closeProfileDrawer() {
    document.getElementById('profileDrawerOverlay')?.classList.remove('active');
    document.getElementById('profileDrawer')?.classList.remove('open');
}

function enrichLeads(idx) {
    pendingEnrichIdx = idx;
    document.getElementById('enrichFieldsModal')?.classList.add('open');
    document.getElementById('enrichModalOverlay')?.classList.add('open');
}

function closeEnrichModal() {
    document.getElementById('enrichFieldsModal')?.classList.remove('open');
    document.getElementById('enrichModalOverlay')?.classList.remove('open');
    pendingEnrichIdx = null;
}

// SCAN & ENRICH LOGIC
function startScanRadar() {
    // Hide alerts and old results
    document.querySelectorAll('#resultsArea .scan-alert').forEach(el => el.style.display = 'none');
    document.getElementById('ledgerWrap').style.display = 'none';
    
    // Show radar
    document.getElementById('scanRadarLoader').classList.add('active');

    // Disable scan button
    const scanBtn = document.getElementById('scanBtn');
    if (scanBtn) {
        scanBtn.disabled = true;
        scanBtn.innerText = 'Scanning...';
    }
}

async function runEnrichment(idx, fields) {
    const btn = document.getElementById(`fetchBtn-${idx}`);
    const wrapper = document.getElementById(`leadWrapper-${idx}`);
    const businessName = btn?.dataset.businessName || '';
    const website = btn?.dataset.businessWebsite || '';

    if (!btn) return;

    btn.disabled = true;
    btn.innerText = 'Connecting...';

    currentAbortController = new AbortController();
    const timeoutId = setTimeout(() => currentAbortController.abort(), 15000);

    try {
        const response = await fetch(document.getElementById('resultsArea').dataset.enrichUrl, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': getCsrfToken() 
            },
            body: JSON.stringify({ name: businessName, website, fields }),
            signal: currentAbortController.signal
        });

        const data = await response.json();

        if (response.ok) {
            renderEnrichedLeads(idx, data.leads || [], businessName);
        } else {
            throw new Error(data.message || 'Failed to enrich');
        }
    } catch (err) {
        wrapper.innerHTML = `<div class="text-muted-color">Error: ${err.name === 'AbortError' ? 'Request timeout' : 'Failed to fetch'}</div>`;
        btn.disabled = false;
        btn.innerText = 'Retry';
    } finally {
        clearTimeout(timeoutId);
    }
}

const LEAD_PREVIEW_COUNT = 6;

function buildLeadWrapperHtml(idx, leads) {
    if (!leads.length) {
        return `<div class="text-muted-color">No additional records found.</div>`;
    }

    const preview = leads.slice(0, LEAD_PREVIEW_COUNT);
    const hasMore = leads.length > preview.length;

    return `
        <div style="display:flex; justify-content:flex-end; align-items:center; gap:8px; margin-bottom:8px;">
            ${hasMore ? `<button class="reveal-btn" style="padding:6px 12px; font-size:0.75rem;" onclick="openAllLeadsView(${idx})">View All (${leads.length})</button>` : ''}
            <button class="icon-btn" onclick="downloadBusinessLeadsCsv(${idx})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M4 19h16"/>
                </svg>
            </button>
        </div>
        ${preview.map(lead => `
            <div class="lead-card nested-card">
                <div class="lead-name">${escapeHtml(lead.name || '')}</div>
                <div class="lead-contact-value">${escapeHtml(lead.title || '')}</div>
                <div class="lead-contact-value">${escapeHtml(lead.email || '')}</div>
                <div class="lead-contact-value phone-meta">${escapeHtml(lead.phone || '')}</div>
            </div>
        `).join('')}
    `;
}

function openAllLeadsView(idx) {
    const entry = fetchedLeadsByIdx[idx];
    if (!entry) return;

    const title = document.getElementById('allLeadsTitle');
    const body = document.getElementById('allLeadsBody');
    if (title) title.textContent = `All Leads — ${entry.businessName} (${entry.leads.length})`;
    if (body) {
        body.innerHTML = entry.leads.map(lead => `
            <div class="lead-card nested-card">
                <div class="lead-name">${escapeHtml(lead.name || '')}</div>
                <div class="lead-contact-value">${escapeHtml(lead.title || '')}</div>
                <div class="lead-contact-value">${escapeHtml(lead.email || '')}</div>
                <div class="lead-contact-value phone-meta">${escapeHtml(lead.phone || '')}</div>
            </div>
        `).join('');
    }

    document.getElementById('allLeadsOverlay')?.classList.add('active');
    document.getElementById('allLeadsModal')?.classList.add('open');
}

function closeAllLeadsView() {
    document.getElementById('allLeadsOverlay')?.classList.remove('active');
    document.getElementById('allLeadsModal')?.classList.remove('open');
}

function renderEnrichedLeads(idx, leads, businessName) {
    const btn = document.getElementById(`fetchBtn-${idx}`);
    const wrapper = document.getElementById(`leadWrapper-${idx}`);
    const row = document.getElementById(`leadRow-${idx}`);

    if (!btn || !wrapper) return;

    btn.innerText = 'Enriched';
    btn.classList.add('active-enrich');
    btn.disabled = true;

    if (leads.length > 0) {
        fetchedLeadsByIdx[idx] = { businessName, leads };
    }
    wrapper.innerHTML = buildLeadWrapperHtml(idx, leads);

    row.style.display = 'table-row';
}

// Toggles visibility of a nested lead row that was pre-populated from history
// (business already had saved leads, so no fetch is needed).
function toggleLeadRow(idx) {
    const row = document.getElementById(`leadRow-${idx}`);
    if (!row) return;
    row.style.display = row.style.display === 'table-row' ? 'none' : 'table-row';
}

// RESULTS TABLE RENDERING
function renderResultsTable(results) {
    const tbody = document.getElementById('resultsTableBody');
    if (!tbody) return;

    // Different search, so any previously fetched enrichment state is stale.
    Object.keys(fetchedLeadsByIdx).forEach(key => delete fetchedLeadsByIdx[key]);

    if (!results.length) {
        tbody.innerHTML = '';
        return;
    }

    tbody.innerHTML = results.map((biz, index) => {
        const hasLeads = Array.isArray(biz.leads) && biz.leads.length > 0;
        return `
        <tr>
            <td class="cell-name" data-label="Business Name">${escapeHtml(biz.name)}</td>
            <td data-label="Address">${escapeHtml(biz.address)}</td>
            <td class="cell-phone" data-label="Phone">${escapeHtml(biz.phone)}</td>
            <td data-label="Web">
                ${biz.website && biz.website !== 'No Website Listed'
                    ? `<a href="${escapeHtml(biz.website)}" target="_blank" class="cell-link">Open URL</a>`
                    : `<span class="cell-unavailable">Unavailable</span>`}
            </td>
            <td data-label="Category">${escapeHtml(biz.category)}</td>
            <td class="cell-scanned" data-label="Scanned At">${escapeHtml(biz.fetched_at || 'N/A')}</td>
            <td data-label="Actions">
                ${hasLeads
                    ? `<button class="reveal-btn active-enrich" id="fetchBtn-${index}" data-business-name="${escapeHtml(biz.name)}" data-business-website="${escapeHtml(biz.website)}" onclick="toggleLeadRow(${index})">View Leads</button>`
                    : `<button class="reveal-btn" id="fetchBtn-${index}" data-business-name="${escapeHtml(biz.name)}" data-business-website="${escapeHtml(biz.website)}" onclick="enrichLeads(${index})">Fetch</button>`}
            </td>
        </tr>
        <tr id="leadRow-${index}" class="nested-lead-row" style="display: none;">
            <td colspan="7"><div class="nested-lead-wrapper" id="leadWrapper-${index}"></div></td>
        </tr>
    `;
    }).join('');

    // Pre-populate wrapper content + CSV export state for businesses that already
    // have saved leads (loaded from history), so no re-fetch is needed to view them.
    results.forEach((biz, index) => {
        if (Array.isArray(biz.leads) && biz.leads.length > 0) {
            fetchedLeadsByIdx[index] = { businessName: biz.name, leads: biz.leads };
            const wrapper = document.getElementById(`leadWrapper-${index}`);
            if (wrapper) wrapper.innerHTML = buildLeadWrapperHtml(index, biz.leads);
        }
    });
}

function updateScopeCount(count) {
    const scopeCount = document.getElementById('scopeCount');
    if (!scopeCount) return;
    scopeCount.textContent = count > 0 ? `Leads found: ${count}` : 'Scope idle';
}

function loadCachedQuery(category, location, results) {
    const categoryInput = document.getElementById('categoryInput');
    const locationInput = document.getElementById('locationInput');
    if (categoryInput) categoryInput.value = category;
    if (locationInput) locationInput.value = location;

    currentResults = Array.isArray(results) ? results : [];
    renderResultsTable(currentResults);
    updateScopeCount(currentResults.length);

    document.getElementById('scanRadarLoader')?.classList.remove('active');
    const ledgerWrap = document.getElementById('ledgerWrap');
    if (ledgerWrap) ledgerWrap.style.display = '';

    document.querySelectorAll('#resultsArea .scan-alert').forEach(el => el.style.display = 'none');

    // Collapse the sidebar back out of the way now that the query has loaded.
    closeSidebar();
}

// SIDEBAR HISTORY
function renderArchiveList(mode, items) {
    const list = document.getElementById('archiveList');
    const label = document.getElementById('archiveLabel');
    if (!list) return;

    if (mode === 'mine') {
        if (label) label.textContent = 'Your Recent Queries';

        if (!items.length) {
            list.innerHTML = `<div class="archive-empty">You haven't run any scans yet.</div>`;
            return;
        }

        list.innerHTML = items.map(s => `
            <div class="lead-card interactive-card"
                 data-category="${escapeHtml(s.category)}"
                 data-location="${escapeHtml(s.location)}"
                 data-results="${escapeHtml(JSON.stringify(s.results || []))}">
                <div class="lead-name highlight-gold">${escapeHtml(s.category)}</div>
                <div class="lead-title">${escapeHtml(s.location)}</div>
            </div>
        `).join('');
        return;
    }

    // Popular mode: aggregate counts only, no fetched business data, not clickable.
    if (label) label.textContent = 'Popular Categories';

    if (!items.length) {
        list.innerHTML = `<div class="archive-empty">No scans yet — run your first search to build the archive.</div>`;
        return;
    }

    list.innerHTML = items.map(c => `
        <div class="lead-card">
            <div class="lead-name highlight-gold">${escapeHtml(c.category)}</div>
            <div class="lead-title">${c.search_count} ${c.search_count === 1 ? 'search' : 'searches'}</div>
        </div>
    `).join('');
}

function filterUserHistory(userId, btn) {
    const archiveList = document.getElementById('archiveList');
    const historyUrl = archiveList?.dataset.historyUrl;
    if (!historyUrl || !btn) return;

    const showMineOnly = !btn.classList.contains('is-active');
    btn.classList.toggle('is-active', showMineOnly);
    btn.setAttribute('aria-pressed', String(showMineOnly));

    fetch(`${historyUrl}?mine=${showMineOnly ? 1 : 0}`, {
        headers: { 'Accept': 'application/json' }
    })
        .then(res => res.json())
        .then(data => {
            if (data.mode === 'mine') {
                renderArchiveList('mine', data.searches || []);
            } else {
                renderArchiveList('popular', data.categories || []);
            }
        })
        .catch(() => {
            // Leave the existing list in place if the request fails.
        });
}

// DOWNLOAD FUNCTIONS
function downloadCurrentSearchBusinessesCsv() {
    if (!currentResults.length) {
        alert('No results to download yet — run a scan first.');
        return;
    }

    const headers = ['Business Name', 'Address', 'Phone', 'Website', 'Category', 'Scanned At'];
    const rows = currentResults.map(b => [
        b.name || '',
        b.address || '',
        b.phone || '',
        b.website || '',
        b.category || '',
        b.fetched_at || ''
    ]);

    const location = document.getElementById('locationInput')?.value || 'search';
    triggerCsvDownload(`businesses_${slugify(location)}.csv`, buildCsv(headers, rows));
}

function downloadBusinessLeadsCsv(idx) {
    const entry = fetchedLeadsByIdx[idx];
    if (!entry?.leads?.length) return;

    const headers = ['Name', 'Title', 'Email', 'Phone'];
    const rows = entry.leads.map(l => [
        l.name || '', 
        l.title || '', 
        l.email || '', 
        l.phone || ''
    ]);

    triggerCsvDownload(`leads_${slugify(entry.businessName)}.csv`, buildCsv(headers, rows));
}

// INITIALIZE
document.addEventListener('DOMContentLoaded', () => {
    // Load initial results
    const resultsArea = document.getElementById('resultsArea');
    if (resultsArea?.dataset.initialResults) {
        try {
            currentResults = JSON.parse(resultsArea.dataset.initialResults);
        } catch (e) {
            currentResults = [];
        }
    }

    // Form submit
    document.getElementById('scanForm')?.addEventListener('submit', () => {
        startScanRadar();
    });

    // Archive card clicks (delegated so re-rendered lists from filterUserHistory still work)
    document.getElementById('archiveList')?.addEventListener('click', (e) => {
        const card = e.target.closest('.interactive-card');
        if (!card) return;

        let results = [];
        try {
            results = JSON.parse(card.dataset.results || '[]');
        } catch (err) {
            results = [];
        }

        loadCachedQuery(card.dataset.category || '', card.dataset.location || '', results);
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeSidebar();
            closeProfileDrawer();
            closeEnrichModal();
            closeAllLeadsView();
        }
    });
});

// Make functions globally available
window.toggleSidebar = toggleSidebar;
window.closeSidebar = closeSidebar;
window.openProfileDrawer = openProfileDrawer;
window.closeProfileDrawer = closeProfileDrawer;
window.enrichLeads = enrichLeads;
window.closeEnrichModal = closeEnrichModal;
window.confirmEnrich = async () => {
    const idx = pendingEnrichIdx;
    if (idx === null) return;

    const fields = ['name'];
    if (document.getElementById('fieldTitle').checked) fields.push('title');
    if (document.getElementById('fieldEmail').checked) fields.push('email');
    if (document.getElementById('fieldPhone').checked) fields.push('phone');

    closeEnrichModal();
    await runEnrichment(idx, fields);
};

window.toggleLeadRow = toggleLeadRow;
window.openAllLeadsView = openAllLeadsView;
window.closeAllLeadsView = closeAllLeadsView;
window.downloadBusinessLeadsCsv = downloadBusinessLeadsCsv;
window.loadCachedQuery = loadCachedQuery;
window.filterUserHistory = filterUserHistory;
window.downloadCurrentSearchBusinessesCsv = downloadCurrentSearchBusinessesCsv;
// =========================================================================
// 5. DELETE LEAD EVENT CONTROLLER (GLOBAL DELEGATION)
// =========================================================================
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('resultsTableBody');

    if (tableBody) {
        tableBody.addEventListener('click', function (e) {
            // Find the closest delete link element
            const deleteLink = e.target.closest('.btn-delete');
            if (!deleteLink) return;

            e.preventDefault();

            const businessId = deleteLink.dataset.id;
            const row = deleteLink.closest('tr');

            if (!businessId) {
                alert('This business cannot be deleted because its ID is missing.');
                return;
            }

            if (!confirm('Are you sure you want to delete this business?')) {
                return;
            }

            // Fire DELETE request
            fetch(`/lead-scan/business/${businessId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken() // Uses your existing utility function
                }
            })
            .then(async response => {
                const data = await response.json();
                
                if (response.ok && data.success) {
                    // Smooth animation
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';
                    
                    setTimeout(() => {
                        // Remove the main row
                        row.remove();
                        // Remove the next row if it's a nested lead row
                        const nextRow = row.nextElementSibling;
                        if (nextRow && nextRow.classList.contains('nested-lead-row')) {
                            nextRow.remove();
                        }
                    }, 300);
                } else {
                    alert(data.message || 'Failed to delete record.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }
});
