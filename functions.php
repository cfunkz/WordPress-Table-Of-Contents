/**
 * Collapsible Table of Contents for WordPress Posts
 * Fully customizable via variables
 */

add_filter( 'the_content', function ( $content ) {

    if ( ! class_exists( 'DOMDocument' ) || ! is_single() || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    // ---------------------------
    // CONFIG VARIABLES (safe to tweak)
    // ---------------------------
    $toc_font_size      = '0.95rem';
    $toc_margin_bottom  = '1rem';
    $toc_padding_left   = '1.25rem';
    $toc_h3_padding     = '1.5rem';
    $toc_text_color     = '#1e293b'; // dark theme fallback
    $toc_link_hover     = 'underline';
    $toc_summary_font_weight = '600';
    $toc_summary_cursor = 'pointer';
    $toc_summary_margin_bottom = '0.5rem';
    $toc_summary_text_color = '#4f46e5'; // summary header color

    // ---------------------------
    // LOAD DOM AND HEADINGS
    // ---------------------------
    $dom = new DOMDocument();
    $load_content = mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' );
    if ( empty( $load_content ) ) return $content;
    @$dom->loadHTML( $load_content );

    $xpath = new DOMXPath( $dom );
    $headings = $xpath->query( '//h2 | //h3' );

    if ( ! $headings || $headings->length === 0 ) return $content;

    // ---------------------------
    // BUILD TOC
    // ---------------------------
    $headings_list = '<details class="toc-collapsible" open><summary>Table of Contents</summary><ul>';
    foreach ( $headings as $heading ) {
        $heading_id = $heading->getAttribute( 'id' );
        if ( empty( $heading_id ) ) {
            $old_heading = $heading->C14N();
            $heading_id = sanitize_title( $heading->nodeValue );
            $heading->setAttribute( 'id', $heading_id );
            $content = str_replace( $old_heading, $heading->C14N(), $content );
        }

        $tag_name = strtolower( $heading->tagName );
        $padding  = $tag_name === 'h3' ? $toc_h3_padding : '0';

        $headings_list .= '<li class="toc-' . $tag_name . '" style="padding-left:' . $padding . ';">';
        $headings_list .= '<a href="#' . esc_attr($heading_id) . '">' . esc_html($heading->nodeValue) . '</a>';
        $headings_list .= '</li>';
    }
    $headings_list .= '</ul></details>';

    // ---------------------------
    // CSS USING VARIABLES
    // ---------------------------
    $css = '<style>
        .toc-collapsible {
            margin-bottom: ' . $toc_margin_bottom . ';
            font-size: ' . $toc_font_size . ';
            color: ' . $toc_text_color . ';
        }
        .toc-collapsible summary {
            font-weight: ' . $toc_summary_font_weight . ';
            cursor: ' . $toc_summary_cursor . ';
            margin-bottom: ' . $toc_summary_margin_bottom . ';
            color: ' . $toc_summary_text_color . ';
            outline: none;
        }
        .toc-collapsible ul {
            list-style: disc inside;
            padding-left: ' . $toc_padding_left . ';
            margin: 0.5rem 0;
        }
        .toc-collapsible li {
            margin-bottom: 0.25rem;
        }
        .toc-collapsible a {
            text-decoration: none;
            color: inherit;
        }
        .toc-collapsible a:hover {
            text-decoration: ' . $toc_link_hover . ';
        }
    </style>';

    return $css . $headings_list . $content;

});
