<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class CompanyContextController extends Controller
{
    public function switch(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        // Store selected company in session for super admin context switching
        Session::put('selected_company_id', $validated['company_id']);

        // Always redirect to dashboard with full page reload
        return Inertia::location(route('dashboard'));
    }

    public function getCurrent(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->hasPermissionTo('manage_companies')) {
            return back();
        }

        $selectedCompanyId = Session::get('selected_company_id', $user->company_id);

        return back();
    }
}
