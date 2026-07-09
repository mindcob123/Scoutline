// =========================================================================
// 1. NAVIGATION & DIALOG CONTROLLERS
// =========================================================================

/**
 * Toggles visibility states for the Sidebar Workspace Hub Menu.
 * @param {boolean} open - True to slide drawer in, false to hide it.
 */
function toggleNavDrawer(open) {
    const appNavDrawer = document.getElementById('appNavDrawer');
    const appDrawerOverlay = document.getElementById('appDrawerOverlay');

    if (!appNavDrawer || !appDrawerOverlay) {
        console.error("Error: Drawer elements could not be found in the DOM.");
        return;
    }

    if (open) {
        appNavDrawer.classList.add('open');
        appDrawerOverlay.classList.add('open');
    } else {
        appNavDrawer.classList.remove('open');
        appDrawerOverlay.classList.remove('open');
    }
}

/**
 * Triggers feedback alert notifications for user profile actions.
 */
function handleProfileAlert() {
    alert('Viewing Account Settings & Profile Data...');
}

/**
 * Fills the scan form with a saved query and submits it.
 * The dashboard now runs on a real <form method="POST"> submit
 * (server renders the results via session), so this just populates
 * the fields and triggers a normal form submission — no fetch here.
 * @param {string} category - Business niche (e.g. Logistics, Bakery)
 * @param {string} location - Geo targeting constraint (e.g. Gulberg, Lahore)
 */
function loadRecentQuery(category, location) {
    document.getElementById('categoryInput').value = category;
    document.getElementById('locationInput').value = location;
    toggleNavDrawer(false);
    document.getElementById('scanForm').submit();
}

// =========================================================================
// 2. SECURITY HELPERS
// =========================================================================

/**
 * Escapes HTML special characters so lead data from the API
 * can never inject markup or scripts into the page.
 */
function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * Reads the CSRF token Laravel injects into the page head.
 * Needed on every POST/PUT/DELETE fetch call.
 */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

// =========================================================================
// 3. APOLLO LEAD ENRICHMENT PIPELINE
// =========================================================================

/**
 * Requests Apollo contact data for a single business row.
 *
 * -------------------------------------------------------------------
 * BACKEND CONTRACT (see EnrichmentController::enrich):
 *
 *   POST {resultsArea.dataset.enrichUrl}
 *   Body (JSON): { "name": string, "website": string|null }
 *
 *   Response JSON (200):
 *   { "leads": [ { "name": string, "title": string, "email": string }, ... ] }
 * -------------------------------------------------------------------
 *
 * @param {number} idx - Row index, matches fetchBtn-{idx} / leadRow-{idx}.
 */
function enrichLeads(idx) {
    const btn = document.getElementById(`fetchBtn-${idx}`);
    const targetRow = document.getElementById(`leadRow-${idx}`);
    const wrapper = document.getElementById(`leadWrapper-${idx}`);

    if (btn.classList.contains('active-enrich')) return;

    const businessName = btn.dataset.businessName;
    const businessWebsite = btn.dataset.businessWebsite;

    if (!businessWebsite) {
        wrapper.innerHTML = `<div class="text-muted-color" style="font-size: 0.85rem; padding: 4px 0;">No website on file — Apollo enrichment needs a domain to search against.</div>`;
        targetRow.style.display = 'table-row';
        return;
    }

    btn.disabled = true;
    btn.innerText = 'Connecting Apollo...';

    const enrichUrl = document.getElementById('resultsArea').dataset.enrichUrl;

    fetch(enrichUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ name: businessName, website: businessWebsite }),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`Enrichment request failed with status ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            const leads = data.leads || [];

            btn.innerText = 'Enriched';
            btn.style.borderColor = 'rgba(242, 169, 59, 0.3)';
            btn.style.color = 'var(--primary-gold)';
            btn.classList.add('active-enrich');

            if (leads.length === 0) {
                wrapper.innerHTML = `<div class="text-muted-color" style="font-size: 0.85rem; padding: 4px 0;">No workspace roster records discovered via secondary enrichment maps.</div>`;
                targetRow.style.display = 'table-row';
                return;
            }

            wrapper.innerHTML = leads.map((lead) => `
                <div class="lead-card nested-card">
                    <div class="lead-name">${escapeHtml(lead.name) || 'Unknown'}</div>
                    <div class="lead-title specialty-color">${escapeHtml(lead.title) || ''}</div>
                    <div class="lead-contact-value">${escapeHtml(lead.email) || 'No email on file'}</div>
                    <div class="source-tag source-apollo">verified workspace</div>
                </div>
            `).join('');

            targetRow.style.display = 'table-row';
        })
        .catch((err) => {
            console.error('Enrichment failed:', err);
            btn.disabled = false;
            btn.innerText = 'Retry Enrich';
        });
}