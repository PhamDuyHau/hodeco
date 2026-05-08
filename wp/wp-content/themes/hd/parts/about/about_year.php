<?php
defined('ABSPATH') || die;

$sub_title     = $args['sub_title'] ?? '';
$heading_title = $args['heading_title'] ?? '';
$content       = $args['content'] ?? '';
$bg_img        = $args['bg_img'] ?? '';
$re_dev        = $args['re_dev'] ?? [];

$data = [
    'loop'          => false,
    'slidesPerView' => 1,
    'spaceBetween'  => 30,
];

$swiper_data = wp_json_encode($data, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE);
?>

<section class="section about-year relative py-10 lg:py-20 overflow-hidden bg-primary text-white">

    <?php if ($bg_img) : ?>
        <div class="absolute right-0 top-0 h-full w-[500px] pointer-events-none">
            <?= \HD_Helper::attachmentImageHTML($bg_img, 'full', ['class' => 'w-full h-full object-contain']) ?>
        </div>
    <?php endif; ?>

    <div class="u-container relative z-10">
        <div class="mx-auto text-center mb-12 lg:mb-20">
            <?php if ($sub_title) : ?>
                <div class="year-sub-title flex items-baseline justify-center mb-6 overflow-visible">
                    <?= wp_kses_post($sub_title) ?>
                </div>
            <?php endif; ?>

            <?php if ($heading_title) : ?>
                <h2 class="text-[32px] lg:text-[44px] font-semibold mb-4"><?= esc_html($heading_title) ?></h2>
            <?php endif; ?>

            <?php if ($content) : ?>
                <div class="text-[18px] lg:text-[24px] opacity-90 max-w-3xl mx-auto"><?= wp_kses_post($content) ?></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($re_dev)) : ?>
            <div class="swiper no-progress" id="swiper-about-year" data-fx-slider>
                <div class="swiper-wrapper mb-10" data-swiper-options="<?php echo esc_attr($swiper_data); ?>">
                    <?php foreach ($re_dev as $index => $item) :
                        $img_dev = $item['img'] ?? '';
                        if (is_array($img_dev)) {
                            $img_dev = $img_dev['id'];
                        }
                        $year_dev  = $item['year'] ?? '';
                        $story_dev = $item['story'] ?? '';
                    ?>
                        <div class="swiper-slide h-auto">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16 items-center">
                                <div class="rounded-3xl overflow-hidden aspect-[4/3] bg-white/10 relative">
                                    <?php if ($img_dev) : ?>
                                        <?= wp_get_attachment_image($img_dev, 'full', false, ['class' => 'absolute inset-0 w-full h-full object-cover']); ?>
                                    <?php else : ?>
                                        <div class="w-full h-full flex items-center justify-center text-white/20 border border-white/10 rounded-3xl">
                                            <span>No Image</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-left">
                                    <h3 class="text-[48px] lg:text-[72px] font-bold mb-4"><?= esc_html($year_dev) ?></h3>
                                    <div class="text-[16px] lg:text-[18px] leading-relaxed opacity-80"><?= wp_kses_post($story_dev) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex flex-wrap justify-center gap-3 lg:gap-4 mt-10">
                <?php foreach ($re_dev as $index => $item) : 
                    // We use 'border-transparent' for inactive to avoid any weird double-border or solid-white ghosting
                    $is_active = ($index === 0);
                    $active_classes = $is_active 
                        ? 'bg-white text-primary border-white active' 
                        : 'bg-transparent text-white border-white/30';
                ?>
                    <button class="year-pill px-6 py-2 border rounded-full transition-all text-[14px] font-semibold cursor-pointer hover:bg-white hover:text-primary hover:border-white <?= $active_classes ?>" 
                            data-index="<?= $index; ?>">
                        <?= esc_html($item['year']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>