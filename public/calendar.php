<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || !is_array($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$dsn = 'mysql:host=db;dbname=helix_db;charset=utf8';
$username = 'root';
$password = 'root_password';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<p data-translate='database_connection_failed'>Database connection failed: " . $e->getMessage() . "</p>");
}

$userId = $_SESSION['user_id'];

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$month = max(1, min(12, $month));
$year = max(1970, min(2100, $year));

$translationsJson = file_get_contents('../assets/js/translation.js');
$translations = json_decode($translationsJson, true);

function translate($key, $translations) {
    return $translations[$key] ?? $key;
}

$stmt = $pdo->prepare("
    SELECT events.* 
    FROM events 
    JOIN event_participants ON events.id = event_participants.event_id 
    WHERE event_participants.user_id = :user_id
    AND (MONTH(events.event_start) = :month OR MONTH(events.event_end) = :month)
    AND (YEAR(events.event_start) = :year OR YEAR(events.event_end) = :year)
");
$stmt->execute(['user_id' => $userId, 'month' => $month, 'year' => $year]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatEventDate($date) {
    $datetime = new DateTime($date);
    return $datetime->format('Y-m-d H:i');
}

function generateCalendar($month, $year, $events, $translations) {
    $firstDayOfMonth = new DateTime("$year-$month-01");
    $totalDays = (int)$firstDayOfMonth->format('t');
    $firstDayOfWeek = (int)$firstDayOfMonth->format('w');

    $daysOfWeek = [
        'day_Sun',
        'day_Mon',
        'day_Tue', 
        'day_Wed',
        'day_Thu',
        'day_Fri',
        'day_Sat' 
    ];

    $calendarHtml = '<table class="calendar-table"><thead><tr>';

    foreach ($daysOfWeek as $day) {
        $calendarHtml .= "<th data-translate='$day'>" . translate($day, $translations['days']) . "</th>";
    }
    $calendarHtml .= '</tr></thead><tbody><tr>';

    if ($firstDayOfWeek > 0) {
        $calendarHtml .= str_repeat('<td class="empty"></td>', $firstDayOfWeek);
    }

    $eventsByDay = [];
    foreach ($events as $event) {
        $startDate = new DateTime($event['event_start']);
        $endDate = new DateTime($event['event_end']);

        if ($startDate->format('m') == $month || $endDate->format('m') == $month) {
            $eventStartDay = (int)$startDate->format('d');
            $eventEndDay = (int)$endDate->format('d');

            for ($day = $eventStartDay; $day <= $eventEndDay && $day <= $totalDays; $day++) {
                $eventDate = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                $eventsByDay[$eventDate][] = $event;
            }
        }
    }

    $currentDay = 1;
    while ($currentDay <= $totalDays) {
        $currentDate = "$year-$month-" . str_pad($currentDay, 2, '0', STR_PAD_LEFT);

        $calendarHtml .= "<td class='day'>";
        $calendarHtml .= "<div class='day-number'>$currentDay</div>";

        if (isset($eventsByDay[$currentDate])) {
            foreach ($eventsByDay[$currentDate] as $event) {
                $calendarHtml .= "<div class='event' onclick='showEventDetails(" . json_encode($event) . ")'>";
                $calendarHtml .= htmlspecialchars($event['event_name']);
                $calendarHtml .= "</div>";
            }
        }

        $calendarHtml .= "</td>";
        $currentDay++;

        if (($firstDayOfWeek + $currentDay - 1) % 7 == 0) {
            $calendarHtml .= '</tr><tr>';
        }
    }

    $remainingCells = 7 - (($firstDayOfWeek + $totalDays) % 7);
    if ($remainingCells < 7) {
        $calendarHtml .= str_repeat('<td class="empty"></td>', $remainingCells);
    }

    $calendarHtml .= '</tr></tbody></table>';

    return $calendarHtml;
}

$monthName = DateTime::createFromFormat('!m', $month)->format('F');
$translatedMonthName = translate('months.' . $monthName, $translations);

//echo "<h2 data-translate='months." . $monthName . "'>" . $translatedMonthName . " " . $year . "</h2>";


$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        $title = "Event Calendar - ATD";
        include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/head.php');
    ?>
    <link rel="stylesheet" href="/assets/css/calendar.css">
    <script src="/assets/js/translation.js"></script>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7); /* Black w/ opacity */
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: black;
            color: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 500px;
            width: 90%;
            position: relative;
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
            animation: animatetop 0.4s;
        }

        @keyframes animatetop {
            from {top: -300px; opacity: 0} 
            to {top: 0; opacity: 1}
        }

        .modal .close {
            color: white;
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
        }

        .modal-content strong {
            color: white;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>
        <main>
            <div class="content">
                <div class="container is-max-desktop">
                    <h1 class="title has-text-centered" data-translate="your_event_calendar">Your Event Calendar</h1>
                    
                    <div class="navigation has-text-centered">
                        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>">&lt; 
                            <span data-translate="months.<?= date('F', mktime(0, 0, 0, $prevMonth, 1, $prevYear)) ?>">
                                <?= translate('months.' . date('F', mktime(0, 0, 0, $prevMonth, 1, $prevYear)), $translations) ?>
                            </span> 
                            <?= $prevYear ?>
                        </a>
                        
                        <span data-translate="months.<?= date('F', mktime(0, 0, 0, $month, 1, $year)) ?>">
                            <?= translate('months.' . date('F', mktime(0, 0, 0, $month, 1, $year)), $translations) ?> 
                        </span> <?= $year ?>
                        
                        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>">
                            <span data-translate="months.<?= date('F', mktime(0, 0, 0, $nextMonth, 1, $nextYear)) ?>">
                                <?= translate('months.' . date('F', mktime(0, 0, 0, $nextMonth, 1, $nextYear)), $translations) ?>
                            </span> 
                            <?= $nextYear ?> &gt;
                        </a>
                    </div>
                    
                    <div class="buttons has-text-centered">
                        <a href="generate_csv.php?download=this-week" data-translate="download_this_week">Download This Week</a>
                        <a href="generate_csv.php?download=next-week" data-translate="download_next_week">Download Next Week</a>
                    </div>
                    
                    <div class="calendar-container">
                        <?= generateCalendar($month, $year, $events, $translations); ?>
                    </div>
                    
                    <?php if (empty($events)): ?>
                        <p class="has-text-centered" data-translate="no_events_participation">You are not participating in any events this month.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
    </div>

    <div id="eventModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalEventName"></h2>
            <p><strong>Type:</strong> <span id="modalEventType"></span></p>
            <p><strong>Location:</strong> <span id="modalEventLocation"></span></p>
            <p><strong>Start:</strong> <span id="modalEventStart"></span></p>
            <p><strong>End:</strong> <span id="modalEventEnd"></span></p>
            <p id="modalEventDescription"></p>
        </div>
    </div>

    <script>
        function showEventDetails(event) {
            document.getElementById('modalEventName').textContent = event.event_name;
            document.getElementById('modalEventType').textContent = event.event_type;
            document.getElementById('modalEventLocation').textContent = event.location;
            document.getElementById('modalEventStart').textContent = event.event_start;
            document.getElementById('modalEventEnd').textContent = event.event_end;
            document.getElementById('modalEventDescription').textContent = event.description;
            document.getElementById('eventModal').style.display = 'flex'; 
        }

        function closeModal() {
            document.getElementById('eventModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('eventModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>
