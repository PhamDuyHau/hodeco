<?php

defined( 'ABSPATH' ) || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if ( ! $acf_fc_layout ) {
    return;
}

$heading_title = $args['heading_title'] ?? '';
$content       = $args['content'] ?? '';
$img_id        = $args['img'] ?? '';
$bg_img_id     = $args['bg-img'] ?? '';
?>

<section class="section home-about relative pb-10 overflow-hidden lg:overflow-visible">
    <div class="u-container">
        <div class="wrapper relative w-full lg:w-[70%] mx-auto lg:translate-x-[80px] mt-20 lg:mt-0">

            <div class="content-box bg-white border border-[#ddd] rounded-[20px] my-10 lg:my-20 py-10 lg:py-15 pr-6 lg:pr-10 xl:pr-30 pl-6 lg:pl-[300px] relative flex flex-col items-center lg:block">
                <?php if ( $img_id ) : ?>
                    <div class="img relative lg:absolute mb-8 lg:mb-0 left-auto lg:left-0 top-auto lg:top-1/2 lg:-translate-y-1/2 lg:-translate-x-1/2 z-10 shrink-0">
                        <div class="w-[280px] h-[280px] md:w-[350px] md:h-[350px] lg:w-[450px] lg:h-[450px] rounded-full overflow-hidden shadow-lg border-[10px] border-white">
                            <?= \HD_Helper::attachmentImageHTML( $img_id, 'full', [
                                'class' => 'w-full h-full object-cover'
                            ] ) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="text-wrapper w-full relative z-10">

                    <?php if ( $heading_title ) : ?>
                        <h2 class="heading-title inline-flex items-center gap-3 px-6 py-2 rounded-full border border-[#808080] font-medium text-[18px] lg:text-[20px] leading-[26px] lg:leading-[30px] text-black mb-6">
                            <span class="w-2.5 h-2.5 bg-secondary rounded-full shrink-0"></span>
                            <span class="capitalize"><?php echo $heading_title; ?></span>
                        </h2>
                        <div class="mb-4 hidden lg:block">
                            <img 
                                src="<?php echo get_template_directory_uri(); ?>/assets/img/quote-left-solid.png" 
                                alt="quote"
                                class="w-8 h-auto"
                            >
                        </div>
                    <?php endif; ?>

                    <?php if ( $content ) : ?>
                        <div class="content font-medium text-[16px] leading-relaxed text-center lg:text-left text-[#444]">
                            <?php echo wp_kses_post( $content ); ?>
                        </div>
                    <?php endif; ?>

                </div>
                <?php if ( $bg_img_id ) : ?>
                    <div class="bg-img absolute bottom-0 right-0 z-0 pointer-events-none opacity-40 lg:opacity-80">
                        <?= \HD_Helper::attachmentImageHTML( $bg_img_id, 'full', [
                            'class' => 'w-auto h-auto max-w-[150px] lg:max-w-[300px] object-contain'
                        ] ) ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>