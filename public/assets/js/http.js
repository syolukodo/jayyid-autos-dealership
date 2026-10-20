/* ==========================================================================
   Jayyid Autos - http.js
   Shared JSON request helper for the page scripts. Sends Laravel's CSRF
   token (from <meta name="csrf-token">) and asks for JSON so validation
   and auth failures come back as { message } rather than a redirect.
   ========================================================================== */
(function () {
  "use strict";

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") || "" : "";
  }

  /**
   * jaHttp("POST", url, { key: value }) -> Promise<object>
   * Resolves with the decoded JSON body (adding ok:false for error statuses
   * that did not set it); rejects only on network failure.
   */
  window.jaHttp = function (method, url, data) {
    var options = {
      method: method,
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": csrfToken()
      }
    };

    if (data && method !== "GET") {
      var body = new FormData();
      Object.keys(data).forEach(function (key) {
        if (data[key] !== undefined && data[key] !== null) body.append(key, data[key]);
      });
      options.body = body;
    }

    return fetch(url, options).then(function (response) {
      return response.json()
        .catch(function () { return {}; })
        .then(function (payload) {
          if (!response.ok && payload.ok === undefined) payload.ok = false;
          if (!response.ok && !payload.message) payload.message = "Something went wrong. Please try again.";
          payload.httpStatus = response.status;
          return payload;
        });
    });
  };
})();
