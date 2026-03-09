<?php

namespace App\Http\Controllers;

use App\Modules\Company\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class CompanyInitController extends Controller
{
    private const COMPANY_TYPES = [
        'gartenbau'       => 'Garten- und Außenanlagenbau',
        'bauunternehmen'  => 'Bauunternehmen',
        'dachdecker'      => 'Dachdeckerei & Spenglerarbeiten',
        'raumausstattung' => 'Raumausstattung & Fliesenarbeiten',
        'gebaudetechnik'  => 'Gebäudetechnik',
        'logistik'        => 'Logistik & Palettenhandel',
        'handel'          => 'Handelsunternehmen',
        'dienstleistung'  => 'Sonstige Dienstleistungen',
        'it-webagentur'   => 'IT & Webagentur',
        'buchhaltung'     => 'Buchhaltung & Steuern',
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        $companies = Company::orderBy('name')
            ->get(['id', 'name', 'city', 'status'])
            ->map(fn($c) => [
                'id'     => $c->id,
                'name'   => $c->name,
                'city'   => $c->city,
                'status' => $c->status,
            ]);

        $types = collect(self::COMPANY_TYPES)->map(fn($label, $slug) => [
            'slug'  => $slug,
            'label' => $label,
        ])->values();

        return Inertia::render('admin/company-init', [
            'companies'    => $companies,
            'companyTypes' => $types,
        ]);
    }

    public function run(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        $validated = $request->validate([
            'company_id' => 'required|uuid|exists:companies,id',
            'type'       => 'required|string|in:' . implode(',', array_keys(self::COMPANY_TYPES)),
            'force'      => 'boolean',
        ]);

        $company = Company::find($validated['company_id']);

        $args = [
            'company_id' => $validated['company_id'],
            '--type'     => $validated['type'],
        ];

        if (!empty($validated['force'])) {
            $args['--force'] = true;
        }

        try {
            Artisan::call('company:init', $args);
            $output = Artisan::output();

            // Strip ANSI color codes for clean display
            $output = preg_replace('/\x1B\[[0-9;]*[mGKHF]/u', '', $output);
            $output = trim($output) ?: 'Befehl erfolgreich ausgeführt.';

            return back()->with([
                'init_success' => true,
                'init_output'  => $output,
                'init_company' => $company->name,
                'init_type'    => self::COMPANY_TYPES[$validated['type']],
                'init_force'   => !empty($validated['force']),
            ]);
        } catch (\Exception $e) {
            return back()->withErrors([
                'init_error' => 'Fehler beim Ausführen: ' . $e->getMessage(),
            ]);
        }
    }
}
