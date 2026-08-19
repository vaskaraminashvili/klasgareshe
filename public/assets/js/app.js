/* Kidzio — shared interaction helpers
 * Loaded as a plain <script src> on every page. Not bundled.
 * Service worker registration lives in assets/js/index.js (bundled entry).
 *
 * ═══════════════════════════════════════════════════════════════════
 *  TABLE OF CONTENTS
 * ═══════════════════════════════════════════════════════════════════
 *   Theme Toggle (window.toggleTheme) .............. line  30
 *     · toggleTheme() — flips html.dark + meta + icons
 *     · Apply-on-load IIFE — sets initial class
 *     · DOMContentLoaded icon sync
 *
 *   Shared interactions (IIFE) ..................... line  71
 *     · PWA install prompt (beforeinstallprompt)
 *     · window.toast(msg, ms) — transient pill
 *     · [data-back]        — back button
 *     · [data-theme-toggle] — theme toggle
 *     · [data-ans] + [data-ans-group] — quiz answer UX
 *     · [data-tab] + [data-tabs] + [data-panel] — tab switcher
 *     · [data-sheet]       — bottom sheet toggle
 *     · [data-pwd-toggle]  — password visibility toggle
 *
 *   Auto-init Swiper rails ......................... line 167
 *     · [data-swiper-rail]      — content row (free-mode)
 *     · [data-swiper-rail-tabs] — filter chip row (tap-preserving)
 *     · Re-init on livewire:navigated (wire:navigate)
 * ═══════════════════════════════════════════════════════════════════ */

"use strict";

// ── Theme Toggle ─────────────────────────────────────────────────
function toggleTheme() {
  const html = document.documentElement;
  const isDark = html.classList.toggle("dark");
  localStorage.setItem("theme", isDark ? "dark" : "light");
  // Update meta theme-color for PWA chrome
  const meta = document.querySelector('meta[name="theme-color"]');
  if (meta) meta.setAttribute("content", isDark ? "#0F0B22" : "#FFF8F1");
  // Update all theme toggle icons
  document.querySelectorAll(".theme-icon-moon").forEach((el) => {
    el.style.display = isDark ? "none" : "block";
  });
  document.querySelectorAll(".theme-icon-sun").forEach((el) => {
    el.style.display = isDark ? "block" : "none";
  });
}

// Apply saved theme on load (idempotent — the pre-paint inline script in
// <head> already sets the class; this keeps a single source of truth).
(function () {
  const saved =
    localStorage.getItem("theme") ||
    (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
  const html = document.documentElement;
  if (saved === "dark") html.classList.add("dark");
  else html.classList.remove("dark");
})();

// Sync icon visibility after DOM ready and after wire:navigate.
function syncThemeIcons() {
  const isDark = document.documentElement.classList.contains("dark");
  document
    .querySelectorAll(".theme-icon-moon")
    .forEach((el) => (el.style.display = isDark ? "none" : "block"));
  document
    .querySelectorAll(".theme-icon-sun")
    .forEach((el) => (el.style.display = isDark ? "block" : "none"));
}

document.addEventListener("DOMContentLoaded", syncThemeIcons);
document.addEventListener("livewire:navigated", syncThemeIcons);

window.toggleTheme = toggleTheme;

// ── Shared interactions ──────────────────────────────────────────
(function () {
  // PWA install prompt
  let deferredPrompt;
  window.addEventListener("beforeinstallprompt", (e) => {
    e.preventDefault();
    deferredPrompt = e;
    document.querySelectorAll("[data-install]").forEach((el) => {
      el.hidden = false;
      el.addEventListener(
        "click",
        async () => {
          el.hidden = true;
          deferredPrompt.prompt();
          await deferredPrompt.userChoice;
          deferredPrompt = null;
        },
        { once: true },
      );
    });
  });

  // Toast helper
  window.toast = function (msg, ms = 1800) {
    const el = document.createElement("div");
    el.className = "toast";
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), ms);
  };

  // Back button (history or href fallback)
  document.addEventListener("click", (e) => {
    const back = e.target.closest("[data-back]");
    if (!back) return;
    e.preventDefault();
    if (history.length > 1) history.back();
    else location.href = back.getAttribute("href") || "index.html";
  });

  // Theme toggle buttons
  document.addEventListener("click", (e) => {
    const t = e.target.closest("[data-theme-toggle]");
    if (!t) return;
    toggleTheme();
  });

  // Quiz answer selection UX
  document.addEventListener("click", (e) => {
    const ans = e.target.closest("[data-ans]");
    if (!ans) return;
    const group = ans.closest("[data-ans-group]");
    if (!group) return;
    const correct = ans.getAttribute("data-ans") === "correct";
    group.querySelectorAll("[data-ans]").forEach((b) => b.classList.remove("correct", "wrong"));
    ans.classList.add(correct ? "correct" : "wrong");
    if (!correct) {
      const right = group.querySelector('[data-ans="correct"]');
      if (right) right.classList.add("correct");
    }
  });

  // Simple tab switcher
  document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-tab]");
    if (!btn) return;
    const group = btn.closest("[data-tabs]");
    if (!group) return;
    const name = btn.getAttribute("data-tab");
    group.querySelectorAll("[data-tab]").forEach((t) => t.classList.remove("is-active"));
    btn.classList.add("is-active");
    group.querySelectorAll("[data-panel]").forEach((p) => {
      p.hidden = p.getAttribute("data-panel") !== name;
    });
  });

  // Bottom sheet toggles
  document.addEventListener("click", (e) => {
    const t = e.target.closest("[data-sheet]");
    if (!t) return;
    const id = t.getAttribute("data-sheet");
    const sheet = document.getElementById(id);
    if (!sheet) return;
    sheet.classList.toggle("hidden");
  });

  // Password visibility toggle
  document.addEventListener("click", (e) => {
    const t = e.target.closest("[data-pwd-toggle]");
    if (!t) return;
    const input = document.getElementById(t.getAttribute("data-pwd-toggle"));
    if (!input) return;
    input.type = input.type === "password" ? "text" : "password";
  });
})();

// ── Auto-init Swiper rails ───────────────────────────────────────
// Two init modes:
//
//   <div class="swiper rail-swiper" data-swiper-rail>          ← content row
//   <div class="swiper rail-swiper" data-swiper-rail-tabs>     ← filter-chip row
//
// Both do free-mode horizontal sliding with slidesPerView:"auto".
// The -tabs variant tunes the config so taps on chips keep firing:
// short touches don't trigger a drag, and Swiper doesn't preventDefault
// on touchstart. Options are tweakable per-rail via data-space-between,
// data-offset-before, data-offset-after.
//
// window.Swiper is exposed by the bundled index.js with `defer`, so we
// wait for DOMContentLoaded before initializing — see NOTES.md
// "Swiper timing". Re-run after wire:navigate: body swap drops instances.
(function () {
  const initRailSwipers = () => {
    if (!window.Swiper) return;
    const mods = window.SwiperModules ? [window.SwiperModules.FreeMode] : [];

    const baseOpts = (el) => ({
      modules: mods,
      slidesPerView: "auto",
      spaceBetween: parseInt(el.dataset.spaceBetween, 10) || 12,
      freeMode: true,
      grabCursor: true,
      slidesOffsetBefore: parseInt(el.dataset.offsetBefore, 10) || 20,
      slidesOffsetAfter: parseInt(el.dataset.offsetAfter, 10) || 20,
    });

    document.querySelectorAll(".swiper[data-swiper-rail]").forEach((el) => {
      if (el.swiper) return;
      new window.Swiper(el, baseOpts(el));
    });

    document
      .querySelectorAll(".swiper[data-swiper-rail-tabs]")
      .forEach((el) => {
        if (el.swiper) return;
        new window.Swiper(el, {
          ...baseOpts(el),
          // Don't block native taps on chips. Swiper still suppresses
          // clicks if a real drag happens (touchmove beyond threshold),
          // so tap-to-filter keeps working and drag-to-scroll works too.
          touchStartPreventDefault: false,
          touchMoveStopPropagation: false,
          // Small slide threshold = more tolerant for taps that move a
          // hair. Default 3px is fine but we set it explicitly.
          threshold: 5,
        });
      });
  };

  const destroyRailSwipers = () => {
    document.querySelectorAll(".swiper").forEach((el) => {
      if (el.swiper) el.swiper.destroy(true, true);
    });
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initRailSwipers);
  } else {
    initRailSwipers();
  }

  document.addEventListener("livewire:navigating", destroyRailSwipers);
  document.addEventListener("livewire:navigated", () => {
    requestAnimationFrame(initRailSwipers);
  });
})();
