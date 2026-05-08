<?php
defined('ABSPATH') || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if (!$acf_fc_layout) {
    return;
}
$heading_title = $args['heading_title'] ?? '';
$content       = $args['content'] ?? '';
$bg_img        = $args['bg_img'] ?? '';
$title_dir_1   = $args['title_dir_1'] ?? '';
$re_dir_1      = $args['re_dir_1'] ?? [];
$title_dir_2   = $args['title_dir_2'] ?? '';
$re_dir_2      = $args['re_dir_2'] ?? [];
?>

<section class="section about-ceo relative py-10 lg:py-16overflow-hidden">

    <?php if ($bg_img) : ?>
        <div class="absolute left-0 top-0 h-full w-[300px] pointer-events-none opacity-30">
            <?= \HD_Helper::attachmentImageHTML($bg_img, 'full', [
                'class' => 'w-full h-full object-contain'
            ]) ?>
        </div>
    <?php endif; ?>

    <div class="u-container relative">
        <?php if ($heading_title || $content) : ?>
            <div class="max-w-4xl mx-auto text-center mb-12">
                <?php if ($heading_title) : ?>
                    <h2 class="text-[32px] lg:text-[44px] font-semibold text-[#333333] mb-4">
                        <?= esc_html($heading_title) ?>
                    </h2>
                <?php endif; ?>

                <?php if ($content) : ?>
                    <div class="text-[16px] text-gray-600 leading-relaxed">
                        <?= wp_kses_post($content) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php 
        $groups = [
            ['title' => $title_dir_1, 'data' => $re_dir_1],
            ['title' => $title_dir_2, 'data' => $re_dir_2]
        ];

        foreach ($groups as $group): 
            if (empty($group['data'])) continue; 
        ?>
            <div class="mb-16 last:mb-0">
                <?php if ($group['title']) : ?>
                    <h3 class="text-xl lg:text-2xl font-bold text-center mb-10 uppercase text-primary">
                        <?= esc_html($group['title']) ?>
                    </h3>
                <?php endif; ?>

                <div class="flex flex-wrap justify-center gap-6 lg:gap-8">
                    <?php foreach ($group['data'] as $item) : 
                        $img   = $item['img'] ?? '';
                        $name  = $item['name'] ?? '';
                        $role  = $item['role'] ?? '';
                    ?>
                        <div class="group relative w-full sm:w-[calc(50%-12px)] lg:w-[calc(25%-24px)] aspect-[4/5] overflow-hidden rounded-2xl shadow-lg bg-gray-900">
                            
                            <?php if ($img): ?>
                                <?= \HD_Helper::attachmentImageHTML($img, 'full', [
                                    'class' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110'
                                ]) ?>
                            <?php endif; ?>
                            
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent opacity-80 transition-opacity duration-300 group-hover:opacity-100"></div>

                            <div class="absolute bottom-0 left-0 w-full p-5 lg:p-6 text-white pointer-events-none">
                                <h4 class="text-lg lg:text-xl font-bold leading-tight mb-1">
                                    <?= esc_html($name) ?>
                                </h4>
                                <?php if ($role): ?>
                                    <p class="text-sm lg:text-[15px] opacity-80 font-normal">
                                        <?= esc_html($role) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
</section>