<?php
/**
* Plugin Name:  Smart Table of Contents
* Plugin URI:   https://github.com/cfunkz/WordPress-Table-Of-Contents
* Description:  Fully customisable, collapsible Table of Contents. Auto-injects into posts, supports [smart_toc] shortcode on any page.
* Version:      1.0.0
* Author:       cfunkz
* License:      GPL-2.0-or-later
* Text Domain:  smart-toc
* Requires at least: 5.0
* Tested up to:      6.7
* Requires PHP:      7.0
*/

defined( 'ABSPATH' ) || exit;

define( 'STOC_OPT',  'smart_toc_settings' );
define( 'STOC_META', '_smart_toc_disable' );

// =============================================================================
// DEFAULTS
//
// All colour defaults are empty strings.
// Empty string → the CSS property is omitted entirely from the output,
// so the element inherits whatever the theme sets — which is exactly what
// makes dark/light mode work automatically with any theme implementation.
//
// Users only fill these in when they want the TOC to look DIFFERENT
// from the surrounding text.
// =============================================================================

function stoc_defaults(): array {
    return [
        // Behaviour
        'auto_posts'        => 1,
        'auto_pages'        => 0,
        'min_headings'      => 3,
        'heading_levels'    => [ 'h2', 'h3' ],
        'open_by_default'   => 1,
        'smooth_scroll'     => 1,
        'position'          => 'before',

        // Labels
        'title'             => 'Table of Contents',
        'toggle_open'       => '▲',
        'toggle_closed'     => '▼',

        // Typography
        'font_size'         => '0.95rem',
        'font_family'       => '',          // empty = inherit

        // Title style
        'title_font_size'   => '1rem',
        'title_font_weight' => '600',
        'title_color'       => '',          // empty = inherit

        // Link colours — empty = inherit from theme (dark/light works automatically)
        'link_color'        => '',          // empty = inherit
        'link_hover_color'  => '',          // empty = inherit

        // Box
        'no_box'            => 0,
        'bg_color'          => '',          // empty = transparent
        'border'            => '',          // empty = none  e.g. "1px solid rgba(128,128,128,0.25)"
        'border_radius'     => '6px',
        'padding'           => '0.85rem 1.1rem',
        'margin_bottom'     => '1.25rem',
        'max_width'         => '100%',

        // List
        'list_style'        => 'disc',
        'indent_sub'        => '1.25rem',   // indent for h3/h4/h5/h6

        // Custom CSS
        'custom_css'        => '',
    ];
}

function stoc_get(): array {
    static $c = null;
    if ( $c === null ) {
        $c = wp_parse_args( (array) get_option( STOC_OPT, [] ), stoc_defaults() );
    }
    return $c;
}

// =============================================================================
// SANITIZE
// =============================================================================

function stoc_sanitize( array $in ): array {
    $d = stoc_defaults();

    foreach ( [ 'auto_posts', 'auto_pages', 'open_by_default', 'smooth_scroll', 'no_box' ] as $k ) {
        $d[$k] = empty( $in[$k] ) ? 0 : 1;
    }

    $d['min_headings'] = max( 1, (int)( $in['min_headings'] ?? 3 ) );

    $valid_levels        = [ 'h2','h3','h4','h5','h6' ];
    $d['heading_levels'] = array_values( array_intersect( (array)( $in['heading_levels'] ?? [] ), $valid_levels ) );
    if ( empty( $d['heading_levels'] ) ) $d['heading_levels'] = [ 'h2','h3' ];

    $d['position']   = in_array( $in['position']   ?? '', [ 'before','after' ], true )                              ? $in['position']   : 'before';
    $d['list_style'] = in_array( $in['list_style'] ?? '', [ 'disc','decimal','circle','square','none' ], true ) ? $in['list_style'] : 'disc';

    // Plain text / CSS value fields — stored as-is (empty string is valid = use default/inherit)
    $text_fields = [
        'title', 'toggle_open', 'toggle_closed',
        'font_size', 'font_family',
        'title_font_size', 'title_font_weight', 'title_color',
        'link_color', 'link_hover_color',
        'bg_color', 'border', 'border_radius',
        'padding', 'margin_bottom', 'max_width', 'indent_sub',
    ];
    foreach ( $text_fields as $k ) {
        $d[$k] = sanitize_text_field( $in[$k] ?? $d[$k] );
    }

    $d['custom_css'] = wp_strip_all_tags( $in['custom_css'] ?? '' );

    return $d;
}

// =============================================================================
// ADMIN MENU
// =============================================================================

add_action( 'admin_menu', function () {
    add_options_page( 'Smart TOC', 'Smart TOC', 'manage_options', 'smart-toc', 'stoc_page' );
} );

add_action( 'admin_init', function () {
    register_setting( 'stoc_group', STOC_OPT, [
        'sanitize_callback' => 'stoc_sanitize',
        'type'              => 'array',
    ] );
} );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( array $links ): array {
    array_unshift( $links, '<a href="' . admin_url( 'options-general.php?page=smart-toc' ) . '">Settings</a>' );
    return $links;
} );

// =============================================================================
// ADMIN PAGE
// =============================================================================

function stoc_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $s = stoc_get();
    ?>
    <div class="wrap" style="max-width:740px">
        <h1>Smart Table of Contents</h1>
        <p>
            Shortcode: <code>[smart_toc]</code> &mdash;
            attrs: <code>title=""</code> <code>open="yes|no"</code>
            <code>heading_levels="h2,h3"</code> <code>min_headings="2"</code>
        </p>

        <form method="post" action="options.php">
            <?php settings_fields( 'stoc_group' ); ?>

            <?php stoc_h( 'Behaviour' ); ?>
            <table class="form-table">
                <?php stoc_cb( 'auto_posts',      'Auto-inject on Posts',      $s['auto_posts'],      'Can be disabled per-post from the post editor.' ); ?>
                <?php stoc_cb( 'auto_pages',      'Auto-inject on Pages',      $s['auto_pages'] ); ?>
                <?php stoc_cb( 'open_by_default', 'Start expanded',            $s['open_by_default'] ); ?>
                <?php stoc_cb( 'smooth_scroll',   'Smooth scroll',             $s['smooth_scroll'] ); ?>
                <tr>
                    <th>Minimum headings</th>
                    <td>
                        <input type="number" min="1" max="20" class="small-text"
                               name="<?php echo STOC_OPT; ?>[min_headings]"
                               value="<?php echo (int) $s['min_headings']; ?>">
                        <p class="description">TOC only appears when at least this many headings are found.</p>
                    </td>
                </tr>
                <tr>
                    <th>Heading levels</th>
                    <td>
                        <?php foreach ( [ 'h2','h3','h4','h5','h6' ] as $lv ) : ?>
                            <label style="margin-right:.8rem">
                                <input type="checkbox"
                                       name="<?php echo STOC_OPT; ?>[heading_levels][]"
                                       value="<?php echo $lv; ?>"
                                    <?php checked( in_array( $lv, (array)$s['heading_levels'], true ) ); ?>>
                                <?php echo strtoupper( $lv ); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th>Position</th>
                    <td>
                        <?php stoc_sel( 'position', [ 'before' => 'Before content', 'after' => 'After content' ], $s['position'] ); ?>
                    </td>
                </tr>
            </table>

            <?php stoc_h( 'Labels' ); ?>
            <table class="form-table">
                <?php stoc_tx( 'title',         'TOC title',     $s['title'] ); ?>
                <?php stoc_tx( 'toggle_open',   'Collapse icon', $s['toggle_open'] ); ?>
                <?php stoc_tx( 'toggle_closed', 'Expand icon',   $s['toggle_closed'] ); ?>
            </table>

            <?php stoc_h( 'Typography' ); ?>
            <table class="form-table">
                <?php stoc_tx( 'font_size',   'Font size',   $s['font_size'],   '0.95rem' ); ?>
                <?php stoc_tx( 'font_family', 'Font family', $s['font_family'], 'leave blank to inherit from theme' ); ?>
            </table>

            <?php stoc_h( 'Title' ); ?>
            <table class="form-table">
                <?php stoc_tx( 'title_font_size',   'Font size',   $s['title_font_size'],   '1rem' ); ?>
                <?php stoc_tx( 'title_font_weight', 'Font weight', $s['title_font_weight'], '600' ); ?>
                <?php stoc_tx( 'title_color', 'Colour', $s['title_color'], 'leave blank to inherit · or any CSS colour' ); ?>
            </table>

            <?php stoc_h( 'Link colours' ); ?>
            <p class="description" style="margin:.3rem 0 .8rem">
                <strong>Leave blank</strong> and links inherit the theme's text colour — dark mode, light mode,
                any theme class toggle, all work automatically with zero extra code.
                Only fill these in if you want links to be a specific colour regardless of the theme.
            </p>
            <table class="form-table">
                <?php stoc_tx( 'link_color',       'Link colour',       $s['link_color'],       'leave blank to inherit from theme' ); ?>
                <?php stoc_tx( 'link_hover_color', 'Link hover colour', $s['link_hover_color'], 'leave blank to inherit from theme' ); ?>
            </table>

            <?php stoc_h( 'Box' ); ?>
            <p class="description" style="margin:.3rem 0 .8rem">
                Leave <strong>Background</strong> and <strong>Border</strong> blank for a fully transparent,
                borderless TOC. Use <code>rgba()</code> values for tints that work in both light and dark mode,
                e.g. <code>rgba(128,128,128,0.08)</code>.
            </p>
            <table class="form-table">
                <?php stoc_cb( 'no_box', 'No box (force transparent, no border, no padding)', $s['no_box'] ); ?>
                <?php stoc_tx( 'bg_color',      'Background',    $s['bg_color'],      'blank = transparent · rgba(128,128,128,0.08)' ); ?>
                <?php stoc_tx( 'border',        'Border',        $s['border'],        'blank = none · e.g. 1px solid rgba(128,128,128,0.25)' ); ?>
                <?php stoc_tx( 'border_radius', 'Border radius', $s['border_radius'], '6px' ); ?>
                <?php stoc_tx( 'padding',       'Padding',       $s['padding'],       '0.85rem 1.1rem' ); ?>
                <?php stoc_tx( 'margin_bottom', 'Margin bottom', $s['margin_bottom'], '1.25rem' ); ?>
                <?php stoc_tx( 'max_width',     'Max width',     $s['max_width'],     '100%' ); ?>
            </table>

            <?php stoc_h( 'List' ); ?>
            <table class="form-table">
                <tr>
                    <th>List style</th>
                    <td>
                        <?php stoc_sel( 'list_style', array_combine(
                            [ 'disc','decimal','circle','square','none' ],
                            [ 'Disc','Decimal','Circle','Square','None' ]
                        ), $s['list_style'] ); ?>
                    </td>
                </tr>
                <?php stoc_tx( 'indent_sub', 'Sub-heading indent (H3+)', $s['indent_sub'], '1.25rem' ); ?>
            </table>

            <?php stoc_h( 'Custom CSS' ); ?>
            <p class="description" style="margin:.3rem 0 .8rem">
                Selectors: <code>.smart-toc-wrap</code> &nbsp;
                <code>.smart-toc-title</code> &nbsp;
                <code>.smart-toc-list</code> &nbsp;
                <code>.smart-toc-item</code> &nbsp;
                <code>.smart-toc-item a</code> &nbsp;
                <code>.smart-toc-h2</code> &nbsp;
                <code>.smart-toc-h3</code> etc.
            </p>
            <table class="form-table">
                <tr><td style="padding-left:0">
                    <textarea name="<?php echo STOC_OPT; ?>[custom_css]"
                              rows="7" class="large-text code"
                              placeholder="/* your overrides here */"
                    ><?php echo esc_textarea( $s['custom_css'] ); ?></textarea>
                </td></tr>
            </table>

            <?php submit_button( 'Save Settings' ); ?>
        </form>
    </div>
    <?php
}

// ── Admin field helpers — kept tiny ──────────────────────────────────────────

function stoc_h( string $t ): void {
    echo '<h2 style="border-bottom:1px solid #ddd;padding-bottom:.2rem;margin-top:1.5rem">' . esc_html( $t ) . '</h2>';
}
function stoc_cb( string $k, string $label, $v, string $desc = '' ): void {
    echo '<tr><th>' . esc_html( $label ) . '</th><td><label>'
       . '<input type="checkbox" name="' . STOC_OPT . '[' . $k . ']" value="1"' . checked( 1, (int)$v, false ) . '>'
       . ' Enable</label>';
    if ( $desc ) echo '<p class="description">' . esc_html( $desc ) . '</p>';
    echo '</td></tr>';
}
function stoc_tx( string $k, string $label, $v, string $ph = '' ): void {
    echo '<tr><th>' . esc_html( $label ) . '</th><td>'
       . '<input type="text" class="regular-text" name="' . STOC_OPT . '[' . $k . ']"'
       . ' value="' . esc_attr( $v ) . '" placeholder="' . esc_attr( $ph ) . '">'
       . '</td></tr>';
}
function stoc_sel( string $k, array $opts, string $cur ): void {
    echo '<select name="' . STOC_OPT . '[' . $k . ']">';
    foreach ( $opts as $v => $l ) {
        echo '<option value="' . esc_attr( $v ) . '"' . selected( $cur, $v, false ) . '>' . esc_html( $l ) . '</option>';
    }
    echo '</select>';
}

// =============================================================================
// PER-POST META BOX
// =============================================================================

add_action( 'add_meta_boxes', function () {
    add_meta_box( 'smart_toc', 'Smart TOC', 'stoc_meta_box', [ 'post','page' ], 'side' );
} );

function stoc_meta_box( WP_Post $post ): void {
    wp_nonce_field( 'stoc_meta_save', '_stoc_n' );
    $off = get_post_meta( $post->ID, STOC_META, true ) === '1';
    echo '<label><input type="checkbox" name="stoc_off" value="1"' . checked( $off, true, false ) . '>'
       . ' Disable TOC on this post/page</label>'
       . '<p class="description" style="margin-top:5px">Or use <code>[smart_toc]</code> to place it manually.</p>';
}

add_action( 'save_post', function ( int $id ): void {
    if (
        ! isset( $_POST['_stoc_n'] )
        || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_stoc_n'] ) ), 'stoc_meta_save' )
        || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        || ! current_user_can( 'edit_post', $id )
    ) return;
    empty( $_POST['stoc_off'] )
        ? delete_post_meta( $id, STOC_META )
        : update_post_meta( $id, STOC_META, '1' );
} );

// =============================================================================
// CSS OUTPUT
//
// The rule: if a setting is an empty string, do NOT emit that CSS property.
// The browser then inherits from the parent — which is the theme element
// wrapping the post content. That element already has the correct colour
// for the current mode (.dark class / media query / whatever the theme uses).
//
// This is why the original snippet worked: `color: inherit` on <a> tags.
// We generalise that principle to every colour setting.
// =============================================================================

function stoc_css(): string {
    $s = stoc_get();

    // Helper: emit a CSS declaration only when value is non-empty
    $prop = static function ( string $property, string $value ): string {
        $v = trim( $value );
        return $v !== '' ? $property . ':' . $v . ';' : '';
    };

    // Box rule
    if ( $s['no_box'] ) {
        $box = 'background:transparent;border:none;padding:0;';
    } else {
        $box = $prop( 'background', $s['bg_color'] )
             . $prop( 'border', $s['border'] )
             . $prop( 'border-radius', $s['border_radius'] )
             . $prop( 'padding', $s['padding'] );
    }

    $css = ( $s['smooth_scroll'] ? 'html{scroll-behavior:smooth}' : '' )

         // Wrap
         . '.smart-toc-wrap{'
         .   $box
         .   $prop( 'margin-bottom', $s['margin_bottom'] )
         .   $prop( 'max-width',     $s['max_width'] )
         .   $prop( 'font-size',     $s['font_size'] )
         .   $prop( 'font-family',   $s['font_family'] )
         .   'box-sizing:border-box}'

         // Summary row
         . '.smart-toc-summary{'
         .   'display:flex;align-items:center;gap:.4rem;'
         .   'cursor:pointer;list-style:none;outline:none;user-select:none}'
         . '.smart-toc-summary::-webkit-details-marker{display:none}'

         // Title
         . '.smart-toc-title{'
         .   'flex:1;'
         .   $prop( 'font-size',   $s['title_font_size'] )
         .   $prop( 'font-weight', $s['title_font_weight'] )
         .   $prop( 'color',       $s['title_color'] )   // inherits if blank
         .   'line-height:1.4}'

         // Toggle icons — inherit colour from title
         . '.smart-toc-toggle{font-size:.72em;opacity:.75}'
         . '.smart-toc-toggle-closed{display:none}'
         . '.smart-toc-wrap:not([open]) .smart-toc-toggle-open{display:none}'
         . '.smart-toc-wrap:not([open]) .smart-toc-toggle-closed{display:inline}'

         // List
         . '.smart-toc-list{'
         .   $prop( 'list-style', $s['list_style'] )
         .   'margin:.5rem 0 0;padding-left:1rem}'
         . '.smart-toc-item{margin-bottom:.2rem}'

         // Links — no color property emitted when blank → browser inherits theme colour
         . '.smart-toc-item a{'
         .   'text-decoration:none;'
         .   $prop( 'color', $s['link_color'] )
         .   '}'
         . '.smart-toc-item a:hover{'
         .   'text-decoration:underline;'
         .   $prop( 'color', $s['link_hover_color'] )
         .   '}'

         . ( $s['custom_css'] ?: '' );

    return '<style id="smart-toc-css">' . $css . '</style>';
}

// =============================================================================
// TOC HTML BUILDER
// Returns [ toc_html, updated_content ] or [ '', original_content ]
// =============================================================================

function stoc_build( string $content, array $override = [] ): array {
    if ( ! class_exists( 'DOMDocument' ) ) return [ '', $content ];

    $s      = wp_parse_args( $override, stoc_get() );
    $levels = array_values( array_filter( (array) $s['heading_levels'] ) );
    if ( empty( $levels ) ) return [ '', $content ];

    libxml_use_internal_errors( true );
    $dom = new DOMDocument();
    $dom->loadHTML(
        '<html><head><meta charset="utf-8"></head><body>'
        . mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' )
        . '</body></html>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();

    $headings = ( new DOMXPath( $dom ) )->query( '//' . implode( ' | //', $levels ) );
    if ( ! $headings || $headings->length < (int) $s['min_headings'] ) return [ '', $content ];

    $h2_levels = [ 'h2' ];
    $indent    = trim( $s['indent_sub'] );

    $items = '';
    foreach ( $headings as $h ) {
        $id = $h->getAttribute( 'id' );
        if ( ! $id ) {
            $id = sanitize_title( $h->nodeValue );
            $h->setAttribute( 'id', $id );
        }
        $tag    = strtolower( $h->tagName );
        $is_sub = ! in_array( $tag, $h2_levels, true );
        $style  = ( $is_sub && $indent !== '' ) ? ' style="padding-left:' . esc_attr( $indent ) . '"' : '';

        $items .= '<li class="smart-toc-item smart-toc-' . esc_attr( $tag ) . '"' . $style . '>'
                . '<a href="#' . esc_attr( $id ) . '">' . esc_html( $h->nodeValue ) . '</a>'
                . '</li>';
    }

    $toc = '<details class="smart-toc-wrap"' . ( $s['open_by_default'] ? ' open' : '' ) . '>'
         . '<summary class="smart-toc-summary">'
         . '<span class="smart-toc-title">'                          . esc_html( $s['title'] )         . '</span>'
         . '<span class="smart-toc-toggle smart-toc-toggle-open">'   . esc_html( $s['toggle_open'] )   . '</span>'
         . '<span class="smart-toc-toggle smart-toc-toggle-closed">' . esc_html( $s['toggle_closed'] ) . '</span>'
         . '</summary>'
         . '<ul class="smart-toc-list">' . $items . '</ul>'
         . '</details>';

    // Rebuild content with any newly-stamped IDs
    $body = $dom->getElementsByTagName( 'body' )->item( 0 );
    $new  = '';
    if ( $body ) foreach ( $body->childNodes as $node ) $new .= $dom->saveHTML( $node );

    return [ $toc, $new ?: $content ];
}

// =============================================================================
// AUTO-INJECT
// =============================================================================

add_filter( 'the_content', function ( string $content ): string {
    if ( ! in_the_loop() || ! is_main_query() ) return $content;

    $s = stoc_get();
    if ( ! ( ( is_single() && $s['auto_posts'] ) || ( is_page() && $s['auto_pages'] ) ) ) return $content;
    if ( get_post_meta( get_the_ID(), STOC_META, true ) === '1' ) return $content;
    if ( has_shortcode( $content, 'smart_toc' ) ) return $content;

    [ $toc, $content ] = stoc_build( $content );
    if ( ! $toc ) return $content;

    $block = stoc_css() . $toc;
    return $s['position'] === 'after' ? $content . $block : $block . $content;
}, 10 );

// =============================================================================
// SHORTCODE [smart_toc]
// =============================================================================

add_shortcode( 'smart_toc', function ( $atts ): string {
    global $post;
    if ( ! $post instanceof WP_Post ) return '';

    $atts = shortcode_atts(
        [ 'title' => '', 'open' => '', 'min_headings' => '', 'heading_levels' => '' ],
        $atts, 'smart_toc'
    );

    $ov = [];
    if ( $atts['title'] )          $ov['title']           = sanitize_text_field( $atts['title'] );
    if ( $atts['open'] !== '' )    $ov['open_by_default'] = strtolower( $atts['open'] ) !== 'no' ? 1 : 0;
    if ( $atts['min_headings'] )   $ov['min_headings']    = max( 1, (int) $atts['min_headings'] );
    if ( $atts['heading_levels'] ) $ov['heading_levels']  = array_intersect(
        array_map( 'trim', explode( ',', $atts['heading_levels'] ) ),
        [ 'h2','h3','h4','h5','h6' ]
    );

    // Process content without triggering the_content (prevents infinite loops)
    $content = wpautop( do_shortcode( $post->post_content ) );
    [ $toc ]  = stoc_build( $content, $ov );
    if ( ! $toc ) return '';

    static $printed = false;
    $css     = $printed ? '' : stoc_css();
    $printed = true;

    return $css . $toc;
} );

// =============================================================================
// UNINSTALL
// =============================================================================

register_uninstall_hook( __FILE__, 'stoc_uninstall' );
function stoc_uninstall(): void {
    delete_option( STOC_OPT );
    delete_post_meta_by_key( STOC_META );
}
