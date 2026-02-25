<?php

namespace App\Providers;

use App\Modules\Calendar\Models\CalendarEvent;
use App\Modules\Calendar\Policies\CalendarEventPolicy;
use App\Modules\Company\Models\Company;
use App\Modules\Company\Policies\CompanyPolicy;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Policies\CustomerPolicy;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Policies\DocumentPolicy;
use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Policies\ExpensePolicy;
use App\Modules\Expense\Models\ExpenseCategory;
use App\Modules\Expense\Policies\ExpenseCategoryPolicy;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Policies\InvoicePolicy;
use App\Modules\Invoice\Models\InvoiceLayout;
use App\Modules\Invoice\Policies\InvoiceLayoutPolicy;
use App\Modules\Offer\Models\Offer;
use App\Modules\Offer\Policies\OfferPolicy;
use App\Modules\Offer\Models\OfferLayout;
use App\Modules\Offer\Policies\OfferLayoutPolicy;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Policies\PaymentPolicy;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Policies\CategoryPolicy;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Policies\ProductPolicy;
use App\Modules\Product\Models\Warehouse;
use App\Modules\Product\Policies\WarehousePolicy;
use App\Modules\User\Models\User;
use App\Modules\User\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        CalendarEvent::class    => CalendarEventPolicy::class,
        Category::class         => CategoryPolicy::class,
        Company::class          => CompanyPolicy::class,
        Customer::class         => CustomerPolicy::class,
        Document::class         => DocumentPolicy::class,
        Expense::class          => ExpensePolicy::class,
        ExpenseCategory::class  => ExpenseCategoryPolicy::class,
        Invoice::class          => InvoicePolicy::class,
        InvoiceLayout::class    => InvoiceLayoutPolicy::class,
        Offer::class            => OfferPolicy::class,
        OfferLayout::class      => OfferLayoutPolicy::class,
        Payment::class          => PaymentPolicy::class,
        Product::class          => ProductPolicy::class,
        User::class             => UserPolicy::class,
        Warehouse::class        => WarehousePolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
