# CI Ireland — CS Optimisation Platform
## Complete Project Specification for Claude Code

---

## 0. HOW TO USE THIS DOCUMENT

This file is the single source of truth for the CI Ireland CS Optimisation Platform. Read it fully before writing any code. Every architectural decision, naming convention, database column, and UX behaviour described here must be implemented exactly as specified.

When in doubt, refer back to this document. Do not invent fields, routes, or logic that are not described here.

---

## 1. COMPANY CONTEXT

**Company:** CI Ireland (CI Exchange Ireland)
**Website:** ciireland.ie
**Location:** Dublin, Ireland
**Business:** International education consultancy placing students — primarily from Brazil and Latin America — into Irish higher education institutions (TU Dublin, DCU, NCI, Griffith College, DBS, and 8+ other partner colleges).

**Student Journey Stages (in order):**
1. English with Academic Purpose
2. Pathway Programme
3. Undergraduate
4. Postgraduate & Masters

**The CS team guides students through this journey after the initial sale is closed.**

---

## 2. WHAT WE ARE BUILDING

A two-component internal platform for the Customer Success (CS) team:

### Component A — Chrome Browser Extension
- Injected as a sidebar into **WhatsApp Web** (`web.whatsapp.com`)
- CS agents use their **own personal/business WhatsApp numbers** — NO WhatsApp API, NO third-party messaging service
- The extension reads the currently open WhatsApp contact's phone number and queries the Laravel API to fetch the matching student profile
- Displays a full CRM pipeline sidebar without the agent ever leaving WhatsApp

### Component B — Laravel + AdminLTE Web Dashboard
- Accessible at `app.ciireland.ie/admin` (or configured domain)
- **Managers and supervisors only** — CS agents do not use the dashboard as their primary interface
- Used for: student oversight, assignment rules, SLA configuration, message templates, reporting

### Backend — Laravel 11 REST API
- Powers both the extension and the dashboard
- Receives Google Form webhook
- Runs scheduled jobs (daily digest)
- Manages all business logic

---

## 3. TECH STACK

| Layer | Technology | Notes |
|---|---|---|
| Backend | Laravel 11 (PHP 8.3+) | API + dashboard backend |
| Dashboard Frontend | AdminLTE 3 + Blade | Rendered server-side, no separate SPA |
| Browser Extension | Vanilla JavaScript (Chrome Manifest V3) | Injected into WhatsApp Web |
| Database | MySQL 8+ | All persistent data |
| Cache / Queue | Redis + Laravel Queues | Async jobs, daily digest |
| Scheduler | Laravel Task Scheduler (`php artisan schedule:run`) | Cron every minute |
| Auth | Laravel Sanctum | API tokens for extension; session for dashboard |
| Google Integration | Google Apps Script + webhook | No Google SDK needed on Laravel side |
| Hosting | VPS via Laravel Forge | Ubuntu 22.04 recommended |

---

## 4. TEAM & USER ROLES

### 4.1 Role Definitions

| Role | Who | System Access |
|---|---|---|
| `admin` | Marilu Rosado (Director), supervisors | Full AdminLTE dashboard — all students, agents, settings, reports |
| `cs_agent` | Amanda Zangarini, Thamiris Bastos, Juliana | Chrome extension + read-only student list (own students only) |
| *(no login)* | Sales Consultants — Wagner, Talita, Gabriel, Aliny, Albert, Romario | Do not log in; their name appears in Google Form submissions |

### 4.2 Important Distinction
Sales consultants are **not system users**. They exist as a reference entity (`sales_consultants` table) populated from form submissions. They have no login.

### 4.3 Assignment Rules
When a new student form arrives, the system reads the `sales_consultant` field and looks up the `assignment_rules` table to find the correct CS agent automatically.

**Current default mappings:**
- Wagner Marinho → Amanda Zangarini
- Talita → Amanda Zangarini
- Gabriel → Juliana

Managers can change these rules at any time via the dashboard. Individual students can also be manually reassigned.

---

## 5. DATABASE SCHEMA

Create all tables via Laravel migrations. Use `snake_case` for all column names.

```sql
-- Platform user accounts (managers + CS agents only)
users
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  name                VARCHAR(255)
  email               VARCHAR(255) UNIQUE
  password            VARCHAR(255)
  role                ENUM('admin', 'cs_agent')
  whatsapp_phone      VARCHAR(30) NULL   -- for receiving morning digest
  active              BOOLEAN DEFAULT TRUE
  created_at, updated_at

-- Sales consultants (no login — reference only)
sales_consultants
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  name                VARCHAR(255) UNIQUE  -- must match Google Form values exactly
  created_at, updated_at

-- Assignment rules: sales consultant → CS agent
assignment_rules
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  sales_consultant_id BIGINT UNSIGNED REFERENCES sales_consultants(id)
  cs_agent_id         BIGINT UNSIGNED REFERENCES users(id)
  created_by          BIGINT UNSIGNED REFERENCES users(id)
  created_at, updated_at

-- Core student entity
students
  id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY

  -- Identity (from form)
  name                    VARCHAR(255)            -- "Student full name"
  email                   VARCHAR(255)            -- "E-mail" (student email submitted by consultant)
  whatsapp_phone          VARCHAR(30) NULL        -- normalised from form field "Student WhatsApp number"
                                                        -- CS agent can correct it manually if wrong

  -- Product & enrolment (from form)
  product_type            ENUM(
                            'higher_education',
                            'first_visa',
                            'reapplication',
                            'insurance',
                            'emergencial_tax',
                            'learn_protection',
                            'other'
                          )                       -- "Product type" radio field
  product_type_other      VARCHAR(255) NULL       -- free text when product_type = 'other'
  course                  VARCHAR(255) NULL       -- "Course" — written exactly as on university website
                                                  -- CAO code if DBS
  university              VARCHAR(255) NULL       -- "University" dropdown/text
  intake                  VARCHAR(100) NULL       -- "Intake": 'January/February', 'September', or custom text

  -- Financials (from form)
  sales_price             DECIMAL(10,2) NULL      -- "Sales price without scholarship"
  sales_price_scholarship DECIMAL(10,2) NULL      -- "Sales price with scholarship (If Applicable)"

  -- Pending & notes (from form)
  pending_documents       TEXT NULL               -- "Pending documents and add informations"

  -- Reapplication handling (from form)
  reapplication_action    ENUM('keep_previous','cancel_previous') NULL
                                                  -- only set when product_type = 'reapplication'

  -- Assignment
  sales_consultant_id     BIGINT UNSIGNED REFERENCES sales_consultants(id)
  assigned_cs_agent_id    BIGINT UNSIGNED REFERENCES users(id) NULL

  -- CS pipeline (managed by CS agents after assignment)
  pipeline_stage          ENUM('first_contact','exam','payment','visa','complete')
                          DEFAULT 'first_contact'
  exam_date               DATE NULL
  exam_result             ENUM('pending','pass','fail') DEFAULT 'pending'
  payment_status          ENUM('pending','partial','confirmed') DEFAULT 'pending'
  visa_status             ENUM('not_started','material_sent','answered','complete') DEFAULT 'not_started'

  -- Timestamps
  form_submitted_at       DATETIME                -- SLA countdown starts here
  first_contacted_at      DATETIME NULL           -- set when CS agent logs first contact
  created_at, updated_at

-- Full audit trail of every stage movement
student_stage_logs
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  student_id          BIGINT UNSIGNED REFERENCES students(id)
  from_stage          VARCHAR(50)
  to_stage            VARCHAR(50)
  changed_by          BIGINT UNSIGNED REFERENCES users(id)
  changed_at          DATETIME

-- Agent notes on students
notes
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  student_id          BIGINT UNSIGNED REFERENCES students(id)
  author_id           BIGINT UNSIGNED REFERENCES users(id)
  body                TEXT
  created_at, updated_at

-- WhatsApp message templates (managed by admin, used by agents)
message_templates
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  name                VARCHAR(255)
  category            ENUM('exam_reminder','visa_material','welcome','payment','followup')
  body                TEXT
  active              BOOLEAN DEFAULT TRUE
  created_by          BIGINT UNSIGNED REFERENCES users(id)
  created_at, updated_at

-- Log of every template sent to a student
message_logs
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  student_id          BIGINT UNSIGNED REFERENCES students(id)
  sent_by             BIGINT UNSIGNED REFERENCES users(id)
  template_id         BIGINT UNSIGNED REFERENCES message_templates(id) NULL
  channel             ENUM('whatsapp','email') DEFAULT 'whatsapp'
  sent_at             DATETIME

-- SLA limits per pipeline stage (one row per stage)
sla_settings
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  stage               VARCHAR(50) UNIQUE  -- matches pipeline_stage enum values
  days_limit          INT                 -- max days before flagged overdue
  updated_by          BIGINT UNSIGNED REFERENCES users(id)
  updated_at          DATETIME

-- In-app notifications for CS agents
notifications
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  user_id             BIGINT UNSIGNED REFERENCES users(id)
  type                ENUM('new_assignment','sla_breach','daily_digest')
  student_id          BIGINT UNSIGNED REFERENCES students(id) NULL
  read_at             DATETIME NULL
  created_at, updated_at
```

**Default SLA seed data:**
```
first_contact → 2 days
exam          → 5 days
payment       → 3 days
visa          → 3 days
```

---

## 6. GOOGLE FORMS WEBHOOK

### 6.1 Overview
When a **sales consultant** (Wagner, Talita, Gabriel, etc.) submits the student intake Google Form, a Google Apps Script fires automatically and sends a POST request to the Laravel webhook endpoint.

**This is the ONLY way students enter the system in Phase 1.** The student does not fill the form — the sales consultant fills it on the student's behalf.

### 6.2 Laravel Webhook Endpoint

```
POST /api/webhook/form
Headers:
  Content-Type: application/json
  Authorization: Bearer {WEBHOOK_SECRET}   ← validated against WEBHOOK_SECRET in .env
```

**The form is called "CI Sales Dispatch Form"** and is submitted by the sales consultant (not the student).

**Exact Google Form field names and expected payload:**
```json
{
  "E-mail":                                  ["joao@email.com"],
  "Sales Advisor":                           ["Wagner Marinho"],
  "Student full name":                       ["João Silva"],
  "Product type":                            ["Higher Education"],
  "Course":                                  ["Business Studies — QQI Level 6"],
  "University":                              ["Dublin Business School"],
  "Intake":                                  ["September"],
  "Sales price without scholarship":         ["9500"],
  "Sales price with scholarship (If Applicable)": ["8500"],
  "Student WhatsApp number":                 ["+353 87 123 4567"],
  "Pending documents and add informations":  ["Awaiting passport copy"],
  "If REAPPLICATION:":                       ["Keep previous application"]
}
```

**Product type possible values (from form radio buttons):**
- `"Higher Education"`
- `"First Visa"`
- `"Reapplication"`
- `"Insurance"`
- `"Emergencial Tax"`
- `"Learn Protection"`
- `"Outro: [free text]"` ← when "Outro" selected, value includes the custom text

**Intake possible values:**
- `"January/February"`
- `"September"`
- `"Outro: [free text]"` ← custom intake text

**Reapplication field possible values (only present if product is Reapplication):**
- `"Keep previous application"`
- `"Cancel previous application"`

**IMPORTANT notes on the payload:**
- Google Forms wraps all values in arrays — always access `value[0]`
- The `"Sales Advisor"` field is what drives CS agent auto-assignment — it must match a name in the `sales_consultants` table exactly (case-insensitive, trimmed)
- The `"E-mail"` field is the **student's email** entered by the consultant
- There is no phone number field in the form — the CS agent links the WhatsApp phone manually on first contact
- Fields not filled in by the consultant arrive as `[""]` — treat as null
- The `"If REAPPLICATION:"` field only appears when the product type is Reapplication; otherwise it is absent from the payload

### 6.3 Laravel Webhook Controller Logic

```
WebhookController@handleForm:

  1. Validate bearer token against env('WEBHOOK_SECRET') — return 401 if missing or wrong

  2. Parse payload — for each field take [0] of the array, trim whitespace, treat "" as null

  3. Extract fields:
       student_email     = payload["E-mail"][0]
       sales_advisor     = payload["Sales Advisor"][0]
       student_name      = payload["Student full name"][0]
       product_raw       = payload["Product type"][0]
       course            = payload["Course"][0]           or null
       university        = payload["University"][0]       or null
       intake_raw        = payload["Intake"][0]           or null
       price             = payload["Sales price without scholarship"][0]            or null
       price_scholarship = payload["Sales price with scholarship (If Applicable)"][0] or null
       pending_docs      = payload["Pending documents and add informations"][0]     or null
       reapp_raw         = payload["If REAPPLICATION:"][0] or null

  4. Map product_type:
       "Higher Education"  → 'higher_education'
       "First Visa"        → 'first_visa'
       "Reapplication"     → 'reapplication'
       "Insurance"         → 'insurance'
       "Emergencial Tax"   → 'emergencial_tax'
       "Learn Protection"  → 'learn_protection'
       starts with "Outro" → 'other', store remainder in product_type_other

  5. Map intake:
       "January/February"  → store as-is
       "September"         → store as-is
       starts with "Outro" → store the custom text

  6. Map reapplication_action (only if product_type = 'reapplication'):
       "Keep previous application"   → 'keep_previous'
       "Cancel previous application" → 'cancel_previous'
       otherwise                     → null

  7. Find or create SalesConsultant:
       match by LOWER(TRIM(name)) = LOWER(TRIM(sales_advisor))
       if not found: create new SalesConsultant with that name

  8. Look up AssignmentRule for sales_consultant_id → get cs_agent_id
       if no rule found: cs_agent_id = null (will appear in admin unassigned queue)

  9. Extract and normalise WhatsApp phone:
       raw_phone    = payload["Student WhatsApp number"][0]   or null
       whatsapp_phone = PhoneNormaliser::normalise(raw_phone) if raw_phone else null

  10. Create Student record with all mapped fields:
       pipeline_stage    = 'first_contact'
       form_submitted_at = now()
       whatsapp_phone    = normalised phone from step 9 (may be null if field left blank)

  11. Create Notification for assigned CS agent (if cs_agent_id is not null):
        type = 'new_assignment', student_id = new student id

  12. Return HTTP 200 OK
```

### 6.4 Google Apps Script (set up once per Google Form)

```javascript
function onFormSubmit(e) {
  var payload = e.namedValues;
  var url = 'https://app.ciireland.ie/api/webhook/form';
  var secret = 'YOUR_WEBHOOK_SECRET'; // store in Script Properties

  UrlFetchApp.fetch(url, {
    method: 'post',
    contentType: 'application/json',
    headers: { 'Authorization': 'Bearer ' + secret },
    payload: JSON.stringify(payload),
    muteHttpExceptions: true
  });
}
// Trigger: Script Editor → Triggers → onFormSubmit → On form submit
```

---

## 7. LARAVEL API ROUTES

All extension routes require Sanctum API token auth (`auth:sanctum` middleware).
All dashboard routes require session auth + `role:admin` middleware.

### 7.1 Extension API Routes (prefix: `/api/`)

```
POST   /api/auth/login                → issue Sanctum token for CS agent
POST   /api/auth/logout               → revoke token

GET    /api/students/match?phone=X    → find student by normalised whatsapp_phone
                                        returns student + pipeline_stage + sla_status
GET    /api/students/search?q=X       → search by name/email (for first-contact linking)
POST   /api/students/{id}/link-phone  → save whatsapp_phone to student (first-contact link)
GET    /api/students/pipeline         → return agent's students grouped by stage with SLA flags
PATCH  /api/students/{id}/stage       → advance stage (logs to student_stage_logs)
PATCH  /api/students/{id}/exam        → update exam_date and/or exam_result
PATCH  /api/students/{id}/payment     → update payment_status
PATCH  /api/students/{id}/visa        → update visa_status

POST   /api/notes                     → create note for a student
GET    /api/notes/{student_id}        → get all notes for a student

GET    /api/templates                 → list active message templates for agent's use
POST   /api/message-logs              → log that agent sent a template to a student

GET    /api/notifications             → get unread notifications for authed agent
PATCH  /api/notifications/{id}/read   → mark notification as read
GET    /api/sla-settings              → return current SLA day limits (for extension badge logic)
```

### 7.2 Webhook Route (no auth middleware — validated manually by secret)

```
POST   /api/webhook/form              → Google Forms submission handler
```

### 7.3 Dashboard Web Routes (prefix: `/admin/`, session auth + admin role)

```
GET    /admin/dashboard               → overview stats
GET    /admin/students                → paginated student list with filters
GET    /admin/students/{id}           → student detail + full audit log
PATCH  /admin/students/{id}/reassign  → reassign student to different CS agent

GET    /admin/agents                  → CS agent list with pipeline badge counts
GET    /admin/assignment-rules        → list rules
POST   /admin/assignment-rules        → create rule
PUT    /admin/assignment-rules/{id}   → update rule
DELETE /admin/assignment-rules/{id}   → delete rule

GET    /admin/sla-settings            → list SLA settings
PUT    /admin/sla-settings            → bulk update all SLA settings

GET    /admin/templates               → list all templates
POST   /admin/templates               → create template
PUT    /admin/templates/{id}          → update template
PATCH  /admin/templates/{id}/toggle   → activate/deactivate

GET    /admin/reports                 → reporting page (conversion rates, response times)
```

---

## 8. SLA LOGIC

The SLA system calculates whether a student is overdue based on their current pipeline stage and `form_submitted_at` or stage entry time.

### 8.1 SLA Calculation (run in a service class: `SlaService`)

```
For stage 'first_contact':
  deadline = student.form_submitted_at + sla_settings['first_contact'].days_limit days
  overdue  = now() > deadline AND student.first_contacted_at IS NULL

For stages 'exam', 'payment', 'visa':
  Use the timestamp from student_stage_logs where to_stage = current stage
  deadline = stage_entry_time + sla_settings[stage].days_limit days
  overdue  = now() > deadline

'complete' stage: never overdue
```

### 8.2 API Response Shape (student pipeline endpoint)

```json
{
  "first_contact": [
    {
      "id": 1,
      "name": "João Silva",
      "course": "Business English B2",
      "sales_consultant": "Wagner Marinho",
      "pipeline_stage": "first_contact",
      "sla_overdue": true,
      "sla_days_remaining": -3,
      "form_submitted_at": "2024-03-25T10:00:00Z",
      "whatsapp_phone": null
    }
  ],
  "exam": [...],
  "payment": [...],
  "visa": [...],
  "complete": [...]
}
```

---

## 9. CHROME EXTENSION

### 9.1 Manifest (manifest.json)

```json
{
  "manifest_version": 3,
  "name": "CI Ireland CS Platform",
  "version": "1.0.0",
  "description": "Customer Success CRM sidebar for WhatsApp Web",
  "permissions": ["storage", "activeTab"],
  "host_permissions": ["https://web.whatsapp.com/*"],
  "content_scripts": [
    {
      "matches": ["https://web.whatsapp.com/*"],
      "js": ["content.js"],
      "css": ["sidebar.css"],
      "run_at": "document_idle"
    }
  ],
  "action": {
    "default_popup": "popup.html",
    "default_icon": "icon48.png"
  }
}
```

### 9.2 File Structure

```
extension/
  manifest.json
  content.js          ← injected into WhatsApp Web
  sidebar.css         ← sidebar styles
  popup.html          ← login screen (shown when clicking extension icon)
  popup.js
  api.js              ← all fetch calls to Laravel API
  icon48.png
  icon128.png
```

### 9.3 content.js — Core Logic

```javascript
// 1. Wait for WhatsApp Web to fully load
// 2. Inject sidebar div into the DOM (right side of WhatsApp layout)
// 3. On page load and on every contact change (MutationObserver on chat header):
//    a. Extract the phone number from the open chat header
//    b. Normalise phone number (strip spaces, dashes, brackets; ensure + prefix)
//    c. Call GET /api/students/match?phone={normalised_phone}
//    d. If match found → render StudentCard component
//    e. If no match → render LinkStudentPanel (search + link UI)
// 4. Poll GET /api/students/pipeline every 60 seconds to refresh badge counts on tabs
// 5. All API calls include: Authorization: Bearer {token from chrome.storage.local}
```

### 9.4 Phone Number Normalisation

Critical: Phone numbers must be normalised consistently before storage and before matching.

```javascript
function normalisePhone(raw) {
  // Remove all non-digit characters except leading +
  let digits = raw.replace(/[^\d+]/g, '');
  // Handle Irish numbers: 083... → +353 83...
  if (digits.startsWith('0') && !digits.startsWith('00')) {
    digits = '+353' + digits.slice(1);
  }
  // Handle 00353 prefix
  if (digits.startsWith('00353')) {
    digits = '+' + digits.slice(2);
  }
  return digits;
}
```

Run this normalisation both when saving `whatsapp_phone` to the DB and when querying by phone.

### 9.5 Extension UI Sections

**Tab Bar (always visible at top of sidebar):**
```
[ New (4🔴) | Exam (7) | Pay (3🔴) | Visa (5) | Done ]
```
- Red badge = one or more students are overdue in this tab
- Green/neutral badge = students present, all within SLA
- Number = count of students in that stage

**Student Card (shown when WhatsApp contact matches a student):**
```
┌──────────────────────────────────────────┐
│ João Silva                               │
│ Product:    Higher Education             │
│ Course:     Business Studies QQI L6      │
│ University: Dublin Business School       │
│ Intake:     September                    │
│ Price:      €9,500  (€8,500 w/schol.)   │
│ Sales:      Wagner Marinho               │
│ Stage:      English Exam                 │
│ 🔴 Overdue — exam not booked (3d)       │
├──────────────────────────────────────────┤
│ Pending: Awaiting passport copy          │
├──────────────────────────────────────────┤
│ [Book Exam]  [Send Reminder]  [Note]     │
├──────────────────────────────────────────┤
│ Notes:                                   │
│ "Called 3x — replied this morning"       │
│                                          │
│ [ Move to Payment >> ]                   │
└──────────────────────────────────────────┘
```

Fields displayed on the student card (all sourced from the dispatch form):
- `name` — Student full name
- `product_type` — formatted as human-readable label
- `course` — as entered by consultant
- `university` — university name
- `intake` — intake period
- `sales_price` + `sales_price_scholarship` — shown together if scholarship applies
- `sales_consultant.name` — which consultant submitted the form
- `pipeline_stage` — current CRM stage
- `pending_documents` — shown as a warning if not null
- SLA overdue indicator — calculated by SlaService
- `reapplication_action` — shown only if product_type = 'reapplication'

**Link Student Panel (shown when no match found):**
```
No student matched this contact.
Search: [___________________]
Results: [ João Silva — joao@email.com ] ← click to link
```

**Pipeline Tab Content (shown when browsing pipeline, not in a matched chat):**
- List of student cards sorted by: overdue first, then by days remaining ASC
- Each card shows: name, course, SLA status, quick action button

### 9.6 Extension Auth Flow

1. Agent clicks extension icon → popup.html shown
2. Agent enters email + password → POST /api/auth/login
3. Token stored in `chrome.storage.local` as `{ token, agent_name, agent_id }`
4. All subsequent API calls use this token
5. Logout button clears storage and revokes token

---

## 10. WHATSAPP MESSAGING — IMPORTANT

**There is NO WhatsApp API integration.**

Agents use their own WhatsApp numbers in WhatsApp Web as normal. The extension does not send messages programmatically.

When an agent clicks "Send Reminder" or "Send Visa Pack" in the extension:
1. The template text is **copied to clipboard** (or pre-filled into the WhatsApp message input box using DOM manipulation)
2. The agent reviews and hits send manually
3. After sending, the extension logs the action via `POST /api/message-logs`

**DOM injection for pre-filling WhatsApp message input:**
```javascript
function prefillWhatsAppMessage(text) {
  // Target the WhatsApp message input
  const input = document.querySelector('[data-testid="conversation-compose-box-input"]');
  if (input) {
    // Use execCommand or InputEvent to set the value
    input.focus();
    document.execCommand('insertText', false, text);
  }
}
```

Note: WhatsApp Web's DOM selectors may change. Use `data-testid` attributes where available as they are more stable.

---

## 11. DAILY MORNING DIGEST

Since there is no WhatsApp API, the morning digest is **not** sent via WhatsApp programmatically.

**Instead:**
- A Laravel scheduled job runs every morning at 08:30 (configurable)
- It calculates each CS agent's pending student counts per stage
- It sends a **summary email** to each active CS agent
- The email subject: `CI Ireland — Your morning student summary`
- Use Laravel's built-in `Mail` facade with a Blade email template

**Scheduled job (app/Console/Kernel.php or routes/console.php in Laravel 11):**
```php
Schedule::job(new SendMorningDigestJob)->dailyAt('08:30');
```

**Digest email content per agent:**
```
Good morning {name}!

Here is your student summary for today:

📋 First Contact:  {n} students  ({x} overdue)
📝 English Exam:   {n} students  ({x} overdue)
💳 Payment:        {n} students  ({x} overdue)
🛂 Visa:           {n} students  ({x} overdue)
✅ Complete:       {n} students

Log in to the platform or open your WhatsApp extension to get started.
```

---

## 12. ADMINLTE DASHBOARD PAGES

### 12.1 Layout
- Use AdminLTE 3 with Laravel Blade layouts
- Base layout: `resources/views/layouts/admin.blade.php`
- Sidebar navigation links: Dashboard, Students, Agents, Assignment Rules, SLA Settings, Templates, Reports

### 12.2 Dashboard Page (`/admin/dashboard`)
- Team overview table: one row per CS agent, columns = pipeline stages, cells = badge count (red if any overdue)
- Total students per stage (global)
- Recent activity feed (last 10 stage changes)

### 12.3 Students Page (`/admin/students`)
- Paginated table: name, email, course, sales consultant, assigned agent, stage, SLA status, actions
- Filters: stage, assigned agent, sales consultant, overdue only
- Search by name/email
- Reassign button per row → modal to select new CS agent

### 12.4 Assignment Rules Page (`/admin/assignment-rules`)
- Table: Sales Consultant | CS Agent | Actions (Edit, Delete)
- "Add Rule" button → inline form: select consultant from dropdown, select CS agent from dropdown
- Warn if consultant already has a rule

### 12.5 SLA Settings Page (`/admin/sla-settings`)
- Simple form: one number input per stage
- Single "Save All" button
- Shows last updated by / when

### 12.6 Templates Page (`/admin/templates`)
- Table: Name, Category, Active toggle, Edit, Delete
- Create/Edit form: name, category dropdown, body textarea (supports {student_name} placeholder)
- Preview of rendered template with placeholder substitution

### 12.7 Reports Page (`/admin/reports`)
- Conversion rate: % of students who reached each stage
- Average days per stage before advancing
- Agent performance: avg response time (first_contact stage)
- Filter by date range

---

## 13. LARAVEL PROJECT STRUCTURE

```
app/
  Http/
    Controllers/
      Api/
        AuthController.php
        StudentController.php
        NoteController.php
        TemplateController.php
        NotificationController.php
        WebhookController.php
      Admin/
        DashboardController.php
        StudentController.php
        AssignmentRuleController.php
        SlaSettingController.php
        TemplateController.php
        ReportController.php
  Models/
    User.php
    Student.php
    SalesConsultant.php
    AssignmentRule.php
    StudentStageLog.php
    Note.php
    MessageTemplate.php
    MessageLog.php
    SlaSetting.php
    Notification.php
  Services/
    SlaService.php             ← calculates overdue status
    AssignmentService.php      ← resolves consultant → agent
    PhoneNormaliser.php        ← consistent phone normalisation
  Jobs/
    SendMorningDigestJob.php
  Mail/
    MorningDigestMail.php

resources/
  views/
    layouts/
      admin.blade.php
    admin/
      dashboard.blade.php
      students/index.blade.php
      students/show.blade.php
      assignment-rules/index.blade.php
      sla-settings/index.blade.php
      templates/index.blade.php
      reports/index.blade.php
    emails/
      morning-digest.blade.php

database/
  migrations/
    (one migration file per table, in dependency order)
  seeders/
    DatabaseSeeder.php
    SlaSettingSeeder.php        ← seeds default SLA values
    UserSeeder.php              ← seeds admin + test CS agent accounts
    SalesConsultantSeeder.php   ← seeds Wagner, Talita, Gabriel, etc.
    AssignmentRuleSeeder.php    ← seeds default assignment rules

routes/
  api.php                      ← extension + webhook routes
  web.php                      ← admin dashboard routes

extension/
  manifest.json
  content.js
  sidebar.css
  popup.html
  popup.js
  api.js
```

---

## 14. ENVIRONMENT VARIABLES (.env)

```env
APP_NAME="CI Ireland CS Platform"
APP_URL=https://app.ciireland.ie

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ciireland_cs
DB_USERNAME=
DB_PASSWORD=

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database

WEBHOOK_SECRET=                    # random string — must match Google Apps Script

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=cs@ciireland.ie
MAIL_FROM_NAME="CI Ireland CS Team"

DIGEST_TIME=08:30                  # morning digest send time
```

---

## 15. PHASE 1 — BUILD ORDER

Build in this exact order. Each step should be working and tested before moving to the next.

```
Step 1:  Laravel project setup
         → install Laravel 11, configure .env, connect MySQL

Step 2:  Database migrations
         → all tables in dependency order (users first, students last)

Step 3:  Database seeders
         → SLA defaults, admin user, sales consultants, assignment rules

Step 4:  Sanctum auth
         → POST /api/auth/login and /api/auth/logout

Step 5:  WebhookController
         → POST /api/webhook/form — full logic including auto-assignment

Step 6:  SlaService
         → calculateOverdue(Student) → returns bool + days_remaining

Step 7:  StudentController (API)
         → /api/students/match, /api/students/pipeline, /api/students/search
         → /api/students/{id}/link-phone, /api/students/{id}/stage

Step 8:  Notes, Templates, MessageLogs, Notifications API endpoints

Step 9:  Chrome extension scaffold
         → manifest.json, inject sidebar into WhatsApp Web DOM

Step 10: Extension auth (popup login → token storage)

Step 11: Extension phone matching → student card render

Step 12: Extension pipeline tabs with badge counts

Step 13: Extension student card actions
         → stage move, note add, template prefill into WhatsApp input

Step 14: AdminLTE layout + dashboard page

Step 15: Admin students page (list + reassign)

Step 16: Admin assignment rules page

Step 17: Admin SLA settings page

Step 18: Admin templates page

Step 19: Morning digest job + mail template

Step 20: Admin reports page
```

---

## 16. KEY BUSINESS RULES

These rules must be enforced at the API level, not just the UI level:

1. **A CS agent can only see their own assigned students** — enforced in all API queries with `where('assigned_cs_agent_id', auth()->id())`
2. **Admins can see all students** — no filter applied for admin role
3. **Stage can only move forward** — the system should warn (but not block) if moving backwards
4. **Every stage move must create a `student_stage_logs` record** — never update `pipeline_stage` without also inserting a log
5. **`whatsapp_phone` must be normalised** before saving to DB — use `PhoneNormaliser::normalise($raw)`
6. **Webhook secret must be validated** on every incoming webhook request — return 401 if missing or wrong
7. **Assignment rule lookup is case-insensitive** — always trim and lowercase when matching consultant names
8. **If no assignment rule exists** for a consultant — create student with `assigned_cs_agent_id = null` and add to an "Unassigned" queue visible to admins only

---

## 17. TESTING CHECKLIST (Phase 1)

Before declaring Phase 1 complete, verify:

- [ ] Google Form submission → student created in DB → correct CS agent assigned
- [ ] CS agent logs in via extension popup → token issued and stored
- [ ] Opening a WhatsApp chat with a linked student → student card loads in sidebar
- [ ] Student created from form with WhatsApp number → auto-matched when agent opens their chat
- [ ] Student created from form with blank WhatsApp number → link panel shown, search works
- [ ] Manual phone link → phone saved and normalised, card auto-loads on next open
- [ ] Agent can correct a wrong phone number via the link panel even on already-matched students
- [ ] Stage move from extension → `pipeline_stage` updated + `student_stage_logs` row created
- [ ] SLA overdue calculation correct — student with `form_submitted_at` > 2 days ago shows as overdue
- [ ] Pipeline tabs show correct badge counts, red when overdue students present
- [ ] Admin can log into dashboard and see all students
- [ ] Admin can change assignment rules and new form submissions respect updated rules
- [ ] Admin can reassign a student to a different CS agent
- [ ] Admin can update SLA settings — extension reflects new values within 60 seconds (next poll)
- [ ] Webhook rejects requests with wrong or missing bearer token

---

## 18. CIGO INTEGRATION (Phase 4 — Future)

CIGO is the company's main CRM system containing lead and student data. Integration is deferred to Phase 4.

When the time comes:
- Investigate available GET endpoints from CIGO API
- Build a Laravel sync service that pulls student data on a schedule
- Reconcile CIGO records with existing `students` table (match by email)
- Gradually reduce reliance on Google Forms as the primary intake method

Do not attempt CIGO integration until Phases 1–3 are fully stable.

---

## 19. QUICK REFERENCE — PIPELINE STAGES

| Stage Key | Display Name | Badge Emoji |
|---|---|---|
| `first_contact` | First Contact | 📋 |
| `exam` | English Exam | 📝 |
| `payment` | Payment | 💳 |
| `visa` | Visa Assistance | 🛂 |
| `complete` | Enrolled / Complete | ✅ |

---

## 20. BRAND & DESIGN

**CI Ireland colour palette (use throughout dashboard and extension):**

| Name | Hex | Usage |
|---|---|---|
| Navy | `#0D2137` | Primary backgrounds, headers |
| Teal | `#0E7C6B` | Accent, buttons, active states |
| Light Teal | `#E6F4F1` | Card backgrounds, subtle fills |
| White | `#FFFFFF` | Content backgrounds |
| Light Grey | `#F4F6F8` | Page backgrounds |
| Text | `#1C2833` | Body text |
| Mid Grey | `#8FA3B1` | Muted labels, captions |
| Gold | `#F0A500` | Warnings, highlights, overdue accents |
| Red | `#E53E3E` | Overdue badges, error states |

**Typography:** Arial throughout (universally available, matches CI Ireland website)

**Extension sidebar width:** 340px, fixed right side of WhatsApp Web layout
**Extension sidebar background:** `#0D2137` (navy) — stands out from WhatsApp's white UI

---

*End of specification. Build Phase 1 first. Ask for clarification before assuming anything not covered here.*
