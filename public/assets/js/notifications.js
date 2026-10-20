/* ==========================================================================
   Jayyid Autos - notifications.js
   Topbar bell shared by the admin and customer portals.

   - Click the bell to open the dropdown; opening marks the list read
     (server side) so the badge disappears the moment you look at it.
   - "Mark all as read" does the same on demand.
   - A quiet poll keeps the badge in sync when new activity lands while the
     page is open, and hides it again as soon as the count reaches zero.

   Markup comes from the notification-bell Blade component; the data lives in
   /api/notifications.
   ========================================================================== */
(function () {
  "use strict";

  function boot(root) {
    var API = root.getAttribute("data-notif-api") || "api/notifications";

    var toggle = root.querySelector("[data-notif-toggle]");
    var panel = root.querySelector("[data-notif-panel]");
    var list = root.querySelector("[data-notif-list]");
    var empty = root.querySelector("[data-notif-empty]");
    var markAll = root.querySelector("[data-notif-mark-all]");
    var badge = root.querySelector("[data-notif-badge]");
    if (!toggle || !panel) return;

    var opened = false;

    function jaItems() {
      return Array.prototype.slice.call(list ? list.querySelectorAll("[data-notif-item]") : []);
    }

    function signature(nodes) {
      return nodes.map(function (node) {
        return node.getAttribute("data-notif-item") + ":" + (node.classList.contains("is-unread") ? "1" : "0");
      }).join("|");
    }

    /* The server sends absolute links. */
    function href(path) {
      return String(path == null ? "" : path);
    }

    /* open -> POST /api/notifications/read, read -> POST /api/notifications/{id}/read */
    function post(action, data) {
      var url = action === "read" && data ? API + "/" + encodeURIComponent(data.id) + "/read" : API + "/read";
      return window.jaHttp("POST", url);
    }

    function poll() {
      return window.jaHttp("GET", API).catch(function () { return null; });
    }

    /* --------------------------------------------------------------- render */

    /* The server omits the badge entirely while there is nothing unread, so
       build it on demand when the poll or mark-all finds new activity. */
    function ensureBadge() {
      if (badge || !toggle) return badge;
      badge = document.createElement("span");
      badge.className = "admin-topbar__badge";
      badge.setAttribute("data-notif-badge", "");
      toggle.appendChild(badge);
      return badge;
    }

    function setBadge(unread) {
      if (unread > 0) {
        ensureBadge();
        if (badge) {
          badge.hidden = false;
          badge.textContent = unread > 9 ? "9+" : String(unread);
        }
      } else if (badge) {
        badge.hidden = true;
        badge.textContent = "0";
      }
      toggle.setAttribute(
        "aria-label",
        unread > 0 ? "Notifications (" + unread + " unread)" : "Notifications"
      );
      if (markAll) markAll.hidden = unread <= 0;
    }

    function itemNode(item) {
      var li = document.createElement("li");
      li.className = "notif-item" + (item.is_read ? "" : " is-unread");
      li.setAttribute("data-notif-item", String(item.id));

      var a = document.createElement("a");
      a.href = href(item.link || "#");
      a.setAttribute("role", "menuitem");

      var dot = document.createElement("span");
      dot.className = "notif-item__dot";
      dot.setAttribute("aria-hidden", "true");

      var body = document.createElement("span");
      body.className = "notif-item__body";

      var title = document.createElement("span");
      title.className = "notif-item__title";
      title.textContent = item.title || "";

      var text = document.createElement("span");
      text.className = "notif-item__text";
      text.textContent = item.body || "";

      var time = document.createElement("span");
      time.className = "notif-item__time";
      time.textContent = item.time || "";

      body.appendChild(title);
      if (item.body) body.appendChild(text);
      body.appendChild(time);
      a.appendChild(dot);
      a.appendChild(body);
      li.appendChild(a);

      return li;
    }

    function applyState(state) {
      if (!state || state.ok !== true) return;
      var items = Array.isArray(state.items) ? state.items : [];

      var next = items.map(itemNode);
      if (list && signature(jaItems()) !== signature(next)) {
        list.textContent = "";
        next.forEach(function (node) { list.appendChild(node); });
      }
      if (empty) empty.hidden = next.length > 0;
      setBadge(Number(state.unread) || 0);
    }

    /* ---------------------------------------------------------------- open */

    function open() {
      if (opened) return;
      opened = true;
      panel.hidden = false;
      toggle.setAttribute("aria-expanded", "true");

      var unread = Number((badge && !badge.hidden && badge.textContent || "0").replace("+", "")) || 0;
      if (unread > 0 || jaItems().some(function (node) { return node.classList.contains("is-unread"); })) {
        post("open").then(applyState).catch(function () {});
      }
    }

    function close() {
      if (!opened) return;
      opened = false;
      panel.hidden = true;
      toggle.setAttribute("aria-expanded", "false");
    }

    /* --------------------------------------------------------------- events */

    toggle.addEventListener("click", function (event) {
      event.stopPropagation();
      if (opened) close();
      else open();
    });

    document.addEventListener("click", function (event) {
      if (opened && !root.contains(event.target)) close();
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && opened) {
        close();
        toggle.focus();
      }
    });

    if (markAll) {
      markAll.addEventListener("click", function (event) {
        event.stopPropagation();
        post("open").then(applyState).catch(function () {});
      });
    }

    list && list.addEventListener("click", function (event) {
      var item = event.target.closest("[data-notif-item]");
      if (!item || !item.classList.contains("is-unread")) return;
      post("read", { id: item.getAttribute("data-notif-item") }).catch(function () {});
    });

    /* Keep the badge honest without a page refresh. */
    window.setInterval(function () {
      if (document.hidden) return;
      poll().then(applyState);
    }, 45000);
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-notif]").forEach(boot);
  });
})();
