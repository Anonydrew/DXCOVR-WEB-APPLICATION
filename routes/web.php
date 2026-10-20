<?php

use App\Http\Controllers\Site\CareerController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\InsightController;
use App\Http\Controllers\Site\NewsletterController;
use App\Http\Controllers\Site\OnboardingController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\PreviewController;
use App\Http\Controllers\Site\ProductController;
use App\Http\Controllers\Site\SearchController;
use App\Http\Controllers\Site\ServiceController;
use App\Http\Controllers\Site\SitemapController;
use App\Http\Controllers\Site\WorkController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/about/founder', [PageController::class, 'founder'])->name('about.founder');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{service:slug}', [ServiceController::class, 'show'])->name('services.show');

Route::get('/work', [WorkController::class, 'index'])->name('work.index');
Route::get('/work/{project:slug}', [WorkController::class, 'show'])->name('work.show');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/insights', [InsightController::class, 'index'])->name('insights.index');
Route::get('/insights/feed', [InsightController::class, 'feed'])->name('insights.feed');
Route::get('/insights/category/{category:slug}', [InsightController::class, 'category'])->name('insights.category');
Route::get('/insights/tag/{tag:slug}', [InsightController::class, 'tag'])->name('insights.tag');
Route::get('/insights/{article:slug}', [InsightController::class, 'show'])->name('insights.show');

Route::get('/careers', [CareerController::class, 'index'])->name('careers.index');
Route::get('/careers/application-received', [CareerController::class, 'received'])->name('careers.received');
Route::get('/careers/{job:slug}', [CareerController::class, 'show'])->name('careers.show');
Route::post('/careers/{job:slug}/apply', [CareerController::class, 'apply'])
    ->middleware('throttle:applications')
    ->name('careers.apply');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:forms')->name('contact.store');
Route::get('/contact/thank-you', [ContactController::class, 'thanks'])->name('contact.thanks');

Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->middleware('throttle:forms')->name('newsletter.subscribe');
Route::get('/newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'showUnsubscribe'])->name('newsletter.unsubscribe');
Route::post('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe.confirm');

Route::get('/search', SearchController::class)->middleware('throttle:search')->name('search');

Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::prefix('onboarding/{token}')
    ->middleware(['noindex', 'throttle:onboarding'])
    ->where(['token' => '[A-Za-z0-9]{48}', 'file' => '[0-9]+'])
    ->name('onboarding.')
    ->group(function (): void {
        Route::get('/', [OnboardingController::class, 'show'])->name('show');
        Route::post('/', [OnboardingController::class, 'update'])->name('update');
        Route::post('/files', [OnboardingController::class, 'upload'])->withoutMiddleware('throttle:onboarding')->middleware('throttle:onboarding-uploads')->name('files.store');
        Route::get('/files/{file}', [OnboardingController::class, 'file'])->withoutMiddleware('throttle:onboarding')->name('files.show');
        Route::delete('/files/{file}', [OnboardingController::class, 'destroyFile'])->name('files.destroy');
    });

Route::get('/preview/{type}/{id}', PreviewController::class)
    ->middleware(['signed', 'noindex'])
    ->whereNumber('id')
    ->name('preview');

/*
 * The catch-all route for custom pages lives in routes/pages.php and is
 * registered after the admin routes (see bootstrap/app.php).
 */
