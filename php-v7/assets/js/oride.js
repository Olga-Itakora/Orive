/* ORIDE v7 — JavaScript del sitio. Sin dependencias, sin cookies y sin almacenamiento local. */
(function () {
  "use strict";
  document.documentElement.classList.add("js");

  var I18N = {};
  try { I18N = JSON.parse(document.body.getAttribute("data-i18n") || "{}"); } catch (e) { I18N = {}; }
  var STR = I18N.str || {};
  function fmt(key) {
    var args = Array.prototype.slice.call(arguments, 1), i = 0;
    return String(STR[key] || key).replace(/%s/g, function () { return args[i++]; });
  }
  var LANG = I18N.lang || "es";

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $all(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]; });
  }

  // --- Menú en pantallas estrechas ---
  var toggle = $(".menu-toggle");
  var nav = $("#site-nav");
  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      var open = toggle.getAttribute("aria-expanded") !== "true";
      toggle.setAttribute("aria-expanded", String(open));
      nav.classList.toggle("open", open);
    });
  }

  // --- Copiar al portapapeles (con alternativa para conexiones sin HTTPS) ---
  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text).then(function () { return true; }, function () { return legacyCopy(text); });
    }
    return Promise.resolve(legacyCopy(text));
  }
  function legacyCopy(text) {
    var ta = document.createElement("textarea");
    ta.value = text;
    ta.setAttribute("readonly", "");
    ta.className = "copy-buffer";
    document.body.appendChild(ta);
    ta.select();
    var ok = false;
    try { ok = document.execCommand("copy"); } catch (e) { ok = false; }
    document.body.removeChild(ta);
    return ok;
  }
  function bindCopy(btn, attr) {
    btn.addEventListener("click", function () {
      var text = btn.getAttribute(attr);
      if (!text) return;
      var label = btn.getAttribute("data-label") || btn.textContent;
      btn.setAttribute("data-label", label);
      copyText(text).then(function (ok) {
        btn.textContent = ok ? fmt("copied") : fmt("copy_failed");
        setTimeout(function () { btn.textContent = label; }, 1800);
      });
    });
  }
  $all("[data-copy-code]").forEach(function (b) { bindCopy(b, "data-copy-code"); });
  $all("[data-copy-text]").forEach(function (b) { bindCopy(b, "data-copy-text"); });
  $all("[data-copy-link]").forEach(function (b) { bindCopy(b, "data-copy-link"); });

  // El enlace de cambio de idioma conserva las respuestas que haya en la URL.
  function updateSwitch(params) {
    var sw = $("[data-lang-switch]");
    if (!sw) return;
    var p = new URLSearchParams(params.toString());
    p.set("lang", sw.getAttribute("data-lang-switch"));
    sw.setAttribute("href", location.pathname + "?" + p.toString());
  }

  // --- Formulario (crear.php e instituciones.php) ---
  // El código y el Índice Global ORIDE los calcula el servidor (calc.php): una sola fuente de verdad.
  // Aquí solo se muestran u ocultan preguntas al momento y se pinta la respuesta.
  var form = $("[data-builder]");
  if (form) {
    var box = $("[data-box]");
    var chip = $("[data-chip]");
    var inst = form.getAttribute("data-mode") === "inst";
    var calcUrl = (form.getAttribute("action") || "").replace(/[^/?#]*([?#].*)?$/, "") + "calc.php";
    var seq = 0, timer = null;
    if (chip && "IntersectionObserver" in window) {
      new IntersectionObserver(function (entries) { chip.classList.toggle("chip-off", entries[0].isIntersecting); }).observe(box);
    }

    // data-when="clave=v1,v2": visible si el campo «clave» tiene alguno de esos valores.
    var applyVisibility = function (fd) {
      $all("[data-when]", form).forEach(function (el) {
        var parts = el.getAttribute("data-when").split("=");
        var vals = parts[1].split(",");
        var current = fd.getAll(parts[0]);
        el.hidden = !vals.some(function (v) { return current.indexOf(v) >= 0; });
      });
    };

    // Respuestas relevantes, en el orden de la URL (se puede recargar o compartir sin guardar nada).
    var params = function (fd) {
      var p = new URLSearchParams();
      p.set("lang", LANG);
      var type = fd.get("type") || "", ia = fd.get("ia") || "";
      if (type) p.set("type", type);
      if (ia) p.set("ia", ia);
      if (ia && fd.get("ev")) p.set("ev", fd.get("ev"));
      if (ia === "si" && type) {
        fd.getAll("acts_" + type + "[]").forEach(function (act) {
          p.append("acts_" + type + "[]", act);
          var u = fd.get("u_" + type + "_" + act), s = fd.get("s_" + type + "_" + act);
          if (u !== null) p.set("u_" + type + "_" + act, u);
          if (u !== null && +u >= 2 && s !== null) p.set("s_" + type + "_" + act, s);
        });
        var showQ = $("[data-showq]", form);
        if (showQ && !showQ.hidden) {
          if (fd.get("q") !== null) p.set("q", fd.get("q"));
          if ((fd.get("qp") || "").trim() !== "") p.set("qp", fd.get("qp").trim());
        }
      }
      return p;
    };

    var paint = function (d) {
      var introEl = $("[data-intro]", box);
      $("[data-code]", box).innerHTML = d.codeHtml;
      $("[data-status]", box).textContent = d.statusText;
      $("[data-explain]", box).innerHTML = d.explainHtml;
      $("[data-index]", box).innerHTML = d.indexHtml;
      var az = $("[data-allzero]", box), azf = $("[data-allzero-form]", form);
      if (az) az.hidden = !d.allZero;
      if (azf) azf.hidden = !d.allZero;
      var q = $("[data-showq]", form);
      if (q && q.hidden === d.needsQ) { q.hidden = !d.needsQ; if (d.needsQ) schedule(); }
      var complete = d.status === "complete";
      var bc = $("[data-copy-code]", box), bt = $("[data-copy-text]", box), bl = $("[data-copy-link]", box);
      bc.disabled = bt.disabled = bl.disabled = !complete;
      bc.setAttribute("data-copy-code", d.copy.code);
      bt.setAttribute("data-copy-text", d.copy.text);
      bl.setAttribute("data-copy-link", d.copy.link);
      if (chip) {
        chip.hidden = d.status === "empty";
        $("[data-chip-code]", chip).textContent = d.chip;
      }
      if (introEl) introEl.hidden = false;
    };

    var update = function () {
      var fd = new FormData(form);
      applyVisibility(fd);
      var p = params(fd);
      history.replaceState(null, "", location.pathname + "?" + p.toString());
      updateSwitch(p);
      var my = ++seq;
      var q = new URLSearchParams(p.toString());
      if (inst) q.set("mode", "inst");
      fetch(calcUrl + "?" + q.toString(), { headers: { "Accept": "application/json" } })
        .then(function (res) { return res.json(); })
        .then(function (d) { if (my === seq) paint(d); })
        .catch(function () { /* Sin conexión: la caja conserva el último resultado. */ });
    };
    var schedule = function () { clearTimeout(timer); timer = setTimeout(update, 60); };

    form.addEventListener("change", function () { applyVisibility(new FormData(form)); schedule(); });
    form.addEventListener("input", function (e) { if (e.target.name === "qp") schedule(); });
    form.addEventListener("submit", function (e) { e.preventDefault(); update(); });
    applyVisibility(new FormData(form));
  }

  // --- Validación al salir del campo (formulario de mejora) ---
  $all("form[data-validate]").forEach(function (f) {
    var check = function (el) {
      var err = document.getElementById(el.id + "-error");
      if (!err) return true;
      var msg = "";
      if (el.validity.valueMissing) msg = el.getAttribute("data-msg-required") || fmt("v_required");
      else if (el.validity.typeMismatch) msg = fmt("v_email");
            err.textContent = msg;
      err.hidden = !msg;
      if (msg) el.setAttribute("aria-invalid", "true"); else el.removeAttribute("aria-invalid");
      return !msg;
    };
    $all("input[required], textarea[required]", f).forEach(function (el) {
      el.addEventListener("blur", function () { check(el); });
      el.addEventListener("input", function () { if (el.getAttribute("aria-invalid")) check(el); });
    });
    f.addEventListener("submit", function (e) {
      var first = null;
      $all("input[required], textarea[required]", f).forEach(function (el) { if (!check(el) && !first) first = el; });
      if (first) { e.preventDefault(); first.focus(); }
    });
    f.setAttribute("novalidate", "");
  });
})();
