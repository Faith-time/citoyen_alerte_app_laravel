<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AgentAdminSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'       => 'Agent Diallo',
                'email'      => 'agent@citoyen-alerte.sn',
                'phone'      => '771234567',
                'password'   => Hash::make('password123'),
                'role'       => 'agent',
                'avatar'     => null,
                'is_verified' => 1,
                'is_active'  => 1,
                'is_banned'  => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Admin Système',
                'email'      => 'admin@citoyen-alerte.sn',
                'phone'      => '779876543',
                'password'   => Hash::make('password123'),
                'role'       => 'admin',
                'avatar'     => null,
                'is_verified' => 1,
                'is_active'  => 1,
                'is_banned'  => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            // Évite les doublons si le seeder est relancé
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                $user
            );
        }

        $this->command->info('Agent et Admin créés avec succès.');
        $this->command->table(
            ['Nom', 'Email', 'Rôle', 'Mot de passe'],
            [
                ['Agent Diallo',  'agent@citoyen-alerte.sn', 'agent', 'password123'],
                ['Admin Système', 'admin@citoyen-alerte.sn', 'admin', 'password123'],
            ]
        );
    }
}
