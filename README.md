# WordPress Collapsible Table of Contents

**Collapsible table of contents** for WordPress posts. Supports `<h2>` and `<h3>` headings, nested lists, and easy customization of colors, font sizes, and spacing.

---

## Features

* Clickable links scroll to headings in the post.
* Collapsible `<details>/<summary>` style TOC.
* Supports nested `<h3>` under `<h2>` automatically.
* Easy to customize visual styles via PHP variables.

---

## Installation

1. Copy the PHP snippet to your theme’s `functions.php` file or create a small plugin file like `toc-collapsible.php` in `wp-content/plugins/`.
2. Activate the plugin if using a standalone file, or reload the theme if adding to `functions.php`.
3. Customize TOC appearance by editing the variables at the top of the snippet:

```php
// Example variables you can change:
$toc_font_size      = '0.95rem';
$toc_margin_bottom  = '2rem';
$toc_padding_left   = '1.25rem';
$toc_h3_padding     = '1.5rem';
$toc_text_color     = '#1e293b';
$toc_link_hover     = 'underline';
$toc_summary_font_weight = '600';
$toc_summary_cursor = 'pointer';
$toc_summary_margin_bottom = '0.5rem';
$toc_summary_text_color = '#4f46e5';
```

4. Save changes and view a single post — the Table of Contents should appear at the top automatically.
