from spyne import Application, rpc, ServiceBase, Float
from spyne.protocol.soap import Soap11
from spyne.server.wsgi import WsgiApplication
import logging
import math


class CalculatorService(ServiceBase):

    @rpc(
        Float,  # distance_km
        Float,  # initial_soc_percent
        Float,  # battery_capacity_kwh
        Float,  # consumption_kwh_per_100km
        Float,  # charging_time_min
        _returns=Float
    )
    def calculate_travel_time(
        ctx,
        distance_km,
        initial_soc_percent,
        battery_capacity_kwh,
        consumption_kwh_per_100km,
        charging_time_min
    ):
       

        # Vitesse moyenne fixée
        average_speed_kmh = 100

        # Temps de conduite
        driving_time_min = (distance_km / average_speed_kmh) * 60

        # Energie disponible au départ
        initial_energy_kwh = battery_capacity_kwh * (initial_soc_percent / 100)

        # Energie nécessaire pour le trajet
        total_energy_needed_kwh = (distance_km * consumption_kwh_per_100km) / 100

        # Energie à recharger
        energy_to_charge_kwh = max(
            0,
            total_energy_needed_kwh - initial_energy_kwh
        )

        # Nombre de recharges nécessaires
        nb_charging_stops = math.ceil(
            energy_to_charge_kwh / battery_capacity_kwh
        ) if energy_to_charge_kwh > 0 else 0

        # Temps total de recharge
        total_charging_time_min = nb_charging_stops * charging_time_min

        total_time = driving_time_min + total_charging_time_min

        print(
            f"Trajet {distance_km} km | "
            f"SOC départ: {initial_soc_percent}% | "
            f"Recharges: {nb_charging_stops} | "
            f"Temps total: {total_time} min"
        )

        return total_time


application = Application(
    [CalculatorService],
    tns='ev.calculator.soap',
    in_protocol=Soap11(validator='lxml'),
    out_protocol=Soap11()
)

if __name__ == '__main__':
    from wsgiref.simple_server import make_server

    logging.basicConfig(level=logging.DEBUG)

    server = make_server(
        '0.0.0.0',
        8000,
        WsgiApplication(application)
    )

    print("Service SOAP EV Calculator disponible sur http://0.0.0.0:8000")
    print("WSDL : http://0.0.0.0:8000/?wsdl")

    server.serve_forever()