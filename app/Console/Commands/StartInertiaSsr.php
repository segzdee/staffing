<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Inertia\Ssr\BundleDetector;
use Inertia\Ssr\SsrException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;

/**
 * Custom Inertia SSR start command that exits successfully when SSR is disabled.
 *
 * This prevents Laravel Cloud from treating disabled SSR as a failure.
 * Overrides the vendor command to handle disabled SSR gracefully.
 */
#[AsCommand(name: 'inertia:start-ssr')]
class StartInertiaSsr extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inertia:start-ssr {--runtime=node : The runtime to use (`node` or `bun`)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the Inertia SSR server (exits successfully when disabled)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if SSR is enabled before attempting to start
        if (! config('inertia.ssr.enabled', false)) {
            $this->info('Inertia SSR is disabled. Skipping SSR server startup.');

            return Command::SUCCESS; // Exit successfully instead of failing
        }

        // If SSR is enabled, proceed with normal startup
        $bundle = (new BundleDetector)->detect();
        $configuredBundle = config('inertia.ssr.bundle');

        if ($bundle === null) {
            $this->error(
                $configuredBundle
                    ? 'Inertia SSR bundle not found at the configured path: "'.$configuredBundle.'"'
                    : 'Inertia SSR bundle not found. Set the correct Inertia SSR bundle path in your `inertia.ssr.bundle` config.'
            );

            return Command::FAILURE;
        } elseif ($configuredBundle && $bundle !== $configuredBundle) {
            $this->warn('Inertia SSR bundle not found at the configured path: "'.$configuredBundle.'"');
            $this->warn('Using a default bundle instead: "'.$bundle.'"');
        }

        $runtime = $this->option('runtime');

        if (! in_array($runtime, ['node', 'bun'])) {
            $this->error('Unsupported runtime: "'.$runtime.'". Supported runtimes are `node` and `bun`.');

            return Command::INVALID;
        }

        $this->callSilently('inertia:stop-ssr');

        $process = new Process([$runtime, $bundle]);
        $process->setTimeout(null);
        $process->start();

        if (extension_loaded('pcntl')) {
            $stop = function () use ($process) {
                $process->stop();
            };
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, $stop);
            pcntl_signal(SIGQUIT, $stop);
            pcntl_signal(SIGTERM, $stop);
        }

        foreach ($process as $type => $data) {
            if ($process::OUT === $type) {
                $this->info(trim($data));
            } else {
                $this->error(trim($data));
                report(new SsrException($data));
            }
        }

        return Command::SUCCESS;
    }
}
