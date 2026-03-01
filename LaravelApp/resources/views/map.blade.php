<!DOCTYPE html>
<html>
<head>
    <title>Trajet EV</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #0b0f1a;
            --surface: #111827;
            --surface2: #1a2236;
            --border: rgba(255,255,255,0.07);
            --accent: #00e5a0;
            --accent2: #0099ff;
            --text: #e8edf5;
            --muted: #6b7897;
            --danger: #ff4d6d;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        #container {
            display: flex;
            height: 100vh;
        }

        /* ── SIDEBAR ── */
        #sidebar {
            width: 340px;
            flex-shrink: 0;
            background: var(--surface);
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border);
            overflow: hidden;
        }

        .sidebar-header {
            padding: 28px 24px 20px;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
            position: relative;
        }

        .sidebar-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--accent2));
        }

        .logo-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 4px;
        }

        .logo-icon {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
        }

        .logo-text {
            font-family: 'Syne', sans-serif;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .logo-text span { color: var(--accent); }

        .subtitle {
            font-size: 12px;
            color: var(--muted);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .sidebar-body {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            scrollbar-width: thin;
            scrollbar-color: var(--surface2) transparent;
        }

        /* ── FORM ── */
        .form-group {
            margin-bottom: 14px;
        }

        .form-label {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
            display: block;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 12px; top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            color: var(--muted);
            pointer-events: none;
        }

        input[type=text], select {
            width: 100%;
            padding: 11px 12px 11px 36px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
            -webkit-appearance: none;
        }

        input[type=text]:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(0, 229, 160, 0.12);
        }

        input[type=text]::placeholder { color: var(--muted); }

        select option { background: var(--surface2); }

        /* Route connector */
        .route-connector {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 4px 0 4px 0;
        }

        .connector-line {
            width: 2px;
            height: 20px;
            background: linear-gradient(to bottom, var(--accent), var(--accent2));
            margin-left: 23px;
            border-radius: 2px;
            opacity: 0.5;
        }

        .divider {
            height: 1px;
            background: var(--border);
            margin: 20px 0;
        }

        /* Submit button */
        button[type=submit] {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--accent), #00c87a);
            color: #0b0f1a;
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.03em;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 6px;
            transition: opacity 0.2s, transform 0.15s;
        }

        button[type=submit]:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        button[type=submit]:active { transform: translateY(0); }

        /* Travel info card */
        .info-card {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            margin: 20px 0 0;
        }

        .info-card-title {
            font-family: 'Syne', sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 14px;
        }

        .info-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .stat {
            background: var(--surface);
            border-radius: 8px;
            padding: 12px;
        }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--accent);
            line-height: 1;
        }

        .stat-unit {
            font-size: 12px;
            color: var(--muted);
            margin-top: 4px;
        }

        /* Station list */
        .station-section {
            margin-top: 24px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .count-badge {
            background: rgba(0,229,160,0.15);
            color: var(--accent);
            font-family: 'Syne', sans-serif;
            font-size: 12px;
            font-weight: 700;
            padding: 2px 9px;
            border-radius: 20px;
        }

        .station-item {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 14px;
            margin-bottom: 8px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            transition: border-color 0.2s;
        }

        .station-item:hover {
            border-color: rgba(0,229,160,0.3);
        }

        .station-dot {
            width: 32px; height: 32px;
            background: rgba(0,153,255,0.15);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .station-info { flex: 1; min-width: 0; }

        .station-name {
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 3px;
        }

        .station-addr {
            font-size: 11px;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .station-power {
            font-family: 'Syne', sans-serif;
            font-size: 12px;
            font-weight: 700;
            color: var(--accent2);
            white-space: nowrap;
            padding-top: 2px;
        }

        /* ── MAP ── */
        #map {
            flex: 1;
            filter: saturate(0.8) brightness(0.85);
        }

        /* Scrollbar */
        .sidebar-body::-webkit-scrollbar { width: 4px; }
        .sidebar-body::-webkit-scrollbar-thumb { background: var(--surface2); border-radius: 4px; }
    </style>
</head>
<body>

<div id="container">
    <div id="sidebar">
        <div class="sidebar-header">
            <div class="logo-row">
                <div class="logo-icon">⚡</div>
                <div class="logo-text">Trajet<span>EV</span></div>
            </div>
            <div class="subtitle">Planificateur de route électrique</div>
        </div>

        <div class="sidebar-body">
            <form method="GET" action="{{ route('map.index') }}">

                <div class="form-group">
                    <label class="form-label">Départ</label>
                    <div class="input-wrapper">
                        <span class="input-icon">📍</span>
                        <input type="text" name="start" placeholder="Ville de départ" value="{{ old('start', $start) }}">
                    </div>
                </div>

                <div class="connector-line"></div>

                <div class="form-group">
                    <label class="form-label">Arrivée</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🏁</span>
                        <input type="text" name="end" placeholder="Ville d'arrivée" value="{{ old('end', $end) }}">
                    </div>
                </div>

                <div class="divider"></div>

                <div class="form-group">
                    <label class="form-label">Véhicule</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🚗</span>
                        <select name="vehicle_id">
                            <option value="">— Choisir un véhicule —</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle['id'] }}"
                                    {{ (old('vehicle_id', $selectedVehicleId ?? '') == $vehicle['id']) ? 'selected' : '' }}>
                                    {{ $vehicle['naming']['make'] }} {{ $vehicle['naming']['model'] }}
                                    ({{ $vehicle['naming']['chargetrip_version'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button type="submit">Calculer l'itinéraire →</button>

                @if(!empty($totalTravelTimeMin))
                    <div class="info-card">
                        <div class="info-card-title">Résumé du trajet</div>
                        <div class="info-stats">
                            @if(!empty($routeDistanceKm))
                                <div class="stat">
                                    <div class="stat-value">{{ round($routeDistanceKm) }}</div>
                                    <div class="stat-unit">kilomètres</div>
                                </div>
                            @endif
                            <div class="stat">
                                <div class="stat-value">{{ round($totalTravelTimeMin / 60, 1) }}</div>
                                <div class="stat-unit">heures estimées</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value">{{ round($totalTravelTimeMin) }}</div>
                                <div class="stat-unit">minutes au total</div>
                            </div>
                        </div>
                    </div>
                @endif

            </form>

            @if(!empty($stationsOnRoute))
                <div class="station-section">
                    <div class="section-header">
                        <span class="section-title">Bornes de recharge</span>
                        <span class="count-badge">{{ count($stationsOnRoute) }}</span>
                    </div>

                    @foreach($stationsOnRoute as $station)
                        <div class="station-item">
                            <div class="station-dot">🔌</div>
                            <div class="station-info">
                                <div class="station-name">{{ $station['nom'] ?? 'Station inconnue' }}</div>
                                <div class="station-addr">{{ $station['adresse'] ?? '' }}</div>
                            </div>
                            <div class="station-power">{{ $station['puissance'] ?? 'N/A' }} kW</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div id="map"></div>
</div>

<script>
const map = L.map('map', { zoomControl: false }).setView([46.5, 2.5], 6);

L.control.zoom({ position: 'bottomright' }).addTo(map);

L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '© OpenStreetMap © CARTO',
    subdomains: 'abcd',
    maxZoom: 19
}).addTo(map);

const startIcon = L.divIcon({
    html: `<div style="width:36px;height:36px;background:linear-gradient(135deg,#00e5a0,#00c87a);border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,229,160,0.4)"><span style="transform:rotate(45deg);font-size:16px">📍</span></div>`,
    iconSize: [36, 36],
    iconAnchor: [18, 36],
    className: ''
});

const endIcon = L.divIcon({
    html: `<div style="width:36px;height:36px;background:linear-gradient(135deg,#0099ff,#0066cc);border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,153,255,0.4)"><span style="transform:rotate(45deg);font-size:16px">🏁</span></div>`,
    iconSize: [36, 36],
    iconAnchor: [18, 36],
    className: ''
});

const chargingIcon = L.divIcon({
    html: `<div style="width:32px;height:32px;background:rgba(17,24,39,0.95);border:2px solid #0099ff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;box-shadow:0 2px 10px rgba(0,153,255,0.4)">⚡</div>`,
    iconSize: [32, 32],
    iconAnchor: [16, 16],
    className: ''
});

@if($startCoords)
    L.marker([{{ $startCoords['lat'] }}, {{ $startCoords['lon'] }}], { icon: startIcon })
        .addTo(map)
        .bindPopup('<strong>Départ</strong><br>{{ $start }}');
@endif

@if($endCoords)
    L.marker([{{ $endCoords['lat'] }}, {{ $endCoords['lon'] }}], { icon: endIcon })
        .addTo(map)
        .bindPopup('<strong>Arrivée</strong><br>{{ $end }}');
@endif

@if($routeCoordinates)
    const route = [
        @foreach($routeCoordinates as $coord)
            [{{ $coord[1] }}, {{ $coord[0] }}],
        @endforeach
    ];
    const polyline = L.polyline(route, {
        color: '#00e5a0',
        weight: 4,
        opacity: 0.9,
        dashArray: null
    }).addTo(map);
    map.fitBounds(polyline.getBounds(), { padding: [40, 40] });
@endif

@if(!empty($stationsOnRoute))
    @foreach($stationsOnRoute as $station)
        @if(!empty($station['lat']) && !empty($station['lon']))
            L.marker(
                [{{ $station['lat'] }}, {{ $station['lon'] }}],
                { icon: chargingIcon }
            )
            .addTo(map)
            .bindPopup(`
                <div style="font-family:'DM Sans',sans-serif;min-width:160px">
                    <strong style="font-size:13px">{{ addslashes($station['nom'] ?? 'Station') }}</strong><br>
                    <span style="color:#888;font-size:11px">{{ addslashes($station['adresse'] ?? '') }}</span><br><br>
                    <span style="background:rgba(0,153,255,0.15);color:#0099ff;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600">⚡ {{ $station['puissance'] ?? 'N/A' }} kW</span>
                </div>
            `);
        @endif
    @endforeach
@endif
</script>

</body>
</html>