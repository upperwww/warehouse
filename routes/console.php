<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about:warehouse', function (): void {
    $this->info('Warehouse bundle is loaded.');
});
