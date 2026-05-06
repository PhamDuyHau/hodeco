<?php
/**
 * 404 Monitor Admin Page.
 *
 * @package HDAddons\Monitor404
 * @author  HD
 */

namespace HDAddons\Monitor404;

use HDAddons\Plugin;

\defined( 'ABSPATH' ) || exit;

final class Monitor404Admin {

	/**
	 * Menu slug.
	 */
	public const string MENU_SLUG = 'hda-404-monitor';

	/**
	 * Nonce action for clear all.
	 */
	public const string CLEAR_ALL_NONCE = 'hda_clear_all_404_logs';

	// ------------------------------------------------------

	public function __construct() {
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
			__( '404 Monitor', HDA_TEXTDOMAIN ),
			__( '404 Monitor', HDA_TEXTDOMAIN ),
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

		// Verify nonce — use dedicated field name to avoid conflict with WP_List_Table bulk nonce
		$nonce = isset( $_REQUEST['_hda_clear_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_hda_clear_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::CLEAR_ALL_NONCE ) ) {
			return;
		}

		// Check capability
		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			return;
		}

		Monitor404::clearAll();

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
		if ( ! Monitor404::tableExists() ) {
			Monitor404::createTable();
		}

		$table = new Monitor404Table();
		$table->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( '404 Monitor', HDA_TEXTDOMAIN ); ?></h1>

			<?php
			if ( isset( $_GET['cleared'] ) ) :
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'All 404 logs have been cleared.', HDA_TEXTDOMAIN ); ?></p>
				</div>
			<?php endif; ?>

			<hr class="wp-header-end">

			<form method="post">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>">
				<?php $table->search_box( __( 'Search URL', HDA_TEXTDOMAIN ), '404-log' ); ?>
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}
}
