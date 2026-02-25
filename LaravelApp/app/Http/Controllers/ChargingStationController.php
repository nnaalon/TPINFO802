<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class ChargingStationController extends Controller
{
    /**
     * Récupère les bornes autour d'un point donné
     *
     * @param float $latitude
     * @param float $longitude
     * @param float $radiusMeters Rayon en mètres (défaut: 10000)
     * @return array
     */
    public function getNearbyStations(float $latitude, float $longitude, float $radiusMeters = 10000): array
{
    $url = 'https://odre.opendatasoft.com/api/explore/v2.1/catalog/datasets/bornes-irve/records';

    // Conversion du rayon en degrés approximatifs pour la bounding box
    $delta = $radiusMeters / 111000; // 1° ≈ 111 km
    $latMin = $latitude - $delta;
    $latMax = $latitude + $delta;
    $lonMin = $longitude - $delta;
    $lonMax = $longitude + $delta;

    // Clause WHERE pour la bounding box
    $where = "consolidated_latitude>{$latMin} AND consolidated_latitude<{$latMax} "
           . "AND consolidated_longitude>{$lonMin} AND consolidated_longitude<{$lonMax}";

    $params = [
        'limit' => 50,
        'where' => $where,
    ];

    $response = Http::timeout(5)->get($url, $params);

    $records = $response->json('results', []);

    // Transformer les résultats pour la vue
    $stations = collect($records)
        ->map(fn($rec) => [
            'id'        => $rec['id_station'] ?? $rec['id_pdc_itinerance'] ?? uniqid(),
            'nom'       => $rec['nom_station'] ?? $rec['nom_amenageur'] ?? 'Station inconnue',
            'adresse'   => $rec['adresse_station'] ?? 'Adresse inconnue',
            'puissance' => $rec['puissance_nominale'] ?? 'N/A',
            'lat'       => (float) ($rec['consolidated_latitude'] ?? $rec['coordonneesxy']['lat'] ?? 0),
            'lon'       => (float) ($rec['consolidated_longitude'] ?? $rec['coordonneesxy']['lon'] ?? 0),
        ])
        ->filter(fn($s) => $s['lat'] !== 0.0 && $s['lon'] !== 0.0)
        ->unique('id')
        ->values()
        ->all();

    return $stations;
}
}