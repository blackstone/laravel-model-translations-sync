<?php

namespace BlackstonePro\ModelTranslationsSync\Tests\Fixtures\Support;

use Illuminate\Support\ServiceProvider;

class TestConsoleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TestFailCommand::class,
                TestSuccessCommand::class,
            ]);
        }
    }
}
