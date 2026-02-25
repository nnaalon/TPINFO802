<h1>{{ $vehicle['naming']['make'] }} {{ $vehicle['naming']['model'] }}</h1>

<img src="{{ $vehicle['media']['image']['url'] }}" alt="Image véhicule">

<p>Batterie utilisable : {{ $vehicle['battery']['usable_kwh'] }} kWh</p>
<p>Autonomie combinée (best) : {{ $vehicle['range']['best']['combined'] }} km</p>
<p>Vitesse max : {{ $vehicle['performance']['top_speed'] }} km/h</p>