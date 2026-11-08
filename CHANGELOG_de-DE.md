# 1.12.0
- **Versandkosten mit Nova Versandkosten:** Die automatische Übersicht schrieb bei dessen Versandarten „versandkostenfrei“ (0-€-Matrix in Shopware, Preis entsteht erst im Warenkorb) – für Kunden und Google Merchant Center eine falsche Angabe. Ist Nova Versandkosten aktiv, kommen die Preise jetzt aus dessen Rechner: Gewichtsstufen im Heimatland inkl. aktueller Zuschläge, Warenpost/Kleinpaket mit Warenwertgrenze, Laufzeit, Hinweise zu Sperrgut, mehreren Paketen und Ausland, Texte für Spedition, „auf Anfrage“ und Abholung. Ohne das Plugin bleibt alles wie bisher
- Neuer Platzhalter `[[Lieferländer]]` (Länder des Verkaufskanals, mit Nova Versandkosten nur dessen freigeschaltete Länder). „Zahlung und Versand“ nennt die Länder jetzt („Wir liefern in die unten genannten Länder“ stand ohne Liste da)
- „Zahlung und Versand“: Satz, dass ein eigener Versandtermin bzw. Bestellschluss auf Produktseite oder im Warenkorb vorgeht (z. B. Monats-Liefertermine)

# 1.11.1
- EU-Gewährleistungshinweis: Das Fenster steht jetzt am Ende der Seite statt in der Kaufbox. Auf dem Desktop lag sonst der abgedunkelte Hintergrund über dem Fenster (Kaufbox „sticky“), Schließen und Esc wirkten nicht

# 1.11.0
- **EU-Gewährleistungshinweis** (DVO (EU) 2025/1960, Pflicht seit 27.09.2026): Link „Ihre gesetzlichen Gewährleistungsrechte“ unter der Kaufbox und in der Kasse, öffnet die amtliche Grafik der EU-Kommission (deutsch, farbig, unverändert) mit Link zu europa.eu/youreurope/garantien; vollständiger Text als Alternativtext. Neue Einstellung „EU-Gewährleistungshinweis“ (Standard an, je Verkaufskanal abschaltbar). Hinweis zur Bestellbestätigung im README

# 1.10.0
- **Widerrufsbelehrung (Waren und Dienstleistungen):** Satz zur Widerrufsfunktion jetzt im amtlichen Wortlaut von Gestaltungshinweis 3 (Anlage 1 EGBGB, BGBl. 2026 I Nr. 28, seit 19.06.2026) – mit Adresse der Funktion, Eingangsbestätigung auf dauerhaftem Datenträger mit Inhalt, Datum und Uhrzeit. Neuer Platzhalter `[[Widerrufsfunktion]]` (Adresse der Seite „Vertrag widerrufen“); leer = Verweis auf den Link „Vertrag widerrufen“. Weiterhin nur mit Baustein `widerrufsformular`
- **Verpackungshinweise:** neue Rechtsgrundlage § 39 VerpackDG (das VerpackG ist seit 12.08.2026 außer Kraft), Rücknahme nur für nicht systembeteiligungspflichtige Verpackungen, „wiederverwendbare Verpackungen“ statt „Mehrweg“, Primärproduktionsverpackungen, Hinweis auf Sinn und Zweck

# 1.9.0
- **Verbraucherstreitbeilegung (§ 36 VSBG):** Impressum und AGB sagten „bereit, aber nicht verpflichtet“, ohne eine Stelle zu nennen. Neu: Feld „[[Schlichtungsstelle]]“ in der Konfiguration. Ausgefüllt → Bereitschaft mit Name, Anschrift und Website der Stelle; leer → „nicht bereit und nicht verpflichtet“
- „Zahlung und Versand“ und Datenschutz (Baustein `paypal`): keine feste Liste der PayPal-Zahlungsarten mehr (SEPA-Lastschrift, Multibanco usw. sind je Shop abschaltbar), sondern „wie im Bestellvorgang angezeigt“ mit Beispielen

# 1.8.9
- AGB-Vorlage: Baustein `gocardless` jetzt auch im Abschnitt „Zahlung“ – SEPA-Lastschrift über die GoCardless SAS, Mandat auf der Seite von GoCardless, Vorabankündigung von GoCardless per E-Mail (ohne feste Frist im Text), „Pay by Bank“ als Sofortüberweisung, Deckung/Rücklastschrift. Der allgemeine Baustein `lastschrift` (eigene Lastschrift mit 5 Tagen Vorabankündigung) bleibt davon getrennt

# 1.8.8
- Datenschutz-Vorlage: neuer Baustein `gocardless` im Abschnitt „Zahlungsdienstleister“ für Nova GoCardless – SEPA-Lastschrift über die GoCardless SAS (Paris): übermittelte Daten (Name, E-Mail, Rechnungsanschrift, Bestellnummer, Betrag), Bankverbindung und Mandat nur auf der Seite von GoCardless, Rückmeldung nur zum Status des Mandats und Einzugs, eigene Verarbeitung durch GoCardless (Kontoprüfung, Betrugsprävention, Vorabankündigung), Link zu den Datenschutzhinweisen für Zahler. Automatisch erkannt, wenn Nova GoCardless installiert und aktiv und die Zahlungsart im Verkaufskanal aktiv und zugeordnet ist; auch in den Listen „Genutzte Bausteine“ und „Nie verwenden“
- Seite „Zahlung und Versand“: Eintrag „SEPA-Lastschrift über GoCardless“ in der Liste der Zahlungsarten (Baustein `gocardless`)

# 1.8.7
- Datenschutz-Vorlage: neuer Baustein `bestandskundenwerbung` im Abschnitt „Werbung“ für Nova Nachfrage – einmalige E-Mail nach dem Versand mit Bitte um Bewertung und Hinweis auf eigene ähnliche Produkte, Rechtsgrundlage § 7 Abs. 3 UWG i. V. m. Art. 6 Abs. 1 lit. f DSGVO, Widerspruchsrecht (Abmeldelink in jeder Mail, nur Übermittlungskosten nach den Basistarifen), dauerhafte Speicherung des Widerspruchs nur als Hash, Versandprotokoll mit Löschung nach 400 Tagen, Hinweis auf Google bei der Google-Bewertung. Automatisch erkannt, wenn Nova Nachfrage installiert und aktiv und im Verkaufskanal eingeschaltet ist; auch in den Listen „Genutzte Bausteine“ und „Nie verwenden“

# 1.8.6
- Automatische Erkennung: Die Bausteine `tagmanager`, `erweitertekonversionen`, `suchauswertung` und `kisuche` gelten nur noch, wenn Nova Tag Manager bzw. Nova Suche auch **aktiv** ist. Bisher reichten die Einstellungen, die Shopware nach dem Deaktivieren eines Plugins behält – die Datenschutzerklärung konnte dann Verarbeitungen beschreiben, die nicht stattfinden. Nova Termine und Nova Versandkosten werden jetzt genauso geprüft (Plugin laut Plugin-Verwaltung aktiv, nicht nur ladbar)
- Datenschutz-Vorlage, Baustein `erweitertekonversionen`: E-Mail, Telefon und Name werden **auf unserem Server** vor der Übermittlung gehasht (seit Nova Tag Manager 1.2.0), nicht im Browser; Straße, Ort, PLZ und Land ausdrücklich als ungehasht genannt. Wer den Text als eigene Kopie in der Erlebniswelt pflegt, sollte den Satz dort ebenfalls anpassen
- Bedingte Abschnitte: Ein PCRE-Fehler bei der Suche nach `[[wenn …]]`/`[[bis …]]` wird jetzt in jedem Fall erkannt und geloggt (bisher konnte er unbemerkt bleiben, wenn die zweite Suche gelang – die Markierungen blieben dann sichtbar im Text)

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
