/* ==========================================================================
   Jayyid Autos - checkout.js
   Single-vehicle deposit checkout (/account/checkout/{vehicle}): method
   selector, the preview-mode "Proceed to Paystack" simulation, and the live
   on-page flow - card in Paystack's secure window, transfer in our own panel.
   The server re-verifies the reference before showing any result.
   ========================================================================== */
(function () {
  "use strict";

  var SELECTED = ["border-navy-500", "bg-navy/[0.04]", "ring-2", "ring-navy-500", "shadow-sm"];
  var UNSELECTED = ["border-slate-200"];

  // Highlight whichever method is checked with a smooth transition.
  function refreshMethod() {
    document.querySelectorAll("[data-method]").forEach(function (option) {
      var input = option.querySelector('input[type="radio"]');
      var on = !!input && input.checked;
      SELECTED.forEach(function (cls) { option.classList.toggle(cls, on); });
      UNSELECTED.forEach(function (cls) { option.classList.toggle(cls, !on); });
      var check = option.querySelector("[data-check]");
      if (check) {
        check.classList.toggle("border-navy-500", on);
        check.classList.toggle("bg-navy-500", on);
        check.classList.toggle("text-white", on);
        check.classList.toggle("border-slate-300", !on);
        check.classList.toggle("bg-transparent", !on);
        check.classList.toggle("text-transparent", !on);
      }
    });
  }

  var group = document.querySelector("[data-method-group]");
  if (group) {
    group.addEventListener("change", refreshMethod);
    // Keep the hover shadow alive when a card is hovered but not selected.
    group.querySelectorAll("[data-method]").forEach(function (option) {
      option.addEventListener("mouseenter", function () {
        if (!option.querySelector('input[type="radio"]').checked) option.classList.add("shadow-sm");
      });
      option.addEventListener("mouseleave", function () {
        if (!option.querySelector('input[type="radio"]').checked) option.classList.remove("shadow-sm");
      });
    });
    refreshMethod();
  }

  function selectedMethod() {
    var selected = group ? group.querySelector('input[name="method"]:checked') : null;
    return selected ? selected.value : "paystack";
  }

  // Preview mode (no gateway key): simulate a completed gateway session.
  var proceed = document.querySelector("[data-proceed]");
  if (proceed) {
    proceed.addEventListener("click", function () {
      var url = new URL(window.location.href);
      url.searchParams.set("method", selectedMethod());
      url.searchParams.set("mock", "1");
      window.location.href = url.toString();
    });
  }

  var checkout = document.querySelector("[data-checkout]");
  if (!checkout) return;

  var api = checkout.getAttribute("data-api");
  var doneUrl = checkout.getAttribute("data-done");
  var payButton = checkout.querySelector("[data-pay]");
  var payLabel = checkout.querySelector("[data-pay-label]");
  var payLabelText = payLabel.textContent;
  var errorBox = checkout.querySelector("[data-pay-error]");
  var heading = checkout.querySelector("[data-pay-heading]");
  var chooser = checkout.querySelector("[data-pay-chooser]");
  var panel = checkout.querySelector("[data-transfer]");
  var statusLine = checkout.querySelector("[data-transfer-status]");
  var timer = checkout.querySelector("[data-transfer-timer]");
  var transferRef = "";
  var pollId = null;
  var clockId = null;

  function showError(message) {
    errorBox.querySelector("span").textContent = message;
    errorBox.classList.toggle("hidden", !message);
  }

  function setBusy(on) {
    payButton.disabled = on;
    payLabel.textContent = on ? "Please wait…" : payLabelText;
  }

  function finish(reference) {
    var url = new URL(doneUrl, window.location.href);
    url.searchParams.set("reference", reference);
    window.location.href = url.toString();
  }

  function closeTransfer() {
    clearInterval(pollId);
    clearInterval(clockId);
    transferRef = "";
    panel.classList.add("hidden");
    chooser.classList.remove("hidden");
    heading.textContent = "Choose how to pay";
    setBusy(false);
  }

  function checkTransfer(manual) {
    var reference = transferRef;
    if (!reference) return;
    window.jaHttp("GET", api + "/" + encodeURIComponent(reference))
      .then(function (result) {
        if (reference !== transferRef) return;
        if (result.status === "paid" || result.status === "mismatch") {
          finish(reference);
        } else if (result.status === "failed") {
          closeTransfer();
          showError("That transfer was not completed. Please start again.");
        } else if (manual) {
          statusLine.textContent = "Not received yet — transfers can take a minute. We are still checking.";
        }
      })
      .catch(function () {
        if (manual) statusLine.textContent = "We could not check just now. We will keep trying.";
      });
  }

  function openTransfer(result) {
    var expires = new Date(result.transfer.expires_at).getTime();
    transferRef = result.reference;
    checkout.querySelector("[data-transfer-bank]").textContent = result.transfer.bank;
    checkout.querySelector("[data-transfer-number]").textContent = result.transfer.account_number;
    checkout.querySelector("[data-transfer-name]").textContent = result.transfer.account_name;
    statusLine.textContent = "Waiting for your transfer…";
    heading.textContent = "Complete your bank transfer";
    chooser.classList.add("hidden");
    panel.classList.remove("hidden");

    function tick() {
      if (isNaN(expires)) return;
      var left = Math.max(0, Math.floor((expires - Date.now()) / 1000));
      timer.textContent = String(Math.floor(left / 60)).padStart(2, "0") + ":" + String(left % 60).padStart(2, "0");
      if (left === 0) {
        closeTransfer();
        showError("That transfer account has expired. If you already sent the money it will still be confirmed — otherwise please start again.");
      }
    }
    tick();
    clockId = setInterval(tick, 1000);
    pollId = setInterval(function () { checkTransfer(false); }, 5000);
  }

  function openCard(result) {
    if (typeof PaystackPop === "undefined") {
      setBusy(false);
      showError("The secure card window could not load. Check your connection and try again.");
      return;
    }
    new PaystackPop().resumeTransaction(result.access_code, {
      onSuccess: function () { finish(result.reference); },
      onCancel: function () {
        setBusy(false);
        showError("Payment window closed. You have not been charged.");
      },
      onError: function () {
        setBusy(false);
        showError("The payment could not be completed. Please try again.");
      }
    });
  }

  payButton.addEventListener("click", function () {
    showError("");
    setBusy(true);

    window.jaHttp("POST", api, {
      vehicle_id: checkout.getAttribute("data-vehicle"),
      method: selectedMethod(),
      purpose: "deposit"
    })
      .then(function (result) {
        if (!result.ok) {
          setBusy(false);
          showError(result.message || "We could not start this payment. Please try again.");
        } else if (result.method === "transfer") {
          openTransfer(result);
        } else {
          openCard(result);
        }
      })
      .catch(function () {
        setBusy(false);
        showError("We could not reach the server. Check your connection and try again.");
      });
  });

  checkout.querySelector("[data-transfer-check]").addEventListener("click", function () { checkTransfer(true); });
  checkout.querySelector("[data-transfer-cancel]").addEventListener("click", function () {
    closeTransfer();
    showError("");
  });
  checkout.querySelector("[data-transfer-copy]").addEventListener("click", function (event) {
    var button = event.currentTarget;
    var number = checkout.querySelector("[data-transfer-number]").textContent;
    if (!navigator.clipboard) return;
    navigator.clipboard.writeText(number).then(function () {
      button.textContent = "Copied";
      setTimeout(function () { button.textContent = "Copy"; }, 2000);
    });
  });
})();
