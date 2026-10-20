/* ==========================================================================
   Jayyid Autos - selection.js
   Vehicle selection (mini-cart): add/remove/clear, sliding side drawer and the
   dynamic navigation counter. The server session (/api/selection) is the source
   of truth; localStorage mirrors it for instant paint before the first fetch.

   Signed-out visitors get an add-to-cart confirmation modal (with a "View in
   Cart" shortcut) and, on checkout, a modal sign-in / create-account step
   (/api/auth) instead of being bounced to the login page.
   ========================================================================== */
(function () {
  "use strict";

  var KEY = "ja_selection";
  var CONFIG = window.JA_SELECTION || {};
  var API = CONFIG.api || "api/selection";
  var AUTH = CONFIG.auth || "api/auth";
  var CHECKOUT = CONFIG.checkout || "account/checkout";
  var BASE = CONFIG.base || "";

  var state = [];

  /* ---------------------------------------------------------------- storage */

  function readStore() {
    try {
      var raw = window.localStorage.getItem(KEY);
      var parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      return [];
    }
  }

  function writeStore(items) {
    try {
      window.localStorage.setItem(KEY, JSON.stringify(items));
    } catch (error) {
      /* storage unavailable - state stays in memory for this view */
    }
  }

  /* ------------------------------------------------------------------- utils */

  function money(value) {
    return "\u20A6" + Number(value || 0).toLocaleString("en-NG");
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value).replace(/[&<>"']/g, function (char) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[char];
    });
  }

  /* Site-root-relative paths (and optimistic items created on another page
     depth) are resolved against this page's own base prefix. Absolute URLs,
     data URIs and fragments pass through untouched. */
  function rel(path) {
    var value = String(path == null ? "" : path);
    if (/^(?:[a-z][a-z0-9+.\-]*:|\/\/|#)/i.test(value)) return value;
    return BASE + value.replace(/^(?:\.\.\/)+/, "");
  }

  function isLogged() {
    if (CONFIG.logged === true || CONFIG.logged === "1" || CONFIG.logged === "true") return true;
    var node = document.querySelector("[data-selection-logged]");
    return !!node && node.getAttribute("data-selection-logged") === "1";
  }

  function toast(message) {
    var stack = document.querySelector(".toast-stack");
    if (!stack) {
      stack = document.createElement("div");
      stack.className = "toast-stack";
      document.body.appendChild(stack);
    }
    var node = document.createElement("div");
    node.className = "toast";
    node.setAttribute("role", "status");
    node.innerHTML =
      '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span></span>';
    node.querySelector("span").textContent = message;
    stack.appendChild(node);
    window.setTimeout(function () { node.classList.add("is-dismissed"); }, 2400);
    window.setTimeout(function () { if (node.parentNode) node.parentNode.removeChild(node); }, 3000);
  }

  /* ------------------------------------------------------------------ server */

  /* add -> POST /api/selection, remove -> DELETE /api/selection/{id}, clear -> DELETE /api/selection */
  function request(action, data) {
    if (action === "add") return window.jaHttp("POST", API, data);
    if (action === "remove") return window.jaHttp("DELETE", API + "/" + encodeURIComponent(data.vehicle_id));
    return window.jaHttp("DELETE", API);
  }

  /* check | login | register -> POST /api/auth/{action} */
  function authRequest(action, data) { return window.jaHttp("POST", AUTH + "/" + action, data); }

  function loadList() {
    return window.jaHttp("GET", API).catch(function () { return null; });
  }

  /* ------------------------------------------------------------------ render */

  function itemMarkup(item) {
    var meta = [item.condition, item.mileage, item.colour].filter(Boolean).join(" \u00b7 ");
    var gone = item.available === false;
    return '' +
      '<li class="selection-item' + (gone ? " is-unavailable" : "") + '" data-id="' + escapeHtml(item.id) + '">' +
        '<a class="selection-item__media" href="' + escapeHtml(rel(item.url)) + '">' +
          '<img src="' + escapeHtml(rel(item.image)) + '" alt="' + escapeHtml(item.title) + '" loading="lazy" width="120" height="90">' +
        '</a>' +
        '<div class="selection-item__body">' +
          '<a class="selection-item__title" href="' + escapeHtml(rel(item.url)) + '">' + escapeHtml(item.title) + '</a>' +
          (meta ? '<p class="selection-item__meta">' + escapeHtml(meta) + '</p>' : '') +
          '<p class="selection-item__price">' + escapeHtml(item.price_label || money(item.price)) + '</p>' +
          (gone
            ? '<p class="selection-item__unavailable">No longer available - please remove</p>'
            : '<p class="selection-item__fulfil">' +
                '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v5c0 4.4-3 8.3-7 9.5C8 19.3 5 15.4 5 11V6z"/><path d="m9 12 2 2 4-4"/></svg>' +
                'Inspected &amp; Fulfilled by Jayyid Autos' +
              '</p>') +
        '</div>' +
        '<button class="selection-item__remove" type="button" data-selection-remove="' + escapeHtml(item.id) + '" aria-label="Remove ' + escapeHtml(item.title) + ' from selection">' +
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>' +
        '</button>' +
      '</li>';
  }

  function render() {
    var count = state.length;
    /* Reserved or sold vehicles stay listed (so the buyer sees why) but are not priced. */
    var subtotal = state.reduce(function (sum, item) { return item.available === false ? sum : sum + Number(item.price || 0); }, 0);

    document.querySelectorAll("[data-selection-count]").forEach(function (node) {
      node.textContent = String(count);
      node.hidden = count === 0;
    });
    document.querySelectorAll("[data-selection-link]").forEach(function (node) {
      node.classList.toggle("has-items", count > 0);
      node.setAttribute("aria-label", "Your vehicle selection (" + count + " vehicle" + (count === 1 ? "" : "s") + ")");
    });

    var list = document.querySelector("[data-selection-items]");
    if (list) {
      list.innerHTML = state.map(itemMarkup).join("");
    }
    document.querySelectorAll("[data-selection-count-label]").forEach(function (node) {
      node.textContent = String(count);
    });
    document.querySelectorAll("[data-selection-checkout-label]").forEach(function (node) {
      node.textContent = String(count);
    });
    document.querySelectorAll("[data-selection-subtotal]").forEach(function (node) {
      node.textContent = money(subtotal);
    });
    document.querySelectorAll("[data-selection-empty]").forEach(function (node) {
      node.hidden = count > 0;
    });
    document.querySelectorAll("[data-selection-foot]").forEach(function (node) {
      node.hidden = count === 0;
    });
    document.querySelectorAll("[data-selection-clear]").forEach(function (node) {
      node.disabled = count === 0;
    });
    document.querySelectorAll("[data-selection-proceed]").forEach(function (node) {
      node.classList.toggle("is-disabled", count === 0);
      node.setAttribute("aria-disabled", count === 0 ? "true" : "false");
    });
  }

  function adopt(items) {
    state = Array.isArray(items) ? items : [];
    writeStore(state);
    render();
  }

  /* ------------------------------------------------------------------ drawer */

  function drawer() { return document.querySelector("[data-selection-drawer]"); }
  function overlay() { return document.querySelector("[data-selection-overlay]"); }

  function openDrawer() {
    var node = drawer();
    var mask = overlay();
    if (!node) return;
    node.classList.add("is-open");
    node.setAttribute("aria-hidden", "false");
    if (mask) mask.classList.add("is-open");
    document.body.classList.add("is-locked");
  }

  function closeDrawer() {
    var node = drawer();
    var mask = overlay();
    if (!node) return;
    node.classList.remove("is-open");
    node.setAttribute("aria-hidden", "true");
    if (mask) mask.classList.remove("is-open");
    document.body.classList.remove("is-locked");
  }

  /* ------------------------------------------------------------------- modals */

  function modal(selector) { return document.querySelector(selector); }
  function cartModal() { return modal("[data-cart-modal]"); }
  function authModal() { return modal("[data-auth-modal]"); }

  function openModal(node) {
    if (!node) return;
    node.classList.add("is-open");
    node.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
  }

  function closeModal(node) {
    if (!node) return;
    node.classList.remove("is-open");
    node.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-open");
  }

  function openCartModal(button, isNew) {
    var node = cartModal();
    if (!node) { openDrawer(); return; }

    var title = button.getAttribute("data-title") || "Vehicle";
    var url = rel(button.getAttribute("data-url") || "#");

    node.querySelectorAll("[data-cart-link]").forEach(function (link) { link.setAttribute("href", url); });
    var titleNode = node.querySelector("[data-cart-title]");
    if (titleNode) titleNode.textContent = title;
    var condNode = node.querySelector("[data-cart-condition]");
    if (condNode) condNode.textContent = button.getAttribute("data-condition") || "";
    var priceNode = node.querySelector("[data-cart-price]");
    if (priceNode) priceNode.textContent = button.getAttribute("data-price-label") || money(button.getAttribute("data-price"));
    var img = node.querySelector("[data-cart-image]");
    if (img) { img.setAttribute("src", rel(button.getAttribute("data-image") || "")); img.setAttribute("alt", title); }

    var specs = node.querySelector("[data-cart-specs]");
    if (specs) {
      var rows = [
        ["Mileage", button.getAttribute("data-mileage")],
        ["Colour", button.getAttribute("data-colour")],
        ["Condition", button.getAttribute("data-condition")]
      ];
      specs.innerHTML = rows.filter(function (row) { return row[1]; }).map(function (row) {
        return '<li><span class="cart-modal__spec-label">' + escapeHtml(row[0]) + '</span>' + escapeHtml(row[1]) + '</li>';
      }).join("");
    }

    var heading = node.querySelector("#cart-modal-title");
    if (heading) heading.textContent = isNew ? "Added to Your Selection" : "Already in Your Selection";

    openModal(node);
  }

  /* --------------------------------------------------------------- confirm */

  /* In-app replacement for window.confirm: promise-based so callers stay
     readable, and the same modal is shared with checkout (/account/checkout)
     through window.JA_CONFIRM. */
  function confirmModal() { return modal("[data-confirm-modal]"); }

  var confirmResolve = null;

  function settleConfirm(answer) {
    if (confirmResolve) {
      var resolve = confirmResolve;
      confirmResolve = null;
      resolve(answer);
    }
    closeModal(confirmModal());
  }

  function askConfirm(message, options) {
    var node = confirmModal();
    var text = message || "Are you sure?";
    if (!node) {
      /* Markup missing: never fall back to a blocking browser dialog. */
      return window.Promise.resolve(true);
    }

    options = options || {};
    if (confirmResolve) settleConfirm(false);

    var title = node.querySelector("[data-confirm-title]");
    if (title) title.textContent = options.title || "Please confirm";
    var body = node.querySelector("[data-confirm-text]");
    if (body) body.textContent = text;
    var label = node.querySelector("[data-confirm-accept-label]");
    if (label) label.textContent = options.acceptLabel || "Remove All";

    openModal(node);
    var accept = node.querySelector("[data-confirm-accept]");
    if (accept) window.setTimeout(function () { accept.focus(); }, 60);

    return new window.Promise(function (resolve) { confirmResolve = resolve; });
  }

  /* Shared with any page that includes the selection drawer but not this
     file's internals (e.g. the checkout drawer on /account/checkout). */
  window.JA_CONFIRM = askConfirm;

  /* ------------------------------------------------------------------ auth */

  var authEmail = "";

  function authError(message) {
    var node = modal("[data-auth-error]");
    if (!node) return;
    node.textContent = message || "";
    node.hidden = !message;
  }

  function setAuthStep(step) {
    var node = authModal();
    if (!node) return;
    node.querySelectorAll("[data-auth-step]").forEach(function (form) {
      form.hidden = form.getAttribute("data-auth-step") !== step;
    });
    node.querySelectorAll("[data-auth-email-mirror]").forEach(function (input) { input.value = authEmail; });
    node.querySelectorAll("[data-auth-welcome], [data-auth-email-label]").forEach(function (label) {
      label.textContent = authEmail || "you";
    });
    var emailInput = node.querySelector("#auth-email");
    if (emailInput && step === "email") emailInput.value = authEmail;
    authError("");
    var focus = node.querySelector('[data-auth-step]:not([hidden]) input:not([type="hidden"])');
    if (focus) window.setTimeout(function () { focus.focus(); }, 60);
  }

  function openAuthModal() {
    var node = authModal();
    if (!node) return;
    authEmail = "";
    var emailInput = node.querySelector("#auth-email");
    if (emailInput) emailInput.value = "";
    setAuthStep("email");
    openModal(node);
  }

  function authSubmit(form, action, data) {
    var button = form.querySelector("[data-auth-submit]");
    if (button) button.disabled = true;
    authError("");
    return authRequest(action, data)
      .catch(function () { return null; })
      .then(function (result) {
        if (button) button.disabled = false;
        if (!result) { authError("Something went wrong. Please try again."); return null; }
        if (!result.ok) { authError(result.message || "Something went wrong. Please try again."); return null; }
        return result;
      });
  }

  function proceedToCheckout() {
    window.location.href = CHECKOUT;
  }

  function bindAuthForms() {
    var node = authModal();
    if (!node) return;

    var emailForm = node.querySelector('[data-auth-step="email"]');
    if (emailForm) {
      emailForm.addEventListener("submit", function (event) {
        event.preventDefault();
        var input = node.querySelector("#auth-email");
        var value = (input ? input.value : "").trim();
        if (!value) { authError("Please enter your email address."); return; }
        authEmail = value.toLowerCase();
        authSubmit(emailForm, "check", { email: authEmail }).then(function (result) {
          if (result) setAuthStep(result.exists ? "password" : "register");
        });
      });
    }

    var passwordForm = node.querySelector('[data-auth-step="password"]');
    if (passwordForm) {
      passwordForm.addEventListener("submit", function (event) {
        event.preventDefault();
        var input = passwordForm.querySelector('[name="password"]');
        var value = input ? input.value : "";
        if (!value) { authError("Please enter your password."); return; }
        authSubmit(passwordForm, "login", { email: authEmail, password: value }).then(function (result) {
          if (result) proceedToCheckout();
        });
      });
    }

    var registerForm = node.querySelector('[data-auth-step="register"]');
    if (registerForm) {
      registerForm.addEventListener("submit", function (event) {
        event.preventDefault();
        var name = (registerForm.querySelector('[name="name"]') || {}).value || "";
        var phone = (registerForm.querySelector('[name="phone"]') || {}).value || "";
        var password = (registerForm.querySelector('[name="password"]') || {}).value || "";
        if (!name.trim() || !phone.trim() || !password) { authError("Please complete every field before continuing."); return; }
        authSubmit(registerForm, "register", { email: authEmail, name: name.trim(), phone: phone.trim(), password: password }).then(function (result) {
          if (!result) return;
          if (result.verify && result.redirect) { window.location.href = result.redirect; return; }
          proceedToCheckout();
        });
      });
    }
  }

  /* ------------------------------------------------------------------ events */

  function optimisticItem(button) {
    return {
      id: button.getAttribute("data-vehicle-id") || "",
      title: button.getAttribute("data-title") || "Vehicle",
      condition: button.getAttribute("data-condition") || "",
      mileage: button.getAttribute("data-mileage") || "",
      colour: button.getAttribute("data-colour") || "",
      price: parseInt(button.getAttribute("data-price") || "0", 10) || 0,
      price_label: button.getAttribute("data-price-label") || "",
      image: button.getAttribute("data-image") || "",
      url: button.getAttribute("data-url") || "#"
    };
  }

  function onAdd(button) {
    var item = optimisticItem(button);
    if (!item.id) return;

    var isNew = !state.some(function (row) { return row.id === item.id; });
    if (isNew) {
      state.push(item);
      writeStore(state);
      render();
    }

    if (isLogged()) {
      openDrawer();
    } else {
      openCartModal(button, isNew);
    }

    request("add", { vehicle_id: item.id })
      .then(function (result) {
        if (result && result.ok) {
          adopt(result.items);
          /* Confirm only once the server accepted it (a car can be reserved meanwhile). */
          if (isLogged()) toast(isNew ? "Added to your selection" : "Already in your selection");
          return;
        }
        /* Refused (e.g. reserved by another buyer): undo the optimistic add. */
        if (result && Array.isArray(result.items)) adopt(result.items);
        if (result && result.message) toast(result.message);
      })
      .catch(function () { loadList().then(function (r) { if (r && r.ok) adopt(r.items); }); });
  }

  function onRemove(id) {
    state = state.filter(function (row) { return row.id !== id; });
    writeStore(state);
    render();
    request("remove", { vehicle_id: id })
      .then(function (result) { if (result && result.ok) adopt(result.items); });
  }

  function onClear() {
    if (!state.length) {
      clearSelection();
      return;
    }
    askConfirm("Clear every vehicle from your selection?", {
      title: "Clear your selection?",
      acceptLabel: "Remove All"
    }).then(function (confirmed) {
      if (confirmed) clearSelection();
    });
  }

  function clearSelection() {
    state = [];
    writeStore(state);
    render();
    request("clear", {})
      .then(function (result) { if (result && result.ok) adopt(result.items); });
  }

  function bind() {
    document.addEventListener("click", function (event) {
      var add = event.target.closest("[data-selection-add]");
      if (add) {
        event.preventDefault();
        onAdd(add);
        return;
      }

      if (event.target.closest("[data-confirm-accept]")) {
        event.preventDefault();
        settleConfirm(true);
        return;
      }

      if (event.target.closest("[data-confirm-cancel]")) {
        event.preventDefault();
        settleConfirm(false);
        return;
      }

      if (event.target.closest("[data-cart-view]")) {
        event.preventDefault();
        closeModal(cartModal());
        openDrawer();
        return;
      }

      if (event.target.closest("[data-cart-modal-close]")) {
        event.preventDefault();
        closeModal(cartModal());
        return;
      }

      if (event.target.closest("[data-auth-modal-close]")) {
        event.preventDefault();
        closeModal(authModal());
        return;
      }

      var goto = event.target.closest("[data-auth-goto]");
      if (goto) {
        event.preventDefault();
        var target = goto.getAttribute("data-auth-goto");
        if (target !== "email" && !authEmail) {
          var emailField = document.querySelector("#auth-email");
          var typed = emailField ? (emailField.value || "").trim() : "";
          if (typed) {
            authEmail = typed.toLowerCase();
          } else {
            authError("Please enter your email address first.");
            if (emailField) emailField.focus();
            return;
          }
        }
        setAuthStep(target);
        return;
      }

      var proceed = event.target.closest("[data-selection-proceed]");
      if (proceed) {
        if (isLogged() || proceed.classList.contains("is-disabled")) return;
        event.preventDefault();
        openAuthModal();
        return;
      }

      var remove = event.target.closest("[data-selection-remove]");
      if (remove) {
        event.preventDefault();
        onRemove(remove.getAttribute("data-selection-remove"));
        return;
      }

      if (event.target.closest("[data-selection-clear]")) {
        event.preventDefault();
        onClear();
        return;
      }

      var link = event.target.closest("[data-selection-link]");
      if (link && !link.classList.contains("is-disabled")) {
        event.preventDefault();
        openDrawer();
        return;
      }

      if (event.target.closest("[data-selection-close]") || event.target === overlay()) {
        closeDrawer();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape") return;
      var confirmBox = confirmModal();
      if (confirmBox && confirmBox.classList.contains("is-open")) { settleConfirm(false); return; }
      var auth = authModal();
      if (auth && auth.classList.contains("is-open")) { closeModal(auth); return; }
      var cart = cartModal();
      if (cart && cart.classList.contains("is-open")) { closeModal(cart); return; }
      if (drawer() && drawer().classList.contains("is-open")) closeDrawer();
    });
  }

  /* -------------------------------------------------------------------- boot */

  document.addEventListener("DOMContentLoaded", function () {
    if (!drawer()) return;
    adopt(readStore());
    bind();
    bindAuthForms();
    loadList().then(function (result) {
      if (result && result.ok) adopt(result.items);
    });
  });
})();
