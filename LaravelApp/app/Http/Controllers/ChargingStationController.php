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
     * @param string $radius
     * @return array
     */
    public function getNearbyStations(float $latitude, float $longitude, string $radius = '1km'): array
    {
        $response = Http::get(
            'https://odre.opendatasoft.com/api/explore/v2.1/catalog/datasets/bornes-irve/records',
            [
                'limit' => 50,
                'where' => "within_distance(geo_point_borne, GEOM'POINT($longitude $latitude)', $radius)"
            ]
        );

        $records = $response->json()['results'] ?? [];

        return collect($records)
            ->unique('id_station')
            ->values()
            ->all();
    }
}