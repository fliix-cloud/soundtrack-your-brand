# Translations (maintainer reference only)

These files are for **GitHub / maintainers only**. They are **not** shipped in the WordPress.org plugin package (`docs/` is excluded by `build-release.ps1`).

WordPress.org delivers translations as **language packs** from [translate.wordpress.org](https://translate.wordpress.org/). The plugin has no `Domain Path` and no `languages/` folder.

| Path | Purpose |
|------|---------|
| [`TRANSLATIONS.md`](./TRANSLATIONS.md) | DE/ES string table + GlotPress/PTE how-to |
| `fliix-now-playing-for-soundtrack-your-brand-de_DE.po` | German reference catalog |
| `fliix-now-playing-for-soundtrack-your-brand-es_ES.po` | Spanish reference catalog |

## Use cases

1. Copy strings into GlotPress after the plugin is published.
2. Import `.po` files if you are a **PTE** for that locale.
3. Local testing: put compiled files under `wp-content/languages/plugins/` on a dev site — never inside the plugin package for release.

## String extraction (optional, local only)

```bash
wp i18n make-pot . docs/translations/fliix-now-playing-for-soundtrack-your-brand.pot
```

Do not commit or ship compiled locale catalogs (`.mo`, `.l10n.php`) in the plugin root.
