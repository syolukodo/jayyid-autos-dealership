/* ==========================================================================
   Jayyid Autos - account-payments.js
   "Pay now" on the Payments & Instalments tab: opens the next instalment or
   the full balance with Paystack (in-page window) or PayPal (redirect). The
   server derives the amount from the customer's schedule; on success the
   page reloads with ?reference= and verifies it with the gateway.
   ========================================================================== */
(function () {
  "use strict";

  var panel = document.querySelector("[data-pay-panel]");
  if (!panel) return;

  var api = panel.getAttribute("data-payments-api");
  var doneUrl = panel.getAttribute("data-done");
  var vehicleId = panel.getAttribute("data-vehicle-id");
  var errorBox = panel.querySelector("[data-pay-error]");
  var buttons = panel.querySelectorAll("[data-pay-purpose]");

  function showError(message) {
    if (!errorBox) return;
    errorBox.textContent = message || "";
    errorBox.hidden = !message;
  }

  function setBusy(active, label) {
    buttons.forEach(function (button) {
      button.disabled = active;
      if (button === label.button) button.textContent = active ? "Please wait…" : label.text;
    });
  }

  function finish(reference) {
    var url = new URL(doneUrl, window.location.href);
    url.searchParams.set("reference", reference);
    window.location.href = url.toString();
  }

  function method() {
    var field = panel.querySelector("[data-pay-method]");
    return field && field.value === "paypal" ? "paypal" : "paystack";
  }

  function pay(button) {
    var label = { button: button, text: button.textContent };
    showError("");
    setBusy(true, label);

    window.jaHttp("POST", api, {
      purpose: button.getAttribute("data-pay-purpose"),
      vehicle_id: vehicleId,
      method: method()
    })
      .then(function (result) {
        if (!result.ok) {
          setBusy(false, label);
          showError(result.message || "We could not start this payment. Please try again.");
          return;
        }
        if (result.method === "paypal") {
          window.location.href = result.approve_url;
          return;
        }
        if (typeof PaystackPop === "undefined") {
          setBusy(false, label);
          showError("The secure card window could not load. Check your connection and try again.");
          return;
        }
        new PaystackPop().resumeTransaction(result.access_code, {
          onSuccess: function () { finish(result.reference); },
          onCancel: function () {
            setBusy(false, label);
            showError("Payment window closed. You have not been charged.");
          },
          onError: function () {
            setBusy(false, label);
            showError("The payment could not be completed. Please try again.");
          }
        });
      })
      .catch(function () {
        setBusy(false, label);
        showError("We could not reach the server. Check your connection and try again.");
      });
  }

  buttons.forEach(function (button) {
    button.addEventListener("click", function () { pay(button); });
  });

  /* Show the converted PayPal amounts only while PayPal is the chosen method. */
  var methodSelect = panel.querySelector("select[data-pay-method]");
  var paypalNote = panel.querySelector("[data-paypal-note]");
  if (methodSelect && paypalNote) {
    methodSelect.addEventListener("change", function () { paypalNote.hidden = methodSelect.value !== "paypal"; });
  }
})();
