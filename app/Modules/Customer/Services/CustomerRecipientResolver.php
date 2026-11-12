<?php

namespace App\Modules\Customer\Services;

use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerContact;

/**
 * Decides who receives an email for a customer.
 *
 * Contacts flagged for the purpose (invoice / reminder / offer) win: the
 * first is the recipient, further flagged contacts are CC'd. Without a
 * flagged contact the customer's main email is used, exactly as before
 * contacts existed. Manual send dialogs and all automatic emails share this
 * rule so they can never disagree.
 */
class CustomerRecipientResolver
{
    /**
     * @return array{to: ?string, to_name: ?string, cc: list<string>}
     */
    public function resolve(?Customer $customer, string $purpose): array
    {
        if (! $customer) {
            return ['to' => null, 'to_name' => null, 'cc' => []];
        }

        $column = CustomerContact::PURPOSE_COLUMNS[$purpose] ?? null;

        $flagged = $column
            ? $customer->contacts
                ->filter(fn (CustomerContact $contact) => $contact->{$column} && filled($contact->email))
                ->values()
            : collect();

        if ($flagged->isEmpty()) {
            return [
                'to' => filled($customer->email) ? $customer->email : null,
                'to_name' => $customer->name,
                'cc' => [],
            ];
        }

        $primary = $flagged->first();

        return [
            'to' => $primary->email,
            'to_name' => $primary->name,
            'cc' => $flagged->skip(1)
                ->pluck('email')
                ->reject(fn ($email) => strcasecmp($email, $primary->email) === 0)
                ->unique()
                ->values()
                ->all(),
        ];
    }

    public function hasRecipient(?Customer $customer, string $purpose): bool
    {
        return $this->resolve($customer, $purpose)['to'] !== null;
    }

    /**
     * Normalize a CC value to a list of addresses. Accepts the array the
     * send dialog posts, a legacy single address, or a comma/semicolon
     * separated string.
     *
     * @return list<string>
     */
    public static function normalizeCc(mixed $cc): array
    {
        if (is_string($cc)) {
            $cc = preg_split('/[,;]+/', $cc) ?: [];
        }

        if (! is_array($cc)) {
            return [];
        }

        return collect($cc)
            ->filter(fn ($address) => is_string($address))
            ->map(fn (string $address) => trim($address))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
