<h1>Liste des véhicules</h1>

@foreach ($vehicles as $vehicle)
    <div>
        <img src="{{ $vehicle['media']['image']['thumbnail_url'] }}" alt="{{ $vehicle['naming']['make'] }} {{ $vehicle['naming']['model'] }}">
        <strong>{{ $vehicle['naming']['make'] }} {{ $vehicle['naming']['model'] }}</strong><br>
        Version ChargeTrip : {{ $vehicle['naming']['chargetrip_version'] }}
    </div>
@endforeach