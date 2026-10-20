<?php

namespace App\Http\Controllers\Site;

use App\Enums\SubscriberStatus;
use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Notifications\ConfirmNewsletterSubscription;
use App\Services\InboxNotifier;
use App\Services\SiteSettings;
use App\Services\Spam\SpamGuard;
use App\Support\Seo\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Double opt-in newsletter subscriptions. Responses never reveal whether an
 * address is already subscribed.
 */
class NewsletterController extends Controller
{
    public function subscribe(Request $request, SiteSettings $settings, SpamGuard $spam, InboxNotifier $notifier): RedirectResponse
    {
        abort_unless($settings->get('newsletter.enabled'), 404);

        $validated = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'consent' => ['accepted'],
            'source' => ['nullable', 'string', 'max:120'],
        ], [
            'consent.accepted' => 'Please confirm you would like to receive our emails.',
        ])->validateWithBag('newsletter');

        $spam->check($request, 'newsletter');

        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => Str::lower($validated['email'])]);

        if ($subscriber->status !== SubscriberStatus::Subscribed) {
            $subscriber->fill([
                'consent_given_at' => now(),
                'source' => $validated['source'] ?? null,
                'ip_address' => $request->ip(),
            ]);
            $subscriber->status = SubscriberStatus::Pending;
            $subscriber->token = Str::random(64);
            $subscriber->unsubscribed_at = null;
            $subscriber->save();

            $notifier->sendTo($subscriber->email, new ConfirmNewsletterSubscription($subscriber), 'newsletter confirmation', 'subscriber-'.$subscriber->id);
        }

        return back()->with('newsletter_status', 'Thanks! Please check your inbox and confirm your subscription.');
    }

    public function confirm(string $token): View
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->first();

        if ($subscriber && $subscriber->status === SubscriberStatus::Pending) {
            $subscriber->status = SubscriberStatus::Subscribed;
            $subscriber->confirmed_at = now();
            $subscriber->save();
        }

        return view('site.newsletter.status', [
            'title' => $subscriber ? 'You are subscribed' : 'Link not recognised',
            'message' => $subscriber
                ? 'Thank you for confirming. You will receive occasional updates from us, and every email includes a link to unsubscribe.'
                : 'This confirmation link is invalid or has already been replaced. Please subscribe again.',
            'unsubscribeToken' => $subscriber?->status === SubscriberStatus::Subscribed ? $subscriber->token : null,
            'seo' => Seo::make('Newsletter')->noindex(),
        ]);
    }

    public function showUnsubscribe(string $token): View
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->first();

        return view('site.newsletter.unsubscribe', [
            'token' => $token,
            'valid' => $subscriber !== null && $subscriber->status !== SubscriberStatus::Unsubscribed,
            'seo' => Seo::make('Unsubscribe')->noindex(),
        ]);
    }

    public function unsubscribe(string $token): View
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->first();

        if ($subscriber && $subscriber->status !== SubscriberStatus::Unsubscribed) {
            $subscriber->status = SubscriberStatus::Unsubscribed;
            $subscriber->unsubscribed_at = now();
            $subscriber->save();
        }

        return view('site.newsletter.status', [
            'title' => 'You have been unsubscribed',
            'message' => 'You will not receive any further newsletter emails from us.',
            'unsubscribeToken' => null,
            'seo' => Seo::make('Unsubscribed')->noindex(),
        ]);
    }
}
