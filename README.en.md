# Nova Legal Texts for Shopware 6

🇩🇪 [Deutsche Version](README.md)

Maintain imprint, privacy policy, terms and shipping information **once** for all sales channels. Use placeholders such as `[[Shopname]]`, `[[E-Mail]]`, `[[Telefon]]`, `[[Telefax]]`, `[[WhatsApp]]`, `[[Firma]]`, `[[Inhaber]]`, `[[USt-IdNr]]`, `[[Lieferzeit]]`, `[[Versandkosten]]`, `[[Domain]]`, `[[Shop-URL]]`, `[[Jahr]]`, `[[Zusatz1]]`–`[[Zusatz3]]` in shopping experience text elements; they are replaced per sales channel on the server – independent of the theme and also in modals (terms in checkout).

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

## License

MIT
