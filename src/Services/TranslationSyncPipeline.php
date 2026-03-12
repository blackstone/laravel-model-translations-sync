<?php

namespace BlackstonePro\ModelTranslationsSync\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class TranslationSyncPipeline
{
    public function run(Command $command, bool $dryRun = false): int
    {
        $pipeline = config('model-translations.sync.pipeline', []);
        $stopOnError = (bool) config('model-translations.sync.stop_on_error', false);

        foreach ($pipeline as $index => $step) {
            $commandName = trim((string) data_get($step, 'command', ''));
            $enabled = (bool) data_get($step, 'enabled', true);

            if (! $enabled) {
                continue;
            }

            if ($commandName === '') {
                if ($this->handleFailure($command, "Invalid pipeline step at index [{$index}]: missing command.", $stopOnError)) {
                    return Command::FAILURE;
                }

                continue;
            }

            if ($commandName === $command->getName()) {
                if ($this->handleFailure($command, "Pipeline step [{$commandName}] cannot call itself.", $stopOnError)) {
                    return Command::FAILURE;
                }

                continue;
            }

            if (! $this->hasCommand($commandName)) {
                if ($this->handleFailure($command, "Pipeline step [{$commandName}] was not found.", $stopOnError)) {
                    return Command::FAILURE;
                }

                continue;
            }

            $command->line("Running {$commandName}");

            try {
                $exitCode = Artisan::call($commandName, $this->buildParameters($commandName, $dryRun));
                $this->writeArtisanOutput($command);
            } catch (Throwable $exception) {
                if ($this->handleFailure($command, "Pipeline step [{$commandName}] failed: {$exception->getMessage()}", $stopOnError)) {
                    return Command::FAILURE;
                }

                continue;
            }

            if ($exitCode !== Command::SUCCESS) {
                if ($this->handleFailure($command, "Pipeline step [{$commandName}] exited with code [{$exitCode}].", $stopOnError)) {
                    return Command::FAILURE;
                }
            }
        }

        return Command::SUCCESS;
    }

    protected function hasCommand(string $name): bool
    {
        return array_key_exists($name, Artisan::all());
    }

    protected function writeArtisanOutput(Command $command): void
    {
        $output = trim(Artisan::output());

        if ($output === '') {
            return;
        }

        $command->line($output);
    }

    /**
     * @return array<string, bool>
     */
    protected function buildParameters(string $commandName, bool $dryRun): array
    {
        if (! $dryRun) {
            return [];
        }

        $artisanCommand = Artisan::all()[$commandName] ?? null;

        if (! $artisanCommand || ! $artisanCommand->getDefinition()->hasOption('dry-run')) {
            return [];
        }

        return ['--dry-run' => true];
    }

    protected function handleFailure(Command $command, string $message, bool $stopOnError): bool
    {
        $command->warn($message);

        return $stopOnError;
    }
}
