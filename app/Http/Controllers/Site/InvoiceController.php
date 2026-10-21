<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Invoicing;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The client-facing invoice, reached only through its private link.
 * Drafts are not visible until the invoice is marked as sent.
 */
class InvoiceController extends Controller
{
    public function show(Request $request, string $token, Invoicing $invoicing): Response
    {
        $invoice = Invoice::findByToken($token);

        abort_if($invoice === null || $invoice->isDraft(), 404);

        // Staff opening the link should not look like the client viewing it.
        if (! $request->user() && $invoice->viewed_at === null) {
            $invoice->viewed_at = now();
            $invoice->saveQuietly();
        }

        return response()->view('site.invoices.show', [
            'invoice' => $invoice->load(['client', 'items', 'payments']),
            'issuer' => $invoicing->issuer(),
            'preview' => false,
        ]);
    }
}
