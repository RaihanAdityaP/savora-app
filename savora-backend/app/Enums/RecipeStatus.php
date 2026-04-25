<?php

namespace App\Enums;

enum RecipeStatus: string
{
    case PENDING  = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Label bahasa Indonesia untuk display di UI / notifikasi.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING  => 'Menunggu Persetujuan',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
        };
    }

    /**
     * Cek apakah status ini visible ke publik.
     */
    public function isPublic(): bool
    {
        return $this === self::APPROVED;
    }
}