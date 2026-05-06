<?php

namespace App\Bundles\Warehouse\Utils;

enum UserRole: string
{
    case Admin = 'Admin';
    case Manager = 'Manager';
    case Worker = 'Worker';

    public function badge(): string
    {
        return match ($this) {
            self::Admin => 'bg-red-100 text-red-800',
            self::Manager => 'bg-blue-100 text-blue-800',
            self::Worker => 'bg-emerald-100 text-emerald-800',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->value])
            ->all();
    }
}
