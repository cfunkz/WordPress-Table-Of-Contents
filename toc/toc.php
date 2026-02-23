<?php
/**
 * Plugin Name:  Smart Table of Contents
 * Plugin URI:   https://github.com/cfunkz/WordPress-Table-Of-Contents
 * Description:  Fully customisable, collapsible Table of Contents. Auto-injects into posts, supports shortcodes on any page, and is controlled entirely from the admin panel.
 * Version:      1.0.0
 * Author:       cFunkz
 * License:      GPL-2.0-or-later
 * Text Domain:  smart-toc
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
   CONSTANTS
   ========================================================================= */
define( 'STOC_VERSION',    '1.0.0' );
define( 'STOC_OPTION',     'smart_toc_settings' );
define( 'STOC_META_KEY',   '_smart_toc_disable' );   // per-post override

/* =========================================================================
   DEFAULT SETTINGS
   ========================================================================= */
function stoc_defaults(): array {
    return [
        // Behaviour
        'auto_posts'        => 1,           // auto-inject on posts
        'auto_pages'        => 0,           // auto-inject on pages
        'min_headings'      => 3,           // minimum headings before TOC appears
        'heading_levels'    => ['h2','h3'], // which tags to include
        'open_by_default'   => 1,           // <details> open attribute
        'smooth_scroll'     => 1,           // CSS scroll-behavior: smooth
        'position'          => 'before',    // before | after content

        // Labels
        'title'             => 'Table of Contents',
        'toggle_open'       => '▲',
        'toggle_closed'     => '▼',

        // Typography
        'font_size'         => '0.95rem',
        'font_family'       => 'inherit',
        'text_color'        => '#1e293b',
        'link_color'        => '#1e293b',
        'link_hover_color'  => '#4f46e5',
        'title_color'       => '#4f46e5',
        'title_font_weight' => '600',
        'title_font_size'   => '1rem',

        // Box
        'bg_color'          => '#f8fafc',
        'border_color'      => '#e2e8f0',
        'border_width'      => '1px',
        'border_radius'     => '6px',
        'padding'           => '1rem 1.25rem',
        'margin_bottom'     => '1.5rem',
        'max_width'         => '100%',

        // List
        'list_style'        => 'disc',      // disc | decimal | none
        'indent_h3'         => '1.25rem',
        'indent_h4'         => '2.5rem',

        // Custom CSS
        'custom_css'        => '',
    ];
}

function stoc_get( string $key = '' ) {
    static $settings = null;
    if ( $settings === null ) {
        $settings = wp_parse_args(
            (array) get_option( STOC_OPTION, [] ),
            stoc_defaults()
        );
    }
    return $key === '' ? $settings : ( $settings[ $key ] ?? null );
}

/* =========================================================================
   ADMIN MENU & SETTINGS PAGE
   ========================================================================= */
add_action( 'admin_menu', function () {
    add_options_page(
        __( 'Smart TOC Settings', 'smart-toc' ),
        __( 'Smart TOC', 'smart-toc' ),
        'manage_options',
        'smart-toc',
        'stoc_settings_page'
    );
} );

add_action( 'admin_init', function () {
    register_setting( 'smart_toc_group', STOC_OPTION, [
        'sanitize_callback' => 'stoc_sanitize_settings',
    ] );
} );

function stoc_sanitize_settings( $input ): array {
    $defaults = stoc_defaults();
    $clean    = [];

    // Booleans
    foreach ( ['auto_posts','auto_pages','open_by_default','smooth_scroll'] as $key ) {
        $clean[ $key ] = isset( $input[ $key ] ) ? 1 : 0;
    }

    // Integers
    $clean['min_headings'] = max( 1, intval( $input['min_headings'] ?? 3 ) );

    // Heading levels — whitelist
    $allowed_levels = ['h2','h3','h4','h5','h6'];
    $levels = isset( $input['heading_levels'] ) && is_array( $input['heading_levels'] )
        ? array_intersect( $input['heading_levels'], $allowed_levels )
        : ['h2','h3'];
    $clean['heading_levels'] = array_values( $levels );

    // Select fields
    $clean['position']   = in_array( $input['position'] ?? '', ['before','after'] ) ? $input['position'] : 'before';
    $clean['list_style'] = in_array( $input['list_style'] ?? '', ['disc','decimal','circle','square','none'] ) ? $input['list_style'] : 'disc';

    // Text fields (labels, sizes, colours)
    $text_fields = [
        'title','toggle_open','toggle_closed',
        'font_size','font_family','text_color','link_color','link_hover_color',
        'title_color','title_font_weight','title_font_size',
        'bg_color','border_color','border_width','border_radius',
        'padding','margin_bottom','max_width','indent_h3','indent_h4',
    ];
    foreach ( $text_fields as $key ) {
        $clean[ $key ] = sanitize_text_field( $input[ $key ] ?? $defaults[ $key ] );
    }

    // Custom CSS — strip tags but keep valid CSS
    $clean['custom_css'] = wp_strip_all_tags( $input['custom_css'] ?? '' );

    return $clean;
}

function stoc_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $s = stoc_get();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Smart Table of Contents', 'smart-toc' ); ?></h1>
        <p><?php esc_html_e( 'Use [smart_toc] shortcode on any page/post, or enable auto-injection below.', 'smart-toc' ); ?></p>
        <form method="post" action="options.php">
            <?php settings_fields( 'smart_toc_group' ); ?>

            <?php
            $tabs = [
                'behaviour'  => __( '⚙️ Behaviour', 'smart-toc' ),
                'labels'     => __( '🏷 Labels', 'smart-toc' ),
                'typography' => __( '🔤 Typography', 'smart-toc' ),
                'box'        => __( '📦 Box / Border', 'smart-toc' ),
                'list'       => __( '📋 List', 'smart-toc' ),
                'custom_css' => __( '🎨 Custom CSS', 'smart-toc' ),
            ];
            $active = sanitize_key( $_GET['tab'] ?? 'behaviour' );
            if ( ! array_key_exists( $active, $tabs ) ) $active = 'behaviour';
            ?>

            <nav class="nav-tab-wrapper" style="margin-bottom:1rem">
                <?php foreach ( $tabs as $slug => $label ) : ?>
                    <a href="?page=smart-toc&tab=<?php echo esc_attr( $slug ); ?>"
                       class="nav-tab <?php echo $active === $slug ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <table class="form-table" role="presentation">
            <?php if ( $active === 'behaviour' ) : ?>

                <?php stoc_field_checkbox( 'auto_posts', __( 'Auto-inject on Posts', 'smart-toc' ), $s['auto_posts'], __( 'Automatically add TOC to single posts (can be disabled per-post via the post edit screen).', 'smart-toc' ) ); ?>
                <?php stoc_field_checkbox( 'auto_pages', __( 'Auto-inject on Pages', 'smart-toc' ), $s['auto_pages'] ); ?>
                <?php stoc_field_checkbox( 'open_by_default', __( 'Open by Default', 'smart-toc' ), $s['open_by_default'], __( 'TOC starts expanded. Visitors can collapse it.', 'smart-toc' ) ); ?>
                <?php stoc_field_checkbox( 'smooth_scroll', __( 'Smooth Scroll', 'smart-toc' ), $s['smooth_scroll'] ); ?>

                <tr>
                    <th><?php esc_html_e( 'Minimum Headings', 'smart-toc' ); ?></th>
                    <td>
                        <input type="number" min="1" max="20" name="<?php echo esc_attr(STOC_OPTION); ?>[min_headings]" value="<?php echo esc_attr( $s['min_headings'] ); ?>" class="small-text">
                        <p class="description"><?php esc_html_e( 'Do not show TOC if fewer headings are found.', 'smart-toc' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><?php esc_html_e( 'Heading Levels', 'smart-toc' ); ?></th>
                    <td>
                        <?php foreach ( ['h2','h3','h4','h5','h6'] as $lv ) : ?>
                            <label style="margin-right:1rem">
                                <input type="checkbox" name="<?php echo esc_attr(STOC_OPTION); ?>[heading_levels][]"
                                       value="<?php echo esc_attr( $lv ); ?>"
                                    <?php checked( in_array( $lv, (array) $s['heading_levels'] ) ); ?>>
                                <?php echo esc_html( strtoupper( $lv ) ); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>

                <tr>
                    <th><?php esc_html_e( 'Position', 'smart-toc' ); ?></th>
                    <td>
                        <select name="<?php echo esc_attr(STOC_OPTION); ?>[position]">
                            <option value="before" <?php selected( $s['position'], 'before' ); ?>><?php esc_html_e( 'Before content', 'smart-toc' ); ?></option>
                            <option value="after"  <?php selected( $s['position'], 'after' ); ?>><?php esc_html_e( 'After content', 'smart-toc' ); ?></option>
                        </select>
                    </td>
                </tr>

            <?php elseif ( $active === 'labels' ) : ?>

                <?php stoc_field_text( 'title', __( 'TOC Title', 'smart-toc' ), $s['title'] ); ?>
                <?php stoc_field_text( 'toggle_open', __( 'Toggle Icon (open)', 'smart-toc' ), $s['toggle_open'] ); ?>
                <?php stoc_field_text( 'toggle_closed', __( 'Toggle Icon (closed)', 'smart-toc' ), $s['toggle_closed'] ); ?>

            <?php elseif ( $active === 'typography' ) : ?>

                <?php stoc_field_text( 'font_size', __( 'Font Size', 'smart-toc' ), $s['font_size'], '0.95rem' ); ?>
                <?php stoc_field_text( 'font_family', __( 'Font Family', 'smart-toc' ), $s['font_family'], 'inherit' ); ?>
                <?php stoc_field_color( 'text_color', __( 'Text Color', 'smart-toc' ), $s['text_color'] ); ?>
                <?php stoc_field_color( 'link_color', __( 'Link Color', 'smart-toc' ), $s['link_color'] ); ?>
                <?php stoc_field_color( 'link_hover_color', __( 'Link Hover Color', 'smart-toc' ), $s['link_hover_color'] ); ?>
                <?php stoc_field_color( 'title_color', __( 'Title Color', 'smart-toc' ), $s['title_color'] ); ?>
                <?php stoc_field_text( 'title_font_weight', __( 'Title Font Weight', 'smart-toc' ), $s['title_font_weight'], '600' ); ?>
                <?php stoc_field_text( 'title_font_size', __( 'Title Font Size', 'smart-toc' ), $s['title_font_size'], '1rem' ); ?>

            <?php elseif ( $active === 'box' ) : ?>

                <?php stoc_field_color( 'bg_color', __( 'Background Color', 'smart-toc' ), $s['bg_color'] ); ?>
                <?php stoc_field_color( 'border_color', __( 'Border Color', 'smart-toc' ), $s['border_color'] ); ?>
                <?php stoc_field_text( 'border_width', __( 'Border Width', 'smart-toc' ), $s['border_width'], '1px' ); ?>
                <?php stoc_field_text( 'border_radius', __( 'Border Radius', 'smart-toc' ), $s['border_radius'], '6px' ); ?>
                <?php stoc_field_text( 'padding', __( 'Padding', 'smart-toc' ), $s['padding'], '1rem 1.25rem' ); ?>
                <?php stoc_field_text( 'margin_bottom', __( 'Margin Bottom', 'smart-toc' ), $s['margin_bottom'], '1.5rem' ); ?>
                <?php stoc_field_text( 'max_width', __( 'Max Width', 'smart-toc' ), $s['max_width'], '100%' ); ?>

            <?php elseif ( $active === 'list' ) : ?>

                <tr>
                    <th><?php esc_html_e( 'List Style', 'smart-toc' ); ?></th>
                    <td>
                        <select name="<?php echo esc_attr(STOC_OPTION); ?>[list_style]">
                            <?php foreach ( ['disc','decimal','circle','square','none'] as $ls ) : ?>
                                <option value="<?php echo esc_attr($ls); ?>" <?php selected( $s['list_style'], $ls ); ?>>
                                    <?php echo esc_html( ucfirst($ls) ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php stoc_field_text( 'indent_h3', __( 'H3 Indent', 'smart-toc' ), $s['indent_h3'], '1.25rem' ); ?>
                <?php stoc_field_text( 'indent_h4', __( 'H4+ Indent', 'smart-toc' ), $s['indent_h4'], '2.5rem' ); ?>

            <?php elseif ( $active === 'custom_css' ) : ?>

                <tr>
                    <th><?php esc_html_e( 'Custom CSS', 'smart-toc' ); ?></th>
                    <td>
                        <textarea name="<?php echo esc_attr(STOC_OPTION); ?>[custom_css]"
                                  rows="12" cols="60" class="large-text code"
                                  placeholder="/* target .smart-toc-wrap */"
                        ><?php echo esc_textarea( $s['custom_css'] ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Extra CSS appended after the generated styles. Target .smart-toc-wrap and its children.', 'smart-toc' ); ?></p>
                    </td>
                </tr>

            <?php endif; ?>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/* Helper field renderers */
function stoc_field_checkbox( string $key, string $label, $value, string $desc = '' ) {
    echo '<tr><th>' . esc_html( $label ) . '</th><td>';
    echo '<label><input type="checkbox" name="' . esc_attr(STOC_OPTION) . '[' . esc_attr($key) . ']" value="1" ' . checked( 1, $value, false ) . '> ' . esc_html__( 'Enable', 'smart-toc' ) . '</label>';
    if ( $desc ) echo '<p class="description">' . esc_html( $desc ) . '</p>';
    echo '</td></tr>';
}
function stoc_field_text( string $key, string $label, $value, string $placeholder = '' ) {
    echo '<tr><th>' . esc_html( $label ) . '</th><td>';
    echo '<input type="text" class="regular-text" name="' . esc_attr(STOC_OPTION) . '[' . esc_attr($key) . ']" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '">';
    echo '</td></tr>';
}
function stoc_field_color( string $key, string $label, $value ) {
    echo '<tr><th>' . esc_html( $label ) . '</th><td>';
    echo '<input type="color" name="' . esc_attr(STOC_OPTION) . '[' . esc_attr($key) . ']" value="' . esc_attr( $value ) . '" style="height:38px;width:60px;padding:2px;cursor:pointer">';
    echo ' <input type="text" class="small-text" name="' . esc_attr(STOC_OPTION) . '[' . esc_attr($key) . ']" value="' . esc_attr( $value ) . '" maxlength="20" placeholder="#000000" style="vertical-align:middle">';
    echo '<p class="description">' . esc_html__( 'Enter hex, rgb(), or CSS variable.', 'smart-toc' ) . '</p>';
    echo '</td></tr>';
}

/* =========================================================================
   PER-POST META BOX
   ========================================================================= */
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'smart_toc_post_options',
        __( 'Smart TOC', 'smart-toc' ),
        'stoc_meta_box_cb',
        ['post','page'],
        'side',
        'default'
    );
} );

function stoc_meta_box_cb( WP_Post $post ) {
    wp_nonce_field( 'smart_toc_meta_nonce', 'smart_toc_nonce' );
    $disabled = get_post_meta( $post->ID, STOC_META_KEY, true );
    echo '<label><input type="checkbox" name="smart_toc_disable" value="1" ' . checked( 1, $disabled, false ) . '> ';
    esc_html_e( 'Disable TOC on this post/page', 'smart-toc' );
    echo '</label>';
    echo '<p class="description" style="margin-top:6px">' . esc_html__( 'Or use [smart_toc] shortcode anywhere.', 'smart-toc' ) . '</p>';
}

add_action( 'save_post', function ( int $post_id ) {
    if ( ! isset( $_POST['smart_toc_nonce'] ) ) return;
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['smart_toc_nonce'] ) ), 'smart_toc_meta_nonce' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( ! empty( $_POST['smart_toc_disable'] ) ) {
        update_post_meta( $post_id, STOC_META_KEY, 1 );
    } else {
        delete_post_meta( $post_id, STOC_META_KEY );
    }
} );

/* =========================================================================
   TOC BUILDER — shared logic
   ========================================================================= */
function stoc_build( string $content, array $overrides = [] ): string {
    if ( ! class_exists( 'DOMDocument' ) ) return '';

    $s = wp_parse_args( $overrides, stoc_get() );

    $allowed = array_filter( (array) $s['heading_levels'] );
    if ( empty( $allowed ) ) return '';

    $dom = new DOMDocument();
    libxml_use_internal_errors( true );
    $dom->loadHTML(
        '<html><head><meta charset="utf-8"></head><body>' .
        mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) .
        '</body></html>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();

    $xpath    = new DOMXPath( $dom );
    $query    = '//' . implode( ' | //', $allowed );
    $headings = $xpath->query( $query );

    if ( ! $headings || $headings->length < (int) $s['min_headings'] ) return '';

    $items = [];
    foreach ( $headings as $h ) {
        $id = $h->getAttribute( 'id' );
        if ( empty( $id ) ) {
            $id = sanitize_title( $h->nodeValue );
            $h->setAttribute( 'id', $id );
        }
        $items[] = [
            'id'    => $id,
            'text'  => $h->nodeValue,
            'tag'   => strtolower( $h->tagName ),
        ];
    }

    /* Build list HTML */
    $indent_map = [
        'h2' => '0',
        'h3' => esc_attr( $s['indent_h3'] ),
        'h4' => esc_attr( $s['indent_h4'] ),
        'h5' => esc_attr( $s['indent_h4'] ),
        'h6' => esc_attr( $s['indent_h4'] ),
    ];

    $list = '<ul class="smart-toc-list">';
    foreach ( $items as $item ) {
        $indent = $indent_map[ $item['tag'] ] ?? '0';
        $list .= sprintf(
            '<li class="smart-toc-item smart-toc-%s" style="padding-left:%s"><a href="#%s">%s</a></li>',
            esc_attr( $item['tag'] ),
            esc_attr( $indent ),
            esc_attr( $item['id'] ),
            esc_html( $item['text'] )
        );
    }
    $list .= '</ul>';

    $open_attr = $s['open_by_default'] ? ' open' : '';
    $title_esc = esc_html( $s['title'] );

    $toc = sprintf(
        '<details class="smart-toc-wrap"%s>
            <summary class="smart-toc-summary">
                <span class="smart-toc-title">%s</span>
                <span class="smart-toc-toggle smart-toc-toggle-open">%s</span>
                <span class="smart-toc-toggle smart-toc-toggle-closed">%s</span>
            </summary>
            %s
        </details>',
        $open_attr,
        $title_esc,
        esc_html( $s['toggle_open'] ),
        esc_html( $s['toggle_closed'] ),
        $list
    );

    return $toc;
}

/* Rebuild content with IDs injected */
function stoc_inject_ids( string $content ): string {
    if ( ! class_exists( 'DOMDocument' ) ) return $content;

    $s       = stoc_get();
    $allowed = array_filter( (array) $s['heading_levels'] );
    if ( empty( $allowed ) ) return $content;

    $dom = new DOMDocument();
    libxml_use_internal_errors( true );
    $dom->loadHTML(
        '<html><head><meta charset="utf-8"></head><body>' .
        mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) .
        '</body></html>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();

    $xpath    = new DOMXPath( $dom );
    $query    = '//' . implode( ' | //', $allowed );
    $headings = $xpath->query( $query );

    if ( ! $headings || $headings->length === 0 ) return $content;

    foreach ( $headings as $h ) {
        if ( empty( $h->getAttribute( 'id' ) ) ) {
            $id = sanitize_title( $h->nodeValue );
            $h->setAttribute( 'id', $id );
        }
    }

    // Extract only <body> inner HTML
    $body = $dom->getElementsByTagName( 'body' )->item(0);
    if ( ! $body ) return $content;

    $new_content = '';
    foreach ( $body->childNodes as $node ) {
        $new_content .= $dom->saveHTML( $node );
    }

    return $new_content ?: $content;
}

/* =========================================================================
   CSS OUTPUT
   ========================================================================= */
function stoc_css(): string {
    $s = stoc_get();

    $smooth = $s['smooth_scroll'] ? 'html { scroll-behavior: smooth; }' : '';

    return sprintf(
        '<style id="smart-toc-styles">
        %s
        .smart-toc-wrap {
            background: %s;
            border: %s solid %s;
            border-radius: %s;
            padding: %s;
            margin-bottom: %s;
            max-width: %s;
            font-size: %s;
            font-family: %s;
            color: %s;
            box-sizing: border-box;
        }
        .smart-toc-summary {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            list-style: none;
            outline: none;
            user-select: none;
        }
        .smart-toc-summary::-webkit-details-marker { display: none; }
        .smart-toc-title {
            flex: 1;
            font-size: %s;
            font-weight: %s;
            color: %s;
        }
        .smart-toc-toggle { font-size: 0.75em; color: %s; }
        .smart-toc-toggle-closed { display: none; }
        .smart-toc-wrap:not([open]) .smart-toc-toggle-open   { display: none; }
        .smart-toc-wrap:not([open]) .smart-toc-toggle-closed { display: inline; }
        .smart-toc-list {
            list-style: %s;
            margin: 0.5rem 0 0;
            padding-left: 1rem;
        }
        .smart-toc-item { margin-bottom: 0.2rem; }
        .smart-toc-item a {
            text-decoration: none;
            color: %s;
            transition: color 0.15s;
        }
        .smart-toc-item a:hover { color: %s; text-decoration: underline; }
        %s
        </style>',
        $smooth,
        esc_attr( $s['bg_color'] ),
        esc_attr( $s['border_width'] ), esc_attr( $s['border_color'] ),
        esc_attr( $s['border_radius'] ),
        esc_attr( $s['padding'] ),
        esc_attr( $s['margin_bottom'] ),
        esc_attr( $s['max_width'] ),
        esc_attr( $s['font_size'] ),
        esc_attr( $s['font_family'] ),
        esc_attr( $s['text_color'] ),
        esc_attr( $s['title_font_size'] ),
        esc_attr( $s['title_font_weight'] ),
        esc_attr( $s['title_color'] ),
        esc_attr( $s['title_color'] ),
        esc_attr( $s['list_style'] ),
        esc_attr( $s['link_color'] ),
        esc_attr( $s['link_hover_color'] ),
        $s['custom_css'] // already stripped of tags on save
    );
}

/* =========================================================================
   AUTO-INJECTION VIA the_content FILTER
   ========================================================================= */
add_filter( 'the_content', function ( string $content ): string {
    // Only run once in main query loop
    if ( ! in_the_loop() || ! is_main_query() ) return $content;

    $s = stoc_get();

    $is_post = is_single()  && $s['auto_posts'];
    $is_page = is_page()    && $s['auto_pages'];

    if ( ! $is_post && ! $is_page ) return $content;

    // Per-post override
    if ( get_post_meta( get_the_ID(), STOC_META_KEY, true ) ) return $content;

    // If shortcode already present, skip auto-inject
    if ( has_shortcode( $content, 'smart_toc' ) ) return $content;

    $content = stoc_inject_ids( $content );
    $toc     = stoc_build( $content );

    if ( empty( $toc ) ) return $content;

    $toc = stoc_css() . $toc;

    return $s['position'] === 'after' ? $content . $toc : $toc . $content;
}, 10 );

/* =========================================================================
   SHORTCODE  [smart_toc]
   Accepts: title, open, min_headings, heading_levels (comma list)
   Example: [smart_toc title="Contents" open="yes" heading_levels="h2,h3,h4"]
   ========================================================================= */
add_shortcode( 'smart_toc', function ( $atts ): string {
    global $post;
    if ( ! $post instanceof WP_Post ) return '';

    $atts = shortcode_atts( [
        'title'          => '',
        'open'           => '',      // yes/no override
        'min_headings'   => '',
        'heading_levels' => '',
    ], $atts, 'smart_toc' );

    $overrides = [];
    if ( $atts['title'] !== '' )        $overrides['title']          = sanitize_text_field( $atts['title'] );
    if ( $atts['open'] !== '' )         $overrides['open_by_default'] = strtolower( $atts['open'] ) !== 'no' ? 1 : 0;
    if ( $atts['min_headings'] !== '' ) $overrides['min_headings']   = max( 1, intval( $atts['min_headings'] ) );
    if ( $atts['heading_levels'] !== '' ) {
        $levels = array_map( 'trim', explode( ',', $atts['heading_levels'] ) );
        $overrides['heading_levels'] = array_intersect( $levels, ['h2','h3','h4','h5','h6'] );
    }

    $content = apply_filters( 'the_content', $post->post_content );

    /* Always inject IDs into the real content when shortcode is used manually */
    add_filter( 'the_content', function ( string $c ) use ( $post ): string {
        if ( get_the_ID() === $post->ID ) {
            return stoc_inject_ids( $c );
        }
        return $c;
    }, 5 );

    $toc = stoc_build( $content, $overrides );
    if ( empty( $toc ) ) return '';

    static $css_printed = false;
    $css = '';
    if ( ! $css_printed ) {
        $css         = stoc_css();
        $css_printed = true;
    }

    return $css . $toc;
} );

/* =========================================================================
   PLUGIN ACTION LINKS
   ========================================================================= */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( array $links ): array {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=smart-toc' ) ) . '">' . esc_html__( 'Settings', 'smart-toc' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
} );

/* =========================================================================
   UNINSTALL HOOK (registered via option, not separate file for simplicity)
   ========================================================================= */
register_uninstall_hook( __FILE__, 'stoc_uninstall' );
function stoc_uninstall() {
    delete_option( STOC_OPTION );
    // Clean per-post meta
    delete_post_meta_by_key( STOC_META_KEY );
}
