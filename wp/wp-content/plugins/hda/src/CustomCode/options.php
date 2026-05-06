<?php
/**
 * Custom Code module options panel — combines Custom Script + Custom CSS.
 *
 * @package HDAddons\CustomCode
 */

\defined( 'ABSPATH' ) || exit;

?>
<div class="container">
	<input type="hidden" name="custom_code-hidden" value="1">

	<?php
	// Include existing sub-module options (they are self-contained).
	include __DIR__ . '/options-script.php';
	include __DIR__ . '/options-css.php';
	?>
</div>
