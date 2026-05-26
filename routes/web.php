<?php

use App\Http\Controllers\Admin\PersonController as AdminPersonController;
use App\Http\Controllers\Admin\CampaignController as AdminCampaignController;
use App\Http\Controllers\Admin\CheckNewCampaignsController;
use App\Http\Controllers\Admin\ImportAdsOfWorldController;
use App\Http\Controllers\Admin\ImportCampaignController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CampaignRevisionController as AdminCampaignRevisionController;
use App\Http\Controllers\Admin\TaxonomyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\WatchingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PersonApplicationController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\ProfileCampaignsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/help', [PageController::class, 'help'])->name('pages.help');
Route::get('/contact', [PageController::class, 'contact'])->name('pages.contact');
Route::get('/about', [PageController::class, 'about'])->name('pages.about');
Route::get('/submit-advertise', [PageController::class, 'submitAdvertise'])->name('pages.submit-advertise');
Route::get('/terms-policies', [PageController::class, 'termsPolicies'])->name('pages.terms-policies');
Route::get('/editorial-standards', [PageController::class, 'editorialStandards'])->name('pages.editorial-standards');

Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');

Route::middleware(['auth', 'verified', 'noindex'])->group(function () {
    Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{campaign:slug}/pending-review', [CampaignController::class, 'pendingReview'])->name('campaigns.pending-review');
});

Route::get('/campaigns/{campaign:slug}', [CampaignController::class, 'show'])->name('campaigns.show');

Route::get('/agencies', [AgencyController::class, 'index'])->name('agencies.index');
Route::get('/agencies/{agency:slug}', [AgencyController::class, 'show'])->name('agencies.show');

Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');

Route::get('/people', [PersonController::class, 'index'])->name('people.index');
Route::get('/people/apply', [PersonApplicationController::class, 'create'])->name('people.apply');
Route::post('/people/apply', [PersonApplicationController::class, 'store'])->name('people.apply.store');
Route::get('/people/{person:slug}', [PersonController::class, 'show'])->name('people.show');

Route::get('/users/{user:username}', [UserProfileController::class, 'show'])->name('users.show');

// Requires verified email
Route::middleware(['auth', 'verified', 'noindex'])->group(function () {
    Route::get('/campaigns/{campaign:slug}/edit', [CampaignController::class, 'edit'])->name('campaigns.edit');
    Route::put('/campaigns/{campaign:slug}', [CampaignController::class, 'update'])->name('campaigns.update');

    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks.index');
    Route::post('/campaigns/{campaign:slug}/bookmark', [BookmarkController::class, 'store'])->name('campaigns.bookmark.store');
    Route::delete('/campaigns/{campaign:slug}/bookmark', [BookmarkController::class, 'destroy'])->name('campaigns.bookmark.destroy');

    Route::get('/following', [WatchingController::class, 'index'])->name('following.index');
    Route::post('/campaigns/{campaign:slug}/watch', [WatchingController::class, 'store'])->name('campaigns.watch.store');
    Route::delete('/campaigns/{campaign:slug}/watch', [WatchingController::class, 'destroy'])->name('campaigns.watch.destroy');

    Route::post('/users/{user:username}/follow', [FollowController::class, 'store'])->name('users.follow');
    Route::delete('/users/{user:username}/follow', [FollowController::class, 'destroy'])->name('users.unfollow');

    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile', [ProfileController::class, 'showRedirect'])->name('profile.show.redirect');
    Route::get('/profile/campaigns', [ProfileCampaignsController::class, 'index'])->name('profile.campaigns');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin', 'noindex'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/import-campaign', [ImportCampaignController::class, 'create'])->name('import-campaign.create');
    Route::post('/import-campaign', [ImportCampaignController::class, 'store'])->name('import-campaign.store');

    Route::get('/check-new-campaigns', [CheckNewCampaignsController::class, 'index'])->name('check-new-campaigns.index');
    Route::post('/check-new-campaigns', [CheckNewCampaignsController::class, 'start'])->name('check-new-campaigns.start');
    Route::get('/check-new-campaigns/{batch}', [CheckNewCampaignsController::class, 'show'])->name('check-new-campaigns.show');
    Route::get('/check-new-campaigns/{batch}/status', [CheckNewCampaignsController::class, 'status'])->name('check-new-campaigns.status');
    Route::post('/check-new-campaigns/{batch}/process', [CheckNewCampaignsController::class, 'process'])->name('check-new-campaigns.process');
    Route::post('/check-new-campaigns/{batch}/pause', [CheckNewCampaignsController::class, 'pause'])->name('check-new-campaigns.pause');
    Route::post('/check-new-campaigns/{batch}/resume', [CheckNewCampaignsController::class, 'resume'])->name('check-new-campaigns.resume');

    /*
     | Bulk import routes intentionally disabled
     | ---------------------------------------
     | We ran an initial Ads of the World archive import, and we keep all imported campaigns,
     | media, and the `import_batches` / `import_queue` data for provenance. However, the admin
     | UI/tools for bulk import are disabled to prevent accidental re-imports or deletes.
     |
     | Single-campaign import remains available at /admin/import-campaign.
     */
    // Route::get('/import-ads-of-world', [ImportAdsOfWorldController::class, 'index'])->name('import.queue');
    // Route::post('/import-ads-of-world', [ImportAdsOfWorldController::class, 'store'])->name('import.queue.store');
    // Route::post('/import-ads-of-world/delete-last', [ImportAdsOfWorldController::class, 'deleteLast'])->name('import.delete-last');
    // Route::get('/import-ads-of-world/{batch}/status', [ImportAdsOfWorldController::class, 'status'])->name('import-ads-of-world.status');
    // Route::post('/import-ads-of-world/{batch}/process', [ImportAdsOfWorldController::class, 'process'])->name('import-ads-of-world.process');
    // Route::post('/import-ads-of-world/{batch}/pause', [ImportAdsOfWorldController::class, 'pause'])->name('import-ads-of-world.pause');
    // Route::post('/import-ads-of-world/{batch}/resume', [ImportAdsOfWorldController::class, 'resume'])->name('import-ads-of-world.resume');
    // Route::post('/import-ads-of-world/{batch}/retry-failed', [ImportAdsOfWorldController::class, 'retryFailed'])->name('import-ads-of-world.retry-failed');
    // Route::get('/import-ads-of-world/{batch}', [ImportAdsOfWorldController::class, 'show'])->name('import-ads-of-world.show');

    Route::get('/campaigns', [AdminCampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create', [AdminCampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [AdminCampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{campaign:id}/edit', [AdminCampaignController::class, 'edit'])->name('campaigns.edit');
    Route::put('/campaigns/{campaign:id}', [AdminCampaignController::class, 'update'])->name('campaigns.update');
    Route::get('/campaigns/{campaign}', [AdminCampaignController::class, 'show'])->name('campaigns.show');
    Route::post('/campaigns/{campaign}/approve', [AdminCampaignController::class, 'approve'])->name('campaigns.approve');
    Route::post('/campaigns/{campaign}/reject', [AdminCampaignController::class, 'reject'])->name('campaigns.reject');
    Route::post('/campaigns/{campaign}/feature', [AdminCampaignController::class, 'feature'])->name('campaigns.feature');
    Route::put('/campaigns/{campaign}/hero', [AdminCampaignController::class, 'updateHero'])->name('campaigns.hero');
    Route::put('/campaigns/{campaign}/verification', [AdminCampaignController::class, 'updateVerification'])->name('campaigns.verification');
    Route::delete('/campaigns/{campaign}', [AdminCampaignController::class, 'destroy'])->name('campaigns.destroy');

    Route::get('/revisions', [AdminCampaignRevisionController::class, 'index'])->name('revisions.index');
    Route::get('/revisions/{revision}', [AdminCampaignRevisionController::class, 'show'])->name('revisions.show');
    Route::post('/revisions/{revision}/approve', [AdminCampaignRevisionController::class, 'approve'])->name('revisions.approve');
    Route::post('/revisions/{revision}/reject', [AdminCampaignRevisionController::class, 'reject'])->name('revisions.reject');

    Route::get('/brands', [TaxonomyController::class, 'brands'])->name('brands.index');
    Route::get('/brands/{id}', [TaxonomyController::class, 'showBrand'])->name('brands.show')->whereNumber('id');
    Route::post('/brands', [TaxonomyController::class, 'storeBrands'])->name('brands.store');
    Route::put('/brands/{id}', [TaxonomyController::class, 'updateBrand'])->name('brands.update')->whereNumber('id');
    Route::put('/brands/{id}/verification', [TaxonomyController::class, 'verifyBrand'])->name('brands.verification')->whereNumber('id');
    Route::delete('/brands/{id}', [TaxonomyController::class, 'destroyBrand'])->name('brands.destroy')->whereNumber('id');

    Route::get('/agencies', [TaxonomyController::class, 'agencies'])->name('agencies.index');
    Route::get('/agencies/{id}', [TaxonomyController::class, 'showAgency'])->name('agencies.show')->whereNumber('id');
    Route::post('/agencies', [TaxonomyController::class, 'storeAgencies'])->name('agencies.store');
    Route::put('/agencies/{id}', [TaxonomyController::class, 'updateAgency'])->name('agencies.update')->whereNumber('id');
    Route::put('/agencies/{id}/verification', [TaxonomyController::class, 'verifyAgency'])->name('agencies.verification')->whereNumber('id');
    Route::delete('/agencies/{id}', [TaxonomyController::class, 'destroyAgency'])->name('agencies.destroy')->whereNumber('id');

    Route::get('/industries', [TaxonomyController::class, 'industries'])->name('industries.index');
    Route::post('/industries', [TaxonomyController::class, 'storeIndustries'])->name('industries.store');
    Route::put('/industries/{id}', [TaxonomyController::class, 'updateIndustry'])->name('industries.update')->whereNumber('id');
    Route::delete('/industries/{id}', [TaxonomyController::class, 'destroyIndustry'])->name('industries.destroy')->whereNumber('id');

    Route::get('/medium-types', [TaxonomyController::class, 'mediumTypes'])->name('medium-types.index');
    Route::post('/medium-types', [TaxonomyController::class, 'storeMediumTypes'])->name('medium-types.store');
    Route::put('/medium-types/{id}', [TaxonomyController::class, 'updateMediumType'])->name('medium-types.update')->whereNumber('id');
    Route::delete('/medium-types/{id}', [TaxonomyController::class, 'destroyMediumType'])->name('medium-types.destroy')->whereNumber('id');

    Route::get('/countries', [TaxonomyController::class, 'countries'])->name('countries.index');
    Route::post('/countries', [TaxonomyController::class, 'storeCountries'])->name('countries.store');
    Route::put('/countries/{id}', [TaxonomyController::class, 'updateCountry'])->name('countries.update')->whereNumber('id');
    Route::delete('/countries/{id}', [TaxonomyController::class, 'destroyCountry'])->name('countries.destroy')->whereNumber('id');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/people', [AdminPersonController::class, 'index'])->name('people.index');
    Route::get('/people/create', [AdminPersonController::class, 'create'])->name('people.create');
    Route::post('/people', [AdminPersonController::class, 'store'])->name('people.store');
    Route::get('/people/{person}', [AdminPersonController::class, 'show'])->name('people.show');
    Route::get('/people/{person}/edit', [AdminPersonController::class, 'edit'])->name('people.edit');
    Route::put('/people/{person}', [AdminPersonController::class, 'update'])->name('people.update');
    Route::post('/people/{person}/approve', [AdminPersonController::class, 'approve'])->name('people.approve');
    Route::post('/people/{person}/reject', [AdminPersonController::class, 'reject'])->name('people.reject');
    Route::delete('/people/{person}', [AdminPersonController::class, 'destroy'])->name('people.destroy');
});

require __DIR__.'/auth.php';
