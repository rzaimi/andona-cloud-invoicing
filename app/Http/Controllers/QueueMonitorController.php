<?php

namespace App\Http\Controllers;

use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class QueueMonitorController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeSuper($request);

        $pending = DB::table('jobs')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn ($job) => [
                'id' => $job->id,
                'queue' => $job->queue,
                'name' => $this->jobDisplayName($job->payload),
                'attempts' => $job->attempts,
                'reserved' => $job->reserved_at !== null,
                'available_at' => date('c', (int) $job->available_at),
                'created_at' => date('c', (int) $job->created_at),
            ]);

        $failed = DB::table('failed_jobs')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn ($job) => [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'queue' => $job->queue,
                'name' => $this->jobDisplayName($job->payload),
                'exception' => $this->firstExceptionLine($job->exception),
                'failed_at' => $job->failed_at,
            ]);

        return Inertia::render('admin/queue', [
            'connection' => config('queue.default'),
            'pending_count' => DB::table('jobs')->count(),
            'failed_count' => DB::table('failed_jobs')->count(),
            'pending' => $pending,
            'failed' => $failed,
        ]);
    }

    public function retry(Request $request, string $uuid)
    {
        $this->authorizeSuper($request);

        Artisan::call('queue:retry', ['id' => [$uuid]]);

        return back()->with('success', 'Auftrag wurde erneut in die Warteschlange gelegt.');
    }

    public function retryAll(Request $request)
    {
        $this->authorizeSuper($request);

        Artisan::call('queue:retry', ['id' => ['all']]);

        return back()->with('success', 'Alle fehlgeschlagenen Aufträge wurden erneut eingereiht.');
    }

    public function forget(Request $request, string $uuid)
    {
        $this->authorizeSuper($request);

        Artisan::call('queue:forget', ['id' => $uuid]);

        return back()->with('success', 'Fehlgeschlagener Auftrag wurde entfernt.');
    }

    public function flushFailed(Request $request)
    {
        $this->authorizeSuper($request);

        Artisan::call('queue:flush');

        return back()->with('success', 'Alle fehlgeschlagenen Aufträge wurden gelöscht.');
    }

    public function clearPending(Request $request)
    {
        $this->authorizeSuper($request);

        Artisan::call('queue:clear', [
            'connection' => config('queue.default'),
            '--force' => true,
        ]);

        return back()->with('success', 'Ausstehende Aufträge wurden gelöscht.');
    }

    private function authorizeSuper(Request $request): void
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isSuperAdmin()) {
            abort(403);
        }
    }

    private function jobDisplayName(?string $payload): string
    {
        $data = json_decode((string) $payload, true);

        $name = $data['displayName'] ?? $data['data']['commandName'] ?? 'Unbekannt';

        return class_basename((string) $name);
    }

    private function firstExceptionLine(?string $exception): string
    {
        $line = strtok((string) $exception, "\n") ?: 'Fehler';

        return Str::limit($line, 220);
    }
}
