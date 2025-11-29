=== Paint Tracker and Mixing Helper ===
Contributors:
Tags: paint, colours, mixing, miniature, hobby, table
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 0.9.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Paint Tracker and Mixing Helper lets you keep a structured list of your miniature paints in WordPress and use that data on the front end via three shortcodes:

* A paint table (`[paint_table]`) for listing paints from a range.
* A two-paint mixing helper (`[mixing-helper]`) for mixing two paints and previewing the result.
* A shade helper (`[shade-helper]`) that suggests suitable highlight and shadow colours for a base paint.

Version 0.9.0 adds:
* A new **“Exclude from shading helper”** toggle per paint.
* New Quick Edit and Bulk Edit support for the exclude option.
* Shade-helper logic updated to skip excluded paints.
* Table rows with no hex now get a watermark and are no longer clickable.
* Improved Quick Edit field population.

= Data model =

=== Custom post type: Paint Colours ===

* Post type: `paint_color`
* Label: “Paint Colours”

Each paint stores:

* Title – paint name.
* Paint number / code / type – free text.
* Base type – Acrylic, Enamel, Oil or Lacquer.
* Hex colour.
* On the shelf.
* Exclude from shading helper.
* Linked posts / URLs (repeatable).

=== Taxonomy: Paint Ranges ===

Hierarchical taxonomy used for grouping paints.

= Front-end shortcodes =

=== [paint_table] ===

Displays a table of paints with optional filters.

Attributes:

* `range`
* `limit`
* `orderby`
* `shelf`

In “rows” display mode, the row is tinted using the paint’s colour. Rows with no hex show a watermark and are not clickable.

If a Shading page URL is set, clicking a paint opens `[shade-helper]` with that paint pre-selected.

=== [mixing-helper] ===

Two-paint mixer with ratio inputs and live preview. Prevents mixing incompatible base types.

=== [shade-helper] ===

Standalone shading/highlighting tool.

Features:

* Range dropdown
* Paint selector
* Strict/relaxed hue modes
* **Respects “Exclude from shading helper”**
* Shows lighter and darker paints with ratios

If linked from the paint table, the chosen paint is pre-filled.

= Admin screens =

* Paint meta box
* Quick Edit and Bulk Edit for:
  - Base type
  - On the shelf
  - Exclude from shading helper
* CSV Import / Export
* Info & Settings

== Installation ==

1. Upload the plugin or install via Plugins → Add New.
2. Activate it.
3. Add paint ranges.
4. Add paints.
5. Use `[paint_table]`, `[mixing-helper]` and `[shade-helper]`.

== Frequently Asked Questions ==

= Does this replace a full inventory system? =

No. It is a lightweight paint tracker and colour helper.

= Can I use it for non-miniature paints? =

Yes.

= Can I customise the styling? =

Yes — override `public/css/style.css` in your theme.

== Changelog ==

= 0.9.0 =

* Added **Exclude from shading helper** meta.
* Added Quick Edit + Bulk Edit support for exclude option.
* Shade helper now ignores excluded paints.
* Table rows without a hex value now show a “No colour hex” watermark and are not clickable.
* Fixed Quick Edit not populating fields reliably.
* Improved layout of Quick Edit and Bulk Edit fields.
* General stability and UI adjustments.

== Upgrade Notice ==

= 0.9.0 =

Adds a new exclude-from-shading feature, UI improvements, safer Quick Edit behaviour and better shading recommendations.

== License ==

GPLv2 or later.
