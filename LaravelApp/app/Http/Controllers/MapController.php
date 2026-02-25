<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\ChargingStationController;

class MapController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->query('start');
        $end   = $request->query('end');

        $startCoords = null;
        $endCoords = null;
        $routeCoordinates = null;
        $routeDistanceKm = null;
        $stationsOnRoute = [];
        $totalTravelTimeMin = null;

        // 1️⃣ Récupération de la liste des véhicules
        $vehicleController = app(VehicleController::class);
        $vehicles = $vehicleController->fetchVehicleList();
        $selectedVehicleId = $request->query('vehicle_id') ?? null;

        if ($start && $end) {
            // 2️⃣ Géocodage
            $startCoords = $this->geocode($start);
            $endCoords = $this->geocode($end);

            if ($startCoords && $endCoords) {
                // 3️⃣ Trajet OSRM (retourne coordonnées + distance)
                $routeData = $this->getRoute($startCoords, $endCoords);

                if ($routeData) {
                    $routeCoordinates = $routeData['coordinates'];
                    $routeDistanceKm = $routeData['distance_km'];

                    // 4️⃣ Calcul des bornes nécessaires
                    if ($selectedVehicleId) {
                        $stationsOnRoute = $this->getRequiredChargingStops($routeCoordinates, $selectedVehicleId, $routeDistanceKm);
                    }
                    // 5️⃣ Calcul du temps total via SOAP
                    if ($selectedVehicleId) {
                        $totalTravelTimeMin = $this->calculateTravelTimeSOAP($selectedVehicleId, $routeDistanceKm);
                    }
                }
            }
        }

        return view('map', [
            'start' => $start,
            'end' => $end,
            'startCoords' => $startCoords,
            'endCoords' => $endCoords,
            'routeCoordinates' => $routeCoordinates,
            'routeDistanceKm' => $routeDistanceKm,
            'stationsOnRoute' => $stationsOnRoute,
            'vehicles' => $vehicles,
            'selectedVehicleId' => $selectedVehicleId,
            'totalTravelTimeMin' => $totalTravelTimeMin,
            'selectedVehicle' => $selectedVehicleId ? app(VehicleController::class)->fetchVehicleDetails($selectedVehicleId) : null,
        ]);
    }

    private function geocode(string $address): ?array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Laravel-EV-App/1.0'
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
        ]);

        if ($response->successful() && count($response->json()) > 0) {
            return [
                'lat' => (float) $response->json()[0]['lat'],
                'lon' => (float) $response->json()[0]['lon'],
            ];
        }

        return null;
    }

    private function getRoute(array $startCoords, array $endCoords): ?array
    {
        $url = "http://router.project-osrm.org/route/v1/driving/"
            . "{$startCoords['lon']},{$startCoords['lat']};"
            . "{$endCoords['lon']},{$endCoords['lat']}"
            . "?overview=full&geometries=geojson";

        $response = Http::get($url);

        if ($response->successful() && isset($response->json()['routes'][0])) {
            $route = $response->json()['routes'][0];
            return [
                'coordinates' => $route['geometry']['coordinates'],
                'distance_km' => $route['distance'] / 1000, // OSRM retourne en mètres 
            ];
        }

        return null;
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $R = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }

    private function getRequiredChargingStops(array $routeCoordinates, string $vehicleId, float $totalDistanceKm): array
    {
        $vehicleController = app(VehicleController::class);
        $vehicle = $vehicleController->fetchVehicleDetails($vehicleId);

        if (empty($vehicle) || empty($routeCoordinates)) {
            return [];
        }

        $rangeKm = $vehicle['range']['chargetrip_range']['best'] ?? 250;
        $initialSocPercent = 80;
        $autonomyRemaining = $rangeKm * ($initialSocPercent / 100);

        $stations = [];
        $chargingController = app(ChargingStationController::class);

        // Échantillonnage intelligent : max 50 points pour optimiser
        $sampleRate = max(1, (int) floor(count($routeCoordinates) / 50));
        $sampledPoints = array_filter($routeCoordinates, fn($_, $i) => $i % $sampleRate === 0, ARRAY_FILTER_USE_BOTH);

        $distanceCovered = 0;
        $prevPoint = null;
        $searchCount = 0; // Compteur de recherches effectuées

        foreach ($sampledPoints as $point) {
            if ($prevPoint) {
                $distanceCovered += $this->haversineDistance(
                    $prevPoint[1], $prevPoint[0],
                    $point[1], $point[0]
                );
            }

            // Chercher une borne dès qu'on atteint l'autonomie restante
            if ($distanceCovered >= $autonomyRemaining) {
                $searchCount++;
                $lat = (float) $point[1];
                $lon = (float) $point[0];
                
                $nearby = $chargingController->getNearbyStations($lat, $lon, '5000'); // 10000m = 10km
                
                if (!empty($nearby)) {
                    $stations[] = $nearby[0];
                    $distanceCovered = 0; // Reset après recharge
                    $autonomyRemaining = $rangeKm; // Recharge complète
                } else {
                    // Même si pas de borne trouvée, on continue quand même
                    $distanceCovered = 0;
                }
            }

            $prevPoint = $point;
        }

        // Stocker le compteur en session pour debug
        session(['debug_search_count' => $searchCount]);
        session(['debug_sampled_points' => count($sampledPoints)]);

        return collect($stations)->unique('id_station')->values()->all();
    }

    private function calculateTravelTimeSOAP(string $vehicleId, float $distanceKm): ?float
    {
        $vehicleController = app(VehicleController::class);
        $vehicleDetails = $vehicleController->fetchVehicleDetails($vehicleId);

        if (empty($vehicleDetails)) return null;

        $batteryCapacity = $vehicleDetails['battery']['usable_kwh'] ?? 50;
        $rangeKm = $vehicleDetails['range']['chargetrip_range']['best'] ?? 250;
        $consumptionKwhPer100km = ($batteryCapacity / $rangeKm) * 100;
        $initialSocPercent = 80;
        $chargingTimeMin = 30;

        try {
            $client = new \SoapClient('http://localhost:8000/?wsdl');

            $params = [
                'distance_km' => $distanceKm,
                'initial_soc_percent' => $initialSocPercent,
                'battery_capacity_kwh' => $batteryCapacity,
                'consumption_kwh_per_100km' => $consumptionKwhPer100km,
                'charging_time_min' => $chargingTimeMin,
            ];

            $response = $client->__soapCall('calculate_travel_time', [$params]);
            return $response->calculate_travel_timeResult ?? null;

        } catch (\Exception $e) {
            logger()->error('Erreur SOAP: '.$e->getMessage());
            return null;
        }
    }
}
