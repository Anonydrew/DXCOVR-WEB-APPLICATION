<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriberStatus;
use App\Models\NewsletterSubscriber;
use App\Services\SiteSettings;
use App\Support\CsvExport;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Subscriber list. No sending is built in: export confirmed subscribers to
 * the newsletter provider of your choice.
 */
class NewsletterController extends AdminController
{
    public function index(Request $request): View
    {
        return view('admin.newsletter.index', [
            'subscribers' => $this->filtered($request)->latest()->paginate(50)->withQueryString(),
            'counts' => NewsletterSubscriber::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'enabled' => (bool) app(SiteSettings::class)->get('newsletter.enabled'),
        ]);
    }

    public function destroy(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        $this->audit()->record('newsletter.deleted', null, 'Deleted a newsletter subscriber');

        return back()->with('status', 'Subscriber deleted.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->audit()->record('export.newsletter', null, 'Exported newsletter subscribers to CSV');

        $rows = NewsletterSubscriber::query()
            ->where('status', SubscriberStatus::Subscribed->value)
            ->orderBy('email')
            ->lazy(500)
            ->map(fn (NewsletterSubscriber $subscriber) => [$subscriber->email, $subscriber->name, $subscriber->confirmed_at, $subscriber->source]);

        return CsvExport::download('newsletter-subscribers-'.now()->format('Y-m-d').'.csv', ['Email', 'Name', 'Confirmed', 'Source'], $rows);
    }

    protected function filtered(Request $request): Builder
    {
        return NewsletterSubscriber::query()
            ->when(in_array($request->query('status'), SubscriberStatus::values(), true), fn (Builder $query) => $query->where('status', $request->query('status')))
            ->when($request->query('q'), fn (Builder $query, $term) => $query->where('email', 'like', '%'.addcslashes((string) $term, '%_').'%'));
    }
}
