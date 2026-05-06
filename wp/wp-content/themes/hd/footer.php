<?php
/**
 * The template for displaying the footer.
 * Contains the body & HTML closing tags.
 *
 * @package HD
 * @author  HD
 */

use HD\Utilities\Helper;

\defined( 'ABSPATH' ) || die;

/**
 * HOOK: hd_site_content_after_action
 */
do_action( 'hd_site_content_after_action' );

?>
</main><!-- #site-content -->
<?php

/**
 * HOOK: hd_footer_before_action
 */
do_action( 'hd_footer_before_action' );

$footer_class = apply_filters( 'hd_footer_class_filter', 'site-footer' );
?>
<footer id="footer" class="<?php echo esc_attr( $footer_class ); ?>" <?php echo Helper::microdata( 'footer' ); ?>>
	<?php

	/**
	 * HOOK: hd_footer_action
	 *
	 * @see hd_construct_footer() - 10
	 */
	do_action( 'hd_footer_action' );

	?>
</footer><!-- #footer -->
</div><!-- .min-h-dvh -->
<?php

/**
 * HOOK: hd_footer_after_action
 */
do_action( 'hd_footer_after_action' );

/**
 * HOOK: wp_footer
 *
 * @see ContactLink::addThisContactLink() - 30
 * @see CustomScript::footerScripts() - 99
 * @see CustomScript::bodyScriptsBottom() - 99
 */
wp_footer();

?>
</body>
</html>
