<?php

\defined('ABSPATH') || die;

$acf_fc_layout = $args['acf_fc_layout'] ?? '';
if (! $acf_fc_layout) {
    return;
}

$heading_title = $args['heading_title'] ?? '';
$bg_img = $args['bg_img'] ?? '';
$sub_title     = $args['sub_title'] ?? '';
?>
<section class="section home-activity relative pb-10">
    <?php if (!empty($bg_img)) { ?>
        <div class="absolute top-0 right-0 -z-1 pointer-events-none">
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

        <?php if (!empty($args['re_activity'])) { ?>
            <div class="activity-wrapper mt-10">

                <!-- BUTTONS -->
                <div class="flex flex-wrap justify-center gap-3 mb-10">
                    <?php foreach ($args['re_activity'] as $index => $item) { ?>
                        <button
                            class="activity-btn px-5 py-2 border border-[#808080] rounded-full text-[16px] font-medium transition-all <?php echo $index === 0 ? 'bg-black text-white' : ''; ?>"
                            data-index="<?php echo $index; ?>">
                            <?php echo $item['button']; ?>
                        </button>
                    <?php } ?>
                </div>

                <div class="activity-content relative mt-10">
                    <?php foreach ($args['re_activity'] as $index => $item) { ?>
                        <div class="activity-item <?php echo $index === 0 ? '' : 'hidden'; ?>" data-index="<?php echo $index; ?>">

                            <div class="grid lg:grid-cols-[60%_40%] gap-0 lg:items-center">

                                <!-- IMAGE COLUMN -->
                                <div class="w-full relative z-10 lg:translate-x-[15px]">
                                    <?php
                                    if (!empty($item['act_img'])) {
                                        echo wp_get_attachment_image(
                                            $item['act_img'],
                                            'full',
                                            false,
                                            [
                                                'class' => 'w-full h-auto object-cover rounded-[20px] shadow-md'
                                            ]
                                        );
                                    }
                                    ?>
                                </div>

                                <div class="p-8 lg:p-12 z-2 relative border border-gray-200 rounded-[20px] bg-white flex flex-col lg:-translate-x-[15px] pb-[100px]">

                                    <?php if (!empty($item['act_title'])) { ?>
                                        <h2 class="text-[24px] lg:text-[28px] font-semibold mb-4 text-black">
                                            <?php echo $item['act_title']; ?>
                                        </h2>
                                    <?php } ?>

                                    <?php if (!empty($item['content'])) { ?>
                                        <div class="text-[16px] leading-[26px] text-gray-700 mb-8">
                                            <?php echo $item['content']; ?>
                                        </div>
                                    <?php } ?>

                                    <?php if (!empty($item['re_project'])) { ?>
                                        <div class="grid grid-cols-2 lg:grid-cols-3 gap-y-6 gap-x-10 mb-8">
                                            <?php foreach ($item['re_project'] as $project) { ?>
                                                <div class="project-item flex flex-col items-start text-left">
                                                    <?php if (!empty($project['icon_img'])) {
                                                        echo wp_get_attachment_image($project['icon_img'], 'full', false, ['class' => 'w-[40px] h-[40px] object-contain mb-3']);
                                                    } ?>
                                                    <div class="text-[22px] font-semibold text-black"><?php echo $project['number'] ?? ''; ?></div>
                                                    <div class="w-[40px] h-[2px] bg-black my-2"></div>
                                                    <div class="text-[14px] text-gray-600"><?php echo $project['pro_title'] ?? ''; ?></div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>

                                    <div class="absolute bottom-0 left-0 right-0 pr-[110px] pb-6 flex items-center gap-3">

                                        <div class="flex-1 h-px bg-gray-200"></div>

                                        <span class="text-[14px] font-bold text-gray-500 uppercase tracking-widest whitespace-nowrap">
                                            Xem thêm
                                        </span>

                                        <div class="custom-corner-wrapper absolute bottom-[-1px] right-[-1px] w-[100px] h-[100px] bg-white border-l border-t border-gray-200 flex items-center justify-center z-10 rounded-tl-[40px]">
                                            <a href="#" class="w-[65px] h-[65px] bg-[#0E4087] rounded-full flex items-center justify-center text-white text-[24px] transition hover:bg-[#092c5c] hover:scale-105">
                                                <i class="fa-solid fa-angle-right"></i>
                                            </a>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

            </div>
        <?php } ?>
    </div>
</section>