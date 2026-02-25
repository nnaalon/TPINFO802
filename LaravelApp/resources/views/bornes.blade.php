@if(!empty($stationsOnRoute))
    <div class="station-list">
        <h2>Bornes sur le trajet</h2>

        @foreach($stationsOnRoute as $station)
            @if(isset($station))
                <div class="station-item">
                    <strong>{{ $station['n_station'] }}</strong><br>
                    {{ $station['ad_station'] ?? '' }}<br>
                    Puissance max : {{ $station['puiss_max'] ?? 'N/A' }} kW<br>
                    Type prise : {{ $station['type_prise'] ?? 'N/A' }}
                </div>
            @endif
        @endforeach
    </div>
@elsea
    <p>Aucune borne trouvée sur le trajet.</p>
@endif