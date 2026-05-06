import "./jquery-plugins.Cyn_OIe4.js";
function initContactLinkRepeater($) {
  const $container = $("#contact-link-items");
  if (!$container.length) return;
  $container.data("default") || {};
  const i18n = window.hdaContactLinkI18n || {
    newContact: "New Contact",
    remove: "Remove",
    selectIcon: "Select Icon",
    useThisIcon: "Use this icon",
    atLeastOne: "You must have at least one contact link."
  };
  $container.sortable({
    handle: ".drag-handle",
    placeholder: "repeater-item ui-sortable-placeholder",
    update: function() {
      updateItemOrders();
    }
  });
  $container.on("click", ".toggle-item, .item-title", function(e) {
    e.stopPropagation();
    const $item = $(this).closest(".repeater-item");
    $item.toggleClass("collapsed");
  });
  $container.on("input", ".item-name", function() {
    const $item = $(this).closest(".repeater-item");
    const name = $(this).val() || i18n.newContact;
    $item.find(".item-title").text(name);
  });
  $container.on("click", ".remove-item", function(e) {
    e.stopPropagation();
    const $item = $(this).closest(".repeater-item");
    if ($container.find(".repeater-item").length > 1) {
      $item.slideUp(200, function() {
        $(this).remove();
        updateItemOrders();
      });
    } else {
      alert(i18n.atLeastOne);
    }
  });
  $("#add-contact-item").on("click", function() {
    const index = $container.find(".repeater-item").length;
    const id = generateUUID();
    const $newItem = createItemHTML(index, id, i18n);
    $container.append($newItem);
    $newItem.hide().slideDown(200);
    $("html, body").animate(
      {
        scrollTop: $newItem.offset().top - 100
      },
      300
    );
  });
  function updateItemOrders() {
    $container.find(".repeater-item").each(function(index) {
      $(this).attr("data-index", index);
      $(this).find(".item-order").val(index);
      $(this).find('[name^="contact_items["]').each(function() {
        const name = $(this).attr("name");
        const newName = name.replace(/contact_items\[\d+\]/, "contact_items[" + index + "]");
        $(this).attr("name", newName);
        const id = $(this).attr("id");
        if (id) {
          const newId = id.replace(/_\d+$/, "_" + index);
          $(this).attr("id", newId);
        }
      });
      $(this).find("label[for]").each(function() {
        const forAttr = $(this).attr("for");
        const newFor = forAttr.replace(/_\d+$/, "_" + index);
        $(this).attr("for", newFor);
      });
    });
  }
  function generateUUID() {
    return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, function(c) {
      const r = Math.random() * 16 | 0;
      const v = c === "x" ? r : r & 3 | 8;
      return v.toString(16);
    });
  }
  function createItemHTML(index, id, i18n2) {
    return $(`
                <div class="repeater-item" data-index="${index}">
                    <div class="repeater-item-header">
                        <span class="drag-handle dashicons dashicons-move"></span>
                        <span class="item-title">${i18n2.newContact}</span>
                        <button type="button" class="toggle-item" aria-expanded="true">
                            <span class="dashicons dashicons-arrow-up-alt2"></span>
                        </button>
                        <button type="button" class="remove-item" title="${i18n2.remove}">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>

                    <div class="repeater-item-content">
                        <input type="hidden" name="contact_items[${index}][id]" value="${id}">
                        <input type="hidden" name="contact_items[${index}][order]" value="${index}" class="item-order">

                        <div class="field-row field-icon">
                            <label>${i18n2.icon || "Icon"}</label>
                            <div class="hda-media-upload" data-title="${i18n2.selectIcon}" data-button="${i18n2.useThisIcon}" data-library="image,image/svg+xml">
                                <div class="hda-media-preview empty">
                                    <span class="dashicons dashicons-format-image"></span>
                                </div>
                                <input type="hidden" name="contact_items[${index}][icon]" value="" class="hda-media-value">
                                <div style="display:flex;gap:6px;margin-top:6px;">
                                    <button type="button" class="button js-media-select">${i18n2.selectIcon}</button>
                                    <button type="button" class="button js-media-remove hidden">${i18n2.remove}</button>
                                </div>
                            </div>
                            <p class="field-desc">${i18n2.iconDesc || "Select an image or SVG from the media library."}</p>
                        </div>

                        <div class="field-row">
                            <label for="contact_name_${index}">${i18n2.name || "Name"}</label>
                            <input type="text" id="contact_name_${index}" name="contact_items[${index}][name]" value="" class="regular-text item-name" placeholder="${i18n2.namePlaceholder || "e.g., Hotline, Zalo, Facebook"}">
                        </div>

                        <div class="field-row">
                            <label for="contact_value_${index}">${i18n2.linkValue || "Link/Value"}</label>
                            <input type="text" id="contact_value_${index}" name="contact_items[${index}][value]" value="" class="regular-text" placeholder="${i18n2.valuePlaceholder || "e.g., tel:+84123456789, https://zalo.me/..."}">
                        </div>

                        <div class="field-row field-row-inline">
                            <div class="field-col">
                                <label for="contact_target_${index}">${i18n2.target || "Target"}</label>
                                <select id="contact_target_${index}" name="contact_items[${index}][target]">
                                    <option value="_blank">${i18n2.targetBlank || "New Tab (_blank)"}</option>
                                    <option value="_self">${i18n2.targetSelf || "Same Tab (_self)"}</option>
                                </select>
                            </div>

                            <div class="field-col">
                                <label for="contact_class_${index}">${i18n2.cssClass || "CSS Class"}</label>
                                <input type="text" id="contact_class_${index}" name="contact_items[${index}][class]" value="" class="regular-text" placeholder="${i18n2.classPlaceholder || "e.g., hotline"}">
                            </div>

                            <div class="field-col">
                                <label for="contact_color_${index}">${i18n2.color || "Color"}</label>
                                <input type="text" id="contact_color_${index}" name="contact_items[${index}][color]" value="" class="hda-color-field" placeholder="#000000">
                            </div>
                        </div>
                    </div>
                </div>
            `);
  }
}
function initEditorToggle() {
  const blockEditorToggle = document.getElementById("use_block_editor_for_post_type_off");
  const blockEditorDependents = document.querySelectorAll(".block-editor-dependent");
  if (!blockEditorToggle || !blockEditorDependents.length) return;
  blockEditorToggle.addEventListener("change", function() {
    blockEditorDependents.forEach((el) => {
      el.classList.toggle("hidden", this.checked);
    });
  });
}
function initMediaUpload($) {
  $(document).on("click", ".js-media-select", function(e) {
    e.preventDefault();
    const $wrapper = $(this).closest(".hda-media-upload");
    const $input = $wrapper.find(".hda-media-value");
    const $preview = $wrapper.find(".hda-media-preview");
    const $removeBtn = $wrapper.find(".js-media-remove");
    const title = $wrapper.data("title") || "Select Image";
    const buttonText = $wrapper.data("button") || "Use this image";
    const previewSize = $wrapper.data("preview") || "medium";
    let libraryType = "image";
    const rawLibrary = $wrapper.data("library");
    if (rawLibrary) {
      const types = String(rawLibrary).split(",").map((s) => s.trim()).filter(Boolean);
      libraryType = types.length > 1 ? types : types[0] || "image";
    }
    const frame = wp.media({
      title,
      button: { text: buttonText },
      multiple: false,
      library: { type: libraryType }
    });
    frame.on("select", function() {
      var _a, _b, _c, _d, _e, _f;
      const attachment = frame.state().get("selection").first().toJSON();
      $input.val(attachment.id);
      const url = ((_b = (_a = attachment.sizes) == null ? void 0 : _a[previewSize]) == null ? void 0 : _b.url) || ((_d = (_c = attachment.sizes) == null ? void 0 : _c.medium) == null ? void 0 : _d.url) || ((_f = (_e = attachment.sizes) == null ? void 0 : _e.thumbnail) == null ? void 0 : _f.url) || attachment.url;
      $preview.html('<img src="' + url + '" alt="preview">').removeClass("empty");
      $removeBtn.removeClass("hidden");
    });
    frame.open();
  });
  $(document).on("click", ".js-media-remove", function(e) {
    e.preventDefault();
    const $wrapper = $(this).closest(".hda-media-upload");
    $wrapper.find(".hda-media-value").val("");
    $wrapper.find(".hda-media-preview").html('<span class="dashicons dashicons-format-image"></span>').addClass("empty");
    $(this).addClass("hidden");
  });
}
jQuery(function($) {
  $(document).on("click", ".notice-dismiss", function() {
    var _a;
    (_a = $(this).closest(".notice.is-dismissible")) == null ? void 0 : _a.fadeOutAndRemove(500);
  });
  initContactLinkRepeater($);
  initEditorToggle();
  initMediaUpload($);
});
//# sourceMappingURL=admin.BgdJb5Eu.js.map
