/* ORIDE v5 — JavaScript del sitio. Sin dependencias, sin cookies y sin almacenamiento local. */
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

  // --- Lógica del código (idéntica a includes/app.php) ---
  var TAX = I18N;
  function needsScope(l) { return l >= (TAX.scopeFrom || 2); }
  function actPart(act, l, s) {
    if (l === null) return act;
    return act + l + (needsScope(l) ? (s === null ? "" : s) : "0");
  }
  function actName(type, act) { return TAX.types[type].acts[act][0]; }
  function compute(inp) {
    var r = { status: "empty", type: null, ia: null, acts: [], ev: null, code: null, missing: null };
    if (!TAX.types || !TAX.types[inp.type]) return r;
    r.type = inp.type;
    r.status = "pending";
    if (inp.ia !== "si" && inp.ia !== "no") { r.missing = fmt("status_need_ia"); return r; }
    if (inp.ia === "no") { r.status = "complete"; r.ia = 0; r.code = inp.type + "-IA0"; return r; }
    r.ia = 1;
    Object.keys(TAX.types[inp.type].acts).forEach(function (act) {
      if (inp.acts.indexOf(act) < 0) return;
      var l = /^[0-5]$/.test(inp.u[act] || "") ? +inp.u[act] : null;
      var s = l !== null && needsScope(l) && /^[0-4]$/.test(inp.s[act] || "") ? +inp.s[act] : null;
      r.acts.push([act, l, s]);
    });
    r.ev = inp.ev === "si" ? 1 : inp.ev === "no" ? 0 : null;
    for (var i = 0; i < r.acts.length; i++) {
      var a = r.acts[i];
      if (a[1] === null) { r.missing = fmt("miss_level", actName(r.type, a[0])); return r; }
      if (needsScope(a[1]) && a[2] === null) { r.missing = fmt("miss_scope", actName(r.type, a[0])); return r; }
    }
    r.status = "complete";
    r.code = [r.type, "IA1"].concat(r.acts.map(function (a) { return actPart(a[0], a[1], a[2]); }), r.ev === null ? [] : ["V" + r.ev]).join("-");
    return r;
  }
  var READER = false; // instituciones: explicación en impersonal
  function actLines(l, s) {
    var lines = [(READER ? TAX.levelsReader : TAX.levels)[l]];
    if (s !== null) lines.push(fmt("scope_line", TAX.scope[s].toLowerCase()));
    return lines;
  }
  function codeUrl(code) {
    var base = location.href.replace(/[^/]*([?#].*)?$/, "");
    return base + "oride.php?" + code + "&lang=" + LANG;
  }
  function plainText(r, intro) {
    if (r.status !== "complete") return "";
    var lines = intro ? [intro] : [];
    lines.push(fmt("code_label") + " " + r.code);
    lines.push(fmt("type_line", TAX.types[r.type].name));
    lines.push(r.ia ? fmt("ia1_line") : fmt("ia0_line"));
    r.acts.forEach(function (a) { lines.push(actName(r.type, a[0]) + ": " + actLines(a[1], a[2]).join(". ") + "."); });
    if (r.ev !== null) lines.push(fmt("text_evidence", STR.evidence[r.ev].toLowerCase()));
    lines.push(fmt("link_line", codeUrl(r.code)));
    return lines.join("\n");
  }
  function seg(v, empty) { return '<span class="seg' + (empty ? " seg-empty" : "") + '">' + esc(v) + "</span>"; }
  var SEP = '<span class="sep">-</span>';
  function segmentsHtml(r) {
    if (r.status === "empty") return seg(LANG === "en" ? "TYPE–" : "TIPO–", true) + SEP + seg("IA–", true);
    var parts = [seg(r.type)];
    if (r.ia === null) return parts.concat([seg("IA–", true)]).join(SEP);
    parts.push(seg("IA" + r.ia));
    r.acts.forEach(function (a) {
      var done = a[1] !== null && (!needsScope(a[1]) || a[2] !== null);
      parts.push(seg(actPart(a[0], a[1], a[2]) + (done ? "" : "–"), !done));
    });
    if (r.ev !== null) parts.push(seg("V" + r.ev));
    return parts.join(SEP);
  }
  function dim(label, lines, note) {
    return '<div class="dim"><span>' + esc(label) + "</span>" + lines.map(function (t) { return '<p class="val">' + esc(t) + "</p>"; }).join("")
      + (note ? '<p class="val note">' + esc(note) + "</p>" : "") + "</div>";
  }
  function explainHtml(r) {
    if (r.status === "empty") return "";
    var en = LANG === "en";
    var html = dim(r.type + " · " + (en ? "Type of work" : "Tipo de obra"), [TAX.types[r.type].name]);
    if (r.ia === null) return html;
    html += dim("IA" + r.ia + " · " + (en ? "Artificial intelligence" : "Inteligencia artificial"), [r.ia ? fmt("ia1_line") : fmt("ia0_line")]);
    r.acts.forEach(function (a) {
      if (a[1] === null) return;
      html += dim(actPart(a[0], a[1], a[2]) + " · " + actName(r.type, a[0]), actLines(a[1], a[2]));
    });
    if (r.ev !== null) html += dim("V" + r.ev + " · " + fmt("evidence_title"), [STR.evidence[r.ev]], r.ev ? fmt("evidence_note") : "");
    return html;
  }

  // --- Formulario (crear.php e instituciones.php) ---
  var form = $("[data-builder]");
  if (form) {
    var box = $("[data-box]");
    var chip = $("[data-chip]");
    var inst = form.getAttribute("data-mode") === "inst";
    READER = inst;
    if (chip && "IntersectionObserver" in window) {
      new IntersectionObserver(function (entries) { chip.classList.toggle("chip-off", entries[0].isIntersecting); }).observe(box);
    }

    var read = function () {
      var fd = new FormData(form);
      var type = fd.get("type") || "";
      var u = {}, s = {};
      if (TAX.types[type]) {
        Object.keys(TAX.types[type].acts).forEach(function (act) {
          if (fd.get("u_" + type + "_" + act) !== null) u[act] = fd.get("u_" + type + "_" + act);
          if (fd.get("s_" + type + "_" + act) !== null) s[act] = fd.get("s_" + type + "_" + act);
        });
      }
      return { fd: fd, type: type, ia: fd.get("ia") || "", acts: TAX.types[type] ? fd.getAll("acts_" + type + "[]") : [], u: u, s: s, ev: fd.get("ev") || "", limit: fd.get("limit") || "max" };
    };

    // data-when="clave=v1,v2": visible si el campo «clave» tiene alguno de esos valores.
    var applyVisibility = function (fd) {
      $all("[data-when]", form).forEach(function (el) {
        var parts = el.getAttribute("data-when").split("=");
        var vals = parts[1].split(",");
        var current = fd.getAll(parts[0]);
        el.hidden = !vals.some(function (v) { return current.indexOf(v) >= 0; });
      });
    };

    var render = function () {
      var inp = read();
      applyVisibility(inp.fd);
      var r = compute(inp);
      var intro = inst ? fmt(inp.limit === "min" ? "inst_min" : "inst_max") : "";
      var introEl = $("[data-intro]", box);
      if (introEl) introEl.textContent = intro;
      $("[data-code]", box).innerHTML = segmentsHtml(r);
      $("[data-status]", box).textContent = r.status === "complete"
        ? (r.ia === 0 ? fmt("status_no_ai") : fmt("status_complete"))
        : (r.missing || fmt("status_empty"));
      $("[data-explain]", box).innerHTML = explainHtml(r);

      var complete = r.status === "complete";
      var bc = $("[data-copy-code]", box), bt = $("[data-copy-text]", box), bl = $("[data-copy-link]", box);
      bc.disabled = bt.disabled = bl.disabled = !complete;
      bc.setAttribute("data-copy-code", complete ? "ORIDE " + r.code : "");
      bt.setAttribute("data-copy-text", plainText(r, intro));
      bl.setAttribute("data-copy-link", complete ? codeUrl(r.code) : "");

      if (chip) {
        chip.hidden = r.status === "empty";
        $("[data-chip-code]", chip).textContent = complete ? r.code : fmt("pending");
      }

      // La URL refleja las respuestas: se puede recargar o compartir sin guardar nada.
      var params = new URLSearchParams();
      params.set("lang", LANG);
      if (inst) params.set("limit", inp.limit);
      if (inp.type) params.set("type", inp.type);
      if (inp.ia) params.set("ia", inp.ia);
      if (inp.ia === "si") {
        if (inp.ev) params.set("ev", inp.ev);
        r.acts.forEach(function (a) {
          params.append("acts_" + r.type + "[]", a[0]);
          if (a[1] !== null) params.set("u_" + r.type + "_" + a[0], a[1]);
          if (a[2] !== null) params.set("s_" + r.type + "_" + a[0], a[2]);
        });
      }
      history.replaceState(null, "", location.pathname + "?" + params.toString());
      updateSwitch(params);
    };

    form.addEventListener("change", render);
    form.addEventListener("submit", function (e) { e.preventDefault(); render(); });
    render();
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
