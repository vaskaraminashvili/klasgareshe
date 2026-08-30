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
 *
 *   Parent verify (method tabs, OTP, resend timer) . after rails
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
// cssMode uses native overflow-x so rails still swipe after Livewire
// morphs (JS transform handlers get detached). preventClicks is off so
// chip taps and wire:navigate links inside slides keep working.
// Options are tweakable per-rail via data-space-between,
// data-offset-before, data-offset-after.
//
// window.Swiper is exposed by assets/js/index.js. Retry until it exists,
// then re-run after wire:navigate / Livewire morph.
(function () {
  let swiperWaitFrames = 0;

  const railIsAlive = (el) =>
    el.swiper &&
    !el.swiper.destroyed &&
    el.swiper.wrapperEl &&
    el.swiper.wrapperEl.isConnected &&
    el.swiper.wrapperEl === el.querySelector(".swiper-wrapper");

  const mountRail = (el, opts) => {
    if (railIsAlive(el)) {
      el.swiper.update();
      return;
    }
    if (el.swiper) {
      try {
        el.swiper.destroy(true, true);
      } catch (e) {
        /* ignore stale instance */
      }
    }
    new window.Swiper(el, opts);
  };

  const initRailSwipers = () => {
    if (!window.Swiper) {
      if (swiperWaitFrames++ < 60) requestAnimationFrame(initRailSwipers);
      return;
    }
    swiperWaitFrames = 0;
    const mods = window.SwiperModules ? [window.SwiperModules.FreeMode] : [];

    const baseOpts = (el) => ({
      modules: mods,
      slidesPerView: "auto",
      spaceBetween: parseInt(el.dataset.spaceBetween, 10) || 12,
      freeMode: true,
      cssMode: true,
      grabCursor: true,
      slidesOffsetBefore: parseInt(el.dataset.offsetBefore, 10) || 20,
      slidesOffsetAfter: parseInt(el.dataset.offsetAfter, 10) || 20,
      preventClicks: false,
      preventClicksPropagation: false,
    });

    document.querySelectorAll(".swiper[data-swiper-rail]").forEach((el) => {
      mountRail(el, baseOpts(el));
    });

    document.querySelectorAll(".swiper[data-swiper-rail-tabs]").forEach((el) => {
      mountRail(el, {
        ...baseOpts(el),
        touchStartPreventDefault: false,
        touchMoveStopPropagation: false,
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
  document.addEventListener("livewire:initialized", () => {
    requestAnimationFrame(initRailSwipers);
  });
  document.addEventListener("livewire:init", () => {
    if (!window.Livewire || typeof window.Livewire.hook !== "function") return;
    window.Livewire.hook("morph.updated", () => {
      requestAnimationFrame(initRailSwipers);
    });
  });
})();

// ── Parent verification (survives wire:navigate) ─────────────────
(function () {
  function otpBoxes() {
    return Array.from(document.querySelectorAll(".otp-box"));
  }

  function syncOtpState() {
    const verifyBtn = document.getElementById("verifyBtn");
    const hint = document.getElementById("otpHint");
    if (!verifyBtn || !hint) return;
    const code = otpBoxes()
      .map((b) => b.value)
      .join("");
    verifyBtn.disabled = code.length !== 6;
    const empty = hint.dataset.hintEmpty || "Tip: paste the code and it auto-fills.";
    const ready = hint.dataset.hintReady || "Looks good — tap verify!";
    if (code.length === 6) {
      hint.textContent = ready;
      hint.classList.remove("text-muted");
      hint.classList.add("text-mint-ink");
    } else {
      hint.textContent = empty;
      hint.classList.add("text-muted");
      hint.classList.remove("text-mint-ink");
    }
  }

  function livewireFrom(el) {
    const root = el.closest("[wire\\:id]");
    if (!root || !window.Livewire) return null;
    return window.Livewire.find(root.getAttribute("wire:id"));
  }

  document.addEventListener("click", (e) => {
    const method = e.target.closest("#methodPicker [data-method]");
    if (!method) return;
    const key = method.getAttribute("data-method");
    document.querySelectorAll("#methodPicker [data-method]").forEach((other) => {
      other.classList.toggle("is-selected", other === method);
    });
    document.querySelectorAll("[data-panel='link'], [data-panel='code']").forEach((panel) => {
      panel.classList.toggle("hidden", panel.getAttribute("data-panel") !== key);
    });
  });

  document.addEventListener("input", (e) => {
    const box = e.target;
    if (!(box instanceof HTMLInputElement) || !box.classList.contains("otp-box")) return;
    const boxes = otpBoxes();
    const i = boxes.indexOf(box);
    box.value = (box.value || "").replace(/\D/g, "").slice(0, 1);
    if (box.value && i > -1 && i < boxes.length - 1) boxes[i + 1].focus();
    syncOtpState();
  });

  document.addEventListener("keydown", (e) => {
    const box = e.target;
    if (!(box instanceof HTMLInputElement) || !box.classList.contains("otp-box")) return;
    if (e.key !== "Backspace" || box.value) return;
    const boxes = otpBoxes();
    const i = boxes.indexOf(box);
    if (i > 0) boxes[i - 1].focus();
  });

  document.addEventListener("paste", (e) => {
    const box = e.target;
    if (!(box instanceof HTMLInputElement) || !box.classList.contains("otp-box")) return;
    const text = (e.clipboardData || window.clipboardData).getData("text") || "";
    const digits = text.replace(/\D/g, "").slice(0, 6).split("");
    if (!digits.length) return;
    e.preventDefault();
    const boxes = otpBoxes();
    digits.forEach((d, k) => {
      if (boxes[k]) boxes[k].value = d;
    });
    boxes[Math.min(digits.length, boxes.length) - 1].focus();
    syncOtpState();
  });

  document.addEventListener("click", (e) => {
    const verifyBtn = e.target.closest("#verifyBtn");
    if (!verifyBtn) return;
    const code = otpBoxes()
      .map((b) => b.value)
      .join("");
    if (code.length !== 6) return;
    const component = livewireFrom(verifyBtn);
    if (!component) return;
    component.verifyCode(code);
  });

  let resendTimerId = null;
  function startResendCountdown() {
    if (resendTimerId) {
      clearTimeout(resendTimerId);
      resendTimerId = null;
    }
    const timerEl = document.getElementById("resendTimer");
    const resendBtn = document.getElementById("resendLink");
    if (!timerEl || !resendBtn) return;
    let remaining = 30;
    function tick() {
      if (!document.getElementById("resendTimer") || !document.getElementById("resendLink")) {
        return;
      }
      if (remaining > 0) {
        timerEl.textContent = "(" + remaining + "s)";
        resendBtn.disabled = true;
        resendBtn.classList.add("opacity-60");
        remaining--;
        resendTimerId = setTimeout(tick, 1000);
      } else {
        timerEl.textContent = "";
        resendBtn.disabled = false;
        resendBtn.classList.remove("opacity-60");
      }
    }
    tick();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", startResendCountdown);
  } else {
    startResendCountdown();
  }
  document.addEventListener("livewire:navigated", startResendCountdown);
})();
