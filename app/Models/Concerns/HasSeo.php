<?php

namespace App\Models\Concerns;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeo
{
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /**
     * Create or update the SEO metadata for the model.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function saveSeo(array $attributes): SeoMeta
    {
        $attributes['noindex'] = (bool) ($attributes['noindex'] ?? false);

        $seo = $this->seo()->updateOrCreate([], $attributes);
        $this->setRelation('seo', $seo);

        return $seo;
    }
}
