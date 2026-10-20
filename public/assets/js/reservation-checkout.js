/* ==========================================================================
   Jayyid Autos - reservation-checkout.js
   The 4-step reservation checkout on /account/checkout: selected vehicles,
   delivery & registration, payment method and confirmation. Totals shown
   here are a preview; the server re-derives the amount when the payment
   starts, and the promo code is validated server-side.
   Configuration comes from data-* attributes on #checkout-workspace.
   ========================================================================== */
(function () {
  "use strict";

  var ws = document.getElementById("checkout-workspace");
  if (!ws) return;

  var cfg = {
    live: ws.getAttribute("data-live") === "1",
    paymentsApi: ws.getAttribute("data-payments-api"),
    promoApi: ws.getAttribute("data-promo-api"),
    selectionApi: ws.getAttribute("data-selection-api"),
    doneUrl: ws.getAttribute("data-done"),
    previewUrl: ws.getAttribute("data-preview")
  };
  var subtotal = Number(ws.getAttribute("data-subtotal") || 0);
  var documentation = Number(ws.getAttribute("data-documentation") || 0);
  var taxRate = Number(ws.getAttribute("data-tax-rate") || 0);
  /* Delivery is priced per vehicle: the state's zone rate x the body type's size factor
     (mirrors App\Services\Delivery\DeliveryRates; the server figure is the one charged). */
  function readJson(attr, fallback) {
    try { return JSON.parse(ws.getAttribute(attr) || "") || fallback; } catch (e) { return fallback; }
  }
  var deliveryRates = readJson("data-delivery-rates", { states: {}, rates: {}, factors: {} });
  var vehicleCategories = readJson("data-vehicle-categories", []);
  var promotion = "";
  var promoDiscount = 0;
  var delivery = "pickup";
  var gateway = "paystack";
  var cardBrand = "mastercard";
  var savedBrand = "mastercard";
  var step = parseInt(ws.getAttribute("data-ws-initial") || "1", 10) || 1;

  function money(value) { return "₦" + Number(value || 0).toLocaleString("en-NG"); }
  function set(selector, text) {
    ws.querySelectorAll(selector).forEach(function (node) { node.textContent = text; });
  }

  function renderSteps() {
    ws.querySelectorAll("[data-ws-step]").forEach(function (node) {
      var n = parseInt(node.getAttribute("data-ws-step"), 10);
      node.classList.toggle("is-active", n === step);
      node.classList.toggle("is-done", n < step);
    });
    ws.querySelectorAll("[data-ws-connector]").forEach(function (node) {
      node.classList.toggle("is-done", parseInt(node.getAttribute("data-ws-connector"), 10) < step);
    });
    ws.querySelectorAll("[data-ws-panel]").forEach(function (node) {
      node.classList.toggle("is-active", node.getAttribute("data-ws-panel") === String(step));
    });
    var back = ws.querySelector("[data-ws-back]");
    var next = ws.querySelector("[data-ws-next]");
    if (back) back.hidden = step === 1;
    if (next) next.hidden = step === 4;
    /* Step 3 owns its own actions (pay CTA + Back to Delivery), so the shared row is hidden there. */
    var navRow = ws.querySelector("[data-ws-nav]");
    if (navRow) navRow.hidden = step === 3;
  }

  function field(name) {
    var input = ws.querySelector('[data-ws-field="' + name + '"]');
    return input ? (input.value || "").trim() : "";
  }

  /* Fee to the chosen state, or null until a served state is picked. */
  function deliveryQuote() {
    var zone = deliveryRates.states[field("state")];
    if (!zone) return null;
    var rate = Number(deliveryRates.rates[zone] || 0);
    return vehicleCategories.reduce(function (sum, category) {
      var factor = Number(deliveryRates.factors[category] || 100);
      return sum + Math.round(rate * factor / 100 / 1000) * 1000;
    }, 0);
  }

  function renderDeliveryPrice() {
    var label = ws.querySelector("[data-ws-delivery-price]");
    if (!label) return;
    if (!label.hasAttribute("data-from")) label.setAttribute("data-from", label.textContent);
    var quote = deliveryQuote();
    label.textContent = quote === null ? label.getAttribute("data-from") : "+ " + money(quote) + " to " + field("state");
  }

  function compute() {
    var tax = Math.round(subtotal * taxRate);
    var logistics = delivery === "delivery" ? (deliveryQuote() || 0) : 0;
    var discount = promotion ? Math.min(promoDiscount, subtotal) : 0;
    var total = Math.max(0, subtotal + documentation + tax + logistics - discount);
    return { tax: tax, logistics: logistics, discount: discount, total: total };
  }

  /* PayPal does not take naira: show the converted amount (the server converts it again when charging). */
  var paypalRate = Number(ws.getAttribute("data-paypal-rate") || 0);
  var paypalCurrency = ws.getAttribute("data-paypal-currency") || "USD";
  function paypalAmount(naira) {
    if (!paypalRate || paypalCurrency === "NGN") return "";
    var minor = Math.ceil(Math.round(naira * 100 / paypalRate * 1e6) / 1e6);
    var symbols = { USD: "$", GBP: "\u00a3", EUR: "\u20ac" };
    var value = (minor / 100).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return symbols[paypalCurrency] ? symbols[paypalCurrency] + value : value + " " + paypalCurrency;
  }

  function updatePayLabel() {
    var names = { paystack: "Paystack", card: "Card", paypal: "PayPal" };
    var total = compute().total;
    var converted = paypalAmount(total);
    set("[data-ws-paypal-amount]", converted);
    set("[data-ws-pay-label]", gateway === "paypal" && converted
      ? "Pay " + converted + " via PayPal (" + money(total) + ")"
      : "Pay " + money(total) + " via " + (names[gateway] || "Paystack"));
  }

  function renderSummary() {
    var c = compute();
    set("[data-ws-tax]", money(c.tax));
    set("[data-ws-logistics]", money(c.logistics));
    set("[data-ws-logistics-state]", field("state") ? " to " + field("state") : "");
    renderDeliveryPrice();
    var lr = ws.querySelector("[data-ws-logistics-row]");
    if (lr) lr.hidden = c.logistics === 0;
    var dr = ws.querySelector("[data-ws-discount-row]");
    if (dr) dr.hidden = c.discount === 0;
    set("[data-ws-discount]", c.discount ? ("-" + money(c.discount)) : money(0));
    set("[data-ws-total]", money(c.total));
    set("[data-ws-confirm-total]", money(c.total));
    updatePayLabel();
  }

  function renderConfirm() {
    var labels = { pickup: "Showroom Pickup", delivery: "Doorstep Flatbed Delivery" };
    var method;
    if (gateway === "paypal") {
      method = "PayPal";
    } else if (gateway === "card") {
      method = (cardBrand === "visa" ? "Visa" : "Mastercard") + " via Paystack";
    } else {
      method = (savedBrand === "visa" ? "Visa" : "Mastercard") + " via Paystack";
    }
    var destination = [field("city"), field("state")].filter(Boolean).join(", ");
    set("[data-ws-confirm-delivery]", delivery === "delivery" && destination
      ? "Doorstep Delivery to " + destination
      : (labels[delivery] || "Showroom Pickup"));
    set("[data-ws-confirm-gateway]", method);
  }

  function go(n) {
    step = Math.max(1, Math.min(4, n));
    renderSteps();
    renderConfirm();
    ws.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  /* Delivery toggle */
  ws.querySelectorAll("[data-ws-delivery-input]").forEach(function (input) {
    input.addEventListener("change", function () {
      delivery = input.value;
      ws.querySelectorAll("[data-ws-delivery] .ws-option").forEach(function (opt) {
        opt.classList.toggle("is-selected", opt.contains(input));
      });
      showDeliveryError("");
      renderSummary();
    });
  });

  ["state", "city", "address"].forEach(function (name) {
    var input = ws.querySelector('[data-ws-field="' + name + '"]');
    if (!input) return;
    input.addEventListener(name === "state" ? "change" : "input", function () {
      showDeliveryError("");
      renderSummary();
      renderConfirm();
    });
  });

  function showDeliveryError(message) {
    var box = ws.querySelector("[data-ws-delivery-error]");
    if (!box) return;
    box.textContent = message;
    box.classList.toggle("hidden", !message);
    box.classList.toggle("flex", !!message);
  }

  /* Doorstep delivery needs a served state and a street address before payment. */
  function deliveryReady() {
    if (delivery !== "delivery") return true;
    if (deliveryQuote() === null) { showDeliveryError("Choose the state we should deliver to."); return false; }
    if (!field("address")) { showDeliveryError("Enter the street address for doorstep delivery."); return false; }
    return true;
  }

  /* Category tabs (Paystack / Credit Card / PayPal / Other) */
  function applyGateway() {
    ws.querySelectorAll("[data-ws-gateway-input]").forEach(function (input) {
      var tab = input.closest(".ws-tab");
      if (tab) tab.classList.toggle("is-active", input.value === gateway);
    });
    ws.querySelectorAll("[data-ws-tabpanel]").forEach(function (panel) {
      panel.hidden = panel.getAttribute("data-ws-tabpanel") !== gateway;
    });
    updatePayLabel();
    renderConfirm();
  }

  /* New-card brand selector (Credit Card tab) */
  function applyBrand() {
    ws.querySelectorAll("[data-ws-brand-input]").forEach(function (input) {
      var card = input.closest(".ws-brand-card");
      if (card) card.classList.toggle("is-selected", input.value === cardBrand);
    });
    ws.querySelectorAll("[data-ws-brand-logo]").forEach(function (img) {
      img.hidden = img.getAttribute("data-ws-brand-logo") !== cardBrand;
    });
    renderConfirm();
  }

  /* Saved-card selector (Paystack tab) */
  function applySaved() {
    ws.querySelectorAll("[data-ws-saved-input]").forEach(function (input) {
      var pill = input.closest(".ws-card-pill");
      if (pill) pill.classList.toggle("is-selected", input.value === savedBrand);
    });
    renderConfirm();
  }

  ws.querySelectorAll("[data-ws-gateway-input]").forEach(function (input) {
    input.addEventListener("change", function () { gateway = input.value; applyGateway(); });
  });
  ws.querySelectorAll("[data-ws-brand-input]").forEach(function (input) {
    input.addEventListener("change", function () { cardBrand = input.value; applyBrand(); });
  });
  ws.querySelectorAll("[data-ws-saved-input]").forEach(function (input) {
    input.addEventListener("change", function () { savedBrand = input.value; applySaved(); });
  });

  /* CVV visibility toggle */
  var cvvInput = ws.querySelector("[data-ws-cvv]");
  var cvvToggle = ws.querySelector("[data-ws-cvv-toggle]");
  if (cvvInput && cvvToggle) {
    cvvToggle.addEventListener("click", function () {
      var revealed = cvvInput.type === "password";
      cvvInput.type = revealed ? "text" : "password";
      cvvToggle.setAttribute("aria-pressed", revealed ? "true" : "false");
      cvvToggle.classList.toggle("text-slate-600", revealed);
      cvvToggle.classList.toggle("text-slate-400", !revealed);
    });
  }

  /* Promo: the server owns the rules (targeting, limits, dates), so ask it. */
  var promoApply = ws.querySelector("[data-ws-promo-apply]");
  if (promoApply) {
    promoApply.addEventListener("click", function () {
      var input = ws.querySelector("[data-ws-promo-input]");
      var msg = ws.querySelector("[data-ws-promo-msg]");
      var code = (input.value || "").trim().toUpperCase();

      function showPromo(text, ok) {
        if (!msg) return;
        msg.hidden = false;
        msg.className = "mt-2 text-xs font-semibold " + (ok ? "text-success" : "text-coral");
        msg.textContent = text;
      }

      if (!code) {
        promotion = "";
        promoDiscount = 0;
        showPromo("Enter a promo code to apply a discount.", false);
        renderSummary();
        return;
      }

      promoApply.disabled = true;
      window.jaHttp("POST", cfg.promoApi, { code: code })
        .then(function (result) {
          promoApply.disabled = false;
          if (result.valid) {
            promotion = result.code;
            promoDiscount = Number(result.discount || 0);
            showPromo(result.message || (result.code + " applied."), true);
          } else {
            promotion = "";
            promoDiscount = 0;
            showPromo(result.message || "That promo code is not valid.", false);
          }
          renderSummary();
        })
        .catch(function () {
          promoApply.disabled = false;
          showPromo("We could not check that code. Please try again.", false);
        });
    });
  }

  /* ID upload label */
  var upload = ws.querySelector("[data-ws-id-upload]");
  if (upload) {
    upload.addEventListener("change", function () {
      var name = ws.querySelector("[data-ws-upload-name]");
      if (name && upload.files && upload.files[0]) name.textContent = upload.files[0].name;
    });
  }

  /* Remove / clear (delegated, reload to re-render the server truth) */
  ws.addEventListener("click", function (event) {
    var remove = event.target.closest("[data-ws-remove]");
    if (remove) {
      window.jaHttp("DELETE", cfg.selectionApi + "/" + encodeURIComponent(remove.getAttribute("data-ws-remove")))
        .then(function () { window.location.reload(); });
      return;
    }
    if (event.target.closest("[data-ws-clear]")) {
      /* In-app confirmation modal (selection drawer) instead of window.confirm. */
      var askConfirm = window.JA_CONFIRM || function () { return Promise.resolve(true); };
      askConfirm("Clear every vehicle from your selection?", {
        title: "Clear your selection?",
        acceptLabel: "Remove All"
      }).then(function (confirmed) {
        if (!confirmed) return;
        window.jaHttp("DELETE", cfg.selectionApi).then(function () { window.location.reload(); });
      });
    }
  });

  /* Live gateway flow */
  var payBtn = ws.querySelector("[data-ws-pay]");

  function showError(message) {
    var box = ws.querySelector("[data-ws-pay-error]");
    if (!box) return;
    box.querySelector("span").textContent = message;
    box.classList.toggle("hidden", !message);
    box.classList.toggle("flex", !!message);
  }

  function finish(reference) {
    var url = new URL(cfg.doneUrl, window.location.href);
    url.searchParams.set("reference", reference);
    window.location.href = url.toString();
  }

  function openCard(result, button, label, original) {
    if (typeof PaystackPop === "undefined") {
      button.disabled = false;
      label.textContent = original;
      showError("The secure card window could not load. Check your connection and try again.");
      return;
    }
    new PaystackPop().resumeTransaction(result.access_code, {
      onSuccess: function () { finish(result.reference); },
      onCancel: function () {
        button.disabled = false; label.textContent = original;
        showError("Payment window closed. You have not been charged.");
      },
      onError: function () {
        button.disabled = false; label.textContent = original;
        showError("The payment could not be completed. Please try again.");
      }
    });
  }

  function startPayFlow() {
    if (!deliveryReady()) { go(2); return; }
    if (!cfg.live) { window.location.href = cfg.previewUrl; return; }

    showError("");
    payBtn.disabled = true;
    var label = ws.querySelector("[data-ws-pay-label]");
    var original = label.textContent;
    label.textContent = "Please wait…";

    /* Paystack and Credit Card both settle through Paystack's window; PayPal
       leaves the site. Card details are collected by the gateway, never here. */
    window.jaHttp("POST", cfg.paymentsApi, {
      purpose: "cart",
      method: gateway === "paypal" ? "paypal" : "paystack",
      delivery: delivery,
      delivery_state: field("state"),
      delivery_city: field("city"),
      delivery_address: field("address"),
      promo: promotion
    })
      .then(function (result) {
        if (!result.ok) {
          payBtn.disabled = false;
          label.textContent = original;
          showError(result.message || "We could not start this payment. Please try again.");
          return;
        }
        if (result.method === "paypal") {
          window.location.href = result.approve_url;
          return;
        }
        openCard(result, payBtn, label, original);
      })
      .catch(function () {
        payBtn.disabled = false;
        label.textContent = original;
        showError("We could not reach the server. Check your connection and try again.");
      });
  }

  if (payBtn) payBtn.addEventListener("click", startPayFlow);

  /* Step navigation */
  var back = ws.querySelector("[data-ws-back]");
  var next = ws.querySelector("[data-ws-next]");
  if (back) back.addEventListener("click", function () { go(step - 1); });
  if (next) next.addEventListener("click", function () {
    if (step === 1 && (subtotal <= 0 || Number(ws.getAttribute("data-unavailable") || 0) > 0)) {
      var first = ws.querySelector('[data-ws-panel="1"]');
      if (first) first.scrollIntoView({ behavior: "smooth", block: "center" });
      return;
    }
    if (step === 2 && !deliveryReady()) return;
    go(step + 1);
  });

  var backDelivery = ws.querySelector("[data-ws-back-delivery]");
  if (backDelivery) backDelivery.addEventListener("click", function () { go(2); });

  renderSteps();
  renderSummary();
  applyGateway();
  applyBrand();
  applySaved();
  renderConfirm();
})();
