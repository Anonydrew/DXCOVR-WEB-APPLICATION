<?php

namespace App\Support;

use App\Enums\ApplicationStage;
use App\Enums\PublicationStatus;
use App\Models\Article;
use App\Models\ClientQuestionnaire;
use App\Models\Enquiry;
use App\Models\Invoice;
use App\Models\JobApplication;
use App\Models\User;

/**
 * The admin sidebar. Items are shown only when the user holds the required
 * permission; the routes enforce the same permissions on the server.
 */
final class AdminNavigation
{
    /**
     * @return list<array{label: string, items: list<array{label: string, route: string, params?: array<string, mixed>, icon: string, can: string|list<string>, active: string, count?: int}>}>
     */
    public static function for(User $user): array
    {
        $groups = [
            ['label' => 'Overview', 'items' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'grid', 'can' => 'dashboard.view', 'active' => 'admin.dashboard'],
            ]],
            ['label' => 'Content', 'items' => [
                ['label' => 'Articles', 'route' => 'admin.articles.index', 'icon' => 'file', 'can' => ['articles.view', 'articles.create'], 'active' => 'admin.articles.*', 'count' => 'review'],
                ['label' => 'Categories & tags', 'route' => 'admin.article-categories.index', 'icon' => 'tag', 'can' => 'taxonomy.manage', 'active' => 'admin.article-categories.*|admin.tags.*'],
                ['label' => 'Services', 'route' => 'admin.services.index', 'icon' => 'layers', 'can' => 'services.view', 'active' => 'admin.services.*'],
                ['label' => 'Portfolio', 'route' => 'admin.projects.index', 'icon' => 'briefcase', 'can' => 'projects.view', 'active' => 'admin.projects.*|admin.project-categories.*|admin.technologies.*'],
                ['label' => 'Products', 'route' => 'admin.products.index', 'icon' => 'box', 'can' => 'products.view', 'active' => 'admin.products.*'],
                ['label' => 'Media library', 'route' => 'admin.media.index', 'icon' => 'image', 'can' => 'media.view', 'active' => 'admin.media.*'],
            ]],
            ['label' => 'Company', 'items' => [
                ['label' => 'Founder profile', 'route' => 'admin.founder.edit', 'icon' => 'user', 'can' => 'company.manage', 'active' => 'admin.founder.*'],
                ['label' => 'Team', 'route' => 'admin.team.index', 'icon' => 'users', 'can' => 'company.view', 'active' => 'admin.team.*'],
                ['label' => 'Testimonials', 'route' => 'admin.testimonials.index', 'icon' => 'quote', 'can' => 'company.view', 'active' => 'admin.testimonials.*'],
                ['label' => 'FAQs', 'route' => 'admin.faqs.index', 'icon' => 'help', 'can' => 'company.view', 'active' => 'admin.faqs.*'],
                ['label' => 'Announcements', 'route' => 'admin.announcements.index', 'icon' => 'megaphone', 'can' => 'company.view', 'active' => 'admin.announcements.*'],
            ]],
            ['label' => 'Careers', 'items' => [
                ['label' => 'Job listings', 'route' => 'admin.jobs.index', 'icon' => 'briefcase', 'can' => 'careers.view', 'active' => 'admin.jobs.*|admin.departments.*'],
                ['label' => 'Applications', 'route' => 'admin.applications.index', 'icon' => 'users', 'can' => 'applications.view', 'active' => 'admin.applications.*', 'count' => 'applications'],
            ]],
            ['label' => 'Inbox', 'items' => [
                ['label' => 'Enquiries', 'route' => 'admin.enquiries.index', 'icon' => 'inbox', 'can' => 'enquiries.view', 'active' => 'admin.enquiries.*', 'count' => 'enquiries'],
                ['label' => 'Newsletter', 'route' => 'admin.newsletter.index', 'icon' => 'mail', 'can' => 'newsletter.view', 'active' => 'admin.newsletter.*'],
            ]],
            ['label' => 'Clients', 'items' => [
                ['label' => 'Client onboarding', 'route' => 'admin.onboarding.index', 'icon' => 'sparkle', 'can' => 'onboarding.view', 'active' => 'admin.onboarding.*', 'count' => 'onboarding'],
                ['label' => 'Clients', 'route' => 'admin.clients.index', 'icon' => 'user', 'can' => 'invoices.view', 'active' => 'admin.clients.*'],
                ['label' => 'Invoices', 'route' => 'admin.invoices.index', 'icon' => 'receipt', 'can' => 'invoices.view', 'active' => 'admin.invoices.*', 'count' => 'overdue'],
            ]],
            ['label' => 'Site', 'items' => [
                ['label' => 'Pages & homepage', 'route' => 'admin.pages.index', 'icon' => 'layers', 'can' => 'pages.manage', 'active' => 'admin.pages.*|admin.sections.*'],
                ['label' => 'Navigation', 'route' => 'admin.navigation.index', 'icon' => 'navigation', 'can' => 'navigation.manage', 'active' => 'admin.navigation.*'],
                ['label' => 'Settings', 'route' => 'admin.settings.edit', 'params' => ['group' => 'general'], 'icon' => 'settings', 'can' => ['settings.manage', 'seo.manage', 'careers.manage'], 'active' => 'admin.settings.*'],
                ['label' => 'Redirects', 'route' => 'admin.redirects.index', 'icon' => 'redirect', 'can' => 'seo.manage', 'active' => 'admin.redirects.*'],
            ]],
            ['label' => 'Access', 'items' => [
                ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users', 'can' => 'users.manage', 'active' => 'admin.users.*'],
                ['label' => 'Roles & permissions', 'route' => 'admin.roles.index', 'icon' => 'shield', 'can' => 'users.manage', 'active' => 'admin.roles.*'],
                ['label' => 'Audit log', 'route' => 'admin.audit.index', 'icon' => 'history', 'can' => 'audit.view', 'active' => 'admin.audit.*'],
            ]],
        ];

        $result = [];

        foreach ($groups as $group) {
            $items = [];

            foreach ($group['items'] as $item) {
                if (! $user->canAny((array) $item['can'])) {
                    continue;
                }

                if (isset($item['count'])) {
                    $item['count'] = self::count($item['count'], $user);
                }

                $items[] = $item;
            }

            if ($items !== []) {
                $result[] = ['label' => $group['label'], 'items' => $items];
            }
        }

        // Settings link should open a group the user may edit.
        foreach ($result as &$group) {
            foreach ($group['items'] as &$item) {
                if ($item['route'] === 'admin.settings.edit' && ! $user->can('settings.manage')) {
                    $item['params'] = ['group' => $user->can('seo.manage') ? 'seo' : 'careers'];
                }
            }
        }

        return $result;
    }

    protected static function count(string $type, User $user): int
    {
        return match ($type) {
            'review' => $user->can('articles.publish') ? Article::query()->where('status', PublicationStatus::InReview->value)->count() : 0,
            'applications' => JobApplication::query()->where('stage', ApplicationStage::Received->value)->count(),
            'enquiries' => Enquiry::query()->unread()->count(),
            'onboarding' => ClientQuestionnaire::query()->awaitingReview()->count(),
            'overdue' => Invoice::query()->overdue()->count(),
            default => 0,
        };
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function isActive(array $item): bool
    {
        return request()->routeIs(...explode('|', $item['active']));
    }
}
