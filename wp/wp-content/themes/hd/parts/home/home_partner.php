<?php

\defined('ABSPATH') || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if (! $acf_fc_layout) {
    return;
}

$heading_title = $args['heading_title'] ?? '';
$sub_title     = $args['sub_title'] ?? '';
$img           = $args['img'] ?? '';
?>

<section class="section home-partner relative pb-10">

    <div class="container mx-auto px-8 lg:px-16 xl:px-24">

        <!-- SPLIT WRAPPER -->
        <div class="grid lg:grid-cols-[40%_60%] items-stretch rounded-[24px] overflow-hidden">

            <!-- LEFT IMAGE -->
            <div class="relative w-full aspect-[4/5] bg-gray-100">
                <?php
                if (!empty($img)) {
                    echo wp_get_attachment_image(
                        $img,
                        'full',
                        false,
                        [
                            'class' => 'absolute inset-0 w-full h-full object-cover'
                        ]
                    );
                }
                ?>
            </div>

            <!-- RIGHT CONTENT -->
            <div class="p-10 lg:p-14 flex flex-col justify-center bg-(--color-primary) text-white">

                <!-- SUB TITLE -->
                <?php if (!empty($sub_title)) { ?>
                    <div class="mb-4">
                        <span class="inline-flex items-center gap-2 px-4 py-2 border border-white/40 rounded-full text-[14px]">
                            <span class="w-2 h-2 bg-white rounded-full"></span>
                            <?php echo $sub_title; ?>
                        </span>
                    </div>
                <?php } ?>

                <!-- TITLE -->
                <?php if ($heading_title) { ?>
                    <h2 class="text-[28px] lg:text-[40px] font-semibold mb-8">
                        <?php echo $heading_title; ?>
                    </h2>
                <?php } ?>

                <!-- COMPANY LIST -->
                <?php if (!empty($args['re_com'])) { ?>

                    <?php
                    $data = [
                        'slidesPerView' => 2,
                        'spaceBetween' => 20,

                        // 🔥 THIS is the important part
                        'grid' => [
                            'rows' => 2,
                            'fill' => 'row'
                        ],

                        'loop' => false,

                        'breakpoints' => [
                            0 => [
                                'slidesPerView' => 1,
                                'grid' => [
                                    'rows' => 1,
                                    'fill' => 'row'
                                ],
                            ],
                            768 => [
                                'slidesPerView' => 2,
                                'grid' => [
                                    'rows' => 2,
                                    'fill' => 'row'
                                ],
                            ]
                        ]
                    ];

                    $swiper_data = wp_json_encode($data, JSON_UNESCAPED_UNICODE);
                    ?>

                    <div class="swiper mt-6 w-full overflow-hidden" data-fx-slider>

                        <div class="swiper-wrapper"
                            data-swiper-options="<?php echo esc_attr($swiper_data); ?>">

                            <?php foreach ($args['re_com'] as $item) {

                                $logo     = $item['logo'] ?? '';
                                $phone    = $item['phone'] ?? '';
                                $email    = $item['email'] ?? '';
                                $web_link = $item['web_link'] ?? '';
                            ?>

                                <div class="swiper-slide p-2">

                                    <div class="bg-white/10 border border-white/20 rounded-[16px] p-5 h-full">

                                        <?php if ($logo) { ?>
                                            <div class="mb-4">
                                                <?php echo wp_get_attachment_image($logo, 'full', false, [
                                                    'class' => 'h-10 w-auto object-contain'
                                                ]); ?>
                                            </div>
                                        <?php } ?>

                                        <div class="space-y-2 text-[14px]">

                                            <?php if ($phone) { ?>
                                                <p><span class="font-semibold">P:</span> <?php echo $phone; ?></p>
                                            <?php } ?>

                                            <?php if ($email) { ?>
                                                <p><span class="font-semibold">E:</span> <?php echo $email; ?></p>
                                            <?php } ?>

                                            <?php if ($web_link) { ?>
                                                <a href="<?php echo $web_link; ?>" target="_blank" class="underline">
                                                    Website
                                                </a>
                                            <?php } ?>

                                        </div>

                                    </div>

                                </div>

                            <?php } ?>

                        </div>

                    </div>

                <?php } ?>

            </div>

        </div>

    </div>

</section>