# Paint Tracker & Mixing Helper

Comprehensive tools for cataloguing miniature paints, browsing your collection, and performing accurate colour mixing and shading calculations — all inside WordPress.

- **Version:** 0.13.0  
- **Requires at least:** WordPress 6.0  
- **Tested up to:** WordPress 6.9  
- **License:** GPLv2 or later  

---

## Overview

Paint Tracker & Mixing Helper provides a full paint‑management system inside WordPress.  
It allows you to:

- Store paints with structured metadata  
- Organise paints into hierarchical ranges  
- Display paints in filterable tables  
- Perform **live two‑paint mixing**  
- Use an intelligent **shading/highlighting helper**  
- Import/export paints via CSV  
- Auto‑assign paints to multiple ranges using CSV data  

It is designed for miniature painters using systems such as Citadel, Vallejo, Army Painter, Two Thin Coats, and custom ranges.

---

## Features at a Glance

### ✔ Paint Database (Custom Post Type)
Each paint includes:

- **Name/title**
- **Identifier/number** (e.g., `70.861`, “Layer”, “Base”)
- **Base type**  
  - Acrylic  
  - Enamel  
  - Oil  
  - Lacquer  
- **Hex colour** (primary swatch)
- **Gradient Flag** (optional darker/lighter HEX for metallics & shades; used to render metallic/shade-style swatches)
- **On shelf** (ownership tracking)
- **Exclude from shading helper**
- **Linked models/URLs** (repeatable fields)

### ✔ Paint Ranges (Hierarchical Taxonomy)
Supports:

- Parent/child product lines  
- Arbitrarily deep hierarchies  
- Sorted using WordPress' built‑in `term_order`  
- Used across all shortcodes and the admin UI  

### ✔ Colour Mixing Helper
A live, interactive tool:

- Choose a range  
- Select two paints  
- Set mixing ratios  
- See the exact resulting colour  
- Warns when incompatible base types are selected  
- Uses accurate RGB maths and gradient rendering  

### ✔ Shade Helper
A smart assistant for selecting:

- **Darker** paints (shading)  
- **Lighter** paints (highlighting)  

Features:

- Strict or relaxed hue matching  
- Range filtering  
- Respects “Exclude from shading helper”  
- Works with deep‑links (e.g., clicking a paint swatch opens shade helper preloaded with that paint)

### ✔ Paint Tables
Display collections anywhere with a shortcode.

Modes:

- **Dots mode** – compact rows with colour dots  
- **Row tint mode** – full‑width tinted rows  

Extras:

- Sortable by identifier or name  
- Link out to shade helper  
- Shows missing HEX warnings  
- Adjustable limits, filtering, and ownership visibility  

### ✔ CSV Import & Export
#### Export
Outputs a full dataset including:

- name  
- identifier  
- hex  
- base type  
- on shelf  
- gradient (metallic/shade flag)  
- ranges (pipe-separated)

#### Import
Supports two modes:

1. **Standard mode** — assign all imported paints to a single selected range  
2. **Pull range from CSV mode** — reads a `ranges` column  
   - Multiple ranges separated by a pipe: `Fantasy|Metallics|Bright`  
   - Automatically creates missing ranges  
   - Assigns each paint to all listed ranges  

---

## How to Use the Plugin

### 1. Create Paint Ranges
Go to **Paint Colours → Paint Ranges** and add ranges matching your paint lines.

Examples:

- Citadel  
  - Base  
  - Layer  
  - Shade  
- Vallejo  
  - Model Color  
  - Game Color  

### 2. Add Paints
Under **Paint Colours → Add New**:

- Enter the name  
- Add identifier (optional)  
- Choose base type  
- Enter main HEX colour  
- Metallic / Shade options to display a gradient swatch
- Assign one or more ranges  
- Mark whether it's “On the shelf”  
- Add model links if desired  

### 3. Display a Table on the Front End
Basic usage:

```
[paint_table range="citadel-base"]
```

Full example:

```
[paint_table range="vallejo-model-color" limit="-1" orderby="meta_number" shelf="any"]
```

### 4. Use the Mixing Helper

```
[mixing-helper]
```

Choose a range → select two paints → set parts → preview mixed colour.

### 5. Use the Shade Helper

```
[shade-helper]
```

Select a paint → instantly see recommended darker/lighter paints.

Works seamlessly with swatch-clicking from paint tables.

### 6. Importing Paints via CSV

Go to **Paint Colours → Import / Export**.

#### Standard Import
Choose a range → upload CSV → import.

#### Pull Range from CSV (multi‑range support)
Tick **Pull range from CSV**.  
CSV must include a header row with a `ranges` column.

Example cell:

```
Citadel Base|Citadel Layer|Experimental
```

Each term will be assigned; missing ranges will be created.

#### Expected CSV Columns
- **name**  
- **identifier**  
- **hex**  
- **base type** (`acrylic`, `enamel`, `oil`, `lacquer`)  
- **on shelf** (`0` or `1`, optional)  
- **gradient** (`0`, `1`, or `2`, optional; 0 = no gradient, 1 = metallic gradient, 2 = shade gradient) 
- **ranges** (optional; pipe-separated when using CSV-driven range assignment)

---

## Shortcodes Summary

### `[paint_table]`  
Displays a sortable, filterable paint table.

### `[mixing-helper]`  
Interactive two‑paint mixer.

### `[shade-helper]`  
Automatic shading/highlighting assistant.

---

## Installation

1. Upload the plugin to `/wp-content/plugins/`.  
2. Activate it.  
3. Create paint ranges.  
4. Add paints manually or import via CSV.  
5. Insert shortcode(s) into posts or pages.

---

## License
GPLv2 or later.
