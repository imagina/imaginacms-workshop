<?php

namespace Imagina\Iworkshop\Commands;

use Illuminate\Console\Command;
use Imagina\Iworkshop\Support\ScaffoldTrait;

class MakeModuleCommand extends Command
{
    use ScaffoldTrait;

    protected $signature = 'module:scaffold {moduleCreation?}';
    protected $description = 'Create a laravel-module | {module?}';

    public function handle(): void
    {
        $this->getModuleName($this->ARG_MODULE_CREATION);
        $this->components->info("Creating module: [$this->moduleName]");
        $this->createFolderStructure();
        $this->createInitialFilesFromStubs();
        $this->runComposerDump();
        $this->components->success("Module [$this->moduleName] created successfully.");
    }

    protected function createFolderStructure(): void
    {
        $folders = [
            'config',
            $this->appFolderPath . 'Models',
            $this->appFolderPath . 'Http/Controllers/Api',
            $this->appFolderPath . 'Http/Requests',
            $this->appFolderPath . 'Transformers',
            $this->appFolderPath . 'Providers',
            $this->appFolderPath . 'Repositories/Eloquent',
            $this->appFolderPath . 'Repositories/Cache',
            'database/Factories',
            'database/Migrations',
            'database/Seeders',
            'routes',
            'test',
        ];
        foreach ($folders as $folder) {
            $dir = "$this->modulePath/$folder";
            $this->components->task("Generating file $dir", function () use($dir) {
                mkdir($dir, 0755, true);
            });
        };
    }

    protected function createInitialFilesFromStubs(): void
    {
        $this->generateFiles([
            ['stub' => '0-composer', 'destination' => 'composer.json'],
            ['stub' => '0-module', 'destination' => 'module.json'],
            ['stub' => '0-seeder', 'destination' => "database/Seeders/{$this->moduleName}DatabaseSeeder.php"],
            ['stub' => '1-config', 'destination' => 'config/config.php'],
            ['stub' => '1-settings', 'destination' => 'config/settings.php'],
            ['stub' => '2-permissions', 'destination' => 'config/permissions.php'],
            ['stub' => '6-routes-web', 'destination' => 'routes/web.php'],
            ['stub' => '6-routes-api', 'destination' => 'routes/api.php'],
            ['stub' => '7-module-service-provider', 'destination' => $this->appFolderPath . "Providers/" . $this->moduleName . "ServiceProvider.php"],
            ['stub' => '7-event-provider', 'destination' => $this->appFolderPath . "Providers/EventServiceProvider.php"],
            ['stub' => '7-route-provider', 'destination' => $this->appFolderPath . "Providers/RouteServiceProvider.php"],
        ]);
    }
}
