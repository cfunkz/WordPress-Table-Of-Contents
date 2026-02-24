# Smart TOC

> Zero-JavaScript Table of Contents for WordPress — pure `<details>`/`<summary>` + CSS.

<img width="944" height="820" alt="image" src="https://github.com/user-attachments/assets/c18da9ff-0469-4b40-9f3f-29da88a768f5" />
<img width="1259" height="750" alt="image" src="https://github.com/user-attachments/assets/8db6c191-cea4-4c9e-b880-3d292227e251" />
<img width="1228" height="834" alt="image" src="https://github.com/user-attachments/assets/6ac5048c-c345-4ac4-a533-08244db5a988" />
<img width="1261" height="730" alt="image" src="https://github.com/user-attachments/assets/2a07fcb0-4c95-4ccd-b0a8-a0a70889bfb3" />
<img width="934" height="811" alt="image" src="https://github.com/user-attachments/assets/b22c1606-8389-44cf-9f14-dbd3e72d7cd0" />
<img width="1239" height="819" alt="image" src="https://github.com/user-attachments/assets/fd17b18f-075c-471b-8f41-4e43945e23a9" />
<img width="930" height="845" alt="image" src="https://github.com/user-attachments/assets/b47fb887-5e2e-4f4d-ac59-3508a53bcd48" />

Scans your post headings, builds an anchor-linked list, and drops it into your content. No scripts loaded, no render blocking, no jQuery. Collapses and expands using the browser's native `<details>` element — it just works everywhere.

## Features

- **Zero front-end JavaScript** — expand/collapse is native HTML
- **Full visual control** — fonts, colours, transparency, borders, radius, alignment, bullet style, indentation
- **Shortcode** with per-instance overrides
- **Auto-inject** into posts and/or pages
- **Per-post opt-out** via a meta box on the edit screen
- **Custom CSS field** with a full selector reference built into the settings page
- Single PHP file, no dependencies

## Installation

1. Download `toc.zip`
2. Upload & Activate
3. Go to **Settings → Smart TOC** to configure

## Usage

### Auto-inject

Enable **Posts** and/or **Pages** in the Behaviour tab. The TOC appears automatically on any content that meets the minimum heading count.

### Shortcode

Place `[smart_toc]` anywhere in your content for manual positioning:

```
[smart_toc]
```

With overrides:

```
[smart_toc title="Contents" open="no" align="center" title_align="center" heading_levels="h2,h3" min_headings="2"]
```

### Disable per post

The **Smart TOC** meta box on the post edit screen lets you skip the TOC on any individual post or page without touching global settings.

## Shortcode attributes

| Attribute | Values | Default |
|:---|:---|:---|
| `title` | any text | `Table of Contents` |
| `open` | `yes` · `no` | `yes` |
| `align` | `left` · `center` · `right` | `left` |
| `title_align` | `left` · `center` · `right` | `left` |
| `link_align` | `left` · `center` · `right` | `left` |
| `heading_levels` | `h2,h3,h4` … | `h2,h3` |
| `min_headings` | any number | `3` |

## Custom CSS

The settings page includes a CSS editor and a full selector reference. Anything the UI doesn't expose can be targeted directly.

**Selectors:**

```css
details.smart-toc-wrap       /* outer box                  */
.smart-toc-summary           /* clickable title row        */
.smart-toc-title             /* title text                 */
.smart-toc-toggle-open       /* icon when expanded         */
.smart-toc-toggle-closed     /* icon when collapsed        */
ul.smart-toc-list            /* the list                   */
li.smart-toc-item            /* every list item            */
li.smart-toc-item a          /* links                      */
li.smart-toc-h2              /* H2 items (h3, h4 same)     */
details.smart-toc-wrap[open] /* state hook when open       */
```

**Example — Wikipedia-style float:**

```css
details.smart-toc-wrap {
  float: right;
  max-width: 280px;
  margin: 0 0 1.25rem 1.75rem;
  box-shadow: 0 2px 14px rgba(0, 0, 0, .08);
}
```

**Example — dim nested items:**

```css
li.smart-toc-h3,
li.smart-toc-h4 {
  opacity: .7;
}
```

## Requirements

| | |
|:---|:---|
| WordPress | 5.0 or later |
| PHP | 7.4 or later |
| `DOMDocument` | Required — enabled by default on virtually every host |

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)
