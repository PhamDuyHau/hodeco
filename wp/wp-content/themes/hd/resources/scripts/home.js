const style = document.createElement('style');
style.innerHTML = `
    @keyframes draw {
        to { stroke-dashoffset: 0; }
    }
    #logo-svg-target svg {
        width: 100%;
        height: auto;
        display: block;
    }
`;
document.head.appendChild(style);

/**
 * 2. NEWTECONS SVG DRAWING & PRELOADER
 */
function initSVGPreloader() {
    const preloader = document.getElementById('logo-preloader');
    const target = document.getElementById('logo-svg-target');
    if (!preloader || !target) return;

    const svgPath = themeUri + "/resources/img/svg/NEWTECONS.svg";

    fetch(svgPath)
        .then(res => res.text())
        .then(data => {
            target.innerHTML = data;
            
            // Target paths, polygons, and rects just in case
            const paths = target.querySelectorAll("path, polygon, rect, polyline");

            paths.forEach((path, i) => {
                const length = path.getTotalLength ? path.getTotalLength() : 1000;

                // Set up initial state (Invisible line, No fill)
                path.style.fillOpacity = "0";
                path.style.strokeDasharray = length;
                path.style.strokeDashoffset = length;
                
                // Set stroke color from the SVG itself or default black
                const color = path.getAttribute("fill") || "#000000";
                path.style.stroke = color;
                path.style.strokeWidth = "0.5"; // Thicker than 0.3 so you can actually see it
                path.style.strokeOpacity = "1";

                // Trigger the Drawing Animation using the Keyframes
                path.style.animation = `draw 3.5s ease forwards`;
                path.style.animationDelay = (i * 0.15) + "s";

                // Fade the Fill in AFTER the line is mostly drawn
                setTimeout(() => {
                    path.style.transition = "fill-opacity 1.2s ease";
                    path.style.fillOpacity = "1";
                }, 2500 + (i * 150));
            });

            // 3. REVEAL THE SITE
            // We wait until the longest staggered animation is done
            setTimeout(() => {
                preloader.style.transition = "opacity 1s cubic-bezier(0.4, 0, 0.2, 1)";
                preloader.style.opacity = "0";
                
                setTimeout(() => {
                    preloader.remove();
                    document.body.classList.add('site-revealed');
                    
                    // Trigger your other scripts (Counters, etc.)
                    if (typeof startCounters === 'function') startCounters();
                }, 1000);
            }, 7000); 
        })
        .catch(err => {
            console.error("SVG Load Failed:", err);
            if(preloader) preloader.remove();
        });
}

// Start it immediately
initSVGPreloader();

// Counter Animation
$(document).ready(function(){
  function animateCounter($el, target) {
    $({ countNum: 0 }).animate(
      { countNum: target },
      {
        duration: 2000,
        easing: 'swing',
        step: function() {
          $el.text(Math.floor(this.countNum).toLocaleString("de-DE"));
        },
        complete: function() {
          $el.text(this.countNum.toLocaleString("de-DE"));
        }
      }
    );
  }
  function startCounters() {
    $('.counter').each(function(){
      var $this = $(this);
      var target = parseInt($this.data('target'));
      if(!$this.hasClass('counted') && $(window).scrollTop() + $(window).height() > $this.offset().top){
        animateCounter($this, target);
        $this.addClass('counted');
      }
    });
  }
  $(window).on('scroll', startCounters);
  startCounters();
});
// FAQ
jQuery('.lists-faq .tab-title').click(function () {
  var $tabContent = jQuery(this).next('.tab-content');
  var $toggleItem = jQuery(this).closest('.toggle-item');
  // Toggle active class
  $toggleItem.toggleClass('active');
  if ($toggleItem.hasClass('active')) {
      var scrollHeight = $tabContent[0].scrollHeight;
      $tabContent.css('max-height', scrollHeight + 'px');
  } else {
      $tabContent.css('max-height', '0');
  }
});

// Home activity switch
jQuery(document).ready(function ($) {

	$('.activity-wrapper').each(function () {

		const $wrapper = $(this);

		$wrapper.find('.activity-btn').on('click', function () {
			const index = $(this).data('index');

			$wrapper.find('.activity-btn').removeClass('bg-black text-white');
			$(this).addClass('bg-black text-white');

			$wrapper.find('.activity-item').addClass('hidden');
			$wrapper.find('.activity-item[data-index="' + index + '"]').removeClass('hidden');
		});

	});

});