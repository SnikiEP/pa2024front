<?php 
include_once('maintenance_check.php');

if (!isset($_SESSION['accessToken'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculateur d'Itinéraire avec plusieurs arrêts et PDF</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="../css/main.css" />
    <script src="https://unpkg.com/html2canvas@1.0.0-rc.7/dist/html2canvas.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1;
        }
        .content {
            padding: 20px;
        }
        #map {
            height: 500px;
            width: 100%;
        }
        form {
            margin-bottom: 20px;
        }
        .stop {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>
        <main>
            <div class="content">
                <div class="container">
                    <h1 class="title">Calculateur d'Itinéraire avec plusieurs arrêts</h1>
                    <form id="route-form">
                        <div class="stop">
                            <label for="start">Adresse de départ :</label>
                            <input type="text" id="start" placeholder="Ex: 2 rue Gervex, Paris" required>
                        </div>
                        <div id="stops-container">
                            <div class="stop">
                                <label for="stop-1">Arrêt :</label>
                                <input type="text" id="stop-1" placeholder="Ex: Arrêt intermédiaire">
                            </div>
                        </div>
                        <button type="button" id="add-stop">Ajouter un arrêt</button>
                        <div class="stop">
                            <label for="end">Adresse d'arrivée :</label>
                            <input type="text" id="end" placeholder="Ex: 34 rue de Clichy, Paris" required>
                        </div>
                        <button type="submit">Calculer l'itinéraire</button>
                        <input type="hidden" id="mapScreenshot" name="mapScreenshot">
                        <input type="hidden" id="routeInstructions" name="routeInstructions">
                    </form>

                    <div id="map"></div>
                    <button id="generate-pdf" style="display:none;">Générer le PDF</button>
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
            var stopCount = document.querySelectorAll('#stops-container .stop').length;
            var stopInput = `<div class="stop"><label for="stop-${stopCount + 1}">Arrêt :</label><input type="text" id="stop-${stopCount + 1}" placeholder="Ex: Arrêt intermédiaire"></div>`;
            document.getElementById('stops-container').insertAdjacentHTML('beforeend', stopInput);
        });

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
                document.getElementById('mapScreenshot').value = imageData; // Envoyer l'image au serveur
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
