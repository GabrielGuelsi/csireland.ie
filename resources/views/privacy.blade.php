<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Privacy Policy — CI Ireland CS Platform</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 760px; margin: 60px auto; padding: 0 24px; color: #333; line-height: 1.7; }
    h1 { color: #e85d04; font-size: 28px; margin-bottom: 4px; }
    h2 { font-size: 18px; margin-top: 36px; color: #111; }
    p, li { font-size: 15px; }
    ul { padding-left: 20px; }
    .meta { color: #888; font-size: 13px; margin-bottom: 40px; }
    a { color: #e85d04; }
  </style>
</head>
<body>

  <h1>Privacy Policy</h1>
  <p class="meta">CI Ireland CS Platform Chrome Extension &mdash; Last updated: 22 April 2026</p>

  <h2>1. Overview</h2>
  <p>
    The <strong>CI Ireland CS Platform</strong> is a Chrome extension used exclusively by CI Ireland's internal
    Customer Success team. It adds a sidebar to WhatsApp Web that allows agents to manage student records
    and send communications without leaving the chat interface.
  </p>
  <p>This extension is not available to the general public and is intended solely for authorised CI Ireland staff.</p>

  <h2>2. Data Collection</h2>
  <p>The extension collects the following data in the course of normal operation:</p>
  <ul>
    <li>
      <strong>Login credentials:</strong> The agent's email address and password are transmitted to the CI Ireland
      backend (<code>cs.ciireland.ie</code>) over HTTPS when the agent logs in. Credentials are used solely to
      authenticate the session and are not retained on the device after login.
    </li>
    <li>
      <strong>Authentication token:</strong> A session token issued by the CI Ireland backend
      (<code>cs.ciireland.ie</code>) upon login, used to authenticate subsequent API requests. Stored on the user's
      device via Chrome's <code>chrome.storage.local</code> API.
    </li>
    <li>
      <strong>Agent identity:</strong> The agent's name and internal user ID, used to personalise the sidebar interface.
    </li>
    <li>
      <strong>Display language preference:</strong> The agent's selected language for the extension UI.
    </li>
    <li>
      <strong>API server URL:</strong> The backend server address the extension communicates with.
    </li>
  </ul>
  <p>The extension does <strong>not</strong> read, collect, or transmit WhatsApp message content.</p>
  <p>The extension does <strong>not</strong> track browsing history or any activity outside of WhatsApp Web.</p>
  <p>The extension does <strong>not</strong> use cookies, analytics services, or advertising trackers.</p>

  <h2>2a. Student Data Displayed by the Extension</h2>
  <p>
    In addition to the data listed in Section 2 above, the extension displays personal data about CI Ireland
    students (name, email address, phone number, date of birth, course, intake, visa status, visa expiry date,
    exam dates and results, payment status, and pricing information) that is retrieved from the CI Ireland
    backend on demand.
  </p>
  <p>
    This student data is received solely to enable authorised CI Ireland staff to manage the student's case.
    It is displayed for the duration of the active browser session only, is not persisted by the extension
    between sessions, and is not transmitted to any third party.
  </p>

  <h2>3. Data Processing</h2>
  <p>
    The extension processes data solely to provide its core functionality &mdash; matching WhatsApp contacts
    to student records and enabling agents to manage those records. Specifically:
  </p>
  <ul>
    <li>
      <strong>Phone number matching:</strong> When an agent opens a WhatsApp chat, the extension reads the
      contact's phone number from the chat header and sends it to the CI Ireland backend to look up the
      corresponding student record. The phone number is not stored locally.
    </li>
    <li>
      <strong>Student record management:</strong> The extension displays student information retrieved from
      the backend and submits updates (status changes, notes, follow-up dates, priority changes) as directed
      by the agent.
    </li>
    <li>
      <strong>Message templating:</strong> The extension retrieves pre-approved message templates from the
      backend and inserts the selected template text into the WhatsApp message input field. It does not send
      messages automatically.
    </li>
  </ul>
  <p>All data processing is performed on behalf of CI Ireland for the purpose of student relationship management.</p>

  <h2>4. Data Storage</h2>
  <p>
    Data stored locally on the device is limited to the authentication token, agent identity, language
    preference, and server URL (items listed in Section 2). Login credentials are not retained on the
    device after login. This locally-stored data is held using Chrome's
    <code>chrome.storage.local</code> API and persists until:
  </p>
  <ul>
    <li>The agent explicitly logs out (which clears all stored data), or</li>
    <li>The extension is uninstalled from the browser.</li>
  </ul>
  <p>
    Student records, notes, and all other business data are stored exclusively on CI Ireland's backend
    server (<code>cs.ciireland.ie</code>), which is hosted on a private VPS operated by CI Ireland.
    The extension does not maintain a local copy of this data between sessions.
  </p>

  <h2>5. Data Sharing and Disclosure</h2>
  <p>
    We do <strong>not</strong> sell, rent, trade, or otherwise transfer any user data to third parties.
  </p>
  <p>
    All data transmitted by the extension is sent exclusively to CI Ireland's own backend server at
    <code>cs.ciireland.ie</code>. No data is sent to any third-party service, analytics platform,
    advertising network, or external API.
  </p>
  <p>
    Data may only be disclosed if required by law or a valid legal process (e.g. a court order).
  </p>

  <h2>6. Data Security</h2>
  <p>
    All communication between the extension and the backend server occurs over HTTPS (TLS-encrypted connections).
    API requests are authenticated using a bearer token with a limited expiry period. The extension enforces a
    strict Content Security Policy and restricts its network access to authorised domains only.
  </p>

  <h2>7. User Rights</h2>
  <p>
    Authorised users (CI Ireland staff) may request access to, correction of, or deletion of their personal
    data at any time by contacting CI Ireland. Logging out of the extension immediately removes all locally
    stored data from the device.
  </p>

  <h2>8. Changes to This Policy</h2>
  <p>
    We may update this Privacy Policy from time to time. Any changes will be reflected on this page with an
    updated revision date. Continued use of the extension after changes constitutes acceptance of the revised policy.
  </p>

  <h2>9. Contact</h2>
  <p>
    For any questions regarding this privacy policy or how your data is handled, please contact us at
    <a href="mailto:info@ciireland.ie">info@ciireland.ie</a>.
  </p>

</body>
</html>
