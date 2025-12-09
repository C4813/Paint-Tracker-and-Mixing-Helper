=== Paint Tracker and Mixing Helper ===
Contributors: C4813
Tags: painting, hobby, miniatures, colours, tools
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.13.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete miniature-paint management system for WordPress: paint tables, colour mixing, shading/highlighting suggestions, and CSV import/export.

== Description ==

Paint Tracker & Mixing Helper is a full-featured paint database and colour-tools plugin for miniature painters.  
It allows you to catalogue your paints, organise them into ranges, display them on the front end, and use interactive tools for mixing, shading, and highlighting.

Perfect for hobbyists using Citadel, Vallejo, Army Painter, Two Thin Coats, and custom paint collections.

### **Key Features**

- Custom post type for paints
- Hierarchical paint ranges (supports parent/child ranges)
- Paint table shortcode with multiple display modes
- Interactive two-paint colour mixing tool
- Smart shading + highlighting helper
- CSV import & export
- “Pull range from CSV” mode (supports assigning multiple ranges per paint)
- Optional metallic colour support (rendered with a gradient-style swatch for metallics, shades, etc.)
- On-shelf ownership tracking
- Linked models/URL fields
- Exclude-from-shading flag for special paints
- Fully translatable and theme-friendly

---

== Data Model ==

### **Custom Post Type: paint_color**
Each paint stores:

- Name (post title)
- Identifier/number (e.g. 70.861, Layer, Base, Wash)
- Base type (acrylic, enamel, oil, lacquer)
- Primary hex colour
- Optional metallic colour
- On-shelf toggle (ownership tracking)
- Exclude from shading helper
- Linked URL fields

### **Taxonomy: paint_range**
A hierarchical taxonomy used to organise paints.

Supports nested ranges, e.g.:

- Citadel  
  - Base  
  - Layer  
  - Shade  

Used across all shortcodes and admin screens.

---

== Shortcodes ==

### **1. Paint Table — `[paint_table]`**

Displays a sortable, filterable table of paints.

**Attributes:**
- `range` – taxonomy slug  
- `limit` – number of paints (default: 100, use `-1` for all)  
- `orderby` – `meta_number` (default) or `title`  
- `shelf` – `yes` or `any`  

Example:

```
[paint_table range="vallejo-model-color" limit="-1" orderby="meta_number" shelf="any"]
```

Two display modes are available via plugin settings:

- **Dots mode** — compact list with colour swatches  
- **Row tint mode** — entire row coloured by the paint hex  

Tables also support:

- Linked model URLs  
- Missing HEX colour warnings  
- Click-through to shade helper (if configured)

---

### **2. Mixing Helper — `[mixing-helper]`**

A live two-paint colour mixing calculator.

Features:

- Range dropdown  
- Two paint selectors  
- “Parts” ratio input  
- Real-time mixed colour preview  
- Base-type compatibility warnings  
- Gradient blending for metallics/shades  

---

### **3. Shade Helper — `[shade-helper]`**

Provides instant lighter/darker paint suggestions.

Features:

- Range filter  
- Paint selector with swatch  
- Strict and relaxed hue modes  
- “Exclude from shading helper” respected  
- Optional deep-linking from paint table rows  

---

== CSV Import & Export ==

Located under **Paint Colours → Import / Export**.

### **Export**

Exports all paints in the selected range, including:

- name  
- identifier  
- hex  
- base type  
- on shelf  
- gradient (metallic or shade flag)  
- ranges (pipe-separated: `Vallejo|Vallejo Model Color`)  

---

### **Import**

Two modes are supported:

#### **Standard Mode**
Assigns all imported paints to one selected range.

#### **Pull Range from CSV Mode**
When enabled:

- The `ranges` column is required  
- Multiple ranges are separated by a pipe (`|`)  
- Missing ranges are automatically created  
- Paints can belong to several ranges simultaneously  

**Expected CSV columns:**

1. name  
2. identifier  
3. hex  
4. base type (`acrylic`, `enamel`, `oil`, `lacquer`)  
5. on shelf (`0` or `1`, optional)  
6. gradient (`0`, `1`, or `2`, optional; 0 = no gradient, 1 = metallic gradient, 2 = shade gradient) 
7. ranges (pipe-separated, optional unless in CSV-range mode)

---

== Installation ==

1. Upload plugin to `/wp-content/plugins/`.
2. Activate **Paint Tracker & Mixing Helper**.
3. Create paint ranges under **Paint Colours → Paint Ranges**.
4. Add paints manually or import via CSV.
5. Use the shortcodes:
   - `[paint_table]`
   - `[mixing-helper]`
   - `[shade-helper]`

---

== Screenshots ==

1. Admin paint editor with metadata fields  
2. Paint table (dots mode)  
3. Paint table (row tint mode)  
4. Mixing Helper  
5. Shade Helper  
6. CSV Import/Export screen  

(You may replace these with real screenshots.)

---

== Changelog ==

---

== Upgrade Notice ==
