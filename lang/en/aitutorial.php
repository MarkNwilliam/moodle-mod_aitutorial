<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Language Strings (English)
 *
 * @package    mod_aitutorial
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'AI Tutorial Generator';
$string['modulename'] = 'AI Tutorial Generator';
$string['modulenameplural'] = 'AI Tutorial Generators';
$string['modulename_help'] = 'The AI Tutorial Generator allows students to upload educational documents (PDFs) and automatically generate narrated video tutorials and research posters using AI.';

// Capabilities.
$string['aitutorial:addinstance'] = 'Add a new AI Tutorial Generator';
$string['aitutorial:view'] = 'View AI Tutorial Generator';
$string['aitutorial:submit'] = 'Submit documents for generation';
$string['aitutorial:manage'] = 'Manage AI Tutorial Generator';

// Form strings.
$string['name'] = 'Name';
$string['intro'] = 'Description';
$string['generationmode'] = 'Generation Mode';
$string['generationmode_video'] = 'Video Only';
$string['generationmode_poster'] = 'Poster Only';
$string['generationmode_both'] = 'Both Video & Poster';
$string['uploadpdf'] = 'Upload PDF Document';
$string['submitforgeneration'] = 'Generate Tutorial';
$string['selectfile'] = 'Choose a PDF file...';

// Job status.
$string['status_pending'] = 'Pending';
$string['status_processing'] = 'Processing';
$string['status_completed'] = 'Completed';
$string['status_failed'] = 'Failed';
$string['progress'] = 'Progress';
$string['generatedvideo'] = 'Generated Video';
$string['generatedposter'] = 'Generated Poster';
$string['downloadvideo'] = 'Download Video';
$string['downloadposter'] = 'Download Poster';
$string['nogenerationyet'] = 'No content generated yet. Upload a PDF to get started!';
$string['generationerror'] = 'Generation Error';
$string['retry'] = 'Retry';

// Admin settings.
$string['apiurl'] = 'Backend API URL';
$string['apiurl_desc'] = 'URL of the Python backend service (e.g., http://localhost:8000)';
$string['geminiapikey'] = 'Gemini API Key';
$string['geminiapikey_desc'] = 'API key for Google Gemini (used by backend)';
$string['enablevideo'] = 'Enable Video Generation';
$string['enablevideo_desc'] = 'Allow students to generate narrated videos from PDFs';
$string['enableposter'] = 'Enable Poster Generation';
$string['enableposter_desc'] = 'Allow students to generate research posters from PDFs';
$string['maxfilesize'] = 'Maximum File Size (MB)';
$string['maxfilesize_desc'] = 'Maximum allowed PDF file size in megabytes';
$string['ttsvoice'] = 'Text-to-Speech Voice';
$string['ttsvoice_desc'] = 'Edge TTS voice ID for narration (e.g., en-US-GuyNeural)';

// Firebase Settings.
$string['firebase_apikey'] = 'Firebase API Key';
$string['firebase_apikey_desc'] = 'The API Key for your Firebase project.';
$string['firebase_authdomain'] = 'Firebase Auth Domain';
$string['firebase_authdomain_desc'] = 'The Auth Domain for your Firebase project (e.g., your-project.firebaseapp.com).';
$string['firebase_projectid'] = 'Firebase Project ID';
$string['firebase_projectid_desc'] = 'The Project ID for your Firebase project.';

// Notifications.
$string['generationstarted'] = 'Your tutorial generation has started!';
$string['generationcomplete'] = 'Your AI-generated tutorial is ready!';
$string['generationfailed'] = 'Tutorial generation failed';
$string['notification_body_complete'] = 'Your {$a} has been generated successfully. Visit the activity to view/download it.';
$string['notification_body_failed'] = 'Tutorial generation failed: {$a}';

// Misc.
$string['jobhistory'] = 'Your Generation History';
$string['deletejob'] = 'Delete';
$string['download'] = 'Download';
$string['filesize'] = 'File Size';
$string['generatedon'] = 'Generated On';
$string['processingtime'] = 'Processing Time';

// Help Section.
$string['howitworks'] = 'How it Works';
$string['step1_title'] = 'Upload PDF';
$string['step1_desc'] = 'Select your lecture notes or textbook chapter (max 50MB).';
$string['step2_title'] = 'Select Mode';
$string['step2_desc'] = 'Choose if you want a video, a poster, or both.';
$string['step3_title'] = 'AI Magic';
$string['step3_desc'] = 'Our AI extracts key concepts and generates your tutorial.';
$string['step4_title'] = 'Study Smarter';
$string['step4_desc'] = 'Watch your video or review your poster anywhere!';
$string['helptip'] = 'Tip: For best results, ensure your PDF has selectable text (not scanned images).';
