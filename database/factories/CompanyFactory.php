<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Company\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = \App\Modules\Company\Models\Company::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'country' => 'Deutschland',
            'tax_number' => fake()->numerify('### ### #####'),
            'vat_number' => 'DE' . fake()->numerify('#########'),
            'commercial_register' => 'HRB ' . fake()->numerify('######'),
            'managing_director' => fake()->name(),
            'bank_name' => fake()->company() . ' Bank',
            'bank_iban' => 'DE' . fake()->numerify('####################'),
            'bank_bic' => fake()->regexify('[A-Z]{4}DE[A-Z0-9]{2}[0-9]{3}'),
            'website' => fake()->url(),
            'status' => 'active',
        ];
    }
}

