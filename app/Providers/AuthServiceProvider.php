<?php

namespace App\Providers;

use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Policies\CustomerPolicy;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Policies\InvoicePolicy;
use App\Modules\Offer\Models\Offer;
use App\Modules\Offer\Policies\OfferPolicy;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Policies\PaymentPolicy;
use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Policies\ExpensePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Customer::class => CustomerPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Offer::class => OfferPolicy::class,
        Payment::class => PaymentPolicy::class,
        Expense::class => ExpensePolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
