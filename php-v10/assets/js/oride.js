/* ORIDE v10 — JavaScript del sitio. Sin dependencias, sin cookies y sin almacenamiento local. */
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
  // Región oculta para anunciar a los lectores de pantalla mensajes breves («Copiado»).
  var live = document.createElement("p");
  live.className = "sr-only";
  live.setAttribute("role", "status");
  document.body.appendChild(live);
  function announce(msg) { live.textContent = ""; setTimeout(function () { live.textContent = msg; }, 50); }

  // Botones de copiar: delegación, para que funcionen también en lo que se redibuja (caja del índice).
  var COPY_ATTRS = ["data-copy-code", "data-copy-text", "data-copy-link", "data-copy-index"];
  document.addEventListener("click", function (e) {
    var btn = e.target.closest && e.target.closest("[data-copy-code],[data-copy-text],[data-copy-link],[data-copy-index]");
    if (!btn || btn.disabled) return;
    var attr = COPY_ATTRS.filter(function (a) { return btn.hasAttribute(a); })[0];
    var text = btn.getAttribute(attr);
    if (!text) return;
    var label = btn.getAttribute("data-label") || btn.textContent;
    btn.setAttribute("data-label", label);
    copyText(text).then(function (ok) {
      btn.textContent = ok ? fmt("copied") : fmt("copy_failed");
      announce(btn.textContent);
      setTimeout(function () { btn.textContent = label; }, 1800);
    });
  });

  // El enlace de cambio de idioma conserva las respuestas que haya en la URL.
  function updateSwitch(params) {
    var sw = $("[data-lang-switch]");
    if (!sw) return;
    var p = new URLSearchParams(params.toString());
    p.set("lang", sw.getAttribute("data-lang-switch"));
    sw.setAttribute("href", location.pathname + "?" + p.toString());
  }

  // --- Descargas de la figura (PNG y SVG): la versión para descargar está en <template data-export> ---
  var downloadBlob = function (name, blob) {
    var a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = name;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
  };
  document.addEventListener("click", function (e) {
    var btn = e.target.closest && e.target.closest("[data-download]");
    if (!btn || btn.disabled) return;
    var box = btn.closest(".decl-box"), tpl = box && $("template[data-export]", box);
    if (!tpl) return;
    // Al leer el HTML, el navegador escribe los espacios duros como &nbsp;, que no es válido en SVG (XML).
    var svg = tpl.innerHTML.trim().replace(/&nbsp;/g, "&#160;");
    var name = box.getAttribute("data-file") || "oride";
    if (btn.getAttribute("data-download") === "svg") {
      downloadBlob(name + ".svg", new Blob(['<?xml version="1.0" encoding="UTF-8"?>\n' + svg], { type: "image/svg+xml" }));
      return;
    }
    var img = new Image(), scale = 2;
    img.onload = function () {
      var c = document.createElement("canvas");
      c.width = img.width * scale; c.height = img.height * scale;
      var ctx = c.getContext("2d");
      ctx.scale(scale, scale);
      ctx.drawImage(img, 0, 0);
      c.toBlob(function (b) { downloadBlob(name + ".png", b); }, "image/png");
    };
    img.src = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(svg);
  });

  // --- Formulario (crear.php e instituciones.php) ---
  // El código y el Índice Global ORIDE los calcula el servidor (calc.php): una sola fuente de verdad.
  // Aquí solo se muestran u ocultan preguntas al momento, se baja a la siguiente y se pinta la respuesta.
  var form = $("[data-builder]");
  if (form) {
    var box = $("[data-box]");
    var chip = $("[data-chip]");
    var inst = form.getAttribute("data-mode") === "inst";
    var calcUrl = (form.getAttribute("action") || "").replace(/[^/?#]*([?#].*)?$/, "") + "calc.php";
    var seq = 0, timer = null;
    var boxVisible = false;
    var wide = window.matchMedia("(min-width: 960px)");
    var reduce = window.matchMedia("(prefers-reduced-motion: reduce)");
    if (chip && "IntersectionObserver" in window) {
      new IntersectionObserver(function (entries) { boxVisible = entries[0].isIntersecting; chip.classList.toggle("chip-off", boxVisible); }).observe(box);
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
        $all("select[name^='u_" + type + "_']", form).forEach(function (sel) {
          if (sel.value === "0") return;
          var act = sel.name.slice(("u_" + type + "_").length);
          p.set(sel.name, sel.value);
          var s = fd.get("s_" + type + "_" + act);
          if (+sel.value >= 2 && s !== null) p.set("s_" + type + "_" + act, s);
        });
      }
      return p;
    };

    var paint = function (d) {
      var fig = $("[data-figure]", box);
      if (fig.getAttribute("data-html") !== d.figureHtml) { fig.innerHTML = d.figureHtml; fig.setAttribute("data-html", d.figureHtml); }
      $("[data-figure-text]", box).textContent = d.figureText;
      $("[data-code]", box).textContent = d.code;
      box.setAttribute("data-file", d.file || "oride");
      $all("[data-download]", box).forEach(function (b) { b.disabled = !d.complete; });
      var bc = $("[data-copy-code]", box), bl = $("[data-copy-link]", box);
      bc.disabled = bl.disabled = !d.complete;
      bc.setAttribute("data-copy-code", d.copy.code);
      bl.setAttribute("data-copy-link", d.copy.link);
      var vt = $("[data-verify-tab]");
      if (vt) vt.setAttribute("href", d.verifyUrl || vt.getAttribute("href").replace(/\?r=[^&]*&?/, "?"));
      var vf = $("[data-verify]", box);
      if (vf) {
        if (d.verifyUrl) { vf.setAttribute("href", d.verifyUrl); vf.removeAttribute("aria-disabled"); }
        else { vf.removeAttribute("href"); vf.setAttribute("aria-disabled", "true"); }
      }
      if (chip) {
        chip.hidden = d.status === "empty";
        $("[data-chip-text]", chip).textContent = d.title;
      }
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

    // Al responder una de las tres primeras preguntas, la página baja sola a la siguiente.
    var goTo = function (el) {
      if (!el || el.hidden) return;
      el.scrollIntoView({ block: "start", behavior: reduce.matches ? "auto" : "smooth" });
    };
    var next = { type: "q-ia", ia: "q-ev" };

    form.addEventListener("change", function (e) {
      var t = e.target;
      // Grado 0 o 1: no hay alcance; se borra el que hubiera.
      if (t.tagName === "SELECT" && /^u_/.test(t.name)) {
        var act = t.closest(".act");
        if (act) act.classList.toggle("used", t.value !== "0");
        if (+t.value < 2) $all("input[name='s_" + t.name.slice(2) + "']", form).forEach(function (r) { r.checked = false; });
      }
      applyVisibility(new FormData(form));
      schedule();
      if (next[t.name]) goTo(document.getElementById(next[t.name]));
      else if (t.name === "ev") {
        var ia = new FormData(form).get("ia");
        goTo(ia === "si" ? document.getElementById("q-acts") : (wide.matches ? null : box));
      }
    });
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
  // Verificar una entrega: «Editar requisito» / «Editar declaración» siguen al código escrito.
  $all("[data-edit-for]").forEach(function (a) {
    var field = document.getElementById(a.getAttribute("data-edit-for"));
    if (!field) return;
    field.addEventListener("input", function () {
      var page = a.getAttribute("data-page"), code = field.value.trim();
      a.href = code ? page + (page.indexOf("?") < 0 ? "?" : "&") + "from=" + encodeURIComponent(code) : page;
    });
  });
})();
