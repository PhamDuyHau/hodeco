<?php

defined('ABSPATH') || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if (! $acf_fc_layout) {
    return;
}

$heading_title = $args['heading_title'] ?? '';
$sub_title     = $args['sub_title'] ?? '';
$bg_img        = $args['bg_img'] ?? '';
$bg_img_1      = $args['bg_img_1'] ?? '';
$re_pro        = $args['re_pro'] ?? [];
?>

<section class="section home-project relative pb-10">

    <?php if ($bg_img) : ?>
        <div class="absolute bottom-0 -z-1 pointer-events-none">
            <?= \HD_Helper::attachmentImageHTML($bg_img, 'full', [
                'class' => 'w-full h-full object-contain'
            ]) ?>
        </div>
    <?php endif; ?>

    <?php if ($bg_img_1) : ?>
        <div class="absolute top-0 left-1/2 -translate-x-1/2 -z-1 pointer-events-none w-full">
            <?= \HD_Helper::attachmentImageHTML($bg_img_1, 'full', [
                'class' => 'mx-auto object-contain'
            ]) ?>
        </div>
    <?php endif; ?>

    <div class="container mx-auto px-8 lg:px-16 xl:px-24">

        <?php if ($sub_title) : ?>
            <div class="mt-4 flex justify-center">
                <h3 class="inline-flex items-center gap-3 px-6 py-2 rounded-full border border-[#808080] font-medium text-[16px] leading-[24px] text-black">
                    <span class="w-2.5 h-2.5 bg-secondary rounded-full shrink-0"></span>
                    <span class="capitalize"><?php echo esc_html($sub_title); ?></span>
                </h3>
            </div>
        <?php endif; ?>

        <?php if ($heading_title) : ?>
            <h2 class="text-[32px] lg:text-[44px] font-semibold text-[#333333] text-center mt-4">
                <?php echo esc_html($heading_title); ?>
            </h2>
        <?php endif; ?>

        <?php if (! empty($re_pro)) : ?>
            <div class="mt-12 space-y-10">

                <?php foreach ($re_pro as $item) :
                    $img     = $item['pro_img'] ?? '';
                    $title   = $item['pro_title'] ?? '';
                    $content = $item['content'] ?? '';
                    $link    = $item['link'] ?? '';
                ?>
                    <div class="px-0 lg:px-6">
                        <div class="relative w-full min-h-[450px] lg:h-[480px] rounded-3xl overflow-hidden shadow-lg flex items-center">

                            <?php if ($img) : ?>
                                <div class="absolute inset-0 z-0">
                                    <?= \HD_Helper::attachmentImageHTML($img, 'full', [
                                        'class' => 'w-full h-full object-cover'
                                    ]) ?>
                                </div>
                            <?php endif; ?>

                            <div class="relative lg:absolute inset-0 flex items-center w-full py-10 lg:py-0 z-10">
                                <div class="container mx-auto px-6 lg:px-16 xl:px-24">
                                    <div class="relative w-full lg:w-[40%]">

                                        <?php if ($link) : ?>
                                            <div class="relative p-6 lg:p-8 rounded-xl h-full flex flex-col">
                                                <div class="absolute inset-0 z-0">
                                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/bg-content-act.png"
                                                        class="w-full h-full object-fill" alt="background content">
                                                </div>
                                                <div class="relative z-10 flex flex-col h-full pt-10">
                                                    <?php if ($title) : ?>
                                                        <h3 class="text-xl lg:text-2xl font-semibold mb-3 pr-16 lg:pr-24 text-[#333333] leading-snug">
                                                            <?php echo esc_html($title); ?>
                                                        </h3>
                                                    <?php endif; ?>

                                                    <?php if ($content) : ?>
                                                        <div class="text-sm lg:text-base text-[#333333] leading-relaxed mb-4">
                                                            <?php echo wp_kses_post($content); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <a href="<?php echo esc_url($link); ?>"
                                                    class="absolute top-4 right-6 lg:right-8 z-20 w-12 h-12 lg:w-14 lg:h-14 rounded-full border-2 border-white bg-primary flex items-center justify-center text-white hover:scale-105 transition shadow-md">
                                                    <i class="fa-solid fa-arrow-right"></i>
                                                </a>
                                            </div>

                                        <?php else : ?>

                                            <div class="relative p-6 lg:p-8 rounded-xl h-full flex flex-col bg-primary shadow-lg">
                                                <div class="relative z-10 flex flex-col h-full pt-10">
                                                    <?php if ($title) : ?>
                                                        <h3 class="text-xl lg:text-2xl font-semibold mb-3 pr-16 lg:pr-24 text-white leading-snug">
                                                            <?php echo esc_html($title); ?>
                                                        </h3>
                                                    <?php endif; ?>

                                                    <?php if ($content) : ?>
                                                        <div class="text-sm lg:text-base text-white/90 leading-relaxed mb-4">
                                                            <?php echo wp_kses_post($content); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                        <?php endif; ?>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>

    </div>
</section>