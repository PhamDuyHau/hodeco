const run = async () => {
  const createUserForm = document.getElementById("createuser");
  if (createUserForm) {
    const sendNotification = createUserForm.querySelector("#send_user_notification");
    if (sendNotification) {
      sendNotification.checked = false;
      sendNotification.disabled = true;
    }
  }
  const HIDDEN_EDITOR_TEMPLATES = /* @__PURE__ */ new Set(["templates/template-page-home.php"]);
  const selectedTemplate = document.getElementById("page_template");
  const editorWrapper = document.getElementById("postdivrich");
  const toggleEditor = () => {
    if (!selectedTemplate || !editorWrapper) return;
    if (HIDDEN_EDITOR_TEMPLATES.has(selectedTemplate.value)) {
      editorWrapper.style.display = "none";
    } else {
      editorWrapper.style.display = "";
      setTimeout(() => {
        window.dispatchEvent(new Event("resize"));
      }, 10);
    }
  };
  toggleEditor();
  if (selectedTemplate) {
    selectedTemplate.addEventListener("change", toggleEditor);
  }
  document.addEventListener("click", (e) => {
    const dismissBtn = e.target.closest(".notice-dismiss");
    if (!dismissBtn) return;
    const notice = dismissBtn.closest(".notice.is-dismissible");
    if (notice) {
      notice.style.transition = "opacity 0.5s ease";
      notice.style.opacity = "0";
      setTimeout(() => notice.remove(), 500);
    }
  });
  const postTitle = document.querySelector('input[name="post_title"]');
  if (postTitle) {
    postTitle.required = true;
  }
  document.addEventListener("click", (e) => {
    const trashLink = e.target.closest('a[href*="action=trash"]');
    if (!trashLink) return;
    if (!confirm("Are you sure you want to move this post to the trash?")) {
      e.preventDefault();
    }
  });
};
document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", run, { once: true }) : run();
//# sourceMappingURL=admin.COe1vRjr.js.map
