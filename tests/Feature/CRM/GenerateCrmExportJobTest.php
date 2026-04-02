<?php

namespace Tests\Feature\CRM;

use App\Models\User;
use App\Modules\CRM\Jobs\GenerateCrmExportJob;
use App\Modules\CRM\Models\CrmExportRequest;
use App\Modules\CRM\Services\CrmReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateCrmExportJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_export_file_and_updates_status(): void
    {
        Storage::fake('public');
        Notification::fake();

        $user = User::factory()->create();
        $exportRequest = CrmExportRequest::create([
            'user_id' => $user->id,
            'type' => 'conversion',
            'format' => 'csv',
            'status' => 'pending',
            'filters' => [
                'date_from' => now()->subDays(30)->toDateString(),
                'date_to' => now()->toDateString(),
            ],
        ]);

        $job = new GenerateCrmExportJob($exportRequest);

        // We need to provide the service as handle() is called by the worker
        $service = app(CrmReportExportService::class);
        $job->handle($service);

        $exportRequest->refresh();

        $this->assertEquals('completed', $exportRequest->status);
        $this->assertNotNull($exportRequest->file_path);
        $this->assertNotNull($exportRequest->completed_at);

        Storage::disk('public')->assertExists($exportRequest->file_path);
    }

    public function test_it_handles_failures_gracefully(): void
    {
        Storage::fake('public');
        Notification::fake();

        $user = User::factory()->create();
        $exportRequest = CrmExportRequest::create([
            'user_id' => $user->id,
            'type' => 'invalid_type',
            'format' => 'csv',
            'status' => 'pending',
            'filters' => [],
        ]);

        $job = new GenerateCrmExportJob($exportRequest);
        $service = app(CrmReportExportService::class);

        try {
            $job->handle($service);
        } catch (\Throwable $e) {
            // Expected
        }

        $exportRequest->refresh();

        $this->assertEquals('failed', $exportRequest->status);
        $this->assertNotNull($exportRequest->error_message);
    }
}
