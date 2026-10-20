<?php

namespace Database\Seeders;

use App\Enums\InstalmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inquiry;
use App\Models\PaymentSchedule;
use App\Models\PromoCode;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Showroom catalogue and demo records from database/seed-data/*.json.
 */
class ShowroomSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'jayyid2026';

    private const DEMO_BUYER = 'Ibrahim Abdulkabir';

    /** @var array<string, User> */
    private array $users = [];

    /** @var array<string, int> title => vehicle id */
    private array $vehicleTitles = [];

    public function run(): void
    {
        $this->seedUsers();
        $vehicleIds = $this->seedVehicles();

        User::query()->where('email', 'buyer@jayyidautos.ng')->firstOrFail()
            ->savedVehicles()->attach(array_fill_keys(array_slice($vehicleIds, 0, 3), ['created_at' => now()]));

        $this->seedInquiries();
        $this->seedSchedules();
        $this->seedPromoCodes();
    }

    private function seedUsers(): void
    {
        $password = Hash::make(self::DEMO_PASSWORD);

        // name, email, phone, password, role, status, joined
        $people = [
            ['Abdulkabir Olaniyan', 'admin@jayyidautos.ng', '+234 803 456 7890', $password, UserRole::Admin, UserStatus::Active, '2024-01-08'],
            [self::DEMO_BUYER, 'buyer@jayyidautos.ng', '+234 803 111 2244', $password, UserRole::Customer, UserStatus::Active, '2024-09-12'],
            ['Fatima Yusuf', 'fatima.yusuf@example.com', '+234 805 990 1122', null, UserRole::Customer, UserStatus::Active, '2026-09-28'],
            ['Tunde Bakare', 'tunde.bakare@example.com', '+234 802 445 7788', null, UserRole::Customer, UserStatus::Active, '2026-08-05'],
            ['Ngozi Eze', 'ngozi.eze@example.com', '+234 806 332 9010', null, UserRole::Customer, UserStatus::Active, '2026-07-19'],
            ['Ibrahim Sule', 'ibrahim.sule@example.com', '+234 809 776 5543', null, UserRole::Customer, UserStatus::Active, '2026-10-01'],
            ['Bisi Adeleke', 'bisi.adeleke@example.com', '+234 801 224 6677', null, UserRole::Customer, UserStatus::Active, '2026-06-14'],
            // Seeded staff have no password yet: send them an invite from Users & Staff.
            ['Adeola Balogun', 'adeola@jayyidautos.ng', '+234 802 555 0110', null, UserRole::SalesOfficer, UserStatus::Active, '2026-01-14'],
            ['Kunle Adeyemi', 'kunle@jayyidautos.ng', '+234 803 555 0121', null, UserRole::Accountant, UserStatus::Active, '2026-03-02'],
            ['Halima Bello', 'halima@jayyidautos.ng', '+234 806 555 0132', null, UserRole::SalesOfficer, UserStatus::Suspended, '2026-05-21'],
        ];

        foreach ($people as [$name, $email, $phone, $hash, $role, $status, $joined]) {
            // The hash is computed once with the current settings, so the "hashed" cast keeps it as-is.
            $user = new User(['full_name' => $name, 'email' => $email, 'phone_number' => $phone, 'password' => $hash, 'role' => $role, 'status' => $status]);
            $user->email_verified_at = $user->created_at = $user->updated_at = Carbon::parse($joined.' 09:00:00');
            $user->save();

            $this->users[$name] = $user;
        }
    }

    /**
     * @return list<int> vehicle ids in catalogue order
     */
    private function seedVehicles(): array
    {
        $ids = [];
        foreach ($this->read('vehicles.json') as $v) {
            $vehicle = new Vehicle([
                'title' => $v['title'],
                'make' => $v['make'],
                'model' => $v['model'],
                'year' => (int) $v['year'],
                'condition' => $v['condition'],
                'category' => $v['category'],
                'price' => (int) $v['price'],
                'original_price' => isset($v['original_price']) ? (int) $v['original_price'] : null,
                'mileage' => $v['mileage'] ?? null,
                'transmission' => $v['transmission'] ?? 'Automatic',
                'fuel_type' => $v['fuel'] ?? 'Petrol',
                'engine' => $v['engine'] ?? null,
                'drive_type' => $v['drive'] ?? null,
                'colour' => $v['colour'] ?? null,
                'seats' => $v['seats'] ?? null,
                'location' => $v['location'] ?? null,
                'description' => $v['description'] ?? null,
                'features' => $v['features'] ?? [],
                'status' => $v['status'] ?? 'Available',
                'is_featured' => ! empty($v['featured']),
                'primary_image' => $v['image'] ?? null,
            ]);
            $vehicle->created_at = $vehicle->updated_at = Carbon::parse(($v['added'] ?? now()->toDateString()).' 09:00:00');
            $vehicle->save();

            $ids[] = $vehicle->id;
            $this->vehicleTitles[$vehicle->title] = $vehicle->id;
        }

        return $ids;
    }

    private function seedInquiries(): void
    {
        $data = $this->read('inquiries.json');
        $buyer = $this->users[self::DEMO_BUYER];

        foreach ($data['leads'] ?? [] as $lead) {
            $user = $this->users[$lead['name']] ?? null;
            $this->createInquiry($lead['ref'], $user, $lead['name'], $user?->email ?? '', $lead['phone'], $lead['vehicle'], $lead['topic'], $lead['status'], $lead['date'].' 09:00:00');
        }

        foreach ($data['mine'] ?? [] as $mine) {
            $this->createInquiry($mine['ref'], $buyer, $buyer->full_name, $buyer->email, $buyer->phone_number, $mine['vehicle'], $mine['type'], $mine['status'], $mine['date'].' 10:00:00');
        }
    }

    private function createInquiry(string $ref, ?User $user, string $name, string $email, ?string $phone, string $vehicle, string $subject, string $status, string $date): void
    {
        $inquiry = new Inquiry([
            'reference_code' => $ref,
            'user_id' => $user?->id,
            'vehicle_id' => $this->vehicleFor($vehicle),
            'client_name' => $name,
            'client_email' => $email,
            'client_phone' => $phone,
            'preferred_contact' => 'Phone call',
            'subject' => $subject,
            'status' => $status,
        ]);
        $inquiry->created_at = $inquiry->updated_at = Carbon::parse($date);
        $inquiry->save();
    }

    private function seedSchedules(): void
    {
        $data = $this->read('payments.json');

        foreach ($data['plans'] ?? [] as $plan) {
            $customer = $this->users[$plan['customer']] ?? null;
            [$vehicleId, $total, $deposit, $balance, $instalments] = $plan['customer'] === self::DEMO_BUYER && isset($data['plan'])
                ? $this->demoBuyerPlan($data['plan'])
                : $this->generatedPlan($plan);

            if ($customer === null || $vehicleId === null) {
                continue;
            }

            $created = $instalments !== [] ? Carbon::parse($instalments[0][0].' 09:00:00') : now();
            $schedule = new PaymentSchedule([
                'schedule_code' => $plan['ref'],
                'user_id' => $customer->id,
                'vehicle_id' => $vehicleId,
                'total_price' => $total,
                'deposit' => $deposit,
                'total_paid' => $total - $balance,
                'remaining_balance' => $balance,
                'status' => $plan['status'],
            ]);
            $schedule->created_at = $schedule->updated_at = $created;
            $schedule->save();

            foreach ($instalments as $index => [$due, $amount, $status]) {
                $schedule->instalments()->create([
                    'sequence' => $index + 1,
                    'due_date' => $due,
                    'amount' => $amount,
                    'status' => $status,
                    'paid_at' => $status === InstalmentStatus::Paid->value ? $due.' 12:00:00' : null,
                ]);
            }
        }
    }

    /**
     * The demo buyer's schedule is the detailed plan shown on their dashboard.
     *
     * @param  array<string, mixed>  $detail
     * @return array{0: ?int, 1: int, 2: int, 3: int, 4: list<array{0: string, 1: int, 2: string}>}
     */
    private function demoBuyerPlan(array $detail): array
    {
        $instalments = array_map(
            fn (array $row) => [Carbon::parse($row['due'])->toDateString(), (int) $row['amount'], (string) $row['status']],
            $detail['instalments']
        );

        return [$this->vehicleFor($detail['vehicle']), (int) $detail['total'], (int) $detail['deposit'], (int) $detail['balance'], $instalments];
    }

    /**
     * Six equal monthly instalments around the plan's next due date.
     *
     * @param  array<string, mixed>  $plan
     * @return array{0: ?int, 1: int, 2: int, 3: int, 4: list<array{0: string, 1: int, 2: string}>}
     */
    private function generatedPlan(array $plan): array
    {
        $total = (int) $plan['total'];
        $deposit = (int) $plan['deposit'];
        $balance = (int) $plan['balance'];
        $financed = $total - $deposit;
        $instalments = [];

        if ($financed > 0) {
            $per = (int) round($financed / 6);
            $paidCount = max(0, min(6, (int) round(($financed - $balance) / max(1, $per))));
            $firstUnpaid = $plan['next_due'] === 'Settled' ? now() : Carbon::parse($plan['next_due']);

            for ($n = 0; $n < 6; $n++) {
                $status = match (true) {
                    $n < $paidCount => InstalmentStatus::Paid,
                    $n === $paidCount => $plan['status'] === 'Overdue' ? InstalmentStatus::Overdue : InstalmentStatus::Due,
                    default => InstalmentStatus::Upcoming,
                };
                $instalments[] = [$firstUnpaid->copy()->addMonthsNoOverflow($n - $paidCount)->toDateString(), $per, $status->value];
            }
        }

        return [$this->vehicleFor($plan['vehicle']), $total, $deposit, $balance, $instalments];
    }

    private function seedPromoCodes(): void
    {
        foreach ([
            ['JAYYID10', 'Jayyid welcome offer', 10],
            ['ETHICAL15', 'Ethical pricing offer', 15],
            ['WELCOME5', 'First reservation offer', 5],
        ] as [$code, $title, $percent]) {
            PromoCode::query()->create([
                'code' => $code,
                'title' => $title,
                'discount_type' => 'percentage',
                'discount_value' => $percent,
            ]);
        }
    }

    /**
     * Match a (possibly shortened) vehicle title to a catalogue id.
     */
    private function vehicleFor(string $title): ?int
    {
        if (isset($this->vehicleTitles[$title])) {
            return $this->vehicleTitles[$title];
        }

        foreach ($this->vehicleTitles as $full => $id) {
            if ($title !== '' && str_starts_with($full, $title)) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @return array<mixed>
     */
    private function read(string $file): array
    {
        $data = json_decode((string) file_get_contents(database_path('seed-data/'.$file)), true);
        if (! is_array($data)) {
            throw new RuntimeException("Seed file {$file} is missing or invalid.");
        }

        return $data;
    }
}
