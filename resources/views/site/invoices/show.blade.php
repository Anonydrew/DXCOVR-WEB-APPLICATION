@php($siteName = app(\App\Services\SiteSettings::class)->get('site.name'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->number }} · {{ $issuer['name'] }}</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    @include('site.invoices.partials.styles')
    <style>
        body { margin: 0; background: #e9ede4; color: #0b0e0b; font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; }
        .inv-page { padding: 1.5rem 1rem 3rem; }
        .inv-bar { max-width: 52rem; margin: 0 auto 1rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #4a5147; }
        .inv-bar p { margin: 0; }
        .inv-btn { display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.55rem 1rem; border-radius: 999px; border: 0; background: #0b0e0b; color: #fff; font: inherit; font-weight: 600; cursor: pointer; }
        .inv-btn:hover { background: #262c25; }
        .inv-btn:focus-visible { outline: 3px solid #3d6814; outline-offset: 2px; }
        .inv-banner { max-width: 52rem; margin: 0 auto 1rem; padding: 0.7rem 1rem; border-radius: 0.6rem; background: #fff4d6; color: #5b4300; font-size: 0.875rem; }
        @media (max-width: 640px) { .inv-page { padding: 0 0 2rem; } .inv-bar, .inv-banner { margin: 0 1rem 1rem; padding-top: 1rem; } }
        @media print {
            @page { margin: 14mm; }
            body { background: #fff; }
            .inv-page { padding: 0; }
            .inv-bar, .inv-banner { display: none; }
        }
    </style>
</head>
<body>
    <main class="inv-page">
        @if ($preview)
            <p class="inv-banner">
                Staff preview.
                @if ($invoice->isDraft())
                    This invoice is still a draft, so your client’s link will not open until you mark it as sent.
                @else
                    This is what your client sees at their private link.
                @endif
            </p>
        @endif

        <div class="inv-bar">
            <p>{{ $invoice->isVoid() ? 'This invoice has been cancelled.' : 'Keep this link private. You can print the invoice or save it as a PDF.' }}</p>
            <button type="button" class="inv-btn" id="print-invoice">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 9V3.5h10V9M7 17.5H5A1.5 1.5 0 0 1 3.5 16v-5.5A1.5 1.5 0 0 1 5 9h14a1.5 1.5 0 0 1 1.5 1.5V16a1.5 1.5 0 0 1-1.5 1.5h-2" /><path d="M7 14h10v6.5H7z" /></svg>
                Print or save as PDF
            </button>
        </div>

        @include('site.invoices.partials.document')
    </main>

    <script nonce="{{ Vite::cspNonce() }}">
        document.getElementById('print-invoice').addEventListener('click', () => window.print());
    </script>
</body>
</html>
