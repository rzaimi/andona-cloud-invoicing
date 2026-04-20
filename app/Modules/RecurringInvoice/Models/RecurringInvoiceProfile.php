<?php

namespace App\Modules\RecurringInvoice\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceLayout;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringInvoiceProfile extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const INTERVAL_DAY = 'day';

    public const INTERVAL_WEEK = 'week';

    public const INTERVAL_MONTH = 'month';

    public const INTERVAL_QUARTER = 'quarter';

    public const INTERVAL_YEAR = 'year';

    protected $fillable = [
        'company_id',
        'customer_id',
        'user_id',
        'layout_id',
        'name',
        'description',
        'vat_regime',
        'tax_rate',
        'payment_method',
        'payment_terms',
        'skonto_percent',
        'skonto_days',
        'due_days_after_issue',
        'notes',
        'bauvorhaben',
        'auftragsnummer',
        'interval_unit',
        'interval_count',
        'day_of_month',
        'start_date',
        'end_date',
        'max_occurrences',
        'occurrences_count',
        'next_run_date',
        'last_run_date',
        'status',
        'paused_until',
        'auto_send',
        'email_subject_template',
        'email_body_template',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:4',
        'skonto_percent' => 'decimal:2',
        'skonto_days' => 'integer',
        'due_days_after_issue' => 'integer',
        'interval_count' => 'integer',
        'day_of_month' => 'integer',
        'max_occurrences' => 'integer',
        'occurrences_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_run_date' => 'date',
        'last_run_date' => 'date',
        'paused_until' => 'date',
        'auto_send' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(InvoiceLayout::class, 'layout_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecurringInvoiceItem::class, 'recurring_profile_id')
            ->orderBy('sort_order');
    }

    /**
     * All invoices generated from this profile (most recent first).
     */
    public function generatedInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'recurring_profile_id')
            ->orderByDesc('issue_date');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Profiles eligible for generation on the given date.
     *
     * Filters `status=active`, `next_run_date <= date`, respects end_date,
     * max_occurrences and paused_until.
     */
    public function scopeDueFor($query, CarbonImmutable|Carbon $date)
    {
        $d = $date instanceof CarbonImmutable
            ? Carbon::instance($date->toDateTimeImmutable())
            : $date->copy();

        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->whereDate('next_run_date', '<=', $d->toDateString())
            ->where(function ($q) use ($d) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $d->toDateString());
            })
            ->where(function ($q) use ($d) {
                $q->whereNull('paused_until')
                    ->orWhereDate('paused_until', '<=', $d->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('max_occurrences')
                    ->orWhereColumn('occurrences_count', '<', 'max_occurrences');
            });
    }

    /**
     * Route model binding mirrors Invoice — hard-fail cross-tenant access
     * even if the UUID leaks.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $profile = parent::resolveRouteBinding($value, $field);

        if (! $profile) {
            return null;
        }

        if (auth()->check()) {
            $user = auth()->user();
            if ($user->company_id !== $profile->company_id && ! $user->hasPermissionTo('manage_companies')) {
                abort(403, 'Unauthorized access to recurring invoice profile');
            }
        }

        return $profile;
    }

    /**
     * Human-readable summary of the schedule, e.g. "Alle 2 Monate am 15.".
     */
    public function getScheduleLabelAttribute(): string
    {
        $every = $this->interval_count > 1
            ? "Alle {$this->interval_count} "
            : 'Jeden ';

        $unitLabel = match ($this->interval_unit) {
            self::INTERVAL_DAY => $this->interval_count > 1 ? 'Tage' : 'Tag',
            self::INTERVAL_WEEK => $this->interval_count > 1 ? 'Wochen' : 'Woche',
            self::INTERVAL_MONTH => $this->interval_count > 1 ? 'Monate' : 'Monat',
            self::INTERVAL_QUARTER => $this->interval_count > 1 ? 'Quartale' : 'Quartal',
            self::INTERVAL_YEAR => $this->interval_count > 1 ? 'Jahre' : 'Jahr',
            default => $this->interval_unit,
        };

        $label = $every.$unitLabel;

        if ($this->day_of_month
            && in_array($this->interval_unit, [self::INTERVAL_MONTH, self::INTERVAL_QUARTER, self::INTERVAL_YEAR], true)
        ) {
            $label .= ' am '.$this->day_of_month.'.';
        }

        return $label;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Aktiv',
            self::STATUS_PAUSED => 'Pausiert',
            self::STATUS_COMPLETED => 'Abgeschlossen',
            self::STATUS_CANCELLED => 'Abgebrochen',
            default => $this->status,
        };
    }

    /**
     * Returns the next N scheduled run dates (not including already-generated
     * invoices). Purely for UI preview — doesn't mutate state.
     */
    public function previewNextRuns(int $count = 5, ?CarbonImmutable $from = null): array
    {
        $from ??= CarbonImmutable::today();
        $cursor = CarbonImmutable::instance($this->next_run_date ?? $from);
        $remaining = $this->max_occurrences !== null
            ? max(0, $this->max_occurrences - $this->occurrences_count)
            : $count;

        $dates = [];
        for ($i = 0; $i < min($count, $remaining); $i++) {
            if ($this->end_date && $cursor->greaterThan(CarbonImmutable::instance($this->end_date))) {
                break;
            }
            $dates[] = $cursor->toDateString();
            $cursor = static::advanceDate($cursor, $this->interval_unit, $this->interval_count, $this->day_of_month);
        }

        return $dates;
    }

    /**
     * Shared date-math helper used by both `previewNextRuns` and the service
     * that actually advances the schedule after a successful generation.
     *
     * For monthly/quarterly/yearly intervals with a `day_of_month`, we clamp
     * to the last day of the target month so e.g. "31st monthly" still
     * generates in February.
     */
    public static function advanceDate(
        CarbonImmutable $from,
        string $intervalUnit,
        int $intervalCount,
        ?int $dayOfMonth = null
    ): CarbonImmutable {
        $next = match ($intervalUnit) {
            self::INTERVAL_DAY => $from->addDays($intervalCount),
            self::INTERVAL_WEEK => $from->addWeeks($intervalCount),
            self::INTERVAL_MONTH => $from->addMonthsNoOverflow($intervalCount),
            self::INTERVAL_QUARTER => $from->addMonthsNoOverflow($intervalCount * 3),
            self::INTERVAL_YEAR => $from->addYearsNoOverflow($intervalCount),
            default => $from->addMonthsNoOverflow($intervalCount),
        };

        if ($dayOfMonth !== null
            && in_array($intervalUnit, [self::INTERVAL_MONTH, self::INTERVAL_QUARTER, self::INTERVAL_YEAR], true)
        ) {
            $clamped = min($dayOfMonth, $next->daysInMonth);
            $next = $next->setDay($clamped);
        }

        return $next;
    }
}
