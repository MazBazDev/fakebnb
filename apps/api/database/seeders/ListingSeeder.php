<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        $listings = [
            ['city' => 'Paris', 'address' => '10 Rue de Rivoli', 'full_address' => '10 Rue de Rivoli, 75001 Paris, France', 'lat' => 48.8566, 'lng' => 2.3522],
            ['city' => 'Lyon', 'address' => '5 Place Bellecour', 'full_address' => '5 Place Bellecour, 69002 Lyon, France', 'lat' => 45.7640, 'lng' => 4.8357],
            ['city' => 'Marseille', 'address' => '20 Quai du Port', 'full_address' => '20 Quai du Port, 13002 Marseille, France', 'lat' => 43.2965, 'lng' => 5.3698],
            ['city' => 'Bordeaux', 'address' => '12 Quai des Chartrons', 'full_address' => '12 Quai des Chartrons, 33000 Bordeaux, France', 'lat' => 44.8378, 'lng' => -0.5792],
            ['city' => 'Nice', 'address' => '4 Promenade des Anglais', 'full_address' => '4 Promenade des Anglais, 06000 Nice, France', 'lat' => 43.7102, 'lng' => 7.2620],
            ['city' => 'Toulouse', 'address' => '8 Place du Capitole', 'full_address' => '8 Place du Capitole, 31000 Toulouse, France', 'lat' => 43.6045, 'lng' => 1.4442],
            ['city' => 'Nantes', 'address' => '3 Rue de Strasbourg', 'full_address' => '3 Rue de Strasbourg, 44000 Nantes, France', 'lat' => 47.2184, 'lng' => -1.5536],
            ['city' => 'Lille', 'address' => '15 Rue Nationale', 'full_address' => '15 Rue Nationale, 59000 Lille, France', 'lat' => 50.6292, 'lng' => 3.0573],
            ['city' => 'Strasbourg', 'address' => '6 Place Kléber', 'full_address' => '6 Place Kléber, 67000 Strasbourg, France', 'lat' => 48.5734, 'lng' => 7.7521],
            ['city' => 'Montpellier', 'address' => '14 Place de la Comédie', 'full_address' => '14 Place de la Comédie, 34000 Montpellier, France', 'lat' => 43.6108, 'lng' => 3.8767],
            ['city' => 'Rennes', 'address' => '9 Place de la Mairie', 'full_address' => '9 Place de la Mairie, 35000 Rennes, France', 'lat' => 48.1173, 'lng' => -1.6778],
            ['city' => 'Annecy', 'address' => '2 Rue Sainte-Claire', 'full_address' => '2 Rue Sainte-Claire, 74000 Annecy, France', 'lat' => 45.8992, 'lng' => 6.1294],
            ['city' => 'Biarritz', 'address' => '18 Avenue de Verdun', 'full_address' => '18 Avenue de Verdun, 64200 Biarritz, France', 'lat' => 43.4832, 'lng' => -1.5586],
            ['city' => 'Dijon', 'address' => '7 Place de la Libération', 'full_address' => '7 Place de la Libération, 21000 Dijon, France', 'lat' => 47.3220, 'lng' => 5.0415],
            ['city' => 'Grenoble', 'address' => '11 Rue Félix Poulat', 'full_address' => '11 Rue Félix Poulat, 38000 Grenoble, France', 'lat' => 45.1885, 'lng' => 5.7245],
            ['city' => 'La Rochelle', 'address' => '5 Quai Duperré', 'full_address' => '5 Quai Duperré, 17000 La Rochelle, France', 'lat' => 46.1603, 'lng' => -1.1511],
        ];

        $titles = [
            'Loft lumineux',
            'Studio cosy',
            'Maison de ville',
            'Appartement design',
            'Duplex familial',
            'Terrasse panoramique',
            'Cocon central',
            'Suite moderne',
        ];
        $descriptions = [
            'Un logement confortable, idéal pour un séjour détente.',
            'Espace optimisé avec tout le nécessaire pour un court séjour.',
            'Ambiance chaleureuse, proche des commerces et transports.',
            'Décoration soignée, lumineux et calme.',
            'Idéal pour familles ou groupes d’amis.',
            'Vue dégagée, quartier vivant et accessible.',
        ];
        $rules = [
            'Non fumeur',
            'Animaux acceptés',
            'Pas de fêtes',
            'Arrivée autonome',
        ];
        $amenitiesPool = [
            'wifi',
            'kitchen',
            'parking',
            'washer',
            'tv',
            'air_conditioning',
            'heating',
            'workspace',
            'pool',
            'hot_tub',
        ];
        $capacities = [2, 3, 4, 5, 6, 7, 8];
        $prices = [55, 70, 85, 95, 110, 130, 150, 180];

        foreach ($listings as $index => $data) {
            $host = User::where('name', 'TestHost')->first();
            if (! $host) {
                $host = User::firstOrCreate(
                    ['email' => 'th@t.fr'],
                    ['name' => 'TestHost', 'password' => bcrypt('password')]
                );
            }
            Listing::create([
                'host_user_id' => $host->id,
                'title' => $titles[$index % count($titles)],
                'description' => $descriptions[$index % count($descriptions)],
                'city' => $data['city'],
                'address' => $data['address'],
                'full_address' => $data['full_address'],
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'guest_capacity' => $capacities[$index % count($capacities)],
                'price_per_night' => $prices[$index % count($prices)],
                'rules' => $rules[$index % count($rules)],
                'amenities' => array_slice(
                    $amenitiesPool,
                    0,
                    3 + ($index % 4)
                ),
            ]);
        }

        $allListings = Listing::query()->get();
        $palette = ['#0f172a', '#1e293b', '#475569', '#0f766e', '#16a34a', '#b45309'];

        foreach ($allListings as $listing) {
            for ($i = 1; $i <= 3; $i++) {
                $color = $palette[($listing->id + $i) % count($palette)];
                $svg = $this->buildPlaceholderSvg($listing->title, $color);
                $path = "listings/{$listing->id}/seed-{$i}.svg";

                Storage::disk('public')->put($path, $svg);

                ListingImage::create([
                    'listing_id' => $listing->id,
                    'path' => $path,
                    'position' => $i,
                ]);
            }
        }
    }

    private function buildPlaceholderSvg(string $title, string $color): string
    {
        $safeTitle = htmlspecialchars(mb_strimwidth($title, 0, 32, '...'), ENT_QUOTES);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800">
  <rect width="1200" height="800" fill="{$color}"/>
  <rect x="80" y="560" width="1040" height="140" rx="32" fill="#ffffff" fill-opacity="0.9"/>
  <text x="120" y="640" font-family="Arial, sans-serif" font-size="48" fill="#0f172a">
    {$safeTitle}
  </text>
</svg>
SVG;
    }
}
