<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DevelopmentUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Development user fixtures are disabled in production.');
        }

        $this->seedUser('Admin', 'admin@engleza.wiki', 'admin');
        $this->seedUser('Editor', 'editor@engleza.wiki', 'editor');
        $this->seedUser('Teacher', 'teacher@engleza.wiki', 'teacher');
        $this->seedUser('Learner', 'learner@engleza.wiki', 'learner');
    }

    private function seedUser(string $name, string $email, string $role): void
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => 'password', 'role' => $role],
        );

        $user->forceFill(['email_verified_at' => now()])->save();
    }
}
