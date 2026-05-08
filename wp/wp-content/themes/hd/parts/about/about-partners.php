<?php
\defined('ABSPATH') || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if (!$acf_fc_layout) {
    return;
}

$heading_title = $args['heading_title'] ?? '';
$gallery_imgs  = $args['gallery_img'] ?? [];
?>

<section class="section about-partners relative py-10 lg:py-16 overflow-hidden">

    <div class="u-container relative">
        <?php if ($heading_title) : ?>
            <h2 class="text-[32px] lg:text-[44px] font-semibold text-[#333333] text-center mb-10 lg:mb-16">
                <?= esc_html($heading_title); ?>
            </h2>
        <?php endif; ?>

        <?php if (!empty($gallery_imgs)) : ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 lg:gap-8 items-center">
                <?php foreach ($gallery_imgs as $img_id) : ?>
                    <div class="partner-item flex justify-center items-center p-4 h-full grayscale hover:grayscale-0 transition-all duration-300">
                        <?= wp_get_attachment_image($img_id, 'full', false, [
                            'class' => 'max-w-full h-auto object-contain max-h-[60px]'
                        ]); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>