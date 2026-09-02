<?php
/**
 * @package WordPress
 * @subpackage Timberland
 * @since Timberland 2.1.0
 */

use Twig\TwigFunction;

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once dirname( __DIR__ ) . '/theme/src/custom-functions.php';

use BarryTimberHelpers\BarryTimberHelpers;

BarryTimberHelpers::init();

Timber\Timber::init();
Timber::$dirname    = array( 'views', 'blocks' );
// Kept disabled for backwards compatibility with the existing starter templates.
// New themes should prefer escaped Twig output and use |raw only for trusted HTML.
Timber::$autoescape = false;

class Timberland extends Timber\Site {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'after_setup_theme', array( $this, 'theme_supports' ) );
		add_filter( 'timber/context', array( $this, 'add_to_context' ) );
		add_filter( 'timber/twig', array( $this, 'add_twig_functions' ) );
		add_action( 'block_categories_all', array( $this, 'block_categories_all' ) );
		add_action( 'acf/init', array( $this, 'acf_register_blocks' ) );

		parent::__construct();
	}

	public function add_twig_functions( $twig ) {
		$twig->addFunction( new TwigFunction( 'check_url_match', array( $this, 'check_url_match' ) ) );
		return $twig;
	}

	public function check_url_match( $string ) {
		$request_uri = $_SERVER['REQUEST_URI'] ?? '/';

		if ( $request_uri === $string ) {
			return true;
		}

		$host = $_SERVER['HTTP_HOST'] ?? '';
		if ( '' === $host ) {
			return false;
		}

		$scheme = is_ssl() ? 'https://' : 'http://';
		return $scheme . $host . $request_uri === $string;
	}

	public function add_to_context( $context ) {
		global $post;

		$context['site']       = $this;
		$context['menus']      = array();
		$context['pathname']   = $_SERVER['REQUEST_URI'] ?? '/';
		$context['options']    = function_exists( 'get_fields' ) ? ( get_fields( 'options' ) ?: array() ) : array();
		$context['header_cta'] = array();

		if ( $post instanceof WP_Post ) {
			$context['processed_content'] = $this->wrap_non_acf_blocks( $post->post_content );
		} else {
			$context['processed_content'] = '';
		}

		foreach ( wp_get_nav_menus() as $menu ) {
			$context['menus'][ $menu->slug ] = Timber::get_menu( $menu->term_id );
		}

		$header_menu = Timber::get_menu( 'header' );
		if ( $header_menu && ! empty( $header_menu->items ) ) {
			$context['header_cta'] = end( $header_menu->items );
		}

		// Require optional block-specific helper files once they are needed.
		foreach ( glob( __DIR__ . '/blocks/*/functions.php' ) as $file ) {
			require_once $file;
		}

		return $context;
	}

	public function theme_supports() {
		add_theme_support( 'automatic-feed-links' );
		add_theme_support(
			'html5',
			array(
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
			)
		);
		add_theme_support( 'menus' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'editor-styles' );
	}

	/**
	 * Wrap top-level non-ACF blocks without trying to parse nested Gutenberg
	 * comments with a regular expression. Raw/legacy HTML is left untouched.
	 */
	public function wrap_non_acf_blocks( $content ) {
		if ( ! is_string( $content ) || '' === trim( $content ) || ! has_blocks( $content ) ) {
			return $content;
		}

		$output = '';

		foreach ( parse_blocks( $content ) as $block ) {
			$block_name = $block['blockName'] ?? null;

			if ( empty( $block_name ) ) {
				$output .= $block['innerHTML'] ?? '';
				continue;
			}

			$rendered = render_block( $block );

			if ( 0 === strpos( $block_name, 'acf/' ) ) {
				$output .= $rendered;
				continue;
			}

			$output .= '<div class="custom-container">' . $rendered . '</div>';
		}

		return $output;
	}

	/**
	 * Preserve the boilerplate's lean frontend by default, but make the WordPress
	 * asset removals easy to disable on projects that need core/plugin styles.
	 */
	private function maybe_dequeue_wordpress_assets() {
		if ( ! apply_filters( 'timberland_strip_wordpress_assets', true ) ) {
			return;
		}

		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'wc-block-style' );
		wp_dequeue_script( 'jquery' );
		wp_dequeue_style( 'global-styles' );
	}

	private function get_vite_environment() {
		$config_path = dirname( get_template_directory() ) . '/config.json';

		if ( ! file_exists( $config_path ) ) {
			return 'production';
		}

		$config = json_decode( file_get_contents( $config_path ), true );
		return $config['vite']['environment'] ?? 'production';
	}

	private function get_vite_manifest() {
		$manifest_path = get_template_directory() . '/assets/dist/.vite/manifest.json';

		if ( ! file_exists( $manifest_path ) ) {
			return null;
		}

		$manifest = json_decode( file_get_contents( $manifest_path ), true );
		return is_array( $manifest ) ? $manifest : null;
	}

	public function enqueue_frontend_assets() {
		$this->maybe_dequeue_wordpress_assets();

		if ( 'development' === $this->get_vite_environment() ) {
			add_action( 'wp_head', array( $this, 'output_vite_dev_scripts' ) );
			return;
		}

		$manifest = $this->get_vite_manifest();
		if ( ! $manifest ) {
			return;
		}

		$dist_uri   = get_template_directory_uri() . '/assets/dist';
		$main_entry = $manifest['theme/assets/main.js'] ?? null;

		if ( ! is_array( $main_entry ) ) {
			return;
		}

		if ( ! empty( $main_entry['css'][0] ) ) {
			wp_enqueue_style( 'timberland-main', $dist_uri . '/' . $main_entry['css'][0], array(), null );
		}

		if ( ! empty( $main_entry['file'] ) ) {
			wp_enqueue_script(
				'timberland-main',
				$dist_uri . '/' . $main_entry['file'],
				array(),
				null,
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
		}
	}

	/**
	 * The editor always uses built assets. This keeps the admin independent from
	 * the Vite dev server and, unlike the old shared method, no longer exits early
	 * simply because WordPress is in the admin area.
	 */
	public function enqueue_editor_assets() {
		$manifest = $this->get_vite_manifest();
		if ( ! $manifest ) {
			return;
		}

		$dist_uri     = get_template_directory_uri() . '/assets/dist';
		$main_entry   = $manifest['theme/assets/main.js'] ?? null;
		$editor_entry = $manifest['theme/assets/styles/editor-style.css'] ?? null;

		if ( is_array( $main_entry ) && ! empty( $main_entry['css'][0] ) ) {
			wp_enqueue_style( 'timberland-editor-main', $dist_uri . '/' . $main_entry['css'][0], array(), null );
		}

		if ( is_array( $editor_entry ) && ! empty( $editor_entry['file'] ) ) {
			wp_enqueue_style( 'timberland-editor', $dist_uri . '/' . $editor_entry['file'], array(), null );
		}
	}

	public function output_vite_dev_scripts() {
		echo '<script type="module" crossorigin src="http://localhost:3000/@vite/client"></script>';
		echo '<script type="module" crossorigin src="http://localhost:3000/theme/assets/main.js"></script>';
	}

	public function block_categories_all( $categories ) {
		return array_merge(
			array(
				array(
					'slug'  => 'custom',
					'title' => __( 'Custom' ),
				),
			),
			$categories
		);
	}

	public function acf_register_blocks() {
		$blocks = array();

		foreach ( new DirectoryIterator( __DIR__ . '/blocks' ) as $dir ) {
			if ( $dir->isDot() ) {
				continue;
			}

			if ( file_exists( $dir->getPathname() . '/block.json' ) ) {
				$blocks[] = $dir->getPathname();
			}
		}

		asort( $blocks );

		foreach ( $blocks as $block ) {
			register_block_type( $block );
		}
	}
}

new Timberland();

/**
 * Shared ACF block renderer.
 */
function acf_block_render_callback( $block, $content ) {
	$context           = Timber::context();
	$context['post']   = Timber::get_post();
	$context['block']  = $block;
	$context['fields'] = get_fields();
	$block_name        = str_replace( 'acf/', '', $block['name'] ?? '' );

	if ( '' === $block_name ) {
		return;
	}

	Timber::render( 'blocks/' . $block_name . '/index.twig', $context );
}

// Remove the ACF frontend InnerBlocks wrapper.
function acf_should_wrap_innerblocks( $wrap, $name ) {
	return false;
}

add_filter( 'acf/blocks/wrap_frontend_innerblocks', 'acf_should_wrap_innerblocks', 10, 2 );
