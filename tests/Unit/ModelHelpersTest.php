<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class ModelHelpersTest extends TestCase
{
    public function test_role_helpers_identify_management_users(): void
    {
        $admin = new User(['role' => 'admin']);
        $manager = new User(['role' => 'manager']);
        $member = new User(['role' => 'member']);

        $this->assertTrue($admin->canManageTeam());
        $this->assertTrue($manager->canManageTeam());
        $this->assertFalse($member->canManageTeam());
    }
}
