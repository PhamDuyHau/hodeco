<?php
/**
 * Traffic Monitor Admin Page — registers submenu and handles actions.
 *
 * @package HDAddons\TrafficMonitor
 * @author  HD
 */

namespace HDAddons\Security\TrafficMonitor;

use HDAddons\Plugin;

\defined( 'ABSPATH' ) || exit;

final class TrafficMonitorAdmin {

	/**
	 * Menu slug.
	 */
	public const string MENU_SLUG = 'hda-traffic-monitor';

	/**
	 * Nonce action for clear all.
	 */
	public const string CLEAR_ALL_NONCE = 'hda_clear_all_traffic_logs';

	// --------------------------------------------------

	public function __construct() {
		add_action( 'admin_menu', $this->addMenuPage( ... ) );
		add_action( 'admin_init', $this->handleClearAll( ... ) );
	}

	// --------------------------------------------------

	/**
	 * Add submenu page under HDA settings.
	 *
	 * @return void
	 */
	public function addMenuPage(): void {
		add_submenu_page(
			'hda-settings',
			__( 'Traffic Monitor', HDA_TEXTDOMAIN ),
			__( 'Traffic Monitor', HDA_TEXTDOMAIN ),
			Plugin::CAPABILITY,
			self::MENU_SLUG,
			$this->renderPage( ... )
		);
	}

	// --------------------------------------------------

	/**
	 * Handle clear all logs action.
	 *
	 * @return void
	 */
	public function handleClearAll(): void {
		if ( ! isset( $_REQUEST['clear_all_logs'] ) ) {
			return;
		}

		$page = isset( $_REQUEST['page'] ) ? sanitize_key( $_REQUEST['page'] ) : '';
		if ( $page !== self::MENU_SLUG ) {
			return;
		}

		$nonce = isset( $_REQUEST['_wpnonce_clear'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce_clear'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::CLEAR_ALL_NONCE ) ) {
			return;
		}

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			return;
		}

		TrafficLogger::clearAll();

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&cleared=1' ) );
		exit;
	}

	// --------------------------------------------------

	/**
	 * Render the admin page with stats summary + log table.
	 *
	 * @return void
	 */
	public function renderPage(): void {
		// Ensure table exists.
		if ( ! TrafficLogger::tableExists() ) {
			TrafficLogger::createTable();
		}

		$stats = TrafficLogger::getStats( 7 );
		$table = new TrafficMonitorTable();
		$table->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Traffic Monitor', HDA_TEXTDOMAIN ); ?></h1>

			<?php if ( isset( $_GET['cleared'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'All traffic logs have been cleared.', HDA_TEXTDOMAIN ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Stats Summary -->
			<div class="hda-traffic-stats" style="display:flex;gap:12px;margin:16px 0;flex-wrap:wrap;">
				<?php $this->renderStatCard( __( 'Total Events', HDA_TEXTDOMAIN ), $stats['total'], '📊' ); ?>
				<?php $this->renderStatCard( __( 'Blocked', HDA_TEXTDOMAIN ), $stats['blocked'], '🛡️', '#ef4444' ); ?>
				<?php $this->renderStatCard( __( 'Logged', HDA_TEXTDOMAIN ), $stats['logged'], '📝', '#eab308' ); ?>
				<?php
				foreach ( $stats['by_type'] as $typeRow ) :
					if ( (int) $typeRow['cnt'] > 0 ) :
						$this->renderStatCard(
							strtoupper( $typeRow['attack_type'] ),
							(int) $typeRow['cnt'],
							'⚠️'
						);
					endif;
				endforeach;
				?>
			</div>

			<hr class="wp-header-end">

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>">
				<?php $table->search_box( __( 'Search', HDA_TEXTDOMAIN ), 'traffic-log' ); ?>
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}

	// --------------------------------------------------

	/**
	 * Render a single stat card.
	 *
	 * @param string      $label Label text.
	 * @param int         $value Numeric value.
	 * @param string      $icon  Emoji icon.
	 * @param string|null $color Optional accent color.
	 *
	 * @return void
	 */
	private function renderStatCard( string $label, int $value, string $icon = '📊', ?string $color = null ): void {
		$borderColor = $color ? "border-left:3px solid {$color};" : '';

		printf(
			'<div style="background:#fff;padding:12px 18px;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,.08);min-width:120px;%s">
				<div style="font-size:1.6em;font-weight:700;">%s %s</div>
				<div style="color:#64748b;font-size:.85em;margin-top:2px;">%s <small style="opacity:.6;">(%s)</small></div>
			</div>',
			esc_attr( $borderColor ),
			$icon,
			esc_html( number_format_i18n( $value ) ),
			esc_html( $label ),
			esc_html__( '7 days', HDA_TEXTDOMAIN )
		);
	}
}
