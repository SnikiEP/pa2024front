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

function generateCalendar($month, $year) {
    $firstDayOfMonth = new DateTime("$year-$month-01");
    $totalDays = (int)$firstDayOfMonth->format('t');
    $firstDayOfWeek = (int)$firstDayOfMonth->format('w');
    
    $daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    
    $calendarHtml = '<table class="calendar-table"><thead><tr>';
    foreach ($daysOfWeek as $day) {
        $calendarHtml .= "<th>$day</th>";
    }
    $calendarHtml .= '</tr></thead><tbody><tr>';
    
    if ($firstDayOfWeek > 0) {
        $calendarHtml .= str_repeat('<td class="empty"></td>', $firstDayOfWeek);
    }
    $currentDay = 1;
    while ($currentDay <= $totalDays) {
        if (($firstDayOfWeek + $currentDay - 1) % 7 == 0) {
            $calendarHtml .= '</tr><tr>';
        }
        $calendarHtml .= "<td class='day'>$currentDay</td>";
        $currentDay++;
    }
    
    $remainingCells = 7 - (($firstDayOfWeek + $totalDays) % 7);
    if ($remainingCells < 7) {
        $calendarHtml .= str_repeat('<td class="empty"></td>', $remainingCells);
    }
    
    $calendarHtml .= '</tr></tbody></table>';
    
    return $calendarHtml;
}

$currentMonth = date('m');
$currentYear = date('Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        $title = "Event Calendar - ATD";
        include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/head.php');
    ?>    
    <style>
        .calendar-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #1c1c1e;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .calendar-table th, .calendar-table td {
            padding: 15px;
            text-align: center;
            color: #fff;
        }
        .calendar-table th {
            background-color: #333;
            font-weight: bold;
        }
        .calendar-table td {
            background-color: #2c2c2e;
            transition: background-color 0.3s ease;
            cursor: pointer;
            border: 1px solid #444;
        }
        .calendar-table td:hover {
            background-color: #444;
        }
        .calendar-table td.empty {
            background-color: #1c1c1e;
            cursor: default;
        }
        .calendar-table .day {
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }
        .calendar-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>
        <main>
            <div class="content">
                <div class="container is-max-desktop">
                    <h1 class="title has-text-centered">Your Event Calendar</h1>
                    <?php if (empty($events)): ?>
                        <p class="has-text-centered">You are not participating in any events.</p>
                    <?php else: ?>
                        <div class="calendar-container">
                            <?php foreach ($events as $event): ?>
                                <div class="box">
                                    <h3 class="title is-5"><?= htmlspecialchars($event['event_name']) ?></h3>
                                    <p><strong>Type:</strong> <?= htmlspecialchars($event['event_type']) ?></p>
                                    <p><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></p>
                                    <p><strong>Start:</strong> <?= formatEventDate($event['event_start']) ?></p>
                                    <p><strong>End:</strong> <?= formatEventDate($event['event_end']) ?></p>
                                    <p><?= htmlspecialchars($event['description']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <h2 class="title is-4 has-text-centered">Monthly Calendar - <?= date('F Y') ?></h2>
                    <?= generateCalendar($currentMonth, $currentYear); ?>
                </div>
            </div>
        </main>
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
    </div>
</body>
</html>
