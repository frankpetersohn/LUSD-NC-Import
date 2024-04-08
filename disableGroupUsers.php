<?php

/**
Deaktivierung aller Benutzer einer Gruppe.   
 **/



// Pfade zur Nextcloud-Installation und Konfiguration
require_once __DIR__ . '/lib/base.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/composer/autoload.php';
require_once __DIR__ . '/3rdparty/autoload.php';
require_once __DIR__ . '/importConfig.php';
require_once __DIR__ . '/config/config.php';

$dataDirectory = \OC::$server->getConfig()->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data');

logMsg(' ####### Starte Deaktivierung der Benutzer der Gruppe: ' . $argv[1] . ' #######');
$ncUsers = [];
try {

    if (isset($argv[1])) {
        $grp = \OC::$server->getGroupManager()->get($argv[1]);
        $users = $grp->searchUsers('');
    } else {
        echo 'Fehlender Parameter Gruppe' . PHP_EOL;
        die;
    }


    // Benutzer ids 
    foreach ($users as $user) {

        if ($user->isEnabled() == true) {
            $user->setEnabled(false);
            logMsg('Benutzer ' . $user->getUID() . ' wurde deaktivert');
        }
    }
} catch (Exception $e) {
    logMsg('Fehler: ' . $e->getMessage());
    echo 'Fehler: ' . $e->getMessage() . PHP_EOL;
}




logMsg(' ####### Deaktivierung von Benutzer beendet #######');

//Funktion für das Schreiben der Log-Datei
function logMsg($msg)
{

    global $importConfig, $dataDirectory, $storage;
    try {

        $rootFolder = \OC::$server->getRootFolder();
        $userFolder = $rootFolder->getUserFolder($importConfig['AdminUser']);
        if (!$userFolder->nodeExists($importConfig['logFile'])) {
            $file = $userFolder->newFile($importConfig['logFile']);
            echo 'Logfile ' . $importConfig['logFile'] . ' erstellt' . PHP_EOL;
            $file->putContent('Erstellt: ' . date("y-m-d H:i:s.") . PHP_EOL);
        }
        if ($userFolder->nodeExists($importConfig['logFile'])) {

            $node = $userFolder->get($importConfig['logFile']);

            if ($node instanceof \OCP\Files\File) {
                $content = $node->getContent();
                $log = date("y-m-d H:i:s.") . ': ' . $msg . PHP_EOL;
                $content .= $log;
                $node->putContent($content);
            }
        }
    } catch (Exception $e) {

        echo 'Fehler beim Erstellen des Logfiles: ' . $e . PHP_EOL;
        exit;
    }
}
