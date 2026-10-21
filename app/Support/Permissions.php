<?php

namespace App\Support;

/**
 * The single source of truth for CMS permissions and the default role matrix.
 *
 * Run `php artisan dxcovr:sync-permissions` (or the RolesAndPermissionsSeeder)
 * after changing this file. Syncing is idempotent and safe in production.
 */
final class Permissions
{
    public const SUPER_ADMIN_ROLE = 'Super Administrator';

    /**
     * Permission name => description, grouped for the roles editor.
     *
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        return [
            'General' => [
                'dashboard.view' => 'Access the admin dashboard',
                'audit.view' => 'View the audit log',
                'exports.run' => 'Export permitted records to CSV',
            ],
            'Insights' => [
                'articles.view' => 'View all articles',
                'articles.create' => 'Create and edit own draft articles',
                'articles.edit-any' => 'Edit any article',
                'articles.publish' => 'Publish, schedule and archive articles',
                'articles.delete' => 'Delete articles',
                'taxonomy.manage' => 'Manage article categories and tags',
            ],
            'Work & offering' => [
                'projects.view' => 'View portfolio projects',
                'projects.manage' => 'Create, edit, publish and delete portfolio projects',
                'services.view' => 'View services',
                'services.manage' => 'Create, edit, publish and delete services',
                'products.view' => 'View products',
                'products.manage' => 'Create, edit, publish and delete products',
            ],
            'Company' => [
                'company.view' => 'View team, founder, testimonials, FAQs and announcements',
                'company.manage' => 'Manage team, founder, testimonials, FAQs and announcements',
            ],
            'Careers' => [
                'careers.view' => 'View job listings',
                'careers.manage' => 'Create, edit, publish and close job listings',
                'applications.view' => 'View job applications and download CVs',
                'applications.update' => 'Change application stages and add notes',
                'applications.delete' => 'Delete applications and recruitment data',
            ],
            'Inbox' => [
                'enquiries.view' => 'View contact and project enquiries',
                'enquiries.update' => 'Update enquiry status, assignment and notes',
                'enquiries.delete' => 'Delete enquiries',
                'newsletter.view' => 'View newsletter subscribers',
                'newsletter.manage' => 'Manage newsletter subscribers',
            ],
            'Clients' => [
                'onboarding.view' => 'View client onboarding questionnaires, answers and uploads',
                'onboarding.manage' => 'Create, share, reopen and delete client onboarding questionnaires',
                'invoices.view' => 'View billing clients, invoices and payments',
                'invoices.manage' => 'Manage billing clients, create and send invoices, record payments and edit invoice settings',
            ],
            'Media' => [
                'media.view' => 'Browse the media library',
                'media.upload' => 'Upload and edit media',
                'media.delete' => 'Delete unused media',
            ],
            'Site' => [
                'pages.manage' => 'Manage pages, homepage sections and legal content',
                'navigation.manage' => 'Manage navigation menus',
                'seo.manage' => 'Manage SEO defaults, metadata and redirects',
                'settings.manage' => 'Manage global settings, contact details and footer',
                'users.manage' => 'Manage users, roles and permissions',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(array_merge(...array_values(self::grouped())));
    }

    /**
     * Default roles and their permissions. The Super Administrator role is
     * granted every ability through a Gate::before check.
     *
     * @return array<string, array{description: string, permissions: list<string>}>
     */
    public static function roles(): array
    {
        $contentEditing = [
            'dashboard.view', 'articles.view', 'articles.create', 'articles.edit-any', 'articles.publish',
            'articles.delete', 'taxonomy.manage', 'projects.view', 'projects.manage', 'services.view',
            'services.manage', 'products.view', 'products.manage', 'company.view', 'company.manage',
            'media.view', 'media.upload', 'media.delete', 'pages.manage', 'navigation.manage', 'seo.manage',
        ];

        return [
            self::SUPER_ADMIN_ROLE => [
                'description' => 'Unrestricted access, including user and role management.',
                'permissions' => [],
            ],
            'Administrator' => [
                'description' => 'Manages all content, careers, inbox and settings. Cannot manage Super Administrators.',
                'permissions' => self::all(),
            ],
            'Editor' => [
                'description' => 'Creates, reviews and publishes website content.',
                'permissions' => array_merge($contentEditing, ['careers.view', 'enquiries.view', 'audit.view']),
            ],
            'Author' => [
                'description' => 'Writes and submits articles for review. Cannot publish.',
                'permissions' => ['dashboard.view', 'articles.create', 'media.view', 'media.upload'],
            ],
            'Careers Manager' => [
                'description' => 'Manages job listings and the recruitment pipeline.',
                'permissions' => [
                    'dashboard.view', 'careers.view', 'careers.manage', 'applications.view',
                    'applications.update', 'applications.delete', 'exports.run', 'media.view', 'media.upload',
                ],
            ],
            'Read-only Analyst' => [
                'description' => 'Read-only access to content and enquiry volumes. No applicant data.',
                'permissions' => [
                    'dashboard.view', 'articles.view', 'projects.view', 'services.view', 'products.view',
                    'company.view', 'careers.view', 'enquiries.view', 'newsletter.view', 'media.view', 'audit.view',
                ],
            ],
        ];
    }
}
