<?php

namespace SpeedBooster;

class SBP_Localize_Tracker extends SBP_Abstract_Module {
	private $file_name = '';
	private $dir_path = SBP_CACHE_DIR . '/analytics/';
	private $dir_url = SBP_CACHE_URL . '/analytics/';
	private $analytics_url = 'https://www.google-analytics.com/analytics.js';
	private $gtm_url = 'https://www.googletagmanager.com/gtm.js?id=';
	private $gtag_url = 'https://www.googletagmanager.com/gtag/js?id=';
	private $transient_name = '';

	public function __construct() {
		if ( ! sbp_get_option('localize-analytics') ) {
			return;
		}

		if ( sbp_get_option('use-minimal-analytics') ) {
			if ( sbp_get_option('tracking-position', 'footer') === 'footer' ) {
				$tracking_code_position = 'wp_footer';
			} else {
				$tracking_code_position = 'wp_head';
			}
			add_action( $tracking_code_position, [ $this, 'insert_minimal_analytics' ] );
		} else {
			add_filter('sbp_output_buffer', [$this, 'replace_url']);
		}
	}

	public function test($html) {
		return 'zahid';
	}

	public function replace_url( $html ) {
		$html = $this->replace_analytics( $html );
		$html = $this->replace_gtm( $html );
		$html = $this->replace_gtag( $html );

		return $html;
	}

	public function insert_minimal_analytics() {
		global $sbp_options;
		$tracking_id = $sbp_options['sbp_ga_tracking_script'];
		require SPEED_BOOSTER_PACK_PATH . '/inc/template/analytics/minimal-analytics.php';
	}

	private function replace_analytics( $html ) {
		// Set file path, file url and file name
		$this->file_name      = 'analytics.js';
		$this->transient_name = 'sbp_analytics_ga';

		// Check if script tags exists
		preg_match_all( '/<script[^>]*?>(.*)<\/script>/Umsi', $html, $matches );

		if ( ! $matches[0] ) {
			return $html;
		}

		// Check if file exists or expired and save new file
		if ( ! $this->check_file() ) {
			if ( ! $this->save_file( $this->analytics_url ) ) {
				return $html;
			}
		}


		// Find Google Analytics script
		$html = preg_replace( '/(?:https?:)?\/\/www\.google-analytics\.com\/analytics\.js/i', $this->dir_url . $this->file_name, $html );

		return $html;
	}

	private function replace_gtm( $html ) {
		// Get GTM id
		preg_match_all( "/<script>\(function\(w,d,s,l,i\)(.*?)\'(GTM-[A-Z0-9a-z]+)\'(.*?)<\/script>/Umsi", $html, $matches );

		if ( ! $matches[2] || ! $matches[2][0] ) {
			return $html;
		}

		$id = $matches[2][0];

		// Set file path, file url and file name
		$this->file_name      = $id . '.js';
		$this->transient_name = 'sbp_analytics_gtm';

		// Set file url
		$this->gtm_url .= $id;

		// Check if file exists or expired and save new file
		if ( ! $this->check_file() ) {
			if ( ! $this->save_file( $this->gtm_url ) ) {
				return $html;
			}
		}

		// Find Google Analytics script
		$html = preg_replace( '/(http(s)?:\/\/www\.googletagmanager\.com\/gtm\.js\?id=\'\+i\+dl)/Umsi', $this->dir_url . $this->file_name . "'", $html );

		return $html;
	}

	private function replace_gtag( $html ) {
		// Set file path, file url and file name
		$this->file_name      = 'gtag.js';
		$this->transient_name = 'sbp_analytics_gtag';

		// Get Gtag id
		preg_match_all( '/src=\"https:\/\/www\.googletagmanager\.com\/gtag\/js\?id=([A-Za-z0-9-_]+)"/Umsi', $html, $matches );

		if ( ! $matches[1] || ! $matches[1][0] ) {
			return $html;
		}

		$id = $matches[1][0];

		// Set file url
		$this->gtag_url .= $id;

		// Check if file exists or expired and save new file
		if ( ! $this->check_file() ) {
			if ( ! $this->save_file( $this->gtag_url ) ) {
				return $html;
			}
		}

		// Find Gtag script
		$html = preg_replace( '/src=\"https:\/\/www\.googletagmanager\.com\/gtag\/js\?id=([A-Za-z0-9-_]+)"/Umsi', 'src="' . $this->dir_url . $this->file_name . '"', $html );

		return $html;
	}

	private function save_file( $url ) {
		global $wp_filesystem;

		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		$remote_response = wp_remote_get( $url );

		if ( wp_remote_retrieve_response_code( $remote_response ) !== 200 ) {
			return false;
		}

		// Fetch file
		$content = wp_remote_retrieve_body( $remote_response );

		if ( ! $content ) {
			return false;
		}

		// Check if file exists
		if ( ! $wp_filesystem->exists( $this->dir_path ) ) {
			$wp_filesystem->mkdir( $this->dir_path, FS_CHMOD_DIR );
		}

		set_transient( $this->transient_name, '1', 60 * 60 * 24 );

		return $wp_filesystem->put_contents( $this->dir_path . $this->file_name, $content );
	}

	private function check_file() {
		global $wp_filesystem;

		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		$file = $this->dir_path . $this->file_name;

		// Check if file exists
		if ( ! $wp_filesystem->exists( $file ) ) {
			return false;
		}

		// If transient doesn't exists, download and rewrite the file
		if ( ! get_transient( $this->transient_name ) ) {
			return false;
		}

		return true;
	}
}

new SBP_Localize_Tracker();