### Sammlung von PHP-Skripten zum Import der Userdaten aus der LUSD in eine Nextcloud

---

Die Sammlung enthält  Skripte, um die Benutzerverwaltung einer Nextcloud, insbesondere für hessische Schulen zu vereinfachen. Für den Import der Benutzer können die gleichen Excel-Dateien verwendet werden, die für den Benutzerimport in das Schulportal Hessen verwendet werden.  Darüber hinaus, können auch selbst erstellte Excel-Dateien verwendet werden, solange sie die entsprechenden Spaltenköpfe enthalten.  
Beim Import werden Gruppen für Schüler und Lehrkräfte sowie für jede Klasse angelegt. Die Accounts der User, welche nicht in der Importdatei enthalten sind, werden deaktiviert.  Die Gruppenzugehörigkeit von vorhandenen Usern wird bei jedem Import überprüft und angepasst. Das Speicherkontingent kann für Lehrkräfte und Schüler festgelegt werden. Am Ende des Import wird eine Logdatei sowie eine CSV-Datei mit den Erstanmeldedaten in das Verzeichnis des Administrators gelegt.

---

#### Vorbereiten der Nextcloud-Instanz

Je nach Installationsart, kann es sein, dass Composer noch nicht installiert ist. Um Composer zu installieren, wechselt man ins Installationsverzeichnis der Nextcloud und installiert Composer wie folgt:

```
curl -sS https://getcomposer.org/installer | php
```

Nun kann phpoffice/phpspreadsheet, das zum Einlesen der Excel-Exporte aus der LUSD benötigt wird mit Composer installiert werden:

```sh
./composer.phar require phpoffice/phpspreadsheet	
```

##### Installation der Skripte

Falls sie noch nicht geschehen, wechseln Sie in das Installationsverzeichnis Ihrer Nextcloud. Der  Befehl,

```bash
git clone https://github.com/frankpetersohn/LUSD-NC-Import.git
```

klont das Repository auf Ihren Rechner.

##### Update

Die Skripte befinden sie in der Weiterentwicklung. Um auf dem neusten Stand zu bleiben, können sie mit

```bash
git pull
```

Ihre Skripte auf dem neusten Stand halten.

##### 

#### Konfiguration

Für die Anpassung auf Ihre Bedürfnisse stehen in der Datei **importConfig.php** folgende Optionen bereit:

- **'Lehrer_Quota' => '10 GB'**  
  Größe des für Lehrkräfte zur Verfügung gestellten Speicherplatzes.
- **Schueler_Quota'1  => '2 GB'**  
  Größe des für Schüler zur Verfügung gestellten Speicherplatzes.
- '**logFile' => 'importLog\_' . date("y-m-d") . '.txt'**  
  Name der Logdatei, die im Verezichnisses des Admins angelegt wird.
- **'nextcloudPath' => '/var/www/html'**  
  Installationspfad der Nextcloud
- **'pwExportFile' => 'Erstpasswoerter\_' . date("y-m-d_h:i") . '.csv',**  
  Bezeichung für die CSV Datei in der die Erstpasswörter gespeichert werden
- **'klassenPraefix' => 'Kl\_',**  
  Wird der Klassengruppe vorangestellt. Wichtig, um Klassengruppen von lokal angelegten Gruppen unterscheiden zu können.
- **'klassenSuffix' => '',**  
  Wird der Klassengruppe nachgestellt
- **'AdminUser' => 'Admin'**  
  Benutzername des Admins. In seinen Ordner werden die Logdateien geschrieben.
- 

---

#### Ausführen der Skripte

Zum Ausführen der Skripte wechseln Sie, in den Sie die Skripte heruntergeladen haben und führen sie mit folgender Syntax aus:

```sh
sudo -u www-data php <skriptname.php> <option>
```

#### Import von Schülern

Für den Import von Schülern wird eine Excel-Datei mit mindestens den folgenden Spalten benötigt:  
***Schueler_Nachname, Schueler_Vorname, Schueler_Geburtsdatum, Klassen_Klassenbezeichnung.***  
Diese Spaltenbezeichnungen stammen aus der LUSD, sodass ein entsprechender Export nicht angepasst werden muss. Die Reihenfolge der Spalten ist dabei nicht relevant.

Das folgende Beispiel zeigt den Aufruf des Importskriptes für Schüler. Die Importdatei mit den Schülerdaten befindet sich dabei im Verzeichnis des Users Admin.

```
sudo -u www-data php importSuS.php ../data/admin/files/susimort.xlsx
```

###### Weitere Hinweise:

- Der Anmeldename der Schüler hat die Syntax ***Schueler_Vorname.Schueler_Nachname.***
- Das Passwort der Schüler besteht aus den Initialen und dem Geburtsdatum ohne Trennzeichen.  
  *Beispiel: Hans Werner.Mueler geb. 18.03.2005 => Passwort: hwm18032005*
- ⚠️ Es kann vorkommen, dass das Passwort bereits in einer Passworttabelle geführt wird und deshalb das Anlegen des Users fehlschlägt. Kontrollieren Sie deshalb unbedingt in der Logdatei, ob alle Schüler ordnungsgemäß angelegt wurden.
- Die Schüler werden in die Gruppe *Schueler* aufgenommen. Darüber hinaus, werden für jede Klasse jeweils eine Gruppe erstellt. Die Bezeigung der Klassengruppen setzt sich zusammen aus dem Suffix, der Klassenbezeichnung aus der Importdatei und dem Präfix. Präfix und Suffix können optional in der importConfig.php angegeben werden.  Sie sollten unbedingt mindestens einen dieser beiden Werte setzen, da über diese Zusätze die Klassengruppen erkannt werden. So wird bei einem Klassenwechsel des Schülers bei einem erneuten Import auch die Gruppenzugehörigkeit angepasst.
- Am Ende des Importvorganges wir eine CSV-Datei mit den Erstpasswörter der Schüler in das Verzeichnis des in der *importConfig.php* angegebenen AdminUser gelegt.
- ℹ️ Schüler,  die der Gruppe Schueler angehören, aber nicht in der Importdatei enthalten sind, werden deaktiviert. So können ausgeschiedene Schüler später einfacher gelöscht, oder wieder aktiv gesetzt werden. 

#### Import von Lehrkräften

Für den Import von Lehrkräften wird eine Excel-Datei mit mindestens den folgenden Spalten benötigt:  
**Nachname, Vorname, Geburtsdatum, Klassenlehrer_Klasse, Klassenlehrer_Vertreter_Klasse, Lehrer_Kuerzel**  
Diese Spaltenbezeichnungen stammen aus der LUSD, sodass ein entsprechender Export nicht angepasst werden muss. Die Reihenfolge der Spalten ist dabei nicht relevant.

Das folgende Beispiel zeigt den Aufruf des Importskriptes für Schüler. Die Importdatei mit den Schülerdaten befindet sich dabei im Verzeichnis des Users Admin.

```
sudo -u www-data php importLuL.php ../data/admin/files/susimort.xlsx
```

###### [\#](https://cloud.berufsschulcampus.de/apps/files/files/3106?dir=/SEK&openfile=true#h-weitere-hinweise "Verweis zu diesem Abschnitt") Weitere Hinweise:

- Der Anmeldename der Lehrkräfte hat die Syntax ***Vorname.Nachname.***
- Das Passwort der Lehrkräfte besteht aus den Initialen und dem Geburtsdatum ohen Trennzeichen.  
  *Beispiel: Hans Werner.Mueler geb. 18.03.2005 => Passwort: hwm18032005*
- ⚠️ Es kann vorkommen, dass das Passwort bereits in einer Passworttabelle geführt wird und deshalb das Anlegen des Users fehlschlägt. Kontrollieren Sie deshalb unbedingt in der Logdatei, ob alle Schüler ordnungsgemäß angelegt wurden.
- Die Lehrkräfte werden in die Gruppe *Lehrer*  aufgenommen. Zusätzlich werde Lehrkräfte den Klassengruppen aus den Feldern Klassenlehrer_Klasse und Klassenlehrer_Vertreter_Klasse hinzugefügt. Die Bezeigung der Klassengruppen setzt sich zusammen aus dem Suffix, der Klassenbezeichnung aus der Importdatei und dem Präfix. Präfix und Suffix können optional in der importConfig.php angegeben werden.  Sie sollten unbedingt mindestens einen dieser beiden Werte setzen, da über diese Zusätze die Klassengruppen erkannt werden. 
- Am Ende des Importvorganges wir eine CSV-Datei mit den Erstpasswörter der Schüler in das Verzeichnis des in der *importConfig.php* angegebenen AdminUser gelegt.
- ℹ️ Lehrkräfte,  die der Gruppe Lehrer angehören, aber nicht in der Importdatei enthalten sind, werden deaktiviert. 

#### Deaktivieren von Benutzern 

Mit dem Skript [disableGroupUsers.php](https://github.com/frankpetersohn/LUSD-NC-Import/blob/master/disableGroupUsers.php "disableGroupUsers.php") können Mitglieder einer Gruppe deaktiviert werden.   
Die Syntax dazu lautet:

```
sudo -u www-data php disableGroupUsers.php <Gruppenname>
```

#### Löschen deaktivierter Benutzer

Mit dem Skript [delDisabledUsers.php](https://github.com/frankpetersohn/LUSD-NC-Import/blob/master/delDisabledUsers.php "delDisabledUsers.php") werden deaktivierte Benutzer gelöscht. Als optionalen Parameter kann dabei einen Gruppennamen übergeben. Bei Aufruf des Skriptes ohne den Parameter werden alle in der Nextcloud deaktivierten Benutzer gelöscht, mit Parameter werden nur die deaktivierten Benutzer mit der entsprechenden Gruppenzugehörigkeit gelöscht.  
Der Aufruf lautet:

```
sudo -u www-data php delDisabledUsers.php [Gruppenname]
```

#### Gruppen löschen

Das Skript [delGroups.php](https://github.com/frankpetersohn/LUSD-NC-Import/blob/master/delGroups.php "delGroups.php") löscht Gruppen, die es aus einer Excel-Datei ausliest. Dafür wird eine Excel-Datei genutzt, welche die Namen der zu löschenden Gruppen in der ersten Spalte ohne Spaltenüberschrift enthält.

Der beispielhafte Aufruf lautet:

```
sudo -u www-data php delGroups.php ../data/admin/files/loeschgruppen.xlsx
```

::: info
Es werden nur die Gruppen gelöscht, nicht die Benutzer, die der Gruppe angehören. So können  überflüssige Klassengruppen einfach gelöscht werden.

:::
