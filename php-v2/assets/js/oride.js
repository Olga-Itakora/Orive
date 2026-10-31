/* ORIDE v2 — JavaScript del sitio. Sin dependencias, sin cookies y sin almacenamiento local. */
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
  function label(v) { return TAX[v[0]].values[v][0]; }
  function bShort(b) { return TAX.B.values[b][2] || label(b).toLowerCase(); }
  function joinValues(dim, values) {
    var digits = values.map(function (v) { return v.slice(1); }).filter(function (d, i, a) { return a.indexOf(d) === i; }).sort();
    return dim + digits.join("");
  }
  function compute(inp) {
    var r = { status: "empty", code: null, a: [], b: [], pairs: [], missing: null };
    if (inp.ia === "no") return { status: "complete", code: "A0B0C0", a: ["A0"], b: ["B0"], pairs: [["B0", "C0"]], missing: null };
    if (inp.ia !== "si") return r;
    r.status = "pending";
    r.a = ["A1", "A2", "A3", "A4", "A5"].filter(function (v) { return inp.a.indexOf(v) >= 0; });
    if (!r.a.length) { r.missing = "Indica para qué utilizaste la IA."; return r; }
    if (inp.inc === "no") {
      r.b = ["B0"]; r.pairs = [["B0", "C0"]]; r.status = "complete";
      r.code = joinValues("A", r.a) + "B0C0";
      return r;
    }
    if (inp.inc !== "si") { r.missing = "Indica si se incorporó a la obra algo procedente de la IA."; return r; }
    r.b = ["B1", "B2", "B3"].filter(function (v) { return inp.b.indexOf(v) >= 0; });
    if (!r.b.length) { r.missing = "Indica qué se incorporó a la obra."; return r; }
    for (var i = 0; i < r.b.length; i++) {
      var c = inp.c[r.b[i]];
      if (["C1", "C2", "C3", "C4", "C5"].indexOf(c) < 0) { r.missing = "Indica qué hiciste con " + bShort(r.b[i]) + "."; return r; }
      r.pairs.push([r.b[i], c]);
    }
    r.status = "complete";
    r.code = joinValues("A", r.a) + joinValues("B", r.b) + joinValues("C", r.pairs.map(function (p) { return p[1]; }));
    return r;
  }
  function plainText(r) {
    if (r.status !== "complete") return "";
    if (r.code === "A0B0C0") return "Código ORIDE A0B0C0\nNo se utilizó inteligencia artificial en la elaboración de esta obra.";
    var lines = ["Código ORIDE " + r.code, "Función de la IA: " + r.a.map(label).join("; ")];
    if (r.b[0] === "B0") lines.push("No se incorporó a la obra nada procedente de la IA.");
    else r.pairs.forEach(function (p) { lines.push("Incorporado a la obra: " + label(p[0]) + " · Intervención humana: " + label(p[1])); });
    return lines.join("\n");
  }
  function segHtml(dim, value) {
    return '<span class="seg' + (value ? "" : " seg-empty") + '" data-seg="' + dim + '">' + esc(value || dim + "–") + "</span>";
  }
  function dimHtml(dim, body) {
    return '<div class="dim"><span>' + esc(dim + " · " + TAX[dim].name) + "</span>" + body + "</div>";
  }
  function valsHtml(vs) {
    return vs.map(function (v) { return '<p class="val"><b>' + esc(v) + "</b> " + esc(label(v)) + "</p>"; }).join("");
  }

  // --- Formulario «Crea tu declaración» ---
  var form = $("[data-builder]");
  if (form) {
    TAX = JSON.parse(form.getAttribute("data-taxonomy"));
    var box = $("[data-box]");
    var chip = $("[data-chip]");
    var boxVisible = false;
    if (chip && "IntersectionObserver" in window) {
      new IntersectionObserver(function (entries) {
        boxVisible = entries[0].isIntersecting;
        chip.classList.toggle("chip-off", boxVisible);
      }).observe(box);
    }

    var read = function () {
      var fd = new FormData(form);
      var c = {};
      ["B1", "B2", "B3"].forEach(function (b) { if (fd.get("c" + b)) c[b] = fd.get("c" + b); });
      return { ia: fd.get("ia") || "", a: fd.getAll("a[]"), inc: fd.get("inc") || "", b: fd.getAll("b[]"), c: c };
    };

    var applyVisibility = function (inp) {
      $all("[data-when]", form).forEach(function (el) {
        var parts = el.getAttribute("data-when").split("=");
        var key = parts[0], val = parts[1];
        var show = key === "b" ? inp.b.indexOf(val) >= 0 : inp[key] === val;
        el.hidden = !show;
      });
    };

    var render = function () {
      var inp = read();
      applyVisibility(inp);
      var r = compute(inp);
      var codeEl = $("[data-code]", box);
      if (r.status === "complete") {
        var m = r.code.match(/^(A\d+)(B\d+)(C\d+)$/);
        codeEl.innerHTML = segHtml("A", m[1]) + segHtml("B", m[2]) + segHtml("C", m[3]);
      } else {
        codeEl.innerHTML = segHtml("A", r.a.length ? joinValues("A", r.a) : null) + segHtml("B", r.b.length ? joinValues("B", r.b) : null) + segHtml("C", null);
      }
      $("[data-status]", box).textContent = r.status === "complete"
        ? (r.code === "A0B0C0" ? "No se utilizó inteligencia artificial." : "Declaración completa.")
        : (r.missing || "Responde a la primera pregunta para empezar.");

      var html = "";
      if (r.a[0] !== "A0") {
        if (r.a.length) html += dimHtml("A", valsHtml(r.a));
        if (r.b.length) html += dimHtml("B", valsHtml(r.b));
        if (r.pairs.length) {
          html += dimHtml("C", r.pairs.map(function (p) {
            return p[0] === "B0"
              ? '<p class="val"><b>C0</b> ' + esc(label("C0")) + "</p>"
              : '<p class="val"><b>' + esc(p[1]) + "</b> " + esc(label(p[1])) + ' <span class="pair">con ' + esc(bShort(p[0])) + "</span></p>";
          }).join(""));
        }
      }
      $("[data-explain]", box).innerHTML = html;

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
        inp.a.forEach(function (v) { params.append("a[]", v); });
        if (inp.inc) params.set("inc", inp.inc);
        if (inp.inc === "si") {
          inp.b.forEach(function (v) { params.append("b[]", v); });
          inp.b.forEach(function (b) { if (inp.c[b]) params.set("c" + b, inp.c[b]); });
        }
      }
      var qs = params.toString();
      history.replaceState(null, "", location.pathname + (qs ? "?" + qs : ""));
    };

    form.addEventListener("change", render);
    form.addEventListener("submit", function (e) { e.preventDefault(); render(); });
    render();
  }

  // --- Explicador de códigos (codigo.php) ---
  var dec = $("[data-decoder]");
  if (dec) {
    TAX = JSON.parse(dec.getAttribute("data-taxonomy"));
    var input = $("input", dec);
    var out = $("[data-decoder-out]");
    var normalize = function (s) {
      return s.toUpperCase().trim()
        .replace(/^(C[OÓ]DIGO\s+)?ORIDE\s*/, "")
        .replace(/\+([A-D])/g, "")
        .replace(/[\s|·]+/g, "")
        .replace(/D[0-3]$/, "");
    };
    var explain = function (raw) {
      var code = normalize(raw);
      var m = code.match(/^A([0-5]+)B([0-3]+)C([0-5]+)$/);
      if (!m) return { error: "Un código ORIDE tiene la forma A…B…C…, por ejemplo A24B13C15." };
      var parts = {}, dims = { A: m[1], B: m[2], C: m[3] };
      for (var dim in dims) {
        var d = dims[dim].split("");
        var sorted = d.slice().sort().filter(function (x, i, a) { return a.indexOf(x) === i; });
        if (d.join("") !== sorted.join("")) return { error: "En la dimensión " + dim + " las cifras van en orden creciente y sin repetir (por ejemplo " + dim + sorted.join("") + ")." };
        if (d.indexOf("0") >= 0 && d.length > 1) return { error: dim + "0 no puede combinarse con otros valores de " + dim + "." };
        parts[dim] = d.map(function (x) { return dim + x; });
      }
      var a = parts.A.join(), b = parts.B.join(), c = parts.C.join();
      if (a === "A0" && (b !== "B0" || c !== "C0")) return { error: "A0 (no se utilizó IA) solo puede ir con B0 y C0." };
      if ((b === "B0") !== (c === "C0")) return { error: "B0 y C0 van siempre juntos: si no se incorporó nada, no hay intervención que describir." };
      if (b !== "B0" && parts.C.length > parts.B.length) return { error: "Cada tipo de aportación (B) tiene una sola intervención (C): no puede haber más valores C que B." };
      return { code: code, parts: parts };
    };
    var show = function () {
      var v = input.value.trim();
      if (!v) { out.innerHTML = ""; return; }
      var r = explain(v);
      if (r.error) {
        out.innerHTML = '<div class="alert alert-warn" role="status">' + esc(r.error) + "</div>";
        return;
      }
      out.innerHTML = '<section class="code-card"><div class="inner"><p class="label">Código ORIDE</p><p class="big code-line">'
        + segHtml("A", "A" + r.parts.A.map(function (x) { return x[1]; }).join(""))
        + segHtml("B", "B" + r.parts.B.map(function (x) { return x[1]; }).join(""))
        + segHtml("C", "C" + r.parts.C.map(function (x) { return x[1]; }).join(""))
        + '</p><div class="dims">' + ["A", "B", "C"].map(function (d) { return dimHtml(d, valsHtml(r.parts[d])); }).join("") + "</div></div></section>";
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
