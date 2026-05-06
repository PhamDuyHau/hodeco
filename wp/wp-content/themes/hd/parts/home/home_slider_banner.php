<?php

\defined('ABSPATH') || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if (! $acf_fc_layout) {
    return;
}

$slider_banner = $args['slider_banner'] ?? '';
$bg_img         = $args['bg_img'] ?? '';
$autoplay       = $args['autoplay'] ?? false;
$navigation     = $args['navigation'] ?? false;
?>

<section
    class="section home-slider relative before:content[''] before:absolute before:top-0 before:left-0 before:w-full before:h-full">
    <?php if ($bg_img) {
        $bg_url = wp_get_attachment_image_url($bg_img, 'full');
    ?>
        <style>
            .home-slider {
                background-image: url('<?php echo esc_url($bg_url); ?>');
                background-size: cover;
                background-position: center;
            }
        </style>
    <?php } ?>

    <?php
    $data = [
        'loop'          => true,
        'navigation'    => $navigation,
        'autoplay'      => $autoplay,
        'slidesPerView' => 'auto',
    ];

    $swiper_data = wp_json_encode(
        $data,
        JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE
    );
    ?>
    <div class="swiper" data-fx-slider>
        <div class="swiper-wrapper" data-swiper-options="<?php echo esc_attr($swiper_data); ?>">
            <?php
            if (! empty($slider_banner)) {
                foreach ($slider_banner as $item) {
                    $sub_title     = $item['sub_title'] ?? '';
                    $heading_title = $item['heading_title'] ?? '';
                    $content       = $item['content'] ?? '';
                    $btn_link      = $item['btn_link'] ?? '';
                    $btn_name      = $item['btn_name'] ?? '';
                    $img           = $item['img'] ?? '';
                    $link_contact  = $item['link_contact'] ?? '';
            ?>
                    <div class="swiper-slide">
                        <div class="container xl:max-w-none xl:w-full xl:px-20 wrapper flex flex-col xl:flex-row items-center min-h-screen">
                            <div class="hidden xl:block xl:flex-1"></div>
                            <div class="col-center flex justify-center items-center order-1 xl:order-2 mt-20">
                                <?php if ($img) { ?>
                                    <div class="img w-full aspect-4/5 mx-auto">
                                        <?php echo wp_get_attachment_image($img, 'full', false, [
                                            'class' => 'w-full h-full object-contain'
                                        ]); ?>
                                    </div>

                                <?php } ?>
                            </div>

                            <div class="col-right flex-1 mb-3 ml-0 xl:ml-10 order-2 xl:order-3 w-full flex flex-col items-center xl:items-start text-center xl:text-left">
                                <?php if ($sub_title) { ?>
                                    <div class="sub-title w-fit flex flex-wrap gap-2 mb-4 items-center bg-white/30 text-[#412F07] text-[18px] lg:text-[24px] font-semibold rounded-lg p-[8px_16px]">
                                        <img src="<?php echo THEME_URL . 'resources/img/ic-law.png'; ?>" alt="icon" class="w-10 h-10 max-sm:w-8 max-sm:h-8 object-contain">
                                        <span><?php echo $sub_title; ?></span>
                                    </div>
                                <?php } ?>

                                <?php if ($heading_title) { ?>
                                    <p class="title font-header text-white text-[24px] 2xl:text-[40px] xl:text-[30px] font-medium leading-snug mb-10">
                                        <?php echo $heading_title; ?>
                                    </p>
                                <?php } ?>

                                <div class="arrow-animate">
                                    <img
                                        src="<?php echo get_template_directory_uri() . '/assets/img/banner-arrow.png'; ?>"
                                        alt="arrow"
                                        class="w-auto h-auto">
                                </div>

                                <?php if ($content || $item['img_sub']) { ?>
                                    <div class="flex items-start gap-4 ml-0 xl:ml-24">
                                        <?php if ($content) { ?>
                                            <div class="content text-white text-[16px] md:text-[18px] xl:text-[22px] 2xl:text-[28px] leading-[1.4] font-normal max-w-[520px]">
                                                <?php echo $content; ?>
                                            </div>
                                        <?php } ?>

                                        <?php if (! empty($item['img_sub'])) { ?>
                                            <div class="sub-img shrink-0">
                                                <?php echo wp_get_attachment_image($item['img_sub'], 'full', false, [
                                                    'class' => 'w-auto h-auto object-contain'
                                                ]); ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                            </div>

                        </div>

                    </div>
            <?php
                }
            }
            ?>
        </div>
    </div>
</section>