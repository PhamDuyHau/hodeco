jQuery(function($) {
  const customSortingVars = window.customSortingVars || {};
  const nonce = customSortingVars.nonce || "";
  const ajaxUrl = customSortingVars.ajaxurl || window.ajaxurl;
  if (!nonce || !ajaxUrl) {
    console.warn("Custom Sorting: Nonce or Ajax URL not found. Sorting disabled.");
    return;
  }
  $("table.widefat tbody th, table.widefat tbody td").css("cursor", "move");
  const sortableHelper = function(event, ui) {
    ui.children("td, th").each(function() {
      $(this).width($(this).width());
    });
    return ui;
  };
  const sortableStart = function(event, ui) {
    ui.item.css({
      "background-color": "#ffffff",
      outline: "1px solid #dfdfdf"
    });
    ui.item.children("td, th").css("border-bottom-width", "0");
  };
  const sortableStop = function(event, ui) {
    ui.item.removeAttr("style");
    ui.item.children("td, th").css("border-bottom-width", "1px");
  };
  const sortableSort = function(event, ui) {
    ui.placeholder.find("td").each(function(key) {
      const helperTd = ui.helper.find("td").eq(key);
      $(this).toggle(helperTd.is(":visible"));
    });
  };
  const showSpinner = function(item) {
    item.find(".check-column input").hide().after('<span class="spinner is-active" style="margin: 0 0 0 6px; float: none;"></span>');
  };
  const hideSpinner = function(item) {
    item.find(".check-column input").show();
    item.find(".check-column .spinner").remove();
  };
  const showNotice = function(type, message) {
    const cssClass = "notice-error";
    const $notice = $('<div class="notice ' + cssClass + ' is-dismissible"><p>' + $("<span>").text(message).html() + "</p></div>");
    $notice.hide().prependTo(".wrap").slideDown(200);
    setTimeout(function() {
      $notice.slideUp(200, function() {
        $(this).remove();
      });
    }, 5e3);
  };
  const disableSortable = function() {
    $("table.widefat tbody th, table.widefat tbody td").css("cursor", "default");
    $("table.widefat tbody").sortable("disable");
  };
  const enableSortable = function() {
    $("table.widefat tbody th, table.widefat tbody td").css("cursor", "move");
    $("table.widefat tbody").sortable("enable");
  };
  const fixAlternateRows = function() {
    $("table.widefat tbody tr").each(function(index) {
      $(this).toggleClass("alternate", index % 2 === 0);
    });
  };
  const createUpdateHandler = function(action) {
    return function(event, ui) {
      disableSortable();
      showSpinner(ui.item);
      $.ajax({
        url: ajaxUrl,
        type: "POST",
        data: {
          action,
          order: $("#the-list").sortable("serialize"),
          nonce
        },
        success: function(response) {
          hideSpinner(ui.item);
          enableSortable();
          if (!response.success) {
            showNotice("error", response.data || "Sorting update failed.");
          }
        },
        error: function(xhr, status, error) {
          hideSpinner(ui.item);
          enableSortable();
          showNotice("error", "Connection error: " + (error || "Please try again."));
        }
      });
      fixAlternateRows();
    };
  };
  const sortableOptions = {
    items: "tr:not(.inline-edit-row)",
    cursor: "move",
    axis: "y",
    containment: "table.widefat",
    scrollSensitivity: 40,
    helper: sortableHelper,
    start: sortableStart,
    stop: sortableStop,
    sort: sortableSort
  };
  $("table.posts #the-list, table.pages #the-list").sortable({
    ...sortableOptions,
    update: createUpdateHandler("update-menu-order")
  });
  $("table.tags #the-list").sortable({
    ...sortableOptions,
    update: createUpdateHandler("update-menu-order-tags")
  });
});
//# sourceMappingURL=sorting.BGtc5XaP.js.map
