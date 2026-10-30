/**
 * Diccionarios de la interfaz. Español completo; inglés preparado para la
 * interfaz común (navegación, autenticación, acciones). Los contenidos largos
 * de las páginas informativas están por ahora solo en español.
 */
export const LOCALES = ["es", "en"] as const;
export type Locale = (typeof LOCALES)[number];
export const DEFAULT_LOCALE: Locale = "es";

const es = {
  brand: { descriptor: "Creative AI Transparency", tagline: "Toda creación tiene un proceso. Hazlo visible.", taglineEn: "Every creation has a process. Make it visible." },
  nav: {
    how: "Cómo funciona",
    code: "ORIVA Code",
    declarations: "Declaraciones",
    verify: "Verificar",
    legal: "Marco normativo",
    faq: "Preguntas",
    login: "Iniciar sesión",
    register: "Crear cuenta",
    dashboard: "Mi espacio",
    logout: "Cerrar sesión",
    admin: "Administración",
    skip: "Saltar al contenido",
  },
  footer: {
    about: "Qué es ORIVA",
    privacy: "Privacidad",
    terms: "Condiciones de uso",
    legalNotice: "Aviso legal",
    contact: "Contacto",
    disclaimer: "ORIVA no es un servicio de la Unión Europea ni una certificación oficial. Las declaraciones son responsabilidad de quien las emite.",
  },
  app: {
    works: "Mis obras",
    declarations: "Mis declaraciones",
    profile: "Perfil",
    newDeclaration: "Nueva declaración",
  },
  common: { save: "Guardar", saveContinue: "Guardar y continuar", back: "Atrás", cancel: "Cancelar", language: "Idioma" },
};

export type Dictionary = typeof es;

const en: Dictionary = {
  brand: { descriptor: "Creative AI Transparency", tagline: "Every creation has a process. Make it visible.", taglineEn: "Every creation has a process. Make it visible." },
  nav: {
    how: "How it works",
    code: "ORIVA Code",
    declarations: "Declarations",
    verify: "Verify",
    legal: "Legal framework",
    faq: "FAQ",
    login: "Sign in",
    register: "Create account",
    dashboard: "My space",
    logout: "Sign out",
    admin: "Administration",
    skip: "Skip to content",
  },
  footer: {
    about: "About ORIVA",
    privacy: "Privacy",
    terms: "Terms of use",
    legalNotice: "Legal notice",
    contact: "Contact",
    disclaimer: "ORIVA is not a European Union service nor an official certification. Declarations are the responsibility of those who issue them.",
  },
  app: { works: "My works", declarations: "My declarations", profile: "Profile", newDeclaration: "New declaration" },
  common: { save: "Save", saveContinue: "Save and continue", back: "Back", cancel: "Cancel", language: "Language" },
};

export const dictionaries: Record<Locale, Dictionary> = { es, en };
