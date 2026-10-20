<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\EnquiryRequest;
use App\Models\Enquiry;
use App\Models\Page;
use App\Models\Service;
use App\Services\InboxNotifier;
use App\Services\Spam\SpamGuard;
use App\Support\Seo\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    public function show(Request $request): View
    {
        $page = Page::system('contact');

        return view('site.contact.show', [
            'page' => $page,
            'services' => Service::query()->published()->ordered()->get(['id', 'title']),
            'preselectedService' => Service::query()->published()->where('slug', $request->query('service'))->value('id'),
            'initialType' => $request->query('type') === 'contact' ? 'contact' : 'project',
            'seo' => Seo::make($page?->title ?? 'Contact')
                ->description($page?->excerpt)
                ->meta($page?->seo)
                ->breadcrumbs([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Contact', 'url' => route('contact')],
                ]),
        ]);
    }

    /**
     * Store the enquiry first, then notify. A mail failure never loses a submission.
     */
    public function store(EnquiryRequest $request, SpamGuard $spam, InboxNotifier $notifier): RedirectResponse
    {
        $spam->check($request, 'enquiry');

        $data = $request->safe()->except(['consent', 'source_url']);

        $enquiry = Enquiry::query()->create(array_merge($data, [
            'email' => Str::lower($data['email']),
            'consent_given_at' => now(),
            'source_url' => $request->safeSourceUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 490, ''),
        ]));

        $enquiry->load(['service', 'product']);
        $notifier->enquiryReceived($enquiry);

        return redirect()->route('contact.thanks')->with('enquiry', [
            'reference' => $enquiry->reference,
            'name' => $enquiry->name,
            'type' => $enquiry->type->value,
        ]);
    }

    public function thanks(Request $request): View|RedirectResponse
    {
        $enquiry = $request->session()->get('enquiry');

        if (! is_array($enquiry)) {
            return redirect()->route('contact');
        }

        return view('site.contact.thanks', [
            'enquiry' => $enquiry,
            'seo' => Seo::make('Thank you')->noindex(),
        ]);
    }
}
