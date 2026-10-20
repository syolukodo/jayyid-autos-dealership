/* ==========================================================================
   Jayyid Autos - motion.js
   Scroll reveals (fade + rise, staggered across grids), count-up numbers and
   the sticky header lift. No animation library; styles live in the "Motion
   system" block at the end of main.css. Respects prefers-reduced-motion.
   ========================================================================== */
(function () {
  "use strict";

  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* Blocks that rise in as a single unit. */
  var SINGLE = [
    ".section-head",
    ".principles-head",
    ".verify-grid__copy",
    ".verify-media",
    ".folio-block",
    ".perk-card",
    ".service-block__body",
    ".service-block__media",
    ".plan-teaser__copy",
    ".plan-calc",
    ".ethics-feature__copy",
    ".ethics-media",
    ".contact-form",
    ".vehicle-section .split > *",
    ".process__cta",
    ".make-chips",
    ".cta-band__inner"
  ];

  /* Grids and lists whose children cascade in one after another. */
  var GROUPS = [
    ".trust-strip",
    ".type-tiles",
    ".car-grid",
    ".process",
    ".service-tiles",
    ".principle-cards",
    ".route-grid",
    ".spec-cards",
    ".service-grid",
    ".feature-grid",
    ".step-cards"
  ];

  var STAGGER_MS = 80;
  var STAGGER_MAX = 6;

  /* ----------------------------------------------------------------------
     Scroll reveals
     ---------------------------------------------------------------------- */
  function initReveals() {
    if (reduceMotion || !("IntersectionObserver" in window)) return;

    var observer = new IntersectionObserver(function (entries) {
      var entering = entries
        .filter(function (entry) { return entry.isIntersecting; })
        .map(function (entry) { return entry.target; })
        .sort(function (a, b) {
          return a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
        });

      // Everything entering in the same frame cascades in document order.
      entering.forEach(function (el, i) {
        observer.unobserve(el);
        el.style.setProperty("--reveal-delay", Math.min(i, STAGGER_MAX) * STAGGER_MS + "ms");
        el.classList.add("is-revealed");
      });
    }, { threshold: 0.15, rootMargin: "0px 0px -6% 0px" });

    function inView(el) {
      var rect = el.getBoundingClientRect();
      return rect.top < window.innerHeight && rect.bottom > 0;
    }

    function prepare(el, force) {
      if (el.hasAttribute("data-motion")) return;
      el.setAttribute("data-motion", "");
      // Content already on screen at load stays put; the hero carries the entrance.
      if (!force && inView(el)) return;
      // A parent that already reveals as one unit owns its contents.
      if (el.parentElement && el.parentElement.closest(".reveal")) return;

      el.classList.add("reveal");
      el.addEventListener("animationend", function done(event) {
        if (event.target !== el) return;
        el.removeEventListener("animationend", done);
        // Hand transform back to the element's own hover styles.
        el.classList.remove("reveal", "is-revealed");
        el.style.removeProperty("--reveal-delay");
      });
      observer.observe(el);
    }

    var singles = document.querySelectorAll(SINGLE.join(","));
    var groups = document.querySelectorAll(GROUPS.join(","));
    var targets = [];

    Array.prototype.forEach.call(singles, function (el) { targets.push(el); });
    Array.prototype.forEach.call(groups, function (group) {
      Array.prototype.forEach.call(group.children, function (child) { targets.push(child); });
    });

    // Document order, so an outer block is marked before anything inside it.
    targets.sort(function (a, b) {
      return a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
    });
    targets.forEach(function (el) { prepare(el, false); });

    // Grids re-rendered on the client (inventory filters, pagination) cascade in again.
    if ("MutationObserver" in window) {
      var watcher = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
          Array.prototype.forEach.call(mutation.addedNodes, function (node) {
            if (node.nodeType === 1) prepare(node, true);
          });
        });
      });
      Array.prototype.forEach.call(groups, function (group) {
        watcher.observe(group, { childList: true });
      });
    }
  }

  /* ----------------------------------------------------------------------
     Count-up numbers: <span data-count-up>150</span> counts from 0 once in view
     ---------------------------------------------------------------------- */
  function initCountUps() {
    var counters = document.querySelectorAll("[data-count-up]");
    if (!counters.length || reduceMotion || !("IntersectionObserver" in window)) return;

    var duration = 1200;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        observer.unobserve(el);

        var target = parseFloat(el.getAttribute("data-count-up"));
        if (!isFinite(target)) return;
        var start = performance.now();

        function tick(now) {
          var progress = Math.min((now - start) / duration, 1);
          el.textContent = Math.round(target * progress).toLocaleString("en-NG");
          if (progress < 1) window.requestAnimationFrame(tick);
        }

        el.textContent = "0";
        window.requestAnimationFrame(tick);
      });
    }, { threshold: 0.4 });

    Array.prototype.forEach.call(counters, function (el) {
      // Lock the width to the final value so the line does not reflow while counting.
      el.style.display = "inline-block";
      el.style.minWidth = el.offsetWidth + "px";
      el.style.fontVariantNumeric = "tabular-nums";
      observer.observe(el);
    });
  }

  /* ----------------------------------------------------------------------
     Sticky header gains a shadow once the page is scrolled
     ---------------------------------------------------------------------- */
  function initHeaderLift() {
    var header = document.querySelector(".site-header");
    if (!header) return;

    var ticking = false;
    function update() {
      ticking = false;
      header.classList.toggle("is-scrolled", window.scrollY > 8);
    }

    update();
    window.addEventListener("scroll", function () {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(update);
    }, { passive: true });
  }

  initReveals();
  initCountUps();
  initHeaderLift();
})();
