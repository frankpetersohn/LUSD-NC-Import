<?php

/**
Lehrendeimport aus der LUSD.   
 **/


// Pfade zur Nextcloud-Installation und Konfiguration
require_once __DIR__ . '/importConfig.php';
require_once $importConfig['nextcloudPath'] . '/config/config.php';
require_once $importConfig['nextcloudPath'] . '/lib/base.php';
require_once $importConfig['nextcloudPath'] . '/config/config.php';
require_once $importConfig['nextcloudPath'] . '/lib/composer/autoload.php';
require_once $importConfig['nextcloudPath'] . '/3rdparty/autoload.php';

$dataDirectory = \OC::$server->getConfig()->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data');

use PhpOffice\PhpSpreadsheet\IOFactory;



$importLuL = []; //User aus dem LUSD-Import
$importGroups = []; // Gruppen aus dem LUSD-Import
$ncGroups = [];
$ncUsers = [];


// Excel-Datei einlesen
$filename = $argv[1];
logMsg(' ####### Starte Lehrerimport mit ' . $argv[1] . ' #######');
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
        $lul = [];
        foreach ($row as $key => $value) {

            if (($spaltenNamen[$key] == 'Klassenlehrer_Klasse' || $spaltenNamen[$key] == 'Klassenlehrer_Vertreter_Klasse') && $value != '') {
                $lul['Klassen'][] = $value;
                if (!in_array('Kl_' . $value, $importGroups)) {
                    array_push($importGroups, 'Kl_' . $value);
                }
            } else {
                $lul[$spaltenNamen[$key]] = $value;
            }
        }


        if (lulExists($importLuL, $lul['Vorname'], $lul['Nachname'], $lul['Lehrer_Kuerzel'])) {
            array_push($importLuL[$lul['Lehrer_Kuerzel']]['Klassen'], $lul['Klassen'][0]);
        } else {
            $importLuL[$lul['Lehrer_Kuerzel']] = $lul;
        }
    }
}



// Gruppendaten abrufen
$groups = \OC::$server->getGroupManager()->search('');


// Alle Gruppen ausgeben
foreach ($groups as $group) {
    array_push($ncGroups, $group->getGID());
}

// Lehrergruppe anlegen
if (!in_Array('Lehrer', $ncGroups)) {
    \OC::$server->getGroupManager()->createGroup('Lehrer');
    logMsg('Gruppe Lehrer angelegt');
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
}


//Nicht in der Importdatei enthaltene Lehrer deaktivieren
try {
    $lehrerGrp = \OC::$server->getGroupManager()->get('Lehrer');
    $lehrer = $lehrerGrp->searchUsers('');

    foreach ($lehrer as $lul) {
        $name = explode('.', $lul->getUID());
        if (!lulExists($importLuL, $name[0], $name[1]) && $lul->isEnabled()) {
            $lul->setEnabled(false);
            logMsg('User ' . $lul->getUID() . ' wird deaktiviert');
        }
    }
} catch (Throwable $e) {
    logMsg('Fehler beim Deaktivieren von Lehrern: ' . $e);
}


// Neue Lehrer anlegen und den Gruppen hinzufügen
foreach ($importLuL as $usr) {
    //$uid = str_replace(' ','-',$usr['Vorname']).'.'.str_replace(' ','-',$usr['Nachname']);
    //$uid = $usr['Lehrer_Kuerzel'];
    $uid = umlautepas($usr['Vorname'] . '.' . $usr['Nachname']);
    $pwd = strtolower(getInitialen(umlautepas($usr['Vorname']))) . strtolower(getInitialen(umlautepas($usr['Nachname']))) . str_replace('.', '', $usr['Geburtsdatum']);




    if (\OC::$server->getUserManager()->userExists($uid)) {
        try {
            //Überprüfung und Anpassunge des Speicherkontingents
            $teacher = \OC::$server->getUserManager()->get($uid);
            $quota = $teacher->getQuota();

            if ($quota != $importConfig['Lehrer_Quota']) {
                $teacher->setQuota($importConfig['Lehrer_Quota']);
                logMsg('Speicherkontingent von ' . $uid . ' auf ' . $importConfig['Lehrer_Quota'] . ' geändert');
            }
        } catch (Throwable $e) {
            logMsg('Fehler beim Ändern des Speicherkontingents: ' . $e);
        }



        //Überprüft die Gruppenzugehörigkeit und korrigiert diese bei Abweichungen

        try {
            $teacher = \OC::$server->getUserManager()->get($uid);

            $grps = \OC::$server->getGroupManager()->getUserGroups($teacher);
            $teachersGroups = [];
            foreach ($grps as $grp) {
                array_push($teachersGroups, $grp->getGID());

                if ($grp->getGID() == 'Lehrer' || in_array(str_replace('Kl_', '', $grp->getGID()), $usr['Klassen'])) continue;


                if (substr($grp->getGID(), 0, 3) == 'Kl_' && !in_array(str_replace('Kl_', '', $grp->getGID()), $usr['Klassen'])) {
                    $grp->removeUser($teacher);
                    logMsg('User ' . $uid . ' wurde aus der Gruppe ' . $grp->getGID() . ' entfernt');
                }
            }

            if (!in_array('Lehrer', $teachersGroups)) {
                $grp = \OC::$server->getGroupManager()->get('Lehrer');
                $grp->addUser($teacher);
                logMsg('User ' . $uid . ' wurde der Gruppe Lehrer hinzugefügt');
            }
            foreach ($usr['Klassen'] as $klasse) {
                if (!in_array('Kl_' . $klasse, $teachersGroups)) {
                    $grp = \OC::$server->getGroupManager()->get('Kl_' . $klasse);
                    $grp->addUser($teacher);
                    logMsg('User ' . $uid . ' wurde der Gruppe ' . 'Kl_' . $klasse . ' hinzugefügt');
                    /* if($importConfig['setGroupmanager'] == 'true'){
                        $grp->addSubAdmin($teacher, true);
                        logMsg('User ' . $uid . ' wurde der Gruppe ' . 'Kl_' . $klasse . ' als Gruppenmanager hinzugefügt');
                    }*/
                }
            }
        } catch (Throwable $e) {
            logMsg('Fehler beim Ändern der Gruppenzugehörigkeit: ' . $e);
        }
    }
    //Neue Lehrer anlegen
    if (!in_array($uid, $ncUsers)) {

        try {
            if (!\OC::$server->getUserManager()->userExists($uid)) {
                $user = \OC::$server->getUserManager()->createUser($uid, $pwd);
                $user->setDisplayName(umlautepas($usr['Vorname'] . ' ' . $usr['Nachname']));
                $user->setQuota($importConfig['Lehrer_Quota']);

                echo 'erstelle ' . $uid . PHP_EOL;
                logMsg('User ' . $uid . ' mit Passwort ' . $pwd . ' erstellt');

                $grp = \OC::$server->getGroupManager()->get('Lehrer');
                $grp->addUser($user);
                logMsg('User ' . $uid . ' wurde der Gruppe Lehrer hinzugefügt');

                foreach ($usr['Klassen'] as $klasse) {
                    $grp = \OC::$server->getGroupManager()->get('Kl_' . $klasse);
                    $grp->addUser($user);
                    logMsg('User ' . $uid . ' wurde der Gruppe ' . 'Kl_' . $klasse . ' hinzugefügt');
                }
            }
        } catch (Throwable $e) {
            logMsg('User ' . $uid . ' erstellt ist fehlgeschlagen: ' . $e);
        }
    }
}


logMsg(' #### Lehrerimport abgeschlossen ####');



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
// Funktion zur Überprüfung, ob ein Lehrer im Array existiert
function lulExists($personen, $vorname, $nachname, $Lehrer_Kuerzel = null)
{
    foreach ($personen as $person) {
        if ($Lehrer_Kuerzel == null) {
            if ($person['Vorname'] === $vorname && $person['Nachname'] === $nachname) {
                return true;
            }
        }
        if ($person['Vorname'] === $vorname && $person['Nachname'] === $nachname && $person['Lehrer_Kuerzel'] === $Lehrer_Kuerzel) {
            return true;
        }
    }
    return false;
}
