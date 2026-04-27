<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Client;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Subcategory;
use App\Policies\CategoryPolicy;
use App\Policies\ClientPolicy;
use App\Policies\DiscountPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SubcategoryPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Category::class => CategoryPolicy::class,
        Client::class => ClientPolicy::class,
        Subcategory::class => SubcategoryPolicy::class,
        Product::class => ProductPolicy::class,
        Discount::class => DiscountPolicy::class,
    ];

    /**
     * Register any application authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
