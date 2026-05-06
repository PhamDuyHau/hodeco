<?php

defined('ABSPATH') || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if (! $acf_fc_layout) {
    return;
}

$heading_title = $args['heading_title'] ?? '';
$sub_title     = $args['sub_title'] ?? '';
$bg_img        = $args['bg_img'] ?? '';
$re_pro        = $args['re_pro'] ?? [];
?>

<section class="section home-project relative pb-10">

    <?php if (!empty($bg_img)) { ?>
        <div class="absolute bottom-0 -z-1 pointer-events-none">
            <?php echo wp_get_attachment_image(
                $bg_img,
                'full',
                false,
                [
                    'class' => 'w-full h-full object-contain'
                ]
            ); ?>
        </div>
    <?php } ?>

    <div class="container mx-auto px-8 lg:px-16 xl:px-24">
        <?php if (!empty($sub_title)) { ?>
            <div class="mt-4 flex justify-center">
                <h3 class="inline-flex items-center gap-3 px-6 py-2 rounded-full border border-[#808080] font-medium text-[16px] leading-[24px] text-black">
                    <span class="w-2.5 h-2.5 bg-red-500 rounded-full shrink-0"></span>
                    <span class="capitalize">
                        <?php echo $sub_title; ?>
                    </span>
                </h3>
            </div>
        <?php } ?>

        <?php if ($heading_title) { ?>
            <h2 class="text-[32px] lg:text-[44px] font-semibold text-[#333333] text-center mt-4">
                <?php echo $heading_title; ?>
            </h2>
        <?php } ?>
        <?php if (!empty($re_pro)) { ?>
            <div class="mt-12 space-y-10">

                <?php foreach ($re_pro as $item) :

                    $img     = $item['pro_img'] ?? '';
                    $title   = $item['pro_title'] ?? '';
                    $content = $item['content'] ?? '';
                    $link    = $item['link'] ?? '';
                ?>
                    <div class="px-6">
                        <div class="relative w-full h-[380px] rounded-xl ">
                            <?php if (!empty($img)) { ?>
                                <div class="absolute inset-0">
                                    <?php echo wp_get_attachment_image(
                                        $img,
                                        'full',
                                        false,
                                        [
                                            'class' => 'w-full h-full object-cover'
                                        ]
                                    ); ?>
                                </div>
                            <?php } ?>

                            <div class="absolute left-20 top-1/2 -translate-y-1/2 w-[30%] overflow-visible">

                                <?php if (!empty($link)) : ?>
                                    <div class="relative p-6 rounded-xl h-full flex flex-col">
                                        <!-- Background Image -->
                                        <div class="absolute inset-0 z-0">
                                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/bg-content-act.png"
                                                class="w-full h-full object-fill" alt="" />
                                        </div>

                                        <div class="relative z-1 flex flex-col h-full">
                                            <?php if (!empty($title)) : ?>
                                                <h3 class="text-xl font-semibold mb-3 pt-10 pr-24 text-[#333333] leading-snug">
                                                    <?php echo esc_html($title); ?>
                                                </h3>
                                            <?php endif; ?>

                                            <?php if (!empty($content)) : ?>
                                                <div class="text-sm text-[#333333] leading-relaxed mb-4 flex-grow">
                                                    <?php echo wp_kses_post($content); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <a href="<?php echo esc_url($link); ?>"
                                            class="absolute top-3 right-8 z-20 w-14 h-14 rounded-full border-2 border-white bg-(--color-primary) flex items-center justify-center text-white hover:scale-105 transition shadow-sm">
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    </div>

                                <?php else : ?>
                                    <div class="relative p-6 rounded-xl h-full flex flex-col bg-(--color-primary) shadow-lg">

                                        <div class="relative z-1 flex flex-col h-full">
                                            <?php if (!empty($title)) : ?>
                                                <h3 class="text-xl font-semibold mb-3 pt-10 pr-24 text-white leading-snug">
                                                    <?php echo esc_html($title); ?>
                                                </h3>
                                            <?php endif; ?>

                                            <?php if (!empty($content)) : ?>
                                                <div class="text-sm text-white/90 leading-relaxed mb-4 flex-grow">
                                                    <?php echo wp_kses_post($content); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>
        <?php } ?>
    </div>
</section>