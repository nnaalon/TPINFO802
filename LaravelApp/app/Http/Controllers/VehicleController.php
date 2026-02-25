<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VehicleController extends Controller
{
    // Récupère la liste des véhicules pour la vue
    public function index()
    {
        $vehicles = $this->fetchVehicleList();
        $selectedVehicleId = old('vehicle_id') ?? null;

        return view('map', compact('vehicles', 'selectedVehicleId'));
    }

    // Fonction pour récupérer la liste de tous les véhicules
    public function fetchVehicleList()
    {
        $endpoint = 'https://api.chargetrip.io/graphql';

        $headers = [
            'x-client-id' => '697b84d30de8b24ced20d297',
            'x-app-id' => '697b84d30de8b24ced20d299',
            'Content-Type' => 'application/json',
        ];

        $query = <<<'GRAPHQL'
        query vehicleList($page: Int!, $size: Int!) {
            vehicleList(page: $page, size: $size) {
                id
                naming { make model chargetrip_version }
                media { image { thumbnail_url } }
            }
        }
        GRAPHQL;

        $variables = ['page' => 0, 'size' => 20];

        $response = Http::withHeaders($headers)->post($endpoint, [
            'query' => $query,
            'variables' => $variables
        ]);

        if ($response->successful()) {
            return $response->json()['data']['vehicleList'] ?? [];
        }

        return [];
    }

    // Récupère les détails d’un véhicule
    public function fetchVehicleDetails($vehicleId)
    {
        $endpoint = 'https://api.chargetrip.io/graphql';

        $headers = [
            'x-client-id' => '697b84d30de8b24ced20d297',
            'x-app-id' => '697b84d30de8b24ced20d299',
            'Content-Type' => 'application/json',
        ];

        $query = <<<'GRAPHQL'
        query vehicle($vehicleId: ID!) {
            vehicle(id: $vehicleId) {
                naming { make model chargetrip_version }
                media { image { url } brand { thumbnail_url } }
                battery { usable_kwh }
                range { best { combined } worst { combined } chargetrip_range { best worst } }
                connectors { standard }
                performance { acceleration top_speed }
            }
        }
        GRAPHQL;

        $response = Http::withHeaders($headers)->post($endpoint, [
            'query' => $query,
            'variables' => ['vehicleId' => $vehicleId],
        ]);

        return $response->json()['data']['vehicle'] ?? [];
    }
}