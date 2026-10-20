<?php

namespace App\Enums;

/**
 * Back-office sections, in sidebar order. The case value is the key used by
 * the `staff:` route middleware.
 */
enum StaffModule: string
{
    case Dashboard = 'dashboard';
    case Vehicles = 'vehicles';
    case AddVehicle = 'add-vehicle';
    case Inquiries = 'inquiries';
    case Users = 'users';
    case Payments = 'payments';
    case PromoCodes = 'promo-codes';
    case Settings = 'settings';

    public function label(): string
    {
        return match ($this) {
            self::Dashboard => 'Dashboard',
            self::Vehicles => 'Vehicles',
            self::AddVehicle => 'Add Vehicle',
            self::Inquiries => 'Inquiries',
            self::Users => 'Users & Staff',
            self::Payments => 'Payments',
            self::PromoCodes => 'Promo Codes',
            self::Settings => 'Settings',
        };
    }

    public function routeName(): string
    {
        return match ($this) {
            self::Dashboard => 'admin.dashboard',
            self::Vehicles => 'admin.vehicles.index',
            self::AddVehicle => 'admin.vehicles.create',
            self::Inquiries => 'admin.inquiries.index',
            self::Users => 'admin.users.index',
            self::Payments => 'admin.payments.index',
            self::PromoCodes => 'admin.promo-codes.index',
            self::Settings => 'admin.settings.edit',
        };
    }

    /**
     * Inner markup of the 24x24 sidebar icon.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Dashboard => '<rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>',
            self::Vehicles => '<path d="M5 17h14M4 17v-4.5a2 2 0 0 1 .35-1.13L6.6 8.1a2 2 0 0 1 1.72-.97h7.36a2 2 0 0 1 1.72.97l2.25 3.27c.22.33.35.72.35 1.13V17"/><path d="M4 17v2M20 17v2M7.5 13.5h1M15.5 13.5h1"/>',
            self::AddVehicle => '<path d="M12 5v14M5 12h14"/>',
            self::Inquiries => '<path d="M4 4h16v16H4z"/><path d="m4 6 8 6 8-6"/>',
            self::Users => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            self::Payments => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
            self::PromoCodes => '<path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><path d="M7 7h.01"/>',
            self::Settings => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        };
    }
}
