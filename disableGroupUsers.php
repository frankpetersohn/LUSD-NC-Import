<?php

/**
Löschen aller deaktivierter Benutzer.   
**/



// Pfade zur Nextcloud-Installation und Konfiguration
require_once __DIR__.'/lib/base.php';
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/lib/composer/autoload.php';
require_once __DIR__.'/3rdparty/autoload.php';
require_once __DIR__.'/importConfig.php';

logMsg(' ####### Starte Deaktivierung der Benutzer der Gruppe: '.$argv[1].' #######');
$ncUsers=[]; 
try{

    if (isset($argv[1])) {
        $grp = \OC::$server->getGroupManager()->get($argv[1]);
        $users = $grp->searchUsers('');
    }else{
       echo 'Fehlender Parameter Gruppe'.PHP_EOL;
        die;
    }


    // Benutzer ids 
    foreach ($users as $user) {
       
        if($user->isEnabled()==true){
            $user->setEnabled(false);
            logMsg('Benutzer '.$user->getUID().' wurde deaktivert');
        }


    }

}catch(Exception $e){
    logMsg('Fehler: '.$e->getMessage());
    echo 'Fehler: '.$e->getMessage().PHP_EOL;
}




logMsg(' ####### Deaktivierung von Benutzer beendet #######');

//Funktion für das Schreiben der Log-Datei
function logMsg($msg){
	global $config;
	if(!isset($config['logFile']) || $config['logFile'] == '' )return;
    $log= date("y-m-d H:i:s.").': '.$msg.PHP_EOL;
	error_log($log, 3, $config['logFile']);
}
















?>