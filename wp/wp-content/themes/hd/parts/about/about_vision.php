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
                <?php if ($sub_title) : ?>
                    <div class="inline-flex items-center gap-2 px-4 py-1 rounded-full border border-[#ddd] mb-6">
                        <span class="w-2 h-2 bg-secondary rounded-full"></span>
                        <span class="text-[14px] font-medium"><?php echo $sub_title; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($heading_title) : ?>
                    <h2 class="text-[28px] lg:text-[40px] font-bold text-[#333] leading-tight mb-6"><?php echo $heading_title; ?></h2>
                <?php endif; ?>

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