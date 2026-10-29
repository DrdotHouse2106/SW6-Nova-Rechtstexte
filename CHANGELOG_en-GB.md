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
