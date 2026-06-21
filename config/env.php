<?php

if (!function_exists('env')) {

    function env($key, $default = null)
    {
        static $env = null;

        if ($env === null) {

            $envFile = dirname(__DIR__) . '/.env';

            if (!file_exists($envFile)) {
                throw new Exception("Le fichier .env est introuvable.");
            }

            $env = parse_ini_file($envFile, false, INI_SCANNER_RAW);

            if ($env === false) {
                throw new Exception("Impossible de lire le fichier .env.");
            }
        }

        return $env[$key] ?? $default;
    }

}