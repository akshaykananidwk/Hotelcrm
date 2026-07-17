# OTA Web Automation (for channels without an API)

Some OTAs — notably **MakeMyTrip** and **Goibibo** — do not grant API access to
smaller properties. For these, HotelCRM can drive the **web extranet** directly,
using the hotel's own login, in one of two modes:

| Mode | How it works | Best for |
|------|--------------|----------|
| **Web session** (`web_session`) | Replays the login form + internal requests with cURL, keeping the auth cookie jar. Pure PHP, headless, cheap. | Plain-HTML extranets, JSON/AJAX endpoints |
| **Browser** (`browser`) | Drives a real (headless) Chrome via the WebDriver protocol, filling the actual UI. | JavaScript-heavy / bot-protected portals |

Both run inside the background queue worker (`cli/worker.php`) with retry/backoff.

> ⚠️ **Read this first.** Automating an OTA extranet may be restricted by that
> OTA's Terms of Service, and it can break whenever the site changes or presents
> a CAPTCHA / OTP / 2FA. Use it only for **your own** property account, at your
> own risk. It is a pragmatic bridge — the durable path remains an official API
> or an approved channel-manager connection. Credentials are encrypted at rest
> (AES-256-GCM) via `App\Core\Crypto`.

---

## Architecture

```
ChannelManager ──resolves──> MakeMyTripConnector (extends WebAutomationConnector)
                                    │
             ┌──────────────────────┴───────────────────────┐
     WebSession (cURL cookie jar)                 BrowserDriver (W3C WebDriver)
                                                          │
                                                   AutomationFlow
                                          (config-driven step runner)
```

- `App\Services\Automation\WebSession` — cURL client + HTML form/token parsing.
- `App\Services\Automation\BrowserDriver` — dependency-free W3C WebDriver client
  (talks to `chromedriver` or a Selenium hub). No Node/Java in the app.
- `App\Services\Automation\AutomationFlow` — runs a JSON step list with
  `{{placeholder}}` interpolation; supports `navigate, type, click, waitFor,
  selectOption, assertUrlContains, assertText, extract, extractAll, script,
  screenshot, sleep`.
- `App\Services\OTA\WebAutomationConnector` / `MakeMyTripConnector` — map the
  connector's push/pull methods onto named flows (`login`, `push_inventory`,
  `push_rates`, `pull_bookings`).

**Flows are configuration, not code.** Every extranet's DOM differs and changes,
so the login/inventory/booking selectors live in the channel's encrypted
`automation_config`, editable in the admin — no code change to adapt.

---

## Server setup (browser mode)

Install a Chrome/Chromium + a matching **chromedriver** on the app host and keep
the driver running (e.g. via systemd):

```bash
chromedriver --port=9515
```

> The chromedriver **major version must match** the installed Chrome/Chromium.
> Get matching builds from Chrome for Testing:
> https://googlechromelabs.github.io/chrome-for-testing/

---

## Configuring a channel

1. **Settings → OTA →** open the channel (e.g. MakeMyTrip).
2. Set **Connection mode** to *Browser automation* (or *Web session*).
3. Enter the extranet **username / password** (encrypted on save).
4. In **Automation config (JSON)** provide the driver + real selectors, e.g.:

```json
{
  "mode": "browser",
  "driver_url": "http://127.0.0.1:9515",
  "browser_binary": "/usr/bin/chromium",
  "headless": true,
  "login_url": "https://ingommt.makemytrip.com/",
  "vars": {
    "post_login_url_contains": "dashboard",
    "inventory_url": "https://ingommt.makemytrip.com/.../inventory",
    "avail_field": "#availability-input",
    "stop_sell_field": "#stop-sell-select",
    "inventory_save": "button.save-inventory",
    "bookings_url": "https://ingommt.makemytrip.com/.../bookings",
    "booking_row": ".booking-row",
    "booking_ref_cell": ".booking-row .ref",
    "booking_guest_cell": ".booking-row .guest"
  },
  "flows": {}
}
```

Leave `flows` empty to use the built-in templates (which reference the `vars`
above), or supply full custom step lists. **The default selectors are
placeholders** — copy the real ones from your extranet with browser dev-tools.

5. **Enable** the channel and save.

---

## Testing & running

- **Verify your flow** (captures a screenshot to `storage/cache/`):
  ```bash
  php cli/automation_test.php <hotel_id> makemytrip login
  php cli/automation_test.php <hotel_id> makemytrip pull_bookings
  ```
  Or use the **Test** button on the Channel Manager page.

- **Automated sync** runs through the queue worker:
  ```cron
  * * * * * php /path/to/cli/worker.php >> storage/logs/worker.log 2>&1
  ```
  Trigger a sync from **Channel Manager → Sync Now** (queues inventory, rates,
  restrictions and booking-import jobs). Failures retry with exponential backoff.

- **Self-test the engine** against a bundled mock extranet:
  ```bash
  chromedriver --port=9515 &
  CHROMIUM_BIN=/path/to/chromium php tests/automation_live.php
  ```

---

## Troubleshooting

| Symptom | Likely cause / fix |
|--------|--------------------|
| `WebDriver connection failed` | chromedriver not running / wrong `driver_url`. |
| `session not created … only supports Chrome version N` | chromedriver ↔ Chrome version mismatch. Install matching builds. |
| `waitFor timed out: <selector>` | Selector changed — update `vars`/`flows` from the live DOM. |
| Login "succeeds" but lands on login page | Wrong `post_login_url_contains`, or a CAPTCHA/OTP appeared. |
| Works locally, fails on server | Server missing Chromium/driver, or portal blocks the datacenter IP. |
