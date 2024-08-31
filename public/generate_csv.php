<?php
session_start();

$dsn = 'mysql:host=db;dbname=helix_db;charset=utf8';
$username = 'root';
$password = 'root_password';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<p>Database connection failed: " . $e->getMessage() . "</p>");
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT events.* 
    FROM events 
    JOIN event_participants ON events.id = event_participants.event_id 
    WHERE event_participants.user_id = :user_id
");
$stmt->execute(['user_id' => $userId]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatEventDate($date) {
    $datetime = new DateTime($date);
    return $datetime->format('Y-m-d H:i');
}

function generateCsv($events, $startDate, $endDate, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $output = fopen('php://output', 'w');

    $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    fputcsv($output, $daysOfWeek);

    $eventsByDay = array_fill(0, 7, []); 

    foreach ($events as $event) {
        $eventStart = new DateTime($event['event_start']);
        $eventEnd = new DateTime($event['event_end']);

        if ($eventStart >= $startDate && $eventStart <= $endDate) {
            $dayIndex = (int) $eventStart->format('w'); 
            $eventText = $event['event_name'] . "\n" . formatEventDate($event['event_start']) . " - " . formatEventDate($event['event_end']);
            $eventsByDay[$dayIndex][] = $eventText;
        }
    }

    $maxEventsPerDay = max(array_map('count', $eventsByDay));

    for ($i = 0; $i < $maxEventsPerDay; $i++) {
        $row = [];
        for ($j = 0; $j < 7; $j++) {
            $row[] = isset($eventsByDay[$j][$i]) ? $eventsByDay[$j][$i] : '';
        }
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

if (isset($_GET['download'])) {
    $currentDate = new DateTime();
    $startDate = (clone $currentDate)->modify('this week');
    $endDate = (clone $startDate)->modify('+6 days');

    if ($_GET['download'] == 'next-week') {
        $startDate->modify('+1 week');
        $endDate->modify('+1 week');
    }
    
    generateCsv($events, $startDate, $endDate, 'events_' . $_GET['download'] . '.csv');
}
?>
