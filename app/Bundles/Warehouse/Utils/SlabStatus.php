<?php

namespace App\Bundles\Warehouse\Utils;

enum SlabStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Damaged = 'damaged';
    case Missing = 'missing';

    public function label(): string
    {
        return __(match ($this) {
            self::Available => 'Available',
            self::Reserved => 'Reserved',
            self::Sold => 'Sold',
            self::Damaged => 'Damaged',
            self::Missing => 'Missing',
        });
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'bg-emerald-100 text-emerald-800',
            self::Reserved => 'bg-[#FDD07D] text-[#333333]',
            self::Sold => 'bg-zinc-200 text-zinc-700',
            self::Damaged => 'bg-red-100 text-red-800',
            self::Missing => 'bg-red-100 text-red-800',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
