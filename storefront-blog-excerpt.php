<?php
/**
 * Plugin Name:			Storefront Blog Excerpt
 * Plugin URI:			http://wordpress.org/plugins/storefront-blog-excerpt/
 * Description:			A boilerplate plugin for creating Storefront extensions.
 * Version:				1.0.0
 * Author:				WooAssist
 * Author URI:			http://wooassist.com
 * Requires at least:	4.0.0
 * Tested up to:		4.0.0
 *
 * Text Domain: storefront-blog-excerpt
 * Domain Path: /languages/
 *
 * @package Storefront_Blog_Excerpt
 * @category Core
 * @author WooAssist
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Returns the main instance of Storefront_Blog_Excerpt to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Storefront_Blog_Excerpt
 */
function Storefront_Blog_Excerpt() {
	return Storefront_Blog_Excerpt::instance();
} // End Storefront_Blog_Excerpt()

Storefront_Blog_Excerpt();

/**
 * Main Storefront_Blog_Excerpt Class
 *
 * @class Storefront_Blog_Excerpt
 * @version	1.0.0
 * @since 1.0.0
 * @package	Storefront_Blog_Excerpt
 */
final class Storefront_Blog_Excerpt {
	/**
	 * Storefront_Blog_Excerpt The single instance of Storefront_Blog_Excerpt.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	// Admin - Start
	/**
	 * The admin object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct() {
		$this->token 			= 'storefront-blog-excerpt';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->version 			= '1.0.0';

		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'init', array( $this, 'setup' ) );
	}

	/**
	 * Main Storefront_Blog_Excerpt Instance
	 *
	 * Ensures only one instance of Storefront_Blog_Excerpt is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Storefront_Blog_Excerpt()
	 * @return Main Storefront_Blog_Excerpt instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'storefront-blog-excerpt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	}

	/**
	 * Installation.
	 * Runs on activation. Logs the version number and assigns a notice message to a WordPress option.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install() {
		$this->_log_version_number();

		if( 'storefront' != basename( TEMPLATEPATH ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( 'Sorry, you can&rsquo;t activate this plugin unless you have installed the Storefront theme.' );
		}

		// get theme customizer url
		$url = admin_url() . 'customize.php?';
		$url .= 'url=' . urlencode( site_url() . '?storefront-customizer=true' ) ;
		$url .= '&return=' . urlencode( admin_url() . 'plugins.php' );
		$url .= '&storefront-customizer=true';

		$notices 		= get_option( 'activation_notice', array() );
		$notices[]		= sprintf( __( '%sThanks for installing the Storefront Blog Excerpt extension. To get started, visit the %sCustomizer%s.%s %sOpen the Customizer%s', 'storefront-blog-excerpt' ), '<p>', '<a href="' . esc_url( $url ) . '">', '</a>', '</p>', '<p><a href="' . esc_url( $url ) . '" class="button button-primary">', '</a></p>' );

		update_option( 'activation_notice', $notices );
	}

	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number() {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	}

	/**
	 * Setup all the things.
	 * Only executes if Storefront or a child theme using Storefront as a parent is active and the extension specific filter returns true.
	 * Child themes can disable this extension using the Storefront_Blog_Excerpt_enabled filter
	 * @return void
	 */
	public function setup() {
		$theme = wp_get_theme();

		if ( 'Storefront' == $theme->name || 'storefront' == $theme->template && apply_filters( 'Storefront_Blog_Excerpt_supported', true ) ) {
			add_action( 'customize_register', array( $this, 'customize_register' ) );
			add_filter( 'body_class', array( $this, 'body_class' ) );
			add_action( 'admin_notices', array( $this, 'customizer_notice' ) );

			// apply the excerpt replacing the content block on the archive page
			remove_action( 'storefront_loop_post',		'storefront_post_content',			30 );
			add_action( 'storefront_loop_post',			array( $this, 'post_excerpt' ),		30 );

			// Hide the 'More' section in the customizer
			add_filter( 'storefront_customizer_more', '__return_false' );
		}
	}

	/**
	 * Admin notice
	 * Checks the notice setup in install(). If it exists display it then delete the option so it's not displayed again.
	 * @since   1.0.0
	 * @return  void
	 */
	public function customizer_notice() {
		$notices = get_option( 'activation_notice' );

		if ( $notices = get_option( 'activation_notice' ) ) {

			foreach ( $notices as $notice ) {
				echo '<div class="updated">' . $notice . '</div>';
			}

			delete_option( 'activation_notice' );
		}
	}

	/**
	 * Customizer Controls and settings
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function customize_register( $wp_customize ) {

		/**
		 * Add new section
		 */
		$wp_customize->add_section(
			'woa_sf_blog_excerpt_customizer_section' , array(
			    'title'      => __( 'Blog Excerpt', 'woa-sf-blog-excerpt' ),
			    'priority'   => 55,
			)
		);

		/**
		 * Add new settings
		 */
		$wp_customize->add_setting(
			'woa_sf_blog_excerpt_word_count',
			array(
				'default' => 55,
			)
		);
		$wp_customize->add_setting(
			'woa_sf_blog_excerpt_end',
			array(
				'default' => '&hellip;',
			)
		);
		$wp_customize->add_setting(
			'woa_sf_blog_excerpt_button_text',
			array(
				'default' => 'Read more',
			)
		);
		$wp_customize->add_setting(
			'woa_sf_blog_excerpt_image_size',
			array(
				'default' => 'full',
			)
		);

		/**
		 * Add controls and apply respective settings and hook on section
		 */
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'woa_sf_blog_excerpt_word_count',
				array(
					'label'      => __( 'Excerpt Word Count', 'woa-sf-blog-excerpt' ),
					'type'		 =>	'text',
					'section'    => 'woa_sf_blog_excerpt_customizer_section',
					'settings'   => 'woa_sf_blog_excerpt_word_count',
				)
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'woa_sf_blog_excerpt_end',
				array(
					'label'      => __( 'Excerpt Word End', 'woa-sf-blog-excerpt' ),
					'type'		 =>	'text',
					'section'    => 'woa_sf_blog_excerpt_customizer_section',
					'settings'   => 'woa_sf_blog_excerpt_end',
				)
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'woa_sf_blog_excerpt_button_text',
				array(
					'label'      => __( 'Read more button text', 'woa-sf-blog-excerpt' ),
					'type'		 =>	'text',
					'section'    => 'woa_sf_blog_excerpt_customizer_section',
					'settings'   => 'woa_sf_blog_excerpt_button_text',
				)
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'woa_sf_blog_excerpt_image_size',
				array(
					'label'      => __( 'Featured Image Size', 'woa-sf-blog-excerpt' ),
					'type'		 =>	'select',
					'section'    => 'woa_sf_blog_excerpt_customizer_section',
					'settings'   => 'woa_sf_blog_excerpt_image_size',
					'choices'	 => array(
						'full'			=>	__( 'Full', 'woa-sf-blog-excerpt' ),
						'large'			=>	__( 'Large', 'woa-sf-blog-excerpt' ),
						'medium'		=>	__( 'Medium', 'woa-sf-blog-excerpt' ),
						'thumbnail'		=>	__( 'thumbnail', 'woa-sf-blog-excerpt' ),
					)
				)
			)
		);
	}

	/**
	 * Display the post content with a link to the single post
	 * @since 1.0.0
	 */
	public function post_excerpt() {
		?>
		<div class="entry-content" itemprop="articleBody">
		<?php
		if ( has_post_thumbnail() ) {
			$thumb_size = get_theme_mod( 'woa_sf_blog_excerpt_image_size', 'full' );
			$img_class = ( $thumb_size == 'thumbnail' ) ? apply_filters( 'woa_sf_blog_excerpt_image_float', 'alignleft' ) : '';
			the_post_thumbnail( $thumb_size, array( 'itemprop' => 'image', 'class' => "attachment-$thumb_size $img_class" ) );
		}

		$content = ( has_excerpt( get_the_ID() ) ) ? get_the_excerpt() : get_the_content();
		?>
		<p><?php echo wp_trim_words( $content, get_theme_mod( 'woa_sf_blog_excerpt_word_count', 55 ), get_theme_mod( 'woa_sf_blog_excerpt_end', '&hellip;' ) ); ?></p>

		<p class="read-more"><a class="button" href="<?php the_permalink(); ?>"><?php echo get_theme_mod( 'woa_sf_blog_excerpt_button_text', 'Read more' ); ?></a></p>
		</div><!-- .entry-content -->
		<?php
	}

	/**
	 * Storefront Blog Excerpt Body Class
	 * Adds a class based on the extension name and any relevant settings.
	 */
	public function body_class( $classes ) {
		$classes[] = 'storefront-blog-excerpt-active';

		return $classes;
	}
} // End Class
