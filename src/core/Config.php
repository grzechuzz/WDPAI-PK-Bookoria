<?php


final class Config
{
    public const ROLE_ADMIN = 1;
    public const ROLE_LIBRARIAN = 2;
    public const ROLE_READER = 3;

    public const ROLE_CODE_ADMIN = 'ADMIN';
    public const ROLE_CODE_LIBRARIAN = 'LIBRARIAN';
    public const ROLE_CODE_READER = 'READER';

    public const COPY_AVAILABLE = 'AVAILABLE';
    public const COPY_LOANED = 'LOANED';
    public const COPY_HELD = 'HELD';
    public const COPY_LOST = 'LOST';
    public const COPY_MAINTENANCE = 'MAINTENANCE';

    public const RES_QUEUED = 'QUEUED';
    public const RES_READY = 'READY_FOR_PICKUP';
    public const RES_CANCELLED = 'CANCELLED';
    public const RES_EXPIRED = 'EXPIRED';
    public const RES_FULFILLED = 'FULFILLED';

    public const MAX_ACTIVE_LOANS = 5;
    public const LOAN_DAYS = 30;
    public const LOAN_RENEWAL_DAYS = 14;
    public const MAX_RENEWALS = 1;
    public const RESERVATION_HOLD_HOURS = 48;

    public const MIN_PASSWORD_LENGTH = 8;
    public const ISBN_LENGTH = 13;
    public const MIN_PUBLICATION_YEAR = 1400;
    public const MAX_DESCRIPTION_LENGTH = 5000;
    public const MAX_COVER_SIZE = 5_000_000; 
    public const MAX_INVENTORY_CODE_LENGTH = 64;

    public const BOOKS_PER_PAGE = 3;
    public const HISTORY_LIMIT = 50;
    public const HISTORY_MAX_LIMIT = 200;

    public static function isAdmin(?int $roleId): bool
    {
        return $roleId === self::ROLE_ADMIN;
    }

    public static function isLibrarian(?int $roleId): bool
    {
        return $roleId === self::ROLE_LIBRARIAN;
    }

    public static function isReader(?int $roleId): bool
    {
        return $roleId === self::ROLE_READER;
    }

    public static function canManageCatalog(?int $roleId): bool
    {
        return self::isAdmin($roleId);
    }

    public static function canManageCopies(?int $roleId): bool
    {
        return self::isLibrarian($roleId);
    }

    public static function canManageCirculation(?int $roleId): bool
    {
        return self::isLibrarian($roleId);
    }

    public static function canReserve(?int $roleId): bool
    {
        return self::isReader($roleId);
    }

    public static function maxPublicationYear(): int
    {
        return (int)date('Y') + 1;
    }

    public static function reservationStatusLabel(string $status): string
    {
        return match ($status) {
            self::RES_QUEUED => 'W kolejce',
            self::RES_READY => 'Gotowa do odbioru',
            self::RES_CANCELLED => 'Anulowana',
            self::RES_EXPIRED => 'Wygasła',
            self::RES_FULFILLED => 'Zrealizowana',
            default => $status,
        };
    }

    public static function copyStatusLabel(string $status): string
    {
        return match ($status) {
            self::COPY_AVAILABLE => 'Dostępna',
            self::COPY_LOANED => 'Wypożyczona',
            self::COPY_HELD => 'Zarezerwowana',
            self::COPY_LOST => 'Zagubiona',
            self::COPY_MAINTENANCE => 'W konserwacji',
            default => $status,
        };
    }

    private function __construct() {}
}