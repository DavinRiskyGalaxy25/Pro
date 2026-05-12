(function setTopbarDate() {
  const el = document.getElementById("topbarDate");
  if (!el) return;
  const now = new Date();
  el.textContent = now.toLocaleDateString("id-ID", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  });
})();

// ── Sidebar Toggle (Mobile) ───────────────────
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebarOverlay");
  if (!sidebar) return;
  const isOpen = !sidebar.classList.contains("-translate-x-full");
  if (isOpen) {
    sidebar.classList.add("-translate-x-full");
    overlay?.classList.add("hidden");
  } else {
    sidebar.classList.remove("-translate-x-full");
    overlay?.classList.remove("hidden");
  }
}

function closeSidebar() {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebarOverlay");
  sidebar?.classList.add("-translate-x-full");
  overlay?.classList.add("hidden");
}

// ── Sidebar Toggle Desktop ───────────────────
let sidebarCollapsed = false;
function toggleSidebarDesktop() {
  const main = document.getElementById("mainContent");
  const sidebar = document.getElementById("sidebar");
  if (!main || !sidebar) return;
  sidebarCollapsed = !sidebarCollapsed;
  if (sidebarCollapsed) {
    sidebar.classList.add("-translate-x-full");
    main.classList.remove("lg:pl-64");
    main.classList.add("lg:pl-0");
  } else {
    sidebar.classList.remove("-translate-x-full");
    main.classList.add("lg:pl-64");
    main.classList.remove("lg:pl-0");
  }
}

// ── Toast Notification ────────────────────────
function showToast(message, type = "default", duration = 3500) {
  const container = document.getElementById("toastContainer");
  if (!container) return;

  const colors = {
    success: "bg-green-800 text-white",
    error: "bg-red-800 text-white",
    warning: "bg-orange-700 text-white",
    default: "bg-gray-800 text-white",
  };
  const icons = { success: "✓", error: "✕", warning: "⚠", default: "ℹ" };

  const toast = document.createElement("div");
  toast.className = `${colors[type] || colors.default}
    flex items-center gap-2 px-4 py-3 rounded-xl shadow-xl text-sm font-medium
    pointer-events-auto max-w-xs
    animate-[slideUp_0.3s_ease]`;
  toast.innerHTML = `<span class="shrink-0">${icons[type] || icons.default}</span>${message}`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = "0";
    toast.style.transform = "translateY(8px)";
    toast.style.transition = "all 0.25s ease";
    setTimeout(() => toast.remove(), 250);
  }, duration);
}

// ── Close modal on overlay click ─────────────
document.addEventListener("click", function (e) {
  if (e.target.classList.contains("modal-overlay")) {
    e.target.classList.add("hidden");
  }
});
