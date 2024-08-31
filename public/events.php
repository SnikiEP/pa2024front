<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || !is_array($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$baseUrl = "http://ddns.callidos-mtf.fr:8085";
$authHeader = "Authorization: Bearer " . $_SESSION['accessToken'];

function makeHttpRequest($url, $method, $data = null) {
    global $authHeader;
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => "",
        CURLOPT_AUTOREFERER => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            $authHeader
        ]
    ];

    if ($method === "POST") {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    } elseif ($method === "PUT") {
        $options[CURLOPT_CUSTOMREQUEST] = "PUT";
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    } elseif ($method === "DELETE") {
        $options[CURLOPT_CUSTOMREQUEST] = "DELETE";
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, $options);
    $result = curl_exec($curl);

    if ($result === false) {
        throw new Exception(curl_error($curl), curl_errno($curl));
    }

    curl_close($curl);

    return json_decode($result, true);
}

function escape($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

try {
    $allUsers = makeHttpRequest($baseUrl . "/account/all", "GET");
} catch (Exception $e) {
    $allUsers = [];
}

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
$successMessage = '';
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['eventAction'])) {
        $eventAction = $_POST['eventAction'];

        if ($eventAction === 'create' || $eventAction === 'edit') {
            $eventName = $_POST['eventName'];
            $eventType = $_POST['eventType'];
            $eventStart = $_POST['eventStart'];
            $eventEnd = $_POST['eventEnd'];
            $location = $_POST['location'];
            $description = $_POST['description'];
            $autoJoin = isset($_POST['autoJoin']) ? true : false;

            if (new DateTime($eventStart) < new DateTime()) {
                $errorMessage = "Event start time cannot be in the past.";
            } else {
                if ($eventAction === 'create') {
                    try {
                        $pdo->beginTransaction();

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

                        if ($autoJoin) {
                            $eventId = $pdo->lastInsertId();
                            $stmt = $pdo->prepare("INSERT INTO event_participants (user_id, event_id) VALUES (:userId, :eventId)");
                            $stmt->execute([':userId' => $userId, ':eventId' => $eventId]);
                        }

                        $pdo->commit();
                        $successMessage = "Event created successfully!";
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $errorMessage = "Failed to create the event: " . $e->getMessage();
                    }
                } elseif ($eventAction === 'edit') {
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
            }
        } elseif ($eventAction === 'join') {
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
        } elseif ($eventAction === 'quit') {
            $eventId = $_POST['event_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM event_participants WHERE user_id = :userId AND event_id = :eventId");
                $stmt->execute([':userId' => $userId, ':eventId' => $eventId]);
                $successMessage = "You have successfully quit the event!";
            } catch (PDOException $e) {
                $errorMessage = "Failed to quit the event: " . $e->getMessage();
            }
        } elseif ($eventAction === 'invite') {
            $eventId = $_POST['event_id'];
            $inviteeId = $_POST['invitee_id'];

            $invitee = array_filter($allUsers, function($user) use ($inviteeId) {
                return $user['id'] == $inviteeId;
            });
            
            $invitee = reset($invitee);  

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO event_invitations (event_id, inviter_username, inviter_email, invitee_username, invitee_email)
                    VALUES (:eventId, :inviterUsername, :inviterEmail, :inviteeUsername, :inviteeEmail)
                ");
                $stmt->execute([
                    ':eventId' => $eventId,
                    ':inviterUsername' => $_SESSION['username'],
                    ':inviterEmail' => $_SESSION['email'],
                    ':inviteeUsername' => $invitee['username'],
                    ':inviteeEmail' => $invitee['email']
                ]);
                $successMessage = "Invitation sent successfully!";
            } catch (PDOException $e) {
                $errorMessage = "Failed to send the invitation: " . $e->getMessage();
            }
        } elseif ($eventAction === 'join_from_invitation') {
            $invitationId = $_POST['invitation_id'];
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE event_invitations SET status = 'accepted' WHERE id = :invitationId");
                $stmt->execute([':invitationId' => $invitationId]);

                $stmt = $pdo->prepare("SELECT event_id FROM event_invitations WHERE id = :invitationId");
                $stmt->execute([':invitationId' => $invitationId]);
                $eventId = $stmt->fetchColumn();

                $stmt = $pdo->prepare("INSERT INTO event_participants (user_id, event_id) VALUES (:userId, :eventId)");
                $stmt->execute([':userId' => $userId, ':eventId' => $eventId]);

                $pdo->commit();

                $successMessage = "You have successfully joined the event!";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errorMessage = "Failed to join the event: " . $e->getMessage();
            }
        }
    }
}

$allEventsStmt = $pdo->prepare("
    SELECT e.*, 
    (SELECT GROUP_CONCAT(v.model SEPARATOR ', ') 
     FROM vehicles v 
     JOIN event_vehicle ev ON v.id = ev.vehicle_id 
     WHERE ev.event_id = e.id) AS vehicles,
    (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id AND ep.user_id = :userId) AS is_participant
    FROM events e 
    ORDER BY e.event_start ASC
");
$allEventsStmt->execute([':userId' => $userId]);
$allEvents = $allEventsStmt->fetchAll(PDO::FETCH_ASSOC);

$myEventsStmt = $pdo->prepare("
    SELECT e.* 
    FROM events e 
    JOIN event_participants ep ON e.id = ep.event_id
    WHERE ep.user_id = :userId
");
$myEventsStmt->execute([':userId' => $userId]);
$myEvents = $myEventsStmt->fetchAll(PDO::FETCH_ASSOC);

$invitationsStmt = $pdo->prepare("
    SELECT ei.id AS invitation_id, e.event_name, e.event_type, e.event_start, e.event_end, e.location, ei.inviter_username AS inviter_name
    FROM event_invitations ei
    JOIN events e ON ei.event_id = e.id
    WHERE ei.invitee_email = :inviteeEmail AND ei.status = 'pending'
");
$invitationsStmt->execute([':inviteeEmail' => $_SESSION['email']]);
$invitations = $invitationsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Event Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        .wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        main {
            flex: 1;
        }

        footer {
            background-color: #222; 
            color: white;
            text-align: center;
            padding: 10px;
            position: relative;
            bottom: 0;
            width: 100%;
        }

        .tab-content {
            display: none;
        }
        .tab-content.is-active {
            display: block;
        }
        input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>
        
        <main>
            <div class="container">
                <h1 class="title" data-translate="event_management"></h1>

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
                        <li class="is-active"><a data-tab="tab-events" data-translate="available_events"></a></li>
                        <li><a data-tab="tab-create" data-translate="create_edit_event"></a></li>
                        <li><a data-tab="tab-my-events" data-translate="my_events"></a></li>
                        <li><a data-tab="tab-invitations" data-translate="invitations"></a></li>
                    </ul>
                </div>

                <div id="tab-events" class="tab-content is-active">
                    <h2 class="subtitle" data-translate="available_events"></h2>
                    <?php foreach ($allEvents as $event): ?>
                        <div class="box">
                            <h3 class="title"><?= escape($event['event_name']) ?></h3>
                            <p><strong data-translate="type_label"></strong> <?= escape($event['event_type']) ?></p>
                            <p><strong data-translate="start_label"></strong> <?= escape($event['event_start']) ?></p>
                            <p><strong data-translate="end_label"></strong> <?= escape($event['event_end']) ?></p>
                            <p><strong data-translate="location_label"></strong> <?= escape($event['location']) ?></p>
                            <p><strong data-translate="vehicles_label"></strong> <?= escape($event['vehicles'] ?? 'None') ?></p>
                            <?php if ($event['is_participant'] == 0): ?>
                                <form method="POST">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <input type="hidden" name="eventAction" value="join">
                                    <button class="button is-primary" type="submit" data-translate="join_event"></button>
                                </form>
                            <?php else: ?>
                                <p data-translate="already_joined"></p>
                            <?php endif; ?>

                            <form method="POST" style="margin-top: 10px;">
                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                <input type="hidden" name="eventAction" value="invite">
                                <div class="field">
                                    <label class="label" data-translate="invite_user"></label>
                                    <div class="control">
                                        <div class="select">
                                            <select name="invitee_id" required>
                                                <option value="" data-translate="select_user"></option>
                                                <?php foreach ($allUsers as $user): ?>
                                                    <option value="<?= $user['id'] ?>"><?= escape($user['username']) ?> (<?= escape($user['email']) ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button class="button is-info" type="submit" data-translate="send_invitation"></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="tab-create" class="tab-content">
                    <h2 class="subtitle" data-translate="create_edit_event"></h2>
                    <form method="POST">
                        <div class="field">
                            <label class="label" for="eventName" data-translate="event_name"></label>
                            <div class="control">
                                <input class="input" type="text" id="eventName" name="eventName" required>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="eventType" data-translate="event_type"></label>
                            <div class="control">
                                <input class="input" type="text" id="eventType" name="eventType" required>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="eventStart" data-translate="event_start"></label>
                            <div class="control">
                                <input class="input" type="datetime-local" id="eventStart" name="eventStart" required>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="eventEnd" data-translate="event_end"></label>
                            <div class="control">
                                <input class="input" type="datetime-local" id="eventEnd" name="eventEnd" required>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="location" data-translate="location"></label>
                            <div class="control">
                                <input class="input" type="text" id="location" name="location" required>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="description" data-translate="description"></label>
                            <div class="control">
                                <textarea class="textarea" id="description" name="description" required></textarea>
                            </div>
                        </div>

                        <div class="field">
                            <div class="control">
                                <label class="checkbox" style="color: white !important;">
                                    <input type="checkbox" name="autoJoin" data-translate="auto_join">
                                    Auto Join
                                </label>
                            </div>
                        </div>


                        <div class="control">
                            <input type="hidden" name="eventAction" value="create">
                            <button class="button is-success" type="submit" data-translate="create_event"></button>
                        </div>
                    </form>
                </div>

                <div id="tab-my-events" class="tab-content">
                    <h2 class="subtitle" data-translate="my_events"></h2>
                    <?php if (empty($myEvents)): ?>
                        <p data-translate="no_events"></p>
                    <?php else: ?>
                        <?php foreach ($myEvents as $event): ?>
                            <div class="box">
                                <h3 class="title"><?= escape($event['event_name']) ?></h3>
                                <p><strong data-translate="type_label"></strong> <?= escape($event['event_type']) ?></p>
                                <p><strong data-translate="start_label"></strong> <?= escape($event['event_start']) ?></p>
                                <p><strong data-translate="end_label"></strong> <?= escape($event['event_end']) ?></p>
                                <p><strong data-translate="location_label"></strong> <?= escape($event['location']) ?></p>
                                <form method="POST">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <input type="hidden" name="eventAction" value="quit">
                                    <button class="button is-danger" type="submit" data-translate="quit_event"></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="tab-invitations" class="tab-content">
                    <h2 class="subtitle" data-translate="invitations"></h2>
                    <?php if (empty($invitations)): ?>
                        <p data-translate="no_invitations"></p>
                    <?php else: ?>
                        <?php foreach ($invitations as $invitation): ?>
                            <div class="box">
                                <h3 class="title"><?= escape($invitation['event_name']) ?></h3>
                                <p><strong data-translate="type_label"></strong> <?= escape($invitation['event_type']) ?></p>
                                <p><strong data-translate="start_label"></strong> <?= escape($invitation['event_start']) ?></p>
                                <p><strong data-translate="end_label"></strong> <?= escape($invitation['event_end']) ?></p>
                                <p><strong data-translate="location_label"></strong> <?= escape($invitation['location']) ?></p>
                                <p><strong data-translate="invited_by_label"></strong> <?= escape($invitation['inviter_name']) ?></p>
                                <form method="POST">
                                    <input type="hidden" name="invitation_id" value="<?= $invitation['invitation_id'] ?>">
                                    <input type="hidden" name="eventAction" value="join_from_invitation">
                                    <button class="button is-success" type="submit" data-translate="join_event"></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
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
