# iworkshop

CLI tool to scaffold Imagina base packages for Laravel.

## Overview

**iworkshop** provides artisan commands to quickly scaffold new Laravel modules and entities, following Imagina's conventions. It helps automate the creation of folder structures, configuration files, models, controllers, and more, making it easier to maintain consistency across packages.

## Features

- Scaffold a new Laravel module with a single command.
- Generate entities (models, translations, controllers, etc.) for existing modules.
- Predefined folder structure and stub files for rapid development.
- Integrates with Laravel's artisan command system.

## Installation DEV

Add the package to your Laravel project's `composer.json` (usually as a local package):

```json
"repositories": [
    {
        "type": "path",
        "url": "./packages/imagina/iworkshop"
    }
]
```
Then run 
```sh
composer require imagina/iworkshop:dev-main
```

## Usage

### Scaffold a New Module

```sh
php artisan module:scaffold {ModuleName}
```

This command creates a new module under `packages/imagina/{ModuleName}` with the standard folder structure and initial files.

### Scaffold a New Entity

```sh
php artisan module:scaffold:entity {ModuleName} {EntityName}
```

This command generates model, translation, controller, and related files for the specified entity within the given module.

## Service Provider

The package registers its commands via `Imagina\Iworkshop\Providers\WorkshopServiceProvider`.

## Requirements

- Laravel 10.x, 11.x, or ^12.0
- PHP 8.1+

## License

MIT

## Author

- msolanogithub <msolano@imaginacolombia.com>