# RRZE-ShortURL
WordPress-Plugin: URL shortener

## Shortcodes
[shorturl] 
Generiert ein Formular, um Links zu verkürzen.
Erweiterte Einstellungen werden auf Klick sichtbar. 

[shorturl-list]
Zeigt in einer Tabelle die URLs an. Es kann nach Kategorien und Schlagwörtern gefiltert werden. 

[shorturl-categories]
Die Verwaltung der Kategorien

[shorturl-tags] (seit 1.1.0 deaktiviert)
Die Verwaltung der Schlagwörter

[shorturl-services]
Gibt eine Tabelle mit den Services aus.

## Block
RRZE ShortURL um im Blockeditor Links zu verkürzen

## Verwaltung
In den Einstellungen kann Folgendes verwaltet werden:
- Services: vordefinierte Dienste 
- Customer Domains: die zulässigen Domains
- IdM: neue IdM werden automatisch erstellt. Hier kann bestimmt werden, welche erweiterten Einstellungen gestattet sind
- Statistik

## Defaults:
- neue IdMs haben keine erweiterten Rechte
- Gültigkeit der Links: 1 Jahr. In den Einstellungen (wp-admin/options-general.php?page=rrze-shorturl&tab=idm) kann diese für ausgewählte User (IdM) auf 5 Jahre eingestellt werden

## Cronjob
- rrze_shorturl_fetch_and_store_customerdomains für die Ermittlung der Customer Domains

## REST-API Endpoints (DOMAIN/wp-json/short-url/v1)
- /active-shorturls (GET) liefert die Paare "long_url" und "short_url" als JSON
- /shorten (POST) empfängt die Parameter und liefert das Paar 'error'(true/false) und 'txt' (short_url oder error-message) als JSON
- /get-longurl (GET) liefert zum Parameter "shortURL" den "long_url" als JSON
- /categories (GET) liefert die Kategorien als JSON
- /add-category (POST) trägt eine neue Kategorie mit ggfalls Parent-Kategorie in die Datenbank ein
- /tags (GET) liefert die Schlagwörter als JSON (seit 1.1.0 deaktiviert)
- /add-tags (POST) trägt ein neues Schlagwort in die Datenbank ein (seit 1.1.0 deaktiviert)
- /services (GET) liefert alle Services samt Regex als JSON
- /decrypt (POST) liefert den decrypted Wert vom Parameter "encrypted" (für einen Service) als JSON

## Zugriffsschutz der REST-API Endpoints:
Alle Endpoints sind zugriffsgeschützt.
- /active-shorturls : IP (Einstellbar in Settings)
- /shorten : SSO
- /categories : SSO
- /add-category : SSO
- /tags : SSO
- /add-tags : SSO
- /services : IP (Einstellbar in Settings)
- /service-decrypt : IP (Einstellbar in Settings)

## Installation
- Plugin auf WordPress Instanz
- Inhalt vom Order "Server" auf den Server ins Root-Verzeichnis kopieren, der die Redirects ausführen soll (Redirect-Server)
- Auf Redirect-Server: make_htaccess.php ein Mal aufrufen. Wenn make_htaccess.php nicht im Root-Verzeichnis liegt, muss der Pfad zur .htaccess angepasst werden

## Ablauf 
ShortURL ruft Redirect-Server auf
Format der ShortURL: "Protokoll://Redirect-Server/Path"

Check in .htaccess: 
- Gibt es zu Path eine RedirectRule? => 303 Redirect 
- Sonst: beginnt Path mit einer Nummer? => shorturl-redirect.php

shorturl-redirect.php:
- Fehlt ein GET-Parameter? (Prefix, Code) => 404 mit Message 
- Sonst: ist Prefix Service? => berechne Long-URL aus SESSION, sonst aus JSON-File, sonst update via REST-API und speichere in SESSION und in JSON-File, 303 Redirect, sonst 404 "Unbekannter Service mit $prefix"
- Sonst: ist Prefix Customer-Link? Hol long_link via REST-API, 303 Redirect wenn gefunden, dann .htaccess Update, sonst 404 "Unbekannter Link" 
- Sonst: 404 "Unbekannter Link"


## Performance
Mit der Zeit wird die .htaccess viele Zeilen für die Redirect Rules enthalten.
Es sollte keine Auswirkung auf die Performance haben, da auch .htaccess sehr viele Anweisungen sehr schnell verarbeiten kann (siehe zB "7G Firewall" https://perishablepress.com/7g-firewall/ ). Gültigkeit der Rules ist durchschnittlich 1 Jahr. Ungültige werden automatisch entfernt.
Services werden stets in shorturl-redirect.php berechnet. Dazu wird die RegEx aus der SESSION gelesen, dann notfalls im JSON-File, dann erst über die REST-API und die Entkodierung findet im Skript statt. Nur wenn Link nicht aufgeschlüsselt werden kann, wird die REST-API nach Updates abgefragt. Dabei so performant wie möglich: lightweight nur zum aktuellen Link. Redirect vor allen weiteren Anweisungen (wie weiteren REST-API-Aufruf, SESSION und JSON-File speichern, ggfalls .htaccess neu generieren).


# Bespiel für Cronjob, der die .htaccess alle 5 Minuten aktualisiert
*/5 * * * * php /path/to/make_htaccess.php

Die .htaccess enthält alle Redirects zwischen den Kommentar-Zeilen "# BEGIN ShortURL" und "# END ShortURL".
Als erstes die Regeln für die Services, dann die User-generierten Redirects für die erlaubten Domains.
Nach "# END ShortURL" alle vorher bereits existierten Zeilen der .htaccess. 
make_htaccess.php überschreibt die Anweisungen der vorhandene .htaccess nicht.



