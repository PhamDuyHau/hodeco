<?php
\defined('ABSPATH') || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if (!$acf_fc_layout) {
    return;
}

$heading_title = $args['heading_title'] ?? '';
$sub_title     = $args['sub_title'] ?? '';
$bg_img        = $args['bg_img'] ?? '';
$re_core       = $args['re_core'] ?? [];
?>

<section class="section about-card relative py-10 lg:py-16 bg-white overflow-hidden">

    <?php if ($bg_img) : ?>
        <div class="absolute left-0 top-0 h-full w-[300px] pointer-events-none">
            <?= \HD_Helper::attachmentImageHTML($bg_img, 'full', [
                'class' => 'w-full h-full object-contain'
            ]) ?>
        </div>
    <?php endif; ?>

    <div class="u-container relative">

        <?php
        if ($sub_title) {
            echo '<div class="mt-4 flex justify-center">';
            echo '<h3 class="inline-flex items-center gap-3 px-6 py-2 rounded-full border border-[#808080] font-medium text-[16px] leading-[24px] text-black">';
            echo '<span class="w-2.5 h-2.5 bg-secondary rounded-full shrink-0"></span>';
            echo '<span class="capitalize">' . $sub_title . '</span>';
            echo '</h3>';
            echo '</div>';
        }

        if ($heading_title) {
            echo '<h2 class="text-[32px] lg:text-[44px] font-semibold text-[#333333] text-center mt-4">';
            echo $heading_title;
            echo '</h2>';
        }
        ?>
        <div class="flex flex-wrap justify-center gap-6 lg:gap-8">

            <?php if (!empty($re_core)): ?>
                <?php foreach ($re_core as $item):

                    $item_title   = $item['title'] ?? '';
                    $item_content = $item['content'] ?? '';
                    $item_img     = $item['img'] ?? '';
                ?>
                    <div class="group relative w-full sm:w-[calc(50%-12px)] lg:w-[calc(33.33%-22px)] flex flex-col h-[500px] overflow-hidden rounded-2xl bg-white shadow-sm transition-all duration-300 hover:shadow-[0_20px_50px_rgba(0,0,0,0.15)]">
                        <div class="relative w-full aspect-[4/5] overflow-hidden">
                            <?php if ($item_img): ?>
                                <?= wp_get_attachment_image($item_img, 'large', false, [
                                    'class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-110'
                                ]) ?>
                            <?php endif; ?>
                            <div class="absolute inset-0 flex flex-col justify-end p-6 bg-black/60 translate-y-full transition-transform duration-300 group-hover:translate-y-0 text-white">
                                <h4 class="text-sm font-bold uppercase tracking-wider mb-2">
                                    <?= esc_html($item_title) ?>
                                </h4>
                                <div class="text-[15px] leading-relaxed opacity-90 line-clamp-4">
                                    <?= wp_kses_post($item_content) ?>
                                </div>
                            </div>
                        </div>
                        <div class="h-40 flex items-center justify-center bg-primary px-4 pt-4 pb-10 transition-colors duration-300 group-hover:bg-primary/90">
                            <h3 class="text-white font-semibold text-lg lg:text-xl text-center leading-tight">
                                <?= esc_html($item_title) ?>
                            </h3>
                        </div>
                        <div class="core-icon">
                            <div class="icon-inner">
                                <img src="<?= get_template_directory_uri(); ?>/resources/img/raimbow-icon.png" alt="icon">
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php endif; ?>

        </div>

    </div>
</section>