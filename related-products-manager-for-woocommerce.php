<?php
/*
Plugin Name: Related Products Manager for WooCommerce
Plugin URI: https://prowcplugins.com/downloads/related-products-manager-for-woocommerce/
Description: Manage related products in WooCommerce, beautifully.
Version: 1.6
Author: ProWCPlugins
Author URI: https://prowcplugins.com
Text Domain: related-products-manager-woocommerce
Domain Path: /langs
Copyright: � 2024 ProWCPlugins.com
WC tested up to: 9.0.2
License: GNU General Public License v3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
define('RPMW_FILE', __FILE__);
define('RPMW_TEXTDOMAIN', 'related-products-manager-woocommerce');
define('RPMW_DIR', plugin_dir_path(RPMW_FILE));
define('RPMW_URL', plugins_url('/', RPMW_FILE));

if ( ! class_exists( 'ProWC_Related_Products_Manager' ) ) :

	/**
	 * Main ProWC_Related_Products_Manager Class
	 *
	 * @class   ProWC_Related_Products_Manager
	 * @version 1.4.0
	 * @since   1.0.0
	 */
	final class ProWC_Related_Products_Manager {

		/**
		 * Plugin version.
		 *
		 * @var   string
		 * @since 1.0.0
		 */
		public $version = '1.5.9';

		/**
		 * @var   ProWC_Related_Products_Manager The single instance of the class
		 * @since 1.0.0
		 */
		protected static $_instance = null;

		/**
		 * Main ProWC_Related_Products_Manager Instance
		 *
		 * Ensures only one instance of ProWC_Related_Products_Manager is loaded or can be loaded.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @static
		 * @return  ProWC_Related_Products_Manager - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * ProWC_Related_Products_Manager Constructor.
		 *
		 * @version 1.4.0
		 * @since   1.0.0
		 * @access  public
		 */
		public $core;
		public function __construct() {

			// Set up localisation
			load_plugin_textdomain('related-products-manager-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
		
			$this->hooks();
			
			// Elementor Load File
			if (did_action('elementor/loaded')){
				require_once('widgets/elementor-helper.php'); //Elementor Add widget
				require_once('widgets/functions-elementor-related-product.php'); //Elementor Function file for Category and Tag 
			}
			// Core
			$this->core = require_once( 'includes/class-prowc-related-products-manager-core.php' );

			// Admin
			if ( is_admin() ) {
				$this->admin();
			}
		}

		/*
		* Check for Elementor
		*/
		public function related_products_manager_plugin_load() {
			if (did_action('elementor/loaded')){
				require_once( 'widgets/related-product-manager-elementor-widget.php' );
				return;
			}
		}

		/**
		 * Load Wb-Bakery widgets files
		 */
		public function related_products_manager_include_files() {
			if (defined('WPB_VC_VERSION')) {
				require_once('widgets/related-product-manager-wpbakery-widget.php');
			}
		}

		/**
		* Initialize
		*/
		public function hooks() {
			add_action('init', array($this, 'related_products_manager_plugin_load'));
			add_action('wp_enqueue_scripts', array($this, 'related_products_manager_widget_script_register'));
			add_action('init', array($this, 'related_products_manager_include_files'));
			add_action('before_woocommerce_init', array($this, 'prowc_related_products_hpos_compatibility'));
		}

		/*
		* Load scripts and styles
		*/
		public function related_products_manager_widget_script_register() {
			wp_register_style('related_products_style', RPMW_URL . 'includes/css/related-products.css', array(), '1.0');
			wp_enqueue_style('related_products_style');
		}

		/**
		 * admin.
		 *
		 * @version 1.3.0
		 * @since   1.3.0
		 */
		public $settings;
		public function admin() {
			// Action links
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
			// Settings
			add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_woocommerce_settings_tab' ) );
			require_once( 'includes/settings/class-prowc-related-products-manager-settings-per-product.php' );
			require_once( 'includes/settings/class-prowc-related-products-manager-settings-section.php' );
			$this->settings = array();
			$this->settings['general'] = require_once( 'includes/settings/class-prowc-related-products-manager-settings-general.php' );
			// Version updated
			if ( get_option( 'prowc_related_products_manager_version', '' ) !== $this->version ) {
				add_action( 'admin_init', array( $this, 'version_updated' ) );
			}

			add_action('admin_enqueue_scripts', array($this, 'prowc_related_products_manager_admin_style'));
			add_action('admin_init',  array($this,'prowc_related_products_manager_notice_update'));
			add_action('admin_init',  array($this,'prowc_related_products_manager_plugin_notice_remindlater'));
			add_action('admin_init',  array($this,'prowc_related_products_manager_plugin_notice_review'));
			add_action('admin_notices', array($this,'prowc_related_products_manager_admin_upgrade_notice'));
			add_action('admin_notices', array($this,'prowc_related_products_manager_admin_review_notice'));
			add_action('plugins_loaded', array($this,'prowc_related_products_manager_check_version'));
			register_activation_hook( __FILE__, array($this,'prowc_rpmw_check_activation_hook'));

			// Admin notice
			if (!class_exists('WooCommerce')) {
				add_action('admin_notices', array( $this, 'fail_load') );
				return;
			}
		}

		// Database options upgrade
		function prowc_related_products_manager_check_version() {
			if ( version_compare( $this->version, '1.5.0', '<' )) {
				global $wpdb;
				$table_options = $wpdb->prefix . 'options';
				$old_keys = wp_cache_get( 'prowc_old_keys' );
				
				if ( false === $old_keys ) {
					$old_keys = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `%s` WHERE `option_name` LIKE %s", $table_options, '%alg_wc_%' ) );
					wp_cache_set( 'prowc_old_keys', $old_keys );
				}
		
				if ( is_array( $old_keys ) || is_object( $old_keys ) ) {
					foreach ( $old_keys as $val ) {
						$option_name = $val->option_name;
						$new_key = str_replace( 'alg_wc_', 'prowc_', $option_name );
						$old_option_value = get_option( $option_name );
						update_option( $new_key, $old_option_value );
						delete_option( $option_name );
					}
				}
			}
		}
		
		/**
		 * Show action links on the plugin screen.
		 *
		 * @version 1.2.0
		 * @since   1.0.0
		 * @param   mixed $links
		 * @return  array
		 */
		function action_links( $links ) {
			$custom_links = array();
			$custom_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=prowc_related_products_manager' ) . '">' . __( 'Settings', 'related-products-manager-woocommerce' ) . '</a>';
			if ( 'related-products-manager-for-woocommerce.php' === basename( __FILE__ ) ) {
				$custom_links[] = '<a href="https://prowcplugins.com/downloads/related-products-manager-for-woocommerce/?utm_source=related-products-manager-for-woocommerce&utm_medium=referral&utm_campaign=settings">' . __( 'Unlock All', 'related-products-manager-woocommerce' ) . '</a>';
			}
			return array_merge( $custom_links, $links );
		}

		/**
		 * Add Related Products Manager settings tab to WooCommerce settings.
		 *
		 * @version 1.2.0
		 * @since   1.0.0
		 */
		function add_woocommerce_settings_tab( $settings ) {
			$settings[] = require_once( 'includes/settings/class-prowc-settings-related-products-manager.php' );
			return $settings;
		}

		/**
		 * version_updated.
		 *
		 * @version 1.3.0
		 * @since   1.2.0
		 */
		function version_updated() {
			update_option( 'prowc_related_products_manager_version', $this->version );
		}

		function prowc_related_products_manager_notice_update() {
			$remdate = gmdate('Y-m-d', strtotime('+ 7 days'));
			$rDater = get_option('prowc_related_products_manager_plugin_notice_nopemaybelater');
			if(!get_option('prowc_related_products_manager_plugin_notice_remindlater')){
				update_option('prowc_related_products_manager_plugin_notice_remindlater',$remdate);
				update_option('prowc_related_products_manager_plugin_reviewtrack', 0);
			}
			
			if($rDater && gmdate('Y-m-d') >= $rDater) {
				update_option('prowc_related_products_manager_plugin_notice_remindlater',$remdate);
			}
		}

		/**
		 * Get the plugin url.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return  string
		 */
		function plugin_url() {
			return untrailingslashit( plugin_dir_url( __FILE__ ) );
		}

		/**
		 * Get the plugin path.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return  string
		 */
		function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Plugin active date.
		 *
		 * @version 1.5.2
		 * @since   1.5.2
		 */
		function prowc_rpmw_check_activation_hook() {
			$get_activation_time = gmdate('Y-m-d', strtotime('+ 3 days'));
			add_option('prowc_rpmw_activation_time', $get_activation_time ); 
		}

		/**
		 * Admin Notice for WooCommerce Install & Active.
		 *
		 * @version 1.4.7
		 * @since   1.4.7
		 * @return  string
		 */
		function prowc_related_wc_installed() {

			$file_path = 'woocommerce/woocommerce.php';
			$installed_plugins = get_plugins();

			return isset($installed_plugins[$file_path]);
		}

		/**
		 * Admin Notice for WooCommerce Install & Active.
		 *
		 * @version 1.4.7
		 * @since   1.4.7
		 * @return  string
		 */
		function fail_load() {
			if(function_exists('WC')){
				return;
			}
			$screen = get_current_screen();
			if (isset($screen->parent_file) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id) {
				return;
			}

			$plugin = 'woocommerce/woocommerce.php';
			if ($this->prowc_related_wc_installed()) {
				if (!current_user_can('activate_plugins')) {
					return;
				}
				$activation_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin);

				$message = '<p><strong>' . esc_html__('Related Products Manager for WooCommerce', 'related-products-manager-woocommerce') . '</strong>' . esc_html__(' plugin is not working because you need to activate the Woocommerce plugin.', 'related-products-manager-woocommerce') . '</p>';
				$message .= '<p>' . sprintf('<a href="%s" class="button-primary">%s</a>', $activation_url, __('Activate Woocommerce Now', 'related-products-manager-woocommerce')) . '</p>';
			} else {
				if (!current_user_can('install_plugins')) {
					return;
				}

				$install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woocommerce'), 'install-plugin_woocommerce');

				$message = '<p><strong>' . esc_html__('Related Products Manager for WooCommerce', 'related-products-manager-woocommerce') . '</strong>' . esc_html__(' plugin is not working because you need to install the WooCoomerce plugin', 'related-products-manager-woocommerce') . '</p>';
				$message .= '<p>' . sprintf('<a href="%s" class="button-primary">%s</a>', $install_url, __('Install WooCoomerce Now', 'related-products-manager-woocommerce')) . '</p>';
			}

			echo '<div class="error"><p>' . wp_kses_post($message) . '</p></div>';
		}

		/**
		 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
		 * 
		 * @version 1.5.9
		 * @since   1.5.9
		 * @return  ProWC_Related_Products_Manager
		 */
		function prowc_related_products_hpos_compatibility() {
			if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
					'custom_order_tables',
					__FILE__,
					true // true (compatible, default) or false (not compatible)
				);
			}
		}

		function prowc_related_products_manager_admin_style(){
			wp_enqueue_style('prowc-related-products-style', RPMW_URL . '/includes/css/admin-style.css', array(), '1.0');
			wp_enqueue_script('prowc-wc-rpmw-script', RPMW_URL . '/includes/js/admin-script.js', array ( 'jquery' ), 1.1, true);

			//admin rating popup js
			wp_enqueue_script('prowc-rpmw-sweetalert-min', RPMW_URL . '/includes/js/sweetalert.min.js', array ( 'jquery' ), 1.1, true);
		}

		/* Admin Notice for upgrade plan Start */
		function prowc_related_products_manager_admin_upgrade_notice() {
			$rDate = get_option('prowc_related_products_manager_plugin_notice_remindlater');
			if (gmdate('Y-m-d') >= $rDate && !get_option('prowc_related_products_manager_plugin_notice_dismissed')) { ?>
				<div class="notice is-dismissible prowc_related_products_manager_prowc_notice">
					<div class="prowc_related_products_manager_wrap">
						<div class="prowc_related_products_manager_gravatar">
							<img alt="" src="<?php echo esc_url(RPMW_URL . '/includes/img/prowc_logo.png'); ?>">
						</div>
						<div class="prowc_related_products_manager_authorname">
							<div class="notice_texts">
								<a href="<?php echo esc_url('https://prowcplugins.com/downloads/related-products-manager-for-woocommerce/?utm_source=related-products-manager-for-woocommerce&utm_medium=referral&utm_campaign=settings'); ?>" target="_blank"><?php esc_html_e('Upgrade Related Products Manager For Woocommerce', 'related-products-manager-woocommerce'); ?> </a> <?php esc_html_e('to get additional features, security, and support. ', 'related-products-manager-woocommerce'); ?> <strong><?php esc_html_e('Get 20% OFF', 'related-products-manager-woocommerce'); ?></strong><?php esc_html_e(' your upgrade, use coupon code', 'related-products-manager-woocommerce'); ?> <strong><?php esc_html_e('WP20', 'related-products-manager-woocommerce'); ?></strong>
							</div>
							<div class="prowc_related_products_manager_desc">
								<div class="notice_button">
								<?php wp_nonce_field( 'prowc_remind_later_nonce', 'nonce' ); ?>
									<a class="prowc_related_products_manager_button button-primary" href="<?php echo esc_url('https://prowcplugins.com/downloads/related-products-manager-woocommerce/?utm_source=related-products-manager-for-woocommerce&utm_medium=referral&utm_campaign=settings'); ?>" target="_blank"><?php echo esc_html__('Buy Now', 'related-products-manager-woocommerce'); ?></a>
									<a href="?prowc-rpm-plugin-remindlater"><?php echo esc_html__('Remind me later', 'related-products-manager-woocommerce'); ?></a>
									<a href="?prowc-rpm-plugin-dismissed"><?php echo esc_html__('Dismiss Notice', 'related-products-manager-woocommerce'); ?></a>
								</div>
							</div>
						</div>
						<div class="clearfix"></div>
					</div>
					<button type="button" class="notice-dismiss">
						<span class="screen-reader-text"></span>
					</button>
				</div>
				<?php
			}
		}

		/* Reminder notice for request plugin review */
		function prowc_related_products_manager_plugin_notice_remindlater() {
			$curDate = gmdate('Y-m-d', strtotime(' + 7 days'));
			$rlDate = gmdate('Y-m-d', strtotime(' + 15 days'));
			if ( isset( $_GET['prowc-rpm-plugin-remindlater'] ) && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'prowc_remind_later_nonce' ) ) {
				update_option('prowc_related_products_manager_plugin_notice_remindlater', $curDate);
				update_option('prowc_related_products_manager_plugin_reviewtrack', 1);
				update_option('prowc_related_products_manager_plugin_notice_nopemaybelater', $rlDate);
			}
			if (isset($_GET['prowc-rpm-plugin-dismissed'])) {
				update_option('prowc_related_products_manager_plugin_reviewtrack', 1);
				update_option('prowc_related_products_manager_plugin_notice_nopemaybelater', $rlDate);
				update_option('prowc_related_products_manager_plugin_notice_dismissed', 'true');
			}
			if(isset($_GET['prowc-wc-rpmw-plugin-remindlater-rating'])){
				update_option('prowc_rpmw_notice_remindlater_rating', $curDate);
			}
			if (isset($_GET['prowc-wc-rpmw-plugin-dismissed-rating'])) {
				update_option('prowc_rpmw_notice_dismissed_rating', 'true');
			}
		}

		/* Admin Notice for Plugin Review Start */
		function prowc_related_products_manager_admin_review_notice() {
			
			$plugin_data = get_plugin_data( __FILE__ );	
			$plugin_name = $plugin_data['Name'];
			$rating_rDate = get_option('prowc_rpmw_notice_remindlater_rating');
			$activationDate = get_option('prowc_rpmw_activation_time');
			
			$rDater = get_option('prowc_related_products_manager_plugin_notice_nopemaybelater');
			$algtrack = get_option('prowc_related_products_manager_plugin_reviewtrack');
		
			
			if (gmdate('Y-m-d') >= $activationDate && gmdate('Y-m-d') >= $rating_rDate && !get_option('prowc_rpmw_notice_dismissed_rating')) {	
				?>
				<div class="notice notice-info  is-dismissible">
					<p><?php // translators: Placeholder %s is the name of the plugin.
					printf( esc_html__( 'How are you liking the %s?', 'related-products-manager-woocommerce' ), esc_html( $plugin_name ) ); ?></p>
					<div class="rpmw_starts_main_div">
						<div class="stars rpmw-star">
							<input type="radio" name="star" class="star-1 rpmw" id="rpmw-star-1" value="1" />
							<label class="star-1" for="rpmw-star-1">1</label>
							<input type="radio" name="star" class="star-2 rpmw" id="rpmw-star-2" value="2" />
							<label class="star-2" for="rpmw-star-2">2</label>
							<input type="radio" name="star" class="star-3 rpmw" id="rpmw-star-3" value="3" />
							<label class="star-3" for="rpmw-star-3">3</label>
							<input type="radio" name="star" class="star-4 rpmw" id="rpmw-star-4" value="4" />
							<label class="star-4" for="rpmw-star-4">4</label>
							<input type="radio" name="star" class="star-5 rpmw" id="srpmw-tar-5" value="5" />
							<label class="star-5" for="rpmw-star-5">5</label>
							<span></span>
						</div>
						<div class="notice_button">
							<a href="?prowc-wc-rpmw-plugin-remindlater-rating" class="button-secondary" ><?php echo esc_html__('Remind me later', 'related-products-manager-woocommerce'); ?></a>
							<a href="?prowc-wc-rpmw-plugin-dismissed-rating" class="button-secondary" ><?php echo esc_html__('Dismiss Notice', 'related-products-manager-woocommerce'); ?></a>
						</div>
					</div>
				</div>
				<?php
			}
		
			if ($rDater != "") {
				if (gmdate('Y-m-d') >= $rDater && $algtrack && !get_option('prowc_related_products_manager_plugin_notice_alreadydid')) {
					?>
					<div class="notice is-dismissible prowc_related_products_manager_prowc_notice">
						<div class="prowc_related_products_manager_wrap">
							<div class="prowc_related_products_manager_gravatar">
								<img alt="" src="<?php echo esc_url(RPMW_URL . '/includes/img/prowc_logo.png'); ?>">
							</div>
							<div class="prowc_related_products_manager_authorname">
								<div class="notice_texts">
									<strong><?php esc_html_e('Are you enjoying Related Products Manager Woocommerce?', 'related-products-manager-woocommerce'); ?></strong>
								</div>
								<div class="prowc_related_products_manager_desc">
									<div class="notice_button">
										<button class="prowc_related_products_manager_button button-primary prowc_related_products_manager_yes"><?php echo esc_html__('Yes!', 'related-products-manager-woocommerce'); ?></button>
										<a class="prowc_related_products_manager_button button action" href="?prowc-rpmw-plugin-alreadydid"><?php echo esc_html__('Not Really!', 'related-products-manager-woocommerce'); ?></a>
									</div>
								</div>
							</div>
							<div class="clearfix"></div>
						</div>

						<button type="button" class="notice-dismiss">
							<span class="screen-reader-text"></span>
						</button>
						<div class="prowc_related_products_manager_prowc_notice_review_yes">
							<div class="notice_texts">
								<?php esc_html_e('That\'s awesome! Could you please do me a BIG favor and give it 5-star rating on WordPress to help us spread the word and boost our motivation?' , 'related-products-manager-woocommerce'); ?>
							</div>
							<div class="prowc_related_products_manager_desc">
								<div class="notice_button">
								<?php wp_nonce_field( 'prowc_rpmw_nopemaybelater', 'nonce' ); ?>
									<a class="prowc_related_products_manager_button button-primary" href="<?php echo esc_url('https://wordpress.org/support/plugin/related-products-manager-woocommerce/reviews/?filter=5#new-post'); ?>" target="_blank"><?php echo esc_html__('Okay You Deserve It', 'related-products-manager-woocommerce'); ?></a>
									<a class="prowc_related_products_manager_button button action" href="?prowc-rpmw-plugin-nopemaybelater"><?php echo esc_html__('Nope Maybe later', 'related-products-manager-woocommerce'); ?></a>
									<a class="prowc_related_products_manager_button button action" href="?prowc-rpmw-plugin-alreadydid"><?php echo esc_html__('I Already Did', 'related-products-manager-woocommerce'); ?></a>
								</div>
							</div>
						</div>
					</div>
					<?php
				}
			}
		}

		function prowc_related_products_manager_plugin_notice_review() {
			$curDate = gmdate('Y-m-d', strtotime(' + 7 Days'));
			if ( isset( $_GET['prowc-rpmw-plugin-nopemaybelater'] ) && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'prowc_rpmw_nopemaybelater' ) ) {
				update_option('prowc_related_products_manager_plugin_notice_nopemaybelater', $curDate);
			}
			if (isset($_GET['prowc-rpmw-plugin-alreadydid'])) {
				update_option('prowc_related_products_manager_plugin_notice_alreadydid', 'true');
			}
		}

	}
endif;

if (!function_exists('prowc_related_product_free_activation')) {

	/**
	 * Add action on plugin activation
	 * 
	 * @version 1.5.3
	 * @since   1.5.3
	 */
	function prowc_related_product_free_activation() {

		// Deactivate Empty Cart Button Pro for WooCommerce
		deactivate_plugins('related-products-manager-pro-for-woocommerce/related-products-manager-pro-for-woocommerce.php'); 
		
	}
}
register_activation_hook(__FILE__, 'prowc_related_product_free_activation');

if ( ! function_exists( 'prowc_related_products_manager' ) ) {
	/**
	 * Returns the main instance of ProWC_Related_Products_Manager to prevent the need to use globals.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return  ProWC_Related_Products_Manager
	 */
	function prowc_related_products_manager() {
		return ProWC_Related_Products_Manager::instance();
	}
}

prowc_related_products_manager();

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
 */
add_action('before_woocommerce_init', 'proWC_related_products_hpos_compatibility');

function proWC_related_products_hpos_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true // true (compatible, default) or false (not compatible)
        );
    }
}
