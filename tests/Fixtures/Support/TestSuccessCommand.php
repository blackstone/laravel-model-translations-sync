<?php

namespace BlackstonePro\ModelTranslationsSync\Tests\Fixtures\Support;

use Illuminate\Console\Command;

class TestSuccessCommand extends Command
{
    protected $signature = 'tests:success';

    protected $description = 'Test command that succeeds.';

    public function handle(): int
    {
        $this->line('tests:success executed');

        return self::SUCCESS;
    }
}
