<?php
session_start();

$dsn = 'mysql:host=db;dbname=helix_db;charset=utf8';
$username = 'root';
$password = 'root_password';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = :eventId");
        $stmt->execute([':eventId' => $_POST['eventId']]);
    } elseif (isset($_POST['edit'])) {
        $stmt = $pdo->prepare("
            UPDATE events SET 
                event_name = :eventName,
                event_type = :eventType,
                event_start = :eventStart,
                event_end = :eventEnd,
                location = :location,
                description = :description
            WHERE id = :eventId
        ");
        $stmt->execute([
            ':eventName' => $_POST['eventName'],
            ':eventType' => $_POST['eventType'],
            ':eventStart' => $_POST['eventStart'],
            ':eventEnd' => $_POST['eventEnd'],
            ':location' => $_POST['location'],
            ':description' => $_POST['description'],
            ':eventId' => $_POST['eventId']
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO events (event_name, event_type, event_start, event_end, location, description)
            VALUES (:eventName, :eventType, :eventStart, :eventEnd, :location, :description)
        ");
        $stmt->execute([
            ':eventName' => $_POST['eventName'],
            ':eventType' => $_POST['eventType'],
            ':eventStart' => $_POST['eventStart'],
            ':eventEnd' => $_POST['eventEnd'],
            ':location' => $_POST['location'],
            ':description' => $_POST['description']
        ]);

        $eventId = $pdo->lastInsertId();
        if (!empty($_POST['vehicleIds'])) {
            $stmt = $pdo->prepare("INSERT INTO event_vehicle (event_id, vehicle_id) VALUES (:eventId, :vehicleId)");
            foreach ($_POST['vehicleIds'] as $vehicleId) {
                $stmt->execute([
                    ':eventId' => $eventId,
                    ':vehicleId' => $vehicleId
                ]);
            }
        }
    }

    header("Location: events.php");
    exit;
}

function escape($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$filters = [];
$sql = "SELECT * FROM events WHERE 1=1";
if (!empty($_GET['eventType'])) {
    $sql .= " AND event_type = :eventType";
    $filters[':eventType'] = $_GET['eventType'];
}
if (!empty($_GET['location'])) {
    $sql .= " AND location = :location";
    $filters[':location'] = $_GET['location'];
}
$allEventsStmt = $pdo->prepare($sql);
$allEventsStmt->execute($filters);
$allEvents = $allEventsStmt->fetchAll(PDO::FETCH_ASSOC);

$allVehiclesStmt = $pdo->query("SELECT * FROM vehicles");
$allVehicles = $allVehiclesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Manage Events - HELIX";
    include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/head.php');
    ?>
    <link rel="stylesheet" href="/assets/css/panel.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php') ?>
        <main>
            <div class="content">
                <div class="filter-container">
                    <h2>Filter Events</h2>
                    <form method="GET">
                        <label for="eventTypeFilter">Event Type:</label>
                        <select id="eventTypeFilter" name="eventType">
                            <option value="">All Types</option>
                            <?php
                            $types = $pdo->query("SELECT DISTINCT event_type FROM events")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($types as $type) : ?>
                                <option value="<?= escape($type['event_type']) ?>" <?= isset($_GET['eventType']) && $_GET['eventType'] == $type['event_type'] ? 'selected' : '' ?>>
                                    <?= escape($type['event_type']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="locationFilter">Location:</label>
                        <select id="locationFilter" name="location">
                            <option value="">All Locations</option>
                            <?php
                            $locations = $pdo->query("SELECT DISTINCT location FROM events")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($locations as $location) : ?>
                                <option value="<?= escape($location['location']) ?>" <?= isset($_GET['location']) && $_GET['location'] == $location['location'] ? 'selected' : '' ?>>
                                    <?= escape($location['location']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit">Filter</button>
                    </form>
                </div>

                <div class="events-list">
                    <h2>Events List</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Location</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allEvents as $event) : ?>
                                <tr>
                                    <td><?= escape($event['event_name']) ?></td>
                                    <td><?= escape($event['event_type']) ?></td>
                                    <td><?= escape($event['event_start']) ?></td>
                                    <td><?= escape($event['event_end']) ?></td>
                                    <td><?= escape($event['location']) ?></td>
                                    <td><?= escape($event['description']) ?></td>
                                    <td class="event-actions">
                                        <button type="button" class="edit" onclick="editEvent(<?= escape($event['id']) ?>)">Edit</button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="eventId" value="<?= escape($event['id']) ?>">
                                            <button type="submit" name="delete" class="delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h2>Create or Edit Event</h2>
                <form method="POST">
                    <input type="hidden" name="eventId" id="eventId">

                    <label for="eventName">Event Name:</label>
                    <input type="text" id="eventName" name="eventName" required>

                    <label for="eventType">Event Type:</label>
                    <input type="text" id="eventType" name="eventType" required>

                    <label for="eventStart">Event Start:</label>
                    <input type="datetime-local" id="eventStart" name="eventStart" required>

                    <label for="eventEnd">Event End:</label>
                    <input type="datetime-local" id="eventEnd" name="eventEnd" required>

                    <label for="location">Location:</label>
                    <input type="text" id="location" name="location" required>

                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required></textarea>

                    <label for="vehicleIds">Select Vehicles:</label>
                    <select id="vehicleIds" name="vehicleIds[]" multiple>
                        <?php foreach ($allVehicles as $vehicle) : ?>
                            <option value="<?= escape($vehicle['id']) ?>">
                                <?= escape($vehicle['id_plate']) ?> - <?= escape($vehicle['model']) ?> (Fret Capacity: <?= escape($vehicle['fret_capacity']) ?>, Human Capacity: <?= escape($vehicle['human_capacity']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="submit" value="Save Event">
                </form>
            </div>
        </main>
        <footer class="footer">
            &copy; <?= date('Y'); ?> HELIX. All Rights Reserved.
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#eventStart", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            altInput: true,
            altFormat: "F j, Y h:i K"
        });

        flatpickr("#eventEnd", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            altInput: true,
            altFormat: "F j, Y h:i K"
        });

        function editEvent(eventId) {
            var events = <?= json_encode($allEvents) ?>;
            var event = events.find(e => e.id == eventId);
            document.getElementById('eventId').value = event.id;
            document.getElementById('eventName').value = event.event_name;
            document.getElementById('eventType').value = event.event_type;
            document.getElementById('eventStart').value = event.event_start.replace(' ', 'T');
            document.getElementById('eventEnd').value = event.event_end.replace(' ', 'T');
            document.getElementById('location').value = event.location;
            document.getElementById('description').value = event.description;
        }
    </script>
</body>

</html>
