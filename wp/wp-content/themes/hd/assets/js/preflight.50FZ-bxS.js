(() => {
  if (window.__darkInit) return;
  window.__darkInit = true;
  const root = document.documentElement;
  const THEME = localStorage.theme;
  if (THEME === "dark") {
    root.classList.add("dark");
  }
  const sunIcon = '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5V3m0 18v-2M7.05 7.05L5.636 5.636m12.728 12.728L16.95 16.95M5 12H3m18 0h-2M7.05 16.95l-1.414 1.414M18.364 5.636L16.95 7.05M16 12a4 4 0 1 1-8 0a4 4 0 0 1 8 0"></path>';
  const moonIcon = '<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 0 1-.5-17.986V3c-.354.966-.5 1.911-.5 3a9 9 0 0 0 9 9c.239 0 .254.018.488 0A9 9 0 0 1 12 21"/>';
  const run = () => {
    const button = document.querySelector(".dark-mode");
    if (!button) return;
    const svg = button.querySelector("svg");
    if (!svg) return;
    const updateIcon = () => {
      svg.innerHTML = root.classList.contains("dark") ? moonIcon : sunIcon;
    };
    updateIcon();
    button.addEventListener("click", () => {
      const isDark = root.classList.toggle("dark");
      localStorage.theme = isDark ? "dark" : "light";
      updateIcon();
    });
  };
  document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", run, { once: true }) : run();
})();
//# sourceMappingURL=preflight.50FZ-bxS.js.map
