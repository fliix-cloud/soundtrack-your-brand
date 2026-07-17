# Translation reference (DE / ES)

Cheat sheet for maintainers: enter these translations on [translate.wordpress.org](https://translate.wordpress.org/).  
**Not** loaded by WordPress. **Do not** ship this inside the plugin package (folder `docs/` is GitHub-only).

**Text domain:** `fliix-now-playing-for-soundtrack-your-brand`  
**Original catalogs (Git only):** `docs/translations/*.po`  
The plugin has **no** `Domain Path` and **no** `languages/` directory.

---

## So läuft das auf WordPress.org ab

1. **Plugin veröffentlichen / aktualisieren** (ohne `languages/`, ohne `.po`/`.mo`/`.l10n.php`/`.pot` im ZIP).
2. **Warten** (oft wenige Stunden): GlotPress importiert die englischen Quellstrings aus dem Plugin-Code.
3. **Projekt öffnen** auf [translate.wordpress.org](https://translate.wordpress.org/) (Slug suchen).
4. **Sprache wählen**, z. B. **Deutsch** (`de` / `de_DE`) oder **Español** (`es` / `es_ES`).
5. **Übersetzen:**
   - **Standard:** String für String in GlotPress eintragen (Tabelle unten als Vorlage).
   - **Schneller (empfohlen für dich):** als Plugin-Autor **Project Translation Editor (PTE)** für DE und ES beantragen → dann darfst du die `.po` aus `docs/translations/` importieren.
6. Vorschläge im Status *waiting* brauchen **Freigabe** durch PTE/GTE der Locale, bevor Language Packs ausgeliefert werden.
7. Nutzer erhalten Übersetzungen unter **Dashboard → Aktualisierungen → Übersetzungen** (nicht mehr aus dem Plugin-ZIP).

### PTE beantragen (Bulk-Import der `.po`)

Als Plugin-Autor kannst du auf dem [Make WordPress Polyglots Blog](https://make.wordpress.org/polyglots/) einen kurzen Post schreiben, z. B.:

> **PTEs requested for fliix – Now Playing for Soundtrack Your Brand**  
> Plugin: https://wordpress.org/plugins/fliix-now-playing-for-soundtrack-your-brand/  
> I am the plugin author and would like PTE for **German (de_DE)** and **Spanish (es_ES)**.  
> @… (optional locale team)

Nach Freigabe: im GlotPress-Projekt der Locale → **Import** → `.po` aus `docs/translations/` hochladen.

### Tipps

- Platzhalter exakt beibehalten (`%s`, `%1$s`, `%d`, …).
- Nach String-Änderungen im Code importiert GlotPress neu; alte Übersetzungen können *fuzzy* werden.
- Quelle dieser Tabelle: frühere gebündelte DE/ES-Kataloge.

### Links (sobald das Plugin live ist)

- Projekt: https://translate.wordpress.org/projects/wp-plugins/fliix-now-playing-for-soundtrack-your-brand/
- Deutsch (Development): https://translate.wordpress.org/projects/wp-plugins/fliix-now-playing-for-soundtrack-your-brand/dev/de/default/
- Español (Development): https://translate.wordpress.org/projects/wp-plugins/fliix-now-playing-for-soundtrack-your-brand/dev/es/default/

Solange noch kein Stable-Tag gesetzt ist, meist im Subprojekt **Development** arbeiten; danach gibt es zusätzlich **Stable**.

---

## String table

| # | Context | English (msgid) | German (de_DE) | Spanish (es_ES) |
|---|---------|-----------------|----------------|-----------------|
| 1 |  | Account / Location | Konto / Standort | Cuenta / Ubicación |
| 2 |  | Album art (square only) | Albumcover (nur quadratisch) | Carátula del álbum (solo cuadrada) |
| 3 |  | Album art for %s | Albumcover für %s | Carátula del álbum para %s |
| 4 |  | Alignment | Ausrichtung | Alineación |
| 5 |  | Animated waves | Animierte Wellen | Ondas animadas |
| 6 |  | API Base URL | API-Basis-URL | URL base de la API |
| 7 |  | API Configuration | API-Konfiguration | Configuración de la API |
| 8 |  | API request failed with HTTP status %d. | Die API-Anfrage ist mit dem HTTP-Status %d fehlgeschlagen. | La solicitud a la API falló con el estado HTTP %d. |
| 9 |  | API Token | API Token | Token de la API |
| 10 |  | API token is not configured. | Das API-Token ist nicht konfiguriert. | El token de la API no está configurado. |
| 11 |  | Artist | Künstler | Artista |
| 12 |  | Artist name | Künstlername | Nombre del artista |
| 13 |  | Artwork | Grafik | Ilustración |
| 14 |  | A token is saved and encrypted. It cannot be viewed — enter a new value only to replace it. Leave blank to keep the current token. | Ein Token wird gespeichert und verschlüsselt. Es kann nicht angezeigt werden – geben Sie nur dann einen neuen Wert ein, wenn Sie es ersetzen möchten. Lassen Sie das Feld leer, um das aktuelle Token beizubehalten. | Se guarda un token cifrado. No se puede ver: introduce un nuevo valor solo si deseas reemplázalo. Déjalo en blanco para conservar el token actual. |
| 15 |  | Bordered frame with icon and text | Rahmen mit Rand, Symbol und Text | Marco con borde, icono y texto |
| 16 |  | Center | Mittig | Centro |
| 17 |  | Classic | Klassisch | Clásico |
| 18 |  | Color | Farbe | Color |
| 19 |  | Compact | Kompakt | Compacto |
| 20 |  | Configure the default appearance of the now playing widget. Individual shortcodes can override some settings via attributes. | Konfigurieren Sie das Standarddesign des Widgets „Aktuelle Wiedergabe“. Einzelne Shortcodes können bestimmte Einstellungen über Attribute überschreiben. | Configura la apariencia por defecto del widget de «está sonando». Cada shortcode puede anular algunos ajustes mediante atributos. |
| 21 |  | Configure your Soundtrack API credentials. The token is sent as an Authorization: Basic header with each request. | Konfigurieren Sie Ihre Anmeldedaten für die Soundtrack-API. Das Token wird bei jeder Anfrage als „Authorization: Basic“-Header gesendet. | Configura tus credenciales de la Soundtrack API. El token se envía como cabecera «Authorization: Basic» con cada solicitud. |
| 22 |  | Copied! | Kopiert! | ¡Copiado! |
| 23 |  | Copy | Kopieren | Copiar |
| 24 |  | Copy failed. | Kopieren fehlgeschlagen. | Error al copiar. |
| 25 |  | Currently playing: | Aktuell läuft: | Sonando ahora: |
| 26 |  | Currently playing label | "Aktuell läuft" Label | Etiqueta «sonando ahora» |
| 27 |  | Custom | Benutzerdefiniert | Personalizado |
| 28 |  | Display currently playing tracks from Soundtrack Your Brand sound zones via shortcode. | Zeige die aktuell abgespielten Titel aus den Soundzones von „Soundtrack Your Brand“ über einen Shortcode an. | Muestra las pistas en reproducción de las zonas sonoras de Soundtrack Your Brand mediante shortcode. |
| 29 |  | Display mode | Anzeigemodus | Modo de visualización |
| 30 |  | Duplicate slug. | Doppelter Slug. | Slug duplicado. |
| 31 |  | Enter your API token | Geben Sie Ihren API-Token ein | Introduce tu token de la API |
| 32 |  | Failed to fetch sound zones. | Das Abrufen der Soundzonen ist fehlgeschlagen. | No se pudieron obtener las zonas sonoras. |
| 33 |  | Failed to save mappings. | Das Speichern der Zuordnungen ist fehlgeschlagen. | No se pudieron guardar las asignaciones. |
| 34 |  | Fallback text | Ersatztext | Texto de reserva |
| 35 |  | Fetch / Refresh SoundZones from API | SoundZones über die API abrufen / aktualisieren | Obtener / Actualizar SoundZones desde la API |
| 36 |  | Fetched %d sound zones. | %d Soundzonen wurden abgerufen. | Se obtuvieron %d zonas sonoras. |
| 37 |  | Fetching sound zones… | Soundzonen werden abgerufen… | Obteniendo zonas sonoras… |
| 38 |  | Fine-tune fonts and colors for each text element. | Passen Sie Schriftarten und Farben für jedes Textelement individuell an. | Ajusta fuentes y colores de cada elemento de texto. |
| 39 |  | fliix - Marc Werner | fliix - Marc Werner | fliix - Marc Werner |
| 40 |  | How long (in seconds) to cache now playing data. Minimum 10, maximum 120. | Wie lange (in Sekunden) sollen die Daten der aktuell wiedergegebenen Titel zwischengespeichert werden? Mindestens 10, höchstens 120. | Cuánto tiempo (en segundos) se almacena en caché los datos de «ahora suena». Mínimo 10, máximo 120. |
| 41 |  | How the visual indicator appears beside the track. | So wird die visuelle Anzeige neben der Spur dargestellt. | Cómo aparece el indicador visual junto a la pista. |
| 42 |  | https://github.com/fliix-cloud/fliix-now-playing-for-soundtrack-your-brand | https://github.com/fliix-cloud/fliix-now-playing-for-soundtrack-your-brand | https://github.com/fliix-cloud/fliix-now-playing-for-soundtrack-your-brand |
| 43 |  | Icon / Artwork | Symbol / Grafik | Icono / Ilustración |
| 44 |  | Invalid API response. | Ungültige API-Antwort. | Respuesta de la API no válida. |
| 45 |  | Invalid mapping data. | Ungültige Zuordnungsdaten. | Datos de asignación no válidos. |
| 46 |  | Invalid slug format. | Ungültiges Slug-Format. | Formato de slug no válido. |
| 47 |  | Label | Label | Etiqueta |
| 48 |  | Labels shown when playing or idle. | Bezeichnungen, die während der Wiedergabe oder im Ruhezustand angezeigt werden. | Etiquetas mostradas durante la reproducción o en inactividad. |
| 49 |  | Large (120px) | Groß (120px) | Grande (120px) |
| 50 |  | Layout | Layout | Diseño |
| 51 |  | Left | Links | Izquierda |
| 52 |  | Map each SoundZone to a unique slug for use in the shortcode. Fetch zones from the API, assign slugs, then click Save All Mappings. | Ordnen Sie jeder SoundZone einen eindeutigen Slug für die Verwendung im Shortcode zu. Rufen Sie die Zonen über die API ab, weisen Sie ihnen Slugs zu und klicken Sie anschließend auf „Alle Zuordnungen speichern“. | Asigna a cada SoundZone un slug único para usarlo en el shortcode. Obtén las zonas desde la API, asígnales slugs y haz clic en «Guardar todas las asignaciones». |
| 53 |  | Mappings saved successfully. | Zuordnungen wurden erfolgreich gespeichert. | Asignaciones guardadas correctamente. |
| 54 |  | Medium (80px) | Mittel (80px) | Mediano (80px) |
| 55 |  | Minimal | Mindestens | Mínimo |
| 56 |  | Modern Card | Moderne Karte | Tarjeta moderna |
| 57 |  | nagold | nagold | nagold |
| 58 |  | No music playback at the moment. | Derzeit keine Musik-Wiedergabe | No se está reproduciendo música en este momento. |
| 59 |  | No slug specified. | Es wurde kein Slug angegeben. | No se ha especificado ningún slug. |
| 60 |  | No sound zones found for this API token. | Für dieses API-Token wurden keine Sound-Zonen gefunden. | No se encontraron zonas sonoras para este token de API. |
| 61 |  | No sound zones loaded. Click "Fetch / Refresh SoundZones from API" first. | Es wurden keine SoundZones geladen. Klicken Sie zunächst auf „SoundZones von der API abrufen/aktualisieren“. | No se cargaron zonas sonoras. Haz clic primero en «Obtener / Actualizar SoundZones desde la API». |
| 62 |  | No sound zones loaded. Click "Fetch / Refresh SoundZones from API" to get started. | Es wurden keine SoundZones geladen. Klicken Sie auf „SoundZones von der API abrufen/aktualisieren“, um loszulegen. | No se cargaron zonas sonoras. Haz clic en «Obtener / Actualizar SoundZones desde la API» para empezar. |
| 63 |  | Now playing data is being fetched. | Die Daten für die aktuelle Wiedergabe werden gerade abgerufen. | Se están obteniendo los datos de «sonando ahora». |
| 64 |  | Paired | Verbunden | Emparejada |
| 65 |  | Permission denied. | Zugriff verweigert. | Permiso denegado. |
| 66 |  | Playing label | Wiedergabelabel | Etiqueta «reproduciendo» |
| 67 |  | Please fix validation errors before saving. | Bitte beheben Sie die Validierungsfehler, bevor Sie speichern. | Por favor, corrige los errores de validación antes de guardar. |
| 68 |  | Right | Rechts | Derecha |
| 69 |  | Rounded card with soft shadow | Abgerundete Karte mit sanftem Schatten | Tarjeta redondeada con sombra suave |
| 70 |  | Save All Mappings | Alle Zuordnungen speichern | Guardar todas las asignaciones |
| 71 |  | Save Settings | Einstellungen speichern | Guardar ajustes |
| 72 |  | Saving mappings… | Zuordnungen werden gespeichert… | Guardando asignaciones… |
| 73 |  | Settings | Einstellungen | Ajustes |
| 74 |  | Single-line, space-efficient | Einreihig, platzsparend | Línea única, uso eficiente del espacio |
| 75 |  | Size | Größe | Tamaño |
| 76 |  | Size (px) | Breite (px) | Tamaño (px) |
| 77 |  | Slug | Slug | Slug |
| 78 |  | Small (48px) | Klein (48px) | Pequeño (48px) |
| 79 |  | Song | Lied | Canción |
| 80 |  | Soundtrack API Documentation | Soundtrack API Dokumentation | Documentación de la Soundtrack API |
| 81 |  | Soundtrack Your Brand | Soundtrack Your Brand | Soundtrack Your Brand |
| 82 |  | Soundtrack Your Brand – Now Playing | Soundtrack Your Brand – Aktuell läuft | Soundtrack Your Brand – Está sonando |
| 83 |  | Soundtrack Your Brand – Now Playing: Composer autoloader not found. Run "composer install" in the plugin directory. | Soundtrack Your Brand – Aktuell läuft: Der Composer-Autoloader wurde nicht gefunden. Führen Sie „composer install“ im Plugin-Verzeichnis aus. | Soundtrack Your Brand – Está sonando: No se encontró el cargador automático de Composer. Ejecuta «composer install» en el directorio del plugin. |
| 84 |  | SoundZone Mapping | SoundZone Zuordnung | Asignación de SoundZone |
| 85 |  | Sound zones refreshed successfully. | Die Soundzonen wurden erfolgreich aktualisiert. | Zonas sonoras actualizadas correctamente. |
| 86 |  | Static icon | Statisches Symbol | Icono estático |
| 87 |  | Status | Status | Estado |
| 88 |  | Template | Vorlage | Plantilla |
| 89 |  | Template, alignment, and visibility options. | Optionen für Vorlagen, Ausrichtung und Sichtbarkeit. | Opciones de plantilla, alineación y visibilidad. |
| 90 |  | Text | Text | Texto |
| 91 |  | Text only, no decoration | Nur Text, keine Verzierungen | Solo texto, sin decoración |
| 92 |  | The plugin uses lazy, on-demand caching. Now playing data is fetched from the API when a visitor loads a page with the shortcode (or when the cache expires during live refresh). The frontend polls for updates at this interval so idle visitors see new tracks without reloading. No WP-Cron is used. | Das Plugin nutzt ein verzögertes Caching auf Abruf. Die Daten für die aktuelle Wiedergabe werden von der API abgerufen, wenn ein Besucher eine Seite mit dem Shortcode lädt (oder wenn der Cache während einer Live-Aktualisierung abläuft). Das Frontend fragt in diesem Intervall nach Aktualisierungen, sodass Besucher, die gerade inaktiv sind, neue Titel sehen, ohne die Seite neu laden zu müssen. Es wird kein WP-Cron verwendet. | El plugin usa un almacenamiento en caché diferido y bajo demanda. Los datos de «ahora suena» se obtienen de la API cuando un visitante carga una página con el shortcode (o cuando el caché caduca durante una actualización en vivo). La interfaz consulta actualizaciones en este intervalo para que los visitantes inactivos vean nuevas pistas sin recargar. No se usa WP-Cron. |
| 93 |  | This slug is already used. | Dieser Slug wird bereits verwendet. | Este slug ya está en uso. |
| 94 |  | Token configured — enter a new token to replace | Token konfiguriert – geben Sie ein neues Token ein, um das aktuelle zu ersetzen | Token configurado: introduce un nuevo token para reemplazarlo |
| 95 |  | Typography & Colors | Typografie und Farben | Tipografía y colores |
| 96 |  | Unknown GraphQL error. | Unbekannter GraphQL-Fehler. | Error GraphQL desconocido. |
| 97 |  | Unknown slug: %s | Unbekannter Slug: %s | Slug desconocido: %s |
| 98 |  | Unpaired | Getrennt | Sin emparejar |
| 99 |  | Update Interval & Caching | Aktualisierungsintervall und Zwischenspeicherung | Intervalo de actualización y caché |
| 100 |  | Update Interval (seconds) | Aktualisierungsintervall (Sekunden) | Intervalo de actualización (segundos) |
| 101 |  | Use lowercase letters, numbers, hyphens, and underscores only. | Verwenden Sie ausschließlich Kleinbuchstaben, Zahlen, Bindestriche und Unterstriche. | Usa solo letras minúsculas, números, guiones y guiones bajos. |
| 102 |  | Visibility | Sichtbarkeit | Visibilidad |
| 103 |  | Weight | Gewicht | Peso |
| 104 |  | Widget Appearance | Darstellung des Widgets | Apariencia del widget |
| 105 |  | Your API token is encrypted before storage and sent as: Authorization: Basic <token> | Ihr API-Token wird vor der Speicherung verschlüsselt und wie folgt gesendet: Authorization: Basic <token> | Tu token de la API se cifra antes de almacenarlo y se envía como: Authorization: Basic <token> |
| 106 |  | Zone ID | Zonen ID | ID de zona |
| 107 |  | Zone Name | Zonen Name | Nombre de zona |

## Counts

- German entries: **107**
- Spanish entries: **107**
- Unique source strings in this table: **107**

---

*Generated for maintainer use. Safe to keep in the GitHub repo; exclude from WordPress.org ZIP if you prefer a lean package (optional).*
