<?php
declare(strict_types=1);

const L_COMPONENT_KIND = ['TEXT' => 'Texto', 'IMAGE' => 'Imagen / ilustración', 'AUDIO' => 'Audio / música', 'VIDEO' => 'Vídeo / animación', 'CODE' => 'Código', 'INTERACTIVE' => 'Diseño interactivo', 'OTHER' => 'Otro'];
const L_WORK_STATUS = ['IN_PROGRESS' => 'En proceso', 'FINISHED' => 'Terminada (inédita)', 'PUBLISHED' => 'Publicada'];
const L_SCOPE = ['WHOLE_WORK' => 'La obra completa', 'COMPONENTS' => 'Componentes concretos de la obra', 'OWN_CONTRIBUTION' => 'Solo mi contribución'];
const L_AI_USAGE = ['NO' => 'No se utilizó IA', 'YES' => 'Sí se utilizó IA', 'UNCERTAIN' => 'No puede determinarse con certeza', 'OWN_CONTRIBUTION_ONLY' => 'Solo puede declararse sobre la propia contribución'];
const L_INCORPORATION = ['NONE' => 'No se incorporó nada a la obra', 'INCORPORATED' => 'Se incorporó algo a la obra', 'UNKNOWN' => 'No se puede precisar'];
const L_INTERVENTION = ['ACCEPTED' => 'Lo seleccioné o acepté sin cambios significativos', 'MINOR_EDITS' => 'Hice ajustes menores', 'TRANSFORMED' => 'Lo transformé', 'OWN_EXPRESSION' => 'Tomé la idea o referencia y realicé yo la expresión final', 'UNSURE' => 'No puedo precisarlo'];
const L_TRI = ['YES' => 'Sí', 'NO' => 'No', 'UNSURE' => 'No lo sé'];
const L_EVIDENCE_KIND = ['VERSION_HISTORY' => 'Historial de versiones', 'CONVERSATION_LOG' => 'Registro de conversación con la IA', 'WORK_MATERIAL' => 'Material de trabajo', 'SCREENSHOT' => 'Captura de pantalla', 'OTHER' => 'Otra'];
const L_DOC_STATUS = ['DRAFT' => 'Borrador', 'ISSUED' => 'Emitida', 'SUPERSEDED' => 'Sustituida', 'REVOKED' => 'Revocada'];
const L_VISIBILITY = ['PRIVATE' => 'Privada', 'PUBLIC' => 'Pública', 'WITHDRAWN' => 'Retirada de publicación'];
const L_PROFILE_STATUS = ['COMPLETE' => 'Clasificación completa', 'PARTIAL' => 'Clasificación parcial (información insuficiente en algún uso)', 'INSUFFICIENT' => 'Información insuficiente', 'UNDETERMINED' => 'Uso de IA no determinado', 'PENDING' => 'Clasificación pendiente'];
const L_SYSTEM_TYPES = ['Modelo de lenguaje / asistente conversacional', 'Generador de imágenes', 'Generador de música o audio', 'Generador de vídeo', 'Traducción automática', 'Corrector o asistente de escritura', 'Herramienta de edición con funciones de IA', 'Asistente de código', 'Otro / no lo sé'];
const L_INCIDENT = ['CONTACT' => 'Consulta', 'CONTENT_REPORT' => 'Comunicación de contenido', 'TECHNICAL' => 'Técnica', 'DATA_ACCESS' => 'RGPD · acceso', 'DATA_RECTIFICATION' => 'RGPD · rectificación', 'DATA_ERASURE' => 'RGPD · supresión', 'DATA_OBJECTION' => 'RGPD · oposición', 'DATA_PORTABILITY' => 'RGPD · portabilidad', 'DATA_RESTRICTION' => 'RGPD · limitación'];

function badge(string $text, string $tone = 'neutral'): string
{
    return '<span class="badge badge-' . h($tone) . '">' . h($text) . '</span>';
}

function status_badge(string $s): string
{
    return badge(L_DOC_STATUS[$s] ?? $s, ['ISSUED' => 'ok', 'DRAFT' => 'warn', 'REVOKED' => 'danger'][$s] ?? 'neutral');
}

function visibility_badge(string $v): string
{
    return badge(L_VISIBILITY[$v] ?? $v, $v === 'PUBLIC' ? 'petrol' : 'neutral');
}
