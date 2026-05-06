<?php
/**
 * Displays navigation mobile
 *
 * @author HD
 */

\defined( 'ABSPATH' ) || die;

$txt_logo = \HD_Helper::getOption( 'blogname' );
$img_logo = \HD_Helper::getThemeMod( 'custom_logo' );

if ( ! $img_logo ) :
	$html = sprintf(
		'<a href="%1$s" class="mobile-logo-link" rel="home" aria-label="%2$s">%3$s</a>',
		\HD_Helper::home(),
		\HD_Helper::escAttr( $txt_logo ),
		$txt_logo
	);
else :
	$image = \HD_Helper::iconImageHTML( $img_logo, 'medium', [ 'loading' => 'eager' ] );
	$html  = sprintf(
		'<a href="%1$s" class="mobile-logo-link" rel="home">%2$s</a>',
		\HD_Helper::home(),
		$image
	);
endif;

$position = \HD_Helper::getThemeMod( 'offcanvas_menu_setting' );
if ( ! in_array( $position, [ 'left', 'right', 'top', 'bottom' ], true ) ) {
	$position = 'left';
}
$m_hotline = \HD_Helper::getField( 'm_hotline', 'option' );
?>
<div class="off-canvas invisible will-change-transform backface-hidden fixed bg-white is-transition-overlap position-<?php echo $position; ?>"
	id="offCanvasMenu" data-fx-off-canvas data-content-scroll="true">
	<div class="menu-heading-outer">
		<button class="menu-lines absolute top-4 right-4 block opacity-0 p-0 w-6 h-6" aria-label="Close" type="button"
			data-close>
			<span class="line line-1 block w-6 h-0.5 rounded-none"></span>
			<span class="line line-2 block w-6 h-0.5 rounded-none -mt-0.5"></span>
		</button>
		<div class="title-bar-title relative my-5 mx-4 w-42.5 max-w-[70%]"><?php echo $html; ?></div>
	</div>
	<div class="menu-outer">
		<?php
		echo \HD_Helper::doShortcode( 'inline_search', [ 'class' => 'px-4 py-3' ] );
		echo \HD_Helper::doShortcode( 'vertical_menu', [ 'extra_class' => 'relative h-full overflow-hidden p-5 gap-5 flex flex-col flex-nowrap' ] );
		if ( $m_hotline ) {
			echo '<a href="tel:' . $m_hotline . '" class="header-hotline flex items-center gap-2 px-4 mt-1 text-[#6f551c]">
            <img src="' . THEME_URL . 'resources/img/ic-hotline.png" alt="Hotline" class="icon w-[35px] h-[35px]" />
            <div class="hotline-info">
            <span class="label block font-medium text-xs">Hotline:</span>
            <span class="value block font-extrabold">' . $m_hotline . '</span>
            </div></a>';
		}
		?>
	</div>
</div>