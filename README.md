=== RRZE ShortURL ===
Contributors: rrze-webteam
Plugin URI: https://gitlab.rrze.fau.de/rrze-webteam/rrze-shorturl
Tags: shorturl, url shortener, redirect, link management
Requires at least: 6.4
Tested up to: 6.6
Requires PHP: 8.2
Stable tag: 3.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Author: RRZE Webteam
Author URI: https://blogs.fau.de/webworking/
Text Domain: rrze-shorturl
Domain Path: /languages

Plugin, um URLs zu verkürzen.


[![Aktuelle Version](https://img.shields.io/github/package-json/v/rrze-webteam/rrze-shorturl/main?label=Version)](https://github.com/RRZE-Webteam/rrze-shorturl)
[![Release Version](https://img.shields.io/github/v/release/rrze-webteam/rrze-shorturl?label=Release+Version)](https://github.com/rrze-webteam/rrze-shorturl/releases/)
[![GitHub License](https://img.shields.io/github/license/rrze-webteam/rrze-shorturl)](https://github.com/RRZE-Webteam/rrze-shorturl)
[![GitHub issues](https://img.shields.io/github/issues/RRZE-Webteam/rrze-shorturl)](https://github.com/RRZE-Webteam/rrze-shorturl/issues)
![GitHub milestone details](https://img.shields.io/github/milestones/progress-percent/RRZE-Webteam/rrze-shorturl/3)


# RRZE-ShortURL
WordPress-Plugin: URL shortener

## Shortcodes
[shorturl] 
Generiert ein Formular, um Links zu verkürzen.
Erweiterte Einstellungen werden auf Klick sichtbar. 

[shorturl-list]
Zeigt in einer Tabelle die URLs an. Es kann nach Kategorien und Schlagwörtern gefiltert werden. 

[shorturl-categories]
Die Verwaltung der Kategorien. Kategorien sind IdM-zugehörig. D.h. es können nur Kategorien zur eigenen IdM editiert, gelöscht, angelegt und zugeordnet werden.

[shorturl-tags] (seit 1.1.0 deaktiviert)
Die Verwaltung der Schlagwörter

[shorturl-services]
Gibt eine Tabelle mit den Services aus.

[shorturl-customer-domains]
Gibt eine Tabelle mit den aktiven Customer Domains aus.

## Block
RRZE ShortURL um im Blockeditor Links zu verkürzen

## Verwaltung
In den Einstellungen kann Folgendes verwaltet werden:
- Services: vordefinierte Dienste 
- Customer Domains: Domains unserer CMS-Kunden
- External Domains: externe Domains
- IdM: neue IdM werden automatisch erstellt. Hier kann bestimmt werden, welche erweiterten Einstellungen gestattet sind
- Statistik

## Defaults:
- neue IdMs haben keine erweiterten Rechte

## Cronjobs (WordPress "cronjobs")
- rrze_shorturl_fetch_and_store_customerdomains für die Ermittlung der Customer Domains (täglich um 4:00 a.m.)
- rrze_shorturl_cleanup_inactive_idms um die IdM zu löschen, die keine Links generiert haben (monatlich)
- rrze_shorturl_cleanup_invalid_links um alle Links zu löschen, deren HTTP Response 4xxx ist (täglich um 2:00 a.m.)


## REST-API Endpoints (DOMAIN/wp/v2/shorturl)
- /active-shorturls (GET) liefert die Paare "long_url" und "short_url" als JSON
- /shorten (POST) empfängt die Parameter und liefert das Paar 'error'(true/false) und 'txt' (short_url oder error-message) als JSON
- /get-longurl (GET) liefert zum Parameter "shortURL" den "long_url" als JSON
- /categories (GET) liefert die Kategorien als JSON
- /services (GET) liefert alle Services samt Regex als JSON
- /decrypt (POST) liefert den decrypted Wert vom Parameter "encrypted" (für einen Service) als JSON
- /encrypt (GET) liefert den encrypted Wert vom Parameter "decrypted" (für einen Service) als JSON

## Zugriffsschutz der REST-API Endpoints:
Alle Endpoints sind zugriffsgeschützt.
- /active-shorturls : IP (Einstellbar in Settings)
- /shorten : SSO
- /categories : SSO
- /services : IP (Einstellbar in Settings)

## Installation
- Plugin auf WordPress Instanz
- Inhalt vom Order "Server" auf den Server ins Root-Verzeichnis kopieren, der die Redirects ausführen soll (Redirect-Server)
- Auf Redirect-Server: make_htaccess.php ein Mal aufrufen. Wenn make_htaccess.php nicht im Root-Verzeichnis liegt, muss der Pfad zur .htaccess angepasst werden

## Ablauf 
Zwischen Generierung des ShortURLs und Aufruf gibt es seit 1.3.0 keine Wartezeit mehr und es ist kein Cron-Job mehr nötig.

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


## Preview

Endet der ShortURL mit einem Plus-Zeichen, wird eine simple HTML-Seite ausgegeben mit "$short_url redirects to / verweist auf $long_url".
Das funktioniert mit Service-URLs und Customer Links.

## .htaccess
Die .htaccess enthält alle Redirects zwischen den Kommentar-Zeilen "# ShortURL BEGIN" und "# ShortURL END".
Als erstes die Regel (Redirect zu shorturl-redirect.php) für die Services, dann die User-generierten Redirects für die erlaubten Domains. Am Schluss Redirect zu shorturl-redirect.php
Nach "# ShortURL END" alle vorher bereits existierten Zeilen der .htaccess. 
make_htaccess.php überschreibt die Anweisungen der vorhandene .htaccess nicht.


## Performance
Mit der Zeit wird die .htaccess viele Zeilen für die Redirect Rules enthalten.
Es sollte keine Auswirkung auf die Performance haben, da auch .htaccess sehr viele Anweisungen sehr schnell verarbeiten kann (siehe zB "7G Firewall" https://perishablepress.com/7g-firewall/ ). Gültigkeit der Rules ist durchschnittlich 1 Jahr. Ungültige werden automatisch entfernt.
Services werden stets in shorturl-redirect.php berechnet. Dazu wird die RegEx aus der SESSION gelesen, dann notfalls im JSON-File, dann erst über die REST-API und die Entkodierung findet im Skript statt. Nur wenn Link nicht aufgeschlüsselt werden kann, wird die REST-API nach Updates abgefragt. Dabei so performant wie möglich: lightweight nur zum aktuellen Link. Redirect vor allen weiteren Anweisungen (wie weiteren REST-API-Aufruf, SESSION und JSON-File speichern, ggfalls .htaccess neu generieren).





