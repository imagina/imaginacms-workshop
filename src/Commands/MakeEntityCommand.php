<?php

namespace Imagina\Iworkshop\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Imagina\Iworkshop\Support\ScaffoldTrait;

class MakeEntityCommand extends Command
{
    use ScaffoldTrait;

    protected $signature = 'module:scaffold:entity {moduleScaffolding?} {entityCreation?}';
    protected $description = 'Create model to an existing module | {module?} {entity?}';

    public function handle(): void
    {
        $this->getEntityName($this->ARG_ENTITY_CREATION);
        $this->components->info("Creating Entity: [$this->entityName]");
        $this->generateFiles([
            [
                'stub' => '4-entity-eloquent',
                'destination' => $this->appFolderPath . "Models/$this->entityName.php"
            ],
            [
                'stub' => '4-eloquent-entity-translation',
                'destination' => $this->appFolderPath . "Models/{$this->entityName}Translation.php"
            ],
            [
                'stub' => '5-api-controller',
                'destination' => $this->appFolderPath . "Http/Controllers/Api/{$this->entityName}ApiController.php"
            ],
            [
                'stub' => '5-create-request',
                'destination' => $this->appFolderPath . "Http/Requests/Create{$this->entityName}Request.php"
            ],
            [
                'stub' => '5-update-request',
                'destination' => $this->appFolderPath . "Http/Requests/Update{$this->entityName}Request.php"
            ],
            [
                'stub' => '8-repository-interface',
                'destination' => $this->appFolderPath . "Repositories/{$this->entityName}Repository.php"
            ],
            [
                'stub' => '8-cache-repository-decorator',
                'destination' => $this->appFolderPath . "Repositories/Cache/Cache{$this->entityName}Decorator.php"
            ],
            [
                'stub' => '8-eloquent-repository',
                'destination' => $this->appFolderPath . "Repositories/Eloquent/Eloquent{$this->entityName}Repository.php"
            ],
            [
                'stub' => '9-transformer',
                'destination' => $this->appFolderPath . "Transformers/{$this->entityName}Transformer.php"
            ],
            [
                'stub' => '10-lang-entity',
                'destination' => "resources/lang/en/" . strtolower($this->entityName) . ".php"
            ],
            [
                'stub' => '10-lang-entity',
                'destination' => "resources/lang/es/" . strtolower($this->entityName) . ".php"
            ],
            ...$this->getMigrationFiles()
        ]);
        $this->appendStub('2-permissions-append', 'config/permissions.php');
        $this->appendStub('6-route-api-controller-append', 'routes/api.php', '// add-use-controller');
        $this->appendStub('6-route-api-append', 'routes/api.php');
        $this->appendStub(
            '7-bindings-append',
            $this->appFolderPath . "Providers/" . $this->moduleName . "ServiceProvider.php",
            '// append-bindings'
        );
        $this->appendStub(
            '7-bindings-use-append',
            $this->appFolderPath . "Providers/" . $this->moduleName . "ServiceProvider.php",
            '// append-use-bindings'
        );
        $this->runComposerDump();
        $this->components->success("Entity [$this->entityName] created successfully in module [$this->moduleName].");
    }

    protected function getMigrationFiles(): array
    {
        $lowercaseModule = strtolower($this->moduleName);
        $pluralEntity = strtolower(Str::plural($this->entityName));
        $singularEntity = strtolower($this->entityName);

        // Get base timestamp and increment for the second migration
        $timestamp = now();
        $migrationName1 = $timestamp->format('Y_m_d_His') . "_create_{$lowercaseModule}_{$pluralEntity}_table.php";
        $timestamp->addSecond();
        $migrationName2 = $timestamp->format('Y_m_d_His') . "_create_{$lowercaseModule}_{$singularEntity}_translations_table.php";

        // return files
        return [
            ['stub' => '3-create-table-migration', 'destination' => "database/Migrations/$migrationName1"],
            ['stub' => '3-create-translation-table-migration', 'destination' => "database/Migrations/$migrationName2"]
        ];
    }
}
