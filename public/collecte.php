<?php 
include_once('maintenance_check.php');

if (!isset($_SESSION['accessToken'])) {
    header("Location: login.php");
    exit;
}
?>

<?php
$title = "Collecte - NMW";
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/head.php');
?>    

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculateur d'Itinéraire avec plusieurs arrêts et PDF</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/html2canvas@1.0.0-rc.7/dist/html2canvas.min.js"></script>
    <style>
        #map {
            height: 500px;
            width: 100%;
            margin-top: 20px;
            border-radius: 8px;
        }
        form {
            margin-bottom: 30px;
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
        }
        .stop {
            margin-bottom: 20px;
        }
        .button {
            margin-top: 15px;
        }
        .field {
            margin-bottom: 20px;
        }
        .remove-stop {
            background-color: #ff3860;
            color: white;
            border: none;
            padding: 0 10px;
            margin-left: 10px;
            cursor: pointer;
            border-radius: 4px;
        }
        .remove-stop:hover {
            background-color: #ff1744;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>
        <main>
            <div class="content">
                <div class="container">
                    <h1 class="title has-text-centered">Calculateur d'Itinéraire avec plusieurs arrêts</h1>
                    <form id="route-form" class="box">
                        <div class="field">
                            <label class="label" for="start">Adresse de départ :</label>
                            <div class="control">
                                <input class="input" type="text" id="start" placeholder="Ex: 2 rue Gervex, Paris" required>
                            </div>
                        </div>
                        <div id="stops-container">
                            <div class="field stop">
                                <label class="label" for="stop-1">Arrêt :</label>
                                <div class="control">
                                    <input class="input" type="text" id="stop-1" placeholder="Ex: Arrêt intermédiaire">
                                    <button type="button" class="remove-stop">Supprimer</button>
                                </div>
                            </div>
                        </div>
                        <button class="button is-link" type="button" id="add-stop">Ajouter un arrêt</button>
                        <div class="field" style="margin-top: 20px;">
                            <label class="label" for="end">Adresse d'arrivée :</label>
                            <div class="control">
                                <input class="input" type="text" id="end" placeholder="Ex: 34 rue de Clichy, Paris" required>
                            </div>
                        </div>
                        <button class="button is-primary" type="submit">Calculer l'itinéraire</button>
                        <input type="hidden" id="mapScreenshot" name="mapScreenshot">
                        <input type="hidden" id="routeInstructions" name="routeInstructions">
                        <input type="hidden" id="distance" name="distance">
                        <input type="hidden" id="duration" name="duration">
                    </form>

                    <div id="map"></div>
                    <button class="button is-success" id="generate-pdf" style="display:none; margin-top: 20px;">Générer le PDF</button>
                </div>
            </div>
        </main>
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
    </div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script>
        var map = L.map('map').setView([48.8566, 2.3522], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var geocoder = L.Control.Geocoder.nominatim();
        var routingControl;
        var routeInfo = null;

        document.getElementById('add-stop').addEventListener('click', function() {
            var stopCount = document.querySelectorAll('#stops-container .field').length;
            var stopInput = `<div class="field stop"><label class="label" for="stop-${stopCount + 1}">Arrêt :</label><div class="control"><input class="input" type="text" id="stop-${stopCount + 1}" placeholder="Ex: Arrêt intermédiaire"><button type="button" class="remove-stop">Supprimer</button></div></div>`;
            document.getElementById('stops-container').insertAdjacentHTML('beforeend', stopInput);
            attachRemoveStopListeners(); 
        });

        function attachRemoveStopListeners() {
            var removeButtons = document.querySelectorAll('.remove-stop');
            removeButtons.forEach(function(button) {
                button.removeEventListener('click', removeStop);
                button.addEventListener('click', removeStop);
            });
        }

        function removeStop(event) {
            var stopField = event.target.closest('.stop');
            stopField.remove();
        }

        attachRemoveStopListeners();

        document.getElementById('route-form').addEventListener('submit', function(event) {
            event.preventDefault();
            var waypoints = [];

            geocoder.geocode(document.getElementById('start').value, function(results) {
                if (results.length > 0) {
                    waypoints.push(L.latLng(results[0].center.lat, results[0].center.lng));
                    var stops = document.querySelectorAll('#stops-container input[type="text"]');
                    var stopsProcessed = 0;

                    for (var i = 0; i < stops.length; i++) {
                        (function(i) {
                            if (stops[i].value.trim() !== '') {
                                geocoder.geocode(stops[i].value, function(stopResults) {
                                    if (stopResults.length > 0) {
                                        waypoints.push(L.latLng(stopResults[0].center.lat, stopResults[0].center.lng));
                                    }
                                    stopsProcessed++;
                                    if (stopsProcessed === stops.length) {
                                        finalizeRouteCalculation(waypoints);
                                    }
                                });
                            } else {
                                stopsProcessed++;
                                if (stopsProcessed === stops.length) {
                                    finalizeRouteCalculation(waypoints);
                                }
                            }
                        })(i);
                    }
                } else {
                    alert("Impossible de géocoder l'adresse de départ.");
                }
            });
        });

        function finalizeRouteCalculation(waypoints) {
            geocoder.geocode(document.getElementById('end').value, function(endResults) {
                if (endResults.length > 0) {
                    waypoints.push(L.latLng(endResults[0].center.lat, endResults[0].center.lng));

                    if (routingControl) {
                        map.removeControl(routingControl);
                    }

                    routingControl = L.Routing.control({
                        waypoints: waypoints,
                        routeWhileDragging: true,
                        showAlternatives: true,
                        geocoder: geocoder,
                        altLineOptions: { styles: [{ color: 'black', opacity: 0.15, weight: 9 }] }
                    }).on('routesfound', function(e) {
                        routeInfo = e.routes[0];
                        document.getElementById('generate-pdf').style.display = 'block';
                        document.getElementById('routeInstructions').value = JSON.stringify(routeInfo.instructions.map(i => i.text));
                        document.getElementById('distance').value = routeInfo.summary.totalDistance;
                        document.getElementById('duration').value = routeInfo.summary.totalTime;
                        captureMap(); 
                    }).addTo(map);
                } else {
                    alert("Impossible de géocoder l'adresse d'arrivée.");
                }
            });
        }

        function captureMap() {
            var mapElement = document.getElementById('map');
            html2canvas(mapElement).then(canvas => {
                var imageData = canvas.toDataURL('image/png');
                document.getElementById('mapScreenshot').value = imageData; 
            });
        }

        document.getElementById('generate-pdf').addEventListener('click', function() {
            if (routeInfo) {
                var formData = new FormData(document.getElementById('route-form'));
                fetch('generate_pdf.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.blob())
                .then(blob => {
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'itineraire_complet.pdf';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                })
                .catch(error => console.error('Erreur lors de la génération du PDF :', error));
            }
        });
    </script>
</body>
</html>
