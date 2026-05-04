<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$current_year  = gmdate( 'Y' );
$cv_name       = 'Edris Husein';
$cv_address    = get_option( 'work_os_cv_address', 'Mitteldorfgasse 1a, 6850 Dornbirn' );
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link
    href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;700&display=swap"
    rel="stylesheet">
<style>
.wo-ea-grid3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

.wo-ea-stat {
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    padding: 20px 24px;
    text-align: center;
}

.wo-ea-stat-val {
    font-size: 28px;
    font-weight: 700;
    line-height: 1.2;
}

.wo-ea-stat-lbl {
    font-size: 12px;
    color: #646970;
    margin-top: 4px;
}

.wo-ea-cat-row td {
    background: #f8f9fa !important;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #646970;
    padding: 6px 10px !important;
}

.wo-ea-inline-input {
    font: inherit;
    border: 1px solid #2271b1;
    border-radius: 3px;
    padding: 2px 6px;
    width: 90px;
    text-align: right;
    font-size: 13px;
}

.wo-ea-cat-select {
    font: inherit;
    font-size: 12px;
    padding: 2px 4px;
    border: 1px solid #dcdcde;
    border-radius: 3px;
}

.wo-ea-tabs {
    display: flex;
    gap: 0;
    margin-bottom: 20px;
    border-bottom: 1px solid #dcdcde;
}

.wo-ea-tab {
    padding: 8px 18px;
    font-size: 13px;
    font-weight: 500;
    color: #646970;
    cursor: pointer;
    border: 1px solid transparent;
    border-bottom: none;
    border-radius: 4px 4px 0 0;
    margin-bottom: -1px;
    background: none;
}

.wo-ea-tab.is-active {
    background: #fff;
    border-color: #dcdcde;
    color: #1d2327;
}

.wo-ea-quick {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}

.wo-ea-add-form {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}

.wo-ea-warn {
    background: #fff8e1;
    border: 1px solid #f0c040;
    border-radius: 4px;
    padding: 10px 14px;
    font-size: 13px;
    color: #856404;
    margin-bottom: 16px;
}

/* ── Print view (hidden in browser, visible on print) ── */
#wo-ea-print-view {
    display: none;
}

@media print {
    @page {
        size: A4;
        margin: 0;
    }

    @page :first {
        margin: 0;
    }

    #wpadminbar,
    #adminmenuwrap,
    #adminmenuback,
    #wpfooter,
    .wp-footer-content,
    .wo-ea-controls,
    .wo-ea-tabs,
    .wo-ea-no-print,
    .wp-header-end,
    h1.wp-heading-inline,
    .wo-ea-add-form,
    .wo-ea-quick,
    #wo-ea-tab-report,
    #wo-ea-tab-mappings,
    .wrap>hr,
    .notice {
        display: none !important;
    }

    body,
    html,
    #wpcontent,
    #wpbody,
    #wpbody-content,
    .wrap {
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
        overflow: visible !important;
        height: auto !important;
        max-height: none !important;
        width: auto !important;
    }

    #wpcontent {
        margin-left: 0 !important;
        padding: 0 !important;
    }

    #wpbody-content {
        padding: 0 !important;
    }

    #wo-ea-print-view {
        display: block !important;
        overflow: visible !important;
    }

    /* ══════════════════════════════════════════════
	   COVER PAGE
	   ══════════════════════════════════════════════ */
    .ea-cover {
        width: 210mm;
        height: 297mm;
        box-sizing: border-box;
        overflow: hidden;
        break-after: page;
        page-break-after: always;
        display: flex;
        flex-direction: row;
    }

    /* Empty left space */
    .ea-cover-left {
        flex: 1;
    }

    /* Right column */
    .ea-cover-col {
        width: 58mm;
        box-sizing: border-box;
        padding: 12mm 10mm 14mm 0;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    /* Clip wrapper — normal-flow element that constrains the vertical text */
    .ea-cover-watermark-clip {
        height: 250mm;
        width: 100%;
        flex-shrink: 0;
    }

    /* Vertical watermark text — two columns via <br>: "Einnahmen-" + "Ausgabenrechnung" */
    .ea-cover-bg-text {
        font-family: 'EB Garamond', Georgia, serif;
        font-size: 60pt;
        line-height: 1.5;
        font-weight: 700;
        color: #c8c8c8;
        writing-mode: vertical-rl;
        white-space: normal;
        word-break: normal;
        display: block;
        width: 100%;
    }

    /* Info block */
    .ea-cover-info {
        text-align: right;
        flex-shrink: 0;
        margin-top: auto;
    }

    .ea-cover-doc-title {
        font-family: 'EB Garamond', Georgia, serif;
        font-size: 15pt;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
        margin-bottom: 3pt;
    }

    .ea-cover-year {
        font-family: 'EB Garamond', Georgia, serif;
        font-size: 34pt;
        font-weight: 700;
        color: #0f172a;
        line-height: 1;
        margin-bottom: 6pt;
        display: block;
    }

    .ea-cover-name {
        font-family: 'Lato', Arial, sans-serif;
        font-size: 12pt;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4pt;
        display: block;
    }

    .ea-cover-address {
        font-family: 'Lato', Arial, sans-serif;
        font-size: 10pt;
        color: #555;
        margin-bottom: 12pt;
        display: block;
    }

    .ea-cover-rule {
        border: none;
        border-top: 0.75pt solid #aaa;
        margin: 0;
        width: 100%;
    }

    /* ══════════════════════════════════════════════
	   DATA PAGES
	   ══════════════════════════════════════════════ */
    .ea-doc {
        font-family: 'Lato', Arial, sans-serif;
        font-size: 11pt;
        color: #0f172a;
        line-height: 1.6;
        padding: 2cm 2.2cm;
        box-sizing: border-box;
    }

    /* ── Header ── */
    .ea-doc-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 6pt;
    }

    .ea-header-left {
        font-size: 10.5pt;
        line-height: 1.5;
    }

    .ea-header-name-line {
        font-weight: 700;
    }

    .ea-header-addr-line {
        color: #555;
        font-size: 9.5pt;
    }

    .ea-header-right {
        text-align: right;
    }

    .ea-doc-title {
        font-family: 'EB Garamond', Georgia, serif;
        font-size: 13pt;
        font-weight: 700;
        margin: 0;
    }

    .ea-doc-period {
        font-size: 10pt;
        color: #444;
        margin-top: 2pt;
    }

    .ea-header-rule {
        border: none;
        border-top: 1pt solid #000;
        margin: 10pt 0 8pt;
    }

    /* ── Column year/EUR labels ── */
    .ea-col-headers {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 2pt;
    }

    .ea-col-right {
        text-align: right;
        width: 130pt;
        font-size: 10pt;
        color: #555;
    }

    /* ── Summary table ── */
    .ea-summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4pt;
    }

    .ea-summary-table td {
        padding: 2.5pt 0;
        vertical-align: top;
    }

    .ea-section-head td {
        font-family: 'EB Garamond', Georgia, serif;
        font-weight: 700;
        font-size: 12pt;
        padding-top: 12pt;
        padding-bottom: 2pt;
    }

    .ea-cat-row td.ea-cat-name {
        padding-left: 22pt;
        font-size: 10.5pt;
    }

    .ea-cat-amount {
        text-align: right;
        width: 130pt;
        font-variant-numeric: tabular-nums;
        font-size: 10.5pt;
        white-space: nowrap;
    }

    .ea-subtotal-row td {
        padding-top: 1pt;
        padding-bottom: 6pt;
    }

    .ea-subtotal {
        font-weight: 700;
        border-top: 0.75pt solid #0f172a;
        padding-top: 3pt !important;
    }

    .ea-spacer td {
        height: 10pt;
    }

    .ea-gewinn-row td {
        font-family: 'EB Garamond', Georgia, serif;
        font-weight: 700;
        font-size: 13pt;
        padding-top: 8pt;
        border-top: 1.5pt solid #0f172a;
    }

    .ea-gewinn-amount {
        border-top: 1.5pt solid #0f172a;
    }
}
</style>

<div class="wrap">
    <h1 class="wp-heading-inline">Work OS — E/A Bericht</h1>
    <hr class="wp-header-end">

    <div class="wo-ea-controls" style="display:flex;gap:10px;align-items:center;margin:16px 0 20px">
        <label style="font-size:13px;color:#646970">Von</label>
        <input type="date" id="wo-ea-start" class="regular-text" style="width:150px"
            value="<?php echo esc_attr( $current_year . '-01-01' ); ?>">
        <label style="font-size:13px;color:#646970">bis</label>
        <input type="date" id="wo-ea-end" class="regular-text" style="width:150px"
            value="<?php echo esc_attr( $current_year . '-12-31' ); ?>">
        <button id="wo-ea-load-btn" class="button button-primary">Laden</button>
        <button id="wo-ea-print-btn" class="button" style="display:none">Drucken / PDF</button>
        <span id="wo-ea-status" style="font-size:13px;color:#646970;margin-left:4px"></span>
    </div>

    <div class="wo-ea-tabs wo-ea-no-print">
        <button class="wo-ea-tab is-active" data-tab="report">Bericht</button>
        <button class="wo-ea-tab" data-tab="mappings">Kategorien</button>
    </div>

    <div id="wo-ea-tab-report">
        <div id="wo-ea-empty"
            style="background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:40px;text-align:center;color:#646970">
            Zeitraum auswählen und auf <strong>Laden</strong> klicken.
        </div>

        <div id="wo-ea-report-content" style="display:none">
            <div class="wo-ea-grid3" id="wo-ea-summary"></div>
            <div id="wo-ea-warnings"></div>

            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle">Erträge / Betriebseinnahmen</h2>
                </div>
                <div class="inside" style="padding:0">
                    <table class="widefat" id="wo-ea-einnahmen-table">
                        <thead>
                            <tr>
                                <th style="width:100px">Datum</th>
                                <th>Beschreibung</th>
                                <th style="width:120px" class="wo-ea-no-print">Original</th>
                                <th style="width:120px;text-align:right">EUR</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="font-weight:700;text-align:right;padding-right:10px">Summe
                                    Einnahmen</td>
                                <td id="wo-ea-total-e" style="font-weight:700;text-align:right"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle">Ausgaben</h2>
                </div>
                <div class="inside" style="padding:0">
                    <table class="widefat" id="wo-ea-ausgaben-table">
                        <thead>
                            <tr>
                                <th style="width:100px">Datum</th>
                                <th>Beschreibung</th>
                                <th style="width:160px" class="wo-ea-no-print">Kategorie</th>
                                <th style="width:120px;text-align:right">EUR</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="font-weight:700;text-align:right;padding-right:10px">Summe
                                    Ausgaben</td>
                                <td id="wo-ea-total-a" style="font-weight:700;text-align:right"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="postbox wo-ea-no-print">
                <div class="postbox-header">
                    <h2 class="hndle">Manuelle Einträge</h2>
                </div>
                <div class="inside">
                    <div class="wo-ea-quick">
                        <button class="button wo-ea-quick-btn" data-desc="Abschreibung PKW" data-amount="0"
                            data-cat="abschreibungen" data-type="ausgabe">+ Abschreibung PKW</button>
                        <button class="button wo-ea-quick-btn" data-desc="KFZ-Versicherung" data-amount="0"
                            data-cat="kfz_kosten" data-type="ausgabe">+ KFZ-Versicherung</button>
                        <button class="button wo-ea-quick-btn" data-desc="Büro Miete" data-amount="250" data-cat="miete"
                            data-type="ausgabe">+ Miete (250 €)</button>
                    </div>
                    <table class="widefat" id="wo-ea-manual-table" style="display:none">
                        <thead>
                            <tr>
                                <th>Beschreibung</th>
                                <th style="width:80px">Typ</th>
                                <th>Kategorie</th>
                                <th style="width:120px;text-align:right">EUR</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div class="wo-ea-add-form">
                        <input type="text" id="wo-ea-m-desc" class="regular-text" placeholder="Beschreibung"
                            style="flex:1;min-width:160px">
                        <input type="text" id="wo-ea-m-amount" class="small-text" placeholder="Betrag"
                            style="width:90px">
                        <select id="wo-ea-m-cat" style="font-size:13px"></select>
                        <select id="wo-ea-m-type" style="font-size:13px">
                            <option value="ausgabe">Ausgabe</option>
                            <option value="einnahme">Einnahme</option>
                        </select>
                        <button id="wo-ea-m-add-btn" class="button button-primary">Hinzufügen</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="wo-ea-print-view"></div>

    <div id="wo-ea-tab-mappings" style="display:none">
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle">Kategorie-Regeln</h2>
            </div>
            <div class="inside">
                <p style="color:#646970;font-size:13px;margin-bottom:16px">
                    Jede Ausgabe wird anhand des Vendor-Namens kategorisiert (Teilstring, Groß-/Kleinschreibung egal).
                    Höhere Priorität = zuerst geprüft.
                </p>
                <table class="widefat striped" id="wo-ea-mappings-table">
                    <thead>
                        <tr>
                            <th>Muster</th>
                            <th>Kategorie</th>
                            <th style="width:80px;text-align:right">Priorität</th>
                            <th style="width:60px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" style="color:#646970">Wird geladen…</td>
                        </tr>
                    </tbody>
                </table>
                <div class="wo-ea-add-form" style="margin-top:16px;padding-top:16px;border-top:1px solid #dcdcde">
                    <input type="text" id="wo-ea-new-pattern" class="regular-text" placeholder="Muster (z.B. sparkasse)"
                        style="width:200px">
                    <select id="wo-ea-new-cat" style="font-size:13px"></select>
                    <input type="number" id="wo-ea-new-prio" class="small-text" value="50" style="width:70px">
                    <button id="wo-ea-mapping-add-btn" class="button button-primary">Regel anlegen</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const cfg = window.workOsConfig;
    const api = cfg.apiUrl;
    const hdr = {
        'Content-Type': 'application/json',
        'X-WP-Nonce': cfg.nonce
    };
    const CV_NAME = <?php echo json_encode( $cv_name, JSON_HEX_TAG | JSON_HEX_AMP ); ?>;
    const CV_ADDRESS = <?php echo json_encode( $cv_address, JSON_HEX_TAG | JSON_HEX_AMP ); ?>;

    const fmtEur = n => Number(n || 0).toLocaleString('de-AT', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' €';

    function safeText(s) {
        return String(s || '');
    }

    // All dynamic content is escaped with this before DOM insertion
    function escHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Safely set HTML of element — all caller sites escape dynamic values via escHtml()
    function setHtml(el, html) {
        el.innerHTML = html;
    }

    let report = null,
        edits = {},
        catOverrides = {},
        manualEntries = [];
    let categoryLabels = {};

    const STORAGE_KEY = 'wo_ea_state_v1';

    function saveState() {
        if (!report) return;
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                report,
                edits,
                catOverrides,
                manualEntries,
                start: document.getElementById('wo-ea-start').value,
                end: document.getElementById('wo-ea-end').value,
            }));
        } catch (e) {}
    }

    function loadState() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return false;
            const state = JSON.parse(raw);
            if (!state.report) return false;
            report = state.report;
            edits = state.edits || {};
            catOverrides = state.catOverrides || {};
            manualEntries = state.manualEntries || [];
            if (state.start) document.getElementById('wo-ea-start').value = state.start;
            if (state.end) document.getElementById('wo-ea-end').value = state.end;
            return true;
        } catch (e) {
            return false;
        }
    }

    const DEFAULT_CATS = {
        betriebseinnahmen: 'Erträge/Betriebseinnahmen',
        uebrige_ertraege: 'übrige Erträge/Betriebseinnahmen',
        beigestelltes_personal: 'Beigestelltes Personal und Fremdleistungen',
        abschreibungen: 'Abschreibungen auf das Anlagevermögen',
        kfz_kosten: 'KFZ-Kosten',
        miete: 'Miete',
        rechtsberatung: 'Rechtsberatung',
        werbung: 'Werbung',
        zinsen: 'Zinsen und ähnliche Ausgaben',
        eigene_pflichtversicherung: 'eigene Pflichtversicherungsbeiträge',
        uebrige_ausgaben: 'übrige Ausgaben',
    };
    const ausgabenKeys = Object.keys(DEFAULT_CATS).filter(k => k !== 'betriebseinnahmen' && k !==
        'uebrige_ertraege');

    function setStatus(msg, color) {
        const el = document.getElementById('wo-ea-status');
        el.textContent = msg;
        el.style.color = color || '#646970';
    }

    function getAmount(line) {
        return edits[line.id] !== undefined ? edits[line.id] : line.amount_eur;
    }

    function computeTotals() {
        if (!report) return {
            sumE: 0,
            sumA: 0
        };
        const sumE = report.einnahmen.reduce((s, l) => s + (parseFloat(getAmount(l)) || 0), 0) +
            manualEntries.filter(e => e.type === 'einnahme').reduce((s, e) => s + (parseFloat(e.amount_eur) || 0),
                0);
        const sumA = report.ausgaben.reduce((s, l) => s + (parseFloat(getAmount(l)) || 0), 0) +
            manualEntries.filter(e => e.type === 'ausgabe').reduce((s, e) => s + (parseFloat(e.amount_eur) || 0),
                0);
        return {
            sumE,
            sumA
        };
    }

    function refreshAndSave() {
        refreshTotals();
        saveState();
    }

    function refreshTotals() {
        const {
            sumE,
            sumA
        } = computeTotals();
        const gewinn = sumE - sumA;
        const eEl = document.getElementById('wo-ea-total-e');
        const aEl = document.getElementById('wo-ea-total-a');
        if (eEl) eEl.textContent = fmtEur(sumE);
        if (aEl) aEl.textContent = fmtEur(sumA);
        const sum = document.getElementById('wo-ea-summary');
        if (sum) {
            // All values here are numeric (from fmtEur) — safe to compose HTML
            const eV = escHtml(fmtEur(sumE));
            const aV = escHtml(fmtEur(sumA));
            const gV = escHtml(fmtEur(gewinn));
            const gC = gewinn >= 0 ? '#2271b1' : '#cc1818';
            setHtml(sum,
                '<div class="wo-ea-stat"><div class="wo-ea-stat-val" style="color:#00a32a">' + eV +
                '</div><div class="wo-ea-stat-lbl">Einnahmen</div></div>' +
                '<div class="wo-ea-stat"><div class="wo-ea-stat-val" style="color:#cc1818">' + aV +
                '</div><div class="wo-ea-stat-lbl">Ausgaben</div></div>' +
                '<div class="wo-ea-stat"><div class="wo-ea-stat-val" style="color:' + gC + '">' + gV +
                '</div><div class="wo-ea-stat-lbl">Gewinn / Verlust</div></div>'
            );
        }
    }

    function buildEinnahmenRow(l) {
        const amount = getAmount(l);
        const origCell = (l.currency_orig && l.currency_orig !== 'EUR') ?
            '<td class="wo-ea-no-print" style="font-size:12px;color:#646970">' + escHtml(fmtEur(l.amount_orig)
                .replace(' €', '')) + ' ' + escHtml(l.currency_orig) + '</td>' :
            '<td class="wo-ea-no-print" style="color:#ccc">—</td>';
        return '<tr data-id="' + escHtml(l.id) + '">' +
            '<td style="white-space:nowrap;font-size:12px;color:#646970">' + escHtml((l.date || '').slice(0, 10)) +
            '</td>' +
            '<td>' + escHtml(l.description) + '</td>' +
            origCell +
            '<td style="text-align:right"><span class="wo-ea-editable" data-id="' + escHtml(l.id) +
            '" style="cursor:pointer;border-bottom:1px dashed #999">' + escHtml(fmtEur(amount)) + '</span></td>' +
            '</tr>';
    }

    function renderEinnahmen() {
        const tbody = document.querySelector('#wo-ea-einnahmen-table tbody');
        if (!report || !report.einnahmen.length) {
            setHtml(tbody,
                '<tr><td colspan="4" style="color:#646970;padding:16px">Keine Einnahmen im Zeitraum.</td></tr>');
            return;
        }
        setHtml(tbody, report.einnahmen.map(buildEinnahmenRow).join(''));
        tbody.querySelectorAll('.wo-ea-editable').forEach(span => {
            span.addEventListener('click', function() {
                startEdit(this);
            });
        });
    }

    function buildAusgabenRows(rows, cats) {
        const grouped = {};
        for (const l of rows) {
            const cat = catOverrides[l.id] || l.category;
            if (!grouped[cat]) grouped[cat] = [];
            grouped[cat].push(l);
        }
        const parts = [];
        for (const cat of ausgabenKeys) {
            const lines = grouped[cat];
            if (!lines || !lines.length) continue;
            const catTotal = lines.reduce((s, l) => s + (parseFloat(getAmount(l)) || 0), 0);
            parts.push('<tr class="wo-ea-cat-row"><td colspan="3">' + escHtml(cats[cat] || cat) +
                '</td><td style="text-align:right;font-weight:700">' + escHtml(fmtEur(catTotal)) + '</td></tr>');
            for (const l of lines) {
                const sel = ausgabenKeys.map(k =>
                    '<option value="' + escHtml(k) + '"' + ((catOverrides[l.id] || l.category) === k ?
                        ' selected' : '') + '>' + escHtml(cats[k] || k) + '</option>'
                ).join('');
                parts.push('<tr>' +
                    '<td style="white-space:nowrap;font-size:12px;color:#646970">' + escHtml((l.date || '')
                        .slice(0, 10)) + '</td>' +
                    '<td>' + escHtml(l.description) + '</td>' +
                    '<td class="wo-ea-no-print"><select class="wo-ea-cat-select wo-ea-cat-change" data-id="' +
                    escHtml(l.id) + '">' + sel + '</select></td>' +
                    '<td style="text-align:right"><span class="wo-ea-editable" data-id="' + escHtml(l.id) +
                    '" style="cursor:pointer;border-bottom:1px dashed #999">' + escHtml(fmtEur(getAmount(l))) +
                    '</span></td>' +
                    '</tr>');
            }
        }
        for (const e of manualEntries.filter(e => e.type === 'ausgabe')) {
            parts.push('<tr>' +
                '<td style="font-size:12px;color:#646970">—</td>' +
                '<td><em>' + escHtml(e.description) + '</em></td>' +
                '<td class="wo-ea-no-print" style="font-size:12px;color:#646970">' + escHtml(cats[e.category] ||
                    e.category) + '</td>' +
                '<td style="text-align:right">' + escHtml(fmtEur(e.amount_eur)) + '</td>' +
                '</tr>');
        }
        return parts.join('');
    }

    function renderAusgaben() {
        const cats = categoryLabels || DEFAULT_CATS;
        const tbody = document.querySelector('#wo-ea-ausgaben-table tbody');
        if (!report || !report.ausgaben.length) {
            setHtml(tbody,
                '<tr><td colspan="4" style="color:#646970;padding:16px">Keine Ausgaben im Zeitraum.</td></tr>');
            return;
        }
        setHtml(tbody, buildAusgabenRows(report.ausgaben, cats));
        tbody.querySelectorAll('.wo-ea-editable').forEach(span => {
            span.addEventListener('click', function() {
                startEdit(this);
            });
        });
        tbody.querySelectorAll('.wo-ea-cat-change').forEach(sel => {
            sel.addEventListener('change', function() {
                catOverrides[this.dataset.id] = this.value;
                renderAusgaben();
                refreshAndSave();
            });
        });
    }

    function startEdit(span) {
        const id = span.dataset.id;
        const cur = (edits[id] !== undefined ? edits[id] : parseFloat(span.textContent.replace(/[^\d,.-]/g, '')
            .replace(',', '.'))).toString();
        const inp = document.createElement('input');
        inp.type = 'text';
        inp.value = cur;
        inp.className = 'wo-ea-inline-input';
        span.replaceWith(inp);
        inp.focus();
        inp.select();
        inp.addEventListener('blur', () => commitEdit(inp, id));
        inp.addEventListener('keydown', e => {
            if (e.key === 'Enter') inp.blur();
            if (e.key === 'Escape') {
                inp.value = cur;
                inp.blur();
            }
        });
    }

    function commitEdit(inp, id) {
        const n = parseFloat(inp.value.replace(',', '.'));
        if (!isNaN(n)) edits[id] = n;
        renderEinnahmen();
        renderAusgaben();
        refreshAndSave();
    }

    function renderManualTable() {
        const table = document.getElementById('wo-ea-manual-table');
        const tbody = table.querySelector('tbody');
        if (!manualEntries.length) {
            table.style.display = 'none';
            return;
        }
        table.style.display = '';
        const cats = categoryLabels || DEFAULT_CATS;
        setHtml(tbody, manualEntries.map(e =>
            '<tr>' +
            '<td>' + escHtml(e.description) + '</td>' +
            '<td style="font-size:12px;color:' + (e.type === 'einnahme' ? '#00a32a' : '#cc1818') + '">' + (e
                .type === 'einnahme' ? 'Einnahme' : 'Ausgabe') + '</td>' +
            '<td style="font-size:12px;color:#646970">' + escHtml(cats[e.category] || e.category) +
            '</td>' +
            '<td style="text-align:right">' + escHtml(fmtEur(e.amount_eur)) + '</td>' +
            '<td><button class="button button-small wo-ea-remove-manual" data-id="' + escHtml(e.id) +
            '" style="color:#cc1818;border-color:#cc181833;padding:0 6px">×</button></td>' +
            '</tr>'
        ).join(''));
        tbody.querySelectorAll('.wo-ea-remove-manual').forEach(btn => {
            btn.addEventListener('click', function() {
                manualEntries = manualEntries.filter(e => e.id !== this.dataset.id);
                renderManualTable();
                refreshAndSave();
            });
        });
    }

    document.querySelectorAll('.wo-ea-quick-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            manualEntries.push({
                id: 'manual-' + Date.now(),
                description: safeText(this.dataset.desc),
                amount_eur: parseFloat(this.dataset.amount) || 0,
                category: safeText(this.dataset.cat),
                type: safeText(this.dataset.type),
            });
            renderManualTable();
            refreshAndSave();
        });
    });

    document.getElementById('wo-ea-m-add-btn').addEventListener('click', function() {
        const desc = document.getElementById('wo-ea-m-desc').value.trim();
        const amount = parseFloat(document.getElementById('wo-ea-m-amount').value.replace(',', '.'));
        const cat = document.getElementById('wo-ea-m-cat').value;
        const type = document.getElementById('wo-ea-m-type').value;
        if (!desc || isNaN(amount) || amount <= 0) return;
        manualEntries.push({
            id: 'manual-' + Date.now(),
            description: desc,
            amount_eur: amount,
            category: cat,
            type
        });
        document.getElementById('wo-ea-m-desc').value = '';
        document.getElementById('wo-ea-m-amount').value = '';
        renderManualTable();
        refreshAndSave();
    });

    function populateCatSelects(cats) {
        const opts = ausgabenKeys.map(k => '<option value="' + escHtml(k) + '">' + escHtml(cats[k] || k) +
            '</option>').join('');
        setHtml(document.getElementById('wo-ea-m-cat'), opts);
        setHtml(document.getElementById('wo-ea-new-cat'), opts);
    }

    function buildPrintView() {
        const pv = document.getElementById('wo-ea-print-view');
        const cats = categoryLabels || DEFAULT_CATS;
        const {
            sumE,
            sumA
        } = computeTotals();
        const gewinn = sumE - sumA;
        const start = document.getElementById('wo-ea-start').value;
        const end = document.getElementById('wo-ea-end').value;
        const year = start ? start.split('-')[0] : '';

        const MONTHS = ['', 'Jänner', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September',
            'Oktober', 'November', 'Dezember'
        ];

        function fmtDateLong(d) {
            if (!d) return '';
            const [y, m, day] = d.split('-');
            return parseInt(day, 10) + '. ' + MONTHS[parseInt(m, 10)] + ' ' + y;
        }

        // ── Category totals ────────────────────────────────────────────
        const eCatTotals = {
            betriebseinnahmen: 0,
            uebrige_ertraege: 0
        };
        if (report && report.einnahmen) {
            for (const l of report.einnahmen) {
                const cat = l.category || 'betriebseinnahmen';
                eCatTotals[cat] = (eCatTotals[cat] || 0) + (parseFloat(getAmount(l)) || 0);
            }
        }
        for (const e of manualEntries.filter(e => e.type === 'einnahme')) {
            const cat = (e.category in eCatTotals) ? e.category : 'uebrige_ertraege';
            eCatTotals[cat] = (eCatTotals[cat] || 0) + (parseFloat(e.amount_eur) || 0);
        }

        const aCatTotals = {};
        if (report && report.ausgaben) {
            for (const l of report.ausgaben) {
                const cat = catOverrides[l.id] || l.category;
                aCatTotals[cat] = (aCatTotals[cat] || 0) + (parseFloat(getAmount(l)) || 0);
            }
        }
        for (const e of manualEntries.filter(e => e.type === 'ausgabe')) {
            aCatTotals[e.category] = (aCatTotals[e.category] || 0) + (parseFloat(e.amount_eur) || 0);
        }

        // ── Table rows ────────────────────────────────────────────────
        let einnahmenRows = '';
        for (const cat of ['betriebseinnahmen', 'uebrige_ertraege']) {
            if (!eCatTotals[cat]) continue;
            einnahmenRows += '<tr class="ea-cat-row"><td class="ea-cat-name">' + escHtml(cats[cat] || cat) +
                '</td>' +
                '<td class="ea-cat-amount">' + escHtml(fmtEur(eCatTotals[cat])) + '</td></tr>';
        }

        let ausgabenRows = '';
        for (const cat of ausgabenKeys) {
            if (!aCatTotals[cat]) continue;
            ausgabenRows += '<tr class="ea-cat-row"><td class="ea-cat-name">' + escHtml(cats[cat] || cat) +
                '</td>' +
                '<td class="ea-cat-amount">' + escHtml(fmtEur(aCatTotals[cat])) + '</td></tr>';
        }

        // ── Cover page ────────────────────────────────────────────────
        const cover =
            '<div class="ea-cover">' +
            '<div class="ea-cover-left"></div>' +
            '<div class="ea-cover-col">' +
            '<div class="ea-cover-watermark-clip">' +
            '<div class="ea-cover-bg-text">Einnahmen-<br>Ausgabenrechnung</div>' +
            '</div>' +
            '<div class="ea-cover-info">' +
            '<div class="ea-cover-year">' + escHtml(year) + '</div>' +
            '<div class="ea-cover-name">' + escHtml(CV_NAME) + '</div>' +
            '<div class="ea-cover-address">' + escHtml(CV_ADDRESS) + '</div>' +
            '<hr class="ea-cover-rule">' +
            '</div>' +
            '</div>' +
            '</div>';

        // ── Data page ─────────────────────────────────────────────────
        const data =
            '<div class="ea-doc">' +
            '<div class="ea-doc-header">' +
            '<div class="ea-header-left">' +
            '<div class="ea-header-name-line">' + escHtml(CV_NAME) + '</div>' +
            '<div class="ea-header-addr-line">' + escHtml(CV_ADDRESS) + '</div>' +
            '</div>' +
            '<div class="ea-header-right">' +
            '<div class="ea-doc-title">Einnahmen-Ausgabenrechnung</div>' +
            '<div class="ea-doc-period">' + escHtml(fmtDateLong(start)) + ' bis ' + escHtml(fmtDateLong(end)) +
            '</div>' +
            '</div></div>' +
            '<hr class="ea-header-rule">' +
            '<div class="ea-col-headers">' +
            '<div class="ea-col-right">' + escHtml(year) + '<br>EUR</div>' +
            '</div>' +
            '<table class="ea-summary-table"><tbody>' +
            '<tr class="ea-section-head"><td>Einnahmen</td><td></td></tr>' +
            einnahmenRows +
            '<tr class="ea-subtotal-row"><td></td><td class="ea-cat-amount ea-subtotal">' + escHtml(fmtEur(sumE)) +
            '</td></tr>' +
            '<tr class="ea-spacer"><td colspan="2"></td></tr>' +
            '<tr class="ea-section-head"><td>Ausgaben</td><td></td></tr>' +
            ausgabenRows +
            '<tr class="ea-subtotal-row"><td></td><td class="ea-cat-amount ea-subtotal">' + escHtml(fmtEur(sumA)) +
            '</td></tr>' +
            '<tr class="ea-spacer"><td colspan="2"></td></tr>' +
            '<tr class="ea-gewinn-row"><td>Gewinn</td><td class="ea-cat-amount ea-gewinn-amount">' + escHtml(fmtEur(
                gewinn)) + '</td></tr>' +
            '</tbody></table>' +
            '</div>';

        setHtml(pv, cover + data);
    }

    document.getElementById('wo-ea-load-btn').addEventListener('click', async function() {
        const start = document.getElementById('wo-ea-start').value;
        const end = document.getElementById('wo-ea-end').value;
        if (!start || !end) {
            setStatus('Zeitraum auswählen.', '#cc1818');
            return;
        }
        this.disabled = true;
        setStatus('Lädt FastBill-Daten…', '#646970');
        document.getElementById('wo-ea-empty').style.display = 'none';
        document.getElementById('wo-ea-report-content').style.display = 'none';
        edits = {};
        catOverrides = {};
        manualEntries = [];

        try {
            const res = await fetch(api + '/ea/report', {
                method: 'POST',
                headers: hdr,
                body: JSON.stringify({
                    start_date: start,
                    end_date: end
                })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'API-Fehler');
            report = data;
            categoryLabels = Object.assign({}, DEFAULT_CATS, data.category_labels || {});
            populateCatSelects(categoryLabels);

            const warnEl = document.getElementById('wo-ea-warnings');
            setHtml(warnEl, (data.warnings || []).map(w => '<div class="wo-ea-warn">⚠ ' + escHtml(w) +
                '</div>').join(''));

            renderEinnahmen();
            renderAusgaben();
            refreshTotals();
            buildPrintView();
            saveState();
            document.getElementById('wo-ea-report-content').style.display = '';
            document.getElementById('wo-ea-print-btn').style.display = '';
            setStatus('', '');
        } catch (e) {
            setStatus('Fehler: ' + e.message, '#cc1818');
            document.getElementById('wo-ea-empty').style.display = '';
        } finally {
            this.disabled = false;
        }
    });

    document.getElementById('wo-ea-print-btn').addEventListener('click', () => {
        buildPrintView();
        window.print();
    });

    document.querySelectorAll('.wo-ea-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.wo-ea-tab').forEach(t => t.classList.remove('is-active'));
            this.classList.add('is-active');
            const name = this.dataset.tab;
            document.getElementById('wo-ea-tab-report').style.display = name === 'report' ? '' :
                'none';
            document.getElementById('wo-ea-tab-mappings').style.display = name === 'mappings' ? '' :
                'none';
            if (name === 'mappings') loadMappings();
        });
    });

    let mappings = null;

    async function loadMappings() {
        if (mappings !== null) {
            renderMappings();
            return;
        }
        const res = await fetch(api + '/ea/vendor-mappings', {
            headers: hdr
        });
        if (!res.ok) {
            setStatus('Fehler beim Laden der Kategorien.', '#cc1818');
            return;
        }
        mappings = await res.json();
        populateCatSelects(categoryLabels || DEFAULT_CATS);
        renderMappings();
    }

    function renderMappings() {
        const cats = categoryLabels || DEFAULT_CATS;
        const tbody = document.querySelector('#wo-ea-mappings-table tbody');
        if (!mappings.length) {
            setHtml(tbody, '<tr><td colspan="4" style="color:#646970">Keine Regeln vorhanden.</td></tr>');
            return;
        }
        setHtml(tbody, mappings.map(m =>
            '<tr>' +
            '<td><code style="background:#f0f0f0;padding:1px 5px;border-radius:3px">' + escHtml(m.pattern) +
            '</code></td>' +
            '<td style="font-size:13px">' + escHtml(cats[m.category] || m.category) + '</td>' +
            '<td style="text-align:right;color:#646970">' + escHtml(m.priority) + '</td>' +
            '<td><button class="button button-small wo-ea-del-mapping" data-id="' + escHtml(m.id) +
            '" style="color:#cc1818;border-color:#cc181833">×</button></td>' +
            '</tr>'
        ).join(''));
        tbody.querySelectorAll('.wo-ea-del-mapping').forEach(btn => {
            btn.addEventListener('click', async function() {
                const id = this.dataset.id;
                await fetch(api + '/ea/vendor-mappings/' + id, {
                    method: 'DELETE',
                    headers: hdr
                });
                mappings = mappings.filter(m => m.id !== id);
                renderMappings();
            });
        });
    }

    document.getElementById('wo-ea-mapping-add-btn').addEventListener('click', async function() {
        const pattern = document.getElementById('wo-ea-new-pattern').value.trim().toLowerCase();
        const cat = document.getElementById('wo-ea-new-cat').value;
        const prio = parseInt(document.getElementById('wo-ea-new-prio').value) || 50;
        if (!pattern) return;
        const res = await fetch(api + '/ea/vendor-mappings', {
            method: 'POST',
            headers: hdr,
            body: JSON.stringify({
                pattern,
                category: cat,
                priority: prio
            })
        });
        const m = await res.json();
        if (!mappings) mappings = [];
        mappings.unshift(m);
        mappings.sort((a, b) => b.priority - a.priority);
        document.getElementById('wo-ea-new-pattern').value = '';
        renderMappings();
    });

    populateCatSelects(DEFAULT_CATS);

    // Auto-restore last loaded report
    if (loadState()) {
        categoryLabels = Object.assign({}, DEFAULT_CATS, report.category_labels || {});
        populateCatSelects(categoryLabels);
        const warnEl = document.getElementById('wo-ea-warnings');
        setHtml(warnEl, (report.warnings || []).map(w => '<div class="wo-ea-warn">⚠ ' + escHtml(w) + '</div>').join(
            ''));
        renderEinnahmen();
        renderAusgaben();
        refreshTotals();
        renderManualTable();
        buildPrintView();
        document.getElementById('wo-ea-empty').style.display = 'none';
        document.getElementById('wo-ea-report-content').style.display = '';
        document.getElementById('wo-ea-print-btn').style.display = '';
    }
})();
</script>