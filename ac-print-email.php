<?php
/**
 * @license GPL-2.0-or-later
 */

/**
 * Plugin Name: AC Print + Email
 * Plugin URI:  https://absolutewebdev.com/
 * Description: Adds a clean Print link and a Craigslist-style Email dropdown (webmail + mailto + copy link) for posts/pages + archives. No tracking, no external services.
 * Version:     1.2.4
 * Author:      James Richardson
 * License:     GPLv2 or later
 * Text Domain: ac-print-email
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


if ( ! class_exists( 'ACPE_Print_Email_Plugin' ) ) {
	
	class ACPE_Print_Email_Plugin {
		
		private $acpe_rendered = false;
		// SVG's for mail client companies
		private $svg_cache = [];
		private $provider_icons = ['gmail','yahoo','outlook','aol','mailto','copy'];
	
	
	  /* ==========================================================
		 0) Constants / Utilities
		 ========================================================== */
	  const OPT_GROUP = 'acpe';      // settings_fields('acpe')
	  const MENU_SLUG = 'acpe';      // options page slug
	
	  public function __construct() {
	
		/* ==========================================================
		   1) Frontend assets
		   - CSS always
		   - Core JS always
		   - A11y JS depends on core (loads after)
		   ========================================================== */
		add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 20);
	
		/* ==========================================================
		   2) Frontend rendering hooks
		   ========================================================== */
		//add_filter('the_content',       [$this, 'inject_toolbar'], 12);
		
		$placement = (string) $this->opt('acpe_placement');
	
		// Only add the_content injection when in auto mode
		if ( $placement === 'auto' && ! apply_filters('acpe_disable_auto_inject', false) ) {
		  add_filter('the_content', [$this, 'inject_toolbar'], 12);
		}
		if ( $placement === 'auto' && ! apply_filters('acpe_disable_archive_holder', false) ) {
		  add_action('wp_footer', [$this, 'taxonomy_toolbar_footer_holder'], 20);
		}
	
		add_shortcode('ac_print_email', [$this, 'shortcode']);
	
		// Archive toolbar is output in the footer in a hidden holder, then relocated by JS.
		//add_action('wp_footer', [$this, 'taxonomy_toolbar_footer_holder'], 20);
	
		/* ==========================================================
		   3) Admin settings
		   ========================================================== */
		add_action('admin_init', [$this, 'register_settings']);
		add_action('admin_menu', [$this, 'add_settings_page']);
	
		// Load frontend CSS on the plugin settings screen so preview looks identical.
		add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_assets']);
		
		
	
		// Yoast: strip ACPE toolbar text from meta/OG/Twitter descriptions
		add_filter('wpseo_metadesc',            [$this, 'yoast_strip_acpe'], 999);
		add_filter('wpseo_opengraph_desc',      [$this, 'yoast_strip_acpe'], 999);
		add_filter('wpseo_twitter_description', [$this, 'yoast_strip_acpe'], 999);
		
		// Settings link on the Plugins screen
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [$this, 'plugin_action_links'] );
	
	
	
	  }
	   
		public function yoast_strip_acpe( $text ) {
		  if ( ! is_string($text) || $text === '' ) return $text;
		
		  // Remove the toolbar “word salad” Yoast sometimes picks up
		  $bad = [
			'Print | Email ▾',
			'Print | Email',
			'Print',
			'Email ▾',
			'Email',
			'Gmail',
			'Yahoo Mail',
			'Outlook / Hotmail',
			'AOL Mail',
			'Default mail app',
			'Copy link',
			'|',
			'▾',
		  ];
		
		  // Remove those tokens only when they appear as a leading block
		  // (keeps you from accidentally deleting real content later in the text)
		  $clean = trim($text);
		
		  // If it starts with the toolbar, strip the toolbar words out
		  if ( preg_match('/^\s*(Print\s*\|\s*Email|Print)\b/i', $clean) ) {
			$clean = str_replace($bad, ' ', $clean);
			$clean = trim(preg_replace('/\s{2,}/', ' ', $clean));
		  }
		
		  return $clean;
		}
	
	
		/* ==========================================================
		SVG output sanitization
		- We output inline SVG markup for icons.
		- Use wp_kses with an allowlist so Plugin Check is satisfied.
		========================================================== */
	
		private function acpe_allowed_svg_tags(): array {
		  return [
			'svg' => [
			  'xmlns' => true,
			  'xmlns:xlink' => true,
			  'viewbox' => true,              
			  'preserveaspectratio' => true,  
			  'class' => true,
			  'width' => true,
			  'height' => true,
			  'fill' => true,
			  'stroke' => true,
			  'stroke-width' => true,
			  'aria-hidden' => true,
			  'role' => true,
			  'focusable' => true,
			],
			'path' => [
			  'd' => true,
			  'fill' => true,
			  'stroke' => true,
			  'stroke-width' => true,
			],
			'g' => [
			  'fill' => true,
			  'stroke' => true,
			  'stroke-width' => true,
			],
			'title' => [],
		  ];
		}
	
		/* Render an SVG icon safely (wp_kses allowlist) */
	
		
		private function acpe_allowed_toolbar_html(): array {
		  return [
			'div'  => [
			  'class' => true,
			  'id' => true,
			  'style' => true,
			  'hidden' => true,
			  'role' => true,
			  'aria-label' => true,
			  'data-acpe' => true,
			  'data-email-subject' => true,
			  'data-email-body' => true,
			  'data-acpe-url' => true,
			],
			'a'    => [
			  'href' => true,
			  'class' => true,
			  'target' => true,
			  'rel' => true,
			  'role' => true,
			  'aria-label' => true,
			  'aria-haspopup' => true,
			  'aria-expanded' => true,
			],
			'span' => [
			  'class' => true,
			  'aria-hidden' => true,
			],
			'hr'   => [
			  'class' => true,
			  'aria-hidden' => true,
			],
			// SVG tags
			'svg'  => [
			  'xmlns' => true,
			  'xmlns:xlink' => true,
			  'viewbox' => true,
			  'preserveaspectRatio' => true,
			  'class' => true,
			  'width' => true,
			  'height' => true,
			  'fill' => true,
			  'stroke' => true,
			  'stroke-width' => true,
			  'aria-hidden' => true,
			  'role' => true,
			  'focusable' => true,
			],
			'path' => [
			  'd' => true,
			  'fill' => true,
			  'stroke' => true,
			  'stroke-width' => true,
			],
			'g' => [
			  'fill' => true,
			  'stroke' => true,
			  'stroke-width' => true,
			],
			'title' => [],
		  ];
		}
	
	  /* ==========================================================
		 Settings defaults
		 ========================================================== */
	  private function defaults() : array {
		return [
		  'acpe_enable_posts'    => 1,
		  'acpe_enable_pages'    => 1,
	
		  // Per-taxonomy toggles
		  'acpe_enable_category' => 0,
		  'acpe_enable_tag'      => 0,
	
		  // Position for auto-inject on singular
		  'acpe_position'        => 'before', // before | after
		  
		  // Selects auto inject toolbar or to use shortcode
		  'acpe_placement' => 'auto', // auto | shortcode
	
		  // Provider list
		  'acpe_providers'       => ['gmail','yahoo','outlook','aol','mailto','copy'],
	
		  // Icons
		  'acpe_icon_style'      => 'minimal', // minimal | outline | solid | text (text = no icons)
	
		  // Colors
		  'acpe_text_color'      => '#000000',
		  'acpe_menu_bg'         => '#ffffff',
	
		  // NEW: display mode
		  // both | icons | labels
		  'acpe_display_mode'    => 'both',
		];
	  }
	
	  private function opt(string $key) {
		$d = $this->defaults();
		return get_option($key, $d[$key] ?? null);
	  }
	
	  /* ==========================================================
		 1) Frontend assets
		 ========================================================== */
	  public function enqueue_assets() {
	
		// Assets are small; enqueue on relevant views.
		// Frontend only
		if ( is_admin() ) return;
	
		// Only enqueue where ACPE can appear
		if ( ! ( is_singular() || is_home() || is_archive() || is_search() ) ) return;
		
		
		$url  = plugin_dir_url(__FILE__);
		$path = plugin_dir_path(__FILE__);
	
		$css_file = $path . 'assets/ac-print-email.css';
		$js_core  = $path . 'assets/ac-print-email.js';
		$js_a11y  = $path . 'assets/ac-print-email-a11y.js';
	
		wp_enqueue_style(
		  'acpe-style',
		  $url . 'assets/ac-print-email.css',
		  [],
		  file_exists($css_file) ? filemtime($css_file) : '1.0'
		);
	
		// Inline colors from settings (theme-agnostic)
		$text = sanitize_hex_color( (string) $this->opt('acpe_text_color') );
		$bg   = sanitize_hex_color( (string) $this->opt('acpe_menu_bg') );
	
	
		if ($text || $bg) {
		  $inline = '';
			if ($text) {
			  $inline .= ".acpe-toolbar a{ color: {$text} !important; }\n";
			  $inline .= ".acpe-toolbar a:hover{ opacity: .85; }\n";
			}
			if ($bg) {
			  $inline .= ".acpe-menu{ background: {$bg} !important; }\n";
			}
	
		  wp_add_inline_style('acpe-style', $inline);
		}
	
		wp_enqueue_script(
		  'acpe-core',
		  $url . 'assets/ac-print-email.js',
		  [],
		  file_exists($js_core) ? filemtime($js_core) : '1.0',
		  true
		);
	
		wp_enqueue_script(
		  'acpe-a11y',
		  $url . 'assets/ac-print-email-a11y.js',
		  ['acpe-core'],
		  file_exists($js_a11y) ? filemtime($js_a11y) : '1.0',
		  true
		);
	  }
	
	  /* ==========================================================
		 2) Enable logic
		 ========================================================== */
	  private function enabled_on_this_view() : bool {
		$enabled_posts = (bool) $this->opt('acpe_enable_posts');
		$enabled_pages = (bool) $this->opt('acpe_enable_pages');
	
		if ( is_single() && $enabled_posts ) return true;
		if ( is_page()   && $enabled_pages ) return true;
	
		return false;
	  }
	
	  private function enabled_on_this_archive() : bool {
		// Per-taxonomy toggles
		if ( is_category() && (bool) $this->opt('acpe_enable_category') ) return true;
		if ( is_tag()      && (bool) $this->opt('acpe_enable_tag') ) return true;
		return false;
	  }
	
	  /* ==========================================================
		 3) Content injection (posts/pages)
		 ========================================================== */
		public function inject_toolbar($content) {
		  if ( is_admin() ) return $content;
		  if ( ! $this->enabled_on_this_view() ) return $content;
		
		  // If user already placed shortcode manually, don't auto-inject.
		  if ( has_shortcode($content, 'ac_print_email') ) return $content;
		
		  // Prevent double-inject if the_content runs more than once
		  if ( strpos($content, 'data-acpe="1"') !== false ) return $content;
		
		  $position = (string) $this->opt('acpe_position'); // before|after
		  $toolbar  = $this->render_toolbar(get_the_title(), get_permalink());
		
		  return ($position === 'after') ? ($content . $toolbar) : ($toolbar . $content);
		}
	
	
	  public function shortcode($atts = []) {
		if ( is_admin() ) return '';
		// Allow shortcode anywhere (even if auto-inject disabled)
		return $this->render_toolbar(get_the_title(), get_permalink());
	  }
	
	  /* ==========================================================
		 4) Toolbar renderer (shared)
		 - Used for single posts/pages and archive toolbars
		 ========================================================== */
	  private function render_toolbar(string $title, string $url) : string {
	
		  // Prevent duplicates (template shortcode + content shortcode + archive holder, etc.)
		  //if ( $this->acpe_rendered ) return '';
		  //$this->acpe_rendered = true;
	
		$providers = (array) $this->opt('acpe_providers');
		$providers = array_map('sanitize_key', $providers);
	
		// NEW: display mode class
		$mode = sanitize_key((string) $this->opt('acpe_display_mode'));
		if (!in_array($mode, ['both','icons','labels'], true)) $mode = 'both';
		$mode_class = 'acpe-mode-' . $mode;
	
		ob_start(); ?>
	
	
		<div class="acpe-toolbar <?php echo esc_attr($mode_class); ?>" data-acpe="1"
		  data-email-subject="<?php echo esc_attr($title); ?>"
		  data-email-body="<?php echo esc_url($url); ?>"
		  data-acpe-url="<?php echo esc_url($url); ?>">
	
		  <a class="acpe-print" href="#" aria-label="Print this page">
			<span class="acpe-icon" aria-hidden="true">
			  <?php echo wp_kses( $this->svg_icon('print'), $this->acpe_allowed_svg_tags() ); ?>
			</span>
	
			<span class="acpe-label">Print</span>
		  </a>
	
		  <span class="acpe-sep" aria-hidden="true">|</span>
	
		  <a class="acpe-email-toggle" href="#" aria-haspopup="menu" aria-expanded="false">
			<span class="acpe-icon" aria-hidden="true">
			  <?php echo wp_kses( $this->svg_icon('email'), $this->acpe_allowed_svg_tags() ); ?>
			</span>
	
			<span class="acpe-label">Email</span>
			<span class="acpe-caret" aria-hidden="true">▾</span>
		  </a>
	
		 <div class="acpe-menu" hidden role="menu" aria-label="Email options">
		  <?php if (in_array('gmail',$providers,true)) : ?>
			<a href="#" class="acpe-gmail" target="_blank" rel="noopener noreferrer" role="menuitem">
			  <span class="acpe-icon" aria-hidden="true">
			  <?php echo wp_kses( $this->svg_icon('gmail'), $this->acpe_allowed_svg_tags() ); ?>
			</span>
	
			  <span class="acpe-label">Gmail</span>
			</a>
		  <?php endif; ?>
		
		  <?php if (in_array('yahoo',$providers,true)) : ?>
			<a href="#" class="acpe-yahoo" target="_blank" rel="noopener noreferrer" role="menuitem">
			  <span class="acpe-icon" aria-hidden="true">
			  <?php echo wp_kses( $this->svg_icon('yahoo'), $this->acpe_allowed_svg_tags() ); ?>
			</span>
	
			  <span class="acpe-label">Yahoo Mail</span>
			</a>
		  <?php endif; ?>
		
		  <?php if (in_array('outlook',$providers,true)) : ?>
			<a href="#" class="acpe-outlook" target="_blank" rel="noopener noreferrer" role="menuitem">
			  <span class="acpe-icon" aria-hidden="true">
			  <?php echo wp_kses( $this->svg_icon('outlook'), $this->acpe_allowed_svg_tags() ); ?>
			</span>
	
			  <span class="acpe-label">Outlook / Hotmail</span>
			</a>
		  <?php endif; ?>
		  
			<?php if (in_array('aol',$providers,true)) : ?>
			<a href="#" class="acpe-aol" target="_blank" rel="noopener noreferrer" role="menuitem">
			  <span class="acpe-icon" aria-hidden="true">
			  <?php echo wp_kses( $this->svg_icon('aol'), $this->acpe_allowed_svg_tags() ); ?>
			</span>
	
			  <span class="acpe-label">AOL Mail</span>
			</a>
		  <?php endif; ?>
		
		  <?php if (count(array_intersect($providers,['gmail','yahoo','outlook','aol'])) > 0
				 && (in_array('mailto',$providers,true) || in_array('copy',$providers,true))) : ?>
			<hr class="acpe-hr" aria-hidden="true">
		  <?php endif; ?>
		
		  <?php if (in_array('mailto',$providers,true)) : ?>
			<a href="#" class="acpe-mailto" role="menuitem">
			  <span class="acpe-icon" aria-hidden="true">
			  <?php echo wp_kses( $this->svg_icon('mailto'), $this->acpe_allowed_svg_tags() ); ?>
			</span>
	
			  <span class="acpe-label">Default mail app</span>
			</a>
		  <?php endif; ?>
		
		  <?php if (in_array('copy',$providers,true)) : ?>
			<a href="#" class="acpe-copy" role="menuitem">
			  <span class="acpe-icon" aria-hidden="true">
			  <?php echo wp_kses( $this->svg_icon('copy'), $this->acpe_allowed_svg_tags() ); ?>
			</span>
	
			  <span class="acpe-label">Copy link</span>
			</a>
		  <?php endif; ?>
		  
		 
		  </div>
		
		
		</div>
		<?php
		return wp_kses( ob_get_clean(), $this->acpe_allowed_toolbar_html() );
	
	  }
	
	  /* ==========================================================
		 5) Archive toolbar holder (category + tag)
		 - SAFE method: output once in wp_footer hidden container
		 - JS relocates into the archive header area
		 ========================================================== */
	  public function taxonomy_toolbar_footer_holder() {
		if ( is_admin() ) return;
		if ( ! $this->enabled_on_this_archive() ) return;
	
		  // Prevent duplicate archive holder output
		  //if ( $this->acpe_rendered ) return;
		  //$this->acpe_rendered = true;
	
		$obj = get_queried_object();
		$title = '';
		$url   = '';
	
		if ( is_category() ) {
		  $title = single_cat_title('', false);
		  $url   = get_term_link($obj);
		} elseif ( is_tag() ) {
		  $title = single_tag_title('', false);
		  $url   = get_term_link($obj);
		} else {
		  return;
		}
	
		if ( is_wp_error($url) ) $url = home_url('/');
		$url = esc_url_raw( $url );
	
		echo wp_kses(
		  '<div id="acpe-archive-toolbar-holder" style="display:none">' .
		  $this->render_toolbar($title, $url) .
		  '</div>',
		  $this->acpe_allowed_toolbar_html()
		);
	
	
	
	  }
	
	  /* ==========================================================
		 6) Icons (SVG)
		 ========================================================== */
		 
	  // Load provider logo SVGs from /assets/icons when available (fallback to built-in icons)
	  private function svg_file_icon(string $name): string {
		$name = sanitize_key($name);
	
		if (isset($this->svg_cache[$name])) {
		  return $this->svg_cache[$name];
		}
	
		$file = plugin_dir_path(__FILE__) . 'assets/icons/' . $name . '.svg';
		if (!file_exists($file)) {
		  $this->svg_cache[$name] = '';
		  return '';
		}
	
		$svg = file_get_contents($file);
		if (!$svg) {
		  $this->svg_cache[$name] = '';
		  return '';
		}
	
		$svg = preg_replace('#<(script|foreignObject)\b[^>]*>(.*?)</\1>#is', '', $svg);
	
		if (strpos($svg, 'class=') === false) {
		  $svg = preg_replace('/<svg\b/', '<svg class="acpe-svg"', $svg, 1);
		} elseif (strpos($svg, 'acpe-svg') === false) {
		  $svg = preg_replace('/<svg\b([^>]*?)\bclass=("|\')([^"\']*)\2/', '<svg$1 class=$2$3 acpe-svg$2', $svg, 1);
		}
	
		$this->svg_cache[$name] = $svg;
		return $svg;
	  }
	  
		private function svg_icon(string $name): string {
		  $name = sanitize_key($name); 
		  $style = sanitize_key((string) $this->opt('acpe_icon_style'));
		  if (!in_array($style, ['minimal','outline','solid'], true)) $style = 'minimal';
		
		  // Provider icons: prefer bundled logo SVG files (assets/icons/*.svg)
		  if (in_array($name, $this->provider_icons, true)) {
			$file_svg = $this->svg_file_icon($name);
			if ($file_svg !== '') return $file_svg;
			$style = 'minimal';
		  }
	
		$svgs = [
		  'minimal' => [
			'print' => '<svg viewbox="0 0 24 24" class="acpe-svg" aria-hidden="true"><path d="M7 7V3h10v4H7zm12 4v6h-3v4H8v-4H5v-6a3 3 0 0 1 3-3h8a3 3 0 0 1 3 3zm-5 10v-6H10v6h4z"/></svg>',
			'email' => '<svg viewbox="0 0 24 24" class="acpe-svg" aria-hidden="true"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>',
			'gmail'  => '<svg viewbox="0 0 24 24" class="acpe-svg" aria-hidden="true"><path d="M20 6v12H4V6l8 6 8-6zm0-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/></svg>',
			'yahoo'  => '<svg viewbox="0 0 24 24" class="acpe-svg" aria-hidden="true"><path d="M6 4h3l3 6 3-6h3l-4.5 8v8h-3v-8L6 4z"/></svg>',
			'outlook'=> '<svg viewbox="0 0 24 24" class="acpe-svg" aria-hidden="true"><path d="M4 6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2l-7 4-7-4V6zm0 4 7 4 7-4v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8z"/></svg>',
			'aol'    => '<svg viewbox="0 0 24 24" class="acpe-svg" aria-hidden="true"><path d="M12 4 4 20h3l1.6-3.5h6.8L17 20h3L12 4zm-2 10 2-4.5 2 4.5h-4z"/></svg>',
			'mailto' => '<svg viewbox="0 0 24 24" class="acpe-svg" aria-hidden="true"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>',
			'copy'   => '<svg viewbox="0 0 24 24" class="acpe-svg" aria-hidden="true"><path d="M8 8h10v12H8V8zm-2 0V6a2 2 0 0 1 2-2h8v2H8v2H6z"/></svg>',
	
		  ],
		  'outline' => [
			'print' => '<svg viewbox="0 0 24 24" class="acpe-svg" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 8V4h10v4"/><path d="M6 10h12a3 3 0 0 1 3 3v4h-4v3H7v-3H3v-4a3 3 0 0 1 3-3z"/><path d="M8 17h8v3H8z"/></svg>',
			'email' => '<svg viewbox="0 0 24 24" class="acpe-svg" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>',
		  ],
		  'solid' => [
			'print' => '<svg viewbox="0 0 24 24" class="acpe-svg" aria-hidden="true"><path d="M6 9V3h12v6H6zm13 2a3 3 0 0 0-3-3H8a3 3 0 0 0-3 3v5h3v5h8v-5h3v-5zm-4 9H9v-5h6v5z"/></svg>',
			'email' => '<svg viewbox="0 0 24 24" class="acpe-svg" aria-hidden="true"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm-8 9L4 8V6l8 5 8-5v2l-8 5z"/></svg>',
		  ],
		];
	
		return $svgs[$style][$name] ?? '';
	  }
	
	  /* ==========================================================
		 7) Settings registration
		 ========================================================== */
		public function register_settings() {
		
		  register_setting(self::OPT_GROUP, 'acpe_enable_posts', [
			'sanitize_callback' => [$this, 'sanitize_checkbox'],
			'default' => 1,
		  ]);
		
		  register_setting(self::OPT_GROUP, 'acpe_enable_pages', [
			'sanitize_callback' => [$this, 'sanitize_checkbox'],
			'default' => 1,
		  ]);
		
		  register_setting(self::OPT_GROUP, 'acpe_enable_category', [
			'sanitize_callback' => [$this, 'sanitize_checkbox'],
			'default' => 0,
		  ]);
		
		  register_setting(self::OPT_GROUP, 'acpe_enable_tag', [
			'sanitize_callback' => [$this, 'sanitize_checkbox'],
			'default' => 0,
		  ]);
		
		  register_setting(self::OPT_GROUP, 'acpe_position', [
			'sanitize_callback' => [$this, 'sanitize_position'],
			'default' => 'before',
		  ]);
		
		  register_setting(self::OPT_GROUP, 'acpe_providers', [
			'sanitize_callback' => [$this, 'sanitize_providers'],
			'default' => ['gmail','yahoo','outlook','aol','mailto','copy'],
		  ]);
		
		  register_setting(self::OPT_GROUP, 'acpe_icon_style', [
			'sanitize_callback' => [$this, 'sanitize_icon_style'],
			'default' => 'minimal',
		  ]);
		
		  register_setting(self::OPT_GROUP, 'acpe_display_mode', [
			'sanitize_callback' => [$this, 'sanitize_display_mode'],
			'default' => 'both',
		  ]);
		
		  register_setting(self::OPT_GROUP, 'acpe_text_color', [
			'sanitize_callback' => 'sanitize_hex_color',
			'default' => '#000000',
		  ]);
		
		  register_setting(self::OPT_GROUP, 'acpe_menu_bg', [
			'sanitize_callback' => 'sanitize_hex_color',
			'default' => '#ffffff',
		  ]);
		  
		  register_setting(self::OPT_GROUP, 'acpe_placement', [
			'sanitize_callback' => [$this, 'sanitize_placement'],
			'default' => 'auto',
		  ]);
	
		}
	
	
	  /* ==========================================================
		 8) Settings page
		 ========================================================== */
	  public function add_settings_page() {
		add_options_page(
		  'AC Print + Email',
		  'AC Print + Email',
		  'manage_options',
		  self::MENU_SLUG,
		  [$this, 'render_settings_page']
		);
	  }
	
		public function admin_enqueue_assets($hook) {
		  // Only on our settings page
		  if ($hook !== 'settings_page_' . self::MENU_SLUG) return;
		
		  // Frontend CSS so preview matches
		  wp_enqueue_style(
			'acpe-style-admin-preview',
			plugin_dir_url(__FILE__) . 'assets/ac-print-email.css',
			[],
			'1.0'
		  );
		// Apply the same color overrides used on the frontend (admin preview only)
		$text = sanitize_hex_color( (string) $this->opt('acpe_text_color') );
		$bg   = sanitize_hex_color( (string) $this->opt('acpe_menu_bg') );
		
		$inline = '';
		
		if ( $text ) {
		  $inline .= ".acpe-toolbar a{ color: {$text} !important; }\n";
		  $inline .= ".acpe-toolbar a:hover{ opacity: .85; }\n";
		}
		
		if ( $bg ) {
		  $inline .= "#acpe-preview .acpe-menu{ background: {$bg} !important; background-color: {$bg} !important; }\n";
		}
		
		// Force the dropdown visible in the admin preview (so you can see the color)
		$inline .= "#acpe-preview .acpe-menu[hidden]{ display:block !important; }\n";
		$inline .= "#acpe-preview .acpe-menu{ position: static !important; margin-top: .35rem !important; }\n";
		
		if ( $inline ) {
		  wp_add_inline_style( 'acpe-style-admin-preview', $inline );
		}
	
		
		// Force the dropdown visible in the admin preview (so you can see the color)
		$inline .= "#acpe-preview .acpe-menu[hidden]{ display:block !important; }\n";
		$inline .= "#acpe-preview .acpe-menu{ position: static !important; margin-top: .35rem !important; }\n";
	
	
		
		  //  WordPress color picker
		  wp_enqueue_style('wp-color-picker');
		  wp_enqueue_script('wp-color-picker');
		
		  //  Init color pickers (no extra file needed)
		  $js = "jQuery(function($){ $('.acpe-color-field').wpColorPicker(); });";
		  wp_add_inline_script('wp-color-picker', $js);
		}
	
	
	  public function render_settings_page() {
		if ( ! current_user_can('manage_options') ) return;
	
		$providers = (array) $this->opt('acpe_providers');
		$providers = array_map('sanitize_key', $providers);
	
		$display_mode = sanitize_key((string) $this->opt('acpe_display_mode'));
		if (!in_array($display_mode, ['both','icons','labels'], true)) $display_mode = 'both';
	
		?>
		<div class="wrap">
		  <h1>AC Print + Email</h1>
	
		  <form method="post" action="options.php">
			<?php settings_fields(self::OPT_GROUP); ?>
	
			<table class="form-table" role="presentation">
	
			  <!-- Enable on posts/pages -->
			  <tr>
				<th scope="row">Enable on Posts</th>
				<td><label>
				  <input type="checkbox" name="acpe_enable_posts" value="1" <?php checked($this->opt('acpe_enable_posts'),1); ?>>
				  Yes
				</label></td>
			  </tr>
	
			  <tr>
				<th scope="row">Enable on Pages</th>
				<td><label>
				  <input type="checkbox" name="acpe_enable_pages" value="1" <?php checked($this->opt('acpe_enable_pages'),1); ?>>
				  Yes
				</label></td>
			  </tr>
	
			  <!-- Per-taxonomy toggles -->
			  <tr>
				<th scope="row">Enable on Category pages</th>
				<td><label>
				  <input type="checkbox" name="acpe_enable_category" value="1" <?php checked($this->opt('acpe_enable_category'),1); ?>>
				  Yes (show toolbar once near the top of category archives)
				</label></td>
			  </tr>
	
			  <tr>
				<th scope="row">Enable on Tag pages</th>
				<td><label>
				  <input type="checkbox" name="acpe_enable_tag" value="1" <?php checked($this->opt('acpe_enable_tag'),1); ?>>
				  Yes (show toolbar once near the top of tag archives)
				</label></td>
			  </tr>
	
			  <!-- Auto-inject position -->
			  <tr>
				<th scope="row">Position</th>
				<td>
				  <select name="acpe_position">
					<option value="before" <?php selected($this->opt('acpe_position'),'before'); ?>>Before content</option>
					<option value="after"  <?php selected($this->opt('acpe_position'),'after');  ?>>After content</option>
				  </select>
				</td>
			  </tr>
			  <tr>
				<th scope="row">Placement</th>
			   <td>
				  <select name="acpe_placement">
					<option value="auto" <?php selected($this->opt('acpe_placement'),'auto'); ?>>Auto (inject into content)</option>
					<option value="shortcode" <?php selected($this->opt('acpe_placement'),'shortcode'); ?>>Shortcode only</option>
				  </select>
				  <p class="description">Use “Shortcode only” for full control of placement in older/custom themes.</p>
				  <p class="description">Position applies only when Placement is Auto.</p>
				</td>
			  </tr>
	
			  <!-- NEW: display mode -->
			  <tr>
				<th scope="row">Show labels / icons</th>
				<td>
				  <select name="acpe_display_mode">
					<option value="both"   <?php selected($display_mode,'both'); ?>>Icons + labels</option>
					<option value="icons"  <?php selected($display_mode,'icons'); ?>>Icons only</option>
					<option value="labels" <?php selected($display_mode,'labels'); ?>>Labels only</option>
				  </select>
				  <p class="description">Controls whether the toolbar shows icons, labels, or both.</p>
				</td>
			  </tr>
	
			  <!-- Colors -->
			  <tr>
				<th scope="row">Toolbar text color</th>
				<td>
				<input type="text"
					   name="acpe_text_color"
					   value="<?php echo esc_attr($this->opt('acpe_text_color')); ?>"
					   class="acpe-color-field"
					   data-default-color="#000000" />
				  <p class="description">Example: #000000</p>
				</td>
			  </tr>
	
			  <tr>
				<th scope="row">Dropdown background color</th>
				<td>
				<input type="text"
					   name="acpe_menu_bg"
					   value="<?php echo esc_attr($this->opt('acpe_menu_bg')); ?>"
					   class="acpe-color-field"
					   data-default-color="#ffffff" />
				  <p class="description">Example: #ffffff</p>
				</td>
			  </tr>
	
			  <!-- Providers -->
			  <tr>
				<th scope="row">Email options</th>
				<td>
				  <?php
				  $all = [
					'gmail'   => 'Gmail',
					'yahoo'   => 'Yahoo Mail',
					'outlook' => 'Outlook / Hotmail',
					'aol'     => 'AOL Mail',
					'mailto'  => 'Default mail app',
					'copy'    => 'Copy link',
				  ];
				  foreach ($all as $key => $label) : ?>
					<label style="display:block;margin:.15rem 0;">
					  <input type="checkbox" name="acpe_providers[]" value="<?php echo esc_attr($key); ?>"
							 <?php checked(in_array($key,$providers,true)); ?>>
					  <?php echo esc_html($label); ?>
					</label>
				  <?php endforeach; ?>
				  <p class="description">Webmail options open a compose window in a new tab. Copy link is the universal fallback.</p>
				</td>
			  </tr>
	
			</table>
	
			<?php submit_button(); ?>
		  </form>
	
		  <!-- Preview panel -->
		  <hr>
		  <h2>Preview</h2>
		  <p class="description">This is a live preview of your current settings.</p>
		  <div id="acpe-preview" style="padding:12px; background:#fff; border:1px solid #ddd; border-radius:10px; display:inline-block;">
			<?php
				$this->acpe_rendered = false; // allow preview to render even if toolbar rendered elsewhere
				echo wp_kses(
				  $this->render_toolbar('Example Post Title', esc_url( home_url('/example-post/') )),
				  $this->acpe_allowed_toolbar_html()
				);
			?>
		  </div>
	
		  <h2 style="margin-top:24px;">Shortcode</h2>
		  <p>Use <code>[ac_print_email]</code> to place the toolbar manually.</p>
	
		  <!-- ======================================================
			   Deprecated / removed approaches (kept as history)
			   - loop_start echo
			   - archive-title filter injection
			   These caused broken markup in some themes.
			   ====================================================== -->
		</div>
		<?php
	  }
	
		/* =========================== 
		Settings link on Plugins page 
		==============================*/
		public function plugin_action_links( $links ) {
		  $url = admin_url( 'options-general.php?page=' . self::MENU_SLUG );
		  $settings = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'ac-print-email' ) . '</a>';
		  array_unshift( $links, $settings );
		  return $links;
		}
		
		
		/* =========================== 
		Sanitizers  
		==============================*/
		public function sanitize_checkbox($value) {
		  return !empty($value) ? 1 : 0;
		}
		
		public function sanitize_position($value) {
		  $value = sanitize_key($value);
		  return in_array($value, ['before','after'], true) ? $value : 'before';
		}
		
		public function sanitize_icon_style($value) {
		  $value = sanitize_key($value);
		  return in_array($value, ['minimal','outline','solid'], true) ? $value : 'minimal';
		}
		
		public function sanitize_display_mode($value) {
		  $value = sanitize_key($value);
		  return in_array($value, ['both','icons','labels'], true) ? $value : 'both';
		}
		
		public function sanitize_providers($value) {
		  $allowed = ['gmail','yahoo','outlook','aol','mailto','copy'];
		  $value = is_array($value) ? $value : [];
		  $value = array_map('sanitize_key', $value);
		  $value = array_values(array_intersect($value, $allowed));
		  return $value;
		}
		
		public function sanitize_placement($v) {
		  $v = sanitize_key($v);
		  return in_array($v, ['auto','shortcode'], true) ? $v : 'auto';
		}
	
	
	}
}



if ( class_exists( 'ACPE_Print_Email_Plugin' ) ) {
    new ACPE_Print_Email_Plugin();
}



