<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class SystemHealthController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Only super admins can access
        if (!$user || !$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        $health = [
            'system' => $this->getSystemInfo(),
            'database' => $this->getDatabaseInfo(),
            'storage' => $this->getStorageInfo(),
            'cache' => $this->getCacheInfo(),
            'performance' => $this->getPerformanceMetrics(),
            'laravel' => $this->getLaravelAbout(),
            'processes' => $this->getBackgroundProcesses(),
            'logs' => $this->getLogsInfo(),
        ];

        return Inertia::render('admin/system-health', [
            'health' => $health,
        ]);
    }

    private function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
            'timezone' => config('app.timezone'),
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];
    }

    private function getDatabaseInfo(): array
    {
        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();
            $version = $connection->select('SELECT version() as version')[0]->version ?? 'Unknown';
            
            // Get database size (MySQL/MariaDB)
            $size = 'Unknown';
            if (in_array($driver, ['mysql', 'mariadb'])) {
                $result = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
                $size = $result[0]->size_mb ?? 'Unknown';
            }

            return [
                'driver' => $driver,
                'version' => $version,
                'size_mb' => $size,
                'connection' => 'Connected',
                'status' => 'healthy',
            ];
        } catch (\Exception $e) {
            return [
                'driver' => 'Unknown',
                'version' => 'Unknown',
                'size_mb' => 'Unknown',
                'connection' => 'Error',
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getStorageInfo(): array
    {
        try {
            $disk = Storage::disk('local');
            $publicDisk = Storage::disk('public');
            
            $localPath = storage_path('app');
            $publicPath = storage_path('app/public');
            
            $localFree = disk_free_space($localPath);
            $localTotal = disk_total_space($localPath);
            $localUsed = $localTotal - $localFree;
            
            $publicFree = disk_free_space($publicPath);
            $publicTotal = disk_total_space($publicPath);
            $publicUsed = $publicTotal - $publicFree;

            return [
                'local' => [
                    'total_gb' => round($localTotal / 1024 / 1024 / 1024, 2),
                    'used_gb' => round($localUsed / 1024 / 1024 / 1024, 2),
                    'free_gb' => round($localFree / 1024 / 1024 / 1024, 2),
                    'usage_percent' => round(($localUsed / $localTotal) * 100, 2),
                ],
                'public' => [
                    'total_gb' => round($publicTotal / 1024 / 1024 / 1024, 2),
                    'used_gb' => round($publicUsed / 1024 / 1024 / 1024, 2),
                    'free_gb' => round($publicFree / 1024 / 1024 / 1024, 2),
                    'usage_percent' => round(($publicUsed / $publicTotal) * 100, 2),
                ],
                'status' => ($localUsed / $localTotal) * 100 > 90 ? 'warning' : 'healthy',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getCacheInfo(): array
    {
        try {
            $driver = config('cache.default');
            $testKey = 'health_check_' . time();
            $testValue = 'test';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            return [
                'driver' => $driver,
                'status' => $retrieved === $testValue ? 'healthy' : 'error',
                'working' => $retrieved === $testValue,
            ];
        } catch (\Exception $e) {
            return [
                'driver' => config('cache.default'),
                'status' => 'error',
                'working' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getLaravelAbout(): array
    {
        try {
            $about = [];
            
            // Environment
            $about['environment'] = [
                'application_name' => config('app.name'),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'composer_version' => $this->getComposerVersion(),
                'environment' => config('app.env'),
                'debug_mode' => config('app.debug') ? 'ENABLED' : 'DISABLED',
                'url' => config('app.url'),
                'maintenance_mode' => app()->isDownForMaintenance() ? 'ON' : 'OFF',
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
            ];

            // Cache status
            $about['cache'] = [
                'config' => $this->isCacheCached('config') ? 'CACHED' : 'NOT CACHED',
                'events' => $this->isCacheCached('events') ? 'CACHED' : 'NOT CACHED',
                'routes' => $this->isCacheCached('routes') ? 'CACHED' : 'NOT CACHED',
                'views' => $this->isCacheCached('views') ? 'CACHED' : 'NOT CACHED',
            ];

            // Drivers
            $about['drivers'] = [
                'broadcasting' => config('broadcasting.default', 'log'),
                'cache' => config('cache.default', 'file'),
                'database' => config('database.default', 'mysql'),
                'logs' => config('logging.default', 'stack'),
                'mail' => config('mail.default', 'smtp'),
                'queue' => config('queue.default', 'sync'),
                'session' => config('session.driver', 'file'),
            ];

            // Storage
            $about['storage'] = [
                'public_storage_linked' => File::exists(public_path('storage')) && is_link(public_path('storage')),
            ];

            // Spatie Permissions (if installed)
            if (class_exists(\Spatie\Permission\PermissionServiceProvider::class)) {
                $about['spatie_permissions'] = [
                    'version' => \Composer\InstalledVersions::getVersion('spatie/laravel-permission') ?? 'Unknown',
                    'features' => 'Default',
                ];
            }

            return $about;
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getComposerVersion(): string
    {
        try {
            $composerPath = base_path('composer.json');
            if (File::exists($composerPath)) {
                $composer = json_decode(File::get($composerPath), true);
                return $composer['require']['composer/composer'] ?? 'Unknown';
            }
            return 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function isCacheCached(string $type): bool
    {
        try {
            $cachePath = base_path('bootstrap/cache');
            switch ($type) {
                case 'config':
                    return File::exists($cachePath . '/config.php');
                case 'events':
                    return File::exists($cachePath . '/events.php');
                case 'routes':
                    return File::exists($cachePath . '/routes-v7.php') || File::exists($cachePath . '/routes.php');
                case 'views':
                    return File::exists(storage_path('framework/views'));
                default:
                    return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getPerformanceMetrics(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test database query performance
            $dbStart = microtime(true);
            DB::table('users')->count();
            $dbTime = (microtime(true) - $dbStart) * 1000; // Convert to milliseconds
            
            // Test cache performance
            $cacheStart = microtime(true);
            $testKey = 'perf_test_' . time();
            Cache::put($testKey, 'test', 10);
            Cache::get($testKey);
            Cache::forget($testKey);
            $cacheTime = (microtime(true) - $cacheStart) * 1000;
            
            $totalTime = (microtime(true) - $startTime) * 1000;

            return [
                'database_query_ms' => round($dbTime, 2),
                'cache_operation_ms' => round($cacheTime, 2),
                'total_page_load_ms' => round($totalTime, 2),
                'status' => $dbTime < 100 && $cacheTime < 50 ? 'healthy' : ($dbTime < 500 ? 'warning' : 'slow'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function runCommand(Request $request)
    {
        $user = $request->user();

        // Only super admins can access
        if (!$user || !$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        $validated = $request->validate([
            'command' => 'required|string|in:cache:clear,config:clear,route:clear,view:clear,optimize:clear,optimize,queue:clear',
        ]);

        $command = $validated['command'];

        try {
            Artisan::call($command);
            $output = Artisan::output();

            return back()->with('success', "Command '{$command}' executed successfully");
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Error executing command: ' . $e->getMessage()]);
        }
    }

    private function getBackgroundProcesses(): array
    {
        $processes = [];

        try {
            // Check for queue workers
            $queueProcesses = $this->checkQueueWorkers();
            $processes = array_merge($processes, $queueProcesses);

            // Check for scheduled tasks
            $scheduledTasks = $this->checkScheduledTasks();
            $processes = array_merge($processes, $scheduledTasks);

            // Check for other Laravel processes
            $otherProcesses = $this->checkOtherProcesses();
            $processes = array_merge($processes, $otherProcesses);
        } catch (\Exception $e) {
            return [
                [
                    'name' => 'Error',
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ]
            ];
        }

        return $processes;
    }

    private function checkQueueWorkers(): array
    {
        $processes = [];
        
        // Check if queue is configured
        $queueDriver = config('queue.default');
        if ($queueDriver === 'sync') {
            return [
                [
                    'name' => 'Queue Worker',
                    'type' => 'queue',
                    'status' => 'not_configured',
                    'message' => 'Queue is set to sync mode (no background workers needed)',
                ]
            ];
        }

        // Try to detect running queue workers
        // This is a simplified check - in production you might use process managers like Supervisor
        $processes[] = [
            'name' => 'Queue Worker',
            'type' => 'queue',
            'status' => 'unknown',
            'message' => 'Queue driver: ' . $queueDriver . '. Check your process manager (Supervisor, systemd, etc.)',
        ];

        return $processes;
    }

    private function checkScheduledTasks(): array
    {
        $processes = [];
        
        // Check if scheduler is configured
        $processes[] = [
            'name' => 'Task Scheduler',
            'type' => 'scheduler',
            'status' => 'configured',
            'message' => 'Ensure cron is running: * * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1',
        ];

        return $processes;
    }

    private function checkOtherProcesses(): array
    {
        $processes = [];

        // Check for Horizon (if installed)
        if (class_exists(\Laravel\Horizon\Horizon::class)) {
            $processes[] = [
                'name' => 'Laravel Horizon',
                'type' => 'horizon',
                'status' => 'installed',
                'message' => 'Horizon is installed. Check if it\'s running.',
            ];
        }

        return $processes;
    }

    private function getLogsInfo(): array
    {
        try {
            $logsPath = storage_path('logs');
            $logFiles = [];
            
            if (File::exists($logsPath)) {
                $files = File::files($logsPath);
                
                foreach ($files as $file) {
                    $logFiles[] = [
                        'name' => $file->getFilename(),
                        'path' => $file->getPathname(),
                        'size' => $file->getSize(),
                        'size_formatted' => $this->formatBytes($file->getSize()),
                        'modified' => $file->getMTime(),
                        'modified_formatted' => date('Y-m-d H:i:s', $file->getMTime()),
                    ];
                }
                
                // Sort by modified time (newest first)
                usort($logFiles, function($a, $b) {
                    return $b['modified'] - $a['modified'];
                });
            }

            return [
                'log_files' => $logFiles,
                'total_files' => count($logFiles),
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'log_files' => [],
                'total_files' => 0,
            ];
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function getLogs(Request $request)
    {
        $user = $request->user();

        // Only super admins can access
        if (!$user || !$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        $validated = $request->validate([
            'file' => 'required|string',
            'type' => 'nullable|string|in:all,error,warning,info,debug',
            'lines' => 'nullable|integer|min:1|max:1000',
        ]);

        $logFile = storage_path('logs/' . basename($validated['file']));
        $type = $validated['type'] ?? 'all';
        $lines = $validated['lines'] ?? 100;

        if (!File::exists($logFile)) {
            return response()->json([
                'error' => 'Log file not found',
            ], 404);
        }

        try {
            $content = File::get($logFile);
            $logEntries = $this->parseLogFile($content, $type, $lines);

            return response()->json([
                'file' => basename($validated['file']),
                'entries' => $logEntries,
                'total_entries' => count($logEntries),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error reading log file: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function parseLogFile(string $content, string $type, int $maxLines): array
    {
        $entries = [];
        $lines = explode("\n", $content);
        
        // Reverse to get newest first, then limit
        $lines = array_reverse($lines);
        $lines = array_slice($lines, 0, $maxLines);
        
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $entry = $this->parseLogLine($line);
            
            if ($type === 'all' || $entry['type'] === $type) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    private function parseLogLine(string $line): array
    {
        // Laravel log format: [YYYY-MM-DD HH:MM:SS] local.ERROR: message
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(\w+)\.(\w+):\s+(.*)/';
        
        if (preg_match($pattern, $line, $matches)) {
            $timestamp = $matches[1];
            $environment = $matches[2];
            $level = strtolower($matches[3]);
            $message = $matches[4];
            
            // Map Laravel log levels to our types
            $typeMap = [
                'emergency' => 'error',
                'alert' => 'error',
                'critical' => 'error',
                'error' => 'error',
                'warning' => 'warning',
                'notice' => 'info',
                'info' => 'info',
                'debug' => 'debug',
            ];
            
            $type = $typeMap[$level] ?? 'info';
            
            return [
                'timestamp' => $timestamp,
                'environment' => $environment,
                'level' => $level,
                'type' => $type,
                'message' => $message,
                'raw' => $line,
            ];
        }

        // If it doesn't match the pattern, treat as info
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => 'unknown',
            'level' => 'info',
            'type' => 'info',
            'message' => $line,
            'raw' => $line,
        ];
    }
}

