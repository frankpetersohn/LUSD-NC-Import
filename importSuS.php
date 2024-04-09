<?php

/**
Lernendenimport aus der LUSD.   
 **/



// Pfade zur Nextcloud-Installation und Konfiguration

require_once __DIR__ . '/importConfig.php';
require_once $importConfig['nextcloudPath'] . '/config/config.php';
require_once $importConfig['nextcloudPath'] . '/lib/base.php';
require_once $importConfig['nextcloudPath'] . '/config/config.php';
require_once $importConfig['nextcloudPath'] . '/lib/composer/autoload.php';
require_once $importConfig['nextcloudPath'] . '/3rdparty/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$dataDirectory = \OC::$server->getConfig()->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data');

$importUsers = []; //User aus dem LUSD-Import
$importGroups = []; // Gruppen aus dem LUSD-Import
$ncGroups = [];
$ncUsers = [];
$pwExportListe = [];

// Excel-Datei einlesen
$filename = $argv[1];
logMsg(' ####### Starte Schülerimport mit ' . $argv[1] . ' #######');
if (!file_exists($filename)) {
	logMsg('Datei ' . $filename . ' nicht gefunden');
	echo 'Datei ' . $filename . ' nicht gefunden ' . PHP_EOL;
	exit;
}

$spreadsheet = IOFactory::load($filename);
$sheet = $spreadsheet->getActiveSheet();
$sheetData = $sheet->toArray(null, true, true, true);


$z = 0;
$spaltenNamen = [];
foreach ($sheetData as $row) {
	$z++;
	if ($z == 1) {
		foreach ($row as $key => $value) {
			$s = [];
			if ($value != '') {
				$spaltenNamen[$key] = $value;
			}
		}
	} else {
		$sus = [];
		foreach ($row as $key => $value) {
			$sus[$spaltenNamen[$key]] = $value;
		}
		array_push($importUsers, $sus);
		$grpBezeichnung = $importConfig['klassenPraefix'] . $sus['Klassen_Klassenbezeichnung'] . $importConfig['klassenSuffix'];
		if (!in_array($grpBezeichnung, $importGroups) && $sus['Klassen_Klassenbezeichnung'] != null) {
			array_push($importGroups, $grpBezeichnung);
		}
	}
}




// Gruppendaten abrufen
$groups = \OC::$server->getGroupManager()->search('');


// Alle Gruppen ausgeben
foreach ($groups as $group) {
	array_push($ncGroups, $group->getGID());
}

// Schülergruppe anlegen
if (!in_Array('Schueler', $ncGroups)) {
	\OC::$server->getGroupManager()->createGroup('Schueler');
	logMsg('Gruppe Schueler angelegt');
}


//Klassengruppen anlegen
foreach ($importGroups as $grp) {
	if (!in_Array($grp, $ncGroups)) {
		\OC::$server->getGroupManager()->createGroup($grp);
		logMsg('Gruppe ' . $grp . ' angelegt');
	}
}

$users = \OC::$server->getUserManager()->search('');

// Benutzer ausgeben
foreach ($users as $user) {
	array_push($ncUsers, $user->getUID());
	// echo "Benutzer: " . $user->getUID() . "\n";
}


//Nicht in der Importdatei enthaltene Schüler deaktivieren
try {
	$schuelerGrp = \OC::$server->getGroupManager()->get('Schueler');
	$schueler = $schuelerGrp->searchUsers('');

	foreach ($schueler as $sus) {
		$name = explode('.', $sus->getUID());
		if (!susExists($importUsers, $name[0], $name[1]) && $sus->isEnabled()) {
			$sus->setEnabled(false);
			logMsg('User ' . $sus->getUID() . ' wird deaktiviert');
		}
	}
} catch (Throwable $e) {
	logMsg('Fehler beim Deaktivieren von Schülern: ' . $e);
}


// Neue Schüler anlegen und den Gruppen hinzufügen
foreach ($importUsers as $usr) {
	//$uid = str_replace(' ','-',$usr['Vorname']).'.'.str_replace(' ','-',$usr['Nachname']);
	$uid = umlautepas($usr['Schueler_Vorname'] . '.' . $usr['Schueler_Nachname']);

	$pwd = strtolower(getInitialen(umlautepas($usr['Schueler_Vorname']))) . strtolower(getInitialen(umlautepas($usr['Schueler_Nachname']))) . str_replace('.', '', $usr['Schueler_Geburtsdatum']);

	if (!in_array($uid, $ncUsers)) {

		try {
			if (!\OC::$server->getUserManager()->userExists($uid)) {
				$user = \OC::$server->getUserManager()->createUser($uid, $pwd);
				array_push($pwExportListe, $usr['Schueler_Nachname'] . ';' . $usr['Schueler_Vorname'] . ';' . $uid . ';' . $pwd);
				$user->setQuota($importConfig['Schueler_Quota']);
				$user->setDisplayName(umlautepas($usr['Schueler_Vorname'] . ' ' . $usr['Schueler_Nachname']));
				echo 'erstelle ' . $uid . PHP_EOL;
				logMsg('User ' . $uid . ' mit Passwort ' . $pwd . ' erstellt');

				$grp = \OC::$server->getGroupManager()->get('Schueler');
				$grp->addUser($user);
				logMsg('User ' . $uid . ' wurde der Gruppe Schueler hinzugefügt');

				$grp = \OC::$server->getGroupManager()->get($importConfig['klassenPraefix'] . $usr['Klassen_Klassenbezeichnung'] . $importConfig['klassenSuffix']);
				$grp->addUser($user);
				logMsg('User ' . $uid . ' wurde der Gruppe ' . $importConfig['klassenPraefix'] . $usr['Klassen_Klassenbezeichnung'] . $importConfig['klassenSuffix'] . ' hinzugefügt');
			}
		} catch (Throwable $e) {
			logMsg('User ' . $uid . ' erstellt ist fehlgeschlagen: ' . $e);
		}
	}

	//Überprüft die Gruppenzugehörigkeit und korrigiert diese bei Abweichungen


	if (\OC::$server->getUserManager()->userExists($uid)) {
		try {
			$pupil = \OC::$server->getUserManager()->get($uid);

			$grps = \OC::$server->getGroupManager()->getUserGroups($pupil);
			$pupilGroups = [];
			foreach ($grps as $grp) {
				array_push($pupilGroups, $grp->getGID());

				if ($grp->getGID() == 'Schueler' || $grp->getGID() == $importConfig['klassenPraefix'] . $usr['Klassen_Klassenbezeichnung'] . $importConfig['klassenSuffix']) {
					continue;
				}

				if ($importConfig['klassenPraefix'] != '' || $importConfig['klassenSuffix'] != '') {
					if ((str_starts_with($grp->getGID(), $importConfig['klassenPraefix']) || str_ends_with($grp->getGID(), $importConfig['klassenSuffix'])) && $grp->getGID() != $importConfig['klassenPraefix'] . $usr['Klassen_Klassenbezeichnung'] . $importConfig['klassenSuffix']) {
						$grp->removeUser($pupil);
						logMsg('User ' . $uid . ' wurde aus der Gruppe ' . $grp->getGID() . ' entfernt');
					}
				}
			}
			if (!in_array('Schueler', $pupilGroups)) {
				$grp = \OC::$server->getGroupManager()->get('Schueler');
				$grp->addUser($pupil);
				logMsg('User ' . $uid . ' wurde der Gruppe Schueler hinzugefügt');
			}
			if (!in_array($importConfig['klassenPraefix'] . $usr['Klassen_Klassenbezeichnung'] . $importConfig['klassenSuffix'], $pupilGroups)) {
				$grp = \OC::$server->getGroupManager()->get($importConfig['klassenPraefix'] . $usr['Klassen_Klassenbezeichnung'] . $importConfig['klassenSuffix']);
				$grp->addUser($pupil);
				logMsg('User ' . $uid . ' wurde der Gruppe ' . $importConfig['klassenPraefix'] . $usr['Klassen_Klassenbezeichnung'] . $importConfig['klassenSuffix'] . ' hinzugefügt');
			}
		} catch (Throwable $e) {
			logMsg('Fehler beim Ändern der Gruppenzugehörigkeit: ' . $e);
		}
	}
}

//Schreibe Passwortliste in Datei   
if (sizeof($pwExportListe) > 0) {

	try {

		$rootFolder = \OC::$server->getRootFolder();
		$userFolder = $rootFolder->getUserFolder($importConfig['AdminUser']);
		$filename = 'SuS_' . $importConfig['pwExportFile'];
		if (!$userFolder->nodeExists($filename)) {
			$file = $userFolder->newFile($filename);
			echo 'Erstpasswörter' . $filename . ' erstellt' . PHP_EOL;
		}

		if ($userFolder->nodeExists($filename)) {

			$node = $userFolder->get($filename);


			if ($node instanceof \OCP\Files\File) {
				$content = $node->getContent();
				foreach ($pwExportListe as $line) {
					$content .= $line . PHP_EOL;
				}

				$node->putContent($content);
			}
		}
	} catch (Exception $e) {
		logMsg('Fehler beim Erstellen des Logfiles: ' . $e . PHP_EOL);
		echo 'Fehler beim Erstellen des Logfiles: ' . $e . PHP_EOL;
		exit;
	}
}

logMsg(' #### Schülerimport abgeschlossen ####');



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
// Funktion für das ersetzen von Umlauten
function umlautepas($string)
{
	$upas = array("ä" => "ae", "ü" => "ue", "ö" => "oe", "Ä" => "Ae", "Ü" => "Ue", "Ö" => "Oe");
	return strtr($string, $upas);
}
// Alternative Funktion zum  Erstellen von Passwörtern 
function makePassword($len)
{
	$pwd = '';
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
		'Quark', 'Qual', 'Quelle', 'Qualle',
		'Rose', 'Reis', 'Rabe', 'Ratte', 'Rad', 'Rind', 'Rasen', 'Regen', 'Ruhe', 'Rakete',
		'Sonne', 'Spiel', 'Stern', 'Schuh', 'Sand', 'Stuhl', 'Schule', 'Straße', 'Sonne', 'Salz',
		'Tisch', 'Tee', 'Tür', 'Tasse', 'Tanz', 'Tier', 'Tafel', 'Traum', 'Teppich', 'Tonne',
		'Uhr', 'Ufer', 'Umsatz', 'Umzug', 'Ufer', 'Ufo', 'Unfall', 'Ufer', 'Uhu', 'Uniform',
		'Vogel', 'Vase', 'Vorhang', 'Vater', 'Vase', 'Vogel', 'Vogel', 'Vogel', 'Vogel', 'Vogel',
		'Wurm', 'Wald', 'Welle', 'Würfel', 'Wagen', 'Wunde', 'Wunsch', 'Wolke', 'Weg', 'Welle',
		'Zahn', 'Zebra', 'Zirkus', 'Zauber', 'Zelt', 'Zucker', 'Ziege', 'Zange', 'Ziege', 'Ziel'
	);
	for ($i = 0; $i < $len; $i++) {
		$pwd = $pwd . $words[rand(0, sizeof($words) - 1)];
	}
	return $pwd;
}

function getInitialen($string)
{
	$worte = explode(' ', $string);
	$initialen = '';

	foreach ($worte as $wort) {
		$initialen .= $wort[0];
	}

	return $initialen;
}
// Funktion zur Überprüfung, ob ein Schüler im Array existiert
function susExists($personen, $vorname, $nachname)
{
	foreach ($personen as $person) {
		if ($person['Schueler_Vorname'] === $vorname && $person['Schueler_Nachname'] === $nachname) {
			return true;
		}
	}
	return false;
}
