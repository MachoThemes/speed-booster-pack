<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_WP_Dashboard {
	public function __construct() {
		add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_links' ], 90 );
		if ( is_admin() ) {
			require_once SBP_LIB_PATH . 'announce4wp/announce4wp-client.php';
			$this->set_notices();
			$this->initialize_announce4wp();

			add_action( 'admin_init', [ $this, 'rating_notice' ] );
			add_action( 'admin_init', [ $this, 'tweet_notice' ] );
		}

		add_filter( 'plugin_row_meta', [ $this, 'sbp_plugin_meta_links' ], 10, 2 );
	}

	public function sbp_plugin_meta_links( $meta_fields, $file ) {
		if ( SBP_PLUGIN_BASENAME == $file ) {
			$plugin_url    = "https://wordpress.org/support/plugin/speed-booster-pack/reviews/?rate=5#new-post";
			$meta_fields[] = "<a href='" . esc_url( $plugin_url ) . "' target='_blank' title='" . esc_html__( 'Rate Us',
					'speed-booster-pack' ) . "'>
            <i class='sbp-stars' style='position: relative; top: 3px;'>"
			                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
			                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
			                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
			                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
			                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
			                 . "</i></a>";
		}

		return $meta_fields;
	}

	public function add_admin_bar_links( \WP_Admin_Bar $admin_bar ) {
		if ( current_user_can( 'manage_options' ) ) {

			$admin_bar->add_menu( [
				'id'    => 'speed_booster_pack',
				'title' => 'Speed Booster',
				'href'  => admin_url( 'admin.php?page=sbp-settings' ),
				'meta'  => [
					'target' => '_self',
					'html'   => '<style>#wpadminbar #wp-admin-bar-speed_booster_pack .ab-item{background:url("' . SBP_URL . 'admin/images/icon.svg") no-repeat 5px center;padding-left:25px;filter: brightness(0.7) sepia(1) hue-rotate(50deg) saturate(1.5);}#wpadminbar #wp-admin-bar-speed_booster_pack .ab-item:hover{color:white;}</style>',
				],
			] );

			if ( sbp_get_option( 'module_caching' ) && ! sbp_should_disable_feature( 'caching' ) ) {
				// Cache clear
				$clear_cache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_cache' ),
					'sbp_clear_total_cache',
					'sbp_nonce' );
				$sbp_admin_menu  = [
					'id'     => 'sbp_clear_cache',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Clear Cache', 'speed-booster-pack' ),
					'href'   => $clear_cache_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );

				// Cache warmup
				$warmup_cache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_warmup_cache' ),
					'sbp_warmup_cache',
					'sbp_nonce' );
				$sbp_admin_menu   = [
					'id'     => 'sbp_warmup_cache',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Warmup Cache', 'speed-booster-pack' ),
					'href'   => $warmup_cache_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );
			}

			if ( sbp_get_option( 'localize_tracking_scripts' ) ) {
				$clear_tracking_scripts_url = wp_nonce_url( add_query_arg( 'sbp_action',
					'sbp_clear_localized_analytics' ),
					'sbp_clear_localized_analytics',
					'sbp_nonce' );
				$sbp_admin_menu             = [
					'id'     => 'sbp_clear_localized_scripts',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Clear Localized Scripts', 'speed-booster-pack' ),
					'href'   => $clear_tracking_scripts_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );
			}

			if ( SBP_Cloudflare::is_cloudflare_active() ) {
				$clear_cloudflare_cache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_cloudflare_cache' ),
					'sbp_clear_cloudflare_cache',
					'sbp_nonce' );
				$sbp_admin_menu             = [
					'id'     => 'sbp_clear_cloudflare_cache',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Clear Cloudflare Cache', 'speed-booster-pack' ),
					'href'   => $clear_cloudflare_cache_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );
			}

			if ( sbp_get_option( 'sucuri_enable' ) ) {
				$clear_sucuri_cache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_sucuri_cache' ),
					'sbp_clear_sucuri_cache',
					'sbp_nonce' );
				$sbp_admin_menu         = [
					'id'     => 'sbp_clear_sucuri_cache',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Clear Sucuri Cache', 'speed-booster-pack' ),
					'href'   => $clear_sucuri_cache_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );
			}
		}
	}

	public function set_notices() {
		// Set Sucuri Notice
		if ( $transient_value = get_transient( 'sbp_clear_sucuri_cache' ) ) {
			$notice_message = $transient_value == '1' ? __( 'Sucuri cache cleared.',
				'speed-booster-pack' ) : __( 'Error occured while clearing Sucuri cache. ',
					'speed-booster-pack' ) . get_transient( 'sbp_sucuri_error' );
			$notice_type    = $transient_value == '1' ? 'success' : 'error';
			SBP_Notice_Manager::display_notice( 'sbp_clear_sucuri_cache',
				'<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( $notice_message, 'speed-booster-pack' ) . '</p>',
				$notice_type,
				true,
				'flash' );
		}

		// Set Cloudflare Notice
		if ( $transient_value = get_transient( 'sbp_notice_cloudflare' ) ) {
			$notice_message = $transient_value == '1' ? __( 'Cloudflare cache cleared.',
				'speed-booster-pack' ) : __( 'Error occured while clearing Cloudflare cache. Possible reason: Credentials invalid.',
				'speed-booster-pack' );
			$notice_type    = $transient_value == '1' ? 'success' : 'error';
			SBP_Notice_Manager::display_notice( 'sbp_notice_cloudflare',
				'<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( $notice_message, 'speed-booster-pack' ) . '</p>',
				$notice_type,
				true,
				'flash' );
		}

		// Set Cache Clear Notice
		if ( get_transient( 'sbp_notice_cache' ) ) {
			SBP_Notice_Manager::display_notice( 'sbp_notice_cache',
				'<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( 'Cache cleared.', 'speed-booster-pack' ) . '</p>',
				'success',
				true,
				'flash' );
		}

		// Set Localizer Cache Clear Notice
		if ( get_transient( 'sbp_notice_tracker_localizer' ) ) {
			SBP_Notice_Manager::display_notice( 'sbp_notice_tracker_localizer',
				'<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( 'Localized scripts are cleared.',
					'speed-booster-pack' ) . '</p>',
				'success',
				true,
				'flash' );
		}

		// Warmup Notice
		if ( get_transient( 'sbp_warmup_started' ) ) {
//			 BEYNTODO: Add translator note
			SBP_Notice_Manager::display_notice( 'sbp_warmup_started',
				'<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( 'Cache warmup started.',
					'speed-booster-pack' ) . '</p>',
				'success',
				true,
				'recurrent' );
		}

		// Warmup Notice
		if ( get_transient( 'sbp_warmup_complete' ) ) {
			// BEYNTODO: Add translator note
			SBP_Notice_Manager::display_notice( 'sbp_warmup_complete',
				'<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( 'Static cache files created.',
					'speed-booster-pack' ) . '</p>',
				'success',
				true,
				'recurrent' );
		}

		// WP-Config Inject File Error
		if ( get_transient( 'sbp_wp_config_inject_error' ) ) {
			SBP_Notice_Manager::display_notice( 'sbp_wp_config_inject_error',
				'<p><strong>' . SBP_PLUGIN_NAME . '</strong> ' . __( 'Can not write plugins/speed-booster-pack/includes/wp-config-options/wp-config-inject.php file. Some ' . SBP_PLUGIN_NAME . ' features may not work. Please check your file permissions.',
					'speed-booster-pack' ) . '</p>',
				'error',
				true,
				'recurrent' );
		}

		// WP-Config File Error
		if ( get_transient( 'sbp_wp_config_error' ) ) {
			SBP_Notice_Manager::display_notice( 'sbp_wp_config_error',
				'<p><strong>' . SBP_PLUGIN_NAME . '</strong> ' . __( 'Can not write wp-config.php file. Some ' . SBP_PLUGIN_NAME . ' features may not work. Please check your file permissions.',
					'speed-booster-pack' ) . '</p>',
				'error',
				true,
				'recurrent' );
		}

		// WP-Config File Error
		if ( get_transient( 'sbp_warmup_errors' ) ) {
			$list   = '';
			$errors = get_transient( 'sbp_warmup_errors' );
			if ( is_array( $errors ) ) {
				foreach ( $errors as $error ) {
					$extras = [];
					if ( isset( $error['options']['user-agent'] ) && $error['options']['user-agent'] === 'Mobile' ) {
						$extras[] = '(Mobile)';
					}
					$list .= '<li><a href="' . $error['url'] . '" target="_blank">' . $error['url'] . ' ' . implode( ' ',
							$extras ) . '</a></li>';
				}
				SBP_Notice_Manager::display_notice( 'sbp_warmup_errors',
					'<p><strong>' . SBP_PLUGIN_NAME . '</strong> ' . __( 'Cache warmup completed but following pages may not be cached. Please check this pages are available. (Hover this notice to see all errors)',
						'speed-booster-pack' ) . '</p><ul class="warmup-cache-error-list">' . $list . '</ul>',
					'error',
					true,
					'recurrent' );
			}
		}
	}

	public function rating_notice() {
		if ( current_user_can( 'manage_options' ) ) {
			$meta_key           = 'sbp_rating_notice_display_time';
			$rating_notice_meta = get_user_meta( get_current_user_id(), $meta_key, true );
			if ( ! $rating_notice_meta ) {
				update_user_meta( get_current_user_id(), $meta_key, strtotime( '+7 days' ) );
			} else {
				if ($rating_notice_meta <= time()) {
					// B_TODO: Change Text
					SBP_Notice_Manager::display_notice('rate_wp_org', '<p>' . __( 'Rate us on <a href="https://wordpress.org/support/plugin/speed-booster-pack/reviews/?rate=5#new-post" target="_blank">wordpress.org</a>', 'speed-booster-pack' ) . '</p>', 'info', true, 'one_time');
				}
			}
		}
	}

	public function tweet_notice() {
		if ( current_user_can( 'manage_options' ) ) {
			$meta_key           = 'sbp_tweet_notice_display_time';
			$rating_notice_meta = get_user_meta( get_current_user_id(), $meta_key, true );
			if ( ! $rating_notice_meta ) {
				update_user_meta( get_current_user_id(), $meta_key, strtotime( '+14 days' ) );
			} else {
				if ($rating_notice_meta <= time()) {
					// B_TODO: Change Text
					SBP_Notice_Manager::display_notice('tweet_sbp', '<p>' . __( 'Tweet about us', 'speed-booster-pack' ) . '</p>', 'info', true, 'one_time');
				}
			}
		}
	}

	private function initialize_announce4wp() {
		if ( sbp_get_option( 'enable_external_notices' ) ) {
			new \Announce4WP_Client( 'speed-booster-pack.php',
				SBP_PLUGIN_NAME,
				"sbp",
				"https://speedboosterpack.com/wp-json/a4wp/v1/" . SBP_VERSION . "/news.json",
				"toplevel_page_sbp-settings" );
		}
	}
}