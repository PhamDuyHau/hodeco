<?php
\defined('ABSPATH') || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if (! $acf_fc_layout) {
    return;
}

$heading_title = $args['heading_title'] ?? '';
$sub_title     = $args['sub_title'] ?? '';
$img_id        = $args['img'] ?? '';
$content       = $args['content'] ?? '';
?>

<section class="section about-mission relative py-10 lg:py-16">
    <div class="u-container">
        <div class="mb-8 lg:mb-12">
            <?php
            if ($sub_title) {
                echo '<div class="flex justify-center mb-4">';
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
        </div>
        <div class="flex flex-col lg:flex-row lg:items-stretch gap-4 lg:gap-6">
            <div class="w-full lg:flex-1">
                <?php if ($content) : ?>
                    <div class="desc h-full rounded-xl p-6 lg:p-8 text-[16px] text-gray-700 leading-relaxed text-justify lg:text-left
                                border border-gray-300 shadow-sm bg-orange-50/30">
                        <?php echo wp_kses_post($content); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="w-full lg:w-[40%] shrink-0">
                <?php if ($img_id) : ?>
                    <div class="h-full rounded-2xl overflow-hidden shadow-sm about-mission-img">
                        <?= \HD_Helper::attachmentImageHTML($img_id, 'full', ['class' => 'w-full h-full object-cover']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>