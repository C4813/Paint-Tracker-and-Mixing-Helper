=== Paint Tracker and Mixing Helper ===
Contributors: C4813
Tags: painting, hobby, miniatures, colours, tools
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.13.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete miniature-paint management system for WordPress: paint tables, colour mixing, shading/highlighting suggestions, and CSV import/export.

== Description ==

Paint Tracker & Mixing Helper is a full-featured paint database and colour-tools plugin for miniature painters.

### Key Features

- Custom post type for paints
- Hierarchical paint ranges
- Paint table shortcode (now sortable by **identifier, name, or type**)
- Two‑paint mixing tool with compatibility checks
- Smart shading/highlighting helper
- CSV import & export
- Multi‑range auto‑assignment via CSV
- Metallic/shade gradient support (`0`, `1`, `2`)
- Ownership tracking
- Linked URLs
- Exclude‑from‑shading flag

---

== Data Model ==

### Custom Post Type: paint_color
Fields:

- Name (title)
- Identifier
- **Type (new)**
- Base type (acrylic/enamel/oil/lacquer)
- Hex colour
- Gradient (0/1/2 — none/metallic/shade)
- On shelf
- Exclude from shading helper
- Linked URLs

### Taxonomy: paint_range
Hierarchical paint ranges used in all UI + shortcodes.

---

== Shortcodes ==

### 1. `[paint_table]`
Displays a sortable, filterable table of paints.

**Attributes:**
- `range`
- `limit`
- `orderby` — `meta_number`, `title`, **`type`**
- `shelf`

Example:
```
[paint_table range="two-thin-coats" orderby="type"]
```

---

### 2. `[mixing-helper]`
Two-paint live mixer with ratio control and compatibility warnings.

### 3. `[shade-helper]`
Darker/lighter paint suggestions with hue‑matching modes.

---

== CSV Import & Export ==

### Export includes:
- name  
- identifier  
- **type**  
- hex  
- base type  
- on shelf  
- gradient (0/1/2)  
- ranges  

### Import
Two modes:

#### Standard
Assign all paints to one selected range.

#### Pull range from CSV
Uses `ranges` column, pipe‑separated.

**Expected CSV columns:**
1. name  
2. identifier  
3. **type**  
4. hex  
5. base type  
6. on shelf  
7. gradient  
8. ranges  

---

== Installation ==
1. Upload plugin  
2. Activate  
3. Create paint ranges  
4. Add/import paints  
5. Use shortcodes  

---

== Changelog ==

= 0.13.1 =
* Added support for ordering paint tables by Type
* Updated readme files to document new Type field and CSV changes
* Internal consistency improvements for gradient/metallic/shade flags

