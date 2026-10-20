<?php

namespace App\Support\Seo;

use App\Models\Article;
use App\Models\JobListing;
use App\Models\Media;
use App\Services\SiteSettings;
use Illuminate\Support\Str;

/**
 * schema.org JSON-LD for the organisation, website, articles and job postings.
 */
final class StructuredData
{
    /**
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        $settings = app(SiteSettings::class);
        $logoId = $settings->get('seo.organization_logo_id');
        $logo = $logoId ? Media::query()->find($logoId) : null;

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => $settings->get('site.name'),
            'legalName' => $settings->get('seo.organization_legal_name'),
            'url' => url('/'),
            'description' => $settings->get('site.description'),
            'logo' => $logo?->url(),
            'email' => $settings->get('contact.email'),
            'telephone' => $settings->get('contact.phone'),
            'sameAs' => collect($settings->get('social.links', []))->pluck('url')->filter()->values()->all() ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'name' => app(SiteSettings::class)->get('site.name'),
            'url' => url('/'),
            'publisher' => ['@id' => url('/').'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('search').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function article(Article $article): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => Str::limit($article->title, 110, ''),
            'description' => $article->excerpt,
            'image' => $article->featuredImage?->variantUrl(1600),
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at?->toIso8601String(),
            'author' => [
                '@type' => $article->show_author && $article->author && ! $article->byline ? 'Person' : 'Organization',
                'name' => $article->bylineName(),
            ],
            'publisher' => ['@id' => url('/').'#organization'],
            'mainEntityOfPage' => $article->publicUrl(),
            'keywords' => $article->tags->pluck('name')->implode(', ') ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function jobPosting(JobListing $job): array
    {
        $settings = app(SiteSettings::class);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $job->title,
            'description' => trim(implode("\n", array_filter([
                $job->summary,
                $job->description,
                $job->responsibilities,
                $job->requirements,
            ]))),
            'datePosted' => $job->published_at?->toDateString(),
            'validThrough' => $job->application_deadline?->toIso8601String(),
            'employmentType' => $job->employment_type?->schemaValue(),
            'hiringOrganization' => array_filter([
                '@type' => 'Organization',
                'name' => $settings->get('site.name'),
                'sameAs' => url('/'),
            ]),
            'directApply' => $job->acceptsFormApplications(),
        ];

        if ($job->work_arrangement?->value === 'remote') {
            $schema['jobLocationType'] = 'TELECOMMUTE';
        } else {
            $schema['jobLocation'] = [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $job->location,
                    'addressCountry' => 'GH',
                ],
            ];
        }

        return array_filter($schema, fn ($value) => $value !== null && $value !== '');
    }
}
