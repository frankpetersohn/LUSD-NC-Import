<?php

/**
Lernendenimport aus der LUSD.   
**/



// Pfade zur Nextcloud-Installation und Konfiguration
require_once __DIR__.'/lib/base.php';
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/lib/composer/autoload.php';
require_once __DIR__.'/3rdparty/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

 //User aus dem LUSD-Import
$klassenliste=[]; // Gruppen aus dem LUSD-Import
$ncGroups=[];


// Excel-Datei einlesen
$filename = $argv[1];
logMsg(' #### Starte das Löschen von Gruppen mit '.$argv[1].' ####');
$spreadsheet = IOFactory::load($filename);
$sheet = $spreadsheet->getActiveSheet();
$sheetData = $sheet->toArray(null, true, true, true);#



foreach ($sheetData as $row){
    try{ 
        
        $groupManager = \OC::$server->getGroupManager();
        if ($groupManager->groupExists($row['A'])) {
            $groupManager->deleteGroup($row['A']);
            logMsg('Gruppe '.$row['A'].'wurde gelöscht');
        } else {
            logMsg('Gruppe '.$row['A'].'nicht gefunden');
        }
  
    }catch(Throwable $e){
        logMsg('Fehler beim Löschen der Gruppe '.$row['A'].' : '.$e->getMessage());
    }
}

//Funktion für das Schreiben der Log-Datei
function logMsg($msg){
	
    $logfile = '/var/www/html/susImport_'. date("y-m-d") . '.log';
	$log= date("y-m-d H:i:s.").': '.$msg.PHP_EOL;
	error_log($log, 3, $logfile);
  
}



?>
