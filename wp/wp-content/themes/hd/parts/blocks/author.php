<?php

\defined( 'ABSPATH' ) || die;

$author_id = get_the_author_meta( 'ID' );
if ( empty( $author_id ) ) {
	return;
}

$ACF    = \HD_Helper::getFields( 'user_' . $author_id );
$name   = ! empty( $ACF['author_alt_name'] ) ? $ACF['author_alt_name'] : get_the_author_meta( 'display_name', $author_id );
$job    = ! empty( $ACF['author_alt_job'] ) ? $ACF['author_alt_job'] : '';
$avatar = ! empty( $ACF['author_alt_profile_picture'] ) ? $ACF['author_alt_profile_picture'] : 0;
$desc   = ! empty( $ACF['author_alt_biographical_info'] ) ? $ACF['author_alt_biographical_info'] : get_the_author_meta( 'description', $author_id );
$social = ! empty( $ACF['author_alt_social_info'] ) ? $ACF['author_alt_social_info'] : [];

$avatar_url   = ! empty( $avatar ) ? wp_get_attachment_image_url( $avatar, 'medium' ) : get_avatar_url( $author_id );
$social_links = [];
if ( $social ) {
	foreach ( $social as $item ) {
		if ( ! empty( $item['social_url']['url'] ) ) {
			$social_links[] = esc_url( $item['social_url']['url'] );
		}
	}
}

$schema = [
	'@context'         => 'https://schema.org',
	'@type'            => 'Person',
	'name'             => $name,
	'description'      => wp_strip_all_tags( $desc ),
	'image'            => $avatar_url,
	'url'              => get_author_posts_url( $author_id ),
	'mainEntityOfPage' => [
		'@type' => 'WebPage',
		'@id'   => get_permalink(),
	],
];

if ( $job ) {
	$schema['jobTitle'] = $job;
}

if ( ! empty( $social_links ) ) {
	$schema['sameAs'] = $social_links;
}

$schema_json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

?>
<section class="section section-author mt-5 max-lg:mb-6">
	<div
		class="author-meta flex flex-row max-lg:flex-wrap items-center gap-5 lg:gap-6 p-5 lg:p-6 bg-white/10 rounded-xl backdrop-blur-[30px] shadow-[0_2px_15px_0_#0000001A]">
		<div class="w-2/5 lg:w-1/5 author-avatar">
			<span class="aspect-square rounded-full u-flex-center c-light-button overflow-hidden">
				<?php
				echo ! empty( $avatar )
						? \HD_Helper::attachmentImageHTML( $avatar, 'medium', [ 'class' => 'object-contain object-center block max-w-full max-h-full !rounded-none' ] )
						: get_avatar( $author_id )
				?>
			</span>
		</div>
		<div class="w-full lg:w-4/5 author-info flex flex-col flex-nowrap justify-around">
			<div class="flex flex-col flex-nowrap">
				<div class="flex items-start justify-between max-sm:flex-col max-sm:mb-3">
					<div
						class="relative mb-4 pb-4 after:content[''] after:absolute after:bottom-1 after:left-0 after:w-16 after:h-1 after:bg-[linear-gradient(90deg,#968968_0%,#C4BBA8_100%)] after:rounded">
						<p class="mb-1"><?php echo __( 'Tác giả', TEXT_DOMAIN ); ?></p>
						<?php echo $name ? '<a class="name uppercase font-body text-lg xl:text-2xl leading-normal text[#243F40] font-medium mb-0" href="' . get_author_posts_url( $author_id ) . '" aria-label="' . $name . '">' . $name . '</a>' : ''; ?>
						<?php echo $job ? '<p class="job text-sm xl:text-base mt-1">' . $job . '</p>' : ''; ?>
					</div>
					<?php if ( $social ) : ?>
					<ul class="author-social !list-none flex flex-wrap items-center !pl-0 !mb-0 gap-2">
						<?php
						foreach ( $social as $item ) :
							$social_name = $item['social_name'] ?? '';
							$social_url  = $item['social_url'] ?? [];
							?>
						<li class="mb-0">
							<?php echo \HD_Helper::ACFLinkOpen( $social_url, 'flex items-center justify-center w-9 h-9 rounded-full bg-[#F6F6F6] transition-all translate-y-0 hover:-translate-y-[2px]' ); ?>
							<?php echo \hd_svg( $social_name ); ?>
							<span class="sr-only"><?php echo $social_name; ?></span>
							<?php echo \HD_Helper::ACFLinkClose( $social_url ); ?>
						</li>
						<?php endforeach; ?>
					</ul>
					<?php endif; ?>
				</div>
				<div class="author-desc text-justify"><?php echo $desc; ?></div>
			</div>
		</div>
	</div>
	<script type="application/ld+json">
	<?php echo $schema_json; ?>
	</script>
</section>