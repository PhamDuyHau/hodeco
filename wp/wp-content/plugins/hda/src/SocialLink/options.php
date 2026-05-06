<?php

use HDAddons\Helper;
use HDAddons\SocialLink\SocialLink;

\defined( 'ABSPATH' ) || exit;

$socialOptions  = SocialLink::getOptions();
$socialLinks    = SocialLink::getFollowsLinks();

?>
<div class="container">
	<input type="hidden" name="social-link-hidden" value="1">

	<fieldset class="container-fieldset">
		<legend class="section-legend"><?php esc_html_e( 'Social Links', HDA_TEXTDOMAIN ); ?></legend>

		<div class="container flex flex-x gap sm-up-1 lg-up-2">
			<?php
			if ( empty( $socialLinks ) ) {
				echo '<div class="cell" style="width:100%"><p><b>' . esc_html__( 'No data available or configuration for this feature has not initialized yet', HDA_TEXTDOMAIN ) . '</b></p></div>';
			} else {
				foreach ( $socialLinks as $key => $social ) :
					if ( empty( $social['name'] ) || empty( $social['icon'] ) ) {
						continue;
					}

					$name        = $social['name'];
					$icon        = $social['icon'];
					$url         = $socialOptions[ $key ]['url'] ?? ( $social['url'] ?? '' );
					$placeholder = $social['placeholder'] ?? '';
					$iconHtml    = Helper::renderIcon( $icon, $name );
				?>
				<div class="cell section section-text">
					<span class="heading"><?php echo esc_html( $name ); ?></span>
					<div class="option">
						<div class="controls control-img">
							<label for="<?php echo esc_attr( $key ); ?>">
								<?php echo $iconHtml; ?>
							</label>
							<input class="input" type="url" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>-url" value="<?php echo esc_url( $url ); ?>" title="URL" placeholder="<?php echo esc_attr( $placeholder ); ?>">
						</div>
					</div>
				</div>
				<?php endforeach;
			} ?>
		</div>
	</fieldset>
</div>
