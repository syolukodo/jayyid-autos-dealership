/* ==========================================================================
   Jayyid Autos - admin-promo-codes.js
   Admin > Promo Codes form: show the targeting field that matches
   "Applies to", and filter the vehicle checklist by search term.
   ========================================================================== */
(function () {
  "use strict";

  var target = document.querySelector("[data-promo-target]");
  if (target) {
    var fields = document.querySelectorAll("[data-promo-target-field]");
    var sync = function () {
      fields.forEach(function (field) {
        field.hidden = field.getAttribute("data-promo-target-field") !== target.value;
      });
    };
    target.addEventListener("change", sync);
    sync();
  }

  var search = document.querySelector("[data-promo-vehicle-search]");
  if (search) {
    var items = document.querySelectorAll("[data-promo-vehicle]");
    var empty = document.querySelector("[data-promo-vehicle-empty]");
    search.addEventListener("keydown", function (event) {
      if (event.key === "Enter") event.preventDefault();
    });
    search.addEventListener("input", function () {
      var term = search.value.trim().toLowerCase();
      var visible = 0;
      items.forEach(function (item) {
        var match = term === "" || (item.getAttribute("data-search") || "").indexOf(term) !== -1;
        item.hidden = !match;
        if (match) visible++;
      });
      if (empty) empty.hidden = visible !== 0;
    });
  }
})();
