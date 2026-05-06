
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
      $wrapper.find('.activity-btn')
        .removeClass('bg-[var(--color-secondary)] border-[var(--color-secondary)] text-white');
            $(this).addClass('bg-[var(--color-secondary)] border-[var(--color-secondary)] text-white');
      $wrapper.find('.activity-item').addClass('hidden');
      $wrapper.find('.activity-item[data-index="' + index + '"]').removeClass('hidden');
    });

  });

});