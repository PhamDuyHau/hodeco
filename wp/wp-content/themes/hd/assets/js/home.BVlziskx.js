$(document).ready(function() {
  function animateCounter($el, target) {
    $({ countNum: 0 }).animate(
      { countNum: target },
      {
        duration: 2e3,
        easing: "swing",
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
    $(".counter").each(function() {
      var $this = $(this);
      var target = parseInt($this.data("target"));
      if (!$this.hasClass("counted") && $(window).scrollTop() + $(window).height() > $this.offset().top) {
        animateCounter($this, target);
        $this.addClass("counted");
      }
    });
  }
  $(window).on("scroll", startCounters);
  startCounters();
});
jQuery(".lists-faq .tab-title").click(function() {
  var $tabContent = jQuery(this).next(".tab-content");
  var $toggleItem = jQuery(this).closest(".toggle-item");
  $toggleItem.toggleClass("active");
  if ($toggleItem.hasClass("active")) {
    var scrollHeight = $tabContent[0].scrollHeight;
    $tabContent.css("max-height", scrollHeight + "px");
  } else {
    $tabContent.css("max-height", "0");
  }
});
jQuery(document).ready(function($2) {
  $2(".activity-wrapper").each(function() {
    const $wrapper = $2(this);
    $wrapper.find(".activity-btn").on("click", function() {
      const index = $2(this).data("index");
      $wrapper.find(".activity-btn").removeClass("bg-[var(--color-secondary)] border-[var(--color-secondary)] text-white");
      $2(this).addClass("bg-[var(--color-secondary)] border-[var(--color-secondary)] text-white");
      $wrapper.find(".activity-item").addClass("hidden");
      $wrapper.find('.activity-item[data-index="' + index + '"]').removeClass("hidden");
    });
  });
});
//# sourceMappingURL=home.BVlziskx.js.map
