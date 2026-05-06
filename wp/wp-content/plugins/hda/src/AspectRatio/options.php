<?php
/**
 * Aspect Ratio Settings Options
 *
 * @package Addons\AspectRatio
 */

use HDAddons\AspectRatio\AspectRatio;
use HDAddons\Helper;

\defined( 'ABSPATH' ) || exit;

$aspect_ratio_settings = Helper::filterSettingOptions( AspectRatio::SETTINGS_FILTER );
$aspect_ratio_options  = Helper::getOption( AspectRatio::OPTION_NAME, [] );
$post_type_terms       = $aspect_ratio_settings[ AspectRatio::SETTING_POST_TYPE_TERM ] ?? [];

?>
<div class="container">
	<input type="hidden" name="aspect-ratio-hidden" value="1">

	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Aspect Ratio Settings', HDA_TEXTDOMAIN ); ?></legend>

		<div class="container flex flex-x gap sm-up-1 md-up-2">
			<?php
			if ( empty( $post_type_terms ) ) {
				printf(
					'<div class="cell" style="width:100%%"><h3>%s</h3></div>',
					esc_html__( 'No data available or configuration for this feature has not been set.', HDA_TEXTDOMAIN )
				);
			} else {
				foreach ( $post_type_terms as $postType ) :
					if ( empty( $postType ) ) {
						continue;
					}

					$width  = $aspect_ratio_options[ "as-{$postType}-width" ] ?? '';
					$height = $aspect_ratio_options[ "as-{$postType}-height" ] ?? '';

					// Get label from post type or taxonomy with null safety
					$postTypeObj = get_post_type_object( $postType );
					$taxonomyObj = get_taxonomy( $postType );

					$title = $postType; // Default fallback
					if ( $postTypeObj instanceof WP_Post_Type && ! empty( $postTypeObj->labels->singular_name ) ) {
						$title = $postTypeObj->labels->singular_name;
					} elseif ( $taxonomyObj instanceof WP_Taxonomy && ! empty( $taxonomyObj->labels->singular_name ) ) {
						$title = $taxonomyObj->labels->singular_name;
					}

					?>
					<div class="section section-text cell">
						<span class="heading"><?php echo esc_html( $title ) . ' ( ' . esc_html( $postType ) . ' )'; ?></span>
						<div class="option inline-option">
							<div class="controls">
								<div class="inline-group">
									<label>
										<span><?php esc_html_e( 'Width:', HDA_TEXTDOMAIN ); ?></span>
										<input
											class="input"
											name="<?php echo esc_attr( $postType ); ?>-width"
											type="number"
											inputmode="numeric"
											size="3"
											min="0"
											max="100"
											value="<?php echo esc_attr( $width ); ?>">
									</label>
									<span>x</span>
									<label>
										<span><?php esc_html_e( 'Height:', HDA_TEXTDOMAIN ); ?></span>
										<input
											class="input"
											name="<?php echo esc_attr( $postType ); ?>-height"
											type="number"
											inputmode="numeric"
											size="3"
											min="0"
											max="100"
											value="<?php echo esc_attr( $height ); ?>">
									</label>
								</div>
							</div>
						</div>
						<div class="desc">
							<?php
							printf(
							/* translators: %s: Post type name */
								esc_html__( 'Apply a fixed aspect ratio to %s featured images. Leave empty to use default.', HDA_TEXTDOMAIN ),
								esc_html( ucfirst( $postType ) )
							);
							?>
						</div>
					</div>
				<?php endforeach;
			} ?>
		</div>
	</fieldset>
</div>
