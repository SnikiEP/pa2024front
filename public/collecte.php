<?php
session_start();

$dsn = 'mysql:host=db;dbname=helix_db;charset=utf8';
$username = 'root';
$password = 'root_password';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Échec de la connexion à la base de données : " . $e->getMessage());
}

if (!isset($_SESSION['accessToken'])) {
    header("Location: login.php");
    exit;
}

$title = "Collecte - NMW";
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/head.php');
?>    

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate="title_collecte">Calculateur d'Itinéraire avec plusieurs arrêts et PDF</title>
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
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .stop {
            margin-bottom: 20px;
        }
        .button {
            margin-top: 15px;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .remove-stop {
            background-color: #ff3860;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 10px;
            border: none;
        }
        .remove-stop:hover {
            background-color: #ff1744;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>
        <main>
            <div class="content">
                <div class="container">
                    <h1 class="title has-text-centered" data-translate="route_calculator_title">Calculateur d'Itinéraire avec plusieurs arrêts</h1>
                    <form id="route-form" class="box">
                        <div class="field">
                            <label class="label" for="start" data-translate="start_address_label">Sélectionner un entrepôt de départ :</label>
                            <div class="control">
                                <select class="input" name="start-warehouse" id="start-warehouse" required>
                                    <option value="" data-translate="select_warehouse">-- Sélectionnez un entrepôt --</option>

                                <?php
                                    $query = "SELECT id, location, address FROM warehouses";
                                    $stmt = $pdo->query($query);
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $displayText = htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8');
                                        if (!is_null($row['address'])) {
                                            $displayText .= ' - ' . htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8');
                                        }
                                        echo '<option value="' . htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') . '">' . $displayText . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div id="stops-container">
                            <div class="field stop">
                                <label class="label" for="collection-point-1">Sélectionner un point de collecte :</label>
                                <div class="control">
                                    <select class="input" name="collection-points[]" id="collection-point-1">
                                        <option value="" data-translate="select_collection_point">-- Sélectionnez un point de collecte --</option>


                                        <?php
                                        $query = "SELECT id, name, address FROM collection_points";
                                        $stmt = $pdo->query($query);
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="' . htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <button type="button" class="remove-stop" data-translate="remove_stop_button">Supprimer</button>
                                </div>
                            </div>
                        </div>
                        <button class="button is-link" type="button" id="add-stop" data-translate="add_stop_button">Ajouter un arrêt</button>
                        <div class="field" style="margin-top: 20px;">
                            <label class="label" for="end" data-translate="end_address_label">Sélectionner un entrepôt d'arrivée :</label>
                            <div class="control">
                                <select class="input" name="end-warehouse" id="end-warehouse" required>
                                    <option value="" data-translate="select_warehouse">-- Sélectionnez un entrepôt --</option>

                                    <?php
                                    $query = "SELECT id, location, address FROM warehouses";
                                    $stmt = $pdo->query($query);
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $displayText = htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8');
                                        if (!is_null($row['address'])) {
                                            $displayText .= ' - ' . htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8');
                                        }
                                        echo '<option value="' . htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') . '">' . $displayText . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <button class="button is-primary" type="submit" data-translate="calculate_route_button">Calculer l'itinéraire</button>
                        <input type="hidden" id="mapScreenshot" name="mapScreenshot">
                        <input type="hidden" id="routeInstructions" name="routeInstructions">
                        <input type="hidden" id="distance" name="distance">
                        <input type="hidden" id="duration" name="duration">
                    </form>

                    <div id="map"></div>
                    <button class="button is-success" id="generate-pdf" style="display:none; margin-top: 20px;" data-translate="generate_pdf_button">Générer le PDF</button>
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
            var stopInput = `<div class="field stop">
                                <label class="label" for="collection-point-${stopCount + 1}">Sélectionner un point de collecte :</label>
                                <div class="control">
                                    <select class="input" name="collection-points[]" id="collection-point-${stopCount + 1}">
                                        <option value="">-- Sélectionnez un point de collecte --</option>
                                        <?php
                                        $query = "SELECT id, name, address FROM collection_points";
                                        $stmt = $pdo->query($query);
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="' . htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <button type="button" class="remove-stop" data-translate="remove_stop_button">Supprimer</button>
                                </div>
                            </div>`;
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
            var stopsProcessed = 0;
            var startWarehouse = document.getElementById('start-warehouse').value;
            var endWarehouse = document.getElementById('end-warehouse').value;
            var selectedCollectionPoints = document.querySelectorAll('#stops-container select');

            geocoder.geocode(startWarehouse, function(results) {
                if (results.length > 0) {
                    waypoints.push(L.latLng(results[0].center.lat, results[0].center.lng));

                    selectedCollectionPoints.forEach(function(point, i) {
                        if (point.value !== '') {
                            geocoder.geocode(point.value, function(pointResults) {
                                if (pointResults.length > 0) {
                                    waypoints.push(L.latLng(pointResults[0].center.lat, pointResults[0].center.lng));
                                }
                                stopsProcessed++;
                                if (stopsProcessed === selectedCollectionPoints.length) {
                                    finalizeRouteCalculation(waypoints, endWarehouse);
                                }
                            });
                        } else {
                            stopsProcessed++;
                            if (stopsProcessed === selectedCollectionPoints.length) {
                                finalizeRouteCalculation(waypoints, endWarehouse);
                            }
                        }
                    });
                } else {
                    alert("Impossible de géocoder l'adresse de l'entrepôt de départ.");
                }
            });
        });

        function finalizeRouteCalculation(waypoints, endWarehouse) {
            geocoder.geocode(endWarehouse, function(endResults) {
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
                    alert("Impossible de géocoder l'adresse de l'entrepôt d'arrivée.");
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
