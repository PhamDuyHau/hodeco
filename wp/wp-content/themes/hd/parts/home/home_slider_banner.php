<?php

defined('ABSPATH') || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if (! $acf_fc_layout) {
    return;
}

$slider_banner = $args['slider_banner'] ?? '';
$bg_img        = $args['bg_img'] ?? '';
$autoplay      = $args['autoplay'] ?? false;
$navigation    = $args['navigation'] ?? false;
?>

<section class="section home-slider relative before:content[''] before:absolute before:top-0 before:left-0 before:w-full before:h-full">
    <?php
    if ($bg_img) {
        $bg_url = wp_get_attachment_image_url($bg_img, 'full');

        echo '<style>
        .home-slider {
            background-image: url("' . esc_url($bg_url) . '");
            background-size: cover;
            background-position: center;
        }
    </style>';
    }
    ?>
    <?php
    $data = [
        'loop'          => true,
        'navigation'    => $navigation,
        'autoplay'      => $autoplay,
        'slidesPerView' => 'auto',
    ];

    $swiper_data = wp_json_encode($data, JSON_UNESCAPED_UNICODE);
    ?>

    <div class="swiper" data-fx-slider>

        <div class="swiper-wrapper" data-swiper-options="<?php echo esc_attr($swiper_data); ?>">

            <?php if (! empty($slider_banner)) : ?>
                <?php foreach ($slider_banner as $item) :

                    $sub_title     = $item['sub_title'] ?? '';
                    $heading_title = $item['heading_title'] ?? '';
                    $content       = $item['content'] ?? '';
                    $img           = $item['img'] ?? '';
                    $img_sub       = $item['img_sub'] ?? '';
                ?>
                    <div class="swiper-slide">
                        <div class="container xl:max-w-none xl:w-full xl:px-20 wrapper flex flex-col xl:flex-row items-center min-h-screen">
                            <div class="hidden xl:block xl:flex-1"></div>
                            <div class="col-center flex justify-center items-center order-1 xl:order-2 mt-20">
                                <?php if ($img) : ?>
                                    <div class="img w-full aspect-4/5 mx-auto">
                                        <?= \HD_Helper::attachmentImageHTML($img, 'full', [
                                            'class' => 'w-full h-full object-contain'
                                        ]) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-right flex-1 mb-3 ml-0 xl:ml-10 order-2 xl:order-3 w-full flex flex-col items-center xl:items-start text-center xl:text-left relative">
   <?php
                                if ($sub_title) {
                                    echo '<div class="sub-title w-fit flex flex-wrap gap-2 mb-4 items-center bg-white/30 text-[#412F07] text-[18px] lg:text-[24px] font-semibold rounded-lg p-[8px_16px]">';
                                    echo '<img src="' . THEME_URL . 'resources/img/ic-law.png" class="w-10 h-10 max-sm:w-8 max-sm:h-8 object-contain" alt="icon">';
                                    echo '<span>' . $sub_title . '</span>';
                                    echo '</div>';
                                }

                                if ($heading_title) {
                                    echo '<p class="title font-header text-white text-[24px] 2xl:text-[40px] xl:text-[30px] font-medium leading-snug mb-10">'
                                        . $heading_title .
                                        '</p>';
                                }
                                ?>

                                <div class="arrow-animate absolute top-[48%] -translate-y-[48%] left-0 md:left-[15%] lg:left-[28%] xl:left-[-5%]">
                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/banner-arrow.png"
                                        alt="arrow"
                                        class="w-auto h-auto">
                                </div>

                                <?php if ($content || $img_sub) : ?>
                                    <div class="flex items-start gap-4 ml-0 xl:ml-24">
                                        <?php if ($content) : ?>
                                            <div class="content text-white text-[16px] md:text-[18px] xl:text-[22px] 2xl:text-[28px] leading-[1.4] font-normal max-w-[520px]">
                                                <?php echo wp_kses_post($content); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($img_sub) : ?>
                                            <div class="sub-img shrink-0">
                                                <?= \HD_Helper::attachmentImageHTML($img_sub, 'full', [
                                                    'class' => 'w-auto h-auto object-contain'
                                                ]) ?>
                                            </div>
                                        <?php endif; ?>

                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>