# 1.8.9
- Terms template: block `gocardless` now also in the “Payment” section – SEPA direct debit via GoCardless SAS, mandate on the GoCardless page, pre-notification by GoCardless by email (no fixed period in the text), “Pay by Bank” as instant transfer, sufficient funds/returned debit. The general `lastschrift` block (own direct debit with 5-day pre-notification) stays separate

# 1.8.8
- Privacy template: new block `gocardless` in the “payment service providers” section for Nova GoCardless – SEPA Direct Debit via GoCardless SAS (Paris): data transmitted (name, e-mail, billing address, order number, amount), bank details and mandate only on GoCardless' page, feedback only on the mandate and collection status, GoCardless' own processing (account checks, fraud prevention, advance notice), link to the payer privacy notice. Detected automatically when Nova GoCardless is installed and active and the payment method is active and assigned to the sales channel; also in the lists “used blocks” and “never use”
- Page “payment and shipping”: entry “SEPA Direct Debit via GoCardless” in the payment method list (block `gocardless`)

# 1.8.7
- Privacy template: new block `bestandskundenwerbung` in the “advertising” section for Nova Nachfrage – one e-mail after shipping asking for a review and pointing out similar products of our own, legal basis § 7(3) UWG in conjunction with Art. 6(1)(f) GDPR, right to object (unsubscribe link in every e-mail, only transmission costs at basic rates), objection stored permanently as a hash only, sending log deleted after 400 days, note on Google for the Google review. Detected automatically when Nova Nachfrage is installed, active and switched on for the sales channel; also in the lists “used blocks” and “never use”

# 1.8.6
- Automatic detection: the blocks `tagmanager`, `erweitertekonversionen`, `suchauswertung` and `kisuche` now only apply when Nova Tag Manager or Nova Search is actually **active**. Previously the settings that Shopware keeps after a plugin is deactivated were enough – the privacy policy could then describe processing that does not take place. Nova Termine and Nova Versandkosten are now checked the same way (plugin active in the plugin manager, not only loadable)
- Privacy template, block `erweitertekonversionen`: e-mail, phone and name are hashed **on our server** before transmission (since Nova Tag Manager 1.2.0), not in the browser; street, city, postcode and country explicitly named as unhashed. If you keep your own copy of the text in a Shopping Experience, adjust the sentence there as well
- Conditional sections: a PCRE error while searching for `[[wenn …]]`/`[[bis …]]` is now always detected and logged (previously it could go unnoticed when the second search succeeded – the markers then stayed visible in the text)

# 1.8.5
- Privacy template: new block `tourbenachrichtigung` for the tour notification of Nova Versandkosten (1.2.0+) – purpose, data (e-mail, postcode, customer account and address if applicable, timestamps), double opt-in, unsubscribe via the link in every e-mail or in the account, deletion of unconfirmed sign-ups after 7 days and inactive ones after 24 months, legal basis consent (Art. 6(1)(a) GDPR). Detected automatically when Nova Versandkosten is active and the tour notification is switched on for the sales channel; also in the lists “used blocks” and “never use”

# 1.8.4
- Terms template: new block `versandanfrage` in “conclusion of contract” – with “shipping costs on request” the order is first a non-binding request; the contract is only concluded when the communicated total is paid; without payment within the stated period it is cancelled. Detected automatically when Nova Versandkosten (1.1.0+) is active and the shipping method “on request” is active and assigned to the sales channel; also in the lists “used blocks” and “never use”

# 1.8.3
- Removed the block for an external search service from the privacy template (no longer used; search runs locally via Nova Search). A leftover selection in the configuration is ignored

# 1.8.2
- Privacy template: new block `treffenmelden` for the event suggestion form (Nova Termine), detected automatically when Nova Termine is active

# 1.8.1
- Privacy template, block `suchauswertung` (Nova Search): paragraph on the voluntary **search result feedback** added – rating and short text, stored without IP address or account reference, deleted after 12 months

# 1.8.0
- Privacy template extended: product uploads (Nova Upload) with retention, enhanced conversions (hashed customer data, block `erweitertekonversionen`), site search analytics (`suchauswertung`) and AI search correction via Anthropic (`kisuche`) – all auto-detected

# 1.7.2
- More robust: if a replacement fails with a PCRE error (invalid UTF-8, very long texts), the text of that step is kept instead of being emptied; the error is logged
- Condition loop stops as soon as nothing changes

# 1.7.1
- Own plugin icon (previously showed the Nova Upload icon by mistake)

# 1.7.0
- New: time-based sections `[[bis DATE]] … [[ende]]` and `[[ab DATE]] … [[ende]]` for any Shopping Experience text

# 1.6.2
- "-" means empty for this sales channel; imprint VAT line optional

# 1.6.1
- Returns and core part templates made generic; shop-specific values in configuration

# 1.6.0
- New templates: returns and core part returns; new placeholders

# 1.5.1
- Building block aliases for starter batteries

# 1.5.0
- New templates: battery notes (EU Battery Regulation/BattDG), packaging notes (VerpackG)

# 1.4.1
- Imprint template: shop line shows the domain only

# 1.4.0
- New: German legal text templates with building blocks, automatic numbering, more placeholders

# 1.3.1
- Shipping table: shorter ranges

# 1.3.0
- Automatic shipping table: customer label from rule description, "-" hides a rule; grouped by rule

# 1.2.1
- README: disclaimer – no legal advice, no liability for text templates

# 1.2.0
- New: automatic shipping cost table from the sales channel's shipping methods

# 1.1.0
- New: building blocks – conditional sections per sales channel with auto-detection

# 1.0.0
- First version: per-sales-channel placeholders in shopping experience texts
