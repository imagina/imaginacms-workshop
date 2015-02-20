<?php namespace Modules\Workshop\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Services\Composer;
use Modules\Workshop\Console\ScaffoldCommand;
use Modules\Workshop\Console\UpdateModuleCommand;
use Modules\Workshop\Scaffold\Generators\EntityGenerator;
use Modules\Workshop\Scaffold\Generators\FilesGenerator;
use Modules\Workshop\Scaffold\Generators\ValueObjectGenerator;
use Modules\Workshop\Scaffold\ModuleScaffold;

class WorkshopServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    /**
     * Register artisan commands
     */
    private function registerCommands()
    {
        $this->registerScaffoldCommand();
        $this->registerUpdateCommand();

        $this->commands([
            'command.asgard.scaffold',
            'command.asgard.update',
        ]);
    }

    /**
     * Register the scaffold command
     */
    private function registerScaffoldCommand()
    {
        $this->app->singleton('asgard.module.scaffold', function ($app) {
            return new ModuleScaffold(
                $app['files'],
                $app['config'],
                new EntityGenerator($app['files'], $app['config']),
                new ValueObjectGenerator($app['files'], $app['config']),
                new FilesGenerator($app['files'], $app['config'])
            );
        });

        $this->app->bindShared('command.asgard.scaffold', function ($app) {
            return new ScaffoldCommand($app['asgard.module.scaffold']);
        });
    }

    /**
     * Register the update module command
     */
    private function registerUpdateCommand()
    {
        $this->app->bindShared('command.asgard.update', function($app) {
            return new UpdateModuleCommand(new Composer($app['files'], base_path()));
        });
    }
}