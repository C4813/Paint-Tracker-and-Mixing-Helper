# Paint Tracker & Mixing Helper

Shortcodes to display your miniature paint collection, plus interactive mixing and shading helpers for specific colours.

- **Version:** 0.10.4
- **Requires at least:** WordPress 6.0  
- **Tested up to:** WordPress 6.7  
- **License:** GPLv2 or later

---

## Overview

Paint Tracker & Mixing Helper lets you keep a structured list of your miniature paints in WordPress and use that data on the front end via three shortcodes:

- A **paint table** (`[paint_table]`) for listing paints from a range.
- A **two-paint mixing helper** (`[mixing-helper]`) for colour mixing and live preview.
- A **shade helper** (`[shade-helper]`) that suggests darker and lighter paints for highlighting and shading.

All data is stored in a custom post type and taxonomy, so paints can be edited like regular WordPress content.

This version also adds an **“Exclude from shading helper”** option per paint, allowing you to prevent certain paints (e.g., washes, technicals, or unsuitable colours) from ever being used as a suggested highlighter or darkener.

---

## Data model

### Custom post type: Paint Colours

The plugin registers a custom post type:

- **Post type:** `paint_color`
- **Label:** “Paint Colours”

Each paint stores:

- **Title** – paint name.
- **Paint number / code / type** – free text field (e.g. `70.800`, `Base`, `Layer`).
- **Base type** – required; one of:
  - `Acrylic`
  - `Enamel`
  - `Oil`
  - `Lacquer`
- **Hex colour** – used for front-end colour display.
- **On the shelf** – simple ownership tracking.
- **Exclude from shading helper** – prevents this paint from being chosen as a lightener or darkener.
- **Linked posts / URLs** – repeatable label + URL fields, shown in the front-end table.

### Taxonomy: Paint Ranges

The plugin registers a hierarchical taxonomy:

- **Taxonomy:** `paint_range`
- **Label:** “Paint Ranges”
- **Usage:** e.g. “Vallejo Model Color”, “Citadel Layer”, etc.

Ranges are used for:

- Filtering the `[paint_table]` output.
- Filtering dropdowns in the mixing and shade helpers.
- CSV import/export scoping.

---

## Front-end shortcodes

### `[paint_table]`

Displays a filterable table of paints.

**Attributes**

- `range` – optional taxonomy slug.  
- `limit` – number of paints; `-1` for all.  
- `orderby` – `meta_number` (default) or `title`.  
- `shelf` – `yes` or `any`.

**Example**

```
[paint_table range="vallejo-model-color" limit="-1" orderby="meta_number" shelf="any"]
```

**Table columns**

- Optional colour swatch column (“dots” mode).
- Paint **name**.
- **Code / Type**.
- **Models**: links from the repeatable metafields.

**Display modes**

Configured in **Paint Colours → Info & Settings**:

- **Dots** – compact swatch + text.  
- **Rows** – the entire row is tinted with the paint colour.

If a paint has no hex colour:

- Rows show a subtle *“No colour hex”* watermark.
- Such rows are not clickable (even when row-highlight mode is active).

If a **Shading page URL** is set, table rows/swatch links open the shade helper pre-selected with the chosen paint.

---

### `[mixing-helper]`

Two-paint mixing calculator.

**Features**

- Range selector.
- Two paint selectors.
- “Parts” inputs to set ratios.
- Live mixed colour preview.
- Base-type compatibility warnings (Acrylic / Enamel / Oil / Lacquer cannot be mixed with one another).

---

### `[shade-helper]`

Standalone shading and highlighting tool.

**Features**

- Range selector.
- Paint selector with swatch.
- Strict vs relaxed hue behaviour.
- Suggests lighter and darker paints.
- **Respects the new “Exclude from shading helper” flag.**
- Deep-linking support from the paint table.

---

## Admin screens

Includes:

- Full meta box for editing paint details.
- Quick Edit (number, hex, on shelf, base type, exclude-from-shading).
- Bulk Edit (base type, on shelf, exclude-from-shading).
- CSV Import / Export.
- Info & Settings page for:
  - Shading page URL
  - Paint table display mode
  - Shade helper hue mode (strict / relaxed)

---

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **Paint Tracker & Mixing Helper**.
3. Create paint ranges.
4. Add paints.
5. Use shortcodes:
   - `[paint_table]`
   - `[mixing-helper]`
   - `[shade-helper]`

---

## License

GPLv2 or later.
