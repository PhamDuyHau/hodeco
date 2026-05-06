<?php
/**
 * Post Type Archive module options panel.
 *
 * @package HDAddons\PostTypeArchive
 */

use HDAddons\Helper;
use HDAddons\PostTypeArchive\PostTypeArchive;

\defined( 'ABSPATH' ) || exit;

$options       = Helper::getOption( PostTypeArchive::OPTION_NAME, [] );
$savedPages    = $options[ PostTypeArchive::KEY_PTA_PAGES ] ?? [];
$eligibleTypes = PostTypeArchive::getEligiblePostTypes();

?>
<div class="container">
	<input type="hidden" name="post_type_archive-hidden" value="1">

	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Archive Page Assignment', HDA_TEXTDOMAIN ); ?></legend>

		<?php if ( empty( $eligibleTypes ) ) : ?>
			<div class="hda-notice hda-notice--warning">
				<p>
					<?php esc_html_e( 'No eligible custom post types found. Post types must be public and not have a built-in archive page.', HDA_TEXTDOMAIN ); ?>
				</p>
			</div>
		<?php else : ?>
			<div class="container flex flex-x gap sm-up-1 md-up-2">
				<?php foreach ( $eligibleTypes as $slug => $postType ) : ?>
					<div class="cell section section-select">
						<label class="heading" for="pta_page_<?php echo esc_attr( $slug ); ?>">
							<?php echo esc_html( $postType->labels->name ?? ucfirst( $slug ) ); ?>
							<code style="font-size:11px;color:#999;margin-left:5px;"><?php echo esc_html( $slug ); ?></code>
						</label>
						<div class="option">
							<div class="controls">
								<div class="select_wrapper">
									<?php
									wp_dropdown_pages(
										[
											'name'              => 'pta_pages[' . esc_attr( $slug ) . ']',
											'id'                => 'pta_page_' . esc_attr( $slug ),
											'selected'          => absint( $savedPages[ $slug ] ?? 0 ),
											'show_option_none'  => __( '— None —', HDA_TEXTDOMAIN ),
											'option_none_value' => '0',
											'class'             => 'select',
											'post_status'       => 'publish',
										]
									);
									?>
								</div>
							</div>
						</div>
						<?php if ( ! empty( $postType->description ) ) : ?>
							<div class="desc"><?php echo esc_html( $postType->description ); ?></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="desc" style="margin-top:15px;">
				<?php echo wp_kses_post( __( 'Selected page becomes the archive URL for that post type — just like the built-in "Posts page".<br>Permalink rules are flushed automatically on save.', HDA_TEXTDOMAIN ) ); ?>
			</div>
		<?php endif; ?>
	</fieldset>
</div>
