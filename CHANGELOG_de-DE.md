# 1.8.5
- Datenschutz-Vorlage: neuer Baustein `tourbenachrichtigung` für die Tour-Benachrichtigung von Nova Versandkosten (ab 1.2.0) – Zweck, Daten (E-Mail, PLZ, ggf. Kundenkonto- und Adressbezug, Zeitpunkte), Double-Opt-in, Abmeldung über den Link in jeder Mail oder im Kundenkonto, Löschung unbestätigter Anmeldungen nach 7 Tagen und inaktiver nach 24 Monaten, Rechtsgrundlage Einwilligung (Art. 6 Abs. 1 lit. a DSGVO). Automatisch erkannt, wenn Nova Versandkosten aktiv und die Tour-Benachrichtigung im Verkaufskanal eingeschaltet ist; auch in den Listen „Genutzte Bausteine“ und „Nie verwenden“

# 1.8.4
- AGB-Vorlage: neuer Baustein `versandanfrage` im Abschnitt „Vertragsschluss“ – bei „Versandkosten auf Anfrage“ ist die Bestellung zunächst eine unverbindliche Anfrage, der Vertrag kommt erst mit Zahlung des mitgeteilten Gesamtbetrags zustande; ohne Zahlung innerhalb der genannten Frist wird storniert. Automatisch erkannt, wenn Nova Versandkosten (ab 1.1.0) aktiv ist und die Versandart „Versandkosten auf Anfrage“ aktiv und dem Verkaufskanal zugeordnet ist; auch in den Listen „Genutzte Bausteine“ und „Nie verwenden“

# 1.8.3
- Baustein für einen externen Suchdienst aus der Datenschutz-Vorlage entfernt (wird nicht mehr genutzt; die Suche läuft lokal über Nova Suche, Baustein `suchauswertung`). Ein in der Konfiguration noch gesetzter alter Baustein wird ignoriert

# 1.8.2
- Datenschutz-Vorlage: neuer Baustein `treffenmelden` für das Formular „Treffen melden“ (Nova Termine) – Angaben zur Veranstaltung, optionale E-Mail, Löschung nach Übernahme bzw. spätestens 180 Tagen; automatisch erkannt, wenn Nova Termine aktiv ist

# 1.8.1
- Datenschutz-Vorlage, Baustein `suchauswertung` (Nova Suche): Absatz zur freiwilligen **Rückmeldung zum Suchergebnis** ergänzt – Bewertung und kurzer Text, gespeichert ohne IP-Adresse und Kontobezug, Löschung nach 12 Monaten

# 1.8.0
- Datenschutz-Vorlage ergänzt:
  - **Dateien zu Artikeln** (Nova Upload): Zweck, Löschung nach 14 Tagen ohne Bestellung bzw. zwei Jahren nach Bestellung, Mitlöschen bei Bestell-/Kontolöschung
  - **Erweiterte Conversions** (Google Ads): gehashte Kundendaten nur mit Marketing-Einwilligung – neuer Baustein `erweitertekonversionen`, automatisch erkannt, wenn im Nova Tag Manager „Kundendaten für erweiterte Conversions“ an ist
  - **Suche auf der Website** (Nova Suche): Speicherung von Suchbegriffen ohne Kontobezug, IP nur kurz gehasht – neuer Baustein `suchauswertung`; **KI-Suchkorrektur** über Anthropic (Claude) – neuer Baustein `kisuche`, beide automatisch erkannt

# 1.7.2
- Robuster: Scheitert eine Textersetzung an einem PCRE-Fehler (z. B. ungültiges UTF-8 aus kopierten Texten oder sehr lange Texte), bleibt der Text dieses Schritts unverändert erhalten statt leer ausgeliefert zu werden (AGB/Widerruf/Datenschutz, auch im Checkout). Der Fehler wird mit Seite und Textelement im Shopware-Log protokolliert
- Bedingungsauflösung bricht ab, sobald sich nichts mehr ändert

# 1.7.1
- Eigenes Plugin-Icon (bisher versehentlich das Icon von Nova Upload)

# 1.7.0
- Neu: **zeitgesteuerte Abschnitte** `[[bis 02.05.2026]] … [[ende]]` und `[[ab 01.03.2027]] … [[ende]]` (auch mit `[[sonst]]`) – in jedem Erlebniswelt-Text, z. B. für Termine auf einer Seite „Treffen & Messen“, die nach dem Datum von selbst verschwinden

# 1.6.2
- „-“ in einem Feld = in diesem Verkaufskanal leer (statt den gemeinsamen Wert zu erben), z. B. für Shops ohne USt-IdNr. oder WhatsApp
- Vorlage Impressum: USt-IdNr.-Zeile entfällt, wenn keine eingetragen ist

# 1.6.1
- Vorlagen Rücksendung und Altteilbestimmungen allgemein gehalten: Fristen (`[[Altteil-Frist]]`, `[[Altteil-Erstattungsfrist]]`), Annahmebedingungen (`[[Altteil-Anforderungen]]`), Beträge und Etiketten kommen aus der Konfiguration; Abschnitte entfallen, wenn nichts eingetragen ist
- AGB-Abschnitt Altteilpfand nutzt dieselben Fristen

# 1.6.0
- Neue Vorlagen **Rücksendung** und **Altteilbestimmungen** (passend zum AGB-Abschnitt Altteilpfand)
- Neue Platzhalter `[[Rücksendekosten]]` und `[[Altteil-Details]]` (HTML, je Verkaufskanal)

# 1.5.1
- Baustein „Starterbatterien (Pfand)“ wird auch unter seinem Anzeigenamen erkannt

# 1.5.0
- Neue Vorlagen: **Batteriehinweise** (nach EU-Batterieverordnung und BattDG, das BattG gilt seit 18.08.2025 nicht mehr; Baustein „Starterbatterien (Pfand)“) und **Verpackungshinweise** (VerpackG)

# 1.4.1
- Vorlage Impressum: Onlineshop-Zeile nur noch mit Domain (vorher doppelt, wenn Shopname und Domain gleich sind)

# 1.4.0
- Neu: **Textvorlagen** für Impressum, AGB, Widerrufsbelehrung (Waren/Dienstleistungen), Zahlung und Versand und Datenschutzerklärung – eigene Formulierungen mit Bausteinen, einzusetzen mit `[[Vorlage …]]`
- Neu: automatische Nummerierung `[[§]]` und `[[Nr]]` ohne Lücken bei ausgeblendeten Abschnitten
- Neu: Platzhalter `[[Anschrift]]`, `[[Aufsichtsbehörde]]`, `[[Warenwirtschaft]]`; gefüllte Platzhalter auch als Bedingung (`[[wenn Telefax]]`)
- Bausteine: Anzeigenamen und Aliase werden erkannt („Gebrauchte Waren“ = „gebrauchtware“), neuer Baustein „Online-Widerrufsformular“

# 1.3.1
- Versandkosten: Bereiche kürzer („10 bis 20 kg“)

# 1.3.0
- Versandkosten automatisch: „Gilt für“ aus der **Regel-Beschreibung** (erste Zeile), Beschreibung „-“ blendet Preise einer Regel aus – interne Regelnamen bleiben unverändert
- Preise je Regel gruppiert (Zwischenüberschrift statt wiederholter Spalte), Spaltenkopf passend zur Berechnung (Gewicht, Anzahl …)

# 1.2.1
- README: Haftungsausschluss – keine Rechtsberatung, keine Gewähr für Textvorlagen, Verantwortung liegt beim Shopbetreiber

# 1.2.0
- Neu: **Versandkosten automatisch** – `[[Versandkosten]]` erzeugt die Übersicht aus den Versandarten des Verkaufskanals (Preis-Matrix, Regeln, Lieferzeit), solange kein eigener Text eingetragen ist; zusätzlich `[[Versandkosten automatisch]]`. Versandarten ausblendbar, Regelnamen und Lieferzeit abschaltbar

# 1.1.0
- Neu: **Bausteine** – Abschnitte mit `[[wenn Klarna]] … [[sonst]] … [[ende]]` je Shop ein- und ausblenden, wie bei Rechtstext-Generatoren. Bausteine zum Anklicken je Verkaufskanal, eigene Bausteine, „Nie verwenden“ und automatische Erkennung (zugewiesene Zahlungsarten, reCAPTCHA, Nova Tag Manager)

# 1.0.0
- Erste Version: Platzhalter wie [[Shopname]], [[E-Mail]], [[Telefon]], [[Firma]], [[Versandkosten]] in Erlebniswelt-Texten je Verkaufskanal ersetzen – ein Layout je Rechtstext für alle Shops (vorher als Testfunktion in Nova Theme 1.10)
