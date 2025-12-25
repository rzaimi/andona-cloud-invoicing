<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Document\Models\Document;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function __construct(ContextService $contextService)
    {
        parent::__construct($contextService);
        $this->middleware('auth');
    }

    /**
     * Display a listing of documents
     */
    public function index(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $query = Document::forCompany($companyId)
            ->with(['uploadedBy', 'linkable']);

        // Category filter
        if ($request->filled('category') && $request->get('category') !== 'all') {
            $query->where('category', $request->get('category'));
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('original_filename', 'like', "%{$search}%");
            });
            
            // Also search in tags
            $query->orWhereJsonContains('tags', $search);
        }

        // Link type filter
        if ($request->filled('link_type') && $request->get('link_type') !== 'all') {
            $query->where('link_type', $request->get('link_type'));
        }

        // Linked entity filter
        if ($request->filled('linkable_type') && $request->filled('linkable_id')) {
            $query->where('linkable_type', $request->get('linkable_type'))
                  ->where('linkable_id', $request->get('linkable_id'));
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $documents = $query->paginate(20)->withQueryString();

        // Get linked entities for filters
        $customers = Customer::forCompany($companyId)->select('id', 'name', 'number')->get();
        $invoices = Invoice::forCompany($companyId)->select('id', 'number')->latest()->limit(100)->get();

        return Inertia::render('settings/documents', [
            'documents' => $documents,
            'customers' => $customers,
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'category', 'link_type', 'linkable_type', 'linkable_id', 'sort_by', 'sort_order']),
        ]);
    }

    /**
     * Store newly uploaded document(s) - supports single or bulk upload
     */
    public function store(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        $user = Auth::user();

        $validated = $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'category' => 'required|in:employee,customer,invoice,company,financial,custom',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|string', // Comma-separated tags
            'linkable_type' => 'nullable|string|in:App\Modules\Invoice\Models\Invoice,App\Modules\Customer\Models\Customer',
            'linkable_id' => 'nullable|uuid|required_with:linkable_type',
            'link_type' => 'nullable|string|in:attachment,contract,receipt,certificate,other',
        ]);

        $files = $request->file('files');
        
        // Parse tags
        $tags = [];
        if (!empty($validated['tags'])) {
            $tags = array_map('trim', explode(',', $validated['tags']));
            $tags = array_filter($tags); // Remove empty tags
        }

        // Get linked entity if provided
        $linkable = null;
        if (!empty($validated['linkable_type']) && !empty($validated['linkable_id'])) {
            $linkable = $validated['linkable_type']::find($validated['linkable_id']);
            if (!$linkable || $linkable->company_id !== $companyId) {
                return back()->withErrors(['linkable_id' => 'Ungültige Verknüpfung.']);
            }
        }

        $uploadedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            // Organize files by company_id/year/month/
            $year = now()->year;
            $month = now()->format('m');
            $storagePath = "{$companyId}/{$year}/{$month}";
            
            foreach ($files as $file) {
                try {
                    // Store file in private documents storage with organized structure
                    // Structure: company_id/year/month/filename
                    $filePath = $file->store($storagePath, 'documents');
                    
                    // Use filename without extension as name, or generate from original filename
                    $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    
                    Document::create([
                        'company_id' => $companyId,
                        'name' => $name,
                        'original_filename' => $file->getClientOriginalName(),
                        'file_path' => $filePath,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'category' => $validated['category'],
                        'description' => $validated['description'] ?? null,
                        'tags' => $tags,
                        'uploaded_by' => $user->id,
                        'linkable_type' => $linkable ? get_class($linkable) : null,
                        'linkable_id' => $linkable ? $linkable->id : null,
                        'link_type' => $validated['link_type'] ?? null,
                    ]);
                    
                    $uploadedCount++;
                } catch (\Exception $e) {
                    $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
                }
            }
            
            DB::commit();
            
            $message = $uploadedCount . ' Dokument(e) erfolgreich hochgeladen.';
            if (count($errors) > 0) {
                $message .= ' ' . count($errors) . ' Fehler aufgetreten.';
            }
            
            return redirect()->route('documents.index')
                ->with('success', $message)
                ->with('upload_errors', $errors);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['files' => 'Fehler beim Hochladen: ' . $e->getMessage()]);
        }
    }

    /**
     * Download a document
     */
    public function download(Document $document)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        // Verify document belongs to company
        if ($document->company_id !== $companyId) {
            abort(403);
        }

        // Use documents disk (private, not publicly accessible)
        if (!Storage::disk('documents')->exists($document->file_path)) {
            abort(404, 'Datei nicht gefunden.');
        }

        // Stream the file through Laravel (ensures authentication check)
        return Storage::disk('documents')->download($document->file_path, $document->original_filename);
    }

    /**
     * Update document metadata
     */
    public function update(Request $request, Document $document)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        // Verify document belongs to company
        if ($document->company_id !== $companyId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:employee,customer,invoice,company,financial,custom',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|string',
            'linkable_type' => 'nullable|string|in:App\Modules\Invoice\Models\Invoice,App\Modules\Customer\Models\Customer',
            'linkable_id' => 'nullable|uuid|required_with:linkable_type',
            'link_type' => 'nullable|string|in:attachment,contract,receipt,certificate,other',
        ]);

        // Parse tags
        $tags = [];
        if (!empty($validated['tags'])) {
            $tags = array_map('trim', explode(',', $validated['tags']));
            $tags = array_filter($tags);
        }

        // Get linked entity if provided
        $linkable = null;
        if (!empty($validated['linkable_type']) && !empty($validated['linkable_id'])) {
            $linkable = $validated['linkable_type']::find($validated['linkable_id']);
            if (!$linkable || $linkable->company_id !== $companyId) {
                return back()->withErrors(['linkable_id' => 'Ungültige Verknüpfung.']);
            }
        }

        $document->update([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'tags' => $tags,
            'linkable_type' => $linkable ? get_class($linkable) : null,
            'linkable_id' => $linkable ? $linkable->id : null,
            'link_type' => $validated['link_type'] ?? null,
        ]);

        return redirect()->route('documents.index')
            ->with('success', 'Dokument erfolgreich aktualisiert.');
    }

    /**
     * Delete a document
     */
    public function destroy(Document $document)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        // Verify document belongs to company
        if ($document->company_id !== $companyId) {
            abort(403);
        }

        // File will be deleted automatically via model boot method
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Dokument erfolgreich gelöscht.');
    }
}
