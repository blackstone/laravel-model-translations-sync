<?php

namespace BlackstonePro\ModelTranslationsSync\Tests\Fixtures\Support;

use Illuminate\Console\Command;

class TestFailCommand extends Command
{
    protected $signature = 'tests:fail';

    protected $description = 'Test command that always fails.';

    public function handle(): int
    {
        $this->line('tests:fail executed');

        return self::FAILURE;
    }
}
