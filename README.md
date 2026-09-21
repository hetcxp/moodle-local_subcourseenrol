# 🎓 Subcourse Auto-Enrolment — `local_subcourseenrol`

> **Moodle Local Plugin** · v2.1.0 · Requires Moodle 4.5+ · Requires `mod_subcourse` ≥ 2025032001

**Subcourse Auto-Enrolment** bridges the gap between structured learning pathways and administrative simplicity. It automatically enrols students into target courses the moment they click on a Subcourse activity, syncing enrolment expiration effortlessly from the master course.

---

## ✨ Why Subcourse Auto-Enrolment?

Managing complex training programs across multiple Moodle courses can be an administrative nightmare. This plugin automates the busywork:

- **Seamless Learner Journey** — Students click a subcourse link and gain instant access. No frustrating _"You cannot enrol yourself in this course"_ errors.
- **Smart Access Expiration** — Enrolment duration in the target course automatically mirrors the master course (`timeend` inheritance). Perpetual enrolments (no expiry) are respected.
- **Admin Relief** — Say goodbye to manual enrolments or complex cohort sync setups for subcourses.
- **Audit Trail** — Every auto-enrolment fires a custom Moodle event (`user_autoenrolled`) visible in the site log.
- **GDPR Compliant** — Implements the Privacy API `null_provider`; no personal data is stored.

---

## 🛠️ Technical Architecture

| Component | Description |
|-----------|-------------|
| **Plugin type** | Local (`local_subcourseenrol`) |
| **Entry point** | `db/events.php` — registers an observer on `\mod_subcourse\event\course_module_viewed` |
| **Observer** | `classes/observer.php` — `subcourse_viewed()` |
| **Custom event** | `classes/event/user_autoenrolled.php` — logged to Moodle's event log |
| **Privacy** | `classes/privacy/provider.php` — `null_provider` (no data stored) |
| **Settings** | `settings.php` — enable/disable toggle under _Site Administration → Local plugins_ |
| **Tests** | `tests/observer_test.php` (9 PHPUnit cases) · `tests/event_test.php` (4 PHPUnit cases) |

### Enrolment Logic Flow

```
User clicks Subcourse activity
        │
        ▼
mod_subcourse fires course_module_viewed event
        │
        ▼
observer::subcourse_viewed()
        ├─ Plugin disabled? → return
        ├─ subcourse.refcourse empty? → return
        ├─ User already enrolled in target? → return
        ├─ No active enrolment in master? → return
        ├─ No manual enrolment instance in target? → return (debugging)
        ├─ No student role? → return
        └─ enrol_user(instance, userid, roleid, now, masterTimeend)
               └─ Fires user_autoenrolled event
```

**`timeend` resolution:** The observer queries `user_enrolments` in the master course and selects the most permissive active enrolment — perpetual (`timeend = 0`) is prioritised over any expiring enrolment.

---

## 📋 Requirements

- Moodle **4.5+** (version ≥ `2024100700`)
- [`mod_subcourse`](https://moodle.org/plugins/mod_subcourse) ≥ `2025032001` (introduces `course_module_viewed` event)
- The **Manual enrolment** method must be enabled on each target course

---

## 🚀 Installation

1. Copy or clone this repository into your Moodle installation:
   ```bash
   # Via Git clone:
   git clone https://github.com/hetcxp/moodle-local_subcourseenrol.git /path/to/moodle/local/subcourseenrol

   # Or manually copying the folder:
   cp -r subcourseenrol /path/to/moodle/local/subcourseenrol
   ```
2. Run the Moodle upgrade script:
   ```bash
   php admin/cli/upgrade.php
   ```
3. Navigate to **Site Administration → Local plugins → Subcourse auto-enrolment** and ensure the plugin is enabled.
4. Verify that the **Manual** enrolment method is enabled on all target courses (Moodle default: enabled).

---

## ⚙️ Configuration

| Setting | Default | Description |
|---------|---------|-------------|
| `enabled` | `1` (on) | Master toggle — disabling prevents all auto-enrolments |

Settings page: `Site Administration → Local plugins → Subcourse auto-enrolment`

---

## 🧪 Running Tests

```bash
# From your Moodle root
vendor/bin/phpunit --filter local_subcourseenrol
```

Requires `mod_subcourse` to be installed; tests are automatically skipped otherwise.

---

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md).

---

## 🪪 License

GNU General Public License v3 or later — see [LICENSE](https://www.gnu.org/licenses/gpl-3.0.html).

Copyright © 2026 Héctor Eduardo Terán Canelones.
