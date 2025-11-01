<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RefactorToModules extends Command
{
    protected $signature = 'refactor:modules';
    protected $description = 'Move Controllers, Models, Policies into module folders';

    public function handle(): void
    {
        $structure = [
            'Company' => [
                'Controllers' => ['CompanyController.php'],
                'Models' => ['Company.php', 'CompanySetting.php']
            ],
            'Customer' => [
                'Controllers' => ['CustomerController.php'],
                'Models' => ['Customer.php'],
                'Policies' => ['CustomerPolicy.php'],
            ],
            'Invoice' => [
                'Controllers' => ['InvoiceController.php'],
                'Models' => ['Invoice.php', 'InvoiceItem.php', 'InvoiceLayout.php'],
                'Policies' => ['InvoicePolicy.php'],
            ],
            'Offer' => [
                'Controllers' => ['OfferController.php'],
                'Models' => ['Offer.php', 'OfferItem.php', 'OfferLayout.php'],
            ],
            'Product' => [
                'Controllers' => ['ProductController.php'],
                'Models' => ['Product.php'],
                'Policies' => ['ProductPolicy.php'],
            ],
            'User' => [
                'Controllers' => ['UserController.php'],
                'Models' => ['User.php'],
            ],
            'Settings' => [
                'Controllers' => ['SettingsController.php'],
            ],
            'Dashboard' => [
                'Controllers' => ['DashboardController.php'],
            ],
            'Calendar' => [
                'Controllers' => ['CalendarController.php'],
            ],
            'Help' => [
                'Controllers' => ['HelpController.php'],
            ],
        ];

        foreach ($structure as $module => $paths) {
            $basePath = app_path("Modules/{$module}");
            foreach (['Controllers', 'Models', 'Policies'] as $folder) {
                if (isset($paths[$folder])) {
                    $fullPath = "{$basePath}/{$folder}";
                    File::ensureDirectoryExists($fullPath);
                    foreach ($paths[$folder] as $file) {
                        $sourcePath = match ($folder) {
                            'Controllers' => app_path("Http/Controllers/Settings/{$file}"),
                            'Models' => app_path("Models/{$file}"),
                            'Policies' => app_path("Policies/{$file}"),
                        };

                        if (File::exists($sourcePath)) {
                            File::move($sourcePath, "{$fullPath}/{$file}");
                            $this->info("Moved {$file} to Modules/{$module}/{$folder}");
                        } else {
                            $this->warn("File not found: {$sourcePath}");
                        }
                    }
                }
            }
        }

        $this->info('✅ Refactoring complete!');
    }
}
