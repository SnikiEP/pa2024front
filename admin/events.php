<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php');

$dsn = 'mysql:host=db;dbname=helix_db;charset=utf8';
$username = 'root';
$password = 'root_password';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function escape($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function loadTranslations($lang = 'en')
{
    $file = __DIR__ . "/locales/{$lang}.json";
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return [];
}

$lang = 'en';
$translations = loadTranslations($lang);

$errorMessage = null;
$successMessage = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $eventStart = $_POST['eventStart'];
    $eventEnd = $_POST['eventEnd'];
    $eventId = $_POST['eventId'] ?? null;
    $vehicleIds = $_POST['vehicleIds'] ?? [];

    if (strtotime($eventStart) >= strtotime($eventEnd)) {
        $errorMessage = $translations['error_date_range'] ?? 'The end date must be after the start date.';
    }

    if (!$errorMessage && !empty($vehicleIds)) {
        foreach ($vehicleIds as $vehicleId) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM event_vehicle ev
                JOIN events e ON ev.event_id = e.id
                WHERE ev.vehicle_id = :vehicleId
                AND (
                    (:eventStart BETWEEN e.event_start AND e.event_end)
                    OR (:eventEnd BETWEEN e.event_start AND e.event_end)
                    OR (e.event_start BETWEEN :eventStart AND :eventEnd)
                    OR (e.event_end BETWEEN :eventStart AND :eventEnd)
                )
            ");
            $stmt->execute([
                ':vehicleId' => $vehicleId,
                ':eventStart' => $eventStart,
                ':eventEnd' => $eventEnd
            ]);

            if ($stmt->fetchColumn() > 0) {
                $errorMessage = sprintf($translations['error_vehicle_conflict'] ?? 'Vehicle %s conflicts with an existing event.', escape($vehicleId));
                break;
            }
        }
    }

    if (!$errorMessage) {
        try {
            if ($eventId) {
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
                    ':eventStart' => $eventStart,
                    ':eventEnd' => $eventEnd,
                    ':location' => $_POST['location'],
                    ':description' => $_POST['description'],
                    ':eventId' => $eventId
                ]);

                $stmt = $pdo->prepare("DELETE FROM event_vehicle WHERE event_id = :eventId");
                $stmt->execute([':eventId' => $eventId]);

                if (!empty($vehicleIds)) {
                    $stmt = $pdo->prepare("INSERT INTO event_vehicle (event_id, vehicle_id) VALUES (:eventId, :vehicleId)");
                    foreach ($vehicleIds as $vehicleId) {
                        $stmt->execute([
                            ':eventId' => $eventId,
                            ':vehicleId' => $vehicleId
                        ]);
                    }
                }

                $successMessage = $translations['success_update_events'] ?? 'Event updated successfully.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO events (event_name, event_type, event_start, event_end, location, description)
                    VALUES (:eventName, :eventType, :eventStart, :eventEnd, :location, :description)
                ");
                $stmt->execute([
                    ':eventName' => $_POST['eventName'],
                    ':eventType' => $_POST['eventType'],
                    ':eventStart' => $eventStart,
                    ':eventEnd' => $eventEnd,
                    ':location' => $_POST['location'],
                    ':description' => $_POST['description']
                ]);

                $eventId = $pdo->lastInsertId();
                if (!empty($vehicleIds)) {
                    $stmt = $pdo->prepare("INSERT INTO event_vehicle (event_id, vehicle_id) VALUES (:eventId, :vehicleId)");
                    foreach ($vehicleIds as $vehicleId) {
                        $stmt->execute([
                            ':eventId' => $eventId,
                            ':vehicleId' => $vehicleId
                        ]);
                    }
                }

                $successMessage = $translations['success_add_events'] ?? 'Event added successfully.';
            }

            header("Location: events.php");
            exit;
        } catch (PDOException $e) {
            $errorMessage = $translations['error_update_events'] ?? 'Failed to update the event: ' . $e->getMessage();
        }
    }
}

// Filters and Fetch Events
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
<html lang="<?= escape($lang) ?>">

<head>
    <?php
    $title = $translations['manage_events'] ?? 'Manage Events';
    include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/head.php');
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php') ?>
        <main class="section">
            <div class="container">
                <h1 class="title has-text-centered"><?= escape($translations['manage_events'] ?? 'Manage Events') ?></h1>

                <?php if ($errorMessage) : ?>
                    <div class="notification is-danger">
                        <?= escape($errorMessage) ?>
                    </div>
                <?php endif; ?>

                <?php if ($successMessage) : ?>
                    <div class="notification is-success">
                        <?= escape($successMessage) ?>
                    </div>
                <?php endif; ?>

                <div class="box">
                    <h2 class="subtitle"><?= escape($translations['filter_events'] ?? 'Filter Events') ?></h2>
                    <form method="GET">
                        <div class="field">
                            <label class="label" for="eventTypeFilter"><?= escape($translations['event_type'] ?? 'Event Type') ?></label>
                            <div class="control">
                                <div class="select">
                                    <select id="eventTypeFilter" name="eventType">
                                        <option value=""><?= escape($translations['all_types'] ?? 'All Types') ?></option>
                                        <?php
                                        $types = $pdo->query("SELECT DISTINCT event_type FROM events")->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($types as $type) : ?>
                                            <option value="<?= escape($type['event_type']) ?>" <?= isset($_GET['eventType']) && $_GET['eventType'] == $type['event_type'] ? 'selected' : '' ?>>
                                                <?= escape($type['event_type']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="locationFilter"><?= escape($translations['location'] ?? 'Location') ?></label>
                            <div class="control">
                                <div class="select">
                                    <select id="locationFilter" name="location">
                                        <option value=""><?= escape($translations['all_locations'] ?? 'All Locations') ?></option>
                                        <?php
                                        $locations = $pdo->query("SELECT DISTINCT location FROM events")->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($locations as $location) : ?>
                                            <option value="<?= escape($location['location']) ?>" <?= isset($_GET['location']) && $_GET['location'] == $location['location'] ? 'selected' : '' ?>>
                                                <?= escape($location['location']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="control">
                            <button class="button is-primary" type="submit"><?= escape($translations['filter'] ?? 'Filter') ?></button>
                        </div>
                    </form>
                </div>

                <div class="box">
                    <h2 class="subtitle"><?= escape($translations['events_list'] ?? 'Events List') ?></h2>
                    <div class="table-container">
                        <table class="table is-striped is-fullwidth">
                            <thead>
                                <tr>
                                    <th><?= escape($translations['event_name'] ?? 'Event Name') ?></th>
                                    <th><?= escape($translations['event_type'] ?? 'Event Type') ?></th>
                                    <th><?= escape($translations['event_start'] ?? 'Event Start') ?></th>
                                    <th><?= escape($translations['event_end'] ?? 'Event End') ?></th>
                                    <th><?= escape($translations['location'] ?? 'Location') ?></th>
                                    <th><?= escape($translations['description'] ?? 'Description') ?></th>
                                    <th><?= escape($translations['actions'] ?? 'Actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allEvents)) : ?>
                                    <tr>
                                        <td colspan="7"><?= escape($translations['no_events_found'] ?? 'No events found.') ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allEvents as $event) : ?>
                                        <tr>
                                            <td><?= escape($event['event_name']) ?></td>
                                            <td><?= escape($event['event_type']) ?></td>
                                            <td><?= escape($event['event_start']) ?></td>
                                            <td><?= escape($event['event_end']) ?></td>
                                            <td><?= escape($event['location']) ?></td>
                                            <td><?= escape($event['description']) ?></td>
                                            <td class="event-actions">
                                                <button class="button is-info is-small" type="button" onclick="editEvent(<?= escape($event['id']) ?>)"><?= escape($translations['edit'] ?? 'Edit') ?></button>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="eventId" value="<?= escape($event['id']) ?>">
                                                    <button class="button is-danger is-small" type="submit" name="delete"><?= escape($translations['delete'] ?? 'Delete') ?></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="box">
                    <h2 class="subtitle"><?= escape($translations['create_edit_event'] ?? 'Create/Edit Event') ?></h2>
                    <form method="POST">
                        <input type="hidden" name="eventId" id="eventId">

                        <div class="field">
                            <label class="label" for="eventName"><?= escape($translations['event_name'] ?? 'Event Name') ?></label>
                            <div class="control">
                                <input class="input" type="text" id="eventName" name="eventName" required>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="eventType"><?= escape($translations['event_type'] ?? 'Event Type') ?></label>
                            <div class="control">
                                <input class="input" type="text" id="eventType" name="eventType" required>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="eventStart"><?= escape($translations['event_start'] ?? 'Event Start') ?></label>
                            <div class="control">
                                <input class="input" type="datetime-local" id="eventStart" name="eventStart" required>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="eventEnd"><?= escape($translations['event_end'] ?? 'Event End') ?></label>
                            <div class="control">
                                <input class="input" type="datetime-local" id="eventEnd" name="eventEnd" required>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="location"><?= escape($translations['location'] ?? 'Location') ?></label>
                            <div class="control">
                                <input class="input" type="text" id="location" name="location" required>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="description"><?= escape($translations['description'] ?? 'Description') ?></label>
                            <div class="control">
                                <textarea class="textarea" id="description" name="description" required></textarea>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="vehicleIds"><?= escape($translations['select_vehicles'] ?? 'Select Vehicles') ?></label>
                            <div class="control">
                                <?php foreach ($allVehicles as $vehicle) : ?>
                                    <label class="checkbox">
                                        <input type="checkbox" name="vehicleIds[]" value="<?= escape($vehicle['id']) ?>">
                                        <?= escape($vehicle['id_plate']) ?> - <?= escape($vehicle['model']) ?> (Fret Capacity: <?= escape($vehicle['fret_capacity']) ?>, Human Capacity: <?= escape($vehicle['human_capacity']) ?>)
                                    </label><br>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="control">
                            <button class="button is-success" type="submit"><?= escape($translations['save_event'] ?? 'Save Event') ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        <footer class="footer">
            <p data-translate="footer_text">&copy; 2024-<?= date("Y"), ($translations['footer_text']) ?></p>
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

            // For vehicle selection
            var vehicleCheckboxes = document.querySelectorAll('input[name="vehicleIds[]"]');
            vehicleCheckboxes.forEach(function(checkbox) {
                checkbox.checked = event.vehicleIds.includes(checkbox.value);
            });
        }
    </script>
</body>

</html>
