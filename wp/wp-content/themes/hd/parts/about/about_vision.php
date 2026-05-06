<?php
defined('ABSPATH') || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if (! $acf_fc_layout) {
    return;
}

$heading_title = $args['heading_title'] ?? '';
$sub_title     = $args['sub_title'] ?? '';
$est_title     = $args['est_title'] ?? '';
$year_num      = $args['year_num'] ?? '';
$content       = $args['content'] ?? '';
$left_img      = $args['left_img'] ?? '';
$right_image   = $args['right_image'] ?? '';
?>

<section class="section about_vision relative py-10 lg:py-20">
    <div class="u-container">
        <div class="grid grid-cols-1 lg:grid-cols-3 items-end gap-10">
                        <div class="relative w-full aspect-[3/4] lg:aspect-[4/6]">
                <?php if ($left_img) : ?>
                    <div class="w-full h-full border-[10px] border-[#f1f1f1] rounded-[20px] overflow-hidden">
                        <?= \HD_Helper::attachmentImageHTML($left_img, 'full', ['class' => 'w-full h-full object-cover']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="content-center pb-5">
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

                <?php if ($content) : ?>
                    <div class="desc text-[15px] text-[#666] leading-relaxed">
                        <?php echo wp_kses_post($content); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="relative w-full">
                <div class="absolute top-0 right-0 -translate-y-[60%] z-20 flex items-end gap-4 pointer-events-none">
                    <?php if ($est_title) : ?>
                        <span class="lg:text-[20px] xl:text-[24px] text-[#333] font-semibold mb-4 lg:mb-12"><?php echo $est_title; ?></span>
                    <?php endif; ?>

                    <?php if ($year_num) : ?>
                        <span class="text-[50px] lg:text-[100px] xl:text-[150px] font-bold text-[#1a4b8c] leading-[0.8] tracking-tighter">
                            <?php echo $year_num; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="w-full aspect-[3/4] border-[10px] border-[#f1f1f1] rounded-[20px] overflow-hidden relative z-10">
                    <?php if ($right_image) : ?>
                        <?= \HD_Helper::attachmentImageHTML($right_image, 'full', ['class' => 'w-full h-full object-cover']) ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>