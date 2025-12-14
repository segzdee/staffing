<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Carbon\Carbon;

class CreateDevAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:create-accounts
                            {--refresh : Force refresh existing dev accounts (extends expiration)}
                            {--show : Show current dev account status without creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or refresh the 5 development test accounts (Worker, Business, Agency, AI Agent, Admin) with 7-day expiration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Only allow in development environments
        if (!app()->environment('local', 'development', 'testing')) {
            $this->error('This command can only be run in local, development, or testing environments.');
            return 1;
        }

        $this->newLine();
        $this->info('========================================');
        $this->info('  OvertimeStaff Development Accounts');
        $this->info('========================================');
        $this->newLine();

        // Show option - display status only
        if ($this->option('show')) {
            return $this->showAccountStatus();
        }

        // Check for existing accounts
        $existingAccounts = User::where('is_dev_account', true)->get();

        if ($existingAccounts->isNotEmpty() && !$this->option('refresh')) {
            $this->warn('Found ' . $existingAccounts->count() . ' existing dev account(s).');
            $this->newLine();

            // Show status table
            $this->showAccountTable($existingAccounts);
            $this->newLine();

            if (!$this->confirm('Do you want to refresh these accounts (extends expiration by 7 days)?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Run the DevCredentialsSeeder
        $this->info('Creating/refreshing dev accounts...');
        $this->newLine();

        try {
            Artisan::call('db:seed', [
                '--class' => 'DevCredentialsSeeder',
                '--force' => true,
            ]);

            $output = Artisan::output();
            $this->line($output);

        } catch (\Exception $e) {
            $this->error('Failed to create dev accounts: ' . $e->getMessage());
            return 1;
        }

        // Display summary
        $this->newLine();
        $this->displayCredentialsSummary();

        return 0;
    }

    /**
     * Show current dev account status.
     */
    private function showAccountStatus(): int
    {
        $devAccounts = User::where('is_dev_account', true)->get();

        if ($devAccounts->isEmpty()) {
            $this->warn('No dev accounts found.');
            $this->info('Run "php artisan dev:create-accounts" to create them.');
            return 0;
        }

        $this->showAccountTable($devAccounts);
        return 0;
    }

    /**
     * Display account status as a table.
     */
    private function showAccountTable($accounts): void
    {
        $rows = [];
        foreach ($accounts as $account) {
            $expiresAt = $account->dev_expires_at;
            $status = 'Active';
            $expiresIn = '-';

            if ($expiresAt) {
                $expiresAt = $expiresAt instanceof Carbon ? $expiresAt : Carbon::parse($expiresAt);

                if ($expiresAt->isPast()) {
                    $status = '<fg=red>EXPIRED</>';
                    $expiresIn = $expiresAt->diffForHumans();
                } elseif ($expiresAt->diffInHours(now()) < 24) {
                    $status = '<fg=yellow>Expiring Soon</>';
                    $expiresIn = $expiresAt->diffForHumans();
                } else {
                    $status = '<fg=green>Active</>';
                    $expiresIn = $expiresAt->diffForHumans();
                }
            }

            $rows[] = [
                ucfirst($account->user_type),
                $account->email,
                $status,
                $expiresIn,
            ];
        }

        $this->table(
            ['Type', 'Email', 'Status', 'Expires'],
            $rows
        );
    }

    /**
     * Display credentials summary.
     */
    private function displayCredentialsSummary(): void
    {
        $this->info('========================================');
        $this->info('  DEV CREDENTIALS SUMMARY');
        $this->info('========================================');
        $this->newLine();

        $credentials = [
            ['Worker', 'dev.worker@overtimestaff.io', 'Dev007!', '/worker/dashboard'],
            ['Business', 'dev.business@overtimestaff.io', 'Dev007!', '/business/dashboard'],
            ['Agency', 'dev.agency@overtimestaff.io', 'Dev007!', '/agency/dashboard'],
            ['AI Agent', 'dev.agent@overtimestaff.io', 'Dev007!', 'API Access'],
            ['Admin', 'dev.admin@overtimestaff.io', 'Dev007!', '/panel/admin'],
        ];

        $this->table(
            ['User Type', 'Email', 'Password', 'Dashboard'],
            $credentials
        );

        $this->newLine();
        $this->info('Quick Access URLs:');
        $this->line('  Credentials Page: http://127.0.0.1:8000/dev/credentials');
        $this->line('  Worker Login:     http://127.0.0.1:8000/dev/login/worker');
        $this->line('  Business Login:   http://127.0.0.1:8000/dev/login/business');
        $this->line('  Agency Login:     http://127.0.0.1:8000/dev/login/agency');
        $this->line('  AI Agent Login:   http://127.0.0.1:8000/dev/login/agent');
        $this->line('  Admin Login:      http://127.0.0.1:8000/dev/login/admin');
        $this->newLine();

        $expiresAt = Carbon::now()->addDays(7);
        $this->info("All accounts expire: {$expiresAt->format('Y-m-d H:i:s')} ({$expiresAt->diffForHumans()})");
        $this->newLine();
    }
}
