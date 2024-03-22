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

[shorturl-tags]
Die Verwaltung der Schlagwörter

## Block
RRZE ShortURL um im Blockeditor Links zu verkürzen

## Verwaltung
In den Einstellungen kann Folgendes verwaltet werden:
- Services: vordefinierte Dienste 
- Customer Domains: die zulässigen Domains
- IdM: neue IdM werden automatisch erstellt. Hier kann bestimmt werden, welche erweiterten Einstellungen gestattet sind
- Statistik


## Cronjobs
- für die Ermittlung der Customer Domains (via REST-API)
- Generierung der .htaccess 


