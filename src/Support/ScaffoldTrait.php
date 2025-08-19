<?php

namespace Imagina\Iworkshop\Support;

use Exception;
use Illuminate\Support\Str;
use function Laravel\Prompts\text;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Illuminate\Support\Facades\Process;

trait ScaffoldTrait
{
    public string $laravelModulesPath = '';
    public string $appFolderPath = '';
    public string $modulePath = '';
    public string $moduleName = '';
    public string $entityName = '';
    public string $entityPath = '';

    public string $ARG_MODULE_CREATION = 'moduleCreation';
    public string $ARG_MODULE_SCAFFOLDING = 'moduleScaffolding';
    public string $ARG_ENTITY_CREATION = 'entityCreation';

    protected function getModuleName(string $argument): string
    {
        $this->laravelModulesPath = config('modules.paths.modules', base_path('Modules'));
        $this->appFolderPath = config('modules.paths.app_folder');
        if (!$this->moduleName) $this->moduleName = $this->getArgument($argument, 'Please enter the name of the module');
        $this->modulePath = $this->getModulePath($this->moduleName);
        return $this->moduleName;
    }

    protected function getEntityName(string $argument): string
    {
        $this->getModuleName($this->ARG_MODULE_SCAFFOLDING);
        $this->entityName = $this->getArgument($argument, 'Please enter the name of the entity');
        $this->entityPath = $this->getEntityPath($this->entityName);
        return $this->entityName;
    }

    protected function getArgument(string $argument, string $description): string
    {
        $value = Str::studly(trim((string)$this->argument($argument)));
        if ($value) {
            $isInvalid = $this->validateArg($argument, $value);
            if ($isInvalid) {
                $this->warn("⚠ $isInvalid");
                $value = null;
            }
        }
        if (!$value) $value = Str::studly(text(
            label: $description,
            required: true,
            validate: fn(string $value) => $this->validateArg($argument, $value)
        ));
        return $value;
    }

    protected function getModulePath(string $moduleName): string
    {
        return "$this->laravelModulesPath/$moduleName";
    }

    protected function getEntityPath(string $entityName): string
    {
        return "$this->modulePath/" . config('modules.paths.app_folder') . "Models/$entityName.php";
    }

    protected function validateArg(string $argument, string $value): ?string
    {
        $isInvalid = null;
        $allowedArg = [$this->ARG_MODULE_CREATION, $this->ARG_MODULE_SCAFFOLDING, $this->ARG_ENTITY_CREATION];
        if (!in_array($argument, $allowedArg, true)) $isInvalid = 'Invalid Argument.';

        if (strlen($value) < 3) $isInvalid = 'The argument must be at least 3 characters.';

        if (Str::contains($value, ' ')) $isInvalid = 'The argument must not contain spaces.';

        if (!$isInvalid) {
            $modulePath = $this->getModulePath($value);
            $entityPath = $this->getEntityPath($value);
            $isInvalid = match ($argument) {
                $this->ARG_MODULE_CREATION => file_exists($modulePath) ? "Module $value already exists." : null,
                $this->ARG_MODULE_SCAFFOLDING => !file_exists($modulePath) ? "Module $value doesn't exist." : null,
                $this->ARG_ENTITY_CREATION => file_exists($entityPath) ? "Entity $value already exists." : null,
                default => null,
            };
        }
        return $isInvalid;
    }

    protected function generateFiles(array $files): void
    {
        foreach ($files as $file) {
            $dir = "$this->modulePath/" . $file['destination'];
            $this->components->task("Generating file $dir", function () use ($file, $dir) {
                $content = $this->getContentForStub($file['stub']);
                $parentDir = dirname($dir);
                if (!is_dir($parentDir)) {
                    mkdir($parentDir, 0755, true);
                }
                file_put_contents($dir, $content);
            });
        }
    }

    protected function getContentForStub(string $stubName): string
    {
        $stubPath = __DIR__ . "/../../stubs/$stubName.stub";
        $moduleName = $this->moduleName;
        $entityName = $this->entityName;
        if (!file_exists($stubPath)) throw new Exception("Stub not found: $stubPath");
        $stub = file_get_contents($stubPath);

        return str_replace(
            [
                '$MODULE_NAMESPACE$',
                '$APP_FOLDER_NAME$',
                '$VENDOR$',
                '$AUTHOR_NAME$',
                '$AUTHOR_EMAIL$',
                '$MODULE_NAME$',
                '$LOWERCASE_MODULE_NAME$',
                '$PLURAL_LOWERCASE_MODULE_NAME$',
                '$CLASS_NAME$',
                '$LOWERCASE_CLASS_NAME$',
                '$PLURAL_LOWERCASE_CLASS_NAME$',
                '$PLURAL_CLASS_NAME$',
                '$ENTITY_TYPE$',
                'PATH_VIEWS',
                'PATH_LANG',
                'PATH_CONFIG',
                '$MIGRATIONS_PATH$',
                'FACTORIES_PATH',
                '$WEB_ROUTES_PATH$',
                '$API_ROUTES_PATH$'
            ],
            [
                config('modules.namespace'),
                config('modules.paths.app_folder'),
                config('modules.composer.vendor'),
                config('modules.composer.author.name'),
                config('modules.composer.author.email'),
                $moduleName,
                strtolower($moduleName),
                strtolower(Str::plural($moduleName)),
                $entityName,
                strtolower($entityName),
                strtolower(Str::plural($entityName)),
                Str::plural($entityName),
                'Eloquent',
                GenerateConfigReader::read('views')->getPath(),
                GenerateConfigReader::read('lang')->getPath(),
                GenerateConfigReader::read('config')->getPath(),
                GenerateConfigReader::read('migration')->getPath(),
                GenerateConfigReader::read('factory')->getPath(),
                config('stubs.files.routes/web', 'Routes/web.php'),
                config('stubs.files.routes/api', 'Routes/api.php'),
            ],
            $stub
        );
    }

    protected function appendStub(string $stub, string $destination, string $replaceValue = '// append'): void
    {
        $fileDestination = "$this->modulePath/" . $destination;
        $stubContent = $this->getContentForStub($stub);
        $destinationContent = file_get_contents($fileDestination);

        // Replace the marker with the stub content (preserving the marker for future appends)
        $newContent = str_replace($replaceValue, $stubContent, $destinationContent);

        // Write back to the permissions file
        file_put_contents($fileDestination, $newContent);
    }

    protected function runComposerDump(): void
    {
        Process::path(base_path())
            ->command('composer dump-autoload')
            ->run()->output();
    }
}
