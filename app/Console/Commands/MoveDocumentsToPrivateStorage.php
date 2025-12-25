<?php

namespace App\Console\Commands;

use App\Modules\Document\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MoveDocumentsToPrivateStorage extends Command
{
    protected $signature = 'documents:move-to-private';
    protected $description = 'Move documents from public storage to private storage';

    public function handle()
    {
        $this->info('Moving documents from public to private storage...');

        $documents = Document::all();
        $moved = 0;
        $errors = 0;

        foreach ($documents as $document) {
            try {
                // Determine new organized path: company_id/year/month/filename
                $createdAt = $document->created_at ?? now();
                $year = $createdAt->format('Y');
                $month = $createdAt->format('m');
                $filename = basename($document->file_path);
                $newPath = "{$document->company_id}/{$year}/{$month}/{$filename}";
                
                // Check if file exists in public storage
                if (Storage::disk('public')->exists($document->file_path)) {
                    // Read file from public storage
                    $fileContent = Storage::disk('public')->get($document->file_path);
                    
                    // Write to documents (private) storage with organized structure
                    Storage::disk('documents')->put($newPath, $fileContent);
                    
                    // Verify the file was moved
                    if (Storage::disk('documents')->exists($newPath)) {
                        // Update document record with new path
                        $document->file_path = $newPath;
                        $document->save();
                        
                        // Delete from public storage
                        Storage::disk('public')->delete($document->file_path);
                        $moved++;
                        $this->line("Moved: {$document->file_path} -> {$newPath}");
                    } else {
                        $errors++;
                        $this->error("Failed to verify move: {$document->file_path}");
                    }
                } else {
                    // File might already be in private storage or missing
                    if (Storage::disk('documents')->exists($document->file_path)) {
                        // Check if it needs to be reorganized
                        if (!str_contains($document->file_path, '/') || 
                            !preg_match('/\d{4}\/\d{2}\//', $document->file_path)) {
                            // Reorganize existing file
                            $fileContent = Storage::disk('documents')->get($document->file_path);
                            Storage::disk('documents')->put($newPath, $fileContent);
                            Storage::disk('documents')->delete($document->file_path);
                            $document->file_path = $newPath;
                            $document->save();
                            $moved++;
                            $this->line("Reorganized: {$document->file_path} -> {$newPath}");
                        } else {
                            $this->line("Already organized: {$document->file_path}");
                        }
                    } else {
                        $errors++;
                        $this->warn("File not found in public or private: {$document->file_path}");
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("Error moving {$document->file_path}: {$e->getMessage()}");
            }
        }

        $this->info("Migration complete. Moved: {$moved}, Errors: {$errors}");
        
        return Command::SUCCESS;
    }
}

