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

## Block
RRZE ShortURL um im Blockeditor Links zu verkürzen

## Verwaltung
In den Einstellungen kann Folgendes verwaltet werden:
- Services: vordefinierte Dienste 
- Customer Domains: die zulässigen Domains
- IdM: neue IdM werden automatisch erstellt. Hier kann bestimmt werden, welche erweiterten Einstellungen gestattet sind
- Statistik

## Cronjob
- rrze_shorturl_fetch_and_store_customerdomains für die Ermittlung der Customer Domains

## REST-API Endpoints (DOMAIN/wp-json/short-url/v1)
Alle Endpoints sind zugriffsgeschützt.
- /active-short-urls (GET) liefert die Paare "long_url" und "short_url" als JSON
- /shorten (POST) empfängt die Parameter und liefert das Paar 'error'(true/false) und 'txt' (short_url oder error-message) als JSON
- /categories (GET) liefert die Kategorien als JSON
- /add-category (POST) trägt eine neue Kategorie mit ggfalls Parent-Kategorie in die Datenbank ein
- /tags (GET) liefert die Schlagwörter als JSON (seit 1.1.0 deaktiviert)
- /add-tags (POST) trägt ein neues Schlagwort in die Datenbank ein (seit 1.1.0 deaktiviert)

## Installation
- Plugin auf WordPress Instanz 
- make_htaccess.php auf den Server kopieren, der die Redirects ausführen soll und über Cronjob aufrufen. Wenn make_htaccess.php nicht im Root-Verzeichnis liegt, muss der Pfad zur .htaccess angepasst werden

# Bespiel für Cronjob, der die .htaccess alle 5 Minuten aktualisiert
*/5 * * * * php /path/to/make_htaccess.php

Die .htaccess enthält alle Redirects zwischen den Kommentar-Zeilen "# BEGIN ShortURL" und "# END ShortURL".
Als erstes die Regeln für die Services, dann die User-generierten Redirects für die erlaubten Domains.
Nach "# END ShortURL" alle vorher bereits existierten Zeilen der .htacess. 
make_htaccess.php überbügelt eine vorhandene .htaccess nicht.



