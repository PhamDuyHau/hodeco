jQuery(document).ready(function($) {
  $("#ajax-select-tinh").on("change", function() {
    let termId = $(this).val();
    let container = $("#ajax-content");
    let loading = $("#loading-overlay");
    loading.removeClass("hidden");
    container.addClass("opacity-50");
    $.ajax({
      url: hdConfig.ajaxUrl,
      type: "POST",
      data: {
        action: "filter_branches",
        term_id: termId
      },
      success: function(response) {
        container.html(response);
        container.removeClass("opacity-50");
        loading.addClass("hidden");
      }
    });
  });
  $("#ajax-select-tinh").trigger("change");
});
//# sourceMappingURL=branch.CaDwhRbV.js.map
