import { q as qrcode } from "./vendor.iJdduJUa.js";
(function($) {
  const config = window.hdaOtpVerify || {};
  const i18n = config.i18n || {};
  $(document).on("click", ".hda-otp-send-code", function(e) {
    e.preventDefault();
    const $btn = $(this);
    const userId = $btn.data("user");
    const field = $btn.data("field");
    const $field = $("#" + field);
    const value = $field.val().trim();
    const $row = $btn.closest("td");
    const $verifyRow = $row.find(".hda-otp-verify-row");
    const $message = $row.find(".hda-otp-message");
    if (!value) {
      alert(i18n.enterValue || "Please enter a value first.");
      return;
    }
    $btn.prop("disabled", true).text(i18n.sending || "Sending...");
    $message.removeClass("error success").text("");
    $.post(
      config.ajaxUrl,
      {
        action: "hda_send_otp_verification",
        nonce: config.nonce,
        user_id: userId,
        field,
        value
      },
      function(response) {
        $btn.prop("disabled", false).text(i18n.sendCode || "Send Test Code");
        if (response.success) {
          $verifyRow.slideDown();
          $message.addClass("success").text(response.data.message);
        } else {
          $message.addClass("error").text(response.data.message || i18n.error);
        }
      }
    ).fail(function() {
      $btn.prop("disabled", false).text(i18n.sendCode || "Send Test Code");
      $message.addClass("error").text(i18n.error || "Error occurred");
    });
  });
  $(document).on("click", ".hda-otp-verify-code", function(e) {
    e.preventDefault();
    const $btn = $(this);
    const userId = $btn.data("user");
    const $row = $btn.closest("td");
    const $codeInput = $row.find(".hda-otp-code-input");
    const $message = $row.find(".hda-otp-message");
    const $status = $row.find(".hda-otp-status");
    const code = $codeInput.val().trim();
    if (!code) {
      $message.removeClass("success").addClass("error").text(i18n.enterCode || "Please enter the code.");
      return;
    }
    $btn.prop("disabled", true).text(i18n.verifying || "Verifying...");
    $message.removeClass("error success").text("");
    $.post(
      config.ajaxUrl,
      {
        action: "hda_verify_otp_code",
        nonce: config.nonce,
        user_id: userId,
        code
      },
      function(response) {
        $btn.prop("disabled", false).text(i18n.verify || "Verify");
        if (response.success) {
          $message.addClass("success").text(response.data.message);
          $status.removeClass("not-verified").addClass("verified").html("✓ " + (i18n.verified || "Verified"));
          $row.find(".hda-otp-verify-row").slideUp();
        } else {
          $message.addClass("error").text(response.data.message || i18n.error);
        }
      }
    ).fail(function() {
      $btn.prop("disabled", false).text(i18n.verify || "Verify");
      $message.addClass("error").text(i18n.error || "Error occurred");
    });
  });
  $(document).on("input", "#phone_number, #telegram_chat_id", function() {
    const $field = $(this);
    const original = $field.data("original");
    if ($field.val() !== original) {
      $field.closest("td").find(".hda-otp-status").removeClass("verified").addClass("not-verified").html("⚠ " + (i18n.notVerified || "Not verified"));
    }
  });
  $(document).on("click", "#hda-totp-setup-btn", function(e) {
    e.preventDefault();
    const $btn = $(this);
    const userId = $("#hda-totp-wrap").data("user");
    $btn.prop("disabled", true).text(i18n.generating || "Generating...");
    $.post(
      config.ajaxUrl,
      {
        action: "hda_totp_generate",
        nonce: config.nonce,
        user_id: userId
      },
      function(response) {
        $btn.prop("disabled", false).text(i18n.setupTotp || "Set Up Authenticator");
        if (response.success) {
          const { secret, uri } = response.data;
          $("#hda-totp-secret").text(secret);
          const qrContainer = document.getElementById("hda-totp-qrcode");
          qrContainer.innerHTML = "";
          const qr = qrcode(0, "M");
          qr.addData(uri);
          qr.make();
          qrContainer.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 4 });
          $btn.hide();
          $("#hda-totp-setup-panel").slideDown();
        } else {
          alert(response.data.message || i18n.error);
        }
      }
    ).fail(function() {
      $btn.prop("disabled", false).text(i18n.setupTotp || "Set Up Authenticator");
      alert(i18n.error || "Error occurred");
    });
  });
  $(document).on("click", "#hda-totp-verify-btn", function(e) {
    e.preventDefault();
    const $btn = $(this);
    const userId = $("#hda-totp-wrap").data("user");
    const code = $("#hda-totp-verify-input").val().trim();
    const $msg = $("#hda-totp-message");
    if (!code) {
      $msg.html('<span style="color:#d63638;">' + (i18n.enterCode || "Please enter the code.") + "</span>");
      return;
    }
    $btn.prop("disabled", true).text(i18n.verifying || "Verifying...");
    $msg.text("");
    $.post(
      config.ajaxUrl,
      {
        action: "hda_totp_verify",
        nonce: config.nonce,
        user_id: userId,
        code
      },
      function(response) {
        $btn.prop("disabled", false).text(i18n.verify || "Verify & Activate");
        if (response.success) {
          $msg.html('<span style="color:#00a32a;font-weight:600;">' + response.data.message + "</span>");
          setTimeout(function() {
            location.reload();
          }, 1500);
        } else {
          $msg.html('<span style="color:#d63638;">' + (response.data.message || i18n.error) + "</span>");
        }
      }
    ).fail(function() {
      $btn.prop("disabled", false).text(i18n.verify || "Verify & Activate");
      $msg.html('<span style="color:#d63638;">' + (i18n.error || "Error occurred") + "</span>");
    });
  });
  $(document).on("click", "#hda-totp-reset-btn", function(e) {
    e.preventDefault();
    if (!confirm(i18n.confirmReset || "Are you sure?")) {
      return;
    }
    const $btn = $(this);
    const userId = $("#hda-totp-wrap").data("user");
    $btn.prop("disabled", true).text(i18n.resetting || "Resetting...");
    $.post(
      config.ajaxUrl,
      {
        action: "hda_totp_reset",
        nonce: config.nonce,
        user_id: userId
      },
      function(response) {
        $btn.prop("disabled", false).text(i18n.resetTotp || "Reset Authenticator");
        if (response.success) {
          location.reload();
        } else {
          alert(response.data.message || i18n.error);
        }
      }
    ).fail(function() {
      $btn.prop("disabled", false).text(i18n.resetTotp || "Reset Authenticator");
      alert(i18n.error || "Error occurred");
    });
  });
})(jQuery);
//# sourceMappingURL=otp-profile.DHr261mE.js.map
