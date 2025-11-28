# Paint Tracker & Mixing Helper

Shortcodes to display your miniature paint collection, plus interactive mixing and shading helpers for specific colours.

- **Version:** 0.8.3  
- **Requires at least:** WordPress 6.0  
- **Tested up to:** WordPress 6.7  
- **License:** GPLv2 or later

---

## Overview

Paint Tracker & Mixing Helper lets you keep a structured list of your miniature paints in WordPress and use that data on the front end via three shortcodes:

- A **paint table** (`[paint_table]`) for listing paints from a range.
- A **two-paint mixing helper** (`[mixing-helper]`) for mixing two paints and previewing the result.
- A **shade helper** (`[shade-helper]`) that suggests suitable highlight and shadow colours for a base paint.

All data is stored in a custom post type and taxonomy, so you can edit paints in the admin like any other content.

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
- **Hex colour** – used for front-end colour display (e.g. `#2f353a`).
- **On the shelf** – checkbox for tracking whether you currently own the paint.
- **Linked posts / URLs** – one or more external links per paint (label + URL), shown in the **Models** column on the front-end table.

### Taxonomy: Paint Ranges

The plugin registers a hierarchical taxonomy:

- **Taxonomy:** `paint_range`
- **Label:** “Paint Ranges”
- **Usage:** assign paints to ranges such as “Vallejo Model Color”, “Citadel Layer”, etc.

Ranges are used to:

- Filter paints displayed in the `[paint_table]` shortcode.
- Filter paint dropdowns in the mixing and shade helpers.
- Limit CSV import/export to a particular range.

---

## Front-end shortcodes

### `[paint_table]`

Displays a table of paints with optional filtering by range and “On the shelf”.

**Attributes**

- `range` – (optional) `paint_range` slug. If omitted, all ranges are shown.  
- `limit` – number of paints to show. Use `-1` to show all matches.  
- `orderby` – `meta_number` (paint number / code) or `title`. Defaults to `meta_number`.  
- `shelf` – `yes` to show only paints marked “On the shelf”; `any` to show all.

**Example**

```
[paint_table range="vallejo-model-color" limit="-1" orderby="meta_number" shelf="any"]
```

**Table columns**

- Optional **swatch** column (in “dots” mode).
- **Colour** – paint name.
- **Code / Type** – paint number / code.
- **Models** – links created via the “Linked posts / URLs” meta box.

The table can be displayed in one of two modes (configured under **Paint Colours → Info & Settings**):

- **Dots** – a small colour swatch column.
- **Rows** – the entire row background is coloured with automatic light/dark text for legibility.

If you set a **Shading page URL** (see settings below), the colour swatch / row becomes clickable and links to your shade helper page, passing the paint ID and hex value as query parameters.

---

### `[mixing-helper]`

Shows the main two-paint mixing helper.

**Features**

- Range dropdown to filter the paint list.
- Two paint selectors (left and right).
- “Parts” inputs for each paint to define the mixing ratio.
- Preview area showing the resulting mixed colour and hex code.
- Base-type safety check: paints with incompatible base types (e.g. Acrylic vs Enamel vs Oil vs Lacquer) cannot be mixed. A warning is shown instead of a mixed result.

### `[shade-helper]`

Shows the shade helper as a standalone tool.

**Features**

- Range dropdown to filter the paint list.
- Paint selector with colour swatch and label.
- Automatic suggestions for highlight and shadow colours (strict or relaxed hue behaviour).
- Deep-linking support when coming from the paint table.

---

## Admin screens

Includes:
- Enhanced Edit screen
- Quick Edit and Bulk Edit fields
- CSV Import / Export
- Info & Settings (paint table display mode, shading page URL, hue behaviour)

---

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **Paint Tracker and Mixing Helper**.
3. Add paint ranges.
4. Add paints.
5. Create pages using the shortcodes:
   - `[paint_table]`
   - `[mixing-helper]`
   - `[shade-helper]`

---

## License

GPLv2 or later.
