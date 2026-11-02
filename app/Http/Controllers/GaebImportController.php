<?php

namespace App\Http\Controllers;

use App\Modules\Company\Models\Company;
use App\Services\GaebImportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class GaebImportController extends Controller
{
    public function positions(Request $request, GaebImportService $gaeb)
    {
        $company = Company::find($this->getEffectiveCompanyId());
        if (! $company || ! $company->hasAnyModule(['invoices', 'offers']) || ! $company->gaebImportEnabled()) {
            abort(403);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ], [
            'file.required' => 'Bitte eine GAEB-Datei auswählen.',
            'file.max' => 'Die Datei darf höchstens 5 MB groß sein.',
        ]);

        $file = $request->file('file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! preg_match('/^(x8[1-9]|d8[1-9]|xml|gaeb)$/', $extension)) {
            throw ValidationException::withMessages([
                'file' => 'Bitte eine GAEB-Datei (.X81, .X83, .X84 oder .xml) hochladen.',
            ]);
        }

        try {
            $parsed = $gaeb->parse((string) file_get_contents($file->getRealPath()));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'file' => $exception->getMessage(),
            ]);
        }

        return response()->json($parsed);
    }
}
