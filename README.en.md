# Nova Legal Texts for Shopware 6

🇩🇪 [Deutsche Version](README.md)

Maintain imprint, privacy policy, terms and shipping information **once** for all sales channels. Use placeholders such as `[[Shopname]]`, `[[E-Mail]]`, `[[Telefon]]`, `[[Telefax]]`, `[[WhatsApp]]`, `[[Firma]]`, `[[Inhaber]]`, `[[USt-IdNr]]`, `[[Lieferzeit]]`, `[[Versandkosten]]`, `[[Domain]]`, `[[Shop-URL]]`, `[[Jahr]]`, `[[Zusatz1]]`–`[[Zusatz3]]` in shopping experience text elements; they are replaced per sales channel on the server – independent of the theme and also in modals (terms in checkout).

> **⚠️ Disclaimer**
>
> This plugin and all included text templates and building blocks are **not legal advice** and have **not been reviewed by a lawyer**. No warranty or liability is accepted for correctness, completeness or timeliness, in particular not for warning letters, fines or other damages. **The shop operator alone is responsible for the texts published in the shop.** Have your texts reviewed by a lawyer before use.

## Building blocks

Mark optional sections with `[[wenn Klarna]] … [[sonst]] … [[ende]]` (also `[[wenn nicht …]]`, `[[wenn A oder B]]`). Active blocks per sales channel: auto-detected (assigned payment methods, reCAPTCHA, Nova Tag Manager), selected from a list, own names, or forced off.

## Installation

```bash
cd <shopware-root>/custom/plugins
git clone https://github.com/DrdotHouse2106/SW6-Nova-Rechtstexte.git NovaRechtstexte
cd <shopware-root>
bin/console plugin:refresh
bin/console plugin:install --activate NovaRechtstexte
bin/console cache:clear
```

## Setup

Set common values under "All sales channels" in the plugin configuration, then only the differences per sales channel. Assign one layout per legal text to all shops.

## Troubleshooting

- **`[[...]]` still visible:** typo, or HTML inside the brackets – check the source view of the text editor.
- **Change not visible:** clear the cache.

## Contributing – lawyers welcome

Are you a lawyer or a data protection expert? Corrections, new building blocks, hints on legal changes or a review of existing texts are very welcome as pull request or issue. Reviews can be credited with name and date in the template. Contributions are MIT licensed; please do not submit texts from paid generators (copyright).

## License

MIT
