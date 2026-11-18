<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class ProductionAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('production')) {
            throw new RuntimeException('Production administrator bootstrap is available only in production.');
        }

        if (User::query()->where('role', 'admin')->exists()) {
            return;
        }

        $name = trim((string) config('app.bootstrap_admin.name'));
        $email = strtolower(trim((string) config('app.bootstrap_admin.email')));
        $password = (string) config('app.bootstrap_admin.password');

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 16) {
            throw new RuntimeException(
                'Production has no administrator. Set ADMIN_NAME, ADMIN_EMAIL and a unique ADMIN_PASSWORD of at least 16 characters, then run the production admin bootstrap.',
            );
        }

        if (User::query()->where('email', $email)->exists()) {
            throw new RuntimeException(
                'The configured production administrator email already belongs to a non-admin account. Promote that account through the authorized admin workflow instead.',
            );
        }

        $admin = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
        ]);

        $admin->forceFill(['email_verified_at' => now()])->save();
    }
}
