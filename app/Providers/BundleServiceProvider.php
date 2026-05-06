<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use ReflectionClass;

class BundleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        collect(File::directories(app_path('Bundles')))
            ->each(fn (string $bundlePath) => $this->loadBundle($bundlePath));
    }

    private function loadBundle(string $bundlePath): void
    {
        $bundle = basename($bundlePath);

        $this->loadRoutes($bundlePath);
        $this->loadMigrationsFrom($bundlePath.'/Migrations');
        $this->loadViewsFrom(resource_path("views/Bundles/{$bundle}"), $bundle);
        $this->registerLivewireComponents($bundle, $bundlePath.'/Livewire');
    }

    private function loadRoutes(string $bundlePath): void
    {
        collect(File::glob($bundlePath.'/Routes/*.php'))
            ->each(fn (string $routeFile) => Route::middleware('web')->group($routeFile));
    }

    private function registerLivewireComponents(string $bundle, string $componentPath): void
    {
        if (! File::isDirectory($componentPath)) {
            return;
        }

        collect(File::allFiles($componentPath))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->each(function ($file) use ($bundle, $componentPath): void {
                $class = $this->classFromFile($bundle, $componentPath, $file->getPathname());

                if (! is_subclass_of($class, \Livewire\Component::class)) {
                    return;
                }

                Livewire::component($this->componentAlias($bundle, $class), $class);
            });
    }

    private function classFromFile(string $bundle, string $componentPath, string $file): string
    {
        $relative = str($file)
            ->after($componentPath.DIRECTORY_SEPARATOR)
            ->beforeLast('.php')
            ->replace(DIRECTORY_SEPARATOR, '\\');

        return "App\\Bundles\\{$bundle}\\Livewire\\{$relative}";
    }

    private function componentAlias(string $bundle, string $class): string
    {
        $shortName = (new ReflectionClass($class))->getShortName();

        return str($bundle.'.'.$shortName)->kebab()->toString();
    }
}
