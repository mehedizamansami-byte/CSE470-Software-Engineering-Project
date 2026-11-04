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
            ['Demo Seller', 'seller@brandbondhu.test', User::ROLE_SELLER],
            ['Demo Collaborator', 'collaborator@brandbondhu.test', User::ROLE_COLLABORATOR],
            ['Demo Designer', 'designer@brandbondhu.test', User::ROLE_DESIGNER],
            ['Demo Admin', 'admin@brandbondhu.test', User::ROLE_ADMIN],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password123', 'role' => $role, 'is_active' => true],
            );
        }
    }
}
