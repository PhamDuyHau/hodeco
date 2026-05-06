<?php
/**
 * Activity Log Admin Page.
 *
 * @package HDAddons\LoginSecurity\ActivityLog
 * @author  HD
 */

namespace HDAddons\LoginSecurity\ActivityLog;

use HDAddons\LoginSecurity\LoginSecurity;
use HDAddons\Plugin;

\defined( 'ABSPATH' ) || exit;

final class ActivityLogAdmin {

	/**
	 * Menu slug.
	 */
	public const string MENU_SLUG = 'hda-activity-log';

	// ------------------------------------------------------

	public function __construct() {
		$options = LoginSecurity::getOptions();
		if ( empty( $options[ LoginSecurity::KEY_ACTIVITY_LOG_ENABLED ] ) ) {
			return;
		}

		add_action( 'admin_menu', $this->addMenuPage( ... ) );
		add_action( 'admin_init', $this->handleClearAll( ... ) );
	}

	// ------------------------------------------------------

	/**
	 * Add submenu page under HDA settings.
	 *
	 * @return void
	 */
	public function addMenuPage(): void {
		add_submenu_page(
			'hda-settings',
			__( 'Activity Log', HDA_TEXTDOMAIN ),
			__( 'Activity Log', HDA_TEXTDOMAIN ),
			Plugin::CAPABILITY,
			self::MENU_SLUG,
			$this->renderPage( ... )
		);
	}

	// ------------------------------------------------------

	/**
	 * Handle clear all logs action.
	 *
	 * @return void
	 */
	public function handleClearAll(): void {
		if ( ! isset( $_REQUEST['clear_all_logs'] ) ) {
			return;
		}

		// Check page
		$page = isset( $_REQUEST['page'] ) ? sanitize_key( $_REQUEST['page'] ) : '';
		if ( $page !== self::MENU_SLUG ) {
			return;
		}

		// Verify nonce
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'bulk-activity logs' ) ) {
			return;
		}

		// Check capability
		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			return;
		}

		ActivityLog::clearAll();

		// Redirect to clean URL
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&cleared=1' ) );
		exit;
	}

	// ------------------------------------------------------

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function renderPage(): void {
		// Check if table exists, create if not
		if ( ! ActivityLog::tableExists() ) {
			ActivityLog::createTable();
		}

		$table = new ActivityLogTable();
		$table->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Activity Log', HDA_TEXTDOMAIN ); ?></h1>

			<?php
			if ( isset( $_GET['cleared'] ) ) :
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'All logs have been cleared.', HDA_TEXTDOMAIN ); ?></p>
				</div>
			<?php endif; ?>

			<hr class="wp-header-end">

			<?php $table->views(); ?>

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>">
				<?php
				// Preserve action filter
				if ( isset( $_REQUEST['action_filter'] ) ) :
					?>
					<input type="hidden" name="action_filter" value="<?php echo esc_attr( sanitize_key( $_REQUEST['action_filter'] ) ); ?>">
				<?php endif; ?>

				<?php $table->search_box( __( 'Search', HDA_TEXTDOMAIN ), 'activity-log' ); ?>
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}
}
