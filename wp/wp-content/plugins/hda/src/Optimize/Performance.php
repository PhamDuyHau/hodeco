<?php
/**
 * WordPress Performance - Heartbeat, Embeds & Core Cleanup.
 *
 * @package HDAddons\Optimize
 * @author  HD
 */

namespace HDAddons\Optimize;

use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

final class Performance {

	/**
	 * Heartbeat frequency options.
	 *
	 * @var array
	 */
	public static array $heartbeatOptions = [
		0   => 'Default (15s)',
		30  => '30 seconds',
		60  => '60 seconds',
		120 => '120 seconds',
		-1  => 'Disable completely',
	];

	/**
	 * Module options.
	 */
	private array $options;

	// ------------------------------------------------------

	public function __construct() {
		$this->options = Optimize::getOptions();

		// Heartbeat control
		$this->initHeartbeat();

		// Embeds control
		$this->initEmbeds();

		// Core cleanup
		$this->initCleanup();
	}

	// ------------------------------------------------------
	// HEARTBEAT CONTROL
	// ------------------------------------------------------

	/**
	 * Initialize Heartbeat modifications.
	 *
	 * @return void
	 */
	private function initHeartbeat(): void {
		$heartbeat_frequency = (int) ( $this->options[ Optimize::KEY_HEARTBEAT_FREQUENCY ] ?? 0 );
		$heartbeat_location  = $this->options[ Optimize::KEY_HEARTBEAT_LOCATION ] ?? 'default';

		if ( 0 === $heartbeat_frequency && 'default' === $heartbeat_location ) {
			return; // No modifications
		}

		// Disable completely
		if ( -1 === $heartbeat_frequency ) {
			add_action( 'init', $this->disableHeartbeat( ... ), 1 );
			return;
		}

		// Location-based control
		if ( 'default' !== $heartbeat_location ) {
			add_action( 'init', $this->controlHeartbeatLocation( ... ), 1 );
		}

		// Modify frequency
		if ( $heartbeat_frequency > 0 ) {
			add_filter( 'heartbeat_settings', $this->modifyHeartbeatFrequency( ... ) );
		}
	}

	/**
	 * Disable Heartbeat completely.
	 *
	 * @return void
	 */
	public function disableHeartbeat(): void {
		wp_deregister_script( 'heartbeat' );
	}

	/**
	 * Control where Heartbeat runs.
	 *
	 * @return void
	 */
	public function controlHeartbeatLocation(): void {
		$location = $this->options[ Optimize::KEY_HEARTBEAT_LOCATION ] ?? 'default';

		$is_admin    = is_admin();
		$is_post_edit = $is_admin && isset( $GLOBALS['pagenow'] ) &&
		                in_array( $GLOBALS['pagenow'], [ 'post.php', 'post-new.php' ], true );

		$should_disable = match ( $location ) {
			'disable_everywhere'    => true,
			'disable_frontend'      => ! $is_admin,
			'allow_post_edit_only'  => ! $is_post_edit,
			default                 => false,
		};

		if ( $should_disable ) {
			wp_deregister_script( 'heartbeat' );
		}
	}

	/**
	 * Modify Heartbeat frequency.
	 *
	 * @param array $settings Heartbeat settings.
	 *
	 * @return array
	 */
	public function modifyHeartbeatFrequency( array $settings ): array {
		$frequency = (int) ( $this->options[ Optimize::KEY_HEARTBEAT_FREQUENCY ] ?? 0 );

		if ( $frequency > 0 ) {
			$settings['interval'] = $frequency;
		}

		return $settings;
	}

	// ------------------------------------------------------
	// EMBEDS CONTROL
	// ------------------------------------------------------

	/**
	 * Initialize Embeds modifications.
	 *
	 * @return void
	 */
	private function initEmbeds(): void {
		if ( empty( $this->options[ Optimize::KEY_DISABLE_EMBEDS ] ) ) {
			return;
		}

		// Dequeue embed scripts early (before they are printed)
		add_action( 'wp_enqueue_scripts', $this->dequeueEmbedScripts( ... ), 9999 );

		// Remove oEmbed discovery links and related actions via init hook
		// (ensures actions are registered before we try to remove them)
		add_action( 'init', $this->removeEmbedActions( ... ), 9998 );

		// Remove oEmbed-related JavaScript from the front-end
		add_action( 'init', $this->disableEmbeds( ... ), 9999 );
	}

	/**
	 * Remove oEmbed head actions.
	 * Called on 'init' hook to ensure actions are registered before removal.
	 *
	 * @return void
	 */
	public function removeEmbedActions(): void {
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	}

	/**
	 * Dequeue embed scripts.
	 * Called on 'wp_enqueue_scripts' with high priority.
	 *
	 * @return void
	 */
	public function dequeueEmbedScripts(): void {
		wp_dequeue_script( 'wp-embed' );
	}

	/**
	 * Disable WordPress Embeds.
	 *
	 * @return void
	 */
	public function disableEmbeds(): void {
		// Remove the REST API endpoint
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );

		// Turn off oEmbed auto discovery
		add_filter( 'embed_oembed_discover', '__return_false' );

		// Don't filter oEmbed results
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );

		// Remove all embeds rewrite rules
		add_filter( 'rewrite_rules_array', $this->disableEmbedsRewrites( ... ) );

		// Remove filter of the oEmbed result before any HTTP requests are made
		remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
	}

	/**
	 * Remove embed rewrites.
	 *
	 * @param array $rules Rewrite rules.
	 *
	 * @return array
	 */
	public function disableEmbedsRewrites( array $rules ): array {
		foreach ( $rules as $rule => $rewrite ) {
			if ( str_contains( $rewrite, 'embed=true' ) ) {
				unset( $rules[ $rule ] );
			}
		}

		return $rules;
	}

	// ------------------------------------------------------
	// CORE CLEANUP
	// ------------------------------------------------------

	/**
	 * Initialize Core Cleanup.
	 *
	 * @return void
	 */
	private function initCleanup(): void {
		if ( empty( $this->options[ Optimize::KEY_ENABLE_CLEANUP ] ) ) {
			return;
		}

		add_action( 'init', $this->runCleanup( ... ), 1 );
	}

	/**
	 * Run WordPress cleanup operations.
	 * Removes unnecessary features and actions.
	 *
	 * @return void
	 */
	public function runCleanup(): void {
		// wp_head cleanup
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );

		// All actions related to emojis
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );

		// Staticize emoji
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

		// Remove the wp-json header from WordPress
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );

		// Remove id li navigation
		add_filter( 'nav_menu_item_id', '__return_null', 10, 3 );

		// Remove DNS prefetch for s.w.org (emoji CDN)
		add_filter( 'emoji_svg_url', '__return_false' );

		// Remove WP version from scripts and styles
		add_filter( 'style_loader_src', Helper::removeVersionQuery( ... ), 10 );
		add_filter( 'script_loader_src', Helper::removeVersionQuery( ... ), 10 );
	}

}
