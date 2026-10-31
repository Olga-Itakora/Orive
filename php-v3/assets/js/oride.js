/* ORIDE v3 — JavaScript del sitio. Sin dependencias, sin cookies y sin almacenamiento local. */
(function () {
  "use strict";
  document.documentElement.classList.add("js");

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
        btn.textContent = ok ? "Copiado" : "No se pudo copiar";
        setTimeout(function () { btn.textContent = label; }, 1800);
      });
    });
  }
  $all("[data-copy-code]").forEach(function (b) { bindCopy(b, "data-copy-code"); });
  $all("[data-copy-text]").forEach(function (b) { bindCopy(b, "data-copy-text"); });

  // --- Lógica del código (idéntica a includes/app.php) ---
  var TAX = null;
  var ORDER = ["D", "I", "E", "C", "R", "F", "T"];
  function needsScope(u) { return u >= 3; }
  function areaPart(l, u, s) { return l + (u === null ? "" : u) + (u !== null && needsScope(u) && s !== null ? s : ""); }
  function compute(inp) {
    var r = { status: "empty", code: null, areas: [], ev: null, missing: null };
    if (inp.ia === "no") { r.status = "complete"; r.code = "IA0"; return r; }
    if (inp.ia !== "si") return r;
    r.status = "pending";
    ORDER.forEach(function (l) {
      if (inp.areas.indexOf(l) < 0) return;
      var u = /^[0-5]$/.test(inp.u[l] || "") ? +inp.u[l] : null;
      var s = u !== null && needsScope(u) && /^[0-4]$/.test(inp.s[l] || "") ? +inp.s[l] : null;
      r.areas.push([l, u, s]);
    });
    r.ev = inp.ev === "si" ? 1 : inp.ev === "no" ? 0 : null;
    if (!r.areas.length) { r.missing = "Señala al menos un área."; return r; }
    for (var i = 0; i < r.areas.length; i++) {
      var a = r.areas[i];
      if (a[1] === null) { r.missing = "Indica cómo utilizaste los resultados de la IA para " + TAX.areas[a[0]][2] + "."; return r; }
      if (needsScope(a[1]) && a[2] === null) { r.missing = "Indica qué parte de tu obra está afectada por " + TAX.areas[a[0]][2] + "."; return r; }
    }
    if (r.ev === null) { r.missing = "Indica si conservas evidencias."; return r; }
    r.status = "complete";
    r.code = "IA1-" + r.areas.map(function (a) { return areaPart(a[0], a[1], a[2]); }).join("-") + "-V" + r.ev;
    return r;
  }
  function areaLines(l, u, s) {
    var lines = [TAX.areas[l][3][u]];
    if (s !== null) lines.push("Parte de la obra afectada: " + TAX.scope[s].toLowerCase());
    return lines;
  }
  function plainText(r) {
    if (r.status !== "complete") return "";
    if (r.code === "IA0") return "Código ORIDE IA0\nNo se utilizó inteligencia artificial en la elaboración de esta obra.";
    var lines = ["Código ORIDE " + r.code];
    r.areas.forEach(function (a) { lines.push(TAX.areas[a[0]][0] + ": " + areaLines(a[0], a[1], a[2]).join(". ") + "."); });
    lines.push("Evidencias: " + TAX.evidence[r.ev].toLowerCase());
    return lines.join("\n");
  }
  function seg(v, empty) { return '<span class="seg' + (empty ? " seg-empty" : "") + '">' + esc(v) + "</span>"; }
  function segmentsHtml(r) {
    if (r.status === "empty") return seg("IA–", true);
    if (r.code === "IA0") return seg("IA0");
    var parts = [seg("IA1")];
    r.areas.forEach(function (a) {
      var done = a[1] !== null && (!needsScope(a[1]) || a[2] !== null);
      parts.push(seg(areaPart(a[0], a[1], a[2]) + (done ? "" : "–"), !done));
    });
    if (!r.areas.length) parts.push(seg("…", true));
    parts.push(seg(r.ev === null ? "V–" : "V" + r.ev, r.ev === null));
    return parts.join('<span class="sep">-</span>');
  }
  function explainHtml(r) {
    if (r.status === "empty" || r.code === "IA0") return "";
    var html = "";
    r.areas.forEach(function (a) {
      if (a[1] === null) return;
      html += '<div class="dim"><span>' + esc(areaPart(a[0], a[1], a[2]) + " · " + TAX.areas[a[0]][0]) + "</span>"
        + areaLines(a[0], a[1], a[2]).map(function (t) { return '<p class="val">' + esc(t) + "</p>"; }).join("") + "</div>";
    });
    if (r.ev !== null) {
      html += '<div class="dim"><span>V' + r.ev + " · Evidencias</span><p class=\"val\">" + esc(TAX.evidence[r.ev]) + "</p>"
        + (r.ev ? '<p class="val note">' + esc(TAX.evidenceNote) + "</p>" : "") + "</div>";
    }
    return html;
  }

  // --- Formulario «Crea tu declaración» ---
  var form = $("[data-builder]");
  if (form) {
    TAX = JSON.parse(form.getAttribute("data-taxonomy"));
    var box = $("[data-box]");
    var chip = $("[data-chip]");
    if (chip && "IntersectionObserver" in window) {
      new IntersectionObserver(function (entries) { chip.classList.toggle("chip-off", entries[0].isIntersecting); }).observe(box);
    }

    var read = function () {
      var fd = new FormData(form);
      var u = {}, s = {};
      ORDER.forEach(function (l) {
        if (fd.get("u" + l) !== null) u[l] = fd.get("u" + l);
        if (fd.get("s" + l) !== null) s[l] = fd.get("s" + l);
      });
      return { ia: fd.get("ia") || "", areas: fd.getAll("areas[]"), u: u, s: s, ev: fd.get("ev") || "" };
    };

    var applyVisibility = function (inp) {
      $all("[data-when]", form).forEach(function (el) {
        var parts = el.getAttribute("data-when").split("=");
        var key = parts[0], vals = parts[1].split(",");
        var current = key === "areas" ? inp.areas : key[0] === "u" && key.length === 2 ? [inp.u[key[1]]] : [inp[key]];
        el.hidden = !vals.some(function (v) { return current.indexOf(v) >= 0; });
      });
    };

    var render = function () {
      var inp = read();
      applyVisibility(inp);
      var r = compute(inp);
      $("[data-code]", box).innerHTML = segmentsHtml(r);
      $("[data-status]", box).textContent = r.status === "complete"
        ? (r.code === "IA0" ? "No se utilizó inteligencia artificial." : "Declaración completa.")
        : (r.missing || "Responde a la primera pregunta para empezar.");
      $("[data-explain]", box).innerHTML = explainHtml(r);

      var complete = r.status === "complete";
      var bc = $("[data-copy-code]", box), bt = $("[data-copy-text]", box);
      bc.disabled = bt.disabled = !complete;
      bc.setAttribute("data-copy-code", complete ? "ORIDE " + r.code : "");
      bt.setAttribute("data-copy-text", plainText(r));

      if (chip) {
        chip.hidden = r.status === "empty";
        $("[data-chip-code]", chip).textContent = complete ? r.code : "pendiente";
      }

      // La URL refleja las respuestas: se puede recargar o compartir sin guardar nada.
      var params = new URLSearchParams();
      if (inp.ia) params.set("ia", inp.ia);
      if (inp.ia === "si") {
        r.areas.forEach(function (a) {
          params.append("areas[]", a[0]);
          if (a[1] !== null) params.set("u" + a[0], a[1]);
          if (a[2] !== null) params.set("s" + a[0], a[2]);
        });
        if (inp.ev) params.set("ev", inp.ev);
      }
      var qs = params.toString();
      history.replaceState(null, "", location.pathname + (qs ? "?" + qs : ""));
    };

    form.addEventListener("change", render);
    form.addEventListener("submit", function (e) { e.preventDefault(); render(); });
    render();
  }

  // --- Lector de códigos (codigo.php) ---
  var dec = $("[data-decoder]");
  if (dec) {
    TAX = JSON.parse(dec.getAttribute("data-taxonomy"));
    var input = $("input", dec);
    var out = $("[data-decoder-out]");
    var normalize = function (s) {
      return s.toUpperCase().trim()
        .replace(/^(C[OÓ]DIGO\s+)?ORIDE[\s:]*/, "")
        .replace(/[\s·_—–.,;]+/g, "-")
        .replace(/-+/g, "-")
        .replace(/^-|-$/g, "");
    };
    var explain = function (raw) {
      var code = normalize(raw);
      if (code === "IA0") return { result: compute({ ia: "no" }) };
      var m = code.match(/^IA1-(.+)-V([01])$/);
      if (!m) return { error: "Un código ORIDE es IA0 o tiene la forma IA1-…-V0 / V1, por ejemplo IA1-I2-C43-V1." };
      var last = -1, areas = [], u = {}, s = {};
      var toks = m[1].split("-");
      for (var i = 0; i < toks.length; i++) {
        var tok = toks[i];
        var t = tok.match(/^([A-Z])(\d)(\d?)$/);
        if (!t || ORDER.indexOf(t[1]) < 0) return { error: "«" + tok + "» no es un área válida. Las áreas son D, I, E, C, R, F y T." };
        var l = t[1], us = +t[2], sc = t[3];
        var pos = ORDER.indexOf(l);
        if (pos <= last) return { error: "Las áreas van una sola vez y en este orden: D, I, E, C, R, F, T." };
        last = pos;
        if (us > 5) return { error: "En " + tok + ", la forma de uso va de 0 a 5." };
        if (needsScope(us) && sc === "") return { error: "En " + tok + " falta el alcance: con los usos 3, 4 y 5 se añade una cifra de 0 a 4 (por ejemplo " + l + us + "2)." };
        if (!needsScope(us) && sc !== "") return { error: "En " + tok + " sobra el alcance: solo se indica con los usos 3, 4 y 5." };
        if (sc !== "" && +sc > 4) return { error: "En " + tok + ", el alcance va de 0 a 4." };
        areas.push(l); u[l] = String(us); if (sc !== "") s[l] = sc;
      }
      return { result: compute({ ia: "si", areas: areas, u: u, s: s, ev: m[2] === "1" ? "si" : "no" }) };
    };
    var show = function () {
      var v = input.value.trim();
      if (!v) { out.innerHTML = ""; return; }
      var r = explain(v);
      if (r.error) { out.innerHTML = '<div class="alert alert-warn" role="status">' + esc(r.error) + "</div>"; return; }
      var res = r.result;
      out.innerHTML = '<section class="code-card"><div class="inner"><p class="label">Código ORIDE</p><p class="big code-line">' + segmentsHtml(res) + "</p>"
        + (res.code === "IA0" ? "<p>No se utilizó inteligencia artificial.</p>" : '<div class="dims">' + explainHtml(res) + "</div>") + "</div></section>";
    };
    input.addEventListener("input", show);
    dec.addEventListener("submit", function (e) {
      e.preventDefault();
      show();
      history.replaceState(null, "", location.pathname + (input.value.trim() ? "?c=" + encodeURIComponent(input.value.trim()) : ""));
    });
  }

  // --- Validación al salir del campo (formulario de mejora) ---
  $all("form[data-validate]").forEach(function (f) {
    var check = function (el) {
      var err = document.getElementById(el.id + "-error");
      if (!err) return true;
      var msg = "";
      if (el.validity.valueMissing) msg = el.getAttribute("data-msg-required") || "Rellena este campo.";
      else if (el.validity.typeMismatch) msg = "Escribe un correo válido, por ejemplo nombre@dominio.com.";
      else if (el.validity.tooShort) msg = "Escribe al menos " + el.minLength + " caracteres.";
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
