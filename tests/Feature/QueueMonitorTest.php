<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueueMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedRolesAndPermissions();

        $this->company = Company::create([
            'name' => 'Queue GmbH',
            'email' => 'queue@example.com',
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_view_queue_jobs(): void
    {
        $this->insertPendingJob();
        $this->insertFailedJob('11111111-1111-1111-1111-111111111111');

        $this->actingAs($this->makeSuperAdmin())
            ->get(route('system-health.queue'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/queue')
                ->where('pending_count', 1)
                ->where('failed_count', 1)
                ->has('pending', 1)
                ->where('pending.0.name', 'SendInvoiceEmail')
                ->has('failed', 1)
                ->where('failed.0.name', 'SendInvoiceEmail')
            );
    }

    public function test_company_admin_cannot_manage_the_queue(): void
    {
        $admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');
        $admin->givePermissionTo('manage_companies');

        $this->actingAs($admin)
            ->get(route('system-health.queue'))
            ->assertForbidden();
    }

    public function test_super_admin_can_retry_and_forget_a_failed_job(): void
    {
        $uuid = '22222222-2222-2222-2222-222222222222';
        $this->insertFailedJob($uuid);
        $superAdmin = $this->makeSuperAdmin();

        $this->actingAs($superAdmin)
            ->post(route('system-health.queue.retry', $uuid))
            ->assertRedirect();

        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $uuid]);
        $this->assertDatabaseCount('jobs', 1);

        $this->insertFailedJob($uuid);

        $this->actingAs($superAdmin)
            ->delete(route('system-health.queue.forget', $uuid))
            ->assertRedirect();

        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $uuid]);
    }

    private function makeSuperAdmin(): User
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);
        $user->assignRole('super_admin');

        return $user;
    }

    private function insertPendingJob(): void
    {
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\SendInvoiceEmail',
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'data' => ['commandName' => 'App\\Jobs\\SendInvoiceEmail'],
            ]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time(),
            'created_at' => time(),
        ]);
    }

    private function insertFailedJob(string $uuid): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\SendInvoiceEmail',
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'data' => ['commandName' => 'App\\Jobs\\SendInvoiceEmail'],
            ]),
            'exception' => "RuntimeException: SMTP timeout\nStack",
            'failed_at' => now(),
        ]);
    }
}
