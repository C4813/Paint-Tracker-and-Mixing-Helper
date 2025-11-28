=== Paint Tracker and Mixing Helper ===
Contributors: 
Tags: paint, colours, mixing, miniature, hobby, table
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 0.8.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Paint Tracker and Mixing Helper lets you keep a structured list of your miniature paints in WordPress and use that data on the front end via three shortcodes:

* A paint table (`[paint_table]`) for listing paints from a range.
* A two-paint mixing helper (`[mixing-helper]`) for mixing two paints and previewing the result.
* A shade helper (`[shade-helper]`) that suggests suitable highlight and shadow colours for a base paint.

All data is stored in a custom post type and taxonomy, so you can edit paints in the admin like any other content.

= Data model =

=== Custom post type: Paint Colours ===

The plugin registers a custom post type:

* Post type: `paint_color`
* Label: “Paint Colours”

Each paint stores:

* Title – paint name.
* Paint number / code / type – free text field (e.g. `70.800`, `Base`, `Layer`).
* Base type – required; one of:
  * Acrylic
  * Enamel
  * Oil
  * Lacquer
* Hex colour – used for front-end colour display (e.g. `#2f353a`).
* On the shelf – checkbox for tracking whether you currently own the paint.
* Linked posts / URLs – one or more external links per paint (label + URL), shown in the “Models” column on the front-end table.

=== Taxonomy: Paint Ranges ===

The plugin registers a hierarchical taxonomy:

* Taxonomy: `paint_range`
* Label: “Paint Ranges”
* Usage: assign paints to ranges such as “Vallejo Model Color”, “Citadel Layer”, etc.

Ranges are used to:

* Filter paints displayed in the `[paint_table]` shortcode.
* Filter paint dropdowns in the mixing and shade helpers.
* Limit CSV import/export to a particular range.

= Front-end shortcodes =

=== [paint_table] ===

Displays a table of paints with optional filtering by range and “On the shelf”.

Attributes:

* `range` – (optional) `paint_range` slug. If omitted, all ranges are shown.
* `limit` – number of paints to show. Use `-1` to show all matches.
* `orderby` – `meta_number` (paint number / code) or `title`. Defaults to `meta_number`.
* `shelf` – `yes` to show only paints marked “On the shelf”; `any` to show all.

Example:

`[paint_table range="vallejo-model-color" limit="-1" orderby="meta_number" shelf="any"]`

Table columns:

* Optional swatch column (in “dots” mode).
* Colour – paint name.
* Code / Type – paint number / code.
* Models – links created via the “Linked posts / URLs” meta box.

The table can be displayed in one of two modes (configured under Paint Colours → Info & Settings):

* Dots – a small colour swatch column.
* Rows – the entire row background is coloured with automatic light/dark text for legibility.

If you set a Shading page URL (see settings below), the colour swatch / row becomes clickable and links to your shade helper page, passing the paint ID and hex value.

=== [mixing-helper] ===

Shows the main two-paint mixing helper.

Features:

* Range dropdown to filter the paint list.
* Two paint selectors (left and right).
* “Parts” inputs for each paint to define the mixing ratio.
* Preview area showing the resulting mixed colour and hex code.
* Base-type safety check: paints with incompatible base types (for example Acrylic vs Enamel vs Oil vs Lacquer) cannot be mixed. A warning is shown instead of a mixed result.

This shortcode does not take attributes; it always operates on the full set of paints in your database (filtered client-side by range).

=== [shade-helper] ===

Shows the shade helper as a standalone tool.

Features:

* Range dropdown to filter the paint list.
* Paint selector with colour swatch and label.
* Automatic suggestions for highlight and shadow colours, based on:
  * Relative lightness of available paints.
  * A configurable hue behaviour:
    * Strict – prefers neutral darks/lights (for example blacks and whites).
    * Relaxed – allows similar hues from the same ranges to be used for shading and highlighting.
* Each suggestion displays the paint name, number/code and hex value.

If you configure a Shading page URL and click on a paint in the front-end table, the plugin:

* Redirects to your shade helper page.
* Pre-selects that paint using the `pct_shade_id` and `pct_shade_hex` query parameters.

= Admin screens =

=== Paint edit screen (meta box) ===

For each paint (post type `paint_color`) you get a meta box with:

* Paint number (text).
* Base type (required select: Acrylic / Enamel / Oil / Lacquer).
* Hex colour (text).
* On the shelf (checkbox).
* Linked posts / URLs:
  * Repeatable rows.
  * Each row has a Title (label) and Link URL.
  * These links appear in the “Models” column in `[paint_table]`.

Quick Edit and Bulk Edit are extended so you can edit “On the shelf” and base type directly from the list table.

=== CSV Import / Export ===

Under Paint Colours you get three extra submenu pages:

* Import from CSV
* Export to CSV
* Info & Settings

==== Import from CSV ====

Location: Paint Colours → Import from CSV

* Choose a target paint range.
* Upload a CSV file; each row creates one paint in that range.
* The plugin accepts an optional header row.

Import column order:

1. `title` – paint name (required).
2. `number` – paint number / code (optional).
3. `hex` – hex colour (with or without `#`).
4. `base_type` – `acrylic`, `enamel`, `oil` or `lacquer`.
5. `on_shelf` – `0` or `1`.

If `base_type` is missing or invalid, it falls back to `acrylic`.

==== Export to CSV ====

Location: Paint Colours → Export to CSV

You can:

* Filter by paint range.
* Optionally restrict to paints marked “On the shelf”.

Export columns:

1. `title`
2. `number`
3. `hex`
4. `base_type`
5. `on_shelf`
6. `ranges` – one or more paint range names, separated by `|`.

=== Info & Settings ===

Location: Paint Colours → Info & Settings

This page controls:

* Shading page URL
  * URL of the page containing your `[shade-helper]` shortcode.
  * When set, front-end paint table rows/swatch links point here with query parameters.
* Paint table display mode
  * `dots` – colour dots in a dedicated column.
  * `rows` – full-row highlight.
* Shade helper hue behaviour
  * `strict` – black/white style neutral shading.
  * `relaxed` – allows similar hues from your ranges for shading/highlighting.

Settings are saved automatically when you change them (the plugin’s admin JS submits the form on change/blur).

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the ZIP via Plugins → Add New → Upload Plugin.
2. Activate Paint Tracker and Mixing Helper.
3. Go to Paint Colours → Paint Ranges and create one or more ranges.
4. Add paints under Paint Colours → Add New, filling in base type, hex colour and any links.
5. Create pages using the shortcodes:
   * `[paint_table]`
   * `[mixing-helper]`
   * `[shade-helper]`
6. Configure the Shading page URL, table display mode and shade helper hue behaviour under Paint Colours → Info & Settings if needed.

== Frequently Asked Questions ==

= Does this plugin replace a full inventory or store management system? =

No. The plugin is designed specifically for tracking paint colours and using them in front-end tools. It does not handle orders, payments or stock control beyond the simple “On the shelf” flag.

= Can I use it for non-miniature paints? =

Yes. The data model is generic enough that you can store any kind of paint or colour range, as long as you are comfortable entering a hex colour and basic metadata.

= Can I customise the front-end styling? =

Yes. The plugin ships with a small stylesheet (`public/css/style.css`) that you can override or extend in your theme. The HTML structure is simple and uses predictable CSS classes.

== Changelog ==

= 0.8.3 =

* Added full support for Lacquer as a base type (Edit screen, Quick Edit, Bulk Edit and CSV import/export).
* Completed Info & Settings page with description of shortcodes, settings and CSV formats.
* Refined Quick Edit and Bulk Edit handling for paint metadata.
* Minor documentation and wording improvements.

== Upgrade Notice ==

= 0.8.3 =

This release completes Lacquer support and improves the Info & Settings documentation. It is fully backward compatible with earlier 0.8.x versions.

== License ==

This plugin is licensed under the GPLv2 or later.
