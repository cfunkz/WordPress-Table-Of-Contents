<?php
/**
 * Plugin Name:  Smart TOC
 * Plugin URI:   https://github.com/cfunkz/WordPress-Table-Of-Contents
 * Description:  Zero-JS Table of Contents — pure <details>/<summary> + CSS. No bloat.
 * Version:      3.0.0
 * Author:       cfunkz
 * License:      GPL-2.0-or-later
 * Text Domain:  smart-toc
 * Requires at least: 5.0
 * Requires PHP:      7.4
 */

defined( 'ABSPATH' ) || exit;

const STOC_OPT  = 'smart_toc_settings';
const STOC_META = '_smart_toc_disable';

// ── Defaults ──────────────────────────────────────────────────────────────────
function stoc_defaults(): array {
	return [
		'auto_posts'      => 1,
		'auto_pages'      => 0,
		'position'        => 'before',
		'min_headings'    => 3,
		'heading_levels'  => ['h2','h3'],
		'open_by_default' => 1,
		'smooth_scroll'   => 1,
		// Labels
		'title'           => 'Table of Contents',
		'toggle_open'     => '▲',
		'toggle_closed'   => '▼',
		// Typography
		'font_size'       => '0.95rem',
		'font_family'     => 'inherit',
		'title_size'      => '1rem',
		'title_weight'    => '600',
		'link_weight'     => 'normal',
		// Colours — empty = inherit
		'color'           => '',
		'title_color'     => '',
		'link_color'      => '',
		'link_hover'      => '',
		// Box
		'bg'              => '',
		'bg_alpha'        => '1',
		'border'          => '1px solid rgba(0,0,0,0.12)',
		'radius'          => '8px',
		'padding'         => '0.8rem 1.1rem',
		'max_width'       => 'fit-content',
		'box_align'       => 'left',
		'no_box'          => 0,
		// List
		'title_align'     => 'left',
		'link_align'      => 'left',
		'list_style'      => 'disc',
		'list_padding'    => '1.4rem',
		'indent'          => '1.2rem',
		'item_gap'        => '0.25rem',
		// Custom CSS
		'custom_css'      => '',
	];
}

function stoc_get(): array {
	static $c = null;
	if ( $c === null ) {
		$c = wp_parse_args( (array) get_option( STOC_OPT, [] ), stoc_defaults() );
	}
	return $c;
}

// ── Sanitize ──────────────────────────────────────────────────────────────────
function stoc_sanitize( array $in ): array {
	$d = stoc_defaults();

	foreach ( ['auto_posts','auto_pages','open_by_default','smooth_scroll','no_box'] as $k ) {
		$d[$k] = isset( $in[$k] ) ? 1 : 0;
	}

	$d['min_headings']   = max( 1, (int) ( $in['min_headings'] ?? 3 ) );
	$d['heading_levels'] = array_values( array_intersect(
		(array) ( $in['heading_levels'] ?? [] ), ['h2','h3','h4','h5','h6']
	) ) ?: ['h2','h3'];

	foreach ( ['position','box_align','title_align','link_align'] as $k ) {
		$allowed = ( $k === 'position' ) ? ['before','after'] : ['left','center','right'];
		$d[$k]   = in_array( $in[$k] ?? '', $allowed, true ) ? $in[$k] : $d[$k];
	}
	$d['list_style'] = in_array( $in['list_style'] ?? '', ['disc','decimal','circle','square','none'], true )
		? $in['list_style'] : 'disc';

	foreach ( [
		'title','toggle_open','toggle_closed',
		'font_size','font_family','title_size','title_weight','link_weight',
		'color','title_color','link_color','link_hover',
		'bg','bg_alpha','border','radius','padding','max_width',
		'list_padding','indent','item_gap',
	] as $k ) {
		$d[$k] = sanitize_text_field( $in[$k] ?? $d[$k] );
	}

	$d['custom_css'] = wp_strip_all_tags( $in['custom_css'] ?? '' );
	return $d;
}

// ── Admin ─────────────────────────────────────────────────────────────────────
add_action( 'admin_menu', fn() =>
	add_options_page( 'Smart TOC', 'Smart TOC', 'manage_options', 'smart-toc', 'stoc_admin_page' )
);
add_action( 'admin_init', fn() =>
	register_setting( 'stoc_group', STOC_OPT, [
		'sanitize_callback' => 'stoc_sanitize',
		'type'              => 'array',
	])
);
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
	array_unshift( $links, '<a href="' . admin_url('options-general.php?page=smart-toc') . '">Settings</a>' );
	return $links;
} );

// ── Admin page styles & scripts ───────────────────────────────────────────────
add_action( 'admin_head', function() {
	if ( ( get_current_screen()->id ?? '' ) !== 'settings_page_smart-toc' ) return; ?>
<style>
/* ── Layout ── */
#stoc-wrap{max-width:980px;margin:24px auto 60px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
#stoc-wrap *{box-sizing:border-box}
/* ── Header ── */
#stoc-head{background:linear-gradient(130deg,#0f172a 0%,#1e3a8a 100%);border-radius:10px;padding:20px 26px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
#stoc-head h1{margin:0;color:#f8fafc;font-size:1.2rem;font-weight:700;display:flex;align-items:center;gap:10px}
#stoc-head h1 span{font-size:.65rem;font-weight:500;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);color:#93c5fd;padding:2px 9px;border-radius:20px;letter-spacing:.05em}
.stoc-sc{display:flex;flex-wrap:wrap;gap:7px}
.stoc-sc code{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);color:#7dd3fc;padding:3px 9px;border-radius:5px;font-size:.72rem}
/* ── Tabs ── */
#stoc-tabs{display:flex;gap:2px;flex-wrap:wrap;margin-bottom:-1px;padding-left:1px}
.stoc-tab{padding:8px 15px;border:1px solid #ddd;border-bottom:none;border-radius:7px 7px 0 0;background:#f6f7f7;color:#50575e;font-size:.8rem;font-weight:600;cursor:pointer;outline:none;transition:background .12s,color .12s}
.stoc-tab:hover{background:#fff;color:#1d2327}
.stoc-tab.active{background:#fff;color:#1d2327;border-bottom-color:#fff;position:relative;z-index:2}
/* ── Card ── */
#stoc-card{background:#fff;border:1px solid #ddd;border-radius:0 10px 10px 10px;padding:26px 28px}
.stoc-panel{display:none}.stoc-panel.active{display:block}
/* ── Sections ── */
.stoc-section{font-size:.67rem;text-transform:uppercase;letter-spacing:.1em;color:#9ca3af;font-weight:700;padding:16px 0 7px;border-bottom:1px solid #f1f5f9;margin-bottom:4px}
.stoc-section:first-child{padding-top:0}
/* ── Row ── */
.stoc-row{display:grid;grid-template-columns:200px 1fr;gap:8px 20px;align-items:center;padding:11px 0;border-bottom:1px solid #f9fafb}
.stoc-row:last-child{border:none}
.stoc-label{font-size:.83rem;font-weight:600;color:#374151;line-height:1.3}
.stoc-label small{display:block;font-weight:400;color:#9ca3af;font-size:.73rem;margin-top:2px}
.stoc-field{display:flex;flex-direction:column;gap:5px}
.stoc-hint{font-size:.74rem;color:#9ca3af;line-height:1.5}
.stoc-hint code{background:#f1f5f9;padding:1px 4px;border-radius:3px;font-size:.88em;color:#0369a1}
/* ── Inputs ── */
.stoc-row input[type=text],
.stoc-row input[type=number],
.stoc-row select,
.stoc-row textarea{width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.83rem;color:#1d2327;background:#fdfdfd;outline:none;transition:border-color .15s,box-shadow .15s;font-family:inherit}
.stoc-row input[type=number]{width:70px}
.stoc-row input:focus,.stoc-row select:focus,.stoc-row textarea:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1);background:#fff}
/* ── Toggle switch ── */
.stoc-switch{display:flex;align-items:center;gap:9px;cursor:pointer}
.stoc-sw{position:relative;width:38px;height:20px;flex-shrink:0}
.stoc-sw input{opacity:0;position:absolute;width:0;height:0}
.stoc-sw-track{position:absolute;inset:0;background:#d1d5db;border-radius:20px;transition:background .2s}
.stoc-sw-track::before{content:'';position:absolute;width:14px;height:14px;left:3px;top:3px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.stoc-sw input:checked~.stoc-sw-track{background:#2563eb}
.stoc-sw input:checked~.stoc-sw-track::before{transform:translateX(18px)}
.stoc-sw-lbl{font-size:.83rem;color:#374151}
/* ── Heading level pills ── */
.stoc-pills{display:flex;gap:5px;flex-wrap:wrap}
.stoc-pill{padding:4px 12px;border:1.5px solid #d1d5db;border-radius:5px;font-size:.78rem;font-weight:700;color:#6b7280;cursor:pointer;user-select:none;transition:all .13s;background:#fafafa;display:flex;align-items:center}
.stoc-pill input{display:none}
.stoc-pill.on{border-color:#2563eb;color:#1d4ed8;background:#eff6ff}
/* ── Color row ── */
.color-row{display:flex;gap:8px;align-items:center}
.color-row input[type=color]{width:34px;height:32px;padding:2px;border:1px solid #d1d5db;border-radius:5px;cursor:pointer;background:none;flex-shrink:0}
.color-row input[type=text]{flex:1}
/* ── Alpha slider ── */
.alpha-wrap{display:flex;align-items:center;gap:9px;margin-top:4px}
.alpha-wrap label{font-size:.75rem;color:#6b7280;white-space:nowrap}
.alpha-wrap input[type=range]{flex:1;accent-color:#2563eb}
.alpha-wrap output{font-size:.75rem;color:#374151;min-width:28px;text-align:right}
/* ── Callout ── */
.stoc-note{background:#eff6ff;border-left:3px solid #3b82f6;border-radius:0 6px 6px 0;padding:10px 13px;margin-bottom:16px;font-size:.78rem;line-height:1.6;color:#1e40af}
/* ── CSS editor ── */
.css-editor{font-family:'SF Mono','Fira Code',monospace!important;font-size:.78rem!important;line-height:1.8!important;min-height:160px;resize:vertical}
/* ── Selector ref ── */
.sel-ref{background:#f8fafc;border:1px solid #e2e8f0;border-radius:7px;padding:13px 15px;margin-top:10px}
.sel-ref h4{margin:0 0 9px;font-size:.67rem;text-transform:uppercase;letter-spacing:.08em;color:#6b7280}
.sel-ref-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:5px 20px}
.sel-ref-row{font-size:.74rem;display:flex;align-items:baseline;gap:6px}
.sel-ref-row code{font-family:monospace;color:#0369a1;background:#e0f2fe;padding:1px 5px;border-radius:3px;white-space:nowrap}
.sel-ref-row span{color:#9ca3af;font-size:.7rem}
pre.sel-ex{background:#0f172a;color:#7dd3fc;padding:11px 14px;border-radius:6px;font-size:.72rem;line-height:1.75;overflow-x:auto;margin:10px 0 0}
/* ── Footer ── */
.stoc-footer{display:flex;justify-content:flex-end;padding-top:18px;margin-top:8px;border-top:1px solid #f1f5f9}
#stoc-save{background:#1d4ed8;color:#fff;border:none;padding:9px 28px;border-radius:7px;font-size:.88rem;font-weight:700;cursor:pointer;box-shadow:0 1px 6px rgba(29,78,216,.25);transition:background .15s,transform .1s}
#stoc-save:hover{background:#1e40af;transform:translateY(-1px)}
@media(max-width:600px){.stoc-row{grid-template-columns:1fr}.sel-ref-grid{grid-template-columns:1fr}}
</style>
<?php } );

// ── Admin JS (tabs + colour picker sync + pill toggle) ────────────────────────
add_action( 'admin_footer', function() {
	if ( ( get_current_screen()->id ?? '' ) !== 'settings_page_smart-toc' ) return; ?>
<script>
(function(){
// Tabs
document.querySelectorAll('.stoc-tab').forEach(btn => {
	btn.addEventListener('click', () => {
		document.querySelectorAll('.stoc-tab, .stoc-panel').forEach(el => el.classList.remove('active'));
		btn.classList.add('active');
		document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
	});
});
// Heading level pills
document.querySelectorAll('.stoc-pill input').forEach(cb => {
	cb.addEventListener('change', () => cb.closest('.stoc-pill').classList.toggle('on', cb.checked));
});
// Colour picker ↔ text sync
document.querySelectorAll('input[type=color][data-for]').forEach(picker => {
	const tx = document.getElementById(picker.dataset.for);
	if (!tx) return;
	// Only show picker when value is a valid hex
	const sync = () => { picker.style.opacity = /^#[0-9a-fA-F]{3,6}$/.test(tx.value.trim()) ? '1' : '0.35'; };
	picker.addEventListener('input', () => { tx.value = picker.value; });
	tx.addEventListener('input',    () => { if(/^#[0-9a-fA-F]{3,6}$/.test(tx.value.trim())) picker.value = tx.value.trim(); sync(); });
	sync();
});
// Alpha slider label
document.querySelectorAll('.alpha-wrap input[type=range]').forEach(r => {
	const out = r.nextElementSibling;
	r.addEventListener('input', () => { if(out) out.value = r.value; });
});
})();
</script>
<?php } );

// ── Admin page HTML ───────────────────────────────────────────────────────────
function stoc_admin_page(): void {
	if ( ! current_user_can('manage_options') ) return;
	$s  = stoc_get();
	$n  = STOC_OPT;
	$lv = (array) $s['heading_levels'];

	// Helper: toggle switch
	$sw = function( string $key, string $label, string $hint = '' ) use ( $s, $n ): void {
		$id = 'sw-' . $key;
		echo '<div class="stoc-row"><div class="stoc-label">' . esc_html($label) . '</div><div class="stoc-field">';
		echo '<label class="stoc-switch">';
		echo '<span class="stoc-sw"><input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($n) . '[' . esc_attr($key) . ']" value="1"' . checked(1,(int)$s[$key],false) . '><span class="stoc-sw-track"></span></span>';
		echo '<span class="stoc-sw-lbl">Enable</span></label>';
		if ($hint) echo '<span class="stoc-hint">' . wp_kses($hint, ['code'=>[]]) . '</span>';
		echo '</div></div>';
	};

	// Helper: text field
	$tf = function( string $key, string $label, string $ph = '', string $hint = '' ) use ( $s, $n ): void {
		$id = 'tf-' . $key;
		echo '<div class="stoc-row"><div class="stoc-label">' . esc_html($label) . '</div><div class="stoc-field">';
		echo '<input type="text" id="' . esc_attr($id) . '" name="' . esc_attr($n) . '[' . esc_attr($key) . ']" value="' . esc_attr($s[$key]) . '" placeholder="' . esc_attr($ph) . '">';
		if ($hint) echo '<span class="stoc-hint">' . wp_kses($hint, ['code'=>[]]) . '</span>';
		echo '</div></div>';
	};

	// Helper: select
	$sel = function( string $key, string $label, array $opts, string $hint = '' ) use ( $s, $n ): void {
		echo '<div class="stoc-row"><div class="stoc-label">' . esc_html($label) . '</div><div class="stoc-field">';
		echo '<select name="' . esc_attr($n) . '[' . esc_attr($key) . ']">';
		foreach ($opts as $v => $l) echo '<option value="' . esc_attr($v) . '"' . selected($s[$key],$v,false) . '>' . esc_html($l) . '</option>';
		echo '</select>';
		if ($hint) echo '<span class="stoc-hint">' . esc_html($hint) . '</span>';
		echo '</div></div>';
	};

	// Helper: colour picker + text
	$col = function( string $key, string $label, string $hint = '' ) use ( $s, $n ): void {
		$id  = 'col-' . $key;
		$val = esc_attr($s[$key]);
		$hex = preg_match('/^#[0-9a-fA-F]{3,6}$/', trim($s[$key])) ? trim($s[$key]) : '#000000';
		echo '<div class="stoc-row"><div class="stoc-label">' . esc_html($label) . ( $hint ? '<small>' . esc_html($hint) . '</small>' : '' ) . '</div><div class="stoc-field">';
		echo '<div class="color-row">';
		echo '<input type="color" data-for="' . esc_attr($id) . '" value="' . esc_attr($hex) . '">';
		echo '<input type="text" id="' . esc_attr($id) . '" name="' . esc_attr($n) . '[' . esc_attr($key) . ']" value="' . $val . '" placeholder="inherit or #hex or rgba(…)">';
		echo '</div></div></div>';
	};
	?>
<div id="stoc-wrap">

<div id="stoc-head">
	<h1>
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7dd3fc" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
		Smart TOC <span>v3.0</span>
	</h1>
	<div class="stoc-sc">
		<code>[smart_toc]</code>
		<code>title="…"</code>
		<code>open="yes|no"</code>
		<code>heading_levels="h2,h3"</code>
		<code>align="left|center|right"</code>
	</div>
</div>

<form method="post" action="options.php">
<?php settings_fields('stoc_group'); ?>

<div id="stoc-tabs">
	<button type="button" class="stoc-tab active" data-tab="behaviour">⚙ Behaviour</button>
	<button type="button" class="stoc-tab"        data-tab="labels">🏷 Labels</button>
	<button type="button" class="stoc-tab"        data-tab="type">Aa Type</button>
	<button type="button" class="stoc-tab"        data-tab="colours">🎨 Colours</button>
	<button type="button" class="stoc-tab"        data-tab="box">◻ Box</button>
	<button type="button" class="stoc-tab"        data-tab="list">☰ List</button>
	<button type="button" class="stoc-tab"        data-tab="css">{} CSS</button>
</div>

<div id="stoc-card">

<!-- BEHAVIOUR ─────────────────────────────────────────────────────────────── -->
<div class="stoc-panel active" id="panel-behaviour">
	<p class="stoc-section">Auto-inject</p>
	<?php $sw('auto_posts', 'Posts', 'Inject TOC automatically into every single post.') ?>
	<?php $sw('auto_pages', 'Pages', 'Same for static pages (About, FAQ, etc.). Off by default.') ?>

	<?php $sel('position', 'Position', ['before'=>'Before content','after'=>'After content'], 'Where to insert the TOC relative to post content.') ?>

	<p class="stoc-section">Parsing</p>
	<div class="stoc-row">
		<div class="stoc-label">Min headings<small>Skip page if fewer</small></div>
		<div class="stoc-field">
			<input type="number" min="1" max="20" name="<?php echo $n ?>[min_headings]" value="<?php echo (int)$s['min_headings'] ?>">
		</div>
	</div>
	<div class="stoc-row">
		<div class="stoc-label">Heading levels<small>Which tags to include</small></div>
		<div class="stoc-field">
			<div class="stoc-pills">
				<?php foreach (['h2','h3','h4','h5','h6'] as $h):
					$on = in_array($h, $lv, true); ?>
				<label class="stoc-pill <?php echo $on ? 'on' : '' ?>">
					<input type="checkbox" name="<?php echo $n ?>[heading_levels][]" value="<?php echo $h ?>" <?php checked($on) ?>>
					<?php echo strtoupper($h) ?>
				</label>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<p class="stoc-section">UX</p>
	<?php $sw('open_by_default', 'Start expanded',  'TOC is open on load — visitor can still collapse it.') ?>
	<?php $sw('smooth_scroll',   'Smooth scroll',   'Adds <code>scroll-behavior:smooth</code> to the page.') ?>
</div>

<!-- LABELS ─────────────────────────────────────────────────────────────────── -->
<div class="stoc-panel" id="panel-labels">
	<?php $tf('title',         'Title text',    'Table of Contents', 'The heading shown at the top of the box.') ?>
	<?php $sel('title_align',  'Title align',   ['left'=>'Left','center'=>'Center','right'=>'Right'], 'Alignment of the title + toggle icons inside the summary bar.') ?>
	<?php $tf('toggle_open',   'Collapse icon', '▲', 'Shown when open. Any text, emoji or symbol.') ?>
	<?php $tf('toggle_closed', 'Expand icon',   '▼', 'Shown when collapsed.') ?>
</div>

<!-- TYPOGRAPHY ─────────────────────────────────────────────────────────────── -->
<div class="stoc-panel" id="panel-type">
	<?php $tf('font_size',    'Font size',    '0.95rem', 'Any CSS unit: <code>rem</code>, <code>em</code>, <code>px</code>.') ?>
	<?php $tf('font_family',  'Font family',  'inherit',  '<code>inherit</code> picks up your theme font automatically.') ?>
	<?php $tf('title_size',   'Title size',   '1rem',     'Font size of the TOC heading row.') ?>
	<?php $tf('title_weight', 'Title weight', '600',      '400 = normal · 600 = semi-bold · 700 = bold') ?>
	<?php $tf('link_weight',  'Link weight',  'normal',   'Font weight of the anchor links.') ?>
</div>

<!-- COLOURS ─────────────────────────────────────────────────────────────────── -->
<div class="stoc-panel" id="panel-colours">
	<div class="stoc-note"><strong>Tip:</strong> Leave a field empty or type <code>inherit</code> to follow your theme colours — dark-mode and CSS variables work automatically.</div>
	<?php $col('color',       'Body text',   'General colour inside the box') ?>
	<?php $col('title_color', 'Title',       'TOC heading text colour') ?>
	<?php $col('link_color',  'Links',       'Anchor link colour') ?>
	<?php $col('link_hover',  'Link hover',  'Link colour on mouse hover') ?>
</div>

<!-- BOX ────────────────────────────────────────────────────────────────────── -->
<div class="stoc-panel" id="panel-box">
	<?php $sw('no_box', 'No box', 'Strip all box styling — useful when the TOC floats inside styled content.') ?>

	<div class="stoc-row">
		<div class="stoc-label">Background<small>Colour or gradient</small></div>
		<div class="stoc-field">
			<div class="color-row">
				<input type="color" data-for="tf-bg" value="#ffffff">
				<input type="text" id="tf-bg" name="<?php echo $n ?>[bg]" value="<?php echo esc_attr($s['bg']) ?>" placeholder="e.g. #f8fafc or rgba(0,0,0,0.04)">
			</div>
			<div class="alpha-wrap">
				<label for="sl-bg-alpha">Opacity</label>
				<input type="range" id="sl-bg-alpha" min="0" max="1" step="0.01"
					name="<?php echo $n ?>[bg_alpha]"
					value="<?php echo esc_attr($s['bg_alpha']) ?>">
				<output for="sl-bg-alpha"><?php echo esc_html($s['bg_alpha']) ?></output>
			</div>
			<span class="stoc-hint">Use <code>bg_alpha</code> for quick transparency. Or write a full <code>rgba()</code> directly in the box above.</span>
		</div>
	</div>

	<?php $tf('border',    'Border',        '1px solid rgba(0,0,0,0.12)', 'e.g. <code>1px solid rgba(0,0,0,0.12)</code> or <code>none</code>') ?>
	<?php $tf('radius',    'Border radius', '8px',            '<code>0</code> for sharp · <code>50%</code> for pill.') ?>
	<?php $tf('padding',   'Padding',       '0.8rem 1.1rem',  'CSS shorthand — vertical horizontal.') ?>
	<?php $tf('max_width', 'Max width',     'fit-content',    '<code>fit-content</code> = shrink to text width. <code>100%</code> = full column.') ?>
	<?php $sel('box_align', 'Box alignment', ['left'=>'Left','center'=>'Center','right'=>'Right'], 'Horizontal position of the whole TOC box on the page.') ?>
</div>

<!-- LIST ────────────────────────────────────────────────────────────────────── -->
<div class="stoc-panel" id="panel-list">
	<?php $sel('list_style', 'Bullet style', ['disc'=>'● Disc','decimal'=>'1. Numbered','circle'=>'○ Circle','square'=>'■ Square','none'=>'— None']) ?>
	<?php $sel('link_align', 'Link align',   ['left'=>'Left','center'=>'Center','right'=>'Right'], 'text-align of each list item.') ?>
	<?php $tf('list_padding', 'List padding', '1.4rem',  'Left padding of the <code>&lt;ul&gt;</code> — shifts bullets + text together.') ?>
	<?php $tf('item_gap',     'Item gap',     '0.25rem', 'Vertical spacing between items.') ?>
	<?php $tf('indent',       'Sub-level indent', '1.2rem', 'Extra indent per sub-level. H3 = 1×, H4 = 2×, etc.') ?>
</div>

<!-- CUSTOM CSS ───────────────────────────────────────────────────────────────── -->
<div class="stoc-panel" id="panel-css">
	<div class="stoc-row" style="grid-template-columns:1fr">
		<div class="stoc-field">
			<textarea name="<?php echo $n ?>[custom_css]" class="css-editor" rows="10"
				placeholder="/* Appended after all generated styles */
details.smart-toc-wrap { box-shadow: 0 2px 12px rgba(0,0,0,.08); }
li.smart-toc-h3        { opacity: .85; }"><?php echo esc_textarea($s['custom_css']) ?></textarea>
		</div>
	</div>
	<div class="sel-ref">
		<h4>Selector reference</h4>
		<div class="sel-ref-grid">
			<?php foreach ([
				'details.smart-toc-wrap'          => 'Outer box',
				'.smart-toc-summary'              => 'Clickable title row',
				'.smart-toc-title'                => 'Title text',
				'.smart-toc-toggle-open'          => 'Icon when expanded',
				'.smart-toc-toggle-closed'        => 'Icon when collapsed',
				'ul.smart-toc-list'               => 'The list',
				'li.smart-toc-item'               => 'Every list item',
				'li.smart-toc-item a'             => 'Links',
				'li.smart-toc-h2 / h3 / h4…'     => 'Items by level',
				'details.smart-toc-wrap[open]'    => 'When expanded',
			] as $sel => $desc): ?>
			<div class="sel-ref-row"><code><?php echo esc_html($sel) ?></code><span><?php echo esc_html($desc) ?></span></div>
			<?php endforeach; ?>
		</div>
<pre class="sel-ex">/* Floating right box, 280 px wide */
details.smart-toc-wrap {
  float: right;
  max-width: 280px;
  margin: 0 0 1rem 1.5rem;
  box-shadow: 0 2px 12px rgba(0,0,0,.08);
}
/* Dimmer sub-items */
li.smart-toc-h3, li.smart-toc-h4 { opacity: .75; }</pre>
	</div>
</div>

<div class="stoc-footer">
	<button type="submit" id="stoc-save">Save Settings</button>
</div>

</div><!-- #stoc-card -->
</form>
</div><!-- #stoc-wrap -->
<?php }

// ── Per-post meta box ──────────────────────────────────────────────────────────
add_action( 'add_meta_boxes', fn() =>
	add_meta_box( 'smart_toc_mb', 'Smart TOC', 'stoc_meta_box', ['post','page'], 'side' )
);

function stoc_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'stoc_meta_nonce', '_stoc_nonce' );
	$dis = get_post_meta( $post->ID, STOC_META, true ) === '1';
	echo '<label style="display:flex;align-items:center;gap:6px;font-size:.85rem">'
	   . '<input type="checkbox" name="stoc_disabled" value="1"' . checked($dis, true, false) . '>'
	   . ' Disable TOC on this post/page</label>'
	   . '<p class="description" style="margin-top:6px;font-size:.8rem">Or place <code>[smart_toc]</code> anywhere in the content for manual positioning.</p>';
}

add_action( 'save_post', function( int $id ): void {
	if ( ! isset($_POST['_stoc_nonce'])
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['_stoc_nonce']) ), 'stoc_meta_nonce' )
		|| ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
		|| ! current_user_can('edit_post', $id) ) return;

	empty($_POST['stoc_disabled'])
		? delete_post_meta($id, STOC_META)
		: update_post_meta($id, STOC_META, '1');
} );

// ── Front-end CSS ──────────────────────────────────────────────────────────────

// Helper: bullet char for inline ::before on center/right layouts
function stoc_bullet_char( string $style ): string {
	return match( $style ) {
		'disc'   => '\\25AA',
		'circle' => '\\25E6',
		'square' => '\\25AA',
		default  => '',
	};
}

function stoc_css(): string {
	$s      = stoc_get();
	$levels = array_values((array)$s['heading_levels']);

	// Box styles
	if ( $s['no_box'] ) {
		$box = 'background:none;border:none;padding:0;';
	} else {
		$alpha = (float)($s['bg_alpha'] ?? 1);
		$bg    = $s['bg'] ? $s['bg'] : '';
		// Apply alpha only when bg is a plain hex — rgba() values are passed through as-is
		if ( $bg && preg_match('/^#[0-9a-fA-F]{3,6}$/', trim($bg)) && $alpha < 1 ) {
			list($r,$g,$b) = sscanf(ltrim(trim($bg),'#'), '%02x%02x%02x');
			$bg = "rgba($r,$g,$b,$alpha)";
		}
		$box  = $bg               ? 'background:' . $bg                . ';' : '';
		$box .= $s['border']      ? 'border:'      . $s['border']      . ';' : '';
		$box .= 'border-radius:'  . $s['radius']   . ';';
		$box .= 'padding:'        . $s['padding']  . ';';
	}

	$align = $s['box_align'] ?? 'left';
	$ml    = $align === 'right'  ? 'auto' : ( $align === 'center' ? 'auto' : '0' );
	$mr    = $align === 'left'   ? 'auto' : ( $align === 'center' ? 'auto' : '0' );

	$tjc   = $s['title_align'] === 'center' ? 'center' : ( $s['title_align'] === 'right' ? 'flex-end' : 'flex-start' );

	// Per-level indent & nested gap
	$indent_css = $nest_css = '';
	if ( count($levels) > 1 ) {
		foreach ( $levels as $i => $lv ) {
			if ( $i === 0 ) continue;
			$indent_css .= 'li.smart-toc-' . $lv . '{margin-left:calc(' . $i . '*' . $s['indent'] . ')}';
		}
	}

	$color = function( string $val ): string {
		return $val !== '' ? $val : 'inherit';
	};

	$css =
		( $s['smooth_scroll'] ? 'html{scroll-behavior:smooth}' : '' )
		. 'details.smart-toc-wrap{'
		.   $box
		.   'margin-left:' . $ml . ';margin-right:' . $mr . ';margin-bottom:1.5rem;'
		.   'max-width:'   . $s['max_width']   . ';'
		.   'font-size:'   . $s['font_size']   . ';'
		.   'font-family:' . $s['font_family'] . ';'
		.   'color:'       . $color($s['color']) . ';'
		.   'box-sizing:border-box}'
		// Summary row — width:100% so justify-content works with fit-content boxes
		. '.smart-toc-summary{display:flex;width:100%;align-items:center;gap:.35rem;list-style:none;cursor:pointer;outline:none;user-select:none;margin-bottom:.4rem;justify-content:' . $tjc . '}'
		. '.smart-toc-summary::-webkit-details-marker{display:none}'
		. '.smart-toc-title{color:' . $color($s['title_color']) . ';font-size:' . $s['title_size'] . ';font-weight:' . $s['title_weight'] . '}'
		. '.smart-toc-toggle{font-size:.72em;opacity:.7;margin-left:.15rem}'
		. '.smart-toc-toggle-open{display:inline!important}'
		. '.smart-toc-toggle-closed{display:none!important}'
		. 'details.smart-toc-wrap:not([open]) .smart-toc-toggle-open{display:none!important}'
		. 'details.smart-toc-wrap:not([open]) .smart-toc-toggle-closed{display:inline!important}'
		. 'ul.smart-toc-list{list-style:none;padding-left:' . $s['list_padding'] . ';margin:0}'
		. 'li.smart-toc-item{list-style:' . ( $s['link_align'] === 'left' ? $s['list_style'] : 'none' ) . '!important;'
		.   'margin-bottom:' . $s['item_gap'] . ';text-align:' . $s['link_align'] . '}'
		. ( $s['link_align'] !== 'left' && $s['list_style'] !== 'none'
			? 'li.smart-toc-item a::before{content:"' . stoc_bullet_char( $s['list_style'] ) . '";margin-right:.4em;opacity:.7}'
			: '' )
		. 'li.smart-toc-item a{text-decoration:none;color:' . $color($s['link_color']) . ';font-weight:' . $s['link_weight'] . '}'
		. 'li.smart-toc-item a:hover{text-decoration:underline;color:' . $color($s['link_hover']) . '}'
		. $indent_css
		. ( $s['custom_css'] ?: '' );

	return '<style id="smart-toc-css">' . $css . '</style>';
}

// ── TOC builder ────────────────────────────────────────────────────────────────
function stoc_build( string $content, array $over = [] ): array {
	if ( ! class_exists('DOMDocument') ) return ['', $content];

	$s      = wp_parse_args( $over, stoc_get() );
	$levels = array_values( array_filter( (array) $s['heading_levels'] ) );
	if ( empty($levels) ) return ['', $content];

	libxml_use_internal_errors(true);
	$dom = new DOMDocument();
	$dom->loadHTML(
		'<?xml encoding="UTF-8"?><html><head><meta charset="UTF-8"></head><body>' . $content . '</body></html>',
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);
	libxml_clear_errors();

	$nodes = (new DOMXPath($dom))->query('//' . implode('|//', $levels));
	if ( ! $nodes || $nodes->length < (int)$s['min_headings'] ) return ['', $content];

	$items = '';
	$seen  = [];
	foreach ( $nodes as $h ) {
		$id = $h->getAttribute('id');
		if ( ! $id ) {
			$base = sanitize_title($h->nodeValue);
			$id   = $base; $i = 1;
			while ( in_array($id, $seen, true) ) $id = $base . '-' . (++$i);
			$h->setAttribute('id', $id);
		}
		$seen[]  = $id;
		$tag     = strtolower($h->tagName);
		$items  .= '<li class="smart-toc-item smart-toc-' . esc_attr($tag) . '">'
		         . '<a href="#' . esc_attr($id) . '">' . esc_html($h->nodeValue) . '</a>'
		         . '</li>';
	}

	$toc = '<details class="smart-toc-wrap"' . ($s['open_by_default'] ? ' open' : '') . '>'
	     . '<summary class="smart-toc-summary">'
	     . '<span class="smart-toc-title">'                         . esc_html($s['title'])         . '</span>'
	     . '<span class="smart-toc-toggle smart-toc-toggle-open">'  . esc_html($s['toggle_open'])   . '</span>'
	     . '<span class="smart-toc-toggle smart-toc-toggle-closed">'. esc_html($s['toggle_closed']) . '</span>'
	     . '</summary>'
	     . '<ul class="smart-toc-list">' . $items . '</ul>'
	     . '</details>';

	$body = $dom->getElementsByTagName('body')->item(0);
	$new  = '';
	if ($body) foreach ($body->childNodes as $node) $new .= $dom->saveHTML($node);
	return [$toc, $new ?: $content];
}

// ── Auto-inject ────────────────────────────────────────────────────────────────
add_filter( 'the_content', function( string $content ): string {
	if ( ! in_the_loop() || ! is_main_query() ) return $content;
	$s = stoc_get();
	if ( ! ( (is_single() && $s['auto_posts']) || (is_page() && $s['auto_pages']) ) ) return $content;
	if ( get_post_meta(get_the_ID(), STOC_META, true) === '1' ) return $content;
	if ( has_shortcode($content, 'smart_toc') ) return $content;

	[$toc, $content] = stoc_build($content);
	if ( ! $toc ) return $content;

	$block = stoc_css() . $toc;
	return $s['position'] === 'after' ? $content . $block : $block . $content;
}, 10 );

// ── Shortcode [smart_toc] ─────────────────────────────────────────────────────
add_shortcode( 'smart_toc', function( $atts ): string {
	static $rendering = false;
	if ($rendering) return '';

	global $post;
	if ( ! $post instanceof WP_Post ) return '';

	$atts = shortcode_atts([
		'title'          => '',
		'open'           => '',
		'min_headings'   => '',
		'heading_levels' => '',
		'align'          => '',
		'title_align'    => '',
		'link_align'     => '',
	], $atts, 'smart_toc');

	$ov = [];
	if ($atts['title']          !== '') $ov['title']           = sanitize_text_field($atts['title']);
	if ($atts['open']           !== '') $ov['open_by_default'] = strtolower($atts['open']) !== 'no' ? 1 : 0;
	if ($atts['min_headings']   !== '') $ov['min_headings']    = max(1, (int)$atts['min_headings']);
	if ($atts['heading_levels'] !== '') $ov['heading_levels']  = array_intersect(
		array_map('trim', explode(',', $atts['heading_levels'])), ['h2','h3','h4','h5','h6']
	);
	foreach (['align'=>'box_align','title_align'=>'title_align','link_align'=>'link_align'] as $attr => $key) {
		if ($atts[$attr] !== '' && in_array($atts[$attr], ['left','center','right'], true)) {
			$ov[$key] = $atts[$attr];
		}
	}

	$rendering = true;
	$content   = wpautop(do_shortcode($post->post_content));
	$rendering = false;

	[$toc] = stoc_build($content, $ov);
	if ( ! $toc ) return '';

	static $css_done = false;
	$css = $css_done ? '' : stoc_css();
	$css_done = true;
	return $css . $toc;
} );

// ── Uninstall ──────────────────────────────────────────────────────────────────
register_uninstall_hook( __FILE__, 'stoc_uninstall' );
function stoc_uninstall(): void {
	delete_option(STOC_OPT);
	delete_post_meta_by_key(STOC_META);
}
