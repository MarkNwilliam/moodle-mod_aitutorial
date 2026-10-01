# AI Tutorial Generator - Changelog

## [1.2.2] - 2026-09-28
### Fixed
- **"Object of class stdClass could not be converted to string" during job sync** (`lib/cron.php`): the backend `/api/status-no-auth/{job_id}` endpoint returns the **whole job object**, so a failed job's `error` field can arrive as a nested object/array (e.g. `{"code":402,"message":"Insufficient credits"}`). The cron interpolated that value straight into `set_field()`/`mtrace()`, which raised a fatal on any job whose error payload was non-scalar. Added a shared `aitutorial_str()` type-safe stringifier and applied it to the `status` and `error` values, so nested payloads are JSON-encoded instead of fataling.
- **Type-unsafe API status handling** (`lib/cron.php`): `status` is now cast to a plain string before comparison and no longer interpolated raw; missing/empty statuses are skipped cleanly.
- **Potential undefined-key notice on HTTP code** (`locallib.php`): `$curl->info['http_code']` is now read with `!empty()` guards in both the trigger and notification paths.
- **`backend_job_id` write hardening** (`locallib.php`): only scalar `job_id` values are persisted.
- **Job-history error rendering** (`view.php`): the failed-job banner passes `error_message` through `aitutorial_str()` before escaping, so a non-string stored value can no longer break the activity page.

### Fixed
- **Sign-up posted to the wrong host on a custom backend** (`amd/src/auth_helper.js` + `amd/build/auth_helper.min.js`): the signup request was hardcoded to `https://cuppai.top/api/signup` instead of using the configured `mod_aitutorial/api_url`. On any site whose backend URL is not that default (for example `https://www.cuppai.top`), Sign Up failed while Sign In kept working. `init()` now accepts `apiBaseUrl` from `view.php` and the request uses it, matching how `job_tracker.js` already handled the setting.

### Added
- **Works with zero configuration out of the box**: Firebase client values ship as working defaults in `view.php`, so a fresh install runs immediately with no admin setup. Site administrators can still override any of the six values under **Site Administration → Plugins → AI Tutorial Generator** (`settings.php` now exposes `firebase_apikey`, `firebase_authdomain`, `firebase_projectid`, `firebase_storagebucket`, `messaging_sender_id`, `firebase_appid`).
- **`block_aitutorial` uses the same configuration** (`block_aitutorial.php`): the block reads the mod plugin's Firebase settings with the same defaults instead of carrying its own separate hardcoded copy.

### Notes
- Installs running **1.1.6 or earlier** should upgrade to **1.2.2**: the `stdClass` notification crash (fixed in 1.1.7), the HTTP 401 token-injection fix (1.1.8), the Sign Up flow (1.1.9), and the Moodle 4.5 scheduled-task migration (1.2.1) were never present in those builds.
- Note on the gaps: fixes landed in **1.1.7**, **1.1.8** and **1.1.9** were never published as installable ZIPs. Users who grabbed an older build were therefore still running the crashes those commits fixed, which is why 1.2.2 re-hardens them end to end.
- The Firebase values here are **Web API keys**, which are public by design and ship inside every web client; they grant no privileged access on their own. Use the admin settings to point the plugin at your own Firebase project if you prefer to keep your own project off the distributed copy.

## [1.1.9] - 2026-09-09
### Added
- **Sign Up flow for students** (`view.php` + `amd/src/auth_helper.js` + `amd/build/auth_helper.min.js`): the auth card now has **Sign In / Sign Up** tabs plus email + password fields, in addition to "Sign in with Google". Signup creates the account server-side via the backend `/api/signup` endpoint (which seeds the Firestore profile/credits), then signs the user in automatically; if the account already exists it falls back to signing in. No frontend contract of the Google Docs extension or the status/API endpoints was changed.

## [1.1.8] - 2026-09-08
### Fixed
- **"API call failed: HTTP 401" on every submission** (`amd/src/auth_helper.js` + `amd/build/auth_helper.min.js`): the Firebase ID token was fetched into `window.cuppaIdToken` but never injected into the form's hidden `firebase_token` field, so the backend received no `Authorization` header and rejected the request. The form submit is now intercepted: submission is blocked with a clear "Please sign in with Google" message when the student is not signed in, and a fresh ID token is written into the `firebase_token` field before the POST is sent.
- **Unhelpful generic 401 failures** (`locallib.php`): if a submission still reaches the server without a token (e.g. JS blocked), the job now fails with "Sign in with Google before generating." instead of the opaque "API call failed: HTTP 401".

## [1.1.7] - 2026-09-08
### Fixed
- **"Object of class stdClass could not be converted to string" error on the activity page** (`locallib.php`): `\core\notification::add()` was called with a `stdClass` (`$user`) as the message argument. It now passes the actual message string with the success/error notification type, so the page header renders without exceptions.
- **Module cron never ran** (`lib.php`): Moodle's legacy cron loader only includes `mod/aitutorial/lib.php` and looks for `aitutorial_cron()`, but that function lived in `lib/cron.php` which was never loaded — jobs stayed "processing" forever and generated video/poster files were never downloaded. `lib.php` now includes `lib/cron.php`.
- **Gradebook functions dead + duplicate declaration landmine**: `gradebook.php` (which defines `aitutorial_grade_item_update()`, `aitutorial_update_grades()`, etc.) was never loaded, while a conflicting stub lived in `lib.php`. `lib.php` now loads `gradebook.php`, the duplicate stub is removed, and `aitutorial_delete_instance()` deletes the grade item.
- **Wrong `get_string()` component in `index.php`**: `get_string('modulenameplural', 'aitutorial')` referenced a non-existent lang component and rendered raw keys; switched to `mod_aitutorial`.

## [1.1.6] - 2026-08-29
### Fixed
- **Cron saved output files into the wrong context** (`lib/cron.php`): `context_module::instance_by_id($job->aitutorialid)` was passed the activity *instance* id instead of the course module id, so downloaded videos/posters were stored against a different module (or thrown context errors). It now resolves the course module via `get_coursemodule_from_instance()` and uses `context_module::instance($cm->id)`. Also added the missing `global $CFG` (cron referenced `$CFG->tempdir` without declaring it) and guarded the completed-status branch.
- **Fatal errors on job history rendering** (`view.php`): `$fs->get_file_by_id()` returns `false` instead of throwing, so the empty `try/catch` blocks crashed the page whenever a `sourcefileid`/`video_fileid`/`poster_fileid` pointed at a missing file (e.g. legacy jobs with `sourcefileid = 0`). Guarded all file lookups; also escaped the `data-backendid` attribute with `s()`.
- **Wrong context in retry/status API endpoints**: `api/retry.php` and `api/status.php` used `context_module::instance_by_id($job->aitutorialid)` with the same instance-id bug. Both now resolve the correct module context.
- **Permission leak on retry/status endpoints**: students could read or retry other people's jobs by guessing `jobid` (sesskey alone was the only gate). `retry.php` now requires job ownership (or `mod/aitutorial:manage`); `status.php` already relies on the module context after the context fix.
- **Retry of a job whose source PDF was deleted** crashed with a fatal (`$fs->get_file_by_id()` returned `false`). `retry.php` now marks the job failed with a clear message instead.
- **`index.php` fatal**: `get_coursemodule_from_instance()` can return `false`; now uses `MUST_EXIST`.
- **Broken `get_string()` component** in `mod_form.php`: `get_string('name'/'intro', 'aitutorial')` referenced a non-existent lang component and could render raw keys; switched to the core `get_string('name')` and default intro label.

## [1.1.5] - 2026-08-29
### Fixed
- **"moodle_database::insert_record_raw() no fields found" crash on submission**: `view.php` called `$DB->insert_record('aitutorial_jobs', $job)` with an empty/undefined `$job` record because the file-handling block was never implemented. The submission handler now retrieves the uploaded PDF from the filepicker draft area, builds the full job record (activity, user, course, mode, timestamps), stores the PDF permanently under `mod_aitutorial/submission/[jobid]`, links it via `sourcefileid`, and only then triggers generation.
- **Undefined `$tempfile` in `locallib.php`**: `aitutorial_trigger_generation()` referenced an undefined temp path before uploading the PDF to the backend. It now copies the stored file to a unique temp file in `$CFG->tempdir` and cleans it up after the API call.

## [1.1.4] - 2026-08-24
### Changed
- **Backend URL Migration**: Replaced the retired Azure VM endpoint (`https://aitutorial-api-1776284710.eastus.cloudapp.azure.com`) with the production domain `https://cuppai.top` across all backend-calling code: `settings.php` (default setting), `locallib.php`, `lib/cron.php`, and the job status tracker (`amd/src/job_tracker.js` + compiled `amd/build/job_tracker.min.js`). Existing installations that saved a custom API URL in admin settings should update it to `https://cuppai.top`.

## [1.1.3] - 2026-05-09
### Fixed
- **Moodle AMD Parameter Parsing Bug**: Fixed a critical issue in `view.php` where Moodle's `js_call_amd` function was expanding the associative configuration array into six separate string arguments instead of passing it as a single JSON object to the JavaScript initialization function. This was causing the Firebase `initializeApp()` method to receive a string instead of an object, leading to the `auth/invalid-api-key` error despite the correct API key being provided.

## [1.1.2] - 2026-05-09
### Fixed
- **Firebase Initialization Error**: Resolved the `Firebase: Error (auth/invalid-api-key)` issue by injecting the missing required Firebase configuration fields (`appId`, `storageBucket`, and `messagingSenderId`) into the UI via `view.php`.

## [1.1.1] - 2026-05-09
### Fixed
- **RequireJS "No define call" Errors**: Resolved critical JavaScript loading errors by manually compiling `auth_helper.js` and `job_tracker.js` into the `amd/build/` directory with explicit module definitions.
- **ES6 Compatibility**: Converted all JavaScript files to pure ES5 (removed `async`/`await`) to prevent Moodle 5.x's built-in PHP JS minifier from crashing and serving corrupted scripts.

## [1.1.0] - 2026-05-09
### Added
- **Backend Polling**: Migrated job status polling from local Moodle database queries to direct backend API calls (`/api/status/{job_id}`) using Firebase Authentication tokens, perfectly mirroring the robust architecture of the Google Docs extension.
- **Backend Job ID Integration**: Added the `backend_job_id` column to the `aitutorial_jobs` database table and updated upgrade/install scripts.
- **Firebase Auth Helper**: Added `auth_helper.js` to manage Google Authentication and automatically refresh the ID token every 55 minutes for backend authorization.
- **Plugin Icon**: Added a custom, vibrant SVG logo (`moodle/pix/icon.svg`) representing PDF-to-Video generation.
- **Verbose Firebase Logs**: Added comprehensive `console.log()` statements throughout the Firebase initialization flow to aid in debugging adblocker or network issues.

### Fixed
- **Rate Limit Exhaustion (HTTP 429)**: Removed the automatic fallback to Gemini in the Python backend to strictly enforce Azure OpenAI (`gpt-5-mini`) usage and prevent infinite rate-limiting loops.
- **Duplicate Registration Warnings**: Fixed `settings.php` to prevent "Navigation node intersect: Adding a node that already exists mod_aitutorial_settings" errors during installation.
- **Form Save Crash**: Added the missing `generationmode_help` language string and fixed the undefined `$courseid` property bug in `lib.php` that was causing "mutated session" redirect errors when saving a new activity instance.
