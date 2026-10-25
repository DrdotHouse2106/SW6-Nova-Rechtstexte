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
