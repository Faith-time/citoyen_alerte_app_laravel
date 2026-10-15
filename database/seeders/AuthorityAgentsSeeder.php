<?php

namespace Database\Seeders;

use App\Models\Authority;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthorityAgentsSeeder extends Seeder
{
    /**
     * Mot de passe commun à tous les agents créés par ce seeder.
     */
    protected const DEFAULT_PASSWORD = 'password123';

    /**
     * Nombre d'agents à créer pour chaque autorité.
     */
    protected const AGENTS_PER_AUTHORITY = 10;

    public function run(): void
    {
        $authorities = Authority::all();

        if ($authorities->isEmpty()) {
            $this->command->warn("Aucune autorité trouvée. Lancez d'abord le seeder des autorités.");
            return;
        }

        $hashedPassword = Hash::make(self::DEFAULT_PASSWORD);
        $created = 0;

        foreach ($authorities as $authority) {
            $this->command->info("Création de " . self::AGENTS_PER_AUTHORITY . " agent(s) pour : {$authority->name}");

            for ($i = 1; $i <= self::AGENTS_PER_AUTHORITY; $i++) {
                $name = fake('fr_FR')->name();
                $email = $this->generateUniqueEmail($authority->name, $i);
                $phone = $this->generateUniquePhone();

                User::create([
                    'name'         => $name,
                    'email'        => $email,
                    'phone'        => $phone,
                    'password'     => $hashedPassword,
                    'role'         => 'agent',
                    'authority_id' => $authority->id,
                    'is_active'    => true,
                    'is_verified'  => true,
                ]);

                $created++;
            }
        }

        $this->command->info("Terminé : $created agent(s) créé(s). Mot de passe pour tous : " . self::DEFAULT_PASSWORD);
    }

    /**
     * Construit un email unique à partir du nom de l'autorité, avec un
     * suffixe numérique en cas de relance du seeder ou de collision.
     */
    protected function generateUniqueEmail(string $authorityName, int $index): string
    {
        $slug = Str::slug($authorityName);
        $base = "{$slug}.agent{$index}";
        $email = "{$base}@citoyen-alerte.sn";

        $suffix = 1;
        while (User::where('email', $email)->exists()) {
            $email = "{$base}.{$suffix}@citoyen-alerte.sn";
            $suffix++;
        }

        return $email;
    }

    /**
     * Génère un numéro de téléphone sénégalais unique (format 7XXXXXXXX).
     */
    protected function generateUniquePhone(): string
    {
        do {
            $prefix = fake()->randomElement(['70', '76', '77', '78']);
            $phone = $prefix . fake()->numerify('#######');
        } while (User::where('phone', $phone)->exists());

        return $phone;
    }
}
