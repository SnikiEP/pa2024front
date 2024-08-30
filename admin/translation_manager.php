<?php 
// Vérifiez qu'il n'y a aucun espace ou sortie avant cette balise
include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/header.php');

function getAvailableLanguages() {
    $dir = '../languages/';
    $availableLanguages = [];

    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $langCode = pathinfo($file, PATHINFO_FILENAME);
                    $availableLanguages[] = $langCode;
                }
            }
            closedir($dh);
        }
    }

    return $availableLanguages;
}

function saveTranslation($langCode, $translations) {
    $filePath = "../languages/{$langCode}.json";
    file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $langCode = $_POST['langCode'] ?? '';
    $translations = json_decode($_POST['translations'] ?? '{}', true);

    if ($langCode && $translations) {
        saveTranslation($langCode, $translations);
        $message = "Translations for {$langCode} have been saved successfully.";
    } else {
        $message = "Failed to save translations.";
    }
}

$availableLanguages = getAvailableLanguages();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php  include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/head.php'); 
    $title = "Translation Manager - HELIX"; ?>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #1e1e1e;
            color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        h1, h2 {
            color: #ffffff;
            text-align: center;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #2e2e2e;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
            border-radius: 8px;
        }
        label {
            font-weight: bold;
            display: block;
            margin: 15px 0 5px;
            color: #dcdcdc;
        }
        select, input[type="text"], textarea, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #555;
            background-color: #3c3c3c;
            color: #ffffff;
            font-size: 16px;
        }
        button {
            background-color: #007bff;
            color: #ffffff;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            margin-top: 10px;
            padding: 10px;
            background-color: #28a745;
            color: #ffffff;
            border-radius: 4px;
        }
        textarea {
            font-family: monospace;
            height: 300px;
        }
        .form-section {
            margin-bottom: 40px;
        }
        @media (max-width: 768px) {
            .container {
                width: 100%;
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Translation Manager</h1>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="form-section">
        <form id="translationForm" method="POST">
            <label for="langCode">Select Language File:</label>
            <select name="langCode" id="langCode">
                <option value="">-- Select Language --</option>
                <?php foreach ($availableLanguages as $lang): ?>
                    <option value="<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($lang) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="translations">Translations (JSON):</label>
            <textarea name="translations" id="translations" placeholder="JSON content will appear here..."></textarea>

            <button type="button" id="loadTranslation">Load Translation</button>
            <button type="submit">Save Translation</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Create New Translation File</h2>
        <form id="newTranslationForm" method="POST">
            <label for="newLangCode">New Language Code:</label>
            <input type="text" name="newLangCode" id="newLangCode" placeholder="es, de, it, ...">

            <label for="newTranslations">New Translations (JSON):</label>
            <textarea name="newTranslations" id="newTranslations" placeholder="Enter JSON content here..."></textarea>

            <button type="button" id="createNewTranslation">Create New Translation</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('loadTranslation').addEventListener('click', function() {
        const langCode = document.getElementById('langCode').value;
        if (langCode) {
            fetch(`../languages/${langCode}.json`)
                .then(response => response.json())
                .then(translations => {
                    document.getElementById('translations').value = JSON.stringify(translations, null, 4);
                })
                .catch(error => {
                    alert('Error loading translation file: ' + error);
                });
        } else {
            alert('Please select a language file.');
        }
    });

    document.getElementById('createNewTranslation').addEventListener('click', function() {
        const langCode = document.getElementById('newLangCode').value;
        const translations = document.getElementById('newTranslations').value;

        if (langCode && translations) {
            fetch('translation_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `langCode=${encodeURIComponent(langCode)}&translations=${encodeURIComponent(translations)}`
            })
            .then(response => response.text())
            .then(data => {
                alert('New translation file created successfully.');
                location.reload();
            })
            .catch(error => {
                alert('Error creating new translation file: ' + error);
            });
        } else {
            alert('Please provide a language code and JSON content.');
        }
    });
</script>

</body>
</html>
