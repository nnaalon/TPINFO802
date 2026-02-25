<!DOCTYPE html>
<html>
<head>
    <title>Trajet EV</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <style>
        html, body { height:100%; margin:0; font-family:Arial, sans-serif; }
        #container { display:flex; height:100vh; }
        #sidebar { width:300px; background:#f8f9fa; padding:20px; box-shadow:2px 0 5px rgba(0,0,0,0.1); overflow-y:auto; }
        #map { flex:1; }
        h2 { font-size:18px; margin-top:0; }
        form { display:flex; flex-direction:column; gap:10px; margin-bottom:20px; }
        input[type=text], select { padding:8px; border-radius:4px; border:1px solid #ccc; }
        button { padding:8px 12px; border-radius:4px; border:none; background-color:#007bff; color:white; cursor:pointer; }
        button:hover { background-color:#0056b3; }
        .station-list { margin-top:20px; }
        .station-item { padding:10px 0; border-bottom:1px solid #ddd; font-size:14px; }
        .station-item strong { display:block; }
        .travel-time { margin:10px 0; padding:8px; background:#e0f0ff; border-radius:4px; }
    </style>
</head>
<body>

<div id="container">
    <div id="sidebar">
        <h2>Itinéraire</h2>

        <form method="GET" action="{{ route('map.index') }}">
            <input type="text" name="start" placeholder="Ville de départ" value="{{ old('start', $start) }}">
            <input type="text" name="end" placeholder="Ville d'arrivée" value="{{ old('end', $end) }}">

            <select name="vehicle_id">
                <option value="">-- Choisir un véhicule --</option>
                @foreach($vehicles as $vehicle)
                    <option value="{{ $vehicle['id'] }}"
                        {{ (old('vehicle_id', $selectedVehicleId ?? '') == $vehicle['id']) ? 'selected' : '' }}>
                        {{ $vehicle['naming']['make'] }} {{ $vehicle['naming']['model'] }}
                        ({{ $vehicle['naming']['chargetrip_version'] }})
                    </option>
                @endforeach
            </select>

            @if(!empty($totalTravelTimeMin))
                <div class="travel-time">
                    <h3>Informations trajet</h3>
                    @if(!empty($routeDistanceKm))
                        <p><strong>Distance :</strong> {{ round($routeDistanceKm) }} km</p>
                    @endif
                    <p><strong>Temps estimé :</strong><br>
                        {{ round($totalTravelTimeMin) }} minutes<br>
                        (~{{ round($totalTravelTimeMin / 60, 1) }} heures)
                    </p>
                </div>
            @endif

            <button type="submit">Afficher le trajet</button>
        </form>

        @if(!empty($stationsOnRoute))
            <div class="station-list">
                <h2>Bornes nécessaires ({{ count($stationsOnRoute) }})</h2>

                @foreach($stationsOnRoute as $station)
                    <div class="station-item">
                        <strong>{{ $station['nom'] ?? 'Station inconnue' }}</strong>
                        {{ $station['adresse'] ?? '' }}<br>
                        Puissance : {{ $station['puissance'] ?? 'N/A' }} kW
                    </div>
                @endforeach

            </div>
        @endif
    </div>

    <div id="map"></div>
</div>

<script>
const map = L.map('map').setView([46.5, 2.5], 6);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution:'Map data © OpenStreetMap contributors'
}).addTo(map);

const startIcon = L.icon({
    iconUrl:'https://cdn-icons-png.flaticon.com/512/64/64113.png',
    iconSize:[32,32]
});

const endIcon = L.icon({
    iconUrl:'https://cdn-icons-png.flaticon.com/512/64/64113.png',
    iconSize:[32,32]
});

const chargingIcon = L.icon({
    iconUrl:'https://cdn-icons-png.flaticon.com/512/3448/3448447.png',
    iconSize:[28,28]
});

@if($startCoords)
    L.marker([{{ $startCoords['lat'] }}, {{ $startCoords['lon'] }}], { icon: startIcon })
        .addTo(map)
        .bindPopup('Départ : {{ $start }}');
@endif

@if($endCoords)
    L.marker([{{ $endCoords['lat'] }}, {{ $endCoords['lon'] }}], { icon: endIcon })
        .addTo(map)
        .bindPopup('Arrivée : {{ $end }}');
@endif

@if($routeCoordinates)
    const route = [
        @foreach($routeCoordinates as $coord)
            [{{ $coord[1] }}, {{ $coord[0] }}],
        @endforeach
    ];
    const polyline = L.polyline(route, { color:'blue', weight:5, opacity:0.7 }).addTo(map);
    map.fitBounds(polyline.getBounds());
@endif

@if(!empty($stationsOnRoute))
    console.log('🔌 Stations:', @json($stationsOnRoute));

    @foreach($stationsOnRoute as $station)

        @if(!empty($station['lat']) && !empty($station['lon']))
            L.marker(
                [{{ $station['lat'] }}, {{ $station['lon'] }}],
                { icon: chargingIcon }
            )
            .addTo(map)
            .bindPopup(`<strong>{{ addslashes($station['nom'] ?? 'Station inconnue') }}</strong><br>{{ addslashes($station['adresse'] ?? '') }}<br><small>Puissance: {{ $station['puissance'] ?? 'N/A' }} kW</small>`);
        @else
            console.log('❌ Coordonnées manquantes pour:', @json($station));
        @endif

    @endforeach
@endif

</script>

</body>
</html>