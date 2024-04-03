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

$importUsers=[]; //User aus dem LUSD-Import
$importGroups=[]; // Gruppen aus dem LUSD-Import
$ncGroups=[];
$ncUsers=[];

// Excel-Datei einlesen
$filename = $argv[1];
logMsg(' ####### Starte Schülerimport mit '.$argv[1].' #######');
if(!file_exists($filename)){
	logMsg('Datei '.$filename.' nicht gefunden');
	echo 'Datei '.$filename.' nicht gefunden '.PHP_EOL;	
	exit;
}

$spreadsheet = IOFactory::load($filename);
$sheet = $spreadsheet->getActiveSheet();
$sheetData = $sheet->toArray(null, true, true, true);


$z=0;
foreach ($sheetData as $row){
	$z++;
	if($z <= 1)continue;
	$sus=[];
	$sus['Vorname']=$row["B"];
	$sus['Nachname']=$row["A"];
	$sus['Gebdatum']=$row['C'];
	$sus['Geschlecht']=$row['D'];
	$sus['Klasse']=$row['E'];
	array_push($importUsers,$sus);
	if(!in_array($row['E'],$importGroups) && $row['E']!=null ){
		array_push($importGroups, $row['E']);
	}
}



// Gruppendaten abrufen
$groups = \OC::$server->getGroupManager()->search('');


// Alle Gruppen ausgeben
foreach ($groups as $group) {
    array_push($ncGroups, $group->getGID());
}

// Schülergruppe anlegen
if(!in_Array('Schueler', $ncGroups)){
	\OC::$server->getGroupManager()->createGroup('Schueler');
	logMsg('Gruppe Schueler angelegt');
}

//Klassengruppen anlegen
foreach ($importGroups as $grp){
	if(!in_Array($grp, $ncGroups)){
		 \OC::$server->getGroupManager()->createGroup($grp);
		logMsg('Gruppe '.$grp.' angelegt');
	}

}

$users = \OC::$server->getUserManager()->search('');

// Benutzer ausgeben
foreach ($users as $user) {
	array_push($ncUsers,$user->getUID());
   // echo "Benutzer: " . $user->getUID() . "\n";
}


//Nicht in der Importdatei enthaltene Schüler deaktivieren
try{
$schuelerGrp = \OC::$server->getGroupManager()->get('Schueler');
$schueler = $schuelerGrp->searchUsers('');

	foreach ($schueler as $sus){
		$name = explode('.', $sus->getUID());
		if(!susExists($importUsers, $name[0], $name[1]) && $sus->isEnabled()){
			$sus->setEnabled(false);
			logMsg('User '.$sus->getUID().' wird deaktiviert');
		}

	}
}catch (Throwable $e) {
	logMsg('Fehler beim Deaktivieren von Schülern: '.$e);
}


// Neue Schüler anlegen und den Gruppen hinzufügen
foreach ($importUsers as $usr){
	//$uid = str_replace(' ','-',$usr['Vorname']).'.'.str_replace(' ','-',$usr['Nachname']);
	$uid = umlautepas($usr['Vorname'].'.'.$usr['Nachname']);
	
	$pwd=strtolower(getInitialen(umlautepas($usr['Vorname']))).strtolower(getInitialen(umlautepas($usr['Nachname']))).str_replace('.','',$usr['Gebdatum']);
		
	if(!in_array($uid, $ncUsers)){
	
		try{
			if(!\OC::$server->getUserManager()->userExists($uid)){
				$user = \OC::$server->getUserManager()->createUser($uid,$pwd);
				echo 'erstelle '.$uid.PHP_EOL;
				logMsg('User '.$uid.' mit Passwort '.$pwd.' erstellt');	
				
				$grp = \OC::$server->getGroupManager()->get('Schueler');
				$grp->addUser($user);
				logMsg('User '.$uid.' wurde der Gruppe Schueler hinzugefügt');	
				
				$grp = \OC::$server->getGroupManager()->get($usr['Klasse']);
				$grp->addUser($user);
				logMsg('User '.$uid.' wurde der Gruppe '.$usr['Klasse'].' hinzugefügt');	
			}
		}catch (Throwable $e) {
			logMsg('User '.$uid.' erstellt ist fehlgeschlagen: '.$e);
		}
	
	}
	
	//Überprüft die Gruppenzugehörigkeit und korrigiert diese bei Abweichungen

	/**TODO:
	 * 
	 */
	if(\OC::$server->getUserManager()->userExists($uid)){
		try{
			$pupil = \OC::$server->getUserManager()->get($uid);
		
			$grps = \OC::$server->getGroupManager()->getUserGroups($pupil);
			$pupilGroups=[];
		 	foreach ($grps as $grp){
				array_push($pupilGroups, $grp->getGID());
		 			
				if($grp->getGID() == 'Schueler' || $grp->getGID() == $usr['Klasse'])continue;
			
				if($grp->getGID() != $usr['Klasse'] ){
					$grp->removeUser($pupil);
					logMsg('User '.$uid.' wurde aus der Gruppe '.$grp->getGID() .' entfernt');
				}
				

		}

		if(!in_array('Schueler', $pupilGroups)){
			$grp = \OC::$server->getGroupManager()->get('Schueler');
			$grp->addUser($pupil);
			logMsg('User '.$uid.' wurde der Gruppe Schueler hinzugefügt');
		}
		if(!in_array($usr['Klasse'], $pupilGroups)){
			$grp = \OC::$server->getGroupManager()->get($usr['Klasse']);
			$grp->addUser($pupil);
			logMsg('User '.$uid.' wurde der Gruppe '.$usr['Klasse'].' hinzugefügt');
		}

		}catch (Throwable $e) {
					logMsg('Fehler beim Ändern der Gruppenzugehörigkeit: '.$e);
				}	

	}


}


logMsg(' #### Schülerimport abgeschlossen ####');



//Funktion für das Schreiben der Log-Datei
function logMsg($msg){
	$logfile = '/var/www/html/susImport_'. date("y-m-d") . '.log';
	$log= date("y-m-d H:i:s.").': '.$msg.PHP_EOL;
	error_log($log, 3, $logfile);
}
// Funktion für das ersetzen von Umlauten
function umlautepas($string){
  $upas = Array("ä" => "ae", "ü" => "ue", "ö" => "oe", "Ä" => "Ae", "Ü" => "Ue", "Ö" => "Oe"); 
  return strtr($string, $upas);
  }
// Alternative Funktion zum  Erstellen von Passwörtern 
function makePassword($len){
  $pwd='';
  $words = array(
    'Apfel', 'Auto', 'Ananas', 'Ball', 'Brot', 'Buch', 'Bleistift', 'Bank', 'Bus',
    'Cafe', 'Creme', 'Computer', 'Chef', 'Chor', 'Clown', 'Couch', 'Code', 'Club', 'Chaos',
    'Dach', 'Dose', 'Drache', 'Drama', 'Dame', 'Duschgel', 'Datum', 'Decke', 'Dialog', 'Dino',
    'Ei', 'Ecke', 'Ente', 'Elch', 'Eule', 'Eis', 'Erde', 'Eule', 'Echo', 'Eiche',
    'Fisch', 'Feld', 'Fenster', 'Flur', 'Flasche', 'Feder', 'Fehler', 'Feuer', 'Familie', 'Fahne',
    'Glas', 'Geld', 'Garten', 'Gabel', 'Gans', 'Golf', 'Gurt', 'Gras', 'Geschenk', 'Giraffe',
    'Haus', 'Hund', 'Hemd', 'Hut', 'Herz', 'Hof', 'Herd', 'Hase', 'Hotel', 'Hose',
    'Igel', 'Idee', 'Insel', 'Ist', 'Igel', 'Iglo', 'Irrenhaus', 'Iris', 'Ikarus', 'Idee',
    'Jahr', 'Junge', 'Jacke', 'Jagd', 'Juwel', 'Judo', 'Jod', 'Jungfrau', 'Journal', 'Jahrmarkt',
    'Keks', 'Kaffee', 'Käse', 'Korb', 'Kran', 'Kopf', 'Kuchen', 'Karte', 'Kerze', 'Kino',
    'Lampe', 'Löffel', 'Lager', 'Leiter', 'Löwe', 'Laterne', 'Leine', 'Lust', 'Liebe', 'Lehrer',
    'Maus', 'Messer', 'Müller', 'Mond', 'Milch', 'Mütze', 'Messer', 'Muster', 'Miete', 'Magnet',
    'Nase', 'Nacht', 'Nest', 'Nudel', 'Notiz', 'Napf', 'Nebel', 'Nerv', 'Nadel', 'Nuss',
    'Ohr', 'Ofen', 'Orange', 'Oase', 'Opa', 'Ort', 'Ober', 'Ochse', 'Oma', 'Obst',
    'Pizza', 'Papier', 'Pilz', 'Park', 'Pflanze', 'Pfanne', 'Pferd', 'Puppe', 'Pfote', 'Pfad',
    'Quark', 'Qual', 'Quelle','Qualle',
    'Rose', 'Reis', 'Rabe', 'Ratte', 'Rad', 'Rind', 'Rasen', 'Regen', 'Ruhe', 'Rakete',
    'Sonne', 'Spiel', 'Stern', 'Schuh', 'Sand', 'Stuhl', 'Schule', 'Straße', 'Sonne', 'Salz',
    'Tisch', 'Tee', 'Tür', 'Tasse', 'Tanz', 'Tier', 'Tafel', 'Traum', 'Teppich', 'Tonne',
    'Uhr', 'Ufer', 'Umsatz', 'Umzug', 'Ufer', 'Ufo', 'Unfall', 'Ufer', 'Uhu', 'Uniform',
    'Vogel', 'Vase', 'Vorhang', 'Vater', 'Vase', 'Vogel', 'Vogel', 'Vogel', 'Vogel', 'Vogel',
    'Wurm', 'Wald', 'Welle', 'Würfel', 'Wagen', 'Wunde', 'Wunsch', 'Wolke', 'Weg', 'Welle',
    'Zahn', 'Zebra', 'Zirkus', 'Zauber', 'Zelt', 'Zucker', 'Ziege', 'Zange', 'Ziege', 'Ziel'
	);
	for($i=0;$i<$len;$i++){
		$pwd=$pwd.$words[rand(0,sizeof($words)-1)];
	}
	return $pwd;
	

  }
  
function getInitialen($string) {
    $worte = explode(' ', $string);
    $initialen = '';

    foreach ($worte as $wort) {
        $initialen .= $wort[0];
    }

    return $initialen;
}
// Funktion zur Überprüfung, ob ein Schüler im Array existiert
function susExists($personen, $vorname, $nachname) {
    foreach ($personen as $person) {
        if ($person['Vorname'] === $vorname && $person['Nachname'] === $nachname) {
            return true;
        }
    }
    return false;
}
?>
