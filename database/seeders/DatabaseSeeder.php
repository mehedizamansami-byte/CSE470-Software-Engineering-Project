<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Demo accounts, one per role. Password for all: password123 */
    public function run(): void
    {
        $accounts = [
            ['Demo Seller', 'seller@brandbondhu.test', User::ROLE_SELLER, '01711000001'],
            ['Demo Collaborator', 'collaborator@brandbondhu.test', User::ROLE_COLLABORATOR, '01811000002'],
            ['Demo Designer', 'designer@brandbondhu.test', User::ROLE_DESIGNER, '01911000003'],
            ['Demo Admin', 'admin@brandbondhu.test', User::ROLE_ADMIN, null],
        ];

        foreach ($accounts as [$name, $email, $role, $phone]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password123', 'role' => $role, 'is_active' => true, 'phone' => $phone],
            );

            // Demo accounts are already verified, so no OTP is needed to log in.
            $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now(), 'deactivated_at' => null])->save();
        }

        $this->call(DemoDataSeeder::class);
    }
}
