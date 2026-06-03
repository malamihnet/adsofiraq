<?php

use App\Http\Controllers\Admin\PersonController as AdminPersonController;
use App\Http\Controllers\Admin\ArchivePlacementController;
use App\Http\Controllers\Admin\CampaignController as AdminCampaignController;
use App\Http\Controllers\Admin\CheckNewCampaignsController;
use App\Http\Controllers\Admin\ImportAdsOfWorldController;
use App\Http\Controllers\Admin\ImportCampaignController;
use App\Http\Controllers\Admin\CampaignResetController;
use App\Http\Controllers\Admin\MediaMaintenanceController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SeoReportController;
use App\Http\Controllers\Admin\CampaignRevisionController as AdminCampaignRevisionController;
use App\Http\Controllers\Admin\TaxonomyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AwardsController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\MadeByIraqController;
use App\Http\Controllers\PersonCreditApiController;
use App\Http\Controllers\PositionApiController;
use App\Http\Controllers\SeoLandingController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\WatchingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PersonApplicationController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\ProfileCampaignsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RankingsController;
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
Route::get('/featured', [CampaignController::class, 'editorsPick'])->name('featured.index');

Route::middleware(['auth', 'verified', 'noindex'])->group(function () {
    Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{campaign:slug}/pending-review', [CampaignController::class, 'pendingReview'])->name('campaigns.pending-review');

    Route::get('/api/people/search', [PersonCreditApiController::class, 'search'])->name('api.people.search');
    Route::post('/api/people', [PersonCreditApiController::class, 'store'])->name('api.people.store');
    Route::get('/api/positions', [PositionApiController::class, 'index'])->name('api.positions.index');
    Route::post('/api/positions', [PositionApiController::class, 'store'])->name('api.positions.store');
});

Route::get('/campaigns/{campaign:slug}', [CampaignController::class, 'show'])->name('campaigns.show');

Route::get('/agency/{agency:slug}', [AgencyController::class, 'show'])->name('agency.show');
Route::get('/agencies', [AgencyController::class, 'index'])->name('agencies.index');
Route::get('/agencies/{agency:slug}', fn (\App\Models\Agency $agency) => redirect()->route('agency.show', $agency, 301))
    ->name('agencies.show');

Route::get('/brand/{brand:slug}', [BrandController::class, 'show'])->name('brand.show');
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand:slug}', fn (\App\Models\Brand $brand) => redirect()->route('brand.show', $brand, 301))
    ->name('brands.show');

Route::get('/person/{person:slug}', [PersonController::class, 'show'])->name('person.show');
Route::get('/people', [PersonController::class, 'index'])->name('people.index');
Route::get('/people/apply', [PersonApplicationController::class, 'create'])->name('people.apply');
Route::post('/people/apply', [PersonApplicationController::class, 'store'])->name('people.apply.store');
Route::get('/people/{person:slug}', fn (\App\Models\Person $person) => redirect()->route('person.show', $person, 301))
    ->name('people.show');

Route::get('/rankings', [RankingsController::class, 'index'])->name('rankings.index');
Route::get('/top-agencies-iraq', [RankingsController::class, 'topAgencies'])->name('rankings.top-agencies');
Route::get('/top-production-houses', [RankingsController::class, 'topProductionHouses'])->name('rankings.top-production-houses');
Route::get('/top-directors-iraq', [RankingsController::class, 'topDirectors'])->name('rankings.top-directors');
Route::get('/top-editors-iraq', [RankingsController::class, 'topEditors'])->name('rankings.top-editors');
Route::get('/top-brands-iraq', [RankingsController::class, 'topBrands'])->name('rankings.top-brands');
Route::get('/top-commercials-iraq', [RankingsController::class, 'topCommercials'])->name('rankings.top-commercials');
Route::get('/top-creative-directors-iraq', [RankingsController::class, 'topCreativeDirectors'])->name('rankings.top-creative-directors');
Route::get('/most-viewed-campaigns', [RankingsController::class, 'mostViewed'])->name('rankings.most-viewed');
Route::get('/trending', [RankingsController::class, 'trending'])->name('rankings.trending');
Route::get('/most-appreciated', [RankingsController::class, 'mostAppreciated'])->name('rankings.most-appreciated');

Route::get('/made-by-iraq', [MadeByIraqController::class, 'index'])->name('made-by-iraq.index');

Route::get('/iraqi-advertising', [SeoLandingController::class, 'iraqiAdvertising'])->name('landing.iraqi-advertising');
Route::get('/iraq-agencies', [SeoLandingController::class, 'iraqAgencies'])->name('landing.iraq-agencies');
Route::get('/iraq-production-houses', [SeoLandingController::class, 'iraqProductionHouses'])->name('landing.iraq-production-houses');
Route::get('/iraq-commercials', [SeoLandingController::class, 'iraqCommercials'])->name('landing.iraq-commercials');
Route::get('/iraq-tv-commercials', [SeoLandingController::class, 'iraqTvCommercials'])->name('landing.iraq-tv-commercials');
Route::get('/iraq-creative-industry', [SeoLandingController::class, 'iraqCreativeIndustry'])->name('landing.iraq-creative-industry');

Route::get('/tag/{tag:slug}', [TagController::class, 'show'])->name('tags.show');

Route::get('/awards', [AwardsController::class, 'index'])->name('awards.index');
Route::get('/awards/{award:slug}', [AwardsController::class, 'show'])->name('awards.show');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-campaigns.xml', [SitemapController::class, 'campaigns'])->name('sitemap.campaigns');
Route::get('/sitemap-agencies.xml', [SitemapController::class, 'agencies'])->name('sitemap.agencies');
Route::get('/sitemap-production-houses.xml', [SitemapController::class, 'productionHouses'])->name('sitemap.production-houses');
Route::get('/sitemap-brands.xml', [SitemapController::class, 'brands'])->name('sitemap.brands');
Route::get('/sitemap-people.xml', [SitemapController::class, 'people'])->name('sitemap.people');
Route::get('/sitemap-categories.xml', [SitemapController::class, 'categories'])->name('sitemap.categories');
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/sitemap-tags.xml', [SitemapController::class, 'tags'])->name('sitemap.tags');
Route::get('/sitemap-rankings.xml', [SitemapController::class, 'rankings'])->name('sitemap.rankings');
Route::get('/sitemap-landing-pages.xml', [SitemapController::class, 'landingPages'])->name('sitemap.landing-pages');

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
    Route::get('/api/people/search', [PersonCreditApiController::class, 'search'])->name('api.people.search');
    Route::post('/api/people', [PersonCreditApiController::class, 'store'])->name('api.people.store');
    Route::get('/api/positions', [PositionApiController::class, 'index'])->name('api.positions.index');
    Route::post('/api/positions', [PositionApiController::class, 'store'])->name('api.positions.store');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/seo-report', [SeoReportController::class, 'index'])->name('seo-report.index');

    Route::get('/import-campaign', [ImportCampaignController::class, 'create'])->name('import-campaign.create');
    Route::post('/import-campaign/debug-parse', [ImportCampaignController::class, 'debugParse'])->name('import-campaign.debug-parse');
    Route::post('/import-campaign', [ImportCampaignController::class, 'store'])->name('import-campaign.store');
    Route::post('/import-campaign/repair-media', [ImportCampaignController::class, 'repairMedia'])->name('import-campaign.repair-media');
    Route::post('/import-campaign/remove-duplicate-stills', [ImportCampaignController::class, 'removeDuplicateStills'])->name('import-campaign.remove-duplicate-stills');
    Route::post('/import-campaign/sync-public-storage', [ImportCampaignController::class, 'syncPublicStorage'])->name('import-campaign.sync-public-storage');
    Route::post('/sync-public-storage', [ImportCampaignController::class, 'syncPublicStorage'])->name('sync-public-storage');

    Route::get('/maintenance/clean-duplicate-media', [MediaMaintenanceController::class, 'index'])->name('maintenance.clean-duplicate-media');
    Route::post('/maintenance/clean-duplicate-media', [MediaMaintenanceController::class, 'cleanDuplicateMedia'])->name('maintenance.clean-duplicate-media.run');
    Route::post('/maintenance/clean-non-gallery-stills', [MediaMaintenanceController::class, 'cleanNonGalleryStills'])->name('maintenance.clean-non-gallery-stills');
    Route::post('/maintenance/cleanup-orphans', [MediaMaintenanceController::class, 'cleanupOrphans'])->name('maintenance.cleanup-orphans');

    Route::get('/maintenance/reset-all-campaigns', [CampaignResetController::class, 'index'])->name('maintenance.reset-all-campaigns');
    Route::post('/maintenance/reset-all-campaigns/dry-run', [CampaignResetController::class, 'dryRun'])->name('maintenance.reset-all-campaigns.dry-run');
    Route::post('/maintenance/reset-all-campaigns/start', [CampaignResetController::class, 'start'])->name('maintenance.reset-all-campaigns.start');
    Route::get('/maintenance/reset-all-campaigns/{session}/progress', [CampaignResetController::class, 'progress'])->name('maintenance.reset-all-campaigns.progress');
    Route::get('/maintenance/reset-all-campaigns/{session}/status', [CampaignResetController::class, 'status'])->name('maintenance.reset-all-campaigns.status');
    Route::post('/maintenance/reset-all-campaigns/{session}/tick', [CampaignResetController::class, 'tick'])->name('maintenance.reset-all-campaigns.tick');
    Route::post('/maintenance/reset-all-campaigns/{session}/pause', [CampaignResetController::class, 'pause'])->name('maintenance.reset-all-campaigns.pause');
    Route::post('/maintenance/reset-all-campaigns/{session}/resume', [CampaignResetController::class, 'resume'])->name('maintenance.reset-all-campaigns.resume');

    Route::get('/check-new-campaigns', [CheckNewCampaignsController::class, 'index'])->name('check-new-campaigns.index');
    Route::post('/check-new-campaigns', [CheckNewCampaignsController::class, 'start'])->name('check-new-campaigns.start');
    Route::post('/check-new-campaigns/full-rebuild', [CheckNewCampaignsController::class, 'startFullRebuild'])->name('check-new-campaigns.start-full-rebuild');
    Route::get('/check-new-campaigns/{batch}', [CheckNewCampaignsController::class, 'show'])->name('check-new-campaigns.show');
    Route::get('/check-new-campaigns/{batch}/status', [CheckNewCampaignsController::class, 'status'])->name('check-new-campaigns.status');
    Route::post('/check-new-campaigns/{batch}/process', [CheckNewCampaignsController::class, 'process'])->name('check-new-campaigns.process');
    Route::post('/check-new-campaigns/{batch}/pause', [CheckNewCampaignsController::class, 'pause'])->name('check-new-campaigns.pause');
    Route::post('/check-new-campaigns/{batch}/resume', [CheckNewCampaignsController::class, 'resume'])->name('check-new-campaigns.resume');
    Route::post('/check-new-campaigns/{batch}/retry-failed', [CheckNewCampaignsController::class, 'retryFailed'])->name('check-new-campaigns.retry-failed');

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
    Route::get('/archive-placements', [ArchivePlacementController::class, 'index'])->name('archive-placements.index');
    Route::post('/archive-placements', [ArchivePlacementController::class, 'store'])->name('archive-placements.store');
    Route::delete('/archive-placements/{campaign:id}', [ArchivePlacementController::class, 'destroy'])->name('archive-placements.destroy');
    Route::post('/archive-placements/clear-all', [ArchivePlacementController::class, 'clearAll'])->name('archive-placements.clear-all');
    Route::post('/archive-placements/clear-legacy-manual-order', [ArchivePlacementController::class, 'clearLegacyManualOrder'])->name('archive-placements.clear-legacy-manual-order');
    Route::get('/campaigns/create', [AdminCampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [AdminCampaignController::class, 'store'])->name('campaigns.store');
    Route::match(['patch', 'post'], '/campaigns/{campaign:id}/inline', [AdminCampaignController::class, 'inlineUpdate'])->name('campaigns.inline');
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
    Route::post('/agencies/backfill-roles', [TaxonomyController::class, 'backfillAgencyRoles'])->name('agencies.backfill-roles');
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
