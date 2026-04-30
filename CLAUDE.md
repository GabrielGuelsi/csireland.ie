# CI Ireland CS Platform — Agent Reference

This document is the single source of truth for any AI agent working on this codebase.
Read it fully before making any changes.

---

## 1. Project Overview

**What it is:** A Customer Success (CS) CRM platform for CI Ireland — an Irish education consultancy.
It manages the student journey from Google Form submission through to visa completion.

**Who uses it:**
- **CS Agents** — primary users; work inside WhatsApp Web via Chrome extension
- **Admins** — manage agents, templates, SLA settings, assignment rules via web admin panel

**Two codebases, one system:**
| Codebase | Path | Purpose |
|---|---|---|
| Laravel backend | `C:\Users\PC_Desk\Desktop\eduauto-platform` | API + admin panel |
| Chrome extension | `C:\Users\PC_Desk\Desktop\eduauto\extension` | CS agent frontend (injected into WhatsApp Web) |

**Deployed at:** `https://cs.ciireland.ie` (Hostinger VPS, alongside `ciireland.ie`)

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.2+ |
| Auth | Laravel Sanctum (token-based for API, session for admin) |
| Database | SQLite (local dev) / MySQL (production) |
| Queue/scheduler | Laravel database queue + scheduler via cron |
| Admin UI | AdminLTE 3 + Blade templates |
| Extension | Chrome Manifest V3, vanilla JS, no bundler |
| Webhook source | Google Forms → Google Apps Script → POST to `/api/webhook/form` |

---

## 3. Directory Structure

```
eduauto-platform/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/                    # JSON API (consumed by extension)
│   │   │   ├── AuthController.php
│   │   │   ├── StudentController.php
│   │   │   ├── NoteController.php
│   │   │   ├── TemplateController.php
│   │   │   ├── MessageLogController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── SlaController.php
│   │   │   ├── ScheduledMessageController.php
│   │   │   └── WebhookController.php
│   │   └── Admin/                  # Blade admin panel
│   │       ├── DashboardController.php
│   │       ├── StudentController.php
│   │       ├── AgentController.php
│   │       ├── SalesConsultantController.php
│   │       ├── AssignmentRuleController.php
│   │       ├── SlaSettingController.php
│   │       ├── TemplateController.php
│   │       ├── MessageSequenceController.php
│   │       └── ReportController.php
│   ├── Models/
│   │   ├── User.php                # CS agents + admins
│   │   ├── Student.php             # Core model — see section 5
│   │   ├── SalesConsultant.php
│   │   ├── AssignmentRule.php      # Sales consultant → CS agent mapping
│   │   ├── StudentStageLog.php     # Audit log of status changes
│   │   ├── Note.php
│   │   ├── MessageTemplate.php
│   │   ├── MessageLog.php          # Record of every sent message
│   │   ├── SlaSetting.php          # Days limit per status
│   │   ├── Notification.php
│   │   ├── MessageSequence.php     # Scheduled message config
│   │   └── ScheduledStudentMessage.php
│   ├── Services/
│   │   ├── AssignmentService.php   # Resolves sales consultant → CS agent
│   │   ├── PhoneNormaliser.php     # Normalises WhatsApp phone numbers to E.164
│   │   ├── SlaService.php          # Calculates SLA status per student
│   │   └── CigoService.php
│   └── Jobs/
│       ├── SendDailyAlertsJob.php          # 7:00am — birthdays, exams, visa, overdue
│       ├── ProcessScheduledMessagesJob.php  # 7:00am — creates notifications for due messages
│       ├── SendMorningDigestJob.php         # 8:30am — email digest per agent
│       └── CreateStudentScheduledMessagesJob.php  # Dispatched on first contact
├── routes/
│   ├── api.php                     # All /api/* routes
│   ├── web.php                     # Admin panel routes
│   └── console.php                 # Scheduled job registration
├── resources/views/
│   └── admin/
│       ├── dashboard.blade.php
│       ├── students/               # index, show, edit, _form
│       └── message-sequences/      # index, create, edit, _form
└── database/migrations/            # 18 migrations total

eduauto/extension/
├── manifest.json                   # Chrome MV3 manifest
├── content.js                      # Main extension logic (injected into WhatsApp Web)
├── sidebar.css                     # Extension styles
├── popup.html                      # Extension popup (login)
└── popup.js                        # Popup logic (auth, settings)
```

---

## 4. Authentication

### API (extension)
- **Sanctum token-based**
- Agent logs in via `POST /api/auth/login` with `{email, password}`
- Receives a bearer token — stored in `chrome.storage.local` (alongside `apiUrl`, `agentName`, `agentId`)
- All subsequent API calls include `Authorization: Bearer {token}`
- Token expires after 14 days (`SANCTUM_EXPIRATION=20160`)

### Admin panel
- Session-based Breeze auth
- Protected by `auth` + `admin` middleware on all `/admin/*` routes
- `admin` middleware checks `$user->role === 'admin'`

### User roles
- `admin` — full access to admin panel + all API endpoints
- `cs_agent` (default) — API access restricted to their own assigned students
- `application` — Applications team. Owns the `application_status` lifecycle (see §16). Access to `/admin/applications/*` via `EnsureAdminOrApplication` middleware.
- `sales_agent` — Sales pipeline (prototype). Access via `sales_stage` global scope opt-in.

### Authorization rule (API)
Every student-specific API endpoint enforces:
```php
if (!$request->user()->isAdmin() && $student->assigned_cs_agent_id !== $request->user()->id) {
    abort(403);
}
```
Exception: `GET /api/students/match` — intentionally open to all agents (extension needs to look up any incoming WhatsApp number).

---

## 5. Core Model: Student

### All fields
| Field | Type | Notes |
|---|---|---|
| `name` | string | |
| `email` | string | |
| `whatsapp_phone` | string | E.164 format e.g. `+353861234567` |
| `product_type` | enum | See product types below |
| `product_type_other` | string\|null | Free text when product_type = `other` |
| `course` | string\|null | |
| `university` | string\|null | |
| `intake` | string\|null | `jan`, `feb`, `may`, `jun`, `sep` |
| `sales_price` | decimal\|null | |
| `sales_price_scholarship` | decimal\|null | |
| `pending_documents` | text\|null | |
| `observations` | text\|null | Internal notes visible to CS agent |
| `reapplication_action` | enum\|null | `keep_previous`, `cancel_previous` |
| `sales_consultant_id` | FK → sales_consultants | |
| `assigned_cs_agent_id` | FK → users | The CS agent responsible |
| `status` | enum | See statuses below |
| `priority` | enum\|null | `high`, `medium`, `low` |
| `system` | string\|null | `edvisor` or `cigo` |
| `exam_date` | date\|null | |
| `exam_result` | enum\|null | `pending`, `pass`, `fail` |
| `payment_status` | enum\|null | `pending`, `partial`, `confirmed` |
| `visa_status` | enum\|null | `not_started`, `material_sent`, `answered`, `complete` |
| `visa_expiry_date` | date\|null | |
| `date_of_birth` | date\|null | |
| `gift_received_at` | timestamp\|null | Set when gift is marked received |
| `form_submitted_at` | timestamp\|null | Set by webhook on creation |
| `first_contacted_at` | timestamp\|null | Set on first status change away from `waiting_initial_documents` |
| `application_status` | enum\|null | Applications-team lifecycle. See §16. Independent from `status`. |
| `application_notes` | text\|null | Free-text notes from Applications team |
| `college_application_date` | date\|null | When Applications submitted the application to the college |
| `college_response_date` | date\|null | When the college responded |
| `offer_letter_received_at` | timestamp\|null | When the offer letter arrived |
| `completed_course` | string\|null | **Realized** course at enrollment (may differ from `course`) |
| `completed_university` | string\|null | **Realized** university at enrollment (may differ from `university`) |
| `completed_intake` | string\|null | **Realized** intake at enrollment |
| `completed_price` | decimal\|null | **Realized** price at enrollment (may differ from `sales_price`) |
| `completed_at` | timestamp\|null | When the student moved to `application_status='enrolled'` |
| `application_cancellation_reason` | string\|null | Dropdown value (see `Student::applicationCancellationReasons()`) |
| `application_cancellation_stage` | string\|null | Snapshot of the `application_status` value just before cancellation |
| `application_cancelled_at` | timestamp\|null | When the student moved to `application_status='cancelled'` |
| `cancellation_reason` | text\|null | **CS-side** free-text reason; not the same as `application_cancellation_reason` |
| `cancellation_justified` | boolean\|null | CS-side: justified cancellations don't trigger the −€5 KPI penalty |

### Student statuses (`Student::allStatuses()`)
```
waiting_initial_documents    → Waiting for Documents (Initial)   [default/entry status]
waiting_offer_letter         → Waiting for Offer Letter
waiting_english_exam         → Waiting for English Exam (College)
waiting_duolingo             → Waiting for Duolingo
waiting_reapplication        → Waiting for Reapplication
waiting_college_documents    → Waiting for Documents (College)
waiting_college_response     → Waiting for College Response
waiting_payment              → Waiting for Payment
waiting_student_response     → Waiting for Student Response
cancelled                    → Cancelled
concluded                    → Concluded
```

### Product types
```
higher_education | first_visa | reapplication | insurance | emergencial_tax | learn_protection | other
```

### Key relationships
```php
$student->salesConsultant       // BelongsTo SalesConsultant
$student->assignedAgent         // BelongsTo User
$student->notes                 // HasMany Note (ordered desc)
$student->stageLogs             // HasMany StudentStageLog
$student->messageLogs           // HasMany MessageLog
$student->notifications         // HasMany Notification
$student->scheduledMessages     // HasMany ScheduledStudentMessage
```

---

## 6. API Endpoints

Base: `/api/` — All protected routes require `Authorization: Bearer {token}`

### Auth
| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `auth/login` | none | `{email, password}` → `{token, agent_name, agent_id}` · throttle: 5/min |
| POST | `auth/logout` | ✓ | Revokes current token |

### Webhook
| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `webhook/form` | Bearer secret | Google Forms submission → creates Student · throttle: 30/min |

The webhook secret is validated with `hash_equals(config('services.webhook.secret'), $header)`.

### Students
| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `students/match?phone=X` | ✓ | Match by WhatsApp phone (open to all agents) |
| GET | `students/search?q=X` | ✓ | Search by name/email (returns max 20) |
| GET | `students/pipeline` | ✓ | All students grouped by status (admin sees all, agent sees own) |
| POST | `students/{id}/link-phone` | ✓ own | Link a phone number to a student |
| PATCH | `students/{id}/stage` | ✓ own | Update status; sets first_contacted_at on first move; dispatches CreateStudentScheduledMessagesJob |
| PATCH | `students/{id}/exam` | ✓ own | `{exam_date, exam_result}` |
| PATCH | `students/{id}/payment` | ✓ own | `{payment_status}` |
| PATCH | `students/{id}/visa` | ✓ own | `{visa_status}` |
| PATCH | `students/{id}/priority` | ✓ own | `{priority}` |
| PATCH | `students/{id}/gift-received` | ✓ own | Sets gift_received_at = now |

### Notes
| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `notes/{student_id}` | ✓ own | Get all notes for a student |
| POST | `notes` | ✓ own | `{student_id, body}` — creates note |

### Templates
| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `templates` | ✓ | All active message templates |

### Notifications
| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `notifications` | ✓ | All unread notifications for agent |
| PATCH | `notifications/{id}/read` | ✓ | Mark notification as read |

### Scheduled Messages
| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `scheduled-messages/pending` | ✓ | Today's pending messages for agent's students |
| PATCH | `scheduled-messages/{id}/sent` | ✓ own | Mark as sent + log to message_logs |
| GET | `students/{id}/scheduled-messages` | ✓ own | Pending messages for a specific student |

### SLA
| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `sla-settings` | ✓ | All SLA day limits per status |

---

## 7. Webhook — Google Forms Integration

### Flow
```
Google Form submit
  → Google Apps Script onFormSubmit()
  → POST https://cs.ciireland.ie/api/webhook/form
     Authorization: Bearer {WEBHOOK_SECRET}
     Content-Type: application/json
     Body: { "Field Name": ["value"], ... }
  → WebhookController::handleForm()
  → Creates Student record
  → Creates new_assignment Notification for CS agent
```

### Google Form field names → Student fields
| Form field | Student field |
|---|---|
| `Student full name` | `name` |
| `Student email` | `email` |
| `Student WhatsApp number` | `whatsapp_phone` |
| `Sales Advisor` | `sales_consultant_id` (via AssignmentService) |
| `Product type` | `product_type` |
| `Course` | `course` |
| `University` | `university` |
| `Intake` | `intake` (mapped: "January/February" → `jan`, "Maio" → `may`, etc.) |
| `Sales price without scholarship` | `sales_price` |
| `Sales price with scholarship (If Applicable)` | `sales_price_scholarship` |
| `Pending documents and add informations` | `pending_documents` |
| `If REAPPLICATION:` | `reapplication_action` |
| `Date of Birth` | `date_of_birth` (parses dd/mm/yyyy) |
| `Visa expiry` | `visa_expiry_date` (parses dd/mm/yyyy) |

### AssignmentService
`AssignmentService::resolve(string $consultantName)` — looks up the `SalesConsultant` by name (creates if not found), then finds the `AssignmentRule` to get the `cs_agent_id`. If no rule exists, student is created unassigned (`assigned_cs_agent_id = null`).

---

## 8. Scheduled Jobs

Registered in `routes/console.php`:

| Job | Time | What it does |
|---|---|---|
| `SendDailyAlertsJob` | 07:00 | Per agent: birthday alerts, exam_today, visa expiry (30/60 days), first_contact_overdue (3 working days), gift_ready |
| `ProcessScheduledMessagesJob` | 07:00 | Finds due ScheduledStudentMessages → creates `scheduled_message` notifications |
| `SendMorningDigestJob` | 08:30 | Emails each agent their daily summary |
| `CreateStudentScheduledMessagesJob` | on-demand | Dispatched when `first_contacted_at` is first set; creates ScheduledStudentMessage rows from active MessageSequences |

### Notification types
```
new_assignment | birthday | exam_today | visa_expiry | first_contact_overdue | scheduled_message | gift_ready
```

### SLA / overdue logic
- `SlaService::getStatus(Student)` checks `SlaSetting` for the current status
- Calculates days remaining based on when that status was entered (`StudentStageLog`)
- For `waiting_initial_documents`: counts from `form_submitted_at`
- Birthday matching uses PHP-side `$student->date_of_birth->format('m-d') === today` (no DATE_FORMAT — SQLite compatible)

---

## 9. Chrome Extension

**Entry point:** `content.js` — injected into `https://web.whatsapp.com/*`

### Key state object
```js
state = {
  token, apiUrl, agentName, agentId,  // from chrome.storage.local
  matchedStudent,                      // currently open student object
  templates,                           // all message templates
  notifications,                       // agent's unread notifications
  scheduledMessages,                   // today's pending scheduled messages
  studentScheduledMsgs,                // pending messages for open student
  pipeline,                            // { status: [students...] }
  activeTab,                           // 'active' | 'concluded' | 'cancelled'
  currentPhone,                        // phone extracted from current WhatsApp chat
  searchResults, searchQuery,          // link student search
}
```

### Main flows

**1. Phone detection**
On chat open, `getActivePhone()` reads the chat header title attribute and strips non-digits.

**2. Student match**
`GET /api/students/match?phone=X` — if matched, shows student card; if not, shows link panel.

**3. Student card**
`renderStudentCard(student)` renders full card with:
- SLA status badge
- Status dropdown (`change-status` → PATCH `/api/students/{id}/stage`)
- Priority dropdown (`change-priority` → PATCH `/api/students/{id}/priority`)
- Gift received button (only when `status === 'concluded' && !gift_received_at`)
- Template picker → `send-template` → `prefillWhatsApp(body)`
- Scheduled messages section → `send-scheduled` → `prefillWhatsApp(body)` + PATCH mark sent
- Notes section + add note form

**4. WhatsApp prefill**
`prefillWhatsApp(text)` injects text into the WhatsApp message input using `document.execCommand('insertText')`.
Template placeholder `{student_name}` is replaced using `.split('{student_name}').join(name)` (not `.replace()` — avoids regex `$&` substitution bugs).

**5. Pipeline tab**
3 tabs: `active` (9 waiting statuses) / `concluded` / `cancelled`
Pipeline data from `GET /api/students/pipeline`.

**6. Event delegation**
All clicks handled via single listener on `#ci-panel`:
```js
panel.addEventListener('click', async (e) => { ... el.dataset.action ... });
panel.addEventListener('change', async (e) => { ... el.dataset.action ... });
```

### Security notes
- All user data rendered into innerHTML is wrapped with `esc()` (HTML entity encoder)
- `getInitials(name)` output is also wrapped with `esc()` before innerHTML insertion
- API token stored in `chrome.storage.local` (plaintext — acceptable trade-off for Chrome extension)
- `<all_urls>` permission removed — only `https://web.whatsapp.com/*` + API domain

---

## 10. Admin Panel

All routes under `/admin` require `auth` + `admin` middleware.

| Route | Description |
|---|---|
| `/admin/dashboard` | Daily ops panel: birthdays, exams today, overdue first contact, pending scheduled messages |
| `/admin/students` | Filterable list with status/priority badges |
| `/admin/students/{id}` | Full student detail + scheduled messages + gift button |
| `/admin/students/{id}/edit` | Edit all student fields |
| `/admin/agents` | CRUD for CS agent users |
| `/admin/sales-consultants` | CRUD for sales consultants |
| `/admin/assignment-rules` | Map sales consultant → CS agent |
| `/admin/sla-settings` | Days limit per status |
| `/admin/templates` | Message template CRUD + toggle active |
| `/admin/message-sequences` | Scheduled message sequence CRUD |
| `/admin/reports` | Reporting |

---

## 11. Database Migrations (in order)

1. `create_users_table` — base Laravel users
2. `create_cache_table`
3. `create_jobs_table` — queue + failed_jobs
4. `create_personal_access_tokens_table` — Sanctum
5. `update_users_table` — adds `role`, `whatsapp_phone`, `active`
6. `create_sales_consultants_table`
7. `create_assignment_rules_table`
8. `recreate_students_table` — full students table
9. `create_student_stage_logs_table`
10. `create_notes_table`
11. `create_message_templates_table`
12. `create_message_logs_table`
13. `create_sla_settings_table`
14. `create_notifications_table`
15. `update_students_cs_fields` — adds status, priority, visa_expiry_date, date_of_birth, observations, system, gift_received_at; drops pipeline_stage
16. `create_message_sequences_table`
17. `create_scheduled_student_messages_table`
18. `update_notifications_type_enum` — adds new notification types + `data` JSON column

### SQLite compatibility rules (important for local dev)
- Never use `DATE_FORMAT()` in queries — use PHP-side Carbon comparisons instead
- Never combine `dropColumn` and `addColumn` in the same `Schema::table` call — split into two calls
- ENUM modification (`MODIFY COLUMN`) is MySQL-only — wrap in `if (DB::getDriverName() === 'mysql')`

---

## 12. Services

### PhoneNormaliser
`PhoneNormaliser::normalise(?string $raw): ?string`
- Strips all non-digit/non-plus characters
- Converts Irish numbers: `00353...` → `+353...`, `0...` → `+353...`
- Returns null if empty or if result length < 7 or > 20

### AssignmentService
`AssignmentService::resolve(string $consultantName): array`
- Returns `['sales_consultant_id' => int, 'assigned_cs_agent_id' => int|null]`
- Creates SalesConsultant if not found (firstOrCreate)
- Returns null agent ID if no AssignmentRule exists → student will be unassigned

### SlaService
`SlaService::getStatus(Student): array`
- Returns `['overdue' => bool, 'days_remaining' => int|null]`
- Reads `SlaSetting` for current status
- Calculates from `StudentStageLog` entry time (or `form_submitted_at` for initial status)

---

## 13. Security

### Applied hardening
- **Webhook auth:** `hash_equals()` timing-safe comparison (not `===`)
- **Webhook config:** Uses `config('services.webhook.secret')` not `env()` directly
- **Rate limiting:** Login 5/min · Webhook 30/min
- **Authorization:** All student-mutating API endpoints check agent assignment
- **CORS:** Restricted to specific methods and headers
- **Sanctum expiry:** 14 days
- **Extension XSS:** All API data through `esc()` before innerHTML; `getInitials()` also escaped
- **Manifest permissions:** No `<all_urls>`

### Production .env requirements
```
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
SESSION_ENCRYPT=true
SESSION_EXPIRE_ON_CLOSE=true
SANCTUM_STATEFUL_DOMAINS=cs.ciireland.ie
```

---

## 14. Deployment

**Server:** Hostinger VPS
**Main site:** `ciireland.ie` → existing Laravel project (separate directory, separate DB)
**This platform:** `cs.ciireland.ie` → `/var/www/eduauto`

### Nginx vhost key points
- Document root: `/var/www/eduauto/public`
- PHP-FPM socket: `unix:/var/run/php/php8.2-fpm.sock`
- `.env` access blocked: `location ~ /\.env { deny all; }`

### Cron (scheduler)
```
* * * * * cd /var/www/eduauto && php artisan schedule:run >> /dev/null 2>&1
```

### Deploy commands
```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan migrate --force
php artisan storage:link
chown -R www-data:www-data /var/www/eduauto
chmod -R 775 /var/www/eduauto/storage bootstrap/cache
```

---

## 15. Key Conventions

- **Never use `DATE_FORMAT()`** in Eloquent queries — always filter with PHP/Carbon for SQLite compatibility
- **Never modify `pipeline_stage`** — it has been replaced by `status` entirely
- **`Student::allStatuses()`** is the single source of truth for valid CS status values — use it for validation and iteration everywhere
- **`Student::allApplicationStatuses()`** is the single source of truth for valid Applications status values
- **`Student::statusLabel(string $status)`** / **`Student::applicationStatusLabel(string $status)`** return human-readable labels
- **Admin controllers** are in `App\Http\Controllers\Admin\` — separate from API controllers in `App\Http\Controllers\Api\`
- **The extension is the primary frontend** — all key CS actions must be accessible from within WhatsApp Web, not just the admin panel
- **Template placeholders** use `{student_name}` syntax; always replace using `.split('{student_name}').join(name)` in JS, never `.replace(/{student_name}/g, name)`
- **Two parallel student lifecycles** — CS `status` and Applications `application_status` are independent (no auto-sync). Don't conflate them in queries or reports.

---

## 16. Applications Module

A parallel lifecycle layered onto the `students` table, owned by the **Applications team** (user role `application`). The CS team and Applications team operate on the same student row but advance independent status fields.

### Status enum (`Student::allApplicationStatuses()`)
```
new_dispatch     → New Dispatch          [default at form arrival; set by webhook]
in_review        → In Review
waiting_cs       → Waiting CS
applied          → Applied to College
waiting_college  → Waiting College Response
offer_received   → Offer Received
enrolled         → Enrolled              [terminal-success — commission realized]
cancelled        → Cancelled             [terminal-loss]
```

### Capture flow on terminal transitions

**Moving to `enrolled`** — `ApplicationStudentController::update()` requires:
- `completed_course`, `completed_university`, `completed_intake`, `completed_price` (REQUIRED)
- `completed_at` (defaults to now)

These fields capture the **realized** values (which can differ from the form-arrival estimates `course` / `university` / `intake` / `sales_price`). The Sales Funnel report sums `COALESCE(completed_price, sales_price)` for enrolled rows.

**Moving to `cancelled`** — requires:
- `application_cancellation_reason` (dropdown — see `Student::applicationCancellationReasons()`)
- Controller auto-sets `application_cancellation_stage` (snapshot of prior `application_status`) and `application_cancelled_at`.

### Cancellation reasons
```
did_not_pay | went_elsewhere | visa_refused | withdrew | documents_incomplete | college_rejected | other
```

### Files
| Path | Role |
|---|---|
| `app/Http/Controllers/Admin/Applications/ApplicationStudentController.php` | Edit page handler — validates capture fields on enrolled/cancelled transitions |
| `app/Http/Middleware/EnsureAdminOrApplication.php` | Guards `/admin/applications/*` |
| `resources/views/admin/applications/student_edit.blade.php` | Edit form with inline capture sections (toggled by status dropdown via vanilla JS) |
| `resources/views/admin/applications/_chat_thread.blade.php` | CS ↔ Applications chat (uses `StudentChat` model with `author_role` ∈ {application, cs_agent, admin}) |

### Sales Funnel report

`ReportController::buildSalesFunnel($from, $to, $mode, $groupColumn)` powers the new report section in `/admin/reports`. Two modes:
- **Cohort** (`form_submitted_at` filter; bucket = current `application_status`) — answers "of leads that arrived in range, what's their state now?"
- **Period** (per-bucket date filter: form_submitted_at / completed_at / application_cancelled_at) — answers "in this period, how much arrived AND how much enrolled, regardless of when those enrolments arrived?"

Per-row metrics: `estimated_count/euro`, `in_process_count/euro`, `enrolled_count/euro` (uses `completed_price` when set), `lost_count/euro`, `conversion_rate`. Grouped by **Sales Consultant** and **CS Agent** in two stacked tables. A separate cancellation-reason breakdown table follows.

### Independence rule
Setting `application_status='enrolled'` does NOT auto-change CS `status`. The CS agent moves their own status separately. This is intentional — keep both lifecycles independent in reports and queries.
