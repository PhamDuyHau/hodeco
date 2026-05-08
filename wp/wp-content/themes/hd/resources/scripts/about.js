jQuery(document).ready(function ($) {
    const $container = $('.about-year');
    const $swiperEl = $('#swiper-about-year');
    const $pills = $('.year-pill');

    if (!$swiperEl.length || !$pills.length) return;

    function updatePillUI(index) {
        $pills.removeClass('bg-white text-primary border-white active')
              .addClass('bg-transparent text-white border-white/30');
                $pills.eq(index)
              .removeClass('bg-transparent text-white border-white/30')
              .addClass('bg-white text-primary border-white active');
    }

    const initSliderLogic = (swiper) => {
        updatePillUI(swiper.activeIndex);

        $pills.on('click', function (e) {
            e.preventDefault();
            const index = $(this).data('index');
            updatePillUI(index);
            swiper.slideTo(index);
        });

        swiper.on('slideChange', function () {
            updatePillUI(swiper.activeIndex);
        });
    };

    let swiperInstance = $swiperEl[0].swiper;
    if (swiperInstance) {
        initSliderLogic(swiperInstance);
    } else {
        const checkSwiper = setInterval(() => {
            swiperInstance = $swiperEl[0].swiper;
            if (swiperInstance) {
                initSliderLogic(swiperInstance);
                clearInterval(checkSwiper);
            }
        }, 50);
        setTimeout(() => clearInterval(checkSwiper), 2000);
    }
});