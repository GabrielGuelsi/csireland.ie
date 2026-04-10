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
  <p class="meta">CI Ireland CS Platform Chrome Extension &mdash; Last updated: April 2026</p>

  <h2>1. Overview</h2>
  <p>
    The <strong>CI Ireland CS Platform</strong> is a Chrome extension used exclusively by CI Ireland's internal
    Customer Success team. It adds a sidebar to WhatsApp Web that allows agents to manage student records
    and send communications without leaving the chat interface.
  </p>
  <p>This extension is not available to the general public and is intended solely for authorised CI Ireland staff.</p>

  <h2>2. Data Collected</h2>
  <p>The extension collects and stores the following data on the user's device:</p>
  <ul>
    <li>
      <strong>Authentication token:</strong> A session token issued by the CI Ireland backend
      (<code>cs.ciireland.ie</code>) upon login. Stored locally using Chrome's
      <code>chrome.storage.local</code> API so the agent remains logged in between sessions.
      This token is never shared with any third party.
    </li>
    <li>
      <strong>Agent preferences:</strong> The agent's selected display language, stored locally
      in <code>chrome.storage.local</code>.
    </li>
  </ul>
  <p>The extension does <strong>not</strong> read, collect, or transmit WhatsApp message content.</p>
  <p>The extension does <strong>not</strong> track browsing history or any activity outside of WhatsApp Web.</p>

  <h2>3. Data Transmitted</h2>
  <p>
    All API communication occurs exclusively between the extension and the CI Ireland backend server at
    <strong>cs.ciireland.ie</strong>, which is operated by CI Ireland. No data is sent to any third-party
    service or analytics platform.
  </p>
  <p>Data transmitted includes:</p>
  <ul>
    <li>The agent's authentication token (in request headers)</li>
    <li>Student record updates made by the agent (status changes, notes, follow-up dates)</li>
    <li>Phone number lookups to match an open WhatsApp chat to a student record</li>
  </ul>

  <h2>4. Data Retention</h2>
  <p>
    The authentication token is stored until the agent explicitly logs out, at which point it is
    deleted from local storage. No other persistent data is stored by the extension on the device.
  </p>

  <h2>5. Third Parties</h2>
  <p>
    We do not sell, transfer, or disclose user data to any third party. Data is used solely to
    operate the CI Ireland Customer Success platform.
  </p>

  <h2>6. Contact</h2>
  <p>
    For any questions regarding this privacy policy, please contact us at
    <a href="mailto:info@ciireland.ie">info@ciireland.ie</a>.
  </p>

</body>
</html>
