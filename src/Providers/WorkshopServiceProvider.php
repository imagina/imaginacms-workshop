<?php

namespace Imagina\Iworkshop\Providers;

use Illuminate\Support\ServiceProvider;
use Imagina\Iworkshop\Commands\MakeModuleCommand;
use Imagina\Iworkshop\Commands\MakeEntityCommand;

class WorkshopServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register command here
        $this->commands([
            MakeModuleCommand::class,
            MakeEntityCommand::class,
        ]);
    }
}
