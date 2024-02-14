# RRZE-FAQ
WordPress-Plugin: Shortcode / Gutenberg Block / Widget zur Einbindung von eigenen FAQ sowie von FAQ-Einträgen aus dem FAU-Netzwerk. 

## Allgemeines

Das Plugin kann genutzt werden, um FAQ zu erstellen und FAQ von Websites aus dem FAU-Netzwerk zu synchronisieren. Es kann nach Kategorien und Schlagwörtern gefiltert werden. Das Layout lässt sich derart bestimmen, dass ein A-Z Register, die Kategorien bzw Schlagwörter als Links oder als Links, die sich nach Anzahl der gefundenen Treffer in der Größe unterscheiden ausgebeben werden kann. Kategorien und Schlagwörter werden in Akkordeons gruppiert. Es ist ebenso möglich, einzelne FAQ auszugeben.
Darüberhinaus wird ein Widget bereitgestellt. Einstellbar sind die Anzeigedauer und ob ein bestimmtes FAQ oder aus einer gewählten Kategorie ein zufälliges FAQ angezeigt werden soll.


## Verwendung des Shortcodes

```html
[faq id=456, 123"] 
[faq category="kategorie-1, kategorie-1"]
[faq tag="schlagwort-1, schlagwort-2"]
[faq category="kategorie-1, kategorie-1"  tag="schlagwort-1, schlagwort-2"]
```


## Alle Attribute des Shortcodes

```html
[faq 
glossary=".." 
category=".."  
tag=".." 
id=".."
hide=".."
show=".."
class=".."
sort=".."
order=".."
hstart=".."
] 
```

Alle Attribute sind optional.


## Erklärungen und Werte zu den Attributen des Shortcodes

glossary : bestimmt, wonach gruppiert werden soll. Mögliche Werte für die Gruppierung sind "category" oder "tag". D.h. entweder es wird nach den Kategorien gruppiert oder nach Schlagwörtern. Um gar nicht zu gruppieren, reicht es, das Attribut glossary wegzulassen. Darüberhinaus können Sie das Aussehen des Glossars bestimmen: "a-z" stellt ein alphabetisches Register dar. Mit "tabs" werden die Begriffe ausgegeben und ebenso mit "tagcloud", wobei sie hier abhängig von der Anzahl an gefundenen Treffer unterschiedlich groß dargestellt werden. Voreingestellt ist die Darstellung "a-z".

category : mit diesem Attribut wird bestimmt, zu welchen Kategorien passende FAQ ausgegeben werden sollen. Es können beliebig viele Kategorien angegeben werden. Nutzen Sie dazu die Titelform der Kategorien, die Sie im Menü unter "FAQ"->"Kategorie" finden und trennen Sie diese voneinander durch Kommata.

tag : mit diesem Attribut wird bestimmt, zu welchen Schlagwörtern passende FAQ ausgegeben werden sollen. Es können beliebig viele Schlagwörter angegeben werden. Nutzen Sie dazu die Titelform der Schlagwörter, die Sie im Menü unter "FAQ"->"Schlagwörter" finden und trennen Sie diese voneinander durch Kommata.

id : mit diesem Attribut erfolgt die Ausgabe eines oder mehrerer FAQ. Sie finden die ID in der rechten Spalte unter "FAQ"->"Alle FAQ" sowie in der Informationsbox "Einfügen in Seiten und Beiträgen" bei jeder FAQ im Bearbeitungsmodus. Sie können damit auch die Reihenfolge der FAQ in der Ausgabe bestimmen. 

hide : hiermit können Sie bestimmen, welche standardmässige Ausgabe nicht dargestellt werden soll. Mit "accordeon" werden die FAQ nicht in einem Akkordeon, sondern direkt mit Frage und Antwort ausgeben. "title" verbirgt dabei die Ausgabe der Frage und mit dem Wert "glossary" wird das Glossar nicht angezeigt. Voreingestellt ist die Ausgabe als Accordeons.

show : belegen Sie dieses Attribut mit dem Wert "expand-all-link", dann erscheint oberhalb der FAQ - Ausgabe ein Button, um alle Akkordeons mit einem Klick zu öffnen. Mit "load-open" werden die Akkordeons im geöffneten Zustand geladen. Voreingestellt ist die Ausgabe mit beim Laden geschlossenen Akkordeons und ohne "Alle öffnen"-Button.

class : hier lässt sich festlegen, in welcher Farbe der linke Rand der Accordeons sein soll. Mögliche Werte sind die "fau" (Standard) sowie die Kennungen der Fakultäten "med", "nat", "rw", "phil" oder "tf". Zusätlich können Sie hier beliebig viele CSS-Klassen durch Leerzeichen getrennt angeben, die als Klassen für das umrahmende DIV dienen.

sort : die Sortierung der Ausgabe kann hiermit gesteuert werden. Mögliche Werte sind "title", "id" und "sortfield". 
"sortfield" bezieht sich auf das Sortierfeld, das bei jeder FAQ eingeben werden kann. Bei Verwendung von "sortfield" wird zuerst nach dem Sortierfeld und danach nach dem Titel sortiert. Voreingestellt ist "title", womit alle Fragen in alphabetischer Reihenfolge angezeigt werden.

order : legt fest, in welcher Reihenfolge sortiert werden soll. "asc" aufsteigend und "desc" absteigend. Voreingestellt ist "asc".

hstart : bestimmt die Überschriftenebene der ersten Überschrift. Voreingestellt ist 2, womit die Überschriften als `<h2>` ausgegeben werden.


## Beispiele


[faq glossary="tag tagcloud"] 
Oberhalb der Ausgabe aller FAQ wird ein Glossar angezeigt, bei dem die Schlagwörter unterschiedlich groß dargestellt werden. Die FAQ sind nach Schlagwörter gruppiert. Das Glossar verlinkt auf die Schlagwörter

[faq category="Titelform-der-Kategorie"] 
Alle FAQ, die zu dieser Kategorie gehören, werden als Akkordeons ausgegeben. Darüber befindet sich das Glossar von A-Z.

[faq category="Titelform-der-Kategorie" tag="Titelform-des-Schlagworts-1, Titelform-des-Schlagworts-2"] 
Alle FAQ, die zu dieser Kategorie gehören und die beiden Schlagwörter enthalten, werden als Akkordeons ausgegeben. Darüber befindet sich das Glossar von A-Z.

[faq id="456, 987, 123" hide="glossary"] 
Die drei FAQ werden in der angegebene Reihenfolge gezeigt.

[faq glossary="category tabs" tag="Titelform-des-Schlagworts-1" show="expand-all-link" order="desc"] 
Unabhängig von der Kategorie werden alle FAQ, die das Schlagwort enthalten ausgegeben. Sie werden dabei in Kategorien gruppiert. Diese Kategorien sind im Glossar verlinkt. Das Glossar besteht aus den Namen der Kategorien. Die Reihenfolge der FAQ ist bezogen auf die Frage in umgekehrter alphabetischer Richtung.


## FAQ von anderer Domain

Hierzu muss die gewünschte Domain über den Menüpunkt "Einstellungen" -> "RRZE FAQ" -> Tab "Domains" hinzugefügt werden.
Das Synchronisieren kann über den Menüpunkt "Einstellungen" -> "RRZE FAQ" -> Tab "Synchonisierung" vorgenommen werden.
Synchronisierte FAQ können nun wie selbst erstellte FAQ mit dem Shortcode ausgegeben werden.


## Verwendung als Widget

Unter /wp-admin/widgets.php wird das Widget als "FAQ Widget" angeboten. Per drag&drop lässt es sich in einen Bereich wie z.B. der Sidebar einbetten. Einstellbar sind die Anzeigedauer und ob ein bestimmtes FAQ oder aus einer gewählten Kategorie ein zufälliges FAQ angezeigt werden soll.


## Verwendung via REST API v2

Beispiele:

https://www.anleitungen.rrze.fau.de/wp-json/wp/v2/faq

Filterungen:

Tag:
https://www.anleitungen.rrze.fau.de/wp-json/wp/v2/faq?filter[faq_tag]=Matrix

Mehrere Tags:
https://www.anleitungen.rrze.fau.de/wp-json/wp/v2/faq?filter[faq_tag]=Matrix%2BAccounts

Kategorie:
https://www.anleitungen.rrze.fau.de/wp-json/wp/v2/faq?filter[faq_category]=Dienste

Tags und Kategorien:
https://www.anleitungen.rrze.fau.de/wp-json/wp/v2/faq?filter[faq_category]=Dienste&filter[faq_tag]=Sprache

Pagination:
https://developer.wordpress.org/rest-api/using-the-rest-api/pagination/



