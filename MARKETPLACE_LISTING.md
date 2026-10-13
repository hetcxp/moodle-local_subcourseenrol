# Moodle Plugins Directory & Marketplace Listing Kit
**Plugin:** Subcourse Auto-Enrolment (`local_subcourseenrol`)
**Version:** 2.1.4 | **Target:** Moodle 4.5+ | **License:** GNU GPL v3
**Guidelines Reference:** [Moodle Marketplace Submission Standards](https://moodle.atlassian.net/wiki/external/OTE4YmM3MDlmM2ZjNDczMGFlNzRlZGM0YTlmNGVlNDU)

---

## 📋 Compliance & Marketing Checklist

| Requirement | Guideline Limit | Current Status | Notes |
|:---|:---|:---|:---|
| **Short Description** | Max 270 chars | **236 chars (EN)** / **217 chars (ES)** | First 119 characters front-load the primary value proposition before card truncation. |
| **Full Description** | Max 7,000 chars | **~3,850 chars (EN)** / **~4,100 chars (ES)** | Rich markdown structure, headings, comparison matrix, prerequisites, and FAQs. |
| **External Dependencies** | Explicit disclosure | **Zero external services** | 100% self-hosted, no third-party accounts, no telemetry. |
| **Pricing / Model** | Commercial disclosure | **100% Free & Open Source** | GNU General Public License v3.0. |
| **GDPR & Privacy** | Moodle Privacy API | **Compliant** | Implements `\core_privacy\local\metadata\null_provider`. |

---

# 🇬🇧 English Listing (Official Submission)

### Plugin Title
**Subcourse Auto-Enrolment**

### Short Description (Max 270 characters)
> Instantly enrols students into subcourses when clicked, syncing access expiration from the master course. Eliminates manual enrolment overhead and ensures a frictionless multi-course learning journey for Moodle administrators and learners.
* **Character count:** 236 / 270
* **First 119 characters (Search Card View):**
  `Instantly enrols students into subcourses when clicked, syncing access expiration from the master course. Eliminates ma`

---

### Full Description (Rich Text / Markdown)

## Connect Modular Pathways with Zero Enrolment Friction

Managing structured multi-course curricula, corporate compliance tracks, or degree modules in Moodle often requires connecting independent courses. While the popular `mod_subcourse` activity links course grades together, **students are frequently blocked by the dreaded error:**
> *"You cannot enrol yourself in this course."*

**Subcourse Auto-Enrolment (`local_subcourseenrol`)** completely automates this process. The exact millisecond a student clicks on a Subcourse activity within a master course, they are seamlessly enrolled into the referenced target course—with zero administrative intervention and zero learner drop-off.

---

### 🎯 Who Is This For?

* **Higher Education & Universities:** Managing prerequisite modules, academic departments, or elective courses tied to a central programme portal.
* **Corporate L&D & Compliance:** Designing multi-stage onboarding academies where employees progress through specialized courses upon demand.
* **Instructional Designers & Course Creators:** Structuring clean, uncluttered learning pathways without bloating single courses into unmanageable course pages.
* **Moodle Site Administrators:** Eliminating repetitive manual enrolments, bulky cohort syncs, or complex meta-link architectures.

---

### ✨ Key Capabilities & Benefits

* ⚡ **Instant Event-Driven Enrolment:** Hooks into Moodle's native `course_module_viewed` event. Enrolment occurs transparently in the background the moment the subcourse activity is accessed.
* ⏳ **Smart Access Expiration Sync (`timeend`):** Enrolment duration in the child course dynamically mirrors the learner's expiration in the master course. If access in the master course ends on June 30, the subcourse access automatically expires on June 30. Perpetual enrolments (no expiration) are preserved.
* 🛡️ **Role Safety & Enrolment Isolation:** Enrols learners using Moodle’s standard Manual Enrolment method with the designated `student` role (Archetype). Teachers, non-editing teachers, and managers retain their standard permissions without cross-contamination.
* 📜 **Full Auditability & Native Logging:** Every automated enrolment triggers a native Moodle event (`\local_subcourseenrol\event\user_autoenrolled`), visible directly in *Site Administration → Reports → Logs*.
* 🎛️ **Granular Site Control:** Toggle auto-enrolment globally via *Site Administration → Plugins → Local plugins → Subcourse Auto-Enrolment* without uninstalling or altering course content.

---

### 📊 Feature Comparison

| Capability | Standard Moodle / Meta-Link | Cohort Sync | Subcourse Auto-Enrolment |
|:---|:---:|:---:|:---:|
| **Enrolment Timing** | Enrols in all courses up front | Manual / Scheduled sync | **Just-in-time on demand (when accessed)** |
| **Navigation Clutter** | Clutters "My Courses" dashboard | Clutters dashboard | **Clean: only active courses appear** |
| **Expiration Sync** | Manual setup per method | Manual / Static | **Automated inheritance from master course** |
| **Setup Complexity** | High (links per course) | Moderate (cohort management) | **Zero setup (works out of the box)** |
| **Auditing & Logs** | Generic enrolment log | Sync cron log | **Dedicated custom Moodle event** |

---

### 🚀 Getting Started in 3 Steps

1. **Install the Plugin:** Extract to your Moodle `/local/subcourseenrol` directory and complete the standard database upgrade via *Site Administration → Notifications*.
2. **Verify Target Course:** Ensure the target child course has the **Manual Enrolment** method enabled (*Course administration → Enrolment methods*).
3. **Add a Subcourse Activity:** In your master course, add a standard **Subcourse** activity pointing to your child course. That’s it! Any enrolled student who clicks the link is automatically enrolled into the child course.

---

### ⚙️ Prerequisites & Technical Requirements

* **Moodle Core:** 4.5 or newer.
* **Activity Dependency:** `mod_subcourse` version 2025032001 or higher.
* **Database Support:** MySQL / MariaDB / PostgreSQL / Oracle / SQL Server (100% standard Moodle DDL).
* **Test Coverage:** Covered by automated PHPUnit and Behat test suites for reliable long-term maintenance.

---

### 🔒 Privacy, Security & Licensing

* **100% Free & Open Source:** Released under the GNU General Public License v3.0 (GPLv3).
* **GDPR Compliant:** Fully implements Moodle's Privacy API (`\core_privacy\local\metadata\null_provider`). No personal data, tracking cookies, or custom tables are stored.
* **No Third-Party Dependencies:** 100% self-hosted; requires no external accounts, APIs, tokens, or subscription plans.

---

### 🤝 Support & Documentation

* **Source Code Repository:** [GitHub - Subcourse Auto-Enrolment](https://github.com/hetcxp/moodle-local_subcourseenrol)
* **Issue Tracker & Feature Requests:** [GitHub Issues](https://github.com/hetcxp/moodle-local_subcourseenrol/issues)
* **License:** GNU General Public License v3.0

---

<br>

# 🇪🇸 Ficha en Español (Para Comunidades Hispanohablantes)

### Título del Plugin
**Auto-matriculación en Subcursos (Subcourse Auto-Enrolment)**

### Descripción Corta (Máximo 270 caracteres)
> Matricula al alumno al entrar al subcurso y sincroniza la expiración del curso principal. Elimina la gestión manual de inscripciones y garantiza una experiencia de aprendizaje modular continua y sin errores de acceso.
* **Conteo de caracteres:** 217 / 270
* **Primeros 119 caracteres (Vista en tarjeta de búsqueda):**
  `Matricula al alumno al entrar al subcurso y sincroniza la expiración del curso principal. Elimina la gestión manual de `

---

### Descripción Completa (Texto Enriquecido)

## Conecte Rutas de Aprendizaje Modulares Sin Fricciones de Acceso

La gestión de itinerarios formativos complejos, programas corporativos o mallas curriculares universitarias en Moodle a menudo requiere enlazar cursos independientes. Aunque la popular actividad `mod_subcourse` permite consolidar calificaciones entre cursos, **los estudiantes chocan frecuentemente con un bloqueo frustrante:**
> *"No puede automatricularse en este curso."*

**Subcourse Auto-Enrolment (`local_subcourseenrol`)** automatiza este proceso por completo. En el milisegundo exacto en que un estudiante hace clic en una actividad de Subcurso dentro de un curso principal, queda matriculado de forma transparente en el curso de destino, sin intervención manual de soporte y sin abandonos en el aula virtual.

---

### 🎯 ¿Para Quién Es Este Plugin?

* **Universidades e Institutos:** Gestión de asignaturas correlativas, departamentos académicos o programas de grado articulados desde un curso troncal.
* **Departamentos de RRHH y Formación Corporativa:** Academias de inducción por etapas donde los colaboradores acceden a cursos especializados bajo demanda.
* **Diseñadores Instruccionales:** Creación de rutas de aprendizaje limpias y modulares sin sobrecargar una única aula virtual con cientos de recursos.
* **Administradores de Moodle:** Eliminación definitiva de inscripciones manuales repetitivas, cohortes estáticas o complejas configuraciones de meta-enlaces.

---

### ✨ Características Principales y Beneficios

* ⚡ **Matriculación Just-In-Time:** Basado en el evento nativo de Moodle `course_module_viewed`. La inscripción se realiza en segundo plano al hacer clic en el subcurso.
* ⏳ **Sincronización Inteligente de Expiración (`timeend`):** La vigencia de acceso en el subcurso hereda con exactitud la fecha de finalización del curso principal. Si el acceso al curso padre vence el 31 de diciembre, el subcurso expirará en la misma fecha. Se respetan inscripciones permanentes.
* 🛡️ **Aislamiento de Roles y Seguridad:** Matricula a los participantes exclusivamente con el rol de `estudiante` mediante el método nativo de Matriculación Manual. No afecta ni altera los permisos de docentes, tutores o gestores.
* 📜 **Auditoría Completa y Registro Nativo:** Cada matriculación dispara el evento `\local_subcourseenrol\event\user_autoenrolled`, visible de inmediato en *Administración del sitio → Informes → Registros*.
* 🎛️ **Control Centralizado:** Interruptor global para activar o desactivar la funcionalidad en *Administración del sitio → Extensiones → Extensiones locales → Subcourse Auto-Enrolment*.

---

### 🚀 Puesta en Marcha en 3 Pasos

1. **Instalación:** Descomprima en `/local/subcourseenrol` y ejecute la actualización de la base de datos en *Administración del sitio → Notificaciones*.
2. **Habilitar Matriculación Manual:** Asegúrese de que el curso de destino tenga activo el método de **Matriculación manual** (*Administración del curso → Métodos de matriculación*).
3. **Crear la Actividad Subcurso:** En el curso principal, añada una actividad **Subcurso** apuntando al curso de destino. ¡Listo! Cualquier alumno matriculado en el curso principal accederá automáticamente.

---

### ⚙️ Requisitos Técnicos

* **Versión de Moodle:** 4.5 o superior.
* **Dependencia requerida:** Actividad `mod_subcourse` versión 2025032001 o superior.
* **Bases de datos:** MySQL, MariaDB, PostgreSQL, Oracle, SQL Server.
* **Control de Calidad:** Respaldado por suites automatizadas de pruebas unitarias (PHPUnit) y funcionales (Behat).

---

### 🔒 Privacidad, Seguridad y Licencia

* **100% Código Abierto y Gratuito:** Distribuido bajo licencia GNU General Public License v3.0 (GPLv3).
* **Cumplimiento GDPR:** Implementa la API de Privacidad oficial de Moodle (`\core_privacy\local\metadata\null_provider`). No almacena datos personales ni instala tablas adicionales.
* **Sin Servicios Externos:** Funciona de forma 100% autónoma en su servidor; no requiere cuentas externas, APIs, suscripciones ni telemetría.
