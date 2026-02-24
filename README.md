# Smart TOC

Table of Contents plugin for WordPress. Zero JavaScript on the front end — just a native `<details>`/`<summary>` element styled with generated CSS. Collapses and expands without a single line of JS.

## What it does

Scans your post content for headings, builds an anchor-linked list, and drops it before (or after) the content. That's it, no scripts.

Fonts, colours, borders, transparency, alignment, bullet style, indentation — and a custom CSS field with a selector reference for anything beyond the UI.

## Installation

1. Download `toc.php`
2. Upload it to `/wp-content/plugins/smart-toc/`
3. Activate from **Plugins → Installed Plugins**
4. Configure at **Settings → Smart TOC**

## Usage

**Auto-inject** — turn on *Posts* and/or *Pages* in the Behaviour tab and the TOC appears automatically wherever there are enough headings.

**Shortcode** — paste `[smart_toc]` anywhere in your content to place it manually. Supports inline overrides:

```
[smart_toc title="Contents" open="no" align="center" title_align="center" heading_levels="h2,h3" min_headings="2"]
```

**Disable per post** — a meta box on the edit screen lets you skip the TOC on any individual post or page.

---

## Shortcode attributes

| Attribute | Values | Default |
|---|---|---|
| `title` | any text | *Table of Contents* |
| `open` | `yes` / `no` | *yes* |
| `align` | `left` / `center` / `right` | *left* |
| `title_align` | `left` / `center` / `right` | *left* |
| `link_align` | `left` / `center` / `right` | *left* |
| `heading_levels` | `h2,h3,h4` … | *h2,h3* |
| `min_headings` | number | *3* |

---

## Custom CSS selectors

```css
details.smart-toc-wrap        /* outer box */
.smart-toc-summary            /* clickable title row */
.smart-toc-title              /* title text */
.smart-toc-toggle-open        /* icon when expanded */
.smart-toc-toggle-closed      /* icon when collapsed */
ul.smart-toc-list             /* the list */
li.smart-toc-item             /* every item */
li.smart-toc-item a           /* links */
li.smart-toc-h2 / h3 / h4…   /* items by heading level */
details.smart-toc-wrap[open]  /* state when expanded */
```

Example — float it to the right like a Wikipedia box:

```css
details.smart-toc-wrap {
  float: right;
  max-width: 280px;
  margin: 0 0 1rem 1.5rem;
  box-shadow: 0 2px 12px rgba(0,0,0,.08);
}
```

---

## Requirements

- WordPress 5.0+
- PHP 7.4+
- The `DOMDocument` PHP extension (standard in almost every host)

## License

GPL-2.0-or-later
