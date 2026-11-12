<?php

namespace App\Modules\Customer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerContact;
use App\Modules\Customer\Services\CustomerRecipientResolver;
use Illuminate\Http\Request;

/**
 * Contact persons of a customer. Authorization rides on the customer's
 * policy (which scopes by company); a contact must belong to the customer
 * in the URL.
 */
class CustomerContactController extends Controller
{
    /**
     * JSON for the send dialog: the contacts plus the default recipients
     * for the given purpose.
     */
    public function index(Request $request, Customer $customer, CustomerRecipientResolver $resolver)
    {
        $this->authorize('view', $customer);

        $purpose = (string) $request->query('purpose', CustomerContact::PURPOSE_INVOICE);
        if (! array_key_exists($purpose, CustomerContact::PURPOSE_COLUMNS)) {
            $purpose = CustomerContact::PURPOSE_INVOICE;
        }

        return response()->json([
            'contacts' => $customer->contacts
                ->filter(fn (CustomerContact $contact) => filled($contact->email))
                ->map(fn (CustomerContact $contact) => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'title' => $contact->title,
                    'is_primary' => $contact->is_primary,
                ])
                ->values(),
            'defaults' => $resolver->resolve($customer, $purpose),
        ]);
    }

    public function store(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer->contacts()->create(array_merge(
            $this->validated($request),
            ['company_id' => $customer->company_id]
        ));

        return back()->with('success', 'Ansprechpartner wurde hinzugefügt.');
    }

    public function update(Request $request, Customer $customer, CustomerContact $contact)
    {
        $this->authorize('update', $customer);
        $this->ensureBelongs($customer, $contact);

        $contact->update($this->validated($request));

        return back()->with('success', 'Ansprechpartner wurde aktualisiert.');
    }

    public function destroy(Customer $customer, CustomerContact $contact)
    {
        $this->authorize('update', $customer);
        $this->ensureBelongs($customer, $contact);

        $contact->delete();

        return back()->with('success', 'Ansprechpartner wurde gelöscht.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'title' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'is_primary' => 'sometimes|boolean',
            'receives_invoices' => 'sometimes|boolean',
            'receives_reminders' => 'sometimes|boolean',
            'receives_offers' => 'sometimes|boolean',
        ], [
            'name.required' => 'Bitte geben Sie einen Namen ein.',
            'email.required' => 'Bitte geben Sie eine E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
        ]);

        foreach (['is_primary', 'receives_invoices', 'receives_reminders', 'receives_offers'] as $flag) {
            if (array_key_exists($flag, $validated)) {
                $validated[$flag] = (bool) $validated[$flag];
            }
        }

        return $validated;
    }

    private function ensureBelongs(Customer $customer, CustomerContact $contact): void
    {
        abort_unless($contact->customer_id === $customer->id, 404);
    }
}
