/* ORIDE — comportamiento común (sin dependencias). Todo funciona también sin JavaScript;
   esto solo mejora la experiencia. */
(function () {
  "use strict";
  document.documentElement.classList.add("js");

  var MSG = {
    missing: "Este campo es obligatorio.",
    email: "Escribe un correo con el formato nombre@dominio.com.",
    url: "Escribe una dirección completa que empiece por https://.",
    short: function (n) { return "Escribe al menos " + n + " caracteres."; },
    generic: "Revisa el formato de este campo."
  };

  function isField(el) {
    return el && (el.tagName === "INPUT" || el.tagName === "TEXTAREA" || el.tagName === "SELECT");
  }
  function errorSlot(el) {
    if (el.id) {
      var s = document.getElementById(el.id + "-error");
      if (s) return s;
    }
    var fs = el.closest("fieldset");
    return fs ? fs.querySelector(".field-error") : null;
  }
  function message(el) {
    if (el.disabled || el.type === "hidden" || el.type === "file" || el.type === "radio" || el.type === "checkbox") return null;
    var v = el.validity;
    if (!v || v.valid) return null;
    if (v.valueMissing) return el.getAttribute("data-error") || MSG.missing;
    if (v.typeMismatch) return el.type === "email" ? MSG.email : el.type === "url" ? MSG.url : MSG.generic;
    if (v.tooShort) return MSG.short(el.minLength);
    return el.getAttribute("data-error") || MSG.generic;
  }
  function show(el, msg) {
    el.setAttribute("aria-invalid", "true");
    var s = errorSlot(el);
    if (s) { s.textContent = msg; s.hidden = false; }
  }
  function clear(el) {
    el.removeAttribute("aria-invalid");
    var s = errorSlot(el);
    if (s) s.hidden = true;
  }

  // --- Formularios: validación al salir del campo, error borrado al editar, borradores ---
  document.querySelectorAll("form[method=post], form[data-validate]").forEach(function (form) {
    form.noValidate = true;
    var key = form.getAttribute("data-draft") ? "oride:draft:" + form.getAttribute("data-draft") : null;
    var dirty = false;

    form.addEventListener("focusout", function (e) {
      if (!isField(e.target)) return;
      var m = message(e.target);
      if (m) show(e.target, m);
    });
    function onEdit(e) {
      if (!isField(e.target)) return;
      clear(e.target);
      if (key && e.isTrusted) { dirty = true; save(); }
    }
    form.addEventListener("input", onEdit);
    form.addEventListener("change", onEdit);

    form.addEventListener("submit", function (e) {
      var bad = Array.prototype.filter.call(form.elements, function (el) { return isField(el) && message(el); });
      if (bad.length) {
        e.preventDefault();
        bad.forEach(function (el) { show(el, message(el)); });
        bad[0].focus();
        bad[0].scrollIntoView({ block: "center" });
        return;
      }
      dirty = false;
      if (key) sessionStorage.setItem(key + ":submitted", "1");
      var btn = e.submitter;
      if (btn) {
        if (btn.getAttribute("aria-busy") === "true") { e.preventDefault(); return; }
        setTimeout(function () { btn.setAttribute("aria-busy", "true"); }, 100);
      }
    });

    if (!key) return;
    function serialize() {
      var out = {};
      Array.prototype.forEach.call(form.elements, function (el) {
        if (!isField(el) || !el.name || el.name === "_csrf" || el.type === "password" || el.type === "file" || el.type === "hidden") return;
        if (el.type === "radio" || el.type === "checkbox") {
          out[el.name] = out[el.name] || [];
          if (el.checked) out[el.name].push(el.value);
        } else {
          (out[el.name] = out[el.name] || []).push(el.value);
        }
      });
      return out;
    }
    function save() {
      try { localStorage.setItem(key, JSON.stringify({ at: Date.now(), data: serialize() })); } catch (err) { /* sin almacenamiento */ }
    }
    // Tras un envío correcto (la página siguiente ya no muestra errores), el borrador se descarta.
    try {
      if (sessionStorage.getItem(key + ":submitted") && !document.querySelector(".field-error:not([hidden]), .alert-danger")) {
        localStorage.removeItem(key);
      }
      sessionStorage.removeItem(key + ":submitted");
      var raw = localStorage.getItem(key);
      if (raw) {
        var saved = JSON.parse(raw);
        if (Date.now() - saved.at < 7 * 86400000 && JSON.stringify(saved.data) !== JSON.stringify(serialize())) {
          var seen = {};
          Array.prototype.forEach.call(form.elements, function (el) {
            if (!isField(el) || !el.name || !(el.name in saved.data) || el.type === "password" || el.type === "file" || el.type === "hidden") return;
            var vals = saved.data[el.name];
            if (el.type === "radio" || el.type === "checkbox") {
              el.checked = vals.indexOf(el.value) !== -1;
            } else {
              var i = seen[el.name] = (seen[el.name] === undefined ? 0 : seen[el.name] + 1);
              if (vals[i] !== undefined) el.value = vals[i];
            }
            el.dispatchEvent(new Event("change", { bubbles: true }));
          });
          dirty = true;
          var note = document.createElement("div");
          note.className = "alert alert-info restored";
          note.setAttribute("role", "status");
          note.innerHTML = "<span>Hemos recuperado cambios que no llegaste a guardar.</span>";
          var b = document.createElement("button");
          b.type = "button";
          b.className = "btn btn-link";
          b.textContent = "Descartar cambios recuperados";
          b.addEventListener("click", function () { localStorage.removeItem(key); dirty = false; location.reload(); });
          note.appendChild(b);
          form.insertBefore(note, form.firstChild);
        } else if (Date.now() - saved.at >= 7 * 86400000) {
          localStorage.removeItem(key);
        }
      }
    } catch (err) { /* borrador ilegible */ }
    window.addEventListener("beforeunload", function (e) {
      if (dirty) { e.preventDefault(); e.returnValue = ""; }
    });
  });

  // --- Diálogos de confirmación de acciones destructivas ---
  document.querySelectorAll("[data-confirm]").forEach(function (trigger) {
    var dialog = trigger.closest(".confirm").querySelector("dialog");
    var cancel = dialog.querySelector("[data-cancel]");
    trigger.addEventListener("click", function () {
      dialog.showModal();
      cancel.focus();
    });
    cancel.addEventListener("click", function () { dialog.close(); });
    dialog.addEventListener("click", function (e) { if (e.target === dialog) dialog.close(); });
    dialog.addEventListener("close", function () { trigger.focus(); });
  });

  // --- Campos condicionales: data-show-if="nombre=valor1,valor2" ---
  var conditional = document.querySelectorAll("[data-show-if]");
  function refresh() {
    conditional.forEach(function (el) {
      var parts = el.getAttribute("data-show-if").split("=");
      var name = parts[0], values = parts[1].split(",");
      var inputs = document.querySelectorAll('[name="' + name + '"], [name="' + name + '[]"]');
      var on = Array.prototype.some.call(inputs, function (i) {
        if (i.type === "radio" || i.type === "checkbox") return i.checked && values.indexOf(i.value) !== -1;
        return values.indexOf(i.value) !== -1;
      });
      el.hidden = !on;
    });
  }
  if (conditional.length) {
    document.addEventListener("change", refresh);
    refresh();
  }

  // --- Menú móvil ---
  var toggle = document.querySelector(".menu-toggle");
  if (toggle) {
    var nav = document.getElementById(toggle.getAttribute("aria-controls"));
    toggle.addEventListener("click", function () {
      var open = toggle.getAttribute("aria-expanded") !== "true";
      toggle.setAttribute("aria-expanded", String(open));
      nav.classList.toggle("open", open);
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && toggle.getAttribute("aria-expanded") === "true") {
        toggle.click();
        toggle.focus();
      }
    });
  }

  // --- Copiar al portapapeles ---
  document.querySelectorAll("[data-copy]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var text = btn.getAttribute("data-copy");
      if (!navigator.clipboard) return;
      navigator.clipboard.writeText(text).then(function () {
        var old = btn.textContent;
        btn.textContent = "Copiado";
        setTimeout(function () { btn.textContent = old; }, 1800);
      });
    });
  });

  // --- «/» enfoca la búsqueda ---
  var search = document.querySelector("[data-slash-focus]");
  if (search) {
    document.addEventListener("keydown", function (e) {
      if (e.key !== "/" || e.ctrlKey || e.metaKey || e.altKey) return;
      if (e.target.closest && e.target.closest("input, textarea, select, [contenteditable]")) return;
      e.preventDefault();
      search.focus();
    });
  }

  // --- Explicador interactivo del código (pestañas por segmento) ---
  document.querySelectorAll("[data-segments]").forEach(function (box) {
    var tabs = box.querySelectorAll("[role=tab]");
    function select(tab) {
      tabs.forEach(function (t) {
        var on = t === tab;
        t.setAttribute("aria-selected", String(on));
        t.tabIndex = on ? 0 : -1;
        document.getElementById(t.getAttribute("aria-controls")).hidden = !on;
      });
    }
    tabs.forEach(function (t, i) {
      t.addEventListener("click", function () { select(t); });
      t.addEventListener("keydown", function (e) {
        if (e.key === "ArrowRight" || e.key === "ArrowLeft") {
          var n = tabs[(i + (e.key === "ArrowRight" ? 1 : tabs.length - 1)) % tabs.length];
          select(n);
          n.focus();
        }
      });
    });
  });
})();
