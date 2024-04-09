### Sammlung von PHP-Skripten zum Import der Userdaten aus der LUSD in eine Nextcloud

---

Die Sammlung enthält Skripte, um die Benutzerverwaltung einer Nextcloud, insbesondere für hessische Schulen zu vereinfachen. Für den Import der Benutzer können die gleichen Excel-Dateien verwendet werden, die für den Benutzerimport in das Schulportal Hessen verwendet werden. Darüber hinaus, können auch selbst erstellte Excel-Dateien verwendet werden, solange sie die entsprechenden Spaltenköpfe enthalten.

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

Falls sie noch nicht geschehen, wechseln Sie in das Installationsverzeichnis Ihrer Nextcloud. Der Befehl,

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

Konfiguration

Für die Anpassung auf Ihre Bedürfnisse stehen in der Datei **importConfig.php** folgende Optionen bereit:

- **'Lehrer_Quota' => '10 GB'**  
  Größe des für Lehrkräfte zur Verfügung gestellten Speicherplatzes.
- **Schueler_Quota'1 => '2 GB'**  
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
- ***

#### Ausführen der Skripte

Zum Ausführen der Skripte ist folgende allgemeine Syntax notwendig:

```sh
sudo -u www-data php <skriptname.php> <option>
```

#### Import von Schülern

Für den Import von Schülern wird eine Excel-Datei mit mindestens den folgenden Spalten benötigt:  
Schueler_Nachname, Schueler_Vorname, Schueler_Geburtsdatum, Klassen_Klassenbezeichnung.  
Diese Spaltenbezeichnungen stammen aus der LUSD, sodass ein entsprechender Export nicht angepasst werden muss.
