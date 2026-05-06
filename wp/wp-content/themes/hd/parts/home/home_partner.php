<?php

defined('ABSPATH') || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if (! $acf_fc_layout) {
    return;
}

$heading_title = $args['heading_title'] ?? '';
$sub_title     = $args['sub_title'] ?? '';
$img_id        = $args['img'] ?? '';
$re_com        = $args['re_com'] ?? [];
?>

<section class="section home-partner relative pb-10">
    <div class="u-container">
        <div class="grid grid-cols-1 lg:grid-cols-[40%_60%] items-stretch rounded-[24px] overflow-hidden shadow-sm border border-gray-100">
            <div class="relative w-full aspect-[16/9] lg:aspect-[4/5] bg-gray-100">
                <?php if ( $img_id ) : ?>
                    <?= \HD_Helper::attachmentImageHTML( $img_id, 'full', [
                        'class' => 'absolute inset-0 w-full h-full object-cover'
                    ] ) ?>
                <?php endif; ?>
            </div>
            <div class="p-6 lg:p-14 flex flex-col justify-center text-white bg-primary bg-no-repeat bg-bottom bg-cover"
                 style="background-image: url('<?php echo get_template_directory_uri(); ?>/assets/img/bg_partner_content.png');">

                <?php if ( $sub_title ) : ?>
                    <div class="mb-4">
                        <span class="inline-flex items-center gap-2 px-4 py-2 border border-white/40 rounded-full text-[12px] lg:text-[14px]">
                            <span class="w-2 h-2 bg-secondary rounded-full"></span>
                            <?php echo $sub_title; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ( $heading_title ) : ?>
                    <h2 class="text-[26px] lg:text-[40px] font-semibold mb-6 lg:mb-8 leading-tight">
                        <?php echo $heading_title; ?>
                    </h2>
                <?php endif; ?>
                <?php if ( ! empty( $re_com ) ) : ?>
                    <?php
                    $data = [
                        'slidesPerView' => 2,
                        'spaceBetween'  => 20,
                        'navigation'    => true,
                        'pagination'    => true,
                        'grid' => [ 'rows' => 2, 'fill' => 'row' ],
                        'loop' => false,
                        'breakpoints' => [
                            0 => [
                                'slidesPerView' => 1,
                                'grid' => [ 'rows' => 1, 'fill' => 'row' ],
                            ],
                            768 => [
                                'slidesPerView' => 2,
                                'grid' => [ 'rows' => 2, 'fill' => 'row' ],
                            ]
                        ]
                    ];
                    $swiper_data = wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
                    ?>

                    <div class="swiper mt-6 w-full overflow-hidden" data-fx-slider>
                        <div class="swiper-wrapper" data-swiper-options="<?php echo esc_attr( $swiper_data ); ?>">

                            <?php foreach ( $re_com as $item ) : 
                                $logo_id    = $item['logo'] ?? '';
                                $phone      = $item['phone'] ?? '';
                                $email      = $item['email'] ?? '';
                                $web_link   = $item['web_link'] ?? '';
                                $bg_slider  = $item['bg_img_slider'] ?? '';
                            ?>

                                <div class="swiper-slide p-1">
                                    <div class="relative bg-white border border-white/20 rounded-2xl p-5 h-full overflow-hidden group">

                                        <?php if ( $bg_slider ) : ?>
                                            <div class="absolute inset-0 z-0 overflow-hidden">
                                                <?= \HD_Helper::attachmentImageHTML( $bg_slider, 'full', [
                                                    'class' => 'w-full h-full object-cover opacity-[0.2] grayscale group-hover:grayscale-0 transition-all duration-500'
                                                ] ) ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="relative z-10">
                                            <?php if ( $logo_id ) : ?>
                                                <div class="mb-4">
                                                    <?= \HD_Helper::attachmentImageHTML( $logo_id, 'full', [
                                                        'class' => 'h-9 w-auto object-contain'
                                                    ] ) ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="my-4 h-px bg-[#eeeeee]"></div>

                                            <div class="space-y-2 text-[14px] lg:text-[16px] font-semibold text-[#333333]">
                                                <?php if ( $phone ) : ?>
                                                    <p class="flex items-center gap-2">
                                                        <i class="fa-solid fa-phone text-secondary text-[12px]"></i>
                                                        <?php echo $phone; ?>
                                                    </p>
                                                <?php endif; ?>

                                                <?php if ( $email ) : ?>
                                                    <p class="flex items-center gap-2">
                                                        <i class="fa-solid fa-envelope text-secondary text-[12px]"></i>
                                                        <span class="truncate"><?php echo $email; ?></span>
                                                    </p>
                                                <?php endif; ?>

                                                <?php if ( $web_link ) : ?>
                                                    <a href="<?= esc_url( $web_link ) ?>" target="_blank" class="flex items-center gap-2 underline hover:text-secondary transition-colors">
                                                        <i class="fa-solid fa-globe text-secondary text-[12px]"></i>
                                                        Website
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>

                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>