<?php

namespace Database\Seeders;

use App\Models\CommunicationTemplate;
use App\Models\User;
use App\Services\CommunicationTemplateService;
use Illuminate\Database\Seeder;

class CommunicationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates default communication templates for all existing business users.
     */
    public function run(): void
    {
        $service = app(CommunicationTemplateService::class);

        // Get all business users
        $businesses = User::where('user_type', 'business')
            ->where('status', 'active')
            ->get();

        $this->command->info("Creating default templates for {$businesses->count()} businesses...");

        $templatesCreated = 0;

        foreach ($businesses as $business) {
            try {
                // Check if business already has templates
                $existingCount = CommunicationTemplate::forBusiness($business->id)->count();

                if ($existingCount === 0) {
                    // Create default templates
                    $service->ensureDefaultTemplates($business);
                    $templatesCreated += 5; // 5 default templates
                    $this->command->info("  - Created templates for: {$business->name}");
                } else {
                    $this->command->info("  - Skipped (already has templates): {$business->name}");
                }
            } catch (\Exception $e) {
                $this->command->error("  - Failed for {$business->name}: {$e->getMessage()}");
            }
        }

        $this->command->info("Total templates created: {$templatesCreated}");
    }
}
