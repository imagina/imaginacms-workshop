<?php

namespace Modules\Workshop\Scaffold\Module\Generators;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class EntityGenerator extends Generator
{
    /**
     * @var \Illuminate\Contracts\Console\Kernel
     */
    protected $artisan;

    public function __construct(Filesystem $finder, Repository $config)
    {
        parent::__construct($finder, $config);
        $this->artisan = app('Illuminate\Contracts\Console\Kernel');
    }

    protected $views = [
        'index-view.stub' => 'Resources/views/admin/$ENTITY_NAME$/index.blade',
        'create-view.stub' => 'Resources/views/admin/$ENTITY_NAME$/create.blade',
        'edit-view.stub' => 'Resources/views/admin/$ENTITY_NAME$/edit.blade',
        'create-fields.stub' => 'Resources/views/admin/$ENTITY_NAME$/partials/create-fields.blade',
        'edit-fields.stub' => 'Resources/views/admin/$ENTITY_NAME$/partials/edit-fields.blade',
    ];

    /**
     * Generate the given entities
     */
    public function generate(array $entities, bool $regenerateSidebar = true)
    {
        $entityType = strtolower($this->entityType);
        $entityTypeStub = "entity-{$entityType}.stub";

        if ($regenerateSidebar === true) {
            $this->generateSidebarListener($entities);
        }

        foreach ($entities as $entity) {
            $this->writeFile(
                $this->getModulesPath("Entities/$entity"),
                $this->getContentForStub($entityTypeStub, $entity)
            );
            $this->writeFile(
                $this->getModulesPath("Entities/{$entity}Translation"),
                $this->getContentForStub("{$entityType}-entity-translation.stub", $entity)
            );
            if ($this->entityType == 'Eloquent') {
                $this->generateMigrationsFor($entity);
            }
            $this->generateRepositoriesFor($entity);

            //=============== Admin Controller
            //$this->generateControllerFor($entity);

            //=============== Admin Views
            //$this->generateViewsFor($entity);

            $this->generateLanguageFilesFor($entity);
            $this->appendBindingsToServiceProviderFor($entity);

            //=============== Admin Routes
            //$this->appendResourceRoutesToRoutesFileFor($entity);

            $this->appendPermissionsFor($entity);

            //=============== Admin Links Sidebar
            //$this->appendSidebarLinksFor($entity);

            // Requests
            $this->generateRequestsFor($entity);

            // Generate API
            $this->generateTransformerFor($entity);
            $this->generateApiRoutesFilesFor($entity);
            $this->generateApiControllerFor($entity);

            // Append Api Routes for Entity
            $this->appendResourceApiRoutesToRoutesFileFor($entity);
        }
    }

    /**
     * Generate the repositories for the given entity
     */
    private function generateRepositoriesFor(string $entity)
    {
        if (! $this->finder->isDirectory($this->getModulesPath('Repositories/'.$this->entityType))) {
            $this->finder->makeDirectory($this->getModulesPath('Repositories/'.$this->entityType));
        }

        $entityType = strtolower($this->entityType);
        $this->writeFile(
            $this->getModulesPath("Repositories/{$entity}Repository"),
            $this->getContentForStub('repository-interface.stub', $entity)
        );
        $this->writeFile(
            $this->getModulesPath("Repositories/Cache/Cache{$entity}Decorator"),
            $this->getContentForStub('cache-repository-decorator.stub', $entity)
        );
        $this->writeFile(
            $this->getModulesPath("Repositories/{$this->entityType}/{$this->entityType}{$entity}Repository"),
            $this->getContentForStub("{$entityType}-repository.stub", $entity)
        );
    }

    /**
     * Generate the controller for the given entity
     */
    private function generateControllerFor(string $entity)
    {
        $path = $this->getModulesPath('Http/Controllers/Admin');
        if (! $this->finder->isDirectory($path)) {
            $this->finder->makeDirectory($path);
        }
        $this->writeFile(
            $this->getModulesPath("Http/Controllers/Admin/{$entity}Controller"),
            $this->getContentForStub('admin-controller.stub', $entity)
        );
    }

    /**
     * Generate the Api controller for the given entity
     */
    private function generateApiControllerFor(string $entity)
    {
        $path = $this->getModulesPath('Http/Controllers/Api');
        if (! $this->finder->isDirectory($path)) {
            $this->finder->makeDirectory($path);
        }
        $this->writeFile(
            $this->getModulesPath("Http/Controllers/Api/{$entity}ApiController"),
            $this->getContentForStub('api-controller.stub', $entity)
        );
    }

    /**
     * Generate the requests for the given entity
     */
    private function generateRequestsFor(string $entity)
    {
        $path = $this->getModulesPath('Http/Requests');
        if (! $this->finder->isDirectory($path)) {
            $this->finder->makeDirectory($path);
        }
        $this->writeFile(
            $this->getModulesPath("Http/Requests/Create{$entity}Request"),
            $this->getContentForStub('create-request.stub', $entity)
        );
        $this->writeFile(
            $this->getModulesPath("Http/Requests/Update{$entity}Request"),
            $this->getContentForStub('update-request.stub', $entity)
        );
    }

    /**
     * Generate views for the given entity
     */
    private function generateViewsFor(string $entity)
    {
        $lowerCasePluralEntity = strtolower(Str::plural($entity));
        $this->finder->makeDirectory($this->getModulesPath("Resources/views/admin/{$lowerCasePluralEntity}/partials"), 0755, true);

        foreach ($this->views as $stub => $view) {
            $view = str_replace('$ENTITY_NAME$', $lowerCasePluralEntity, $view);
            $this->writeFile(
                $this->getModulesPath($view),
                $this->getContentForStub($stub, $entity)
            );
        }
    }

    /**
     * Generate language files for the given entity
     */
    private function generateLanguageFilesFor(string $entity)
    {
        $lowerCaseEntity = Str::plural(strtolower($entity));
        $path = $this->getModulesPath('Resources/lang/en');
        if (! $this->finder->isDirectory($path)) {
            $this->finder->makeDirectory($path);
        }
        $this->writeFile(
            $this->getModulesPath("Resources/lang/en/{$lowerCaseEntity}"),
            $this->getContentForStub('lang-entity.stub', $entity)
        );
    }

    /**
     * Generate migrations file for eloquent entities
     */
    private function generateMigrationsFor(string $entity)
    {
        usleep(250000);
        $lowercasePluralEntityName = strtolower(Str::plural($entity));
        $lowercaseModuleName = strtolower($this->name);
        $migrationName = $this->getDateTimePrefix()."create_{$lowercaseModuleName}_{$lowercasePluralEntityName}_table";
        $this->writeFile(
            $this->getModulesPath("Database/Migrations/{$migrationName}"),
            $this->getContentForStub('create-table-migration.stub', $entity)
        );
        usleep(250000);
        $lowercaseEntityName = strtolower($entity);
        $migrationName = $this->getDateTimePrefix()."create_{$lowercaseModuleName}_{$lowercaseEntityName}_translations_table";
        $this->writeFile(
            $this->getModulesPath("Database/Migrations/{$migrationName}"),
            $this->getContentForStub('create-translation-table-migration.stub', $entity)
        );
    }

    /**
     * Generate Api Routes for the given entity
     */
    private function generateApiRoutesFilesFor(string $entity)
    {
        // Check if exist apiRoutes.php
        $pathApi = $this->getModulesPath('Http/apiRoutes');
        if (! $this->finder->isFile($pathApi.'.php')) {
            $this->writeFile(
                $pathApi,
                $this->getContentForStub('routes-api.stub', $entity)
            );
        }
    }

    /**
     * Generate the Transformers for the given entity
     */
    private function generateTransformerFor(string $entity)
    {
        $path = $this->getModulesPath('Transformers');
        if (! $this->finder->isDirectory($path)) {
            $this->finder->makeDirectory($path);
        }
        $this->writeFile(
            $this->getModulesPath("Transformers/{$entity}Transformer"),
            $this->getContentForStub('transformer.stub', $entity)
        );
    }

    /**
     * Append the api routes
     *
     *
     * @throws FileNotFoundException
     */
    private function appendResourceApiRoutesToRoutesFileFor(string $entity)
    {
        $routeContent = $this->finder->get($this->getModulesPath('Http/apiRoutes.php'));
        $content = $this->getContentForStub('route-resource-api.stub', $entity);
        $routeContent = str_replace('// append', $content, $routeContent);
        $this->finder->put($this->getModulesPath('Http/apiRoutes.php'), $routeContent);
    }

    /**
     * Append the IoC bindings for the given entity to the Service Provider
     *
     *
     * @throws FileNotFoundException
     */
    private function appendBindingsToServiceProviderFor(string $entity)
    {
        $moduleProviderContent = $this->finder->get($this->getModulesPath("Providers/{$this->name}ServiceProvider.php"));
        $binding = $this->getContentForStub('bindings.stub', $entity);
        $moduleProviderContent = str_replace('// add bindings', $binding, $moduleProviderContent);
        $this->finder->put($this->getModulesPath("Providers/{$this->name}ServiceProvider.php"), $moduleProviderContent);
    }

    /**
     * Append the routes for the given entity to the routes file
     *
     *
     * @throws FileNotFoundException
     */
    private function appendResourceRoutesToRoutesFileFor(string $entity)
    {
        $routeContent = $this->finder->get($this->getModulesPath('Http/backendRoutes.php'));
        $content = $this->getContentForStub('route-resource.stub', $entity);
        $routeContent = str_replace('// append', $content, $routeContent);
        $this->finder->put($this->getModulesPath('Http/backendRoutes.php'), $routeContent);
    }

    /**
     *
     * @throws FileNotFoundException
     */
    private function appendPermissionsFor(string $entity)
    {
        $permissionsContent = $this->finder->get($this->getModulesPath('Config/permissions.php'));
        $content = $this->getContentForStub('permissions-append.stub', $entity);
        $permissionsContent = str_replace('// append', $content, $permissionsContent);
        $this->finder->put($this->getModulesPath('Config/permissions.php'), $permissionsContent);
    }

    private function appendSidebarLinksFor(string $entity)
    {
        $sidebarComposerContent = $this->finder->get($this->getModulesPath("Listeners/Register{$this->name}Sidebar.php"));
        $content = $this->getContentForStub('append-sidebar-extender.stub', $entity);
        $sidebarComposerContent = str_replace('// append', $content, $sidebarComposerContent);

        $this->finder->put($this->getModulesPath("Listeners/Register{$this->name}Sidebar.php"), $sidebarComposerContent);
    }

    private function appendBackendTranslations(string $entity)
    {
        $moduleProviderContent = $this->finder->get($this->getModulesPath("Providers/{$this->name}ServiceProvider.php"));

        $translations = $this->getContentForStub('translations-append.stub', $entity);
        $moduleProviderContent = str_replace('// append translations', $translations, $moduleProviderContent);
        $this->finder->put($this->getModulesPath("Providers/{$this->name}ServiceProvider.php"), $moduleProviderContent);
    }

    /**
     * Generate a filled sidebar view composer
     * Or an empty one of no entities
     */
    private function generateSidebarExtender($entities)
    {
        if (count($entities) > 0) {
            $firstModuleName = $entities[0];

            return $this->writeFile(
                $this->getModulesPath('Sidebar/SidebarExtender'),
                $this->getContentForStub('sidebar-extender.stub', $firstModuleName)
            );
        }

        return $this->writeFile(
            $this->getModulesPath('Sidebar/SidebarExtender'),
            $this->getContentForStub('empty-sidebar-view-composer.stub', 'abc')
        );
    }

    /**
     * Generate a sidebar event listener
     */
    public function generateSidebarListener($entities)
    {
        $name = "Register{$this->name}Sidebar";

        if (count($entities) > 0) {
            return $this->writeFile(
                $this->getModulesPath("Listeners/$name"),
                $this->getContentForStub('sidebar-listener.stub', $name)
            );
        }

        return $this->writeFile(
            $this->getModulesPath("Listeners/$name"),
            $this->getContentForStub('sidebar-listener-empty.stub', $name)
        );
    }

    /**
     * Get the current time with microseconds
     */
    private function getDateTimePrefix(): string
    {
        $t = microtime(true);
        $micro = sprintf('%06d', ($t - floor($t)) * 1000000);
        $d = new \DateTime(date('Y-m-d H:i:s.'.$micro, $t));

        return $d->format('Y_m_d_Hisu_');
    }
}
