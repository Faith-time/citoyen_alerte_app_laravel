# Alert Citoyen – API (backend)

API REST de l'application mobile Alert Citoyen (signalement citoyen au Sénégal), candidature Banlieue Smart Genius 2026.

## Rôle de l'API
- Authentification par e-mail ou Google (JWT)
- Gestion des signalements : catégories, photos, gravité, localisation, statuts
- Routage vers la mairie puis l'autorité compétente, affectation aux agents
- Preuve de résolution en photo (avant / après)
- Statistiques pour les mairies

## Technologies
Laravel · MySQL · JWT

## Installation
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```
Renseigner dans `.env` les accès à la base MySQL et les clés JWT / Google.

## Lancer le serveur
```bash
php artisan serve --host=0.0.0.0 --port=8000
```
Le serveur est alors accessible depuis le téléphone, à condition que le PC et le téléphone soient sur le même réseau Wi-Fi. L'API répond à l'adresse `http://ADRESSE_IP_DU_PC:8000` (adresse obtenue avec `ipconfig`). C'est cette adresse qu'il faut renseigner dans l'application Flutter.

## Application mobile
Dépôt Flutter : https://github.com/Faith-time/citoyen_alerte_app_flutter

## Statut
Prototype fonctionnel, pas encore déployé.

## Auteure
Adji Fatime Bâ · Keur Massar, Dakar
