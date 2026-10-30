<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/author.php';
require_once __DIR__ . '/../includes/wizard.php';
require_once __DIR__ . '/../includes/evidence.php';

$user = require_login();
$uid = (int) $user['id'];
$vid = (int) (get('id') ?: post('id'));
$step = get('p') ?: post('p');
if (!isset(WIZ_STEPS[$step])) not_found();
$f = wiz_load($user, $vid);
$v = $f['v'];
$eff = effective_usage($v);
if (in_array($step, ['herramientas', 'usos'], true) && $eff !== 'YES') redirect(wiz_url($vid, 'uso'));
$here = wiz_url($vid, $step);
q('UPDATE declaration_versions SET last_step = ? WHERE id = ?', [$step, $vid]);

// ---------------------------------------------------------------------------
// Guardado
// ---------------------------------------------------------------------------
if (is_post()) {
    csrf_check();
    $action = post('action');
    $go = function (string $nextStep) use ($vid, $here) {
        if (post('intent') === 'stay') {
            flash('ok', 'Borrador guardado.');
            redirect($here);
        }
        redirect(wiz_url($vid, $nextStep));
    };

    if ($step === 'declarante') {
        $errors = [];
        $name = mb_substr(post('declarant_name'), 0, 160);
        $role = mb_substr(post('declarant_role'), 0, 120);
        $scope = post('scope');
        if ($name === '') $errors['declarant_name'] = 'Indica tu nombre.';
        if ($role === '') $errors['declarant_role'] = 'Indica tu rol en la obra.';
        if (!isset(L_SCOPE[$scope])) $errors['scope'] = 'Indica qué cubre tu declaración.';
        $valid = array_map(fn($c) => (int) $c['id'], $f['components']);
        $ids = array_values(array_intersect(array_map('intval', post_list('scope_component_ids')), $valid));
        if ($scope === 'COMPONENTS' && !$ids) $errors['scope_component_ids'] = 'Selecciona al menos un componente.';
        if ($errors) form_fail($errors, $here, 'Revisa los campos marcados.');
        q('UPDATE declaration_versions SET declarant_name=?, declarant_artistic_name=?, declarant_organization=?, declarant_role=?, scope=?, scope_component_ids=?, responsibility_note=?, updated_at=? WHERE id=?',
            [$name, nullable(post('declarant_artistic_name'), 160), nullable(post('declarant_organization'), 200), $role, $scope, json_encode($ids), nullable(post('responsibility_note'), 2000), now(), $vid]);
        $go('uso');
    }

    if ($step === 'uso') {
        $usage = post('ai_usage');
        $own = post('own_ai_usage');
        if (!isset(L_AI_USAGE[$usage])) form_fail(['ai_usage' => 'Selecciona una opción.'], $here);
        if ($usage === 'OWN_CONTRIBUTION_ONLY' && !in_array($own, ['NO', 'YES', 'UNCERTAIN'], true)) form_fail(['own_ai_usage' => 'Indica si se utilizó IA en tu contribución.'], $here);
        $effective = $usage === 'OWN_CONTRIBUTION_ONLY' ? $own : $usage;
        if ($effective === 'NO' && $f['uses']) {
            form_fail([], $here, 'Este borrador tiene ' . count($f['uses']) . ' uso(s) de IA registrados, incompatibles con «No se utilizó IA». Elimínalos en el paso «Usos de IA» o elige otra respuesta.');
        }
        q('UPDATE declaration_versions SET ai_usage=?, own_ai_usage=?, uncertainty_note=?, process_narrative=?, scope = IF(? = "OWN_CONTRIBUTION_ONLY", "OWN_CONTRIBUTION", scope), updated_at=? WHERE id=?',
            [$usage, $usage === 'OWN_CONTRIBUTION_ONLY' ? $own : null, nullable(post('uncertainty_note'), 2000), nullable(post('process_narrative'), 6000), $usage, now(), $vid]);
        $go($effective === 'YES' ? 'herramientas' : 'evidencias');
    }

    if ($step === 'herramientas') {
        if ($action === 'delete_tool') {
            q('DELETE FROM ai_tools WHERE id = ? AND version_id = ?', [(int) post('tool_id'), $vid]);
            flash('ok', 'Herramienta eliminada.');
            redirect($here);
        }
        $identified = post('identified') !== 'no';
        $name = mb_substr(post('name'), 0, 160);
        if ($identified && $name === '') form_fail(['name' => 'Indica el nombre de la herramienta, o márcala como desconocida.'], $here);
        $data = [$identified ? 1 : 0, $name ?: 'Herramienta no identificada', nullable(post('provider'), 160), nullable(post('model_version'), 160), nullable(post('system_type'), 120), nullable(post('purpose'), 1000), nullable(post('usage_period'), 120)];
        $tid = (int) post('tool_id');
        if ($tid) {
            q('UPDATE ai_tools SET identified=?, name=?, provider=?, model_version=?, system_type=?, purpose=?, usage_period=? WHERE id=? AND version_id=?', array_merge($data, [$tid, $vid]));
        } else {
            if (count($f['tools']) >= 50) form_fail([], $here, 'Máximo 50 herramientas.');
            q('INSERT INTO ai_tools (identified, name, provider, model_version, system_type, purpose, usage_period, version_id, sort_order) VALUES (?,?,?,?,?,?,?,?,?)', array_merge($data, [$vid, count($f['tools'])]));
        }
        flash('ok', 'Herramienta guardada.');
        redirect($here);
    }

    if ($step === 'usos' && $action === 'delete_use') {
        q('DELETE FROM ai_uses WHERE id = ? AND version_id = ?', [(int) post('use_id'), $vid]);
        flash('ok', 'Uso eliminado.');
        redirect($here);
    }

    if ($step === 'evidencias') {
        if ($action === 'delete_evidence') {
            // El fichero no se borra: puede estar referenciado por versiones emitidas.
            q('DELETE FROM evidence WHERE id = ? AND version_id = ?', [(int) post('evidence_id'), $vid]);
            flash('ok', 'Evidencia eliminada del borrador.');
            redirect($here);
        }
        if (!rate_limit('evidence', (string) $uid, 30, 3600)) form_fail([], $here, 'Se ha alcanzado el límite de 30 evidencias por hora. Lo ya registrado está guardado.');
        $kind = post('kind');
        $desc = mb_substr(post('description'), 0, 1000);
        $errors = [];
        if (!isset(L_EVIDENCE_KIND[$kind])) $errors['kind'] = 'Selecciona el tipo de evidencia.';
        if ($desc === '') $errors['ev-description'] = 'Describe brevemente la evidencia.';
        if (count($f['evidence']) >= 30) form_fail([], $here, 'Máximo 30 evidencias por declaración.');
        $file = ['file_name' => null, 'mime_type' => null, 'size_bytes' => null, 'sha256' => null];
        if (!$errors && isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $stored = store_evidence($_FILES['file']);
            if (is_string($stored)) $errors['ev-file'] = $stored;
            else $file = $stored;
        }
        if ($errors) form_fail($errors, $here, 'Revisa los campos marcados.');
        $useIds = array_map(fn($u) => (int) $u['id'], $f['uses']);
        $useId = in_array((int) post('use_id'), $useIds, true) ? (int) post('use_id') : null;
        q('INSERT INTO evidence (version_id, use_id, kind, description, file_name, mime_type, size_bytes, sha256, created_at) VALUES (?,?,?,?,?,?,?,?,?)', [$vid, $useId, $kind, $desc, $file['file_name'], $file['mime_type'], $file['size_bytes'], $file['sha256'], now()]);
        flash('ok', 'Evidencia registrada.');
        redirect($here);
    }

    if ($step === 'art50') {
        $answers = oride_article50_answers(array_map(fn($k) => post($k) ?: null, array_combine(ORIDE_A50_FIELDS, ORIDE_A50_FIELDS)));
        $answers['organizationName'] = mb_substr(post('organizationName'), 0, 200);
        q('UPDATE declaration_versions SET article50_answers = ?, updated_at = ? WHERE id = ?', [json_encode($answers, JSON_UNESCAPED_UNICODE), now(), $vid]);
        if (post('intent') === 'stay') {
            flash('ok', 'Respuestas guardadas. La orientación se ha actualizado.');
            redirect($here);
        }
        redirect(wiz_url($vid, 'revision'));
    }

    if ($step === 'revision') {
        if ($action === 'observations') {
            q('UPDATE declaration_versions SET observations = ?, updated_at = ? WHERE id = ?', [nullable(post('observations'), 4000), now(), $vid]);
            flash('ok', 'Observaciones guardadas.');
            redirect($here);
        }
        if ($action === 'discard') {
            $wid = delete_draft($uid, $vid);
            flash('ok', 'Borrador descartado.');
            redirect('autor/obra.php?id=' . $wid);
        }
        if ($action === 'issue') {
            if (!rate_limit('issue', (string) $uid, 20, 3600)) form_fail([], $here, 'Demasiadas emisiones en poco tiempo. Vuelve a intentarlo más tarde.');
            if (post('accept') !== 'on') form_fail(['accept' => 'Para emitir la declaración, acepta expresamente el texto de la declaración responsable.'], $here);
            try {
                $publicId = issue_version($uid, $vid, true, post('statement_hash'));
            } catch (DomainError $e) {
                form_fail([], $here, $e->getMessage());
            }
            flash('ok', "Declaración emitida con el identificador $publicId. Es privada: decide si quieres publicarla en la pestaña «Publicación y distintivo».");
            redirect('autor/declaracion.php?id=' . $vid);
        }
    }
    redirect($here);
}

// ---------------------------------------------------------------------------
// Presentación
// ---------------------------------------------------------------------------
[$errors, $old] = form_state();
$val = fn(string $k) => (string) ($old[$k] ?? $v[$k] ?? '');
$copy = [
    'obra' => ['Identificación de la obra', 'Revisa los datos de la obra. Se congelarán en la declaración en el momento de emitirla.'],
    'declarante' => ['Declarante y alcance', 'Quién declara, con qué rol y qué parte de la obra cubre la declaración.'],
    'uso' => ['Utilización general de IA', 'Responde con lo que sabes. Si no puedes determinar algo, dilo: es preferible registrar la incertidumbre que inventar datos.'],
    'herramientas' => ['Herramientas de IA', 'Registra los sistemas utilizados. No necesitas recordar el modelo exacto; también puedes registrar herramientas desconocidas.'],
    'usos' => ['Usos de IA', 'Registra cada uso concreto por separado: una revisión crítica, una traducción, la generación de una ilustración… Cada uso recibe su propia clasificación ORIDE.'],
    'evidencias' => ['Evidencias complementarias (opcional)', 'Versiones, historiales o materiales de trabajo que respaldan tu descripción. Son privadas por defecto y ORIDE no las audita.'],
    'art50' => ['Orientación sobre el artículo 50', 'Preguntas orientativas para reconocer si podría existir una obligación específica de transparencia según el Reglamento (UE) 2024/1689. No afecta a tu código ORIDE ni constituye asesoramiento jurídico.'],
    'revision' => ['Revisión y emisión', 'Comprueba toda la información. Puedes volver a cualquier paso para corregirla. Una vez emitida, esta versión será inmutable.'],
][$step];
$formStart = fn(string $extra = '', string $enctype = '') => '<form method="post" action="' . u('autor/paso.php') . '"' . $enctype . $extra . '>' . csrf_field() . '<input type="hidden" name="id" value="' . $vid . '"><input type="hidden" name="p" value="' . $step . '">';

page_start($copy[0], ['area' => 'author']);
wiz_header($f, $step, $copy[0], $copy[1]);
echo '<div class="container">';

if ($step === 'obra') {
    $w = $old ? $old + $f['work'] : $f['work'];
    echo '<div class="grid-main-aside"><form class="card" method="post" action="' . u('autor/obra.php') . '" data-draft="obra-' . (int) $f['work']['id'] . '">' . csrf_field()
        . '<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="' . (int) $f['work']['id'] . '"><input type="hidden" name="next" value="' . h(wiz_url($vid, 'declarante')) . '">'
        . work_form($w, $errors, 'Guardar y continuar', '') . '</form>';
    echo '<section class="card"><div class="card-head"><h2>Componentes de la obra</h2></div>';
    if (!$f['components']) echo '<p class="muted small">Sin componentes. Si tu obra combina partes con procesos distintos (texto, imágenes, audio…), añádelos para describir cada una.</p>';
    else echo '<ul class="list-plain small">' . implode('', array_map(fn($c) => '<li><span><strong>' . h($c['name']) . '</strong> <span class="muted">· ' . h(L_COMPONENT_KIND[$c['kind']]) . '</span></span></li>', $f['components'])) . '</ul>';
    echo '<p><a class="link-44" href="' . u('autor/obra.php?id=' . $f['work']['id']) . '">Gestionar componentes y participantes</a></p></section></div>';
}

if ($step === 'declarante') {
    $scopeIds = $old ? array_map('intval', (array) ($old['scope_component_ids'] ?? [])) : array_map('intval', jarr($v['scope_component_ids']));
    echo $formStart(' class="card narrow" data-draft="paso-' . $vid . '-declarante"');
    echo '<div class="form-grid">'
        . field('declarant_name', 'Nombre', ['required' => true, 'max' => 160, 'value' => $val('declarant_name'), 'error' => $errors['declarant_name'] ?? null, 'hint' => 'Tu nombre profesional. En la publicación podrás decidir si se muestra.', 'attrs' => ' autocomplete="name"'])
        . field('declarant_artistic_name', 'Nombre artístico', ['optional' => true, 'max' => 160, 'value' => $val('declarant_artistic_name')])
        . field('email_view', 'Correo electrónico (privado)', ['value' => $user['email'], 'attrs' => ' disabled', 'hint' => 'Procede de tu cuenta. Nunca se incluye en la declaración ni se publica.'])
        . field('declarant_organization', 'Institución u organización', ['optional' => true, 'max' => 200, 'value' => $val('declarant_organization')])
        . field('declarant_role', 'Tu rol en la obra', ['required' => true, 'max' => 120, 'value' => $val('declarant_role'), 'error' => $errors['declarant_role'] ?? null, 'hint' => 'Por ejemplo: autor/a, ilustrador/a, traductor/a.'])
        . '</div>';
    echo choices('scope', '¿Qué cubre esta declaración?', [
        'WHOLE_WORK' => ['La obra completa', 'Conozco el proceso de toda la obra y asumo la responsabilidad de describirlo.'],
        'COMPONENTS' => ['Componentes concretos', $f['components'] ? 'Por ejemplo, solo el texto de un libro ilustrado.' : 'Añade primero componentes en la ficha de la obra.'],
        'OWN_CONTRIBUTION' => ['Solo mi contribución', 'Declaro únicamente sobre lo que yo hice dentro de una obra con más participantes.'],
    ], ['value' => $val('scope'), 'required' => true, 'error' => $errors['scope'] ?? null, 'hint' => ($f['work']['is_collective'] || $f['contributors']) ? 'En una obra colectiva, declara únicamente lo que conoces. Tu declaración no se atribuirá a los demás participantes.' : null]);
    if ($f['components']) {
        echo '<div data-show-if="scope=COMPONENTS,OWN_CONTRIBUTION">' . choices('scope_component_ids', 'Componentes cubiertos', array_column($f['components'], 'name', 'id'), ['type' => 'checkbox', 'value' => $scopeIds, 'error' => $errors['scope_component_ids'] ?? null, 'inline' => true]) . '</div>';
    }
    echo field('responsibility_note', 'Ámbito de responsabilidad', ['type' => 'textarea', 'optional' => true, 'max' => 2000, 'value' => $val('responsibility_note'), 'hint' => 'Precisa, si lo necesitas, qué decisiones creativas y editoriales finales asumes y cuáles no.']);
    echo wiz_nav($vid, $step, $eff) . '</form>';
}

if ($step === 'uso') {
    echo $formStart(' class="card narrow" data-draft="paso-' . $vid . '-uso"');
    echo choices('ai_usage', '¿Se han utilizado sistemas de inteligencia artificial durante la elaboración de esta obra o de la parte que estás declarando?', [
        'NO' => ['No', 'No se utilizaron sistemas de IA en el ámbito que declaro.'],
        'YES' => ['Sí', 'Después describirás cada uso concreto.'],
        'UNCERTAIN' => ['No puedo determinarlo con certeza', 'Por ejemplo, usaste programas que pueden incorporar funciones de IA sin saber si intervinieron.'],
        'OWN_CONTRIBUTION_ONLY' => ['Solo puedo declarar sobre mi contribución', 'No conozco el proceso de las demás partes de la obra.'],
    ], ['value' => $val('ai_usage'), 'required' => true, 'error' => $errors['ai_usage'] ?? null]);
    echo '<div data-show-if="ai_usage=OWN_CONTRIBUTION_ONLY">' . choices('own_ai_usage', 'En tu contribución, ¿se utilizaron sistemas de IA?', ['NO' => 'No', 'YES' => 'Sí', 'UNCERTAIN' => 'No puedo determinarlo con certeza'], ['value' => $val('own_ai_usage'), 'error' => $errors['own_ai_usage'] ?? null, 'inline' => true]) . '</div>';
    echo field('uncertainty_note', 'Incertidumbres o limitaciones', ['type' => 'textarea', 'optional' => true, 'max' => 2000, 'value' => $val('uncertainty_note'), 'hint' => 'Explica lo que no puedes precisar. Por ejemplo: «Usé un editor de fotos que puede aplicar ajustes automáticos con IA; no sé si intervinieron». No se inventará ningún dato.']);
    echo '<div data-show-if="ai_usage=NO,UNCERTAIN,OWN_CONTRIBUTION_ONLY">' . field('process_narrative', 'Describe tu proceso creativo', ['type' => 'textarea', 'optional' => true, 'rows' => 5, 'max' => 6000, 'value' => $val('process_narrative'), 'hint' => 'Opcional. Una descripción de al menos 100 caracteres documenta el proceso (dimensión D2). No incluyas datos personales de terceros ni información confidencial.']) . '</div>';
    echo wiz_nav($vid, $step, $eff, 'Guardar') . '</form>';
}

if ($step === 'herramientas') {
    $toolForm = function (?array $t) use ($formStart, $errors, $vid) {
        $p = $t ? 't' . $t['id'] : 'new';
        $html = $formStart(' class="stack"') . ($t ? '<input type="hidden" name="tool_id" value="' . (int) $t['id'] . '">' : '');
        $html .= choices('identified', '¿Sabes qué herramienta era?', ['yes' => 'Sí, conozco su nombre', 'no' => 'No lo recuerdo / no lo sé'], ['value' => $t ? ($t['identified'] ? 'yes' : 'no') : 'yes', 'inline' => true]);
        $html .= '<div class="form-grid">'
            . field('name', 'Nombre o descripción de la herramienta', ['id' => "$p-name", 'max' => 160, 'value' => $t ? ($t['identified'] ? $t['name'] : '') : '', 'error' => !$t ? ($errors['name'] ?? null) : null, 'hint' => 'Por ejemplo: ChatGPT, Claude, Midjourney, DeepL… Si no la recuerdas, una descripción aproximada: «un traductor en línea».'])
            . field('provider', 'Proveedor', ['id' => "$p-provider", 'optional' => true, 'max' => 160, 'value' => $t['provider'] ?? ''])
            . field('model_version', 'Modelo o versión', ['id' => "$p-model", 'optional' => true, 'max' => 160, 'value' => $t['model_version'] ?? '', 'hint' => 'Solo si lo conoces.'])
            . field('system_type', 'Tipo de sistema', ['id' => "$p-type", 'type' => 'select', 'optional' => true, 'options' => ['' => '—'] + array_combine(L_SYSTEM_TYPES, L_SYSTEM_TYPES), 'value' => $t['system_type'] ?? ''])
            . field('purpose', 'Finalidad', ['id' => "$p-purpose", 'optional' => true, 'max' => 1000, 'value' => $t['purpose'] ?? '', 'hint' => 'Para qué la utilizaste, en general.'])
            . field('usage_period', 'Periodo aproximado de utilización', ['id' => "$p-period", 'optional' => true, 'max' => 120, 'value' => $t['usage_period'] ?? '', 'placeholder' => 'marzo–junio de 2026'])
            . '</div><button class="btn ' . ($t ? 'btn-primary' : 'btn-secondary') . '">' . ($t ? 'Guardar herramienta' : 'Añadir herramienta') . '</button></form>';
        return $html;
    };
    if ($f['tools']) {
        echo '<section class="card"><div class="card-head"><h2>Herramientas registradas</h2></div><ul class="list-plain">';
        foreach ($f['tools'] as $t) {
            $info = implode(' · ', array_filter([$t['provider'], $t['model_version'], $t['system_type'], $t['usage_period']])) ?: 'Sin más datos';
            echo '<li><div class="grow"><strong>' . ($t['identified'] ? h($t['name']) : '<em>Herramienta no identificada</em>') . '</strong><br><span class="meta">' . h($info) . '</span>'
                . '<details class="edit"><summary>Editar herramienta</summary>' . $toolForm($t) . '</details></div>'
                . confirm_button(wiz_url($vid, 'herramientas'), ['action' => 'delete_tool', 'tool_id' => $t['id'], 'id' => $vid, 'p' => 'herramientas'], "Eliminar herramienta «{$t['name']}»", "¿Eliminar la herramienta «{$t['name']}»?", 'Se quitará de este borrador. Los usos que la tenían asociada quedarán sin herramienta.', 'Eliminar herramienta', true) . '</li>';
        }
        echo '</ul></section>';
    }
    echo '<section class="card"><div class="card-head"><h2>' . ($f['tools'] ? 'Añadir otra herramienta' : 'Añadir herramienta') . '</h2></div>' . $toolForm(null) . '</section>';
    echo '<div class="actions spaced"><a class="link-44" href="' . u(wiz_url($vid, 'uso')) . '">← Paso anterior</a><a class="btn btn-primary" href="' . u(wiz_url($vid, 'usos')) . '">Continuar con los usos</a></div>';
}

if ($step === 'usos') {
    $profile = classify_full($f);
    if (!$f['tools']) echo alert('Aún no has registrado herramientas. <a href="' . u(wiz_url($vid, 'herramientas')) . '">Regístralas primero</a> (puedes registrar una herramienta como «desconocida»).', 'warn', null, true);
    if (!$f['uses']) {
        echo '<div class="table-frame"><div class="empty"><h2>Aquí aparecerán los usos de IA de tu obra</h2><p class="muted small">Cada uso recibe su propia clasificación ORIDE. Empieza por el más relevante; podrás añadir tantos como necesites.</p><a class="btn btn-primary" href="' . u('autor/uso.php?id=' . $vid) . '">Añadir el primer uso</a></div></div>';
    } else {
        echo '<div class="table-frame"><ul class="list-plain padded">';
        foreach ($f['uses'] as $u) {
            $p = null;
            foreach ($profile['uses'] as $x) if ((int) $x['ref'] === (int) $u['id']) $p = $x;
            $comp = 'Obra en general';
            foreach ($f['components'] as $c) if ((int) $c['id'] === (int) $u['component_id']) $comp = $c['name'];
            echo '<li><span><strong>' . ($u['parent_use_id'] ? '↳ ' : '') . h($u['title']) . '</strong><br><span class="meta">' . h($comp) . ' · ' . h($u['tool'] ? ($u['tool']['identified'] ? $u['tool']['name'] : 'Herramienta no identificada') : 'Sin herramienta') . '</span></span>'
                . '<span class="badges"><span class="mono">' . h($p['code'] ?? $p['notation']) . '</span>' . ($p['status'] !== 'COMPLETE' ? badge(L_PROFILE_STATUS[$p['status']], 'warn') : '')
                . '<span class="tip-wrap"><a class="icon-btn" href="' . u('autor/uso.php?id=' . $vid . '&uso=' . $u['id']) . '" aria-label="' . h("Editar uso «{$u['title']}»") . '"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></a><span class="tip" role="tooltip">' . h("Editar uso «{$u['title']}»") . '</span></span>'
                . confirm_button(wiz_url($vid, 'usos'), ['action' => 'delete_use', 'use_id' => $u['id'], 'id' => $vid, 'p' => 'usos'], "Eliminar uso «{$u['title']}»", "¿Eliminar el uso «{$u['title']}»?", 'Se eliminarán sus fases y aportaciones en este borrador. Esta acción no se puede deshacer.', 'Eliminar uso', true)
                . '</span></li>';
        }
        echo '</ul></div>';
        if (count($f['uses']) > 1 && $profile['composite_notation']) echo '<p class="small muted">Perfil compuesto provisional: <span class="mono">' . h($profile['code'] ?? $profile['composite_notation']) . '</span></p>';
    }
    echo '<div class="actions spaced"><a class="link-44" href="' . u(wiz_url($vid, 'herramientas')) . '">← Paso anterior</a><a class="btn btn-secondary" href="' . u('autor/uso.php?id=' . $vid) . '">Añadir uso</a><a class="btn btn-primary" href="' . u(wiz_url($vid, 'evidencias')) . '">Continuar</a></div>';
}

if ($step === 'evidencias') {
    if ($f['evidence']) {
        echo '<section class="card"><div class="card-head"><h2>Evidencias registradas</h2></div><ul class="list-plain">';
        foreach ($f['evidence'] as $e) {
            $useTitle = 'Toda la declaración';
            foreach ($f['uses'] as $u) if ((int) $u['id'] === (int) $e['use_id']) $useTitle = 'Uso: ' . $u['title'];
            $label = $e['file_name'] ?? L_EVIDENCE_KIND[$e['kind']];
            echo '<li><span class="small"><strong>' . h(L_EVIDENCE_KIND[$e['kind']]) . '</strong> — ' . h($e['description']) . '<br>'
                . ($e['file_name'] ? '<a href="' . u('autor/descargar.php?id=' . $vid . '&evidencia=' . $e['id']) . '">' . h($e['file_name']) . '</a> · ' . (int) ceil($e['size_bytes'] / 1024) . ' KB · <span class="mono meta">SHA-256 ' . h(substr($e['sha256'], 0, 16)) . '…</span><br>' : '')
                . '<span class="meta">' . h($useTitle) . '</span></span>'
                . confirm_button(wiz_url($vid, 'evidencias'), ['action' => 'delete_evidence', 'evidence_id' => $e['id'], 'id' => $vid, 'p' => 'evidencias'], "Eliminar evidencia «{$label}»", "¿Eliminar la evidencia «{$label}»?", 'Se quitará de este borrador. Las declaraciones ya emitidas que la incluyen no cambian.', 'Eliminar evidencia', true) . '</li>';
        }
        echo '</ul></section>';
    }
    echo '<section class="card"><div class="card-head"><h2>Registrar evidencia</h2></div>';
    echo alert('No subas prompts o conversaciones que contengan datos personales de terceros o información confidencial si no es necesario. Las evidencias nunca se publican sin tu consentimiento específico, y aun así solo se publican sus metadatos.', 'info');
    echo $formStart('', ' enctype="multipart/form-data"') . '<div class="form-grid">'
        . field('kind', 'Tipo de evidencia', ['type' => 'select', 'required' => true, 'options' => L_EVIDENCE_KIND, 'value' => $old['kind'] ?? 'VERSION_HISTORY', 'error' => $errors['kind'] ?? null])
        . field('use_id', 'Relacionada con', ['type' => 'select', 'optional' => true, 'options' => ['' => 'Toda la declaración'] + array_column($f['uses'], 'title', 'id'), 'value' => $old['use_id'] ?? ''])
        . field('description', 'Descripción', ['id' => 'ev-description', 'type' => 'textarea', 'required' => true, 'max' => 1000, 'rows' => 2, 'value' => $old['description'] ?? '', 'error' => $errors['ev-description'] ?? null, 'class' => 'span-2', 'hint' => 'Qué contiene y qué muestra.'])
        . field('file', 'Fichero', ['id' => 'ev-file', 'type' => 'file', 'optional' => true, 'error' => $errors['ev-file'] ?? null, 'class' => 'span-2', 'attrs' => ' accept=".pdf,.png,.jpg,.jpeg,.webp,.txt,.md,.csv,.json"', 'hint' => 'PDF, PNG, JPEG, WebP o texto. Máximo 10 MB. Se guarda de forma privada y se registra su huella SHA-256.'])
        . '</div><button class="btn btn-secondary">Registrar evidencia</button></form></section>';
    $prev = wiz_prev('evidencias', $eff);
    echo '<div class="actions spaced"><a class="link-44" href="' . u(wiz_url($vid, $prev)) . '">← Paso anterior</a><a class="btn btn-primary" href="' . u(wiz_url($vid, 'art50')) . '">Continuar</a></div>';
}

if ($step === 'art50') {
    $a = oride_article50_answers(jarr($v['article50_answers']));
    $res = oride_article50($a);
    $tri = ['YES' => 'Sí', 'NO' => 'No', 'UNSURE' => 'No lo sé'];
    $hasText = in_array($f['work']['category_key'], ['essay', 'academic', 'outreach', 'novel', 'short_story', 'poetry', 'screenplay', 'other', 'multimedia'], true) || in_array('TEXT', array_column($f['components'], 'kind'), true);
    echo '<div class="grid-main-aside">' . $formStart(' class="card" data-draft="paso-' . $vid . '-art50"');
    echo choices('role', '¿Cuál es tu papel respecto de los sistemas de IA utilizados?', ['OWN_AUTHORITY' => 'Los utilicé por mi cuenta, bajo mi propia autoridad', 'FOR_ORGANIZATION' => 'Los utilicé por cuenta de una organización (editorial, empresa, institución…)', 'PROVIDER' => 'Soy proveedor de un sistema de IA (lo desarrollo o comercializo)', 'UNSURE' => 'No lo sé'], ['value' => $a['role'], 'hint' => 'Quien utiliza un sistema de IA bajo su propia autoridad puede ser «responsable del despliegue» a efectos del Reglamento.']);
    echo '<div data-show-if="role=FOR_ORGANIZATION">' . field('organizationName', 'Organización bajo cuya autoridad se utilizó', ['optional' => true, 'max' => 200, 'value' => $a['organizationName']]) . '</div>';
    echo choices('context', '¿En qué contexto se realizó la actividad?', ['PROFESSIONAL' => ['Profesional', 'Incluye publicación comercial, encargos, docencia o investigación.'], 'PERSONAL_NON_PROFESSIONAL' => 'Personal no profesional', 'UNSURE' => 'No lo sé'], ['value' => $a['context'], 'inline' => true]);
    echo choices('syntheticMedia', '¿El contenido publicado incluye imágenes, vídeo o audio generados o manipulados con IA?', $tri, ['value' => $a['syntheticMedia'], 'inline' => true]);
    echo '<div data-show-if="syntheticMedia=YES">' . choices('realisticResemblance', '¿Ese contenido se asemeja a personas, objetos, lugares, entidades o sucesos reales y podría hacer pensar erróneamente que es auténtico?', $tri, ['value' => $a['realisticResemblance'], 'inline' => true, 'hint' => 'No toda imagen generada con IA es una ultrasuplantación (deep fake). Una ilustración claramente fantástica normalmente no lo es.'])
        . choices('evidentlyCreative', '¿Forma parte de una obra manifiestamente artística, creativa, satírica o de ficción?', $tri, ['value' => $a['evidentlyCreative'], 'inline' => true]) . '</div>';
    if ($hasText) {
        echo choices('publicInterestText', '¿Se publica texto generado o manipulado con IA con el fin de informar al público sobre asuntos de interés público?', $tri, ['value' => $a['publicInterestText'], 'inline' => true, 'hint' => 'Por ejemplo, artículos periodísticos o informativos. Una novela o un poema normalmente no lo son.']);
        echo '<div data-show-if="publicInterestText=YES">' . choices('humanReview', '¿Ese texto ha sido sometido a revisión humana o control editorial?', $tri, ['value' => $a['humanReview'], 'inline' => true])
            . choices('editorialResponsibility', '¿Una persona física o jurídica asume la responsabilidad editorial de la publicación?', $tri, ['value' => $a['editorialResponsibility'], 'inline' => true]) . '</div>';
    }
    echo wiz_nav($vid, $step, $eff, 'Guardar y ver orientación') . '</form>';
    echo '<section class="card"><div class="card-head"><h2>Orientación con tus respuestas guardadas</h2></div>' . ($res['answered'] ? a50_findings(json_decode(json_encode($res))) : '<p class="muted small">Responde a las preguntas y guarda para ver la orientación. Este paso es opcional.</p>') . '</section></div>';
}

if ($step === 'revision') {
    $r = readiness($f);
    $st = active_statement();
    [$preview] = build_snapshot($f, $r['profile'], ['public_id' => 'ORD-····-····-····', 'issued_at' => gmdate('Y-m-d\TH:i:s\Z'), 'verify_url' => '', 'statement' => $st]);
    if (!$r['can_issue']) {
        $items = '';
        foreach ($r['completeness'] as [$s, $m]) $items .= '<li>' . h($m) . ' <a href="' . u(wiz_url($vid, $s)) . '">Corregir</a></li>';
        foreach ($r['validation']['errors'] as $e) {
            $useTitle = '';
            $useId = null;
            foreach ($r['profile']['uses'] as $pu) if (in_array($e, $pu['issues'], true)) $useId = $pu['ref'];
            foreach ($f['uses'] as $u) if ((int) $u['id'] === (int) $useId) $useTitle = '<strong>' . h($u['title']) . ':</strong> ';
            $items .= '<li>' . $useTitle . h($e['message']) . ' <a href="' . u($useId ? 'autor/uso.php?id=' . $vid . '&uso=' . $useId : wiz_url($vid, $e['field'] === 'ai_usage' ? 'uso' : 'usos')) . '">Corregir</a></li>';
        }
        echo alert('<ul>' . $items . '</ul>', 'warn', 'Falta información para emitir la declaración', true);
    }
    if ($r['validation']['warnings']) {
        echo alert('<ul>' . implode('', array_map(fn($w) => '<li>' . h($w['message']) . '</li>', $r['validation']['warnings'])) . '</ul>', 'info', 'Avisos de coherencia (no impiden emitir)', true);
    }
    echo '<div class="review">' . render_snapshot(snapshot_sections($preview)) . '</div>';
    echo $formStart(' class="card" data-draft="paso-' . $vid . '-obs"') . '<input type="hidden" name="action" value="observations">'
        . field('observations', 'Observaciones relevantes', ['type' => 'textarea', 'optional' => true, 'max' => 4000, 'value' => $v['observations'] ?? '', 'hint' => 'Aparecerán en la declaración completa.'])
        . '<button class="btn btn-secondary">Guardar observaciones</button></form>';
    echo '<section class="card spaced"><div class="card-head"><h2>Declaración responsable</h2></div>' . $formStart() . '<input type="hidden" name="action" value="issue"><input type="hidden" name="statement_hash" value="' . h($st['content_hash']) . '">'
        . '<blockquote class="statement">' . h($st['content']) . '</blockquote><p class="meta">Texto de la declaración responsable, versión ' . h($st['version']) . '. Se registrará la versión exacta que aceptas.</p>'
        . '<fieldset><label class="check bordered"><input type="checkbox" name="accept" id="f-accept" aria-describedby="f-accept-error"><span>He leído y acepto expresamente la declaración responsable anterior. Entiendo que, una vez emitida, esta versión no podrá modificarse; podré crear una nueva versión o revocarla.</span></label>'
        . '<p class="field-error" id="f-accept-error" role="alert"' . (isset($errors['accept']) ? '' : ' hidden') . '>' . h($errors['accept'] ?? '') . '</p></fieldset>'
        . '<button class="btn btn-primary btn-lg">Emitir declaración</button><p class="meta">Emitir no publica nada: la declaración seguirá siendo privada hasta que decidas publicarla.</p></form></section>';
    echo '<div class="spaced">' . confirm_button(wiz_url($vid, 'revision'), ['action' => 'discard', 'id' => $vid, 'p' => 'revision'], 'Descartar este borrador', "¿Descartar el borrador de la versión {$v['version_number']} de «{$f['work']['title']}»?", 'Se eliminarán sus respuestas, herramientas, usos y evidencias. Las versiones ya emitidas no se ven afectadas. Esta acción no se puede deshacer.', 'Descartar borrador') . '</div>';
}

echo '</div>';
page_end();
