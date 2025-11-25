# Paint Tracker & Mixing Helper

A WordPress plugin for managing miniature paint collections and providing interactive colour tools such as a paint table, mixer, and shade helper.

---

## 📦 What This Plugin Does

Paint Tracker & Mixing Helper helps you:

- Maintain a structured library of your paints  
- Track which paints you currently own (“On the shelf”)  
- Store useful links for each paint (tutorials, reviews, example builds, etc.)  
- Display a searchable, filterable paint table on the frontend  
- Use interactive tools such as:
  - **Two-paint mixing helper**
  - **Shade helper with auto-generated lighter/darker mixes**

The plugin creates and manages:

### **Custom Post Type: “Paint Colours”**
Each paint includes:
- Name (post title)  
- Paint number  
- Hex colour  
- “On the shelf” flag  
- Optional linked posts/URLs  

### **Custom Taxonomy: “Paint Ranges”**
Use this to group paints — for example:
- Vallejo Model Color  
- Vallejo Game Color  
- Citadel  
- Army Painter  
- Etc.

---

## 📊 Shortcodes

### ### `[paint_table]`
Displays a table of paints, optionally filtered by range.

**Attributes:**
- `range` – taxonomy slug (optional)  
- `limit` – number of paints to show (`-1` = all)  
- `orderby` – `"meta_number"` or `"title"`  
- `shelf` – `"yes"` to show only paints on your shelf, `"any"` for all  

**Example:**
```
[paint_table range="vallejo-model-color" limit="-1" orderby="meta_number" shelf="any"]
```

### Paint Table Display Modes
The table can display colours in two styles:

- **Dot mode** (default): A small circular swatch column  
- **Row mode**: Entire row is coloured using the paint’s hex value, with automatic text-contrast adjustment  

---

### `[mixing-helper]`
Displays the **two-paint mixer**:

- Choose two paints  
- Choose their ranges  
- Set the number of parts for each  
- See the mixed colour and the resulting hex  

Useful for planning blends, transitions, highlights, and shadows.

---

### `[shade-helper]`
Displays the **shade helper tool**:

- Choose a base paint  
- Plugin automatically finds the **darkest** and **lightest** paints in the same range  
- Generates a ladder of darker and lighter mixes (3 steps each)  
- Shows hex values and mix ratios for every step  

#### Shade Helper Page URL  
To enable clickable colours/rows in your paint table:  
Provide the URL of the page where `[shade-helper]` is used.

When set:
- Clicking a swatch or row in `[paint_table]` sends the chosen paint directly into the shade helper.
- The shade helper automatically opens with the correct colour selected.

If empty:
- Swatches/rows remain non-clickable.

---

## 📥 Importing Paints from CSV

Go to **Paint Colours → Import from CSV**.

Options:
- Choose the paint range to assign new paints to  
- Upload a CSV (one paint per row)

**Supported columns:**
- `title` – paint name  
- `number` – paint number (optional)  
- `hex` – hex colour (e.g., `#2f353a` or `2f353a`)  
- `on_shelf` – `0` or `1`  

A header row (with these column names) is supported and automatically detected.

---

## 📤 Exporting Paints to CSV

Go to **Paint Colours → Export to CSV**.

Options:
- Filter by range  
- Limit export to only paints “on the shelf” (optional)

**Exported columns:**
- `title`  
- `number`  
- `hex`  
- `on_shelf` (`0` or `1`)  
- `ranges` – pipe-separated list of assigned ranges  

---

## 🧩 Additional Features

- Dropdowns respect custom taxonomy order (compatible with “Taxonomy Terms Order” plugin)  
- Paint table sorting automatically uses paint numbers when available  
- Shade helper now supports **unique paint selection by ID**, preventing conflicts where multiple paints share the same hex code  
- Clean, responsive frontend styling  
- JavaScript-driven custom dropdown components

---

## 📝 License

This project is distributed under the GNU General Public License version 2.

---

## 🤝 Contributing

Feature suggestions are welcome!

---

## ⚠️ Disclaimer

**I am not a developer.** ChatGPT has done all of the heavy lifting here and whilst I more or less understand what it is doing, I would not have managed this by myself.

---


