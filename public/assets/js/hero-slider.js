/* ==========================================================================
   Jayyid Autos - hero-slider.js
   Hero background carousel: continuous infinite autoplay (never pauses),
   3.5s display interval with a 0.5s opacity cross-fade between slides.
   Markup contract: .hero__track[data-hero-track] > .hero__slide[data-hero-slide]
   ========================================================================== */

(function () {
  "use strict";

  var track = document.querySelector("[data-hero-track]");
  var slides = document.querySelectorAll("[data-hero-slide]");
  if (!track || slides.length < 2) return;

  var index = 0;
  var delay = 3500;
  var fade = 500;

  slides[0].setAttribute("aria-hidden", "false");

  function show(next) {
    var current = slides[index];
    index = (next + slides.length) % slides.length;
    var incoming = slides[index];

    incoming.setAttribute("aria-hidden", "false");
    incoming.classList.add("is-active");
    current.classList.remove("is-active");

    window.setTimeout(function () {
      if (index !== Array.prototype.indexOf.call(slides, current)) {
        current.setAttribute("aria-hidden", "true");
      }
    }, fade);
  }

  window.setInterval(function () {
    show(index + 1);
  }, delay);
})();