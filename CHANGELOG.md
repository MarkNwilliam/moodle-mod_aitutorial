# AI Tutorial Generator - Changelog

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
