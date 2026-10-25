# Nova Rechtstexte für Shopware 6

🇬🇧 [English version](README.en.md)

**Rechtstexte einmal pflegen, in allen Shops nutzen.** Wer mehrere Verkaufskanäle mit demselben Betreiber führt, hat Impressum, Datenschutzerklärung, AGB, Widerrufsbelehrung und „Zahlung und Versand“ sonst mehrfach – und vergisst beim nächsten Update garantiert einen Shop.

Mit Nova Rechtstexte gibt es **ein Layout je Rechtstext**, das allen Shops zugewiesen wird. Shop-spezifische Angaben stehen als Platzhalter im Text, z. B. `[[Shopname]]` oder `[[E-Mail]]`, und werden beim Ausliefern je Verkaufskanal ersetzt.

## Funktionen

- Platzhalter in **allen Text-Elementen** von Erlebniswelten (Kategorieseiten, Shopseiten, Landingpages)
- Wirkt auch in **Modalen**, z. B. AGB und Datenschutz im Checkout (Seiten aus *Einstellungen → Grundinformationen*)
- **Unabhängig vom Theme** – läuft serverseitig nach dem Laden der Seite
- Werte je Verkaufskanal mit Vererbung: einmal unter „Alle Verkaufskanäle“ setzen, nur Abweichungen je Shop eintragen
- Groß-/Kleinschreibung und Leerzeichen im Platzhalter sind egal, auch in Links nutzbar (`mailto:[[E-Mail]]`, `tel:[[Telefon]]`)
- Unbekannte Platzhalter (Tippfehler) bleiben sichtbar stehen

## Platzhalter

| Platzhalter | Wert | Quelle |
|---|---|---|
| `[[Shopname]]` | Name des Shops | Einstellungen → Grundinformationen |
| `[[E-Mail]]` | E-Mail-Adresse | Grundinformationen, in der Plugin-Konfiguration überschreibbar |
| `[[Telefon]]` | Telefonnummer | Plugin-Konfiguration, sonst Topbar-Telefon von Nova Theme |
| `[[Telefax]]`, `[[WhatsApp]]` | Fax, WhatsApp | Plugin-Konfiguration |
| `[[Firma]]` | Firmierung, z. B. „Muster GmbH“ | Plugin-Konfiguration, sonst Shopname |
| `[[Inhaber]]` | Inhaber bzw. Verantwortlicher | Plugin-Konfiguration |
| `[[USt-IdNr]]` | Umsatzsteuer-ID | Plugin-Konfiguration |
| `[[Lieferzeit]]` | z. B. „3 - 5 Tagen“ | Plugin-Konfiguration |
| `[[Versandkosten]]` | Versandkosten-Übersicht: **automatisch aus den Versandarten** des Verkaufskanals, oder eigener Text (HTML) | Versandarten bzw. Plugin-Konfiguration |
| `[[Versandkosten automatisch]]` | immer die automatische Übersicht | Versandarten |
| `[[Domain]]` | z. B. mein-shop.de | aufgerufene Domain (ohne www) |
| `[[Shop-URL]]` | z. B. https://mein-shop.de | Domain des Verkaufskanals |
| `[[Jahr]]` | aktuelles Jahr | – |
| `[[Zusatz1]]`–`[[Zusatz3]]` | freier Text je Shop, HTML erlaubt (leer = verschwindet) | Plugin-Konfiguration |

Einfache Werte werden HTML-sicher eingesetzt. `[[Versandkosten]]` und `[[Zusatz…]]` werden unverändert eingesetzt (nur von Admins pflegbar).

## Versandkosten automatisch

Ist das Feld „[[Versandkosten]] von Hand“ leer, erzeugt das Plugin die Versandkosten aus den **Versandarten des Verkaufskanals** (*Einstellungen → Versand*): je aktive, zugewiesene Versandart Name, Lieferzeit, Verfügbarkeitsregel und die Preis-Matrix (Gewicht, Warenwert, Anzahl oder Volumen; Bruttopreise in der Währung des Shops, 0 € = versandkostenfrei). So stimmen Rechtstext und Checkout immer überein – ändert sich ein Preis, ändert sich der Text.

- Regelnamen erscheinen als „Gilt für“ – im Rule Builder kundenverständlich benennen (z. B. „Deutschland“, „EU Zone 1“, „Warenkorb enthält Sperrgut“) oder in der Konfiguration ausblenden.
- Versandarten, die nicht im Text stehen sollen (z. B. interne), unter „diese Versandarten nicht aufführen“ wählen.

## Bausteine: Abschnitte je Shop ein- und ausblenden

Wie bei den Rechtstext-Generatoren der Händlerverbände: Ein Text enthält **alle** möglichen Abschnitte, und je Shop wird angeklickt, welche gelten. Abschnitte werden im Text markiert:

```html
<p>[[wenn Klarna]]</p>
<h3>Zahlung über Klarna</h3>
<p>In Zusammenarbeit mit Klarna bieten wir …</p>
<p>[[ende]]</p>
```

| Schreibweise | Bedeutung |
|---|---|
| `[[wenn Klarna]] … [[ende]]` | nur wenn der Baustein „Klarna“ aktiv ist |
| `[[wenn nicht Klarna]] … [[ende]]` | nur wenn er NICHT aktiv ist |
| `[[wenn PayPal oder Klarna]] … [[ende]]` | wenn mindestens einer aktiv ist |
| `[[wenn Klarna]] … [[sonst]] … [[ende]]` | entweder – oder |

- Verschachteln ist erlaubt. Groß-/Kleinschreibung, Leerzeichen und Umlaute sind egal („Amazon Pay“ = „amazonpay“).
- Steht eine Markierung **allein in einem Absatz**, verschwindet der leere Absatz mit – im Texteditor die Markierungen am besten in eine eigene Zeile schreiben.

**Welche Bausteine aktiv sind** (Plugin-Konfiguration, je Verkaufskanal):
- **Automatisch erkennen** (Standard an): zugewiesene Zahlungsarten (PayPal, Klarna, Amazon Pay, Google Pay, Apple Pay, Ratepay, Mollie, Stripe, Vorkasse, Barzahlung, Rechnung, Lastschrift), Google reCAPTCHA (Grundinformationen) und Nova Tag Manager (Container-ID gesetzt)
- **Genutzte Bausteine:** Liste zum Anklicken, z. B. Newsletter, Kundenkonto, YouTube, Gebrauchte Waren, Reparaturleistungen, Altteilpfand, Batterien, Preisgebundene Bücher …
- **Eigene Bausteine:** freie Namen, kommagetrennt
- **Nie verwenden:** übersteuert die automatische Erkennung

Die Inhalte der Abschnitte schreibt das Plugin nicht – es blendet nur ein und aus. Rechtstexte selbst kommen weiterhin von Ihrem Rechtstext-Anbieter oder Anwalt.

## Installation

**Mit dem GitHub Plugin-Updater:** *Erweiterungen → Meine Erweiterungen → Plugin-Katalog → Nova Rechtstexte → Installieren*.

**Per Konsole:**

```bash
cd <shopware-root>/custom/plugins
git clone https://github.com/DrdotHouse2106/SW6-Nova-Rechtstexte.git NovaRechtstexte

cd <shopware-root>
bin/console plugin:refresh
bin/console plugin:install --activate NovaRechtstexte
bin/console cache:clear
```

## Einrichtung

1. *Erweiterungen → Meine Erweiterungen → Nova Rechtstexte → Konfigurieren*: unter **„Alle Verkaufskanäle“** die gemeinsamen Werte eintragen (Inhaber, Firma, USt-IdNr., Telefon …), danach je Verkaufskanal nur die Abweichungen.
2. Je Rechtstext ein Layout anlegen (z. B. „Rechtstext – Impressum“) und im Text die Platzhalter verwenden.
3. Das Layout in *Einstellungen → Grundinformationen* („Alle Verkaufskanäle“) und in den Footer-Kategorien aller Shops zuweisen.
4. Cache leeren und jeden Shop prüfen: Es darf nirgends `[[` stehen bleiben.

**Beispiel Impressum:**

```html
<p>[[Inhaber]]<br>[[Firma]]<br>Musterstraße 1<br>12345 Musterstadt</p>
<p>Telefon: [[Telefon]]<br>E-Mail: <a href="mailto:[[E-Mail]]">[[E-Mail]]</a></p>
<p>USt-IdNr.: [[USt-IdNr]]</p>
```

## Häufige Probleme und Lösungen

### Im Shop steht noch `[[Telefon]]` o. ä.

**Ursache:** Tippfehler im Platzhalter (unbekannte Platzhalter bleiben absichtlich stehen) oder der Platzhalter ist im Texteditor durch Formatierung zerteilt (z. B. `[[<strong>Telefon</strong>]]`)

**Lösung:** Schreibweise mit der Tabelle oben vergleichen; in der Quellcode-Ansicht des Texteditors prüfen, dass zwischen `[[` und `]]` kein HTML steht

### Geänderter Wert erscheint nicht

**Ursache:** Die Seite liegt noch im HTTP-Cache

**Lösung:** *Einstellungen → System → Caches & Indizes → Cache leeren*

### Ein Shop zeigt die Werte eines anderen Shops

**Ursache:** Der Wert ist nur unter „Alle Verkaufskanäle“ gesetzt und der Shop erbt ihn

**Lösung:** In der Plugin-Konfiguration den Verkaufskanal wählen und dort einen eigenen Wert eintragen

### Ein Abschnitt erscheint nicht (oder fehlt nicht)

**Ursache:** Baustein nicht aktiv bzw. trotz Erkennung unerwünscht aktiv; oder ein Verkaufskanal hat eine eigene Auswahl bei „Genutzte Bausteine“ und erbt die Liste von „Alle Verkaufskanäle“ deshalb nicht mehr

**Lösung:** In der Plugin-Konfiguration den Verkaufskanal wählen und „Genutzte Bausteine“ bzw. „Nie verwenden“ prüfen; Schreibweise im Text mit dem Bausteinnamen vergleichen

### Im Shop steht `[[ende]]` oder `[[wenn …]]`

**Ursache:** Eine Bedingung ist nicht geschlossen (fehlendes `[[ende]]`) oder die Markierung ist durch Formatierung zerteilt

**Lösung:** In der Quellcode-Ansicht prüfen, dass jedes `[[wenn …]]` ein `[[ende]]` hat und kein HTML zwischen den Klammern steht

### Versandkosten-Tabelle zeigt interne Regelnamen

**Ursache:** Die Spalte „Gilt für“ zeigt die Namen aus dem Rule Builder

**Lösung:** Regeln kundenverständlich umbenennen oder „Regelnamen zeigen“ ausschalten

## Lizenz

MIT
