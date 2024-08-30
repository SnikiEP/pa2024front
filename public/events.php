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

$userId = $_SESSION['user_id'] ?? null;  

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['eventAction'])) {
        if ($_POST['eventAction'] === 'create' || $_POST['eventAction'] === 'edit') {
            $eventName = $_POST['eventName'];
            $eventType = $_POST['eventType'];
            $eventStart = $_POST['eventStart'];
            $eventEnd = $_POST['eventEnd'];
            $location = $_POST['location'];
            $description = $_POST['description'];

            if ($_POST['eventAction'] === 'create') {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO events (event_name, event_type, event_start, event_end, location, description)
                        VALUES (:eventName, :eventType, :eventStart, :eventEnd, :location, :description)
                    ");
                    $stmt->execute([
                        ':eventName' => $eventName,
                        ':eventType' => $eventType,
                        ':eventStart' => $eventStart,
                        ':eventEnd' => $eventEnd,
                        ':location' => $location,
                        ':description' => $description
                    ]);
                    $successMessage = "Event created successfully!";
                } catch (PDOException $e) {
                    $errorMessage = "Failed to create the event: " . $e->getMessage();
                }
            } elseif ($_POST['eventAction'] === 'edit') {
                $eventId = $_POST['eventId'];
                try {
                    $stmt = $pdo->prepare("
                        UPDATE events SET event_name = :eventName, event_type = :eventType, event_start = :eventStart,
                        event_end = :eventEnd, location = :location, description = :description
                        WHERE id = :eventId
                    ");
                    $stmt->execute([
                        ':eventName' => $eventName,
                        ':eventType' => $eventType,
                        ':eventStart' => $eventStart,
                        ':eventEnd' => $eventEnd,
                        ':location' => $location,
                        ':description' => $description,
                        ':eventId' => $eventId
                    ]);
                    $successMessage = "Event updated successfully!";
                } catch (PDOException $e) {
                    $errorMessage = "Failed to update the event: " . $e->getMessage();
                }
            }
        } elseif ($_POST['eventAction'] === 'join') {
            $eventId = $_POST['event_id'];
            try {
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM event_participants WHERE user_id = :userId AND event_id = :eventId");
                $checkStmt->execute([':userId' => $userId, ':eventId' => $eventId]);

                if ($checkStmt->fetchColumn() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO event_participants (user_id, event_id) VALUES (:userId, :eventId)");
                    $stmt->execute([':userId' => $userId, ':eventId' => $eventId]);
                    $successMessage = "Successfully joined the event!";
                } else {
                    $errorMessage = "You have already joined this event.";
                }
            } catch (PDOException $e) {
                $errorMessage = "Failed to join the event: " . $e->getMessage();
            }
        }
    }
}

$allEventsStmt = $pdo->query("
    SELECT e.*, 
    (SELECT GROUP_CONCAT(v.model SEPARATOR ', ') 
     FROM vehicles v 
     JOIN event_vehicle ev ON v.id = ev.vehicle_id 
     WHERE ev.event_id = e.id) AS vehicles
    FROM events e 
    ORDER BY e.event_start ASC
");
$allEvents = $allEventsStmt->fetchAll(PDO::FETCH_ASSOC);

$myEventsStmt = $pdo->prepare("
    SELECT e.* 
    FROM events e 
    JOIN event_participants ep ON e.id = ep.event_id
    WHERE ep.user_id = :userId
");
$myEventsStmt->execute([':userId' => $userId]);
$myEvents = $myEventsStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Event Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
    <style>
        .tab-content {
            display: none;
        }
        .tab-content.is-active {
            display: block;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="title">Event Management</h1>

    <?php if (!empty($successMessage)): ?>
        <div class="notification is-success">
            <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="notification is-danger">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="tabs">
        <ul>
            <li class="is-active"><a data-tab="tab-events">Available Events</a></li>
            <li><a data-tab="tab-create">Create/Edit Event</a></li>
            <li><a data-tab="tab-my-events">My Events</a></li>
        </ul>
    </div>

    <div id="tab-events" class="tab-content is-active">
        <h2 class="subtitle">Available Events</h2>
        <?php foreach ($allEvents as $event): ?>
            <div class="box">
                <h3 class="title"><?= htmlspecialchars($event['event_name']) ?></h3>
                <p><strong>Type:</strong> <?= htmlspecialchars($event['event_type']) ?></p>
                <p><strong>Start:</strong> <?= htmlspecialchars($event['event_start']) ?></p>
                <p><strong>End:</strong> <?= htmlspecialchars($event['event_end']) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></p>
                <p><strong>Vehicles:</strong> <?= htmlspecialchars($event['vehicles'] ?? 'None') ?></p>
                <form method="POST">
                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                    <input type="hidden" name="eventAction" value="join">
                    <button class="button is-primary" type="submit">Join Event</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="tab-create" class="tab-content">
        <h2 class="subtitle">Create/Edit Event</h2>
        <form method="POST">
            <div class="field">
                <label class="label" for="eventName">Event Name</label>
                <div class="control">
                    <input class="input" type="text" id="eventName" name="eventName" required>
                </div>
            </div>

            <div class="field">
                <label class="label" for="eventType">Event Type</label>
                <div class="control">
                    <input class="input" type="text" id="eventType" name="eventType" required>
                </div>
            </div>

            <div class="field">
                <label class="label" for="eventStart">Event Start</label>
                <div class="control">
                    <input class="input" type="datetime-local" id="eventStart" name="eventStart" required>
                </div>
            </div>

            <div class="field">
                <label class="label" for="eventEnd">Event End</label>
                <div class="control">
                    <input class="input" type="datetime-local" id="eventEnd" name="eventEnd" required>
                </div>
            </div>

            <div class="field">
                <label class="label" for="location">Location</label>
                <div class="control">
                    <input class="input" type="text" id="location" name="location" required>
                </div>
            </div>

            <div class="field">
                <label class="label" for="description">Description</label>
                <div class="control">
                    <textarea class="textarea" id="description" name="description" required></textarea>
                </div>
            </div>

            <div class="control">
                <input type="hidden" name="eventAction" value="create">
                <button class="button is-success" type="submit">Create Event</button>
            </div>
        </form>
    </div>

    <div id="tab-my-events" class="tab-content">
        <h2 class="subtitle">My Events</h2>
        <?php if (empty($myEvents)): ?>
            <p>You have not joined any events yet.</p>
        <?php else: ?>
            <?php foreach ($myEvents as $event): ?>
                <div class="box">
                    <h3 class="title"><?= htmlspecialchars($event['event_name']) ?></h3>
                    <p><strong>Type:</strong> <?= htmlspecialchars($event['event_type']) ?></p>
                    <p><strong>Start:</strong> <?= htmlspecialchars($event['event_start']) ?></p>
                    <p><strong>End:</strong> <?= htmlspecialchars($event['event_end']) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('.tabs li');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(item => item.classList.remove('is-active'));
                tab.classList.add('is-active');

                const target = tab.querySelector('a').dataset.tab;
                tabContents.forEach(content => {
                    if (content.id === target) {
                        content.classList.add('is-active');
                    } else {
                        content.classList.remove('is-active');
                    }
                });
            });
        });
    });
</script>
</body>
</html>
