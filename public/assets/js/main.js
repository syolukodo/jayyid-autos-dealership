/* ==========================================================================
   Jayyid Autos - main.js
   Mobile navigation, inquiry drawer, gallery, form validation, image fallbacks
   ========================================================================== */
(function () {
  "use strict";

  /* ----------------------------------------------------------------------
     Mobile navigation
     ---------------------------------------------------------------------- */
  function initNav() {
    var toggle = document.querySelector("[data-nav-toggle]");
    var nav = document.getElementById("primary-nav");
    if (!toggle || !nav) return;

    toggle.addEventListener("click", function () {
      var open = nav.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
    });

    document.addEventListener("click", function (event) {
      if (!nav.classList.contains("is-open")) return;
      if (nav.contains(event.target) || toggle.contains(event.target)) return;
      nav.classList.remove("is-open");
      toggle.setAttribute("aria-expanded", "false");
    });

    window.addEventListener("resize", function () {
      if (window.innerWidth > 1023) {
        nav.classList.remove("is-open");
        toggle.setAttribute("aria-expanded", "false");
      }
    });
  }

  /* ----------------------------------------------------------------------
     Dashboard sidebar (off-canvas below 960px)
     ---------------------------------------------------------------------- */
  function initDashboardSidebar() {
    var sidebar = document.querySelector("[data-sidebar]");
    var toggle = document.querySelector("[data-sidebar-toggle]");
    var backdrop = document.querySelector(".admin-backdrop");
    if (!sidebar || !toggle) return;

    function setOpen(open) {
      sidebar.classList.toggle("is-open", open);
      if (backdrop) backdrop.classList.toggle("is-open", open);
      document.body.classList.toggle("is-locked", open);
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      if (open) {
        var first = sidebar.querySelector(".admin-nav a");
        if (first) setTimeout(function () { first.focus(); }, 120);
      } else if (sidebar.contains(document.activeElement)) {
        toggle.focus();
      }
    }

    toggle.addEventListener("click", function () {
      setOpen(!sidebar.classList.contains("is-open"));
    });

    document.addEventListener("click", function (event) {
      if (event.target.closest("[data-sidebar-close]")) setOpen(false);
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && sidebar.classList.contains("is-open")) setOpen(false);
    });

    window.addEventListener("resize", function () {
      if (window.innerWidth > 960 && sidebar.classList.contains("is-open")) setOpen(false);
    });
  }

  /* ----------------------------------------------------------------------
     Inquiry drawer / modal
     ---------------------------------------------------------------------- */
  function initDrawer() {
    var drawer = document.getElementById("inquiry-drawer");
    var overlay = document.querySelector("[data-overlay]");
    if (!drawer || !overlay) return;

    var lastTrigger = null;

    function openDrawer(trigger) {
      lastTrigger = trigger || null;
      drawer.classList.add("is-open");
      overlay.classList.add("is-open");
      drawer.setAttribute("aria-hidden", "false");
      document.body.classList.add("is-locked");
      var first = drawer.querySelector("input, select, textarea, button");
      if (first) setTimeout(function () { first.focus(); }, 120);
    }

    function closeDrawer() {
      drawer.classList.remove("is-open");
      overlay.classList.remove("is-open");
      drawer.setAttribute("aria-hidden", "true");
      document.body.classList.remove("is-locked");
      if (lastTrigger) lastTrigger.focus();
    }

    document.addEventListener("click", function (event) {
      var opener = event.target.closest("[data-drawer-open]");
      if (opener) {
        event.preventDefault();
        openDrawer(opener);
        return;
      }
      if (event.target.closest("[data-drawer-close]") || event.target === overlay) {
        closeDrawer();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && drawer.classList.contains("is-open")) {
        closeDrawer();
      }
    });

    if (drawer.getAttribute("data-auto-open") === "true") {
      openDrawer(null);
    }
  }

  /* ----------------------------------------------------------------------
     Vehicle gallery (thumbnail switching)
     ---------------------------------------------------------------------- */
  function initGallery() {
    var main = document.querySelector("[data-gallery-main]");
    var thumbs = document.querySelectorAll("[data-gallery-thumb]");
    if (!main || !thumbs.length) return;

    thumbs.forEach(function (thumb) {
      thumb.addEventListener("click", function () {
        var src = thumb.getAttribute("data-src");
        if (!src) return;
        main.setAttribute("src", src);
        thumbs.forEach(function (item) { item.classList.remove("is-active"); });
        thumb.classList.add("is-active");
      });
    });
  }

  /* ----------------------------------------------------------------------
     Client-side form validation (progressive enhancement)
     ---------------------------------------------------------------------- */
  function setFieldError(field, message) {
    field.classList.toggle("is-invalid", Boolean(message));
    var wrap = field.closest(".field");
    if (!wrap) return;
    var error = wrap.querySelector(".field__error");
    if (error) error.textContent = message || "";
  }

  function validateForm(form) {
    var valid = true;
    var fields = form.querySelectorAll("[data-validate]");
    fields.forEach(function (field) {
      var value = (field.value || "").trim();
      var label = field.getAttribute("data-label") || "This field";
      var type = field.getAttribute("data-validate");
      var message = "";

      if (field.hasAttribute("required") && !value) {
        message = label + " is required.";
      } else if (value && type === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) {
        message = "Enter a valid email address.";
      } else if (value && type === "phone" && !/^[+\d][\d\s()-]{7,16}$/.test(value)) {
        message = "Enter a valid phone number.";
      } else if (value && type === "password" && value.length < 8) {
        message = label + " must be at least 8 characters.";
      } else if (value && type === "password-confirm") {
        var target = form.querySelector(field.getAttribute("data-match") || "");
        if (target && target.value !== value) {
          message = "Passwords do not match.";
        }
      } else if (value && type === "text" && value.length < 2) {
        message = label + " looks too short.";
      }

      setFieldError(field, message);
      if (message) valid = false;
    });
    return valid;
  }

  function initForms() {
    document.querySelectorAll("form[data-validate-form]").forEach(function (form) {
      form.setAttribute("novalidate", "novalidate");
      form.addEventListener("submit", function (event) {
        if (!validateForm(form)) {
          event.preventDefault();
          var firstInvalid = form.querySelector(".is-invalid");
          if (firstInvalid) firstInvalid.focus();
        }
      });
      form.querySelectorAll("[data-validate]").forEach(function (field) {
        field.addEventListener("blur", function () {
          validateForm(form);
        });
      });
    });
  }

  /* ----------------------------------------------------------------------
     Admin tables - client-side pagination + lead status filter
     ---------------------------------------------------------------------- */
  function initAdminTables() {
    var tables = document.querySelectorAll("table[data-page-table]");
    if (!tables.length) return;

    tables.forEach(function (table) {
      var tbody = table.tBodies[0];
      if (!tbody) return;

      var size = parseInt(table.getAttribute("data-page-size"), 10) || 8;
      var rows = Array.prototype.slice.call(tbody.rows);
      var page = 1;

      var pager = document.createElement("div");
      pager.className = "table-pager";
      var wrap = table.closest(".table-wrap");
      if (wrap && wrap.parentNode) {
        wrap.parentNode.insertBefore(pager, wrap.nextSibling);
      } else if (table.parentNode) {
        table.parentNode.appendChild(pager);
      }

      function matches(row) {
        return row.getAttribute("data-match") !== "0";
      }

      function visibleCount() {
        return rows.filter(matches).length;
      }

      function render() {
        var total = visibleCount();
        var pages = Math.max(1, Math.ceil(total / size));
        if (page > pages) page = pages;

        rows.forEach(function (row) { row.style.display = "none"; });
        rows.filter(matches)
          .slice((page - 1) * size, (page - 1) * size + size)
          .forEach(function (row) { row.style.display = ""; });

        if (pages <= 1) {
          pager.style.display = "none";
          pager.innerHTML = "";
          return;
        }

        pager.style.display = "flex";
        pager.innerHTML =
          '<button type="button" class="pagination__link' + (page === 1 ? " is-disabled" : "") + '" data-pager-prev>Previous</button>' +
          '<span class="table-pager__status">Page ' + page + " of " + pages + "</span>" +
          '<button type="button" class="pagination__link' + (page === pages ? " is-disabled" : "") + '" data-pager-next>Next</button>';
      }

      pager.addEventListener("click", function (event) {
        if (event.target.closest("[data-pager-prev]") && page > 1) {
          page -= 1;
          render();
          return;
        }
        if (event.target.closest("[data-pager-next]")) {
          var pages = Math.max(1, Math.ceil(visibleCount() / size));
          if (page < pages) {
            page += 1;
            render();
          }
        }
      });

      table._jaResetPage = function () {
        page = 1;
        render();
      };

      render();
    });

    document.querySelectorAll("[data-lead-filter]").forEach(function (chip) {
      chip.addEventListener("click", function () {
        var value = chip.getAttribute("data-lead-filter");
        var target = document.getElementById(chip.getAttribute("data-target") || "");
        if (!target || !target.tBodies[0]) return;

        document.querySelectorAll("[data-lead-filter]").forEach(function (item) {
          item.classList.toggle("is-active", item === chip);
        });

        Array.prototype.forEach.call(target.tBodies[0].rows, function (row) {
          var status = row.getAttribute("data-status") || "";
          row.setAttribute("data-match", value === "All" || status === value ? "1" : "0");
        });

        if (target._jaResetPage) target._jaResetPage();
      });
    });
  }

  /* ----------------------------------------------------------------------
     Admin list filters - chip buttons and select dropdowns
     Controls: data-filter-key, data-filter-value (buttons) or native
     value (selects), data-filter-target (container id).
     Items:    data-filter-item + data-<key> attribute on the container.
     ---------------------------------------------------------------------- */
  function initListFilters() {
    var controls = document.querySelectorAll("[data-filter-key]");
    if (!controls.length) return;

    controls.forEach(function (control) {
      var eventName = control.tagName === "SELECT" ? "change" : "click";

      control.addEventListener(eventName, function () {
        var key = control.getAttribute("data-filter-key");
        var target = document.getElementById(control.getAttribute("data-filter-target") || "");
        if (!target) return;

        var value = control.tagName === "SELECT"
          ? control.value
          : control.getAttribute("data-filter-value") || "All";

        document.querySelectorAll('[data-filter-key="' + key + '"]').forEach(function (item) {
          if (item.tagName !== "SELECT") {
            item.classList.toggle("is-active", item === control);
          }
        });

        var items = target.querySelectorAll("[data-filter-item]");
        var visible = 0;
        Array.prototype.forEach.call(items, function (item) {
          var match = value === "All" || value === "" ||
            item.getAttribute("data-" + key) === value;
          item.style.display = match ? "" : "none";
          if (match) visible += 1;
        });

        var card = (target.closest && target.closest(".dash-card")) ||
          control.closest(".dash-card") || control.parentNode;
        var empty = card.querySelector("[data-filter-empty]");
        if (empty) empty.hidden = visible > 0;

        var counter = card.querySelector("[data-filter-count]");
        if (counter) counter.textContent = visible + " of " + items.length + " shown";
      });
    });
  }

  /* ----------------------------------------------------------------------
     Admin tabs - switch between panels within a page
     ---------------------------------------------------------------------- */
  function initAdminTabs() {
    var tabs = document.querySelectorAll("[data-tab-target]");
    if (!tabs.length) return;

    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        var name = tab.getAttribute("data-tab-target");
        var scope = tab.closest(".panel") || tab.closest(".dash-card") || document;

        scope.querySelectorAll("[data-tab-target]").forEach(function (item) {
          var on = item === tab;
          item.classList.toggle("is-active", on);
          item.setAttribute("aria-selected", on ? "true" : "false");
        });

        scope.querySelectorAll("[data-tab-panel]").forEach(function (panel) {
          panel.classList.toggle("is-active", panel.getAttribute("data-tab-panel") === name);
        });
      });
    });
  }

  /* ----------------------------------------------------------------------
     Staff invite link - copy to clipboard
     ---------------------------------------------------------------------- */
  function initInviteCopy() {
    var button = document.querySelector("[data-copy-invite]");
    var input = document.querySelector("[data-invite-link]");
    if (!button || !input) return;

    button.addEventListener("click", function () {
      function done() {
        button.textContent = "Copied";
        setTimeout(function () { button.textContent = "Copy link"; }, 2000);
      }
      input.select();
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value).then(done, function () {
          document.execCommand("copy");
          done();
        });
      } else {
        document.execCommand("copy");
        done();
      }
    });
  }

  /* ----------------------------------------------------------------------
     Modal dialogs - open/close with overlay, Escape and focus return
     ---------------------------------------------------------------------- */
  function initAdminModals() {
    var modals = document.querySelectorAll("[data-modal]");
    if (!modals.length) return;

    var lastTrigger = null;

    function open(modal) {
      modal.classList.add("is-open");
      modal.setAttribute("aria-hidden", "false");
      document.body.classList.add("modal-open");
      var focusable = modal.querySelector("input, select, textarea, button, a[href]");
      if (focusable) focusable.focus();
    }

    function close(modal) {
      modal.classList.remove("is-open");
      modal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("modal-open");
      if (lastTrigger) lastTrigger.focus();
    }

    function updateStatusLabel(modal) {
      var toggle = modal.querySelector("#ef-status");
      var label = modal.querySelector("[data-status-label]");
      if (toggle && label) label.textContent = toggle.checked ? "Active" : "Suspended";
    }

    function fillEditModal(trigger, modal) {
      function value(key) {
        return trigger.getAttribute("data-edit-" + key) || "";
      }

      var fields = { first: "#ef-first", last: "#ef-last", email: "#ef-email", phone: "#ef-phone" };
      Object.keys(fields).forEach(function (key) {
        var input = modal.querySelector(fields[key]);
        if (input) input.value = value(key);
      });

      var role = modal.querySelector("#ef-role");
      if (role) role.value = value("role");

      var toggle = modal.querySelector("#ef-status");
      if (toggle) {
        toggle.checked = value("status") !== "Suspended";
        updateStatusLabel(modal);
      }

      var idInput = modal.querySelector("[data-edit-id-input]");
      if (idInput) idInput.value = trigger.getAttribute("data-edit-id") || "";

      /* Each account has its own update URL (PUT /admin/users/{id}). */
      var form = modal.querySelector("[data-edit-form]");
      if (form && trigger.getAttribute("data-edit-action")) form.action = trigger.getAttribute("data-edit-action");

      var nameOut = modal.querySelector("[data-edit-name]");
      if (nameOut) nameOut.textContent = (value("first") + " " + value("last")).trim();

      ["#ef-password", "#ef-password-confirm"].forEach(function (selector) {
        var input = modal.querySelector(selector);
        if (input) input.value = "";
      });

      modal.querySelectorAll(".field__control.is-invalid").forEach(function (input) {
        input.classList.remove("is-invalid");
      });
      modal.querySelectorAll(".field__error").forEach(function (error) {
        error.textContent = "";
      });
    }

    document.querySelectorAll("[data-modal-open]").forEach(function (trigger) {
      trigger.addEventListener("click", function () {
        var modal = document.querySelector('[data-modal="' + trigger.getAttribute("data-modal-open") + '"]');
        if (modal) {
          if (trigger.hasAttribute("data-edit-account")) fillEditModal(trigger, modal);
          lastTrigger = trigger;
          open(modal);
        }
      });
    });

    var statusToggle = document.querySelector("#ef-status");
    if (statusToggle) {
      statusToggle.addEventListener("change", function () {
        updateStatusLabel(statusToggle.closest("[data-modal]"));
      });
    }

    modals.forEach(function (modal) {
      modal.addEventListener("click", function (event) {
        if (event.target.closest("[data-modal-close]")) close(modal);
      });
    });

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape") return;
      modals.forEach(function (modal) {
        if (modal.classList.contains("is-open")) close(modal);
      });
    });
  }

  /* ----------------------------------------------------------------------
     Destructive action caution modal (admin)
     <form data-confirm="Title" data-confirm-text="Body" data-confirm-accept="Button">
     is held back until the staff member confirms in [data-admin-confirm].
     ---------------------------------------------------------------------- */
  function initAdminConfirm() {
    var modal = document.querySelector("[data-admin-confirm]");
    if (!modal) return;

    var title = modal.querySelector("[data-admin-confirm-title]");
    var text = modal.querySelector("[data-admin-confirm-text]");
    var accept = modal.querySelector("[data-admin-confirm-accept]");
    var cancel = modal.querySelector(".confirm-modal__actions [data-admin-confirm-cancel]");
    var pendingForm = null;
    var lastFocus = null;

    function open(form) {
      pendingForm = form;
      lastFocus = document.activeElement;
      title.textContent = form.getAttribute("data-confirm") || "Are you sure?";
      text.textContent = form.getAttribute("data-confirm-text") || "This action cannot be undone.";
      accept.textContent = form.getAttribute("data-confirm-accept") || "Delete";
      accept.disabled = false;
      modal.classList.add("is-open");
      modal.setAttribute("aria-hidden", "false");
      document.body.classList.add("modal-open");
      /* Safe choice gets focus so a stray Enter does not confirm. */
      cancel.focus();
    }

    function close() {
      pendingForm = null;
      modal.classList.remove("is-open");
      modal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("modal-open");
      if (lastFocus) lastFocus.focus();
    }

    document.addEventListener("submit", function (event) {
      var form = event.target;
      if (!form.matches || !form.matches("form[data-confirm]")) return;
      event.preventDefault();
      open(form);
    });

    accept.addEventListener("click", function () {
      if (!pendingForm) return;
      accept.disabled = true;
      /* form.submit() skips the submit event, so the modal does not reopen. */
      pendingForm.submit();
    });

    modal.addEventListener("click", function (event) {
      if (event.target.closest("[data-admin-confirm-cancel]")) close();
    });

    document.addEventListener("keydown", function (event) {
      if (!modal.classList.contains("is-open")) return;
      if (event.key === "Escape") {
        close();
      } else if (event.key === "Tab") {
        var focusable = modal.querySelectorAll("button:not([disabled])");
        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    });
  }

  /* ----------------------------------------------------------------------
     Image fallback - hide broken images, reveal neutral placeholder
     ---------------------------------------------------------------------- */
  function markMissing(img) {
    var frame = img.closest(".media-frame, .car-card__media, .gallery__main, .gallery__thumb, .dash-list__thumb");
    if (frame) frame.classList.add("is-missing");
  }

  function initImageFallbacks() {
    document.querySelectorAll("img").forEach(function (img) {
      img.addEventListener("error", function () { markMissing(img); });
      if (img.complete && img.naturalWidth === 0) markMissing(img);
    });
  }

  function initToasts() {
    document.querySelectorAll("[data-toast]").forEach(function (toast) {
      window.setTimeout(function () {
        toast.classList.add("is-dismissed");
      }, 5000);
    });

    /* Customers land on the homepage after signing out (?signed_out=1). */
    if (/[?&]signed_out=1(?:&|$)/.test(window.location.search)) {
      showToast("You've been signed out.");
      if (window.history.replaceState) {
        var url = window.location.pathname + window.location.search.replace(/([?&])signed_out=1(&|$)/, function (m, lead, tail) {
          return tail ? lead : "";
        }) + window.location.hash;
        window.history.replaceState(null, "", url);
      }
    }
  }

  /* ----------------------------------------------------------------------
     Saved vehicles - heart toggles stored per account (/api/saved-vehicles)
     <meta name="ja-saved"> carries the signed-in customer's saved ids, the
     endpoint, CSRF token and sign-in URL; visitors are sent to sign in.
     ---------------------------------------------------------------------- */
  var saveMeta = document.querySelector('meta[name="ja-saved"]');
  var savedIds = saveMeta && saveMeta.content ? saveMeta.content.split(",") : [];

  function readSaved() {
    return savedIds.slice();
  }

  function showToast(message) {
    var stack = document.querySelector(".toast-stack");
    if (!stack) {
      stack = document.createElement("div");
      stack.className = "toast-stack";
      document.body.appendChild(stack);
    }

    var toast = document.createElement("div");
    toast.className = "toast";
    toast.setAttribute("role", "status");
    toast.innerHTML =
      '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span></span>';
    toast.querySelector("span").textContent = message;
    stack.appendChild(toast);

    window.setTimeout(function () { toast.classList.add("is-dismissed"); }, 2600);
    window.setTimeout(function () {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 3200);
  }

  function syncSaveButtons() {
    var saved = readSaved();
    document.querySelectorAll("[data-save-toggle]").forEach(function (button) {
      var active = saved.indexOf(String(button.getAttribute("data-vehicle-id") || "")) !== -1;
      button.classList.toggle("is-saved", active);
      button.setAttribute("aria-pressed", active ? "true" : "false");
    });
  }

  /* A guest's click is remembered across the sign-in round trip and
     completed when they land back on the page signed in. */
  var PENDING_SAVE_KEY = "ja-pending-save";

  function readPendingSave() {
    try { return window.sessionStorage.getItem(PENDING_SAVE_KEY) || ""; } catch (e) { return ""; }
  }

  function writePendingSave(id) {
    try {
      if (id) window.sessionStorage.setItem(PENDING_SAVE_KEY, id);
      else window.sessionStorage.removeItem(PENDING_SAVE_KEY);
    } catch (e) { /* storage unavailable: the visitor just clicks again */ }
  }

  function initSaveButtons() {
    syncSaveButtons();
    window.jaSyncSaveButtons = syncSaveButtons;

    document.addEventListener("click", function (event) {
      var button = event.target.closest("[data-save-toggle]");
      if (!button) return;
      event.preventDefault();

      var id = String(button.getAttribute("data-vehicle-id") || "");
      if (!id || !saveMeta || button.disabled) return;

      var auth = saveMeta.getAttribute("data-auth");

      /* Staff accounts have no saved list; sending them to sign-in would
         bounce them into the back office. */
      if (auth === "staff") {
        showToast("Saved cars belong to customer accounts. Sign out of the staff account to save vehicles.");
        return;
      }

      if (auth !== "1") {
        writePendingSave(id);
        var here = window.location.pathname + window.location.search;
        window.location.href = saveMeta.getAttribute("data-login") + "?redirect=" + encodeURIComponent(here);
        return;
      }

      toggleSaved(button, id, savedIds.indexOf(id) === -1);
    });

    /* Back from signing in: finish the save the visitor asked for. */
    var pending = readPendingSave();
    if (pending && saveMeta && saveMeta.getAttribute("data-auth") === "1") {
      writePendingSave("");
      if (savedIds.indexOf(pending) === -1) {
        toggleSaved(document.querySelector('[data-save-toggle][data-vehicle-id="' + pending + '"]'), pending, true);
      }
    }
  }

  /* PUT /api/saved-vehicles/{id} saves, DELETE removes. */
  function toggleSaved(button, id, save) {
    if (button) button.disabled = true;
    window.jaHttp(save ? "PUT" : "DELETE", saveMeta.getAttribute("data-api") + "/" + encodeURIComponent(id))
        .then(function (data) {
          return { status: data.httpStatus, data: data };
        })
        .then(function (result) {
          if (!result.data.ok) {
            showToast(result.data.message || "We could not update your saved list.");
            return;
          }
          if (result.data.saved) {
            if (savedIds.indexOf(id) === -1) savedIds.push(id);
            showToast("Vehicle added to your saved dashboard");
          } else {
            savedIds = savedIds.filter(function (item) { return item !== id; });
            showToast("Vehicle removed from saved list");
          }
          syncSaveButtons();
        })
        .catch(function () {
          showToast("We could not update your saved list. Please try again.");
        })
        .then(function () {
          if (button) button.disabled = false;
        });
  }

  /* ----------------------------------------------------------------------
     Profile settings - live avatar preview before upload
     ---------------------------------------------------------------------- */
  function initAvatarPreview() {
    document.querySelectorAll("[data-avatar-input]").forEach(function (input) {
      input.addEventListener("change", function () {
        var file = input.files && input.files[0];
        if (!file) return;

        if (!/^image\/(png|jpeg)$/.test(file.type) || file.size > 300 * 1024) {
          window.alert("Please choose a JPG or PNG image smaller than 300KB.");
          input.value = "";
          return;
        }

        var scope = input.closest(".settings-hero");
        var preview = scope && scope.querySelector(".settings-hero__avatar");
        if (!preview) return;

        /* Only one file input may carry the photo, or the server takes the wrong one. */
        scope.querySelectorAll("[data-avatar-input]").forEach(function (other) {
          if (other !== input) other.value = "";
        });
        var flag = scope.querySelector("[data-avatar-remove-flag]");
        if (flag) flag.value = "0";

        var reader = new FileReader();
        reader.onload = function () {
          var img = document.createElement("img");
          img.setAttribute("data-avatar-preview", "");
          img.alt = "";
          img.src = String(reader.result);
          setAvatarFace(preview, img);
          saveAvatarOnly(input.form);
        };
        reader.readAsDataURL(file);
      });
    });

    document.querySelectorAll("[data-avatar-remove]").forEach(function (button) {
      button.addEventListener("click", function () {
        var scope = button.closest(".settings-hero");
        if (!scope) return;
        scope.querySelectorAll("[data-avatar-input]").forEach(function (input) { input.value = ""; });
        var flag = scope.querySelector("[data-avatar-remove-flag]");
        if (flag) flag.value = "1";
        var preview = scope.querySelector(".settings-hero__avatar");
        if (preview) {
          var span = document.createElement("span");
          span.className = "settings-hero__initial";
          span.textContent = preview.getAttribute("data-avatar-initial") || "";
          setAvatarFace(preview, span);
        }
        saveAvatarOnly(button.form);
      });
    });
  }

  /* Swap the photo/initial but keep the camera badge (and its file input) in place. */
  function setAvatarFace(preview, face) {
    preview.querySelectorAll("[data-avatar-preview], .settings-hero__initial").forEach(function (node) {
      node.remove();
    });
    preview.insertBefore(face, preview.firstChild);
  }

  /* Submit just the photo change to the form's data-avatar-endpoint (a plain
     POST), skipping the rest of the form's validation. */
  function saveAvatarOnly(form) {
    if (!form) return;
    var endpoint = form.getAttribute("data-avatar-endpoint");
    if (endpoint) {
      form.action = endpoint;
      var spoof = form.querySelector('input[name="_method"]');
      if (spoof) spoof.parentNode.removeChild(spoof);
    }
    form.submit();
  }

  /* ----------------------------------------------------------------------
     List pagination - dash lists with a count label + page pills
     List:   [data-page-list] + [data-page-size] + [data-page-filter-by]
     Rows:   li[data-pager-item] (+ data-<filterBy> attribute)
     Filter: select[data-page-filter="<filterBy>"]
     ---------------------------------------------------------------------- */
  function initPageLists() {
    document.querySelectorAll("[data-page-list]").forEach(function (list) {
      var rows = Array.prototype.slice.call(list.querySelectorAll("[data-pager-item]"));
      if (!rows.length) return;

      var size = parseInt(list.getAttribute("data-page-size"), 10) || 6;
      var filterKey = list.getAttribute("data-page-filter-by") || "";
      var select = filterKey !== "" && document.querySelector('[data-page-filter="' + filterKey + '"]');
      var noun = list.getAttribute("data-page-noun") || "items";
      var page = 1;

      var card = list.closest(".dash-card");
      var empty = card && card.querySelector("[data-filter-empty]");

      var pager = document.createElement("div");
      pager.className = "table-pager table-pager--list";
      pager.setAttribute("aria-label", "Pagination");
      list.parentNode.insertBefore(pager, list.nextSibling);

      function filtered() {
        var value = select ? select.value : "All";
        return rows.filter(function (row) {
          if (value === "All" || value === "") return true;
          return row.getAttribute("data-" + filterKey) === value;
        });
      }

      function render() {
        var visible = filtered();
        var total = visible.length;
        var pages = Math.max(1, Math.ceil(total / size));
        if (page > pages) page = pages;

        rows.forEach(function (row) { row.style.display = "none"; });
        visible.slice((page - 1) * size, page * size).forEach(function (row) {
          row.style.display = "";
        });

        if (empty) empty.hidden = total !== 0;

        var start = total === 0 ? 0 : (page - 1) * size + 1;
        var end = total === 0 ? 0 : Math.min(page * size, total);
        var status = "Showing " + start + " to " + end + " of " + total + " " + noun;

        pager.style.display = total === 0 ? "none" : "flex";

        var pills = "";
        for (var i = 1; i <= pages; i += 1) {
          pills += '<button type="button" class="pagination__link' + (i === page ? " is-active" : "") + '" data-pager-page="' + i + '">' + i + "</button>";
        }

        pager.innerHTML =
          '<span class="table-pager__status">' + status + "</span>" +
          '<div class="table-pager__nav">' +
          '<button type="button" class="pagination__link' + (page === 1 ? " is-disabled" : "") + '" data-pager-prev>Previous</button>' +
          pills +
          '<button type="button" class="pagination__link' + (page === pages ? " is-disabled" : "") + '" data-pager-next>Next</button>' +
          "</div>";
      }

      pager.addEventListener("click", function (event) {
        var btn = event.target.closest("button");
        if (!btn) return;
        var pages = Math.max(1, Math.ceil(filtered().length / size));
        var target = null;
        if (btn.hasAttribute("data-pager-prev")) target = page - 1;
        else if (btn.hasAttribute("data-pager-next")) target = page + 1;
        else if (btn.hasAttribute("data-pager-page")) target = parseInt(btn.getAttribute("data-pager-page"), 10);
        if (target === null) return;
        if (target < 1) target = 1;
        if (target > pages) target = pages;
        if (target !== page) {
          page = target;
          render();
        }
      });

      if (select) {
        select.addEventListener("change", function () {
          page = 1;
          render();
        });
      }

      render();
    });
  }

  /* ----------------------------------------------------------------------
     File dropzone - thumbnail preview before upload
     Container: [data-dropzone] + input[data-dropzone-input] inside
     ---------------------------------------------------------------------- */
  function initDropzones() {
    document.querySelectorAll("[data-dropzone]").forEach(function (drop) {
      var input = drop.querySelector("[data-dropzone-input]");
      if (!input) return;
      var inner = drop.querySelector("[data-dropzone-inner]");
      var preview = drop.querySelector("[data-dropzone-preview]");
      var remove = drop.querySelector("[data-dropzone-remove]");

      function previewFile(file) {
        if (!file) return;
        if (!/^image\/(png|jpeg|webp)$/.test(file.type)) {
          window.alert("Please choose a PNG, JPG or WEBP image.");
          input.value = "";
          return;
        }
        if (file.size > 5 * 1024 * 1024) {
          window.alert("Image size must be 5MB or smaller.");
          input.value = "";
          return;
        }
        var reader = new FileReader();
        reader.onload = function () {
          preview.src = String(reader.result);
          preview.hidden = false;
          if (remove) remove.hidden = false;
          if (inner) inner.hidden = true;
        };
        reader.readAsDataURL(file);
      }

      drop.addEventListener("click", function (event) {
        if (event.target.closest("[data-dropzone-remove]")) return;
        input.click();
      });

      if (remove) {
        remove.addEventListener("click", function (event) {
          event.stopPropagation();
          input.value = "";
          preview.hidden = true;
          remove.hidden = true;
          if (inner) inner.hidden = false;
        });
      }

      input.addEventListener("change", function () {
        previewFile(input.files && input.files[0]);
      });

      ["dragenter", "dragover"].forEach(function (name) {
        drop.addEventListener(name, function (event) {
          event.preventDefault();
          drop.classList.add("is-dragging");
        });
      });

      ["dragleave", "drop"].forEach(function (name) {
        drop.addEventListener(name, function (event) {
          event.preventDefault();
          drop.classList.remove("is-dragging");
        });
      });

      drop.addEventListener("drop", function (event) {
        var file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
        previewFile(file);
      });
    });
  }

  /* ----------------------------------------------------------------------
     Password visibility toggles
     Button: [data-pw-toggle="input-id"], toggles type and aria-pressed
     ---------------------------------------------------------------------- */
  function initPasswordToggles() {
    document.querySelectorAll("[data-pw-toggle]").forEach(function (button) {
      button.addEventListener("click", function () {
        var input = document.getElementById(button.getAttribute("data-pw-toggle") || "");
        if (!input) return;
        var show = input.type === "password";
        input.type = show ? "text" : "password";
        button.setAttribute("aria-pressed", show ? "true" : "false");
        button.classList.toggle("is-active", show);
      });
    });
  }

  /* ----------------------------------------------------------------------
     Navbar account dropdown
     Container: [data-profile-dropdown], toggle: [data-profile-toggle].
     Primary trigger is hover (mouseenter / mouseleave) with a short exit
     delay so the pointer can cross the hover bridge into the menu. Focus
     and click cover keyboard and touch devices. Escape and outside clicks
     close it.
     ---------------------------------------------------------------------- */
  function initProfileDropdowns() {
    var dropdowns = document.querySelectorAll("[data-profile-dropdown]");
    if (!dropdowns.length) return;

    var EXIT_DELAY = 180;
    var canHover = window.matchMedia && window.matchMedia("(hover: hover) and (pointer: fine)").matches;

    function setOpen(dropdown, open) {
      dropdown.classList.toggle("is-open", open);
      var trigger = dropdown.querySelector("[data-profile-toggle]");
      if (trigger) trigger.setAttribute("aria-expanded", open ? "true" : "false");
    }

    function closeAll(except) {
      dropdowns.forEach(function (dropdown) {
        if (dropdown !== except) setOpen(dropdown, false);
      });
    }

    dropdowns.forEach(function (dropdown) {
      var trigger = dropdown.querySelector("[data-profile-toggle]");
      if (!trigger) return;

      var closeTimer = null;

      function scheduleClose() {
        clearTimeout(closeTimer);
        closeTimer = setTimeout(function () {
          setOpen(dropdown, false);
        }, EXIT_DELAY);
      }

      function cancelClose() {
        clearTimeout(closeTimer);
      }

      if (canHover) {
        dropdown.addEventListener("mouseenter", function () {
          cancelClose();
          setOpen(dropdown, true);
          closeAll(dropdown);
        });
        dropdown.addEventListener("mouseleave", scheduleClose);
      }

      dropdown.addEventListener("focusin", function () {
        cancelClose();
        setOpen(dropdown, true);
        closeAll(dropdown);
      });

      dropdown.addEventListener("focusout", function (event) {
        if (!dropdown.contains(event.relatedTarget)) setOpen(dropdown, false);
      });

      trigger.addEventListener("click", function () {
        cancelClose();
        setOpen(dropdown, true);
        closeAll(dropdown);
      });

      dropdown.querySelectorAll(".nav__profile-menu a").forEach(function (link) {
        link.addEventListener("click", function () {
          setOpen(dropdown, false);
        });
      });
    });

    document.addEventListener("click", function (event) {
      if (!event.target || !event.target.closest) return;
      if (event.target.closest("[data-profile-dropdown]")) return;
      closeAll(null);
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") closeAll(null);
    });
  }

  /* ----------------------------------------------------------------------
     Boot
     ---------------------------------------------------------------------- */
  /* ----------------------------------------------------------------------
     Preloader - fades out once the page has loaded (or after a 4s cap),
     held for at least 1.8s so the logo animation can play through. First page only.
     ---------------------------------------------------------------------- */
  function initPreloader() {
    var loader = document.querySelector("[data-preloader]");
    var root = document.documentElement;
    if (!loader) return;
    if (root.classList.contains("no-preloader")) {
      loader.remove();
      return;
    }

    var started = Date.now();
    var finished = false;

    function finish() {
      if (finished) return;
      finished = true;
      window.setTimeout(function () {
        loader.classList.add("is-done");
        root.classList.remove("is-preloading");
        try { sessionStorage.setItem("ja-preloaded", "1"); } catch (e) {}
        window.setTimeout(function () { loader.remove(); }, 900);
      }, Math.max(0, 1800 - (Date.now() - started)));
    }

    if (document.readyState === "complete") {
      finish();
    } else {
      window.addEventListener("load", finish);
      window.setTimeout(finish, 4000);
    }
  }

  initPreloader();

  /* ----------------------------------------------------------------------
     Landing payment teaser - deposit / monthly estimate, 0% interest
     ---------------------------------------------------------------------- */
  function initPlanCalculator() {
    document.querySelectorAll("[data-plan-calc]").forEach(function (calc) {
      var vehicle = calc.querySelector("[data-plan-vehicle]");
      var deposit = calc.querySelector("[data-plan-deposit]");
      var pct = calc.querySelector("[data-plan-pct]");
      var link = calc.querySelector("[data-plan-link]");
      var naira = new Intl.NumberFormat("en-NG", { maximumFractionDigits: 0 });

      function out(key, amount) {
        var node = calc.querySelector('[data-plan-out="' + key + '"]');
        if (node) node.textContent = "₦" + naira.format(amount);
      }

      function update() {
        var price = parseInt(vehicle.value, 10) || 0;
        var rate = (parseInt(deposit.value, 10) || 0) / 100;
        var checked = calc.querySelector("[data-plan-months]:checked");
        var months = checked ? parseInt(checked.value, 10) : 1;
        var upfront = Math.round(price * rate);

        pct.textContent = deposit.value + "%";
        deposit.style.setProperty("--fill", ((deposit.value - deposit.min) / (deposit.max - deposit.min)) * 100 + "%");
        out("deposit", upfront);
        out("monthly", Math.ceil((price - upfront) / months));
        out("total", price);

        var option = vehicle.options[vehicle.selectedIndex];
        if (link && option) link.href = option.getAttribute("data-url");
      }

      calc.addEventListener("input", update);
      calc.addEventListener("change", update);
      update();
    });
  }

  /* ----------------------------------------------------------------------
     Landing popup advert - opens shortly after the preloader clears, at most
     once a day per advert version (a new image from Admin > Settings resets it).
     ---------------------------------------------------------------------- */
  function initPromoPopup() {
    var popup = document.querySelector("[data-promo-popup]");
    if (!popup) return;

    var root = document.documentElement;
    var dialog = popup.querySelector('[role="dialog"]');
    var storeKey = "ja-promo-seen";
    var version = popup.getAttribute("data-promo-version") || "default";
    var lastFocus = null;

    try {
      var seen = JSON.parse(localStorage.getItem(storeKey) || "null");
      if (seen && seen.v === version && Date.now() - seen.t < 24 * 60 * 60 * 1000) return;
    } catch (e) {}

    function remember() {
      try { localStorage.setItem(storeKey, JSON.stringify({ v: version, t: Date.now() })); } catch (e) {}
    }

    function close() {
      if (popup.hidden) return;
      popup.classList.remove("is-open");
      document.body.classList.remove("modal-open");
      document.removeEventListener("keydown", onKey);
      window.setTimeout(function () { popup.hidden = true; }, 300);
      if (lastFocus && lastFocus.focus) lastFocus.focus();
    }

    function onKey(event) {
      if (event.key === "Escape") return close();
      if (event.key !== "Tab") return;
      var items = dialog.querySelectorAll("a[href], button");
      var first = items[0];
      var last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }

    function open() {
      // Never interrupt another open modal or drawer.
      if (document.body.classList.contains("modal-open")) return;
      lastFocus = document.activeElement;
      popup.hidden = false;
      void popup.offsetWidth;
      popup.classList.add("is-open");
      document.body.classList.add("modal-open");
      document.addEventListener("keydown", onKey);
      dialog.focus();
      remember();
    }

    popup.querySelectorAll("[data-promo-close]").forEach(function (el) {
      el.addEventListener("click", close);
    });
    popup.querySelectorAll("[data-promo-cta]").forEach(function (el) {
      el.addEventListener("click", remember);
    });

    // Open once the preloader has cleared, the page has settled for a moment
    // and the advert image is ready - never as an empty frame. A broken image
    // skips the popup entirely.
    var image = popup.querySelector(".promo-popup__media img");
    var imageReady = new Promise(function (resolve, reject) {
      if (!image) return reject();
      if (image.complete) return image.naturalWidth ? resolve() : reject();
      image.addEventListener("load", resolve);
      image.addEventListener("error", reject);
    });
    var pageReady = new Promise(function (resolve) {
      (function waitForPreloader() {
        if (root.classList.contains("is-preloading")) {
          window.setTimeout(waitForPreloader, 250);
          return;
        }
        window.setTimeout(resolve, 1500);
      })();
    });
    Promise.all([imageReady, pageReady]).then(open, function () {});
  }

  document.addEventListener("DOMContentLoaded", function () {
    initNav();
    initDashboardSidebar();
    initDrawer();
    initGallery();
    initForms();
    initImageFallbacks();
    initAdminTables();
    initListFilters();
    initAdminTabs();
    initAdminModals();
    initAdminConfirm();
    initInviteCopy();
    initToasts();
    initSaveButtons();
    initAvatarPreview();
    initPageLists();
    initDropzones();
    initPasswordToggles();
    initProfileDropdowns();
    initPlanCalculator();
    initPromoPopup();
  });
})();
