/* Recalcula la huella de una declaración en el navegador (WebCrypto), con el mismo
   algoritmo que el servidor: JSON canónico + SHA-256 por secciones con sal. */
(function () {
  "use strict";
  var SECTIONS = ["meta", "work", "declarant", "scope", "classification", "summary", "process", "article50", "statement", "evidence"];
  var FORMAT = "oride-declaration/1";

  function canonical(v) {
    if (v === null) return "null";
    if (typeof v === "boolean") return v ? "true" : "false";
    if (typeof v === "number") {
      if (!Number.isSafeInteger(v)) throw new Error("Solo se admiten enteros");
      return String(v);
    }
    if (typeof v === "string") return JSON.stringify(v);
    if (Array.isArray(v)) return "[" + v.map(canonical).join(",") + "]";
    return "{" + Object.keys(v).sort().map(function (k) { return JSON.stringify(k) + ":" + canonical(v[k]); }).join(",") + "}";
  }
  function sha256(text) {
    return crypto.subtle.digest("SHA-256", new TextEncoder().encode(text)).then(function (buf) {
      return Array.prototype.map.call(new Uint8Array(buf), function (b) { return b.toString(16).padStart(2, "0"); }).join("");
    });
  }
  function verify(view) {
    var digests = {};
    var mismatched = [];
    return SECTIONS.reduce(function (p, name) {
      return p.then(function () {
        var s = view.sections[name];
        if (s) {
          return sha256(canonical({ section: name, salt: s.salt, data: s.data })).then(function (d) {
            digests[name] = d;
            if (view.digests && view.digests[name] !== d) mismatched.push(name);
          });
        }
        digests[name] = view.digests[name];
      });
    }, Promise.resolve()).then(function () {
      return sha256(canonical({ format: FORMAT, sections: digests })).then(function (root) { return { root: root, mismatched: mismatched }; });
    });
  }

  document.querySelectorAll("[data-verify]").forEach(function (box) {
    var btn = box.querySelector("[data-verify-run]");
    var out = box.querySelector("[data-verify-result]");
    btn.addEventListener("click", function () {
      btn.setAttribute("aria-busy", "true");
      out.innerHTML = "";
      fetch(box.getAttribute("data-verify"), { cache: "no-store" })
        .then(function (r) { return r.json(); })
        .then(function (json) { return verify(json.view); })
        .then(function (r) {
          var ok = r.root === box.getAttribute("data-hash") && r.mismatched.length === 0;
          out.className = "alert " + (ok ? "alert-ok" : "alert-danger");
          out.textContent = (ok ? "La huella recalculada coincide con la registrada. " : "La huella recalculada no coincide. ") + r.root;
        })
        .catch(function () {
          out.className = "alert alert-danger";
          out.textContent = "No se ha podido completar la comprobación. Vuelve a intentarlo.";
        })
        .then(function () { btn.removeAttribute("aria-busy"); });
    });
  });
})();
