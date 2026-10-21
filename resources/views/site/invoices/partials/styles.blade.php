<style>
    .inv-doc {
        --inv-ink: #0b0e0b;
        --inv-muted: #4a5147;
        --inv-line: #dde3d6;
        --inv-soft: #f3f5f0;
        --inv-accent: #3d6814;
        --inv-danger: #b3261e;
        box-sizing: border-box;
        width: 100%;
        max-width: 52rem;
        margin: 0 auto;
        padding: 3rem;
        background: #fff;
        color: var(--inv-ink);
        font-family: 'Inter Variable', 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
        font-size: 0.875rem;
        line-height: 1.55;
        border-radius: 0.75rem;
        box-shadow: 0 1px 2px rgb(0 0 0 / 0.06), 0 12px 32px rgb(0 0 0 / 0.08);
        position: relative;
        overflow: hidden;
    }
    .inv-doc *, .inv-doc *::before, .inv-doc *::after { box-sizing: border-box; }
    .inv-doc h1, .inv-doc h2, .inv-doc p, .inv-doc dl, .inv-doc dd, .inv-doc table { margin: 0; }
    .inv-pre { white-space: pre-line; }
    .inv-muted { color: var(--inv-muted); }
    .inv-label { font-size: 0.6875rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--inv-muted); }
    .inv-head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 2rem; padding-bottom: 2rem; border-bottom: 2px solid var(--inv-ink); }
    .inv-logo { font-size: 1.35rem; color: var(--inv-accent); display: inline-flex; }
    .inv-logo svg { height: 1em; width: auto; }
    .inv-from { margin-top: 1rem; }
    .inv-from strong { display: block; font-size: 0.9375rem; }
    .inv-title { text-align: right; }
    .inv-title h1 { font-family: 'Space Grotesk Variable', 'Space Grotesk', ui-sans-serif, system-ui, sans-serif; font-size: 2rem; font-weight: 600; letter-spacing: -0.02em; line-height: 1; }
    .inv-title p { margin-top: 0.5rem; font-size: 1rem; font-weight: 600; }
    .inv-meta { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); gap: 2rem; padding: 2rem 0; }
    .inv-meta dl { display: grid; grid-template-columns: auto 1fr; gap: 0.4rem 1.5rem; align-content: start; }
    .inv-meta dt { color: var(--inv-muted); }
    .inv-meta dd { text-align: right; font-weight: 500; }
    .inv-billto strong { display: block; margin-top: 0.4rem; font-size: 0.9375rem; }
    .inv-doc .inv-due { margin-top: 0.85rem; padding: 0.6rem 0.8rem; background: var(--inv-soft); border-radius: 0.5rem; display: flex; justify-content: space-between; gap: 1rem; font-weight: 600; }
    .inv-subject { margin-bottom: 1.25rem; font-weight: 600; font-size: 1rem; }
    .inv-table { width: 100%; border-collapse: collapse; }
    .inv-table th { padding: 0.6rem 0.5rem; border-bottom: 1px solid var(--inv-ink); text-align: left; }
    .inv-table td { padding: 0.75rem 0.5rem; border-bottom: 1px solid var(--inv-line); vertical-align: top; }
    .inv-table th:first-child, .inv-table td:first-child { padding-left: 0; }
    .inv-table th:last-child, .inv-table td:last-child { padding-right: 0; }
    .inv-num { text-align: right !important; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .inv-totals { display: flex; justify-content: flex-end; margin-top: 1.25rem; }
    .inv-totals dl { width: min(100%, 20rem); display: grid; grid-template-columns: 1fr auto; row-gap: 0.45rem; }
    .inv-totals dt { color: var(--inv-muted); }
    .inv-totals dd { padding-left: 1.5rem; text-align: right; font-variant-numeric: tabular-nums; }
    .inv-totals .inv-total { padding-top: 0.6rem; border-top: 1px solid var(--inv-ink); color: var(--inv-ink); font-weight: 700; font-size: 1rem; }
    .inv-totals .inv-balance { margin-top: 0.25rem; padding-top: 0.5rem; padding-bottom: 0.5rem; background: var(--inv-soft); color: var(--inv-ink); font-weight: 700; font-size: 1.05rem; }
    .inv-totals dt.inv-balance { margin-left: -0.75rem; padding-left: 0.75rem; border-radius: 0.5rem 0 0 0.5rem; }
    .inv-totals dd.inv-balance { margin-right: -0.75rem; padding-right: 0.75rem; border-radius: 0 0.5rem 0.5rem 0; }
    .inv-notes { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 1.5rem 2rem; margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--inv-line); }
    .inv-notes p.inv-pre { margin-top: 0.4rem; }
    .inv-payments { margin-top: 2rem; }
    .inv-payments .inv-table { margin-top: 0.5rem; font-size: 0.8125rem; }
    .inv-stamp { position: absolute; top: 2.2rem; right: -3.2rem; width: 14rem; padding: 0.35rem 0; transform: rotate(35deg); text-align: center; font-size: 0.75rem; font-weight: 800; letter-spacing: 0.2em; color: #fff; background: var(--inv-accent); }
    .inv-stamp-danger { background: var(--inv-danger); }
    .inv-head-with-stamp { padding-right: 3rem; }
    .inv-doc .inv-foot { margin-top: 3rem; text-align: center; font-size: 0.75rem; color: var(--inv-muted); }
    @media (max-width: 640px) {
        .inv-doc { padding: 1.5rem 1.25rem; border-radius: 0; }
        .inv-title { text-align: left; }
        .inv-meta { grid-template-columns: 1fr; }
        .inv-table .inv-hide-sm { display: none; }
        .inv-stamp { top: 1.4rem; right: -4rem; }
    }
    @media print {
        .inv-doc { max-width: none; padding: 0; box-shadow: none; border-radius: 0; }
        .inv-stamp { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .inv-due, .inv-totals .inv-balance { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .inv-table tr { break-inside: avoid; }
    }
</style>
