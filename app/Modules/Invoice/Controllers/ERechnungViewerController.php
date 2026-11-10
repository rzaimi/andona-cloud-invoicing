<?php

namespace App\Modules\Invoice\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ERechnungReaderService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use InvalidArgumentException;

/**
 * Read-only viewer for received e-invoices (XRechnung UBL/CII, ZUGFeRD/
 * Factur-X PDFs). Files are parsed in memory and never stored.
 */
class ERechnungViewerController extends Controller
{
    public function show()
    {
        return Inertia::render('invoices/erechnung-viewer', [
            'parsed' => null,
            'parse_error' => null,
            'filename' => null,
        ]);
    }

    public function parse(Request $request, ERechnungReaderService $reader)
    {
        $request->validate([
            'file' => 'required|file|max:20480|mimes:xml,pdf,txt',
        ], [
            'file.required' => 'Bitte wählen Sie eine Datei aus.',
            'file.max' => 'Die Datei darf höchstens 20 MB groß sein.',
            'file.mimes' => 'Unterstützt werden XML-Dateien (XRechnung) und PDF-Dateien (ZUGFeRD/Factur-X).',
        ]);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();

        try {
            $parsed = $reader->parse($file->get(), $filename);
            $error = null;
        } catch (InvalidArgumentException $e) {
            $parsed = null;
            $error = $e->getMessage();
        } catch (\Throwable $e) {
            report($e);
            $parsed = null;
            $error = 'Die Datei konnte nicht gelesen werden. Bitte prüfen Sie, ob es sich um eine gültige E-Rechnung handelt.';
        }

        return Inertia::render('invoices/erechnung-viewer', [
            'parsed' => $parsed,
            'parse_error' => $error,
            'filename' => $filename,
        ]);
    }
}
