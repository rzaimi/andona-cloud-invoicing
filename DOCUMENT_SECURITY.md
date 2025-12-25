# Document Security Implementation

## Overview
Documents (including invoices) stored in `storage/app/public/documents` are now secured and moved to private storage that requires authentication.

## Changes Made

### 1. Storage Configuration
- **Created new 'documents' disk** in `config/filesystems.php`
  - Location: `storage/app/documents` (private, not publicly accessible)
  - `serve => false` - ensures files cannot be accessed directly via URL

### 2. Document Controller
- **Changed storage from 'public' to 'documents' disk**
  - New documents are stored in `storage/app/documents/{companyId}/`
  - Files are no longer publicly accessible via `/storage/...` URLs

### 3. Download Route
- **Route is protected** with `auth` middleware (already in place)
- **Company ownership verification** - users can only download documents from their company
- **File streaming** - files are served through Laravel's authenticated route, not direct file access

### 4. Document Model
- **Updated `getUrlAttribute()`** - always returns authenticated route URL, never direct file path
- **Updated deletion** - uses 'documents' disk instead of default storage

### 5. Migration Command
- **Created `documents:move-to-private` command** to migrate existing documents
  - Run: `php artisan documents:move-to-private`
  - Moves files from `storage/app/public/documents` to `storage/app/documents`
  - Updates file paths in database

## Security Features

✅ **Authentication Required** - All document downloads require user authentication
✅ **Company Isolation** - Users can only access documents from their own company
✅ **No Direct File Access** - Files cannot be accessed via public URLs
✅ **Route Protection** - Download route is protected by `auth` middleware
✅ **Authorization Check** - Company ownership is verified before download

## File Locations

- **Old (Public)**: `storage/app/public/documents/{companyId}/` ❌ Publicly accessible
- **New (Private)**: `storage/app/documents/{companyId}/{year}/{month}/` ✅ Requires authentication

### Organized Structure

Documents are now organized by:
- `company_id` - Company identifier
- `year` - Year (YYYY format, e.g., 2025)
- `month` - Month (MM format, e.g., 12)

Example: `storage/app/documents/019a91de-316f-73c1-b8a6-2d4e93038774/2025/12/invoice.pdf`

This organization makes it easier to:
- Manage documents by time period
- Archive old documents
- Improve filesystem performance
- Organize backups by year/month

## Migration

To migrate existing documents from public to private storage:

```bash
php artisan documents:move-to-private
```

This command will:
1. Find all documents in the database
2. Move files from public to private storage
3. Verify files were moved successfully
4. Delete files from public storage after successful migration

## Access Pattern

All document access now follows this secure pattern:

1. User clicks download link → `route('documents.download', $document->id)`
2. Request goes to `/documents/{document}/download` (protected route)
3. Middleware checks authentication
4. Controller verifies company ownership
5. File is streamed through Laravel response (not direct file access)

## Notes

- The `storage/app/documents` directory is **not** symlinked to `public/storage`
- Files in this directory are **not** accessible via HTTP without authentication
- All document URLs use the authenticated route: `route('documents.download', $id)`
- Frontend code already uses the route helper, so no frontend changes needed

