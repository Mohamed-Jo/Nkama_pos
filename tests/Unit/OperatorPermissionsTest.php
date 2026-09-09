<?php

namespace Tests\Unit;

use App\Services\OperatorPermissions;
use PHPUnit\Framework\TestCase;

class OperatorPermissionsTest extends TestCase
{
    public function test_super_user_has_all_critical_permissions(): void
    {
        foreach (OperatorPermissions::criticalPermissions() as $permission) {
            $this->assertTrue(OperatorPermissions::allows('super_user', $permission));
        }
    }

    public function test_admin_does_not_have_critical_system_permissions(): void
    {
        foreach (OperatorPermissions::criticalPermissions() as $permission) {
            $this->assertFalse(OperatorPermissions::allows('admin', $permission));
        }
    }

    public function test_admin_keeps_operational_permissions(): void
    {
        $this->assertTrue(OperatorPermissions::allows('admin', 'sales.create'));
        $this->assertTrue(OperatorPermissions::allows('admin', 'purchases.approve'));
        $this->assertTrue(OperatorPermissions::allows('admin', 'stock.transfer.approve'));
        $this->assertTrue(OperatorPermissions::allows('admin', 'reports.view'));
    }
}
