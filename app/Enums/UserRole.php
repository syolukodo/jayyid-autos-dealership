<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case SalesOfficer = 'sales_officer';
    case Accountant = 'accountant';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::SalesOfficer => 'Sales Officer',
            self::Accountant => 'Finance & Accountant',
            self::Customer => 'Customer',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }

    /**
     * Back-office modules this role may open.
     *
     * @return list<StaffModule>
     */
    public function modules(): array
    {
        return match ($this) {
            self::Admin => StaffModule::cases(),
            self::SalesOfficer => [StaffModule::Vehicles, StaffModule::AddVehicle, StaffModule::Inquiries],
            self::Accountant => [StaffModule::Inquiries, StaffModule::Payments],
            self::Customer => [],
        };
    }

    public function canAccess(StaffModule $module): bool
    {
        return in_array($module, $this->modules(), true);
    }

    /**
     * @return list<self>
     */
    public static function staff(): array
    {
        return [self::Admin, self::SalesOfficer, self::Accountant];
    }

    public static function fromLabel(string $label): ?self
    {
        foreach (self::cases() as $role) {
            if ($role->label() === $label) {
                return $role;
            }
        }

        return null;
    }
}
