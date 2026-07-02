<?php

namespace App\Services;

class OperatorPermissions
{
    private const ROLE_LABELS = [
        'super_user' => 'Super-user',
        'admin' => 'Administrador',
        'manager' => 'Gestor',
        'cashier' => 'Caixa',
    ];

    private const ROLE_PERMISSIONS = [
        'super_user' => ['*'],
        'admin' => [
            'dashboard.view',
            'pos.use',
            'sales.view',
            'sales.create',
            'sales.credit_note',
            'cash.operate',
            'cash.audit',
            'reports.view',
            'current_account.manage',
            'purchases.create',
            'purchases.approve',
            'purchases.receive',
            'catalog.manage',
            'restaurant.manage',
            'audit.view',
            'management.view',
        ],
        'manager' => [
            'dashboard.view',
            'pos.use',
            'sales.view',
            'sales.create',
            'sales.credit_note',
            'cash.operate',
            'cash.audit',
            'reports.view',
            'current_account.manage',
            'purchases.create',
            'purchases.approve',
            'purchases.receive',
            'catalog.manage',
            'restaurant.manage',
            'audit.view',
            'management.view',
        ],
        'cashier' => [
            'dashboard.view',
            'pos.use',
            'sales.view',
            'sales.create',
            'cash.operate',
            'restaurant.operate',
        ],
    ];

    public static function roleOptions(): array
    {
        return self::ROLE_LABELS;
    }

    public static function roleLabel(?string $role): string
    {
        return self::ROLE_LABELS[$role] ?? ucfirst((string) $role);
    }

    public static function roleKeys(): array
    {
        return array_keys(self::ROLE_LABELS);
    }

    public static function allows(?string $role, string $permission): bool
    {
        $permissions = self::ROLE_PERMISSIONS[$role] ?? [];

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public static function allowsAny(?string $role, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::allows($role, $permission)) {
                return true;
            }
        }

        return false;
    }
}
