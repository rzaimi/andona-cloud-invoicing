<?php

namespace App\Modules\Employee\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Document\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EmployeePortalController extends Controller
{
    /**
     * Employee self-service: list own documents.
     */
    public function documents(Request $request)
    {
        $user = Auth::user();

        $query = Document::query()
            ->where('linkable_type', \App\Modules\User\Models\User::class)
            ->where('linkable_id', $user->id)
            ->where('visible_to_employee', true)
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('original_filename', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category') && $request->get('category') !== 'all') {
            $query->where('category', $request->get('category'));
        }

        $documents = $query->paginate(20)->withQueryString();

        return Inertia::render('employee/documents', [
            'documents' => $documents,
            'filters'   => $request->only(['search', 'category']),
            'employee'  => $user->only('id', 'name', 'email', 'staff_number', 'department', 'job_title'),
        ]);
    }

    /**
     * Employee self-service: download own document.
     */
    public function download(Document $document)
    {
        $user = Auth::user();

        // Strict ownership + visibility check
        if (
            $document->linkable_type !== \App\Modules\User\Models\User::class
            || $document->linkable_id !== $user->id
            || !$document->visible_to_employee
        ) {
            abort(403);
        }

        $disk = Storage::disk('private')->exists($document->file_path) ? 'private' : 'documents';

        if (!Storage::disk($disk)->exists($document->file_path)) {
            abort(404, 'Datei nicht gefunden.');
        }

        return Storage::disk($disk)->download($document->file_path, $document->original_filename);
    }
}
