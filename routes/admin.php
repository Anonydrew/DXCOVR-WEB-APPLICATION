<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\ArticleCategoryController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ClientQuestionnaireController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\FounderController;
use App\Http\Controllers\Admin\JobListingController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NavigationController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PageSectionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProjectCategoryController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\PublicationController;
use App\Http\Controllers\Admin\QuestionnaireBuilderController;
use App\Http\Controllers\Admin\QuestionnaireQuestionController;
use App\Http\Controllers\Admin\QuestionnaireSectionController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\ReorderController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TeamMemberController;
use App\Http\Controllers\Admin\TechnologyController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
|
| Loaded from bootstrap/app.php with the "web", "auth", "active" and
| "noindex" middleware, the /admin prefix and the "admin." name prefix.
| Every group below is additionally protected by a permission check, which
| is enforced on the server regardless of what the interface displays.
|
*/

Route::get('/account', [AccountController::class, 'edit'])->name('account');

Route::middleware('can:dashboard.view')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    // Publication workflow and ordering shared by all content types (authorised per type).
    Route::post('/publication/{type}/{id}', PublicationController::class)->whereNumber('id')->name('publication');
    Route::post('/reorder/{type}', ReorderController::class)->name('reorder');

    // Insights
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/create', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store');
    Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
    Route::post('/articles/{article}/autosave', [ArticleController::class, 'autosave'])->name('articles.autosave');
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');

    Route::middleware('can:taxonomy.manage')->group(function (): void {
        Route::resource('article-categories', ArticleCategoryController::class)->except(['show'])->parameters(['article-categories' => 'category']);
        Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
        Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
        Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
        Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
    });

    // Services
    Route::get('/services', [ServiceController::class, 'index'])->middleware('can:services.view')->name('services.index');
    Route::resource('services', ServiceController::class)->except(['index', 'show'])->middleware('can:services.manage');

    // Portfolio
    Route::get('/projects', [ProjectController::class, 'index'])->middleware('can:projects.view')->name('projects.index');
    Route::middleware('can:projects.manage')->group(function (): void {
        Route::resource('projects', ProjectController::class)->except(['index', 'show']);
        Route::resource('project-categories', ProjectCategoryController::class)->except(['show'])->parameters(['project-categories' => 'category']);
        Route::get('/technologies', [TechnologyController::class, 'index'])->name('technologies.index');
        Route::post('/technologies', [TechnologyController::class, 'store'])->name('technologies.store');
        Route::put('/technologies/{technology}', [TechnologyController::class, 'update'])->name('technologies.update');
        Route::delete('/technologies/{technology}', [TechnologyController::class, 'destroy'])->name('technologies.destroy');
    });

    // Products
    Route::get('/products', [ProductController::class, 'index'])->middleware('can:products.view')->name('products.index');
    Route::resource('products', ProductController::class)->except(['index', 'show'])->middleware('can:products.manage');

    // Company
    Route::get('/team', [TeamMemberController::class, 'index'])->middleware('can:company.view')->name('team.index');
    Route::get('/testimonials', [TestimonialController::class, 'index'])->middleware('can:company.view')->name('testimonials.index');
    Route::get('/faqs', [FaqController::class, 'index'])->middleware('can:company.view')->name('faqs.index');
    Route::get('/announcements', [AnnouncementController::class, 'index'])->middleware('can:company.view')->name('announcements.index');
    Route::middleware('can:company.manage')->group(function (): void {
        Route::get('/founder', [FounderController::class, 'edit'])->name('founder.edit');
        Route::put('/founder', [FounderController::class, 'update'])->name('founder.update');
        Route::resource('team', TeamMemberController::class)->except(['index', 'show'])->parameters(['team' => 'member']);
        Route::resource('testimonials', TestimonialController::class)->except(['index', 'show']);
        Route::resource('faqs', FaqController::class)->except(['index', 'show']);
        Route::resource('announcements', AnnouncementController::class)->except(['index', 'show']);
    });

    // Careers
    Route::get('/jobs', [JobListingController::class, 'index'])->middleware('can:careers.view')->name('jobs.index');
    Route::middleware('can:careers.manage')->group(function (): void {
        Route::resource('jobs', JobListingController::class)->except(['index', 'show'])->parameters(['jobs' => 'job']);
        Route::post('/jobs/{job}/close', [JobListingController::class, 'close'])->name('jobs.close');
        Route::post('/jobs/{job}/reopen', [JobListingController::class, 'reopen'])->name('jobs.reopen');
        Route::resource('departments', DepartmentController::class)->except(['show']);
    });

    Route::middleware('can:applications.view')->group(function (): void {
        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/export', [ApplicationController::class, 'export'])->middleware('can:exports.run')->name('applications.export');
        Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
        Route::get('/applications/{application}/cv', [ApplicationController::class, 'downloadCv'])->name('applications.cv');
        Route::patch('/applications/{application}/stage', [ApplicationController::class, 'updateStage'])->middleware('can:applications.update')->name('applications.stage');
        Route::post('/applications/{application}/notes', [ApplicationController::class, 'storeNote'])->middleware('can:applications.update')->name('applications.notes.store');
        Route::delete('/applications/{application}', [ApplicationController::class, 'destroy'])->middleware('can:applications.delete')->name('applications.destroy');
    });

    // Inbox
    Route::middleware('can:enquiries.view')->group(function (): void {
        Route::get('/enquiries', [EnquiryController::class, 'index'])->name('enquiries.index');
        Route::get('/enquiries/export', [EnquiryController::class, 'export'])->middleware('can:exports.run')->name('enquiries.export');
        Route::get('/enquiries/{enquiry}', [EnquiryController::class, 'show'])->name('enquiries.show');
        Route::patch('/enquiries/{enquiry}', [EnquiryController::class, 'update'])->middleware('can:enquiries.update')->name('enquiries.update');
        Route::delete('/enquiries/{enquiry}', [EnquiryController::class, 'destroy'])->middleware('can:enquiries.delete')->name('enquiries.destroy');
    });

    Route::middleware('can:newsletter.view')->group(function (): void {
        Route::get('/newsletter', [NewsletterController::class, 'index'])->name('newsletter.index');
        Route::get('/newsletter/export', [NewsletterController::class, 'export'])->middleware('can:exports.run')->name('newsletter.export');
        Route::delete('/newsletter/{subscriber}', [NewsletterController::class, 'destroy'])->middleware('can:newsletter.manage')->name('newsletter.destroy');
    });

    // Client onboarding
    Route::middleware('can:onboarding.view')->prefix('onboarding')->name('onboarding.')->group(function (): void {
        Route::get('/', [ClientQuestionnaireController::class, 'index'])->name('index');

        Route::middleware('can:onboarding.manage')->group(function (): void {
            // Questionnaire builder: the sections and questions new questionnaires use.
            Route::prefix('questions')->group(function (): void {
                Route::get('/', [QuestionnaireBuilderController::class, 'index'])->name('questions.index');
                Route::get('/preview', [QuestionnaireBuilderController::class, 'preview'])->name('questions.preview');
                Route::get('/sections/create', [QuestionnaireSectionController::class, 'create'])->name('sections.create');
                Route::post('/sections', [QuestionnaireSectionController::class, 'store'])->name('sections.store');
                Route::get('/sections/{section}/edit', [QuestionnaireSectionController::class, 'edit'])->whereNumber('section')->name('sections.edit');
                Route::put('/sections/{section}', [QuestionnaireSectionController::class, 'update'])->whereNumber('section')->name('sections.update');
                Route::delete('/sections/{section}', [QuestionnaireSectionController::class, 'destroy'])->whereNumber('section')->name('sections.destroy');
                Route::get('/create', [QuestionnaireQuestionController::class, 'create'])->name('questions.create');
                Route::post('/', [QuestionnaireQuestionController::class, 'store'])->name('questions.store');
                Route::get('/{question}/edit', [QuestionnaireQuestionController::class, 'edit'])->whereNumber('question')->name('questions.edit');
                Route::put('/{question}', [QuestionnaireQuestionController::class, 'update'])->whereNumber('question')->name('questions.update');
                Route::delete('/{question}', [QuestionnaireQuestionController::class, 'destroy'])->whereNumber('question')->name('questions.destroy');
            });

            Route::get('/create', [ClientQuestionnaireController::class, 'create'])->name('create');
            Route::post('/', [ClientQuestionnaireController::class, 'store'])->name('store');
            Route::get('/{questionnaire}/edit', [ClientQuestionnaireController::class, 'edit'])->name('edit');
            Route::put('/{questionnaire}', [ClientQuestionnaireController::class, 'update'])->name('update');
            Route::patch('/{questionnaire}/notes', [ClientQuestionnaireController::class, 'notes'])->name('notes');
            Route::post('/{questionnaire}/reopen', [ClientQuestionnaireController::class, 'reopen'])->name('reopen');
            Route::post('/{questionnaire}/regenerate', [ClientQuestionnaireController::class, 'regenerate'])->name('regenerate');
            Route::post('/{questionnaire}/refresh-questions', [ClientQuestionnaireController::class, 'refreshQuestions'])->name('refresh-questions');
            Route::delete('/{questionnaire}', [ClientQuestionnaireController::class, 'destroy'])->name('destroy');
        });

        Route::get('/{questionnaire}', [ClientQuestionnaireController::class, 'show'])->name('show');
        Route::get('/{questionnaire}/files/{file}', [ClientQuestionnaireController::class, 'file'])->whereNumber('file')->name('files.show');
    });

    // Media library
    Route::middleware('can:media.view')->group(function (): void {
        Route::get('/media', [MediaController::class, 'index'])->name('media.index');
        Route::get('/media/picker', [MediaController::class, 'picker'])->name('media.picker');
        Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
        Route::middleware('can:media.upload')->group(function (): void {
            Route::post('/media', [MediaController::class, 'store'])->middleware('throttle:admin-uploads')->name('media.store');
            Route::put('/media/{media}', [MediaController::class, 'update'])->name('media.update');
            Route::post('/media/{media}/replace', [MediaController::class, 'replace'])->middleware('throttle:admin-uploads')->name('media.replace');
        });
        Route::delete('/media/{media}', [MediaController::class, 'destroy'])->middleware('can:media.delete')->name('media.destroy');
    });

    // Site
    Route::middleware('can:pages.manage')->group(function (): void {
        Route::resource('pages', PageController::class)->except(['show']);
        Route::post('/pages/{page}/sections', [PageSectionController::class, 'store'])->name('pages.sections.store');
        Route::put('/sections/{section}', [PageSectionController::class, 'update'])->name('sections.update');
        Route::post('/sections/{section}/toggle', [PageSectionController::class, 'toggle'])->name('sections.toggle');
        Route::delete('/sections/{section}', [PageSectionController::class, 'destroy'])->name('sections.destroy');
    });

    Route::middleware('can:navigation.manage')->group(function (): void {
        Route::get('/navigation', [NavigationController::class, 'index'])->name('navigation.index');
        Route::get('/navigation/{menu}', [NavigationController::class, 'edit'])->name('navigation.edit');
        Route::post('/navigation/{menu}/items', [NavigationController::class, 'store'])->name('navigation.items.store');
        Route::put('/navigation/items/{item}', [NavigationController::class, 'update'])->name('navigation.items.update');
        Route::delete('/navigation/items/{item}', [NavigationController::class, 'destroy'])->name('navigation.items.destroy');
    });

    Route::get('/settings/{group}', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings/{group}', [SettingsController::class, 'update'])->name('settings.update');

    Route::resource('redirects', RedirectController::class)->except(['show'])->middleware('can:seo.manage');

    Route::middleware('can:users.manage')->group(function (): void {
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('/users/{user}/send-reset', [UserController::class, 'sendReset'])->name('users.send-reset');
        Route::resource('roles', RoleController::class)->except(['show']);
    });

    Route::get('/audit-log', AuditLogController::class)->middleware('can:audit.view')->name('audit.index');
});
