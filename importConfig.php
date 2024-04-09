<?php

$importConfig = [
    'Lehrer_Quota' => '10 GB', //Speicherkapazität mit Leerzeichen zwischen Wert und Einheit
    'Schueler_Quota' => '2 GB', //Speicherkapazität mit Leerzeichen zwischen Wert und Einheit
    'logFile' => 'importLog4_' . date("y-m-d") . '.txt',
    'pwExportFile' => 'Erstpasswoerter_' . date("y-m-d_h:i") . '.csv',
    'klassenPraefix' => 'Kl_', // Wird der Klassengruppe vorangestellt
    'klassenSuffix' => '', //wird der Klassengruppe nachgestellt
    'nextcloudPath' => '/var/www/html',
    'AdminUser' => 'Admin',
];
