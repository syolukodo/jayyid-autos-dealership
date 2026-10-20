<?php

namespace Tests\Unit;

use App\Enums\PreferredContact;
use App\Enums\StaffModule;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class SupportTest extends TestCase
{
    public function test_naira_formatting(): void
    {
        $this->assertSame('₦65,000,000', naira(65_000_000));
        $this->assertSame('₦0', naira(null));
        $this->assertSame('₦1,500', naira('1500.00'));
    }

    public function test_initials(): void
    {
        $this->assertSame('NE', initials('Ngozi  Eze'));
        $this->assertSame('A', initials('Abdulkabir'));
        $this->assertSame('A', initials(''));
    }

    public function test_role_permissions_match_the_back_office_matrix(): void
    {
        $this->assertCount(count(StaffModule::cases()), UserRole::Admin->modules());
        $this->assertTrue(UserRole::SalesOfficer->canAccess(StaffModule::AddVehicle));
        $this->assertFalse(UserRole::SalesOfficer->canAccess(StaffModule::Payments));
        $this->assertTrue(UserRole::Accountant->canAccess(StaffModule::Payments));
        $this->assertFalse(UserRole::Accountant->canAccess(StaffModule::Users));
        $this->assertSame([], UserRole::Customer->modules());
        $this->assertSame(UserRole::Accountant, UserRole::fromLabel('Finance & Accountant'));
    }

    public function test_contact_method_choices_map_to_stored_values(): void
    {
        $this->assertSame('Phone call', PreferredContact::fromChoice('Phone')->value);
        $this->assertSame('WhatsApp', PreferredContact::fromChoice('WhatsApp')->value);
        $this->assertSame('Phone call', PreferredContact::fromChoice(null)->value);
    }
}
