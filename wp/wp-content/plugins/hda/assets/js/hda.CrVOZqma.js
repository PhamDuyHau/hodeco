import { s as select2 } from "./vendor.iJdduJUa.js";
import "./jquery-plugins.Cyn_OIe4.js";
function initCodeMirror() {
  if (typeof codemirror_settings === "undefined") return;
  const codemirrorCss = document.querySelectorAll(".codemirror_css");
  const codemirrorHtml = document.querySelectorAll(".codemirror_html");
  function initialize(elements, settings) {
    elements.forEach((el) => {
      if (!el.CodeMirror) {
        const editorSettings = settings ? { ...settings } : {};
        editorSettings.codemirror = {
          ...editorSettings.codemirror,
          indentUnit: 3,
          tabSize: 3,
          autoRefresh: true
        };
        el.CodeMirror = wp.codeEditor.initialize(el, editorSettings);
      }
    });
  }
  initialize(codemirrorCss, codemirror_settings.codemirror_css);
  initialize(codemirrorHtml, codemirror_settings.codemirror_html);
}
function isValidEmail(email) {
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailPattern.test(email);
}
function isValidIPRange(range) {
  const ipPattern = /^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})$/;
  const rangePattern = /^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})-(\\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])$/;
  const cidrPattern = /^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\/(\\d|[1-2]\d|3[0-2])$/;
  if (ipPattern.test(range)) {
    return true;
  }
  if (rangePattern.test(range)) {
    const [startIP, endRange] = range.split("-");
    const endIP = startIP.split(".").slice(0, 3).join(".") + "." + endRange;
    return compareIPs(startIP, endIP) < 0;
  }
  return cidrPattern.test(range);
}
function compareIPs(ip1, ip2) {
  const ip1Parts = ip1.split(".").map(Number);
  const ip2Parts = ip2.split(".").map(Number);
  for (let i = 0; i < 4; i++) {
    if (ip1Parts[i] < ip2Parts[i]) return -1;
    if (ip1Parts[i] > ip2Parts[i]) return 1;
  }
  return 0;
}
function initSelect2($2) {
  $2(".select2-multiple").each(function() {
    $2(this).select2({
      multiple: true,
      allowClear: true,
      width: "resolve",
      dropdownAutoWidth: true,
      placeholder: $2(this).attr("placeholder")
    });
  });
  $2(".select2-tags").each(function() {
    $2(this).select2({
      multiple: true,
      tags: true,
      allowClear: true,
      width: "resolve",
      dropdownAutoWidth: true,
      placeholder: $2(this).attr("placeholder")
    });
  });
  $2(".select2-ips").each(function() {
    $2(this).select2({
      multiple: true,
      tags: true,
      allowClear: true,
      width: "resolve",
      dropdownAutoWidth: true,
      placeholder: $2(this).attr("placeholder"),
      createTag: function(params) {
        const term = params.term.trim();
        if (isValidIPRange(term)) {
          return { id: term, text: term };
        }
        return null;
      }
    });
  });
  $2(".select2-emails").each(function() {
    $2(this).select2({
      multiple: true,
      tags: true,
      allowClear: true,
      width: "resolve",
      dropdownAutoWidth: true,
      placeholder: $2(this).attr("placeholder"),
      createTag: function(params) {
        const term = params.term.trim();
        if (isValidEmail(term)) {
          return { id: term, text: term };
        }
        return null;
      }
    });
  });
}
function initOtpSettings() {
  const otpModeRadios = document.querySelectorAll('input[name="otp_mode"]');
  const otpGatewaySelect = document.getElementById("otp_gateway");
  if (!otpModeRadios.length) return;
  const smsOnlyElements = document.querySelectorAll(".otp-sms-only");
  const enabledOnlyElements = document.querySelectorAll(".otp-enabled-only");
  const gatewayConfigs = document.querySelectorAll(".otp-gateway-config");
  function updateVisibility() {
    var _a;
    const selectedMode = ((_a = document.querySelector('input[name="otp_mode"]:checked')) == null ? void 0 : _a.value) || "disabled";
    const selectedGateway = (otpGatewaySelect == null ? void 0 : otpGatewaySelect.value) || "telegram";
    const isOtp = ["email", "sms", "totp"].includes(selectedMode);
    smsOnlyElements.forEach((el) => {
      el.style.display = selectedMode === "sms" ? "" : "none";
    });
    enabledOnlyElements.forEach((el) => {
      el.style.display = isOtp || selectedMode === "magic_link" ? "" : "none";
    });
    gatewayConfigs.forEach((el) => {
      const isCurrentGateway = el.classList.contains("gateway-" + selectedGateway);
      el.style.display = selectedMode === "sms" && isCurrentGateway ? "" : "none";
    });
  }
  otpModeRadios.forEach((radio) => radio.addEventListener("change", updateVisibility));
  otpGatewaySelect == null ? void 0 : otpGatewaySelect.addEventListener("change", updateVisibility);
  updateVisibility();
}
function initRedirectRepeater() {
  const tbody = document.getElementById("hda-redirect-rules");
  const tableWrap = document.getElementById("hda-redirect-table-wrap");
  const addBtn = document.getElementById("hda-redirect-add");
  const emptyMsg = document.getElementById("hda-redirect-empty");
  const selectAllCb = document.getElementById("hda-redirect-select-all");
  const deleteSelectedBtn = document.getElementById("hda-redirect-delete-selected");
  const deleteAllBtn = document.getElementById("hda-redirect-delete-all");
  const searchInput = document.getElementById("hda-redirect-search");
  const importBtn = document.getElementById("hda-redirect-import-btn");
  const importFile = document.getElementById("hda-redirect-import-file");
  const importMode = document.getElementById("hda-redirect-import-mode");
  const importStatus = document.getElementById("hda-redirect-import-status");
  const config = window.hdaRedirect || {};
  const nonce = config.nonce || "";
  const i18n = config.i18n || {};
  if (!tbody) return;
  tbody.addEventListener(
    "blur",
    (e) => {
      const input = e.target;
      if (input.name !== "redirect_from[]") return;
      const val = (input.value || "").trim();
      clearDupeWarning(input);
      if (!val) return;
      const row = input.closest(".hda-redirect-row");
      const allFromInputs = tbody.querySelectorAll('[name="redirect_from[]"]');
      let clientDupe = false;
      const normalized = val.toLowerCase().replace(/\/+$/, "");
      allFromInputs.forEach((other) => {
        if (other === input) return;
        const otherVal = (other.value || "").trim().toLowerCase().replace(/\/+$/, "");
        if (otherVal === normalized) clientDupe = true;
      });
      if (clientDupe) {
        showDupeWarning(input, "Duplicate — this path already exists on this page.");
        return;
      }
      const fd = new FormData();
      fd.append("action", "hda_redirect_check_dupe");
      fd.append("_nonce", nonce);
      fd.append("from", val);
      if (row && row.dataset.origFrom) {
        const origNorm = row.dataset.origFrom.toLowerCase().replace(/\/+$/, "");
        if (origNorm === normalized) return;
      }
      fetch(ajaxurl, { method: "POST", body: fd }).then((r) => r.json()).then((data) => {
        if (data.success && data.data.exists) {
          showDupeWarning(input, `Duplicate — already redirects to: ${data.data.existing_to}`);
        }
      }).catch(() => {
      });
    },
    true
  );
  function showDupeWarning(input, message) {
    clearDupeWarning(input);
    const warn = document.createElement("small");
    warn.className = "hda-redirect-dupe-warn";
    warn.textContent = message;
    input.parentNode.appendChild(warn);
    input.classList.add("hda-redirect-input--dupe");
  }
  function clearDupeWarning(input) {
    var _a;
    const existing = (_a = input.parentNode) == null ? void 0 : _a.querySelector(".hda-redirect-dupe-warn");
    if (existing) existing.remove();
    input.classList.remove("hda-redirect-input--dupe");
  }
  tbody.addEventListener("click", (e) => {
    const editBtn = e.target.closest(".hda-redirect-edit");
    if (!editBtn) return;
    const row = editBtn.closest(".hda-redirect-row");
    if (!row) return;
    const isEditing = row.classList.contains("hda-redirect-row--editing");
    if (isEditing) {
      cancelEdit(row);
    } else {
      enterEdit(row);
    }
  });
  function enterEdit(row) {
    var _a, _b, _c;
    row.classList.add("hda-redirect-row--editing");
    row.querySelectorAll(".hda-redirect-input").forEach((input) => {
      input.removeAttribute("readonly");
    });
    const sel = row.querySelector(".hda-redirect-select");
    if (sel) {
      sel.disabled = false;
      sel.onchange = () => {
        const hidden = row.querySelector(".hda-redirect-type-hidden");
        if (hidden) hidden.value = sel.value;
      };
    }
    row.dataset.origFrom = ((_a = row.querySelector('[name="redirect_from[]"]')) == null ? void 0 : _a.value) || "";
    row.dataset.origTo = ((_b = row.querySelector('[name="redirect_to[]"]')) == null ? void 0 : _b.value) || "";
    row.dataset.origType = ((_c = row.querySelector(".hda-redirect-type-hidden")) == null ? void 0 : _c.value) || "301";
    const editBtn = row.querySelector(".hda-redirect-edit");
    if (editBtn) {
      editBtn.title = "Cancel";
      const icon = editBtn.querySelector(".dashicons");
      if (icon) {
        icon.classList.remove("dashicons-edit");
        icon.classList.add("dashicons-no-alt");
      }
    }
  }
  function cancelEdit(row) {
    row.classList.remove("hda-redirect-row--editing");
    const fromInput = row.querySelector('[name="redirect_from[]"]');
    const toInput = row.querySelector('[name="redirect_to[]"]');
    const typeSelect = row.querySelector('[name="redirect_type[]"]');
    if (fromInput) {
      fromInput.value = row.dataset.origFrom || "";
      fromInput.setAttribute("readonly", "");
    }
    if (toInput) {
      toInput.value = row.dataset.origTo || "";
      toInput.setAttribute("readonly", "");
    }
    if (typeSelect) {
      typeSelect.value = row.dataset.origType || "301";
      typeSelect.disabled = true;
      typeSelect.onchange = null;
    }
    const hiddenType = row.querySelector(".hda-redirect-type-hidden");
    if (hiddenType) hiddenType.value = row.dataset.origType || "301";
    updateDisplaySpans(row);
    const editBtn = row.querySelector(".hda-redirect-edit");
    if (editBtn) {
      editBtn.title = "Edit";
      const icon = editBtn.querySelector(".dashicons");
      if (icon) {
        icon.classList.remove("dashicons-no-alt");
        icon.classList.add("dashicons-edit");
      }
    }
  }
  function updateDisplaySpans(row) {
    const spans = row.querySelectorAll(".hda-redirect-display");
    const fromInput = row.querySelector('[name="redirect_from[]"]');
    const toInput = row.querySelector('[name="redirect_to[]"]');
    const hiddenType = row.querySelector(".hda-redirect-type-hidden");
    if (spans[0] && fromInput) spans[0].textContent = fromInput.value;
    if (spans[1] && toInput) spans[1].textContent = toInput.value;
    if (spans[2] && hiddenType) spans[2].textContent = hiddenType.value;
  }
  if (addBtn) {
    addBtn.addEventListener("click", () => {
      var _a;
      if (emptyMsg) emptyMsg.remove();
      if (tableWrap) tableWrap.style.display = "";
      const rowNum = tbody.querySelectorAll(".hda-redirect-row").length + 1;
      const tr = document.createElement("tr");
      tr.className = "hda-redirect-row hda-redirect-row--new hda-redirect-row--editing";
      tr.innerHTML = `
				<td class="hda-redirect-table__cb"><input type="checkbox" class="hda-redirect-cb"></td>
				<td class="hda-redirect-table__num">${rowNum}</td>
				<td>
					<span class="hda-redirect-display"></span>
					<input type="text" class="input hda-redirect-input" name="redirect_from[]" placeholder="/old-page">
				</td>
				<td>
					<span class="hda-redirect-display"></span>
					<input type="url" class="input hda-redirect-input" name="redirect_to[]" placeholder="https://example.com/new-page">
				</td>
				<td>
					<span class="hda-redirect-display">301</span>
					<input type="hidden" name="redirect_type[]" value="301" class="hda-redirect-type-hidden">
					<select class="select hda-redirect-select" data-name="redirect_type">
						<option value="301">301</option>
						<option value="302">302</option>
					</select>
				</td>
				<td class="hda-redirect-table__actions-cell">
					<button type="button" class="button button-small hda-redirect-edit" title="Cancel">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
					<button type="button" class="button button-small hda-redirect-remove" title="Delete">
						<span class="dashicons dashicons-trash"></span>
					</button>
				</td>
			`;
      const newSelect = tr.querySelector(".hda-redirect-select");
      const newHidden = tr.querySelector(".hda-redirect-type-hidden");
      if (newSelect && newHidden) {
        newSelect.onchange = () => {
          newHidden.value = newSelect.value;
        };
      }
      tbody.appendChild(tr);
      (_a = tr.querySelector('input[name="redirect_from[]"]')) == null ? void 0 : _a.focus();
    });
  }
  tbody.addEventListener("click", (e) => {
    const removeBtn = e.target.closest(".hda-redirect-remove");
    if (!removeBtn) return;
    const row = removeBtn.closest(".hda-redirect-row");
    if (!row) return;
    const idx = row.dataset.index;
    if (idx === void 0 || idx === "") {
      fadeOutRow(row);
      return;
    }
    if (!confirm("Delete this redirect rule?")) return;
    removeBtn.disabled = true;
    ajaxDeleteByIndices([parseInt(idx, 10)], [row]);
  });
  function fadeOutRow(row) {
    row.style.transition = "opacity 0.25s ease";
    row.style.opacity = "0";
    setTimeout(() => {
      row.remove();
      renumberRows();
      updateBulkUI();
    }, 250);
  }
  function ajaxDeleteByIndices(indices, rows) {
    const fd = new FormData();
    fd.append("action", "hda_redirect_delete");
    fd.append("_nonce", nonce);
    indices.forEach((idx) => fd.append("indices[]", idx));
    fetch(ajaxurl, { method: "POST", body: fd }).then((r) => r.json()).then((data) => {
      var _a;
      if (data.success) {
        rows.forEach((row) => fadeOutRow(row));
        setTimeout(() => window.location.reload(), 800);
      } else {
        alert(((_a = data.data) == null ? void 0 : _a.message) || "Error");
      }
    }).catch(() => alert("Request failed."));
  }
  if (selectAllCb) {
    selectAllCb.addEventListener("change", () => {
      const checked = selectAllCb.checked;
      tbody.querySelectorAll(".hda-redirect-cb").forEach((cb) => {
        const row = cb.closest(".hda-redirect-row");
        if (row && row.style.display !== "none") {
          cb.checked = checked;
        }
      });
      updateBulkUI();
    });
  }
  tbody.addEventListener("change", (e) => {
    if (e.target.classList.contains("hda-redirect-cb")) {
      updateBulkUI();
    }
  });
  function getCheckedRows() {
    return [...tbody.querySelectorAll(".hda-redirect-cb:checked")].map((cb) => cb.closest(".hda-redirect-row"));
  }
  function updateBulkUI() {
    const checked = getCheckedRows();
    if (deleteSelectedBtn) {
      deleteSelectedBtn.style.display = checked.length > 0 ? "" : "none";
    }
  }
  if (deleteSelectedBtn) {
    deleteSelectedBtn.addEventListener("click", () => {
      const rows = getCheckedRows();
      if (!rows.length) return;
      if (!confirm(`Delete ${rows.length} selected rule(s)?`)) return;
      const indices = rows.map((row) => row.dataset.index).filter((idx) => idx !== void 0 && idx !== "").map((idx) => parseInt(idx, 10));
      if (indices.length > 0) {
        ajaxDeleteByIndices(indices, rows);
      } else {
        rows.forEach((row) => row.remove());
        renumberRows();
        updateBulkUI();
      }
      if (selectAllCb) selectAllCb.checked = false;
    });
  }
  if (deleteAllBtn) {
    deleteAllBtn.addEventListener("click", () => {
      const rows = tbody.querySelectorAll(".hda-redirect-row");
      if (!rows.length) return;
      if (!confirm("Delete ALL redirect rules? This cannot be undone.")) return;
      const fd = new FormData();
      fd.append("action", "hda_redirect_delete_all");
      fd.append("_nonce", nonce);
      fetch(ajaxurl, { method: "POST", body: fd }).then((r) => r.json()).then((data) => {
        var _a;
        if (data.success) {
          rows.forEach((row) => fadeOutRow(row));
          setTimeout(() => window.location.reload(), 800);
        } else {
          alert(((_a = data.data) == null ? void 0 : _a.message) || "Error");
        }
      }).catch(() => alert("Request failed."));
    });
  }
  if (searchInput) {
    searchInput.addEventListener("input", () => {
      const query = searchInput.value.toLowerCase().trim();
      const rows = tbody.querySelectorAll(".hda-redirect-row");
      rows.forEach((row) => {
        var _a, _b, _c;
        if (!query) {
          row.style.display = "";
          return;
        }
        const from = (((_a = row.querySelector('[name="redirect_from[]"]')) == null ? void 0 : _a.value) || "").toLowerCase();
        const to = (((_b = row.querySelector('[name="redirect_to[]"]')) == null ? void 0 : _b.value) || "").toLowerCase();
        const type = (((_c = row.querySelector('[name="redirect_type[]"]')) == null ? void 0 : _c.value) || "").toLowerCase();
        const match = from.includes(query) || to.includes(query) || type.includes(query);
        row.style.display = match ? "" : "none";
      });
    });
  }
  function renumberRows() {
    const rows = tbody.querySelectorAll(".hda-redirect-row");
    rows.forEach((row, i) => {
      const numCell = row.querySelector(".hda-redirect-table__num");
      if (numCell) numCell.textContent = i + 1;
    });
  }
  if (importBtn && importFile) {
    importBtn.addEventListener("click", () => {
      importFile.click();
    });
    importFile.addEventListener("change", () => {
      var _a;
      const file = (_a = importFile.files) == null ? void 0 : _a[0];
      if (!file) return;
      const mode = (importMode == null ? void 0 : importMode.value) || "append";
      if (mode === "replace" && !confirm(i18n.confirm_replace || "Replace all existing rules?")) {
        importFile.value = "";
        return;
      }
      const fd = new FormData();
      fd.append("action", "hda_redirect_import");
      fd.append("_nonce", nonce);
      fd.append("import_file", file);
      fd.append("import_mode", mode);
      showImportStatus(i18n.importing || "Importing...", "#0073aa");
      importBtn.disabled = true;
      fetch(ajaxurl, { method: "POST", body: fd }).then((r) => r.json()).then((data) => {
        var _a2, _b;
        if (data.success) {
          let msg = `✅ ${data.data.message}`;
          if ((_a2 = data.data.errors) == null ? void 0 : _a2.length) {
            msg += `<br><small style="color:#d63638;">${data.data.errors.join("<br>")}</small>`;
          }
          showImportStatus(msg, "#46b450");
          setTimeout(() => window.location.reload(), 1500);
        } else {
          showImportStatus(`❌ ${((_b = data.data) == null ? void 0 : _b.message) || "Error"}`, "#d63638");
          importBtn.disabled = false;
        }
      }).catch(() => {
        showImportStatus("❌ Request failed", "#d63638");
        importBtn.disabled = false;
      });
      importFile.value = "";
    });
  }
  function showImportStatus(html, color) {
    if (!importStatus) return;
    importStatus.innerHTML = html;
    importStatus.style.color = color || "#666";
    importStatus.style.display = "";
  }
}
function initSettingsForm($2) {
  $2(document).on("submit", "#_settings_form", function(e) {
    e.preventDefault();
    const $this = $2(this);
    const $data = $this.serializeObject();
    const btnSubmit = $this.find('button[name="_submit_settings"]');
    const buttonText = btnSubmit.html();
    const buttonTextLoading = '<span class="ajax-loader">&nbsp;</span>';
    btnSubmit.prop("disabled", true).html(buttonTextLoading);
    $2.ajax({
      type: "POST",
      url: ajaxurl,
      dataType: "json",
      data: {
        action: "submit_settings",
        _data: $data,
        _ajax_nonce: $this.find('input[name="_wpnonce"]').val(),
        _wp_http_referer: $this.find('input[name="_wp_http_referer"]').val()
      }
    }).done(function(response) {
      var _a;
      if ((_a = response == null ? void 0 : response.data) == null ? void 0 : _a.message) {
        showToast($2, response.data.message, response.data.type || "success", response.data.autoHide);
      }
      const hash = window.location.hash;
      const shouldReload = !hash || hash === "#global_setting_settings" || hash === "#custom_css_settings" || hash === "#custom_script_settings" || hash === "#custom_sorting_settings" && $data["order_reset"] !== void 0 || hash === "#base_slug_settings" && $data["base_slug_reset"] !== void 0;
      if (shouldReload) {
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      }
    }).fail(function(xhr) {
      var _a;
      let errorMsg = "An error occurred";
      try {
        const response = JSON.parse(xhr.responseText);
        if ((_a = response == null ? void 0 : response.data) == null ? void 0 : _a.message) {
          errorMsg = response.data.message;
        }
      } catch (e2) {
      }
      showToast($2, errorMsg, "error", false);
    }).always(function() {
      btnSubmit.prop("disabled", false).html(buttonText);
    });
  });
}
function showToast($2, message, type = "success", autoHide = true) {
  let $container = $2("#hda-toast-container");
  if (!$container.length) {
    $container = $2('<div id="hda-toast-container"></div>').appendTo("body");
  }
  const iconSuccess = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
  const iconError = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
  const icon = type === "success" ? iconSuccess : iconError;
  const safeType = type === "error" ? "error" : "success";
  const $toast = $2("<div>", { class: `hda-toast hda-toast--${safeType}` }).append($2("<span>", { class: "hda-toast__icon" }).html(icon)).append($2("<span>", { class: "hda-toast__message" }).text(message)).append(
    $2("<button>", { type: "button", class: "hda-toast__close", "aria-label": "Close" }).html(
      '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>'
    )
  );
  $container.append($toast);
  requestAnimationFrame(() => {
    $toast.addClass("hda-toast--show");
  });
  $toast.find(".hda-toast__close").on("click", function() {
    removeToast($toast);
  });
  if (autoHide) {
    setTimeout(() => {
      removeToast($toast);
    }, 4e3);
  }
}
function removeToast($toast) {
  $toast.removeClass("hda-toast--show");
  setTimeout(() => {
    $toast.remove();
  }, 300);
}
function initFilterTabs($2) {
  const $filterTabs = $2(".filter-tabs");
  $filterTabs.each(function() {
    const $el = $2(this);
    const $nav = $el.find(".tabs-nav");
    const $content = $el.find(".tabs-content");
    const $tabs = $nav.find("a");
    const initialHash = window.location.hash;
    const activateTab = (hash) => {
      const $tab = $nav.find(`a[href="${hash}"]`);
      $nav.find("a").removeClass("current");
      $content.find(".tabs-panel").hide();
      if ($tab.length) {
        $tab.addClass("current");
        $2(hash).show();
      } else {
        $tabs.first().addClass("current");
        $content.find(".tabs-panel").first().show();
        window.history.replaceState(null, null, window.location.pathname + window.location.search);
      }
    };
    activateTab(initialHash || $tabs.first().attr("href"));
    $nav.on("click", "a", function(e) {
      e.preventDefault();
      const hash = $2(this).attr("href");
      window.location.hash = hash;
      activateTab(hash);
      $2("html, body").animate({ scrollTop: $el.offset().top - $2("header").outerHeight() }, 300);
    });
    $2(window).on("hashchange", function() {
      activateTab(window.location.hash || $tabs.first().attr("href"));
    });
  });
}
function initWaf() {
  const select = document.getElementById("hda-country-select");
  const addBtn = document.getElementById("hda-add-country-btn");
  const list = document.getElementById("hda-blocked-list");
  if (!select || !addBtn || !list) return;
  const TEXT = {
    block_selected: {
      heading: "Blocked Countries",
      placeholder: "Select a country to block...",
      btn: "Add to Blocklist",
      empty: "No countries blocked."
    },
    allow_selected: {
      heading: "Allowed Countries",
      placeholder: "Select a country to allow...",
      btn: "Add to Allowlist",
      empty: "No countries in allowlist. All traffic will be blocked!"
    }
  };
  function getMode() {
    const checked = document.querySelector('input[name="country_mode"]:checked');
    return checked ? checked.value : "block_selected";
  }
  function updateModeText() {
    const mode = getMode();
    const t = TEXT[mode] || TEXT.block_selected;
    const heading = document.getElementById("hda-country-heading");
    const placeholder = document.getElementById("hda-country-placeholder");
    const btnText = document.getElementById("hda-country-btn-text");
    const emptyMsg = list.querySelector(".empty-msg");
    if (heading) heading.textContent = t.heading;
    if (placeholder) placeholder.textContent = t.placeholder;
    if (btnText) btnText.textContent = t.btn;
    if (emptyMsg) emptyMsg.textContent = t.empty;
  }
  document.querySelectorAll('input[name="country_mode"]').forEach((radio) => {
    radio.addEventListener("change", updateModeText);
  });
  function createItem(code, name) {
    const li = document.createElement("li");
    li.className = "blocked-item";
    li.innerHTML = `
			<img src="https://flagcdn.com/16x12/${code.toLowerCase()}.png" width="16" height="12" alt="" />
			<span>${name}</span>
			<input type="hidden" name="blocked_countries[]" value="${code}">
			<button type="button" class="remove-country" aria-label="Remove">&times;</button>
		`;
    return li;
  }
  addBtn.addEventListener("click", () => {
    const code = select.value;
    if (!code) return;
    const selectedOption = select.options[select.selectedIndex];
    const name = selectedOption.textContent.replace(/\s*\([A-Z]{2}\)\s*$/, "");
    const emptyMsg = list.querySelector(".empty-msg");
    if (emptyMsg) emptyMsg.remove();
    if (list.querySelector(`input[value="${code}"]`)) return;
    list.appendChild(createItem(code, name));
    selectedOption.disabled = true;
    select.value = "";
  });
  list.addEventListener("click", (e) => {
    const btn = e.target.closest(".remove-country");
    if (!btn) return;
    const li = btn.closest(".blocked-item");
    const code = li.querySelector("input").value;
    li.remove();
    const option = select.querySelector(`option[value="${code}"]`);
    if (option) option.disabled = false;
    if (!list.querySelector(".blocked-item")) {
      const mode = getMode();
      const t = TEXT[mode] || TEXT.block_selected;
      const emptyLi = document.createElement("li");
      emptyLi.className = "empty-msg";
      emptyLi.textContent = t.empty;
      list.appendChild(emptyLi);
    }
  });
}
function initDbOptimizer() {
  var _a;
  const btn = document.getElementById("hda-db-optimize-btn");
  const statusEl = document.getElementById("hda-db-optimize-status");
  const checks = document.querySelectorAll(".hda-db-check");
  const selectAll = document.getElementById("hda-db-select-all");
  if (!btn || !selectAll || !checks.length) return;
  const nonce = ((_a = window.hdaDbOptimizer) == null ? void 0 : _a.nonce) || "";
  function updateBtn() {
    const anyChecked = [...checks].some((c) => c.checked);
    const allChecked = [...checks].every((c) => c.checked);
    btn.disabled = !anyChecked;
    selectAll.checked = allChecked;
    selectAll.indeterminate = !allChecked && anyChecked;
  }
  selectAll.addEventListener("change", () => {
    checks.forEach((c) => {
      c.checked = selectAll.checked;
    });
    updateBtn();
  });
  checks.forEach((c) => c.addEventListener("change", updateBtn));
  updateBtn();
  btn.addEventListener("click", () => {
    var _a2, _b;
    const tasks = [...checks].filter((c) => c.checked).map((c) => {
      var _a3;
      return (_a3 = c.name.match(/\[(\w+)\]/)) == null ? void 0 : _a3[1];
    }).filter(Boolean);
    if (!tasks.length) return;
    btn.disabled = true;
    statusEl.textContent = ((_b = (_a2 = window.hdaDbOptimizer) == null ? void 0 : _a2.i18n) == null ? void 0 : _b.optimizing) || "Optimizing...";
    statusEl.style.color = "#0073aa";
    const fd = new FormData();
    fd.append("action", "hda_db_optimize");
    fd.append("_nonce", nonce);
    tasks.forEach((t) => fd.append("tasks[]", t));
    fetch(ajaxurl, { method: "POST", body: fd }).then((r) => r.json()).then((data) => {
      var _a3;
      if (data.success) {
        const res = data.data.results;
        const summary = Object.entries(res).map(([k, v]) => `${k}: ${v}`).join(", ");
        statusEl.textContent = `✅ ${summary}`;
        statusEl.style.color = "#46b450";
        document.querySelectorAll(".hda-db-count").forEach((el) => {
          el.textContent = "0";
          el.closest("strong").style.color = "#46b450";
        });
      } else {
        statusEl.textContent = `❌ ${((_a3 = data.data) == null ? void 0 : _a3.message) || "Error"}`;
        statusEl.style.color = "#d63638";
      }
      btn.disabled = false;
    }).catch(() => {
      statusEl.textContent = "❌ Request failed";
      statusEl.style.color = "#d63638";
      btn.disabled = false;
    });
  });
}
function initCronManager() {
  var _a;
  const statusEl = document.getElementById("hda-cron-status");
  const table = document.getElementById("hda-cron-table");
  if (!table) return;
  const nonce = ((_a = window.hdaCronManager) == null ? void 0 : _a.nonce) || "";
  function showStatus(msg, color) {
    if (statusEl) {
      statusEl.textContent = msg;
      statusEl.style.color = color || "#666";
    }
  }
  function fadeOutRow(row) {
    row.style.transition = "opacity 0.3s ease";
    row.style.opacity = "0";
    setTimeout(() => {
      const cells = row.querySelectorAll("td");
      cells.forEach((td) => {
        td.style.transition = "padding 0.25s ease, height 0.25s ease";
        td.style.padding = "0";
        td.style.height = "0";
        td.style.overflow = "hidden";
        td.style.lineHeight = "0";
        td.style.fontSize = "0";
        td.style.border = "none";
      });
      setTimeout(() => row.remove(), 300);
    }, 300);
  }
  function setButtonLoading(btn, loading) {
    btn.disabled = loading;
    const icon = btn.querySelector(".dashicons");
    if (!icon) return;
    if (loading) {
      btn._origIcon = icon.className;
      icon.className = "dashicons dashicons-update hda-spin";
      btn.style.opacity = "0.7";
    } else {
      if (btn._origIcon) icon.className = btn._origIcon;
      btn.style.opacity = "";
    }
  }
  function flashRow(row, message, isSuccess) {
    const color = isSuccess ? "#46b450" : "#d63638";
    const bg = isSuccess ? "#ecf7ed" : "#fcf0f1";
    row.style.transition = "background-color 0.3s ease";
    row.style.backgroundColor = bg;
    showStatus(`${isSuccess ? "✅" : "❌"} ${message}`, color);
    if (isSuccess) {
      setTimeout(() => {
        row.style.backgroundColor = "";
      }, 2e3);
    }
  }
  table.addEventListener("click", (e) => {
    const btn = e.target.closest(".hda-cron-run");
    if (!btn || btn.disabled) return;
    const row = btn.closest(".hda-cron-row");
    const hook = row.dataset.hook;
    const ts = row.dataset.timestamp;
    const sig = row.dataset.sig || "";
    if (!confirm(`Run "${hook}" now?`)) return;
    setButtonLoading(btn, true);
    const fd = new FormData();
    fd.append("action", "hda_cron_run");
    fd.append("_nonce", nonce);
    fd.append("hook", hook);
    fd.append("timestamp", ts);
    fd.append("sig", sig);
    showStatus("Running...", "#0073aa");
    fetch(ajaxurl, { method: "POST", body: fd }).then((r) => r.json()).then((data) => {
      var _a2;
      if (data.success) {
        flashRow(row, data.data.message, true);
        if (data.data.removed) {
          setTimeout(() => fadeOutRow(row), 1500);
        } else {
          setButtonLoading(btn, false);
        }
      } else {
        flashRow(row, ((_a2 = data.data) == null ? void 0 : _a2.message) || "Error", false);
        setButtonLoading(btn, false);
      }
    }).catch(() => {
      flashRow(row, "Request failed", false);
      setButtonLoading(btn, false);
    });
  });
  table.addEventListener("click", (e) => {
    const btn = e.target.closest(".hda-cron-delete");
    if (!btn || btn.disabled) return;
    const row = btn.closest(".hda-cron-row");
    const hook = row.dataset.hook;
    const ts = row.dataset.timestamp;
    const sig = row.dataset.sig || "";
    if (!confirm(`Delete "${hook}" event?`)) return;
    setButtonLoading(btn, true);
    const fd = new FormData();
    fd.append("action", "hda_cron_delete");
    fd.append("_nonce", nonce);
    fd.append("hook", hook);
    fd.append("timestamp", ts);
    fd.append("sig", sig);
    showStatus("Deleting...", "#d63638");
    fetch(ajaxurl, { method: "POST", body: fd }).then((r) => r.json()).then((data) => {
      var _a2;
      if (data.success) {
        flashRow(row, data.data.message, true);
        setTimeout(() => fadeOutRow(row), 1e3);
      } else {
        flashRow(row, ((_a2 = data.data) == null ? void 0 : _a2.message) || "Error", false);
        setButtonLoading(btn, false);
      }
    }).catch(() => {
      flashRow(row, "Request failed", false);
      setButtonLoading(btn, false);
    });
  });
}
function initFileIntegrity() {
  const buttons = document.querySelectorAll(".hda-fi-scan-btn");
  if (!buttons.length) return;
  const config = window.hdaFileIntegrity || {};
  const nonce = config.nonce || "";
  const i18n = config.i18n || {};
  buttons.forEach((btn) => {
    btn.addEventListener("click", () => {
      if (btn.disabled) return;
      const action = btn.dataset.scan;
      if (!action) return;
      btn.disabled = true;
      const progressEl = document.querySelector(`.hda-fi-progress[data-progress-for="${action}"]`);
      if (progressEl) progressEl.classList.add("is-active");
      const fd = new FormData();
      fd.append("action", action);
      fd.append("_nonce", nonce);
      fetch(ajaxurl, { method: "POST", body: fd }).then((r) => r.json()).then((data) => {
        var _a;
        if (data.success) {
          window.location.reload();
        } else {
          btn.disabled = false;
          if (progressEl) progressEl.classList.remove("is-active");
          showToast2(((_a = data.data) == null ? void 0 : _a.message) || i18n.error || "Error", "error");
        }
      }).catch(() => {
        btn.disabled = false;
        if (progressEl) progressEl.classList.remove("is-active");
        showToast2(i18n.error || "Scan failed. Please try again.", "error");
      });
    });
  });
  function showToast2(message, type = "success") {
    const container = document.getElementById("hda-fi-toast");
    if (!container) return;
    const noticeClass = type === "error" ? "notice-error" : "notice-success";
    const toast = document.createElement("div");
    toast.className = `notice ${noticeClass} is-dismissible`;
    const p = document.createElement("p");
    p.textContent = message;
    toast.appendChild(p);
    container.appendChild(toast);
    setTimeout(() => {
      toast.style.transition = "opacity 0.3s ease";
      toast.style.opacity = "0";
      setTimeout(() => toast.remove(), 300);
    }, 6e3);
  }
}
function initCookieConsentSettings() {
  const enableCheckbox = document.getElementById("cc_enabled");
  const privacyUrlInput = document.getElementById("cc_privacy_url");
  if (!enableCheckbox) return;
  const dependentFields = document.querySelectorAll(".cc-depends-enabled");
  const privacyTextWrap = document.querySelector(".cc-depends-privacy-url");
  function updateVisibility() {
    const isEnabled = enableCheckbox.checked;
    dependentFields.forEach((el) => {
      el.style.display = isEnabled ? "" : "none";
    });
    if (privacyTextWrap) {
      const hasUrl = isEnabled && privacyUrlInput && privacyUrlInput.value.trim() !== "";
      privacyTextWrap.style.display = hasUrl ? "" : "none";
    }
  }
  enableCheckbox.addEventListener("change", updateVisibility);
  privacyUrlInput == null ? void 0 : privacyUrlInput.addEventListener("input", updateVisibility);
  updateVisibility();
}
function initMaintenanceSettings() {
  const enableCheckbox = document.getElementById("mt_enabled");
  if (!enableCheckbox) return;
  const dependentFields = document.querySelectorAll(".mt-depends-enabled");
  function updateVisibility() {
    const isEnabled = enableCheckbox.checked;
    dependentFields.forEach((el) => {
      el.style.display = isEnabled ? "" : "none";
    });
  }
  enableCheckbox.addEventListener("change", updateVisibility);
  updateVisibility();
}
const $ = window.jQuery;
select2($);
document.addEventListener("DOMContentLoaded", () => {
  initCodeMirror();
  initSelect2($);
  initOtpSettings();
  initRedirectRepeater();
  initWaf();
  initDbOptimizer();
  initCronManager();
  initFileIntegrity();
  initCookieConsentSettings();
  initMaintenanceSettings();
});
jQuery(function($2) {
  $2(".hda-color-field").wpColorPicker();
  initSettingsForm($2);
  initFilterTabs($2);
});
//# sourceMappingURL=hda.CrVOZqma.js.map
