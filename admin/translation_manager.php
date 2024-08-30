<?php 
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

function deleteTranslation($langCode) {
    $filePath = "../languages/{$langCode}.json";
    if (file_exists($filePath)) {
        unlink($filePath);
        return true;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['langCode']) && isset($_POST['translations'])) {
        $langCode = $_POST['langCode'];
        $translations = json_decode($_POST['translations'], true);

        if ($langCode && $translations) {
            saveTranslation($langCode, $translations);
            $message = "Translations for {$langCode} have been saved successfully.";
        } else {
            $message = "Failed to save translations.";
        }
    }

    if (isset($_POST['newLangCode']) && isset($_POST['newTranslations'])) {
        $newLangCode = $_POST['newLangCode'];
        $newTranslations = json_decode($_POST['newTranslations'], true);

        if ($newLangCode && $newTranslations) {
            saveTranslation($newLangCode, $newTranslations);
            $message = "New translation file {$newLangCode}.json has been created successfully.";
        } else {
            $message = "Failed to create new translation file.";
        }
    }

    if (isset($_POST['deleteLangCode'])) {
        $deleteLangCode = $_POST['deleteLangCode'];
        if (deleteTranslation($deleteLangCode)) {
            $message = "Translation file {$deleteLangCode}.json has been deleted successfully.";
        } else {
            $message = "Failed to delete translation file.";
        }
    }
}

$availableLanguages = getAvailableLanguages();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/includes/head.php'); 
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
        .language-list {
            margin-top: 20px;
        }
        .language-item {
            margin-bottom: 10px;
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
    <h1 data-translate="translation_manager">Translation Manager</h1>

    <?php if (!empty($message)): ?>
        <div class="message" data-translate="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="form-section">
        <form id="translationForm" method="POST">
            <label for="langCode" data-translate="select_language_file">Select Language File:</label>
            <select name="langCode" id="langCode">
                <option value="" data-translate="select_language">-- Select Language --</option>
                <?php foreach ($availableLanguages as $lang): ?>
                    <option value="<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($lang) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="translations" data-translate="translations">Translations (JSON):</label>
            <textarea name="translations" id="translations" placeholder="JSON content will appear here..."></textarea>

            <button type="button" id="loadTranslation" data-translate="load_translation">Load Translation</button>
            <button type="submit" data-translate="save_translation">Save Translation</button>
        </form>
    </div>

    <div class="form-section">
        <h2 data-translate="create_new_translation_file">Create New Translation File</h2>
        <form id="newTranslationForm" method="POST">
            <label for="newLangCode" data-translate="new_language_code">New Language Code:</label>
            <input type="text" name="newLangCode" id="newLangCode" placeholder="es, de, it, ...">

            <label for="newTranslations" data-translate="new_translations">New Translations (JSON):</label>
            <textarea name="newTranslations" id="newTranslations" placeholder="Enter JSON content here..."></textarea>

            <button type="button" id="loadDefault" data-translate="load_default">Load Default Translation</button>
            <button type="button" id="createNewTranslation" data-translate="create_new_translation">Create New Translation</button>
        </form>
    </div>

    <div class="form-section">
        <h2 data-translate="manage_existing_files">Manage Existing Translation Files</h2>
        <div class="language-list">
            <?php foreach ($availableLanguages as $lang): ?>
                <div class="language-item">
                    <?= htmlspecialchars($lang) ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="deleteLangCode" value="<?= htmlspecialchars($lang) ?>">
                        <button type="submit" onclick="return confirm('Are you sure you want to delete this file?');" data-translate="delete">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    function translatePage(translations) {
        document.querySelectorAll('[data-translate]').forEach(function(element) {
            var key = element.getAttribute('data-translate');
            if (translations[key]) {
                element.textContent = translations[key];
            }
        });
    }

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

    document.getElementById('loadDefault').addEventListener('click', function() {
        fetch('default_keys.json')
            .then(response => response.json())
            .then(translations => {
                document.getElementById('newTranslations').value = JSON.stringify(translations, null, 4);
            })
            .catch(error => {
                alert('Error loading default translation file: ' + error);
            });
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
                body: `newLangCode=${encodeURIComponent(langCode)}&newTranslations=${encodeURIComponent(translations)}`
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

    fetch('translations.json')
        .then(response => response.json())
        .then(translations => {
            translatePage(translations);
        })
        .catch(error => {
            console.error('Error loading translations:', error);
        });
</script>

</body>
</html>
