<?php

function getLanguageName($langCode) {
    $languageNames = [
        'fr' => 'Français',
        'en' => 'English',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'zh' => '中文',
        'jp' => '日本語',
        'ru' => 'Русский',
        'nl' => 'Nederlands',
        'ar' => 'العربية',
        'hi' => 'हिन्दी',
        'ko' => '한국어',
        'tr' => 'Türkçe',
        'sv' => 'Svenska',
        'da' => 'Dansk',
        'fi' => 'Suomi',
        'no' => 'Norsk',
        'pl' => 'Polski',
        'ro' => 'Română',
        'el' => 'Ελληνικά',
        'cs' => 'Čeština',
        'hu' => 'Magyar',
        'bg' => 'Български',
        'uk' => 'Українська',
        'vi' => 'Tiếng Việt',
        'th' => 'ไทย',
        'ms' => 'Bahasa Melayu',
        'id' => 'Bahasa Indonesia',
        'he' => 'עברית',
    ];

    return $languageNames[$langCode] ?? strtoupper($langCode);
}

$availableLanguages = [];
$dir = '../languages/';

if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            $langCode = pathinfo($file, PATHINFO_FILENAME);
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $availableLanguages[$langCode] = getLanguageName($langCode);
            }
        }
        closedir($dh);
    }
}

header('Content-Type: application/json');
echo json_encode($availableLanguages);
?>
