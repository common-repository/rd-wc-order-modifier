<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class RDWCOM_Manager {
	private static $order_items = array();

	public static function get_premium_version_url() {
		return 'https://www.robotdwarf.com/woocommerce-plugins/admin-order-modifier/';
	}

	public static function load( $plugin_file_path ) {
		self::add_actions();
		self::add_filters();
	}

	public static function activate() {
		register_uninstall_hook( RDWCOM_PLUGIN_FILE, array( __CLASS__, 'uninstall' ) );
	}

	public static function uninstall() {
		delete_option( 'rdwcom_options' );
	}

	public static function get_options() {
		$options = json_decode( get_option( 'rdwcom_options' ), true );
		$options = ( $options ) ? $options : array();

		$defaults = array(
			'activate_includes_tax_modifier' => 'yes',
			'includes_tax_modifier_edit_mode' => 'item_single',
			'show_includes_tax_modifier_cost_column' => 'yes',
			'show_includes_tax_modifier_total_column' => 'no',
			'show_review_upgrade_notice' => 'yes',
			'show_tax_item_required_notice' => 'yes',
		);

		return array_replace_recursive( $defaults, $options );
	}

	public static function update_options( $updated_options ) {
		$current_options = self::get_options();
		$options = array_replace_recursive( $current_options, $updated_options );
		update_option( 'rdwcom_options', json_encode( $options ) );
	}

	public static function get_option( $key ) {
		$options = self::get_options();
		return ( isset( $options[$key] ) ) ? $options[$key] : false;
	}

	public static function update_option( $key, $value ) {
		$options = self::get_options();
		$options[$key] = $value;
		self::update_options($options);
	}

	public static function add_actions() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'woocommerce_admin_order_item_headers', array( __CLASS__, 'woocommerce_admin_order_item_headers' ), 11, 1 );
		add_action( 'woocommerce_admin_order_item_values', array( __CLASS__, 'woocommerce_admin_order_item_values' ), 11, 3 );
		add_action( 'woocommerce_before_save_order_items', array( __CLASS__, 'woocommerce_before_save_order_items' ), 11, 2 );
		add_action( 'woocommerce_before_save_order_item', array( __CLASS__, 'woocommerce_before_save_order_item' ), 11, 1 );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices') );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'wp_ajax_rdwcom_hide_review_upgrade_notice', array( __CLASS__, 'hide_review_upgrade_notice' ) );
		add_action( 'wp_ajax_rdwcom_hide_tax_item_required_notice', array( __CLASS__, 'hide_tax_item_required_notice' ) );
		add_action( 'before_woocommerce_init', array( __CLASS__, 'before_woocommerce_init' ) );
	}

	public static function add_filters() {
		add_filter( 'woocommerce_ajax_order_item', array( __CLASS__, 'woocommerce_ajax_order_item' ), 11, 4 );
		add_filter( 'plugin_action_links_' . plugin_basename( RDWCOM_PLUGIN_FILE ), array( __CLASS__, 'plugin_action_links' ) );
	}

	public static function admin_notices() {
		global $post;
		global $pagenow;

		//Check for WooCommerce is active
		if ( ! self::is_plugin_activated( 'woocommerce' ) ) {
			self::display_woocommerce_plugin_required_notice();
			deactivate_plugins( plugin_basename( RDWCOM_PLUGIN_FILE ) );
		}

		//Show notices
		if ( isset( $pagenow ) && 'post.php' == $pagenow ) {
			if ( isset( $post ) && 'shop_order' == $post->post_type ) {
				$options = self::get_options();
				if ( 'yes' == $options['show_review_upgrade_notice'] ) {
					self::display_review_upgrade_notice();
				}
				if ( 'yes' == $options['show_tax_item_required_notice'] ) {
					self::display_tax_item_required_notice();
				}
			}
		}

		//HPOS
		if ( isset( $pagenow ) && 'admin.php' == $pagenow ) {
			$screen = get_current_screen();
			$screen_id = ( $screen ) ? $screen->id : '';
			if ( 'woocommerce_page_wc-orders' == $screen_id && isset( $_GET['action'] ) ) {
				$action = sanitize_text_field( $_GET['action'] );
				if ( 'edit' == $action ) {
					$options = self::get_options();
					if ( 'yes' == $options['show_review_upgrade_notice'] ) {
						self::display_review_upgrade_notice();
					}
					if ( 'yes' == $options['show_tax_item_required_notice'] ) {
						self::display_tax_item_required_notice();
					}
				}
			}
		}
	}

	public static function is_plugin_activated( $plugin_name ) {
		$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . "$plugin_name/$plugin_name.php";
		if ( in_array( $plugin_path, wp_get_active_and_valid_plugins() ) ) {
			return true;
		}
		if ( function_exists( 'wp_get_active_network_plugins' ) ) {
			if ( in_array( $plugin_path, wp_get_active_network_plugins() ) ) {
				return true;
			}
		}
		return false;
	}

	public static function display_woocommerce_plugin_required_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo wp_kses( __( 'The <strong>RD Order Modifier for WooCommerce</strong> plugin depends on the <strong>WooCommerce</strong> plugin. Please activate <strong>WooCommerce</strong> in order to use the <strong>RD Order Modifier for WooCommerce</strong> plugin.', 'rdwcom' ), array( 'strong' => array() ) ); ?></p>
		</div>
	<?php
	}

	public static function display_review_upgrade_notice() {
		?>
		<div id="rdwcom-review-upgrade-notice" class="updated notice is-dismissible">
			<p>
			<?php 
			echo wp_kses( 
				sprintf(
					/* translators: %1$s: premium version URL %2$s: newsletter signup url */
					__( 'Thank you for using the <strong>RD Order Modifier for WooCommerce</strong> plugin. If you find this plugin useful, please consider leaving a <a href="https://wordpress.org/support/plugin/rd-wc-order-modifier/reviews/#new-post" target="_blank">review</a>. If you need advanced features, have a look at the premium <a href="%1$s" target="_blank">Admin Order Modifier for WooCommerce</a> plugin.<br><br><a href="#" class="rdwcom-hide-notice">Don\'t show again</a>.', 'rdwcom' ),
					esc_html( self::get_premium_version_url() )
				), array( 
					'br' => array(), 
					'strong' => array(), 
					'a' => array( 'href' => array(), 'target' => array(), 'class' => array(), ),
				) ); 
			?>
				</p>
		</div>
		<?php
	}

	public static function display_tax_item_required_notice() {
		?>
		<div id="rdwcom-tax-item-required-notice" class="updated notice is-dismissible">
			<p>
			<?php 
			echo wp_kses(
				sprintf(
					/* translators: %s: premium version URL */
					__( 'When using the <strong>RD Order Modifier for WooCommerce</strong> plugin for your orders, please ensure that you have <a href="https://woocommerce.com/document/managing-orders/#order-items-and-totals" target="_blank">tax items enabled for your order</a> or upgrade to the premium <a href="%s" target="_blank">Admin Order Modifier for WooCommerce</a> plugin to disable the calculation for non-taxable items.<br><br><a href="#" class="rdwcom-hide-notice">Don\'t show again</a>', 'rdwcom' ),
					esc_html( self::get_premium_version_url() )
				), array( 
					'br' => array(), 
					'strong' => array(), 
					'a' => array( 'href' => array(), 'target' => array(), 'class' => array(), ),
				) ); 
			?>
				</p>
		</div>
		<?php
	}

	public static function hide_review_upgrade_notice() {
		if ( check_ajax_referer( 'rdwcom-ajax-nonce' ) ) {
			self::update_option( 'show_review_upgrade_notice', 'no' );
			wp_send_json_success( array(), 200 );
		}

		wp_send_json_error( array(), 302 );
	}

	public static function hide_tax_item_required_notice() {
		if ( check_ajax_referer( 'rdwcom-ajax-nonce' ) ) {
			self::update_option( 'show_tax_item_required_notice', 'no' );
			wp_send_json_success( array(), 200 );
		}

		wp_send_json_error( array(), 302 );
	}

	public static function plugin_action_links( $links ) {
		$settings_url = menu_page_url( 'rdwcom-settings', false );
		$rd_products_url = menu_page_url( 'robot-dwarf-menu', false );
		$plugin_action_links = array(
			'<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'rdwcom' ) . '</a>',
			'<a href="' . esc_url( $rd_products_url ) . '">' . __( 'RD Products', 'rdwcom' ) . '</a>',
		);

		return array_merge( $plugin_action_links, $links );
	}

	public static function admin_enqueue_scripts() {
		$screen = get_current_screen();
		$screen_id = ( $screen ) ? $screen->id : '';

		wp_enqueue_style( 'rdwcom-admin', RDWCOM_URL . 'css/admin.css', array( 'woocommerce_admin_styles' ), RDWCOM_VERSION );

		if ( self::is_order_meta_box_screen( $screen_id ) ) {
			wp_enqueue_script( 'rdwcom-admin', RDWCOM_URL . 'js/admin.js', array( 'wc-admin-order-meta-boxes' ), RDWCOM_VERSION );
			wp_localize_script(
				'rdwcom-admin', 'RDWCOMSettings', array( 
					'options' => self::get_options(),
					'ajax_nonce' => wp_create_nonce( 'rdwcom-ajax-nonce' ),
				) 
			);
		}
	}

	/**
	 * Duplicate of WooCommerce is_order_meta_box_screen() method
	 */
	private static function is_order_meta_box_screen( $screen_id ) {
		if ( ! function_exists( 'wc_get_order_types' ) ) {
			return false;
		}

		$screen_id = str_replace( 'edit-', '', $screen_id );

		$types_with_metaboxes_screen_ids = array_filter(
			array_map(
				'wc_get_page_screen_id',
				wc_get_order_types( 'order-meta-boxes' )
			)
		);

		return in_array( $screen_id, $types_with_metaboxes_screen_ids, true );
	}
	
	public static function admin_init() {
		load_plugin_textdomain( 'rdwcom', false, plugin_basename( dirname( RDWCOM_PLUGIN_FILE ) ) . '/languages' );

		register_setting( 'rdwcom', 'rdwcom_options' );
		add_settings_section(
			'rdwcom_section_admin_orders',
			__( 'RD Order Modifier For WooCommerce', 'rdwcom' ),
			array( __CLASS__, 'section_admin_orders_callback' ),
			'rdwcom'
		);
		add_settings_field(
			'activate_includes_tax_modifier',
			__( 'Activate "includes tax" modifier', 'rdwcom' ),
			array( __CLASS__, 'field_checkbox_callback' ),
			'rdwcom',
			'rdwcom_section_admin_orders',
			array(
				'label_for' => 'activate_includes_tax_modifier',
				'class' => '',
			)
		);
		add_settings_field(
			'includes_tax_modifier_edit_mode',
			__( 'Set "includes tax" modifier edit mode', 'rdwcom' ),
			array( __CLASS__, 'field_includes_tax_modifier_edit_mode_callback' ),
			'rdwcom',
			'rdwcom_section_admin_orders',
			array(
				'label_for' => 'includes_tax_modifier_edit_mode',
				'class' => '',
			)
		);
		add_settings_field(
			'show_includes_tax_modifier_cost_column',
			__( 'Show "includes tax" modifier cost column', 'rdwcom' ),
			array( __CLASS__, 'field_checkbox_callback' ),
			'rdwcom',
			'rdwcom_section_admin_orders',
			array(
				'label_for' => 'show_includes_tax_modifier_cost_column',
				'class' => '',
			)
		);
		add_settings_field(
			'show_includes_tax_modifier_total_column',
			__( 'Show "includes tax" modifier total column', 'rdwcom' ),
			array( __CLASS__, 'field_checkbox_callback' ),
			'rdwcom',
			'rdwcom_section_admin_orders',
			array(
				'label_for' => 'show_includes_tax_modifier_total_column',
				'class' => '',
			)
		);
		add_settings_field(
			'show_review_upgrade_notice',
			__( 'Show review / upgrade notice', 'rdwcom' ),
			array( __CLASS__, 'field_checkbox_callback' ),
			'rdwcom',
			'rdwcom_section_admin_orders',
			array(
				'label_for' => 'show_review_upgrade_notice',
				'class' => '',
			)
		);
		add_settings_field(
			'show_tax_item_required_notice',
			__( 'Show tax item required notice', 'rdwcom' ),
			array( __CLASS__, 'field_checkbox_callback' ),
			'rdwcom',
			'rdwcom_section_admin_orders',
			array(
				'label_for' => 'show_tax_item_required_notice',
				'class' => '',
			)
		);
	}

	public static function before_woocommerce_init() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', RDWCOM_PLUGIN_FILE, true );
		}
	}

	public static function section_admin_orders_callback( $args ) {
		?>
		<a class="button button-secondary" href="<?php echo esc_html( self::get_premium_version_url() ); ?>" target="_blank"><?php esc_html_e( 'Get the premium version', 'rdwcom' ); ?></a>
		<a class="button button-secondary" href="https://wordpress.org/support/plugin/rd-wc-order-modifier/reviews/#new-post" target="_blank"><?php esc_html_e( 'Review this plugin', 'rdwcom' ); ?></a>
		<h4 id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Settings / Options', 'rdwcom' ); ?></h4>
	<?php
	}

	public static function field_checkbox_callback( $args ) {
		$options = self::get_options();
		?>
		<input 
			name="rdwcom_options[<?php echo esc_attr( $args['label_for'] ); ?>]" 
			type="checkbox" id="<?php echo esc_attr( $args['label_for'] ); ?>" 
			value="yes"
			<?php echo ( ( $options[$args['label_for']] ) == 'yes' ) ? 'checked="checked"' : ''; ?>>
		<?php
	}

	public static function field_includes_tax_modifier_edit_mode_callback( $args ) {
		$options = self::get_options();
		?>
		<select name="rdwcom_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
			<option value="item_single" <?php echo ( 'item_single' == $options[$args['label_for']] ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Single item', 'rdwcom' ); ?></option>
			<option value="item_total" <?php echo ( 'item_total' == $options[$args['label_for']] ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Item total', 'rdwcom' ); ?></option>
		</select>
		<?php
	}

	public static function menu_exists( $slug = '' ) {
		global $menu;
		foreach ( $menu as $menu_item ) {
			if ( isset( $menu_item[2] ) ) {
				if ( $menu_item[2] == $slug ) {
					return true;
				}
			}
		}
		return false;
	}

	public static function submenu_exists( $parent_slug = '', $slug = '' ) {
		global $submenu;
		if ( isset( $submenu[$parent_slug] ) ) {
			foreach ( $submenu[$parent_slug] as $submenu_item ) {
				if ( isset( $submenu_item[2] ) ) {
					if ( $submenu_item[2] == $slug ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	public static function admin_menu() {
		if ( ! self::menu_exists( 'robot-dwarf-menu' ) ) {
			add_menu_page( 
				__( 'Robot Dwarf', 'rdwcom' ),
				__( 'Robot Dwarf', 'rdwcom' ),
				'manage_options',
				'robot-dwarf-menu',
				array( __CLASS__, 'our_products_page_html' ),
				RDWCOM_URL . 'images/robotdwarf-mascot.png',
				80
			);

			add_submenu_page(
				'robot-dwarf-menu',
				__( 'Our Products', 'rdwcom' ),
				__( 'Our Products', 'rdwcom' ),
				'manage_options',
				'robot-dwarf-menu'
			);
		}

		$hook_name = add_submenu_page(
			'robot-dwarf-menu',
			__( 'Order Modifier', 'rdwcom' ),
			__( 'Order Modifier', 'rdwcom' ),
			'manage_options',
			'rdwcom-settings',
			array( __CLASS__, 'settings_page_html' )
		);

		add_action( 'load-' . $hook_name, array( __CLASS__, 'settings_page_submit' ) );
	}

	public static function settings_page_submit() {
		if ( isset( $_POST['submit'] ) ) {

			if ( ! isset( $_POST['rdwcom_settings_nonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( sanitize_text_field( $_POST['rdwcom_settings_nonce'] ), 'rdwcom_settings' ) ) {
				return;
			}

			$options = self::get_options();
			$_GET['options-updated'] = 'true';

			$options['activate_includes_tax_modifier'] = 'no';
			if ( isset( $_POST['rdwcom_options']['activate_includes_tax_modifier'] ) ) {
				$options['activate_includes_tax_modifier'] = 'yes';
			}

			if ( isset( $_POST['rdwcom_options']['includes_tax_modifier_edit_mode'] ) ) {
				$options['includes_tax_modifier_edit_mode'] = sanitize_text_field( $_POST['rdwcom_options']['includes_tax_modifier_edit_mode'] );
			}

			$options['show_includes_tax_modifier_cost_column'] = 'no';
			if ( isset( $_POST['rdwcom_options']['show_includes_tax_modifier_cost_column'] ) ) {
				$options['show_includes_tax_modifier_cost_column'] = 'yes';
			}

			$options['show_includes_tax_modifier_total_column'] = 'no';
			if ( isset( $_POST['rdwcom_options']['show_includes_tax_modifier_total_column'] ) ) {
				$options['show_includes_tax_modifier_total_column'] = 'yes';
			}

			$options['show_review_upgrade_notice'] = 'no';
			if ( isset( $_POST['rdwcom_options']['show_review_upgrade_notice'] ) ) {
				$options['show_review_upgrade_notice'] = 'yes';
			}

			$options['show_tax_item_required_notice'] = 'no';
			if ( isset( $_POST['rdwcom_options']['show_tax_item_required_notice'] ) ) {
				$options['show_tax_item_required_notice'] = 'yes';
			}

			self::update_options( $options );
		}
	}

	public static function our_products_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$products = array();
		$remote = wp_remote_get(
			RDWCOM_API_URL . 'fetch-products',
			array(
				'timeout' => 10,
				'headers' => array(
					'Content-Type' => 'application/json',
				)
			)
		);

		$payload = json_decode( wp_remote_retrieve_body( $remote ), true );
		$products = ( isset( $payload['products'] ) ) ? $payload['products'] : array();
		?>
		<div class="wc-addons-wrap">
			<div class="wrap">
				<h1><?php esc_html_e( get_admin_page_title() ); ?></h1>
				<p><?php esc_html_e( 'We think WooCommerce is a fantastic solution for a wide variety of Ecommerce stores due to its stability, ease of use, and above all, its extensibility.', 'rdwcom' ); ?></p>
				<p><?php esc_html_e( 'With the use of WooCommerce plugins, there are virtually unlimited ways to add functionality and customisations to fit your store and operations.', 'rdwcom' ); ?></p>
				<p><?php esc_html_e( 'In our experience working with ecommerce clients, we have identified key areas, particularly in the order management process, that can be enhanced and improved and have developed several premium WooCommerce plugins specifically aimed at making this process easier for store managers.', 'rdwcom' ); ?></p>
				<div class="addon-product-group__items">
					<ul class="products addons-products-two-column">
						<?php 
						foreach ( $products as $product ) :
							self::render_product_card( $product );
						endforeach;
						?>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	public static function render_product_card( $product ) {
		?>
		<li class="product">
			<div class="product-details">
				<div class="product-text-container">
					<a target="_blank" href="<?php echo esc_url( $product['url'] ); ?>">
						<h2><?php echo esc_html( $product['title'] ); ?></h2>
					</a>
					<p><?php echo wp_kses_post( $product['description'] ); ?></p>
				</div>
			</div>
			<div class="product-footer">
				<div class="product-price-and-reviews-container">
					<div class="product-price-block">
						<?php if ( $product['price'] > 0 ) : ?> 
							<span class="price">
								<?php
								echo wp_kses(
									'$' . sprintf( '%01.2f', $product['price'] ),
									array(
										'span' => array(
											'class' => array(),
										),
										'bdi'  => array(),
									)
								);
								?>
							</span>
							<span class="price-suffix">
								<?php
								$price_suffix = __( 'per year', 'woocommerce' );
								echo esc_html( $price_suffix );
								?>
							</span>
						<?php else : ?>
							<span class="price"><?php esc_html_e( 'FREE', 'rdwcom' ); ?>/span>
						<?php endif; ?>
					</div>
				</div>
				<a class="button" target="_blank" href="<?php echo esc_url( $product['url'] ); ?>">
					<?php esc_html_e( 'View details', 'woocommerce' ); ?>
				</a>
			</div>
		</li>
		<?php
	}

	public static function settings_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['options-updated'] ) ) {
			add_settings_error( 'rdwcom_messages', 'rdcom_message', __( 'Options updated', 'rdwcom' ), 'updated' );
		}

		settings_errors( 'rdwcom_messages' );
		?>
		<div class="wrap">
			<form action="<?php menu_page_url( 'rdwcom' ); ?>" method="post">
				<?php 
				wp_nonce_field( 'rdwcom_settings', 'rdwcom_settings_nonce' );
				settings_fields( 'rdwcom' );
				do_settings_sections( 'rdwcom' );
				submit_button( __( 'Update options', 'rdwcom') ); 
				?>
			</form>
			<h4><?php esc_html_e( 'Get the premium version for advanced features including:', 'rdwcom' ); ?></h4>
			<ul class="rdwcom-upgrade-list">
				<li><?php esc_html_e( 'Support for custom fee and shipping line items in addition to product line items', 'rdwcom' ); ?></li>
				<li><?php esc_html_e( 'Support for multiple tax rates', 'rdwcom' ); ?></li>
				<li><?php esc_html_e( 'Support for location based and reduced tax rates', 'rdwcom' ); ?></li>
				<li><?php esc_html_e( 'Adds a quick link to item product page', 'rdwcom' ); ?></li>
				<li><?php esc_html_e( 'Adds a warning if order total has increased after item price edit', 'rdwcom' ); ?></li>
				<li><?php esc_html_e( 'Optionally change the position of the new columns to show after the standard WooCommerce columns instead of before', 'rdwcom' ); ?></li>
				<li><?php esc_html_e( 'Ability to disable the feature for non-taxable items', 'rdwcom' ); ?></li>
				<li><?php esc_html_e( 'Adds a product info quick view button where you can quickly view line item details without leaving the order screen', 'rdwcom' ); ?></li>
				<li><?php esc_html_e( 'Adds “Quick apply” buttons to the input boxes so you can quickly apply the regular or sales price to a line item so you don\'t have to check pricing first', 'rdwcom' ); ?></li>
				<li><?php esc_html_e( 'Moves the “Settings” menu into the WooCommerce -> Tax area so that it seamlessly fits with WooCommerce instead of being a separate menu on the WordPress sidebar', 'rdwcom' ); ?></li>
				<li><?php esc_html_e( 'Removes RD branding', 'rdwcom' ); ?></li>
			</ul>
			<a class="button button-secondary" href="<?php echo esc_html( self::get_premium_version_url() ); ?>" target="_blank"><?php esc_html_e( 'Get the premium version', 'rdwcom' ); ?></a>
		</div>
	<?php
	}

	public static function woocommerce_ajax_order_item( $item, $item_id, $order, $product ) {
		if ( check_ajax_referer( 'order-item', 'security' ) ) {
			if ( 'line_item' == $item->get_type() ) {
				if ( $item->get_subtotal() == $item->get_total() ) {
					$item->set_subtotal_tax( 0 );
					$item->set_total_tax( 0 );
					$item->save();
					$order->add_item( $item );

					$calculate_tax_args = array(
						'country'  => isset( $_POST['country'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['country'] ) ) ) : '',
						'state'    => isset( $_POST['state'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['state'] ) ) ) : '',
						'postcode' => isset( $_POST['postcode'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['postcode'] ) ) ) : '',
						'city'     => isset( $_POST['city'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['city'] ) ) ) : '',
					);
			
					$order->calculate_taxes( $calculate_tax_args );
					$order->calculate_totals( false );
				}
			}
		}

		return $item;
	}

	public static function woocommerce_admin_order_item_headers( $order ) {
		$options = self::get_options();
		if ( 'yes' == $options['activate_includes_tax_modifier'] ) :
			if ( 'yes' == $options['show_includes_tax_modifier_cost_column'] ) : 
				?>
				<th class="item_cost_incl sortable" data-sort="float"><?php esc_html_e( 'Cost (Incl.)', 'rdwcom' ); ?></th>
			<?php 
			endif;
			if ( 'yes' == $options['show_includes_tax_modifier_total_column'] ) : 
				?>
				<th class="item_total_incl sortable" data-sort="float"><?php esc_html_e( 'Total (Incl.)', 'rdwcom' ); ?></th>
			<?php 
			endif;
		endif;
	}

	public static function get_allowed_html() {
		return array( 
			'span' => array(
			'class' => array(),
			),
			'bdi' => array(),
			'button' => array(
			'type' => array(),
			'class' => array(),
			'title' => array(),
			'data-price' => array(),
			),
		);
	}

	public static function woocommerce_admin_order_item_values( $product, $item, $item_id ) {
		$options = self::get_options();
		if ( 'yes' == $options['activate_includes_tax_modifier'] ) :
			if ( 'line_item' == $item->get_type() ) :
				$order = wc_get_order( $item->get_order_id() );
				$subtotal_inc_price = wc_round_tax_total( ( $item->get_subtotal() + $item->get_subtotal_tax() )  / $item->get_quantity(), wc_get_price_decimals() );
				$total_inc_price = wc_round_tax_total( ( $item->get_total() + $item->get_total_tax() )  / $item->get_quantity(), wc_get_price_decimals() );
				$subtotal_inc = wc_round_tax_total( ( $item->get_subtotal() + $item->get_subtotal_tax() ), wc_get_price_decimals() );
				$total_inc = wc_round_tax_total( ( $item->get_total() + $item->get_total_tax() ), wc_get_price_decimals() );
				$taxes = $item->get_taxes();
				$tax_ids = array();
				foreach ( $taxes['total'] as $tax_id => $tax_total ) {
					$tax_ids[] = $tax_id;
				}
				$tax_refund = 0;
				foreach ( $tax_ids as $tax_id ) {
					$tax_refund += $order->get_tax_refunded_for_item( $item_id, $tax_id );
				}
				if ( 'yes' == $options['show_includes_tax_modifier_cost_column'] ) : 
					?>
					<td class="item_cost_incl" width="1%" data-sort-value="<?php echo esc_attr( $item->get_total() ); ?>">
						<div class="view">
							<?php
							echo wp_kses( wc_price( $total_inc_price, array( 'currency' => $order->get_currency() ) ), self::get_allowed_html() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

							if ( $total_inc_price !== $subtotal_inc_price ) {
								/* translators: %s: discount amount */
								echo '<span class="wc-order-item-discount">' . sprintf( esc_html__( '%s discount', 'woocommerce' ), wp_kses( wc_price( wc_format_decimal( $subtotal_inc_price - $total_inc_price, '' ), array( 'currency' => $order->get_currency() ) ), self::get_allowed_html() ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped	
							}

							$refunded = -1 * ( $order->get_total_refunded_for_item( $item_id ) + $tax_refund );

							if ( $refunded && 'no' == $options['show_includes_tax_modifier_total_column'] ) {
								echo '<small class="refunded">' . wp_kses( wc_price( $refunded, array( 'currency' => $order->get_currency() ) ), self::get_allowed_html() ) . '</small>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</div>
						<?php if ( 'item_single' == $options['includes_tax_modifier_edit_mode'] ) : ?>
						<div class="edit" style="display: none;">
							<div class="split-input">
								<div class="input">
									<label><?php esc_attr_e( 'Cost before discount', 'rdwcom' ); ?></label>
									<input type="text" name="line_incl_subcost[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $subtotal_inc_price ) ); ?>" class="rdwcom_line_incl_subcost wc_input_price" data-subtotal="<?php echo esc_attr( wc_format_localized_price( $subtotal_inc_price ) ); ?>" />
								</div>
								<div class="input">
									<label><?php esc_attr_e( 'Cost after discount', 'rdwcom' ); ?></label>
									<input type="text" name="line_incl_cost[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $total_inc_price ) ); ?>" class="rdwcom_line_incl_cost wc_input_price" data-tip="<?php esc_attr_e( 'After pre-tax discounts.', 'woocommerce' ); ?>" data-total="<?php echo esc_attr( wc_format_localized_price( $total_inc_price ) ); ?>" />
								</div>
							</div>
						</div>
						<div class="rdwcom-refund" style="display: none;">
							<input type="text" name="refund_line_incl_cost[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" data-inc-price="<?php echo esc_attr( wc_format_localized_price( $total_inc_price ) ); ?>" class="rdwcom_refund_line_total wc_input_price" />
						</div>
						<?php endif; ?>
						<?php if ( 'no' == $options['show_includes_tax_modifier_total_column'] ) : ?>
							<?php if ( 'item_total' == $options['includes_tax_modifier_edit_mode'] ) : ?>
							<div class="edit" style="display: none;">
								<div class="split-input">
									<div class="input">
										<label><?php esc_attr_e( 'Total before discount', 'rdwcom' ); ?></label>
										<input type="text" name="line_incl_subtotal[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $subtotal_inc ) ); ?>" class="rdwcom_line_incl_subtotal wc_input_price" data-subtotal="<?php echo esc_attr( wc_format_localized_price( $subtotal_inc_price ) ); ?>" />
									</div>
									<div class="input">
										<label><?php esc_attr_e( 'Total after discount', 'rdwcom' ); ?></label>
										<input type="text" name="line_incl_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $total_inc ) ); ?>" class="rdwcom_line_incl_total wc_input_price" data-tip="<?php esc_attr_e( 'After pre-tax discounts.', 'woocommerce' ); ?>" data-total="<?php echo esc_attr( wc_format_localized_price( $total_inc_price ) ); ?>" />
									</div>
								</div>
							</div>
							<div class="rdwcom-refund" style="display: none;">
								<input type="text" name="refund_line_incl_cost[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" data-inc-price="<?php echo esc_attr( wc_format_localized_price( $total_inc_price ) ); ?>" class="rdwcom_refund_line_total wc_input_price" />
							</div>
							<?php endif; ?>
						<?php endif; ?>
					</td>
				<?php 
				endif;
				if ( 'yes' == $options['show_includes_tax_modifier_total_column'] ) : 
					?>
					<td class="item_total_incl" width="1%" data-sort-value="<?php echo esc_attr( $item->get_total() ); ?>">
						<div class="view">
							<?php
							echo wp_kses( wc_price( $total_inc, array( 'currency' => $order->get_currency() ) ), self::get_allowed_html() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

							if ( $total_inc !== $subtotal_inc ) {
								/* translators: %s: discount amount */
								echo '<span class="wc-order-item-discount">' . sprintf( esc_html__( '%s discount', 'woocommerce' ), wp_kses( wc_price( wc_format_decimal( $subtotal_inc - $total_inc, '' ), array( 'currency' => $order->get_currency() ) ), self::get_allowed_html() ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}

							$refunded = -1 * ( $order->get_total_refunded_for_item( $item_id ) + $tax_refund );

							if ( $refunded ) {
								echo '<small class="refunded">' . wp_kses( wc_price( $refunded, array( 'currency' => $order->get_currency() ) ), self::get_allowed_html() ) . '</small>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</div>
						<?php if ( 'no' == $options['show_includes_tax_modifier_cost_column'] ) : ?>
							<?php if ( 'item_single' == $options['includes_tax_modifier_edit_mode'] ) : ?>
							<div class="edit" style="display: none;">
								<div class="split-input">
									<div class="input">
										<label><?php esc_attr_e( 'Cost before discount', 'rdwcom' ); ?></label>
										<input type="text" name="line_incl_subcost[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $subtotal_inc_price ) ); ?>" class="rdwcom_line_incl_subcost wc_input_price" data-subtotal="<?php echo esc_attr( wc_format_localized_price( $subtotal_inc_price ) ); ?>" />
									</div>
									<div class="input">
										<label><?php esc_attr_e( 'Cost after discount', 'rdwcom' ); ?></label>
										<input type="text" name="line_incl_cost[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $total_inc_price ) ); ?>" class="rdwcom_line_incl_cost wc_input_price" data-tip="<?php esc_attr_e( 'After pre-tax discounts.', 'woocommerce' ); ?>" data-total="<?php echo esc_attr( wc_format_localized_price( $total_inc_price ) ); ?>" />
									</div>
								</div>
							</div>
							<div class="rdwcom-refund" style="display: none;">
								<input type="text" name="refund_line_incl_cost[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" data-inc-price="<?php echo esc_attr( wc_format_localized_price( $total_inc_price ) ); ?>" class="rdwcom_refund_line_total wc_input_price" />
							</div>
							<?php endif; ?>
						<?php endif; ?>
						<?php if ( 'item_total' == $options['includes_tax_modifier_edit_mode'] ) : ?>
						<div class="edit" style="display: none;">
							<div class="split-input">
								<div class="input">
									<label><?php esc_attr_e( 'Total before discount', 'rdwcom' ); ?></label>
									<input type="text" name="line_incl_subtotal[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $subtotal_inc ) ); ?>" class="rdwcom_line_incl_subtotal wc_input_price" data-subtotal="<?php echo esc_attr( wc_format_localized_price( $subtotal_inc_price ) ); ?>" />
								</div>
								<div class="input">
									<label><?php esc_attr_e( 'Total after discount', 'rdwcom' ); ?></label>
									<input type="text" name="line_incl_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $total_inc ) ); ?>" class="rdwcom_line_incl_total wc_input_price" data-tip="<?php esc_attr_e( 'After pre-tax discounts.', 'woocommerce' ); ?>" data-total="<?php echo esc_attr( wc_format_localized_price( $total_inc_price ) ); ?>" />
								</div>
							</div>
						</div>
						<div class="rdwcom-refund" style="display: none;">
							<input type="text" name="refund_line_incl_cost[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" data-inc-price="<?php echo esc_attr( wc_format_localized_price( $total_inc_price ) ); ?>" class="rdwcom_refund_line_total wc_input_price" />
						</div>
						<?php endif; ?>
					</td>
				<?php endif; ?>
			<?php 
			else : 
				if ( 'yes' == $options['show_includes_tax_modifier_cost_column'] ) : 
					?>
				<td class="item_cost_incl" width="1%">-</td>
				<?php endif; ?>
			<?php if ( 'yes' == $options['show_includes_tax_modifier_total_column'] ) : ?>
				<td class="item_total_incl" width="1%">-</td>
			<?php 
			endif;
			endif;
		endif;
	}

	public static function woocommerce_before_save_order_items( $order_id, $items ) {
		self::$order_items = $items;

		if ( isset( self::$order_items['order_item_id'] ) ) {
			foreach ( self::$order_items['order_item_id'] as $item_id ) {
				$item = WC_Order_Factory::get_order_item( absint( $item_id ) );
				if ( ! $item ) {
					continue;
				}
				if ( $item->get_type() == 'line_item' ) {
					if ( $item->get_product_id() ) {
						$qty = ( isset( self::$order_items['order_item_qty'][$item_id] ) ) ? self::$order_items['order_item_qty'][$item_id] : 1;
						//Check for cost submission
						if ( isset( self::$order_items['line_incl_subcost'][$item_id] ) ) {
							$line_incl_subcost = str_replace( wc_get_price_decimal_separator(), '.', self::$order_items['line_incl_subcost'][$item_id] );
							$product = wc_get_product( $item->get_product_id() );
							$tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
							$taxes = WC_Tax::calc_tax( $line_incl_subcost * $qty, $tax_rates, true ); 
							self::$order_items['line_subtotal_taxes'][$item_id] = $taxes;
							$line_excl_subtotal = WC_Tax::round( ( $line_incl_subcost * $qty ) - array_sum( $taxes ) );
							if ( isset( self::$order_items['line_subtotal_tax'][$item_id] ) ) {
								self::$order_items['line_subtotal_tax'][$item_id] = array_sum( $taxes );
							}
							if ( isset( self::$order_items['line_subtotal'][$item_id] ) ) {
								self::$order_items['line_subtotal'][$item_id] = $line_excl_subtotal;
							}
						}
						if ( isset( self::$order_items['line_incl_cost'][$item_id] ) ) {
							$line_incl_cost = str_replace( wc_get_price_decimal_separator(), '.', self::$order_items['line_incl_cost'][$item_id] );
							$product = wc_get_product( $item->get_product_id() );
							$tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
							$taxes = WC_Tax::calc_tax( $line_incl_cost * $qty, $tax_rates, true ); 
							self::$order_items['line_taxes'][$item_id] = $taxes;
							$line_excl_total = WC_Tax::round( ( $line_incl_cost * $qty ) - array_sum( $taxes ) ); 
							if ( isset( self::$order_items['line_tax'][$item_id] ) ) {
								self::$order_items['line_tax'][$item_id] = array_sum( $taxes );
							}
							if ( isset( self::$order_items['line_total'][$item_id] ) ) {
								self::$order_items['line_total'][$item_id] = $line_excl_total;
							}
						}
						//Check for total submission
						if ( isset( self::$order_items['line_incl_subtotal'][$item_id] ) ) {
							$line_incl_subtotal = str_replace( wc_get_price_decimal_separator(), '.', self::$order_items['line_incl_subtotal'][$item_id] );
							$product = wc_get_product( $item->get_product_id() );
							$tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
							$taxes = WC_Tax::calc_tax( $line_incl_subtotal, $tax_rates, true ); 
							self::$order_items['line_subtotal_taxes'][$item_id] = $taxes;
							$line_excl_subtotal = WC_Tax::round( ( $line_incl_subtotal ) - array_sum( $taxes ) );
							if ( isset( self::$order_items['line_subtotal_tax'][$item_id] ) ) {
								self::$order_items['line_subtotal_tax'][$item_id] = array_sum( $taxes );
							}
							if ( isset( self::$order_items['line_subtotal'][$item_id] ) ) {
								self::$order_items['line_subtotal'][$item_id] = $line_excl_subtotal;
							}
						}
						if ( isset( self::$order_items['line_incl_total'][$item_id] ) ) {
							$line_incl_total = str_replace( wc_get_price_decimal_separator(), '.', self::$order_items['line_incl_total'][$item_id] );
							$product = wc_get_product( $item->get_product_id() );
							$tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
							$taxes = WC_Tax::calc_tax( $line_incl_total, $tax_rates, true ); 
							self::$order_items['line_taxes'][$item_id] = $taxes;
							$line_excl_total = WC_Tax::round( ( $line_incl_total ) - array_sum( $taxes ) ); 
							if ( isset( self::$order_items['line_tax'][$item_id] ) ) {
								self::$order_items['line_tax'][$item_id] = array_sum( $taxes );
							}
							if ( isset( self::$order_items['line_total'][$item_id] ) ) {
								self::$order_items['line_total'][$item_id] = $line_excl_total;
							}
						}
					}
				}
			}
		}
	}

	public static function woocommerce_before_save_order_item( $item ) {
		if ( $item && 'line_item' == $item->get_type() ) {
			if ( $item->get_product_id() ) {
				$raw_tax_data = array(
					'subtotal' => array(),
					'total' => array(),
				);

				if ( isset( self::$order_items['line_subtotal_tax'][$item->get_id()] ) ) {
					$item->set_subtotal_tax( self::$order_items['line_subtotal_tax'][$item->get_id()] ); 
					$raw_tax_data['subtotal'] = self::$order_items['line_subtotal_taxes'][$item->get_id()];
				}

				if ( isset( self::$order_items['line_subtotal'][$item->get_id()] ) ) {
					$item->set_subtotal( self::$order_items['line_subtotal'][$item->get_id()] );
				}

				if ( isset( self::$order_items['line_tax'][$item->get_id()] ) ) {
					$item->set_total_tax( self::$order_items['line_tax'][$item->get_id()] );
					$raw_tax_data['total'] = self::$order_items['line_taxes'][$item->get_id()];
				}

				if ( isset( self::$order_items['line_total'][$item->get_id()] ) ) {
					$item->set_total( self::$order_items['line_total'][$item->get_id()] );
				}

				$item->set_taxes( $raw_tax_data );

				$item->save();
			}
		}
	}
}
