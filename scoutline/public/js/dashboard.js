
/**
 * Requests Apollo contact data for a single business row.
 * Updates to align perfectly with Scoutline Laravel/FastAPI backend schema contracts.
 * * @param {number} idx .
 */
function enrichLeads(idx) {
    const btn = document.getElementById(`fetchBtn-${idx}`);
    const targetRow = document.getElementById(`leadRow-${idx}`);
    const wrapper = document.getElementById(`leadWrapper-${idx}`);

    if (btn.classList.contains('active-enrich')) return;

    const businessName = btn.dataset.businessName;        // Read values assigned to the HTML button data attributes
    const businessWebsite = btn.dataset.businessWebsite;

    btn.disabled = true;
    btn.innerText = 'Connecting Apollo...';

    const enrichUrl = document.getElementById('resultsArea').dataset.enrichUrl;

    // MATCH BACKEND EXPECTED SCHEMAS: company_name and domain
    fetch(enrichUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ 
            company_name: businessName, 
            domain: businessWebsite || null // Send fallback null instead of blocking the user completely
        }),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`Enrichment request failed with status ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            // MATCH BACKEND VALUE: The service engine passes 'employees', not 'leads'
            const employees = data.employees || [];

            btn.innerText = 'Enriched';
            btn.style.borderColor = 'rgba(242, 169, 59, 0.3)';
            btn.style.color = 'var(--primary-gold)';
            btn.classList.add('active-enrich');

            if (employees.length === 0) {
                wrapper.innerHTML = `<div class="text-muted-color" style="font-size: 0.85rem; padding: 4px 0;">No workspace roster records discovered via secondary enrichment maps.</div>`;
                targetRow.style.display = 'table-row';
                return;
            }

            // Maps cleanly to your pre-styled layout tokens, pulling from parsed employee objects
            wrapper.innerHTML = employees.map((person) => `
                <div class="lead-card nested-card">
                    <div class="lead-name">${escapeHtml(person.name) || 'Unknown'}</div>
                    <div class="lead-title specialty-color">${escapeHtml(person.title) || 'Executive Decision Maker'}</div>
                    <div class="lead-contact-value">✉ ${escapeHtml(person.email) || 'No email on file'}</div>
                    <div class="lead-contact-value" style="margin-top: 2px; font-size: 0.8rem; color: #a1a1aa;">☎ ${escapeHtml(person.contact_number) || 'N/A'}</div>
                    <div class="source-tag source-apollo">verified workspace</div>
                </div>
            `).join('');

            targetRow.style.display = 'table-row';
        })
        .catch((err) => {
            console.error('Enrichment failed:', err);
            btn.disabled = false;
            btn.innerText = 'Retry Enrich';
            wrapper.innerHTML = `<div style="color: #ef4444; font-size: 0.85rem; padding: 4px 0;">Extraction Sequence Faulted. Please try again.</div>`;
            targetRow.style.display = 'table-row';
        });
}