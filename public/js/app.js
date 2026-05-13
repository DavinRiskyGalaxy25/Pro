// ================================================
// Kantin UAM — Global JS  |  public/js/app.js
// ================================================

// ── Topbar date ──────────────────────────────────
(function () {
  const el = document.getElementById("topbarDate");
  if (!el) return;
  el.textContent = new Date().toLocaleDateString("id-ID", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  });
})();

// ── Sidebar state ────────────────────────────────
let _sidebarDesktopOpen = true;

function toggleSidebar() {
  const s = document.getElementById("sidebar");
  const o = document.getElementById("sidebarOverlay");
  if (!s) return;
  const hidden = s.classList.contains("-translate-x-full");
  if (hidden) {
    s.classList.remove("-translate-x-full");
    o?.classList.remove("hidden");
    o?.classList.add("block");
  } else {
    s.classList.add("-translate-x-full");
    o?.classList.add("hidden");
    o?.classList.remove("block");
  }
}

function closeSidebar() {
  const s = document.getElementById("sidebar");
  const o = document.getElementById("sidebarOverlay");
  s?.classList.add("-translate-x-full");
  o?.classList.add("hidden");
}

function toggleSidebarDesktop() {
  const s = document.getElementById("sidebar");
  const m = document.getElementById("mainContent");
  if (!s || !m) return;
  _sidebarDesktopOpen = !_sidebarDesktopOpen;
  if (_sidebarDesktopOpen) {
    s.classList.remove("-translate-x-full");
    m.classList.add("lg:pl-64");
    m.classList.remove("lg:pl-0");
  } else {
    s.classList.add("-translate-x-full");
    m.classList.remove("lg:pl-64");
    m.classList.add("lg:pl-0");
  }
}

// Close sidebar on outside click (mobile)
document.addEventListener("click", function (e) {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebarOverlay");
  const hamburger =
    e.target.closest('[onclick*="toggleSidebar"]') ||
    e.target.closest('[onclick*="closeSidebar"]');
  if (!sidebar || hamburger) return;
  if (window.innerWidth < 1024) {
    if (
      !sidebar.contains(e.target) &&
      !sidebar.classList.contains("-translate-x-full")
    ) {
      closeSidebar();
    }
  }
});

// ── Toast notification ───────────────────────────
function showToast(msg, type = "default", duration = 3500) {
  const container = document.getElementById("toastContainer");
  if (!container) return;

  const cfg = {
    success: { bg: "bg-emerald-700", icon: "fa-circle-check" },
    error: { bg: "bg-red-700", icon: "fa-circle-xmark" },
    warning: { bg: "bg-orange-600", icon: "fa-triangle-exclamation" },
    default: { bg: "bg-gray-800", icon: "fa-circle-info" },
  };
  const { bg, icon } = cfg[type] || cfg.default;

  const el = document.createElement("div");
  el.className = `${bg} toast-in flex items-center gap-2.5 px-4 py-3 rounded-xl
                  shadow-xl text-white text-sm font-medium pointer-events-auto max-w-xs`;
  el.innerHTML = `<i class="fa-solid ${icon} shrink-0"></i><span>${msg}</span>`;
  container.appendChild(el);

  setTimeout(() => {
    el.style.cssText =
      "opacity:0;transform:translateY(8px);transition:all .25s ease";
    setTimeout(() => el.remove(), 260);
  }, duration);
}

// ── Format Rupiah ────────────────────────────────
function fmtRp(n) {
  return "Rp " + Math.round(n || 0).toLocaleString("id-ID");
}

// ── Keyboard shortcuts ───────────────────────────
document.addEventListener("keydown", function (e) {
  // Escape → close any open modal overlay
  if (e.key === "Escape") {
    document.querySelectorAll('[id*="modal"],[id*="Modal"]').forEach((el) => {
      if (!el.classList.contains("hidden")) el.classList.add("hidden");
    });
    document.body.style.overflow = "";
  }
});
