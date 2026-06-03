<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Policies\CampaignPolicy;
use App\View\Composers\SeoLayoutComposer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Campaign::class, CampaignPolicy::class);

        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.archive');
        \Illuminate\Pagination\Paginator::defaultSimpleView('vendor.pagination.archive');

        View::composer('layouts.app', SeoLayoutComposer::class);
    }
}
