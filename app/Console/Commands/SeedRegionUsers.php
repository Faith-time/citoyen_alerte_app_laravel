<?php

namespace App\Console\Commands;

use App\Services\RegionUserService;
use Illuminate\Console\Command;

class SeedRegionUsers extends Command
{
    protected $signature   = 'mairie:seed';
    protected $description = 'Crée les comptes mairie pour toutes les régions du Sénégal';

    // 14 régions officielles du Sénégal
    private const REGIONS = [
        'Dakar', 'Thiès', 'Diourbel', 'Saint-Louis', 'Louga',
        'Fatick', 'Kaolack', 'Kaffrine', 'Kédougou', 'Kolda',
        'Matam', 'Sédhiou', 'Tambacounda', 'Ziguinchor',
    ];

    public function handle(): void
    {
        foreach (self::REGIONS as $region) {
            $user = RegionUserService::findOrCreateForRegion($region);
            $this->info("✅  {$user->name} — {$user->email}");
        }

        $this->info('');
        $this->warn('⚠️  Pensez à définir les mots de passe via php artisan tinker ou un seeder dédié.');
    }
}
