feat: Secure document storage and hide routes from HTML source

## Security Improvements

### Document Storage Security
- **Moved documents from public to private storage**
  - Created dedicated 'documents' disk in `config/filesystems.php`
  - Documents now stored in `storage/app/documents/` (not publicly accessible)
  - Files organized by `company_id/year/month/` structure for better management
  - All document access requires authentication via protected route

- **Route Protection**
  - Download route `/documents/{document}/download` protected with `auth` middleware
  - Company ownership verification before file access
  - Files streamed through Laravel response, not direct file access

- **Storage Organization**
  - New structure: `storage/app/documents/{companyId}/{year}/{month}/filename.ext`
  - Makes document management, archiving, and backups easier
  - Improves filesystem performance with smaller directories

### Route Privacy
- **Removed Ziggy routes from HTML source**
  - Removed `@routes` directive from `app.blade.php`
  - Routes now loaded dynamically via authenticated API endpoint `/api/routes`
  - Routes completely hidden from HTML source code
  - Created Ziggy plugin to initialize routes from API response

## Files Changed

### Security & Storage
- `config/filesystems.php` - Added 'documents' private disk
- `app/Modules/Document/Controllers/DocumentController.php` - Updated to use private storage with organized structure
- `app/Modules/Document/Models/Document.php` - Updated URL attribute and deletion to use private disk
- `app/Console/Commands/MoveDocumentsToPrivateStorage.php` - Command to migrate existing documents

### Route Privacy
- `resources/views/app.blade.php` - Removed `@routes` directive
- `resources/js/app.tsx` - Load routes dynamically from API
- `resources/js/plugins/ziggy.ts` - Plugin to initialize Ziggy from API
- `resources/js/types/global.d.ts` - Updated type definitions
- `app/Http/Middleware/HandleInertiaRequests.php` - Removed ziggy from shared props
- `routes/web.php` - Added `/api/routes` endpoint

### Documentation
- `DOCUMENT_SECURITY.md` - Security implementation documentation

## Migration

To migrate existing documents to the new secure, organized structure:

```bash
php artisan documents:move-to-private
```

This command will:
- Move files from `storage/app/public/documents/` to `storage/app/documents/`
- Reorganize files into `company_id/year/month/` structure
- Update database records with new file paths
- Preserve all document metadata

## Security Benefits

✅ **Documents**: No longer publicly accessible via `/storage/...` URLs
✅ **Routes**: Hidden from HTML source, loaded via authenticated API
✅ **Organization**: Better file structure for management and archiving
✅ **Authentication**: All document access requires login and company verification
✅ **Authorization**: Company ownership verified before file download

## Breaking Changes

- Documents stored in new location (`storage/app/documents/` instead of `storage/app/public/documents/`)
- Document URLs now use authenticated route instead of direct file access
- Routes loaded asynchronously (may cause brief delay on first page load)

## Notes

- Existing documents need migration using the provided command
- New documents automatically use the secure, organized structure
- Frontend code already uses route helpers, no changes needed
- All document downloads go through authenticated route

