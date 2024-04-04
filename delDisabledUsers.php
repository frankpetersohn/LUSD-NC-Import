<?php

/**
Löschen aller deaktivierter Benutzer.   
**/



// Pfade zur Nextcloud-Installation und Konfiguration
require_once __DIR__.'/lib/base.php';
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/lib/composer/autoload.php';
require_once __DIR__.'/3rdparty/autoload.php';
logMsg(' ####### Starte Löschung deaktivierter Benutzer #######');
$ncUsers=[]; 
try{

    if (isset($argv[1])) {
        $grp = \OC::$server->getGroupManager()->get($argv[1]);
        $users = $grp->searchUsers('');
    }else{
        $users = \OC::$server->getUserManager()->search('');
    }



    // Benutzer ids 
    foreach ($users as $user) {
        //array_push($ncUsers,$user->getUID());
        if($user->isEnabled()==false){
            $user->delete();
            logMsg('Benutzer '.$user->getUID().' gelöscht');
        }


    }

}catch(Exception $e){
    logMsg('Fehler: '.$e->getMessage());
    echo 'Fehler: '.$e->getMessage().PHP_EOL;
}


logMsg(' ####### Löschung deaktivierter Benutzer beendet #######');

//Funktion für das Schreiben der Log-Datei
function logMsg($msg){
	$logfile = '/var/www/html/userDelete_'. date("y-m-d") . '.log';
	$log= date("y-m-d H:i:s.").': '.$msg.PHP_EOL;
	error_log($log, 3, $logfile);
}

















?>