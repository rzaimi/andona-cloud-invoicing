<?php

namespace App\Modules\Document\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'name',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'category',
        'description',
        'tags',
        'uploaded_by',
        'linkable_type',
        'linkable_id',
        'link_type',
    ];

    protected $casts = [
        'tags' => 'array',
        'file_size' => 'integer',
    ];

    // Document categories
    const CATEGORY_EMPLOYEE = 'employee';
    const CATEGORY_CUSTOMER = 'customer';
    const CATEGORY_INVOICE = 'invoice';
    const CATEGORY_COMPANY = 'company';
    const CATEGORY_FINANCIAL = 'financial';
    const CATEGORY_CUSTOM = 'custom';

    // Link types
    const LINK_TYPE_ATTACHMENT = 'attachment';
    const LINK_TYPE_CONTRACT = 'contract';
    const LINK_TYPE_RECEIPT = 'receipt';
    const LINK_TYPE_CERTIFICATE = 'certificate';
    const LINK_TYPE_OTHER = 'other';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by company
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Get the file URL for download (always goes through authenticated route)
     */
    public function getUrlAttribute(): string
    {
        // Always use the authenticated download route, never direct file access
        return route('documents.download', $this->id);
    }

    /**
     * Get human readable file size
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if file is a PDF
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Delete the file when document is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            // Delete from documents (private) storage
            if (Storage::disk('documents')->exists($document->file_path)) {
                Storage::disk('documents')->delete($document->file_path);
            }
        });
    }
}
