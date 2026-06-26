<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Payments;
use App\Models\Operator;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\Supplier;
use App\Observers\AuditableObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            Category::class,
            Customer::class,
            Operator::class,
            Payments::class,
            Product::class,
            RestaurantTable::class,
            Sale::class,
            Shift::class,
            Supplier::class,
        ] as $model) {
            $model::observe(AuditableObserver::class);
        }
    }
}
