<?php

$importConfig = [
    'Lehrer_Quota' => '10 GB', //Speicherkapazität mit Leerzeichen zwischen Wert und Einheit
    'Schueler_Quota' => '2 GB', //Speicherkapazität mit Leerzeichen zwischen Wert und Einheit
    'logFile' => 'importLog' . date("y-m-d") . '.txt',
    'pwExportFile' => 'Erstpasswoerter_' . date("y-m-d_h:i") . '.csv',
    'klassenPraefix' => 'Kl_', // Wird der Klassengruppe vorangestellt
    'klassenSuffix' => '', //wird der Klassengruppe nachgestellt
    'nextcloudPath' => '/var/www/html',
    'AdminUser' => 'Admin',
    'Lehrer-Passwort' => 'Words', // Wie soll das Lehrerpasswort gebildet werden? Mögliche Werte: Words, Initalen, Zufall
    'Schueler-Passwort' => 'Words', // Wie soll das Schülerpasswort gebildet werden? Mögliche Werte: Words, Initalen, Zufall
];
