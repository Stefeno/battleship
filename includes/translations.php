<?php
require_once __DIR__ . '/../config/languages.php';

class Translations {
    private static $translations = [];
    private static $currentLanguage = 'it';

    public static function init() {
        self::$currentLanguage = getCurrentLanguage();
        self::loadLanguageFile();
    }

    private static function loadLanguageFile() {
        $langFile = __DIR__ . '/../languages/' . self::$currentLanguage . '.php';
        if (file_exists($langFile)) {
            self::$translations = require $langFile;
        } else {
            // Si el archivo de idioma no existe, cargar el idioma por defecto
            self::$translations = require __DIR__ . '/../languages/it.php';
        }
    }

    public static function getAll() {
        return self::$translations;
    }

    public static function getCurrentLanguage() {
        return self::$currentLanguage;
    }

    public static function setLanguage($lang) {
        if (in_array($lang, array_keys($GLOBALS['supportedLanguages']))) {
            self::$currentLanguage = $lang;
            $_SESSION['language'] = $lang; // Asegurar que se guarde en la sesión
            self::loadLanguageFile();
            return true;
        }
        return false;
    }

    public static function get($key) {
        return self::$translations[$key] ?? $key;
    }
}

// Inicializar las traducciones
Translations::init();

// Función helper para usar en las plantillas
function t($key) {
    return Translations::get($key);
}
?> 