<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegionUserService
{
    /**
     * Retourne l'email canonique d'une région.
     * Ex: "Dakar" → "mairie.dakar@citoyen-alerte.sn"
     */
    public static function regionEmail(string $regionName): string
    {
        $slug = Str::slug(strtolower($regionName));
        return "mairie.{$slug}@citoyen-alerte.sn";
    }

    /**
     * Trouve ou crée l'utilisateur "mairie" pour une région donnée.
     * Idempotent : safe à appeler plusieurs fois.
     */

    public static function findOrCreateForRegion(string $regionName): User
    {
        $email = self::regionEmail($regionName);

        return User::firstOrCreate(
            ['email' => $email],
            [
                'name'        => "Mairie de {$regionName}",
                'phone'       => null, // ← ajoute cette ligne
                'password'    => Hash::make(Str::random(32)),
                'role'        => 'authority',
                'region_name' => $regionName,
                'is_active'   => true,
                'is_verified' => true,
            ]
        );
    }}
