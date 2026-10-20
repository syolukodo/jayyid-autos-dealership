/* ==========================================================================
   Jayyid Autos - search-filter.js
   Live inventory filtering, sorting and pagination against /api/vehicles
   ========================================================================== */
(function () {
  "use strict";

  var grid = document.getElementById("inventory-grid");
  if (!grid) return;

  var form = document.getElementById("filter-form");
  var countEl = document.getElementById("inventory-count");
  var paginationEl = document.getElementById("inventory-pagination");
  var sortEl = document.getElementById("sort-by");
  var PER_PAGE = Number(grid && grid.getAttribute("data-per-page")) || 12;
  var DATA_URL = (grid && grid.getAttribute("data-catalogue")) || "api/vehicles";

  var state = {
    condition: "",
    make: "",
    body: "",
    budget: "",
    sort: "newest",
    page: 1
  };

  /* ----------------------------------------------------------------------
     Helpers
     ---------------------------------------------------------------------- */
  function mileageNumber(value) {
    return parseInt(String(value).replace(/[^0-9]/g, ""), 10) || 0;
  }

  function formatNaira(value) {
    var n = Math.round(Number(value) || 0);
    return "\u20a6" + String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function cardMarkup(vehicle) {
    var link = escapeHtml(vehicle.url);
    var badge = vehicle.condition
      ? '<span class="car-card__badge">' + escapeHtml(vehicle.condition) + "</span>"
      : "";
    var price = Number(vehicle.price) || 0;
    var original = Number(vehicle.original_price) || 0;
    var hasDiscount = original > price;
    var discount = hasDiscount ? Math.round((1 - price / original) * 100) : 0;
    var pricing =
      '<div class="car-card__pricing">' +
        '<span class="car-card__price">' + formatNaira(price) + "</span>" +
        (hasDiscount
          ? '<span class="car-card__price-old">' + formatNaira(original) + "</span>" +
            '<span class="car-card__discount" aria-label="Save ' + discount + ' percent">-' + discount + "%</span>"
          : "") +
      "</div>";
    var save =
      '<button type="button" class="car-card__save" data-save-toggle data-vehicle-id="' + escapeHtml(vehicle.id) + '" aria-pressed="false" aria-label="Save ' + escapeHtml(vehicle.title) + ' to your dashboard">' +
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>' +
      "</button>";
    var add = vehicle.status && vehicle.status !== "Available"
      ? '<button type="button" class="btn btn--accent btn--sm btn--block is-unavailable" disabled>' + escapeHtml(vehicle.status === "Sold" ? "Sold" : "Reserved") + "</button>"
      : '<button type="button" class="btn btn--accent btn--sm btn--block" ' +
        'data-selection-add ' +
        'data-vehicle-id="' + escapeHtml(vehicle.id) + '" ' +
        'data-title="' + escapeHtml(vehicle.title) + '" ' +
        'data-condition="' + escapeHtml(vehicle.condition) + '" ' +
        'data-mileage="' + escapeHtml(vehicle.mileage) + '" ' +
        'data-colour="' + escapeHtml(vehicle.colour) + '" ' +
        'data-price="' + price + '" ' +
        'data-price-label="' + escapeHtml(formatNaira(price)) + '" ' +
        'data-image="' + escapeHtml(vehicle.image) + '" ' +
        'data-url="' + link + '">' +
        '<svg class="car-card__foot-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>' +
        "Add to Selection" +
      "</button>";
    return (
      '<article class="car-card">' +
        '<div class="car-card__media">' +
          '<img src="' + escapeHtml(vehicle.image) + '" alt="' + escapeHtml(vehicle.title) + '" loading="lazy" width="640" height="400">' +
          '<div class="car-card__tags">' + badge + save + "</div>" +
        "</div>" +
        '<div class="car-card__body">' +
          '<h3 class="car-card__title"><a href="' + link + '">' + escapeHtml(vehicle.title) + "</a></h3>" +
          '<ul class="car-card__specs">' +
            '<li><span class="car-card__spec-label">Transmission</span><span class="car-card__spec-value">' + escapeHtml(vehicle.transmission) + "</span></li>" +
            '<li><span class="car-card__spec-label">Fuel</span><span class="car-card__spec-value">' + escapeHtml(vehicle.fuel) + "</span></li>" +
            '<li><span class="car-card__spec-label">Mileage</span><span class="car-card__spec-value">' + escapeHtml(vehicle.mileage) + "</span></li>" +
          "</ul>" +
          pricing +
          '<div class="car-card__foot">' +
            add +
            '<a class="btn btn--outline btn--sm btn--block" href="' + link + '">View Details</a>' +
          "</div>" +
        "</div>" +
      "</article>"
    );
  }

  function applyFilters(list) {
    return list.filter(function (vehicle) {
      if (state.condition && vehicle.condition !== state.condition) return false;
      if (state.make && vehicle.make !== state.make) return false;
      if (state.body && vehicle.category !== state.body) return false;
      if (state.budget) {
        var range = state.budget.split("-");
        var min = Number(range[0]);
        var max = Number(range[1]);
        if (vehicle.price < min || vehicle.price > max) return false;
      }
      return true;
    });
  }

  function applySort(list) {
    var sorted = list.slice();
    switch (state.sort) {
      case "price-asc":
        sorted.sort(function (a, b) { return a.price - b.price; });
        break;
      case "price-desc":
        sorted.sort(function (a, b) { return b.price - a.price; });
        break;
      case "mileage-asc":
        sorted.sort(function (a, b) { return mileageNumber(a.mileage) - mileageNumber(b.mileage); });
        break;
      case "newest":
      default:
        sorted.sort(function (a, b) {
          if (b.year !== a.year) return b.year - a.year;
          return (b.featured === true) - (a.featured === true);
        });
    }
    return sorted;
  }

  function renderCount(total, shown) {
    if (!countEl) return;
    if (total === 0) {
      countEl.innerHTML = "No vehicles match the selected filters.";
      return;
    }
    countEl.innerHTML =
      "Showing <strong>" + shown + "</strong> of <strong>" + total + "</strong> vehicles in stock";
  }

  function renderPagination(total) {
    if (!paginationEl) return;
    var pages = Math.max(1, Math.ceil(total / PER_PAGE));
    if (state.page > pages) state.page = pages;

    if (pages <= 1) {
      paginationEl.innerHTML = "";
      paginationEl.hidden = true;
      return;
    }

    paginationEl.hidden = false;
    var html = '<li><button type="button" class="pagination__link' +
      (state.page === 1 ? " is-disabled" : "") + '" data-page="' + Math.max(1, state.page - 1) + '" aria-label="Previous page">Previous</button></li>';

    for (var i = 1; i <= pages; i++) {
      html += '<li><button type="button" class="pagination__link' +
        (i === state.page ? " is-active" : "") + '" data-page="' + i + '"' +
        (i === state.page ? ' aria-current="page"' : "") + ">" + i + "</button></li>";
    }

    html += '<li><button type="button" class="pagination__link' +
      (state.page === pages ? " is-disabled" : "") + '" data-page="' + (state.page + 1) + '" aria-label="Next page">Next</button></li>';

    paginationEl.innerHTML = html;
  }

  /* Landing pages (/cars-for-sale/toyota) carry their own filter in the path.
     While the visitor stays on that filter the URL keeps the landing path;
     any other combination moves to /inventory?... */
  var LANDING = JSON.parse(grid.getAttribute("data-landing") || "{}");
  var LANDING_PATH = grid.getAttribute("data-landing-path") || "";
  var INVENTORY_PATH = grid.getAttribute("data-inventory-path") || window.location.pathname;
  var FILTER_KEYS = ["condition", "make", "body", "budget"];

  function onLandingFilter() {
    if (!LANDING_PATH) return false;
    return FILTER_KEYS.every(function (key) { return (state[key] || "") === (LANDING[key] || ""); });
  }

  function syncUrl() {
    if (!window.history || !window.history.replaceState) return;
    var params = new URLSearchParams();
    var landing = onLandingFilter();
    if (!landing) {
      FILTER_KEYS.forEach(function (key) { if (state[key]) params.set(key, state[key]); });
    }
    if (state.sort && state.sort !== "newest") params.set("sort", state.sort);
    if (state.page > 1) params.set("page", String(state.page));
    var query = params.toString();
    var path = landing ? LANDING_PATH : INVENTORY_PATH;
    window.history.replaceState(null, "", path + (query ? "?" + query : ""));
  }

  function render() {
    var matched = applyFilters(window.JAYYID_VEHICLES || []);
    var sorted = applySort(matched);
    var total = sorted.length;
    var pages = Math.max(1, Math.ceil(total / PER_PAGE));
    if (state.page > pages) state.page = pages;

    var start = (state.page - 1) * PER_PAGE;
    var slice = sorted.slice(start, start + PER_PAGE);

    if (slice.length) {
      grid.innerHTML = slice.map(cardMarkup).join("");
      grid.querySelectorAll("img").forEach(function (img) {
        img.addEventListener("error", function () {
          var frame = img.closest(".car-card__media");
          if (frame) frame.classList.add("is-missing");
        });
      });
      if (typeof window.jaSyncSaveButtons === "function") {
        window.jaSyncSaveButtons();
      }
    } else {
      grid.innerHTML =
        '<div class="empty-state">' +
          "<h3>No vehicles match these filters</h3>" +
          "<p>Widen your budget range or clear a filter to see more of our current stock.</p>" +
          '<p style="margin-top:18px"><button type="button" class="btn btn--outline btn--sm" data-reset-filters>Clear all filters</button></p>' +
        "</div>";
    }

    renderCount(total, slice.length);
    renderPagination(total);
    syncUrl();
  }

  function readFormState() {
    if (!form) return;
    var data = new FormData(form);
    state.condition = String(data.get("condition") || "");
    state.make = String(data.get("make") || "");
    state.body = String(data.get("body") || "");
    state.budget = String(data.get("budget") || "");
    if (sortEl) state.sort = sortEl.value;
  }

  function resetFilters() {
    if (form) form.reset();
    if (sortEl) sortEl.value = "newest";
    state.condition = "";
    state.make = "";
    state.body = "";
    state.budget = "";
    state.sort = "newest";
    state.page = 1;
    render();
  }

  /* ----------------------------------------------------------------------
     Events
     ---------------------------------------------------------------------- */
  if (form) {
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      readFormState();
      state.page = 1;
      render();
    });

    form.addEventListener("change", function () {
      readFormState();
      state.page = 1;
      render();
    });
  }

  if (sortEl) {
    sortEl.addEventListener("change", function () {
      state.sort = sortEl.value;
      state.page = 1;
      render();
    });
  }

  if (paginationEl) {
    paginationEl.addEventListener("click", function (event) {
      var btn = event.target.closest("[data-page]");
      if (!btn || btn.classList.contains("is-disabled")) return;
      state.page = Number(btn.getAttribute("data-page")) || 1;
      render();
      grid.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  }

  document.addEventListener("click", function (event) {
    if (event.target.closest("[data-reset-filters]")) resetFilters();
  });

  /* ----------------------------------------------------------------------
     Boot - seed state from the URL, then take over rendering
     ---------------------------------------------------------------------- */
  function seedFromUrl() {
    var params = new URLSearchParams(window.location.search);
    state.condition = params.get("condition") || LANDING.condition || "";
    state.make = params.get("make") || LANDING.make || "";
    state.body = params.get("body") || LANDING.body || "";
    state.budget = params.get("budget") || LANDING.budget || "";
    state.sort = params.get("sort") || "newest";
    state.page = parseInt(params.get("page") || "1", 10) || 1;

    if (form) {
      if (form.elements.condition) form.elements.condition.value = state.condition;
      if (form.elements.make) form.elements.make.value = state.make;
      if (form.elements.body) form.elements.body.value = state.body;
      if (form.elements.budget) form.elements.budget.value = state.budget;
    }
    if (sortEl) sortEl.value = state.sort;
  }

  seedFromUrl();

  fetch(DATA_URL, { cache: "no-store" })
    .then(function (response) {
      if (!response.ok) throw new Error("Failed to load vehicle data");
      return response.json();
    })
    .then(function (data) {
      window.JAYYID_VEHICLES = data;
      render();
    })
    .catch(function () {
      grid.innerHTML =
        '<div class="empty-state"><h3>Vehicle data could not be loaded</h3>' +
        "<p>Reload the page to try again, or contact our sales desk on +234 803 456 7890.</p></div>";
      if (countEl) countEl.textContent = "";
      if (paginationEl) paginationEl.innerHTML = "";
    });
})();
