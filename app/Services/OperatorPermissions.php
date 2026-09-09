<?php

namespace App\Services;

class OperatorPermissions
{
    private const ROLE_LABELS = [
        'super_user' => 'Super usuario',
        'admin' => 'Administrador',
        'manager' => 'Gestor',
        'cashier' => 'Caixa',
        'stock' => 'Stock/Armazem',
        'accountant' => 'Financeiro/Contabilista',
        'auditor' => 'Auditor',
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
            'accounting.view',
            'accounting.manage',
            'purchases.create',
            'purchases.approve',
            'purchases.receive',
            'catalog.manage',
            'commercial.manage',
            'stock.adjust',
            'stock.transfer.request',
            'stock.transfer.approve',
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
            'accounting.view',
            'purchases.create',
            'purchases.approve',
            'purchases.receive',
            'catalog.manage',
            'commercial.manage',
            'stock.adjust',
            'stock.transfer.request',
            'stock.transfer.approve',
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
        'stock' => [
            'dashboard.view',
            'reports.view',
            'catalog.manage',
            'purchases.receive',
            'stock.adjust',
            'stock.transfer.request',
            'stock.transfer.approve',
            'management.view',
        ],
        'accountant' => [
            'dashboard.view',
            'sales.view',
            'cash.audit',
            'reports.view',
            'current_account.manage',
            'accounting.view',
            'accounting.manage',
            'purchases.create',
            'purchases.approve',
            'commercial.manage',
            'audit.view',
            'management.view',
        ],
        'auditor' => [
            'dashboard.view',
            'sales.view',
            'cash.audit',
            'reports.view',
            'audit.view',
            'accounting.view',
            'management.view',
        ],
    ];

    public static function permissionLabels(): array
    {
        return [
            'dashboard.view' => 'Ver dashboard',
            'pos.use' => 'Usar POS',
            'sales.view' => 'Ver vendas',
            'sales.create' => 'Criar vendas/proformas',
            'sales.credit_note' => 'Emitir notas de credito',
            'cash.operate' => 'Operar caixa',
            'cash.audit' => 'Auditar caixa',
            'reports.view' => 'Ver relatorios',
            'current_account.manage' => 'Gerir conta corrente',
            'accounting.view' => 'Ver contabilidade',
            'accounting.manage' => 'Gerir contabilidade',
            'purchases.create' => 'Criar compras',
            'purchases.approve' => 'Aprovar compras',
            'purchases.receive' => 'Receber compras',
            'catalog.manage' => 'Gerir catalogo',
            'commercial.manage' => 'Gerir precos/promocoes',
            'stock.adjust' => 'Ajustar stock',
            'stock.transfer.request' => 'Solicitar transferencia de stock',
            'stock.transfer.approve' => 'Aprovar transferencia de stock',
            'restaurant.manage' => 'Gerir restaurante',
            'restaurant.operate' => 'Operar restaurante',
            'audit.view' => 'Ver auditoria',
            'management.view' => 'Ver gestao',
            'settings.manage' => 'Gerir configuracoes criticas',
            'operators.manage' => 'Gerir operadores',
            'backup.manage' => 'Gerir backups',
            'security.manage' => 'Gerir seguranca do sistema',
        ];
    }

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

    public static function criticalPermissions(): array
    {
        return [
            'settings.manage',
            'operators.manage',
            'backup.manage',
            'security.manage',
        ];
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