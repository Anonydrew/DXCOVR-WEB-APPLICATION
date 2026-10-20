<?php

namespace App\View\Composers;

use App\Models\Announcement;
use App\Services\Navigation;
use App\Services\SiteSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

/**
 * Shares settings, menus and the current announcement with the public layout.
 */
class SiteLayoutComposer
{
    public function __construct(
        protected SiteSettings $settings,
        protected Navigation $navigation,
    ) {}

    public function compose(View $view): void
    {
        $view->with([
            'site' => $this->settings,
            'primaryNav' => $this->navigation->menu('primary'),
            'footerMenus' => [
                'explore' => $this->navigation->menu('footer_explore'),
                'company' => $this->navigation->menu('footer_company'),
                'legal' => $this->navigation->menu('footer_legal'),
            ],
            'announcement' => $this->announcement(),
        ]);
    }

    protected function announcement(): ?Announcement
    {
        try {
            return Cache::remember('announcement.current', 300, fn () => Announcement::query()->current()->latest('starts_at')->latest('id')->first());
        } catch (Throwable) {
            return null;
        }
    }
}
