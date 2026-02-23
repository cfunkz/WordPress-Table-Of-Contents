# Smart Table of Contents

**The last TOC plugin you'll ever install.**

No JavaScript, surprisingly zero dependencies. One file plugin. Install it & never think about "Table Of Content" issues ever again.

## Why another TOC plugin?

Because some are getting out of context, some that load three scripts per page, or a barebones snippet that breaks the moment you look at it wrong.

Smart TOC is the middleground: a single PHP file with a full admin panel integration, shortcode support, per-post overrides, and deep design customisation — built to blend into *any* site without fighting your theme.

## Features

### It just works
- **Auto-injects** into posts and/or pages — configure once, forget about it
- **Detects headings automatically** — H2 through H6, your choice
- **Skips posts with too few headings** — set the minimum threshold yourself
- **IDs are injected cleanly** on headings that don't already have one
- **Plays nice with Gutenberg, Classic Editor, and page builders**

### Zero JavaScript
The collapsible open/close behaviour is pure native HTML (`<details>` / `<summary>`). No jQuery. No script tags. No render-blocking. Works even with JavaScript disabled.

### Shortcode anywhere
Drop a TOC on any post, page, or widget area with a single shortcode:

```
[smart_toc]
```

Override any setting inline without touching the admin panel:

```
[smart_toc title="In This Article" open="no"]
[smart_toc heading_levels="h2,h3,h4" min_headings="2"]
```

### Full design control — no CSS required
Every visual property is editable from **Settings → Smart TOC**:

| Category | What you can control |
|---|---|
| Typography | Font size, family, all colours, title weight & size |
| Box | Background, border width/colour/radius, padding, margin, max-width |
| List | Style (disc, decimal, circle, square, none), per-level indentation |
| Labels | TOC title, open/close toggle icons (text, emoji, or symbols) |
| Custom CSS | Freeform CSS appended after generated styles |

### Per-post control
Every post and page gets a **Smart TOC** meta box in the editor sidebar. One checkbox to disable the TOC on that specific post — no shortcodes, no custom fields.

### Clean and safe
- All output is escaped (`esc_html`, `esc_attr`, `esc_url`)
- Settings are sanitised on save with a whitelist approach
- Nonce-verified meta box saving
- Uninstalling removes **all** options and post meta — no orphaned data left behind

## Installation

1. Download [Release](https://github.com/cfunkz/WordPress-Table-Of-Contents/releases/tag/v1.0.0)
2. Install via **Plugins → Install Plugin**
4. Configure at **Settings → Smart TOC**

That's it.

## Shortcode Reference

```
[smart_toc]
[smart_toc title="Contents"]
[smart_toc title="Jump To" open="no"]
[smart_toc heading_levels="h2,h3,h4"]
[smart_toc min_headings="2"]
```

| Attribute | Default | Description |
|---|---|---|
| `title` | *(from settings)* | Override the TOC heading text |
| `open` | *(from settings)* | `yes` or `no` — start expanded or collapsed |
| `min_headings` | *(from settings)* | Minimum headings before TOC renders |
| `heading_levels` | *(from settings)* | Comma-separated list: `h2,h3,h4` |

## CSS Customisation

Every element has a predictable class name. Target them in the **Custom CSS** tab or your theme's stylesheet:

```css
.smart-toc-wrap        { /* outer box */ }
.smart-toc-summary     { /* the clickable title row */ }
.smart-toc-title       { /* "Table of Contents" text */ }
.smart-toc-toggle      { /* ▲ / ▼ icons */ }
.smart-toc-list        { /* the <ul> */ }
.smart-toc-item        { /* each <li> */ }
.smart-toc-item a      { /* links */ }
.smart-toc-h2          { /* H2-level items only */ }
.smart-toc-h3          { /* H3-level items only */ }
.smart-toc-h4          { /* H4-level items only */ }
```

## Requirements

| | Minimum | Recommended |
|---|---|---|
| PHP | 7.0 | 8.0+ |
| WordPress | 5.0 | 6.4+ |

## FAQ

<details>
<summary><strong>Does it slow down my site?</strong></summary>

No. There's no JavaScript, no external requests, and no database queries beyond reading a single option. The CSS is inlined only on pages that actually have a TOC.

</details>

<details>
<summary><strong>Will it conflict with my SEO plugin?</strong></summary>

No. IDs are only added to headings that don't already have one, so Yoast, RankMath, and others are unaffected.

</details>

<details>
<summary><strong>Can I use the shortcode multiple times on one page?</strong></summary>

Yes. The CSS is printed once no matter how many shortcodes are on the page.

</details>

<details>
<summary><strong>What if I uninstall it?</strong></summary>

Everything is cleaned up — options, post meta, all of it. WordPress is left exactly as it was.

</details>

<details>
<summary><strong>Does it work with custom post types?</strong></summary>

The auto-inject targets posts and pages. For custom post types, use the `[smart_toc]` shortcode.

</details>
