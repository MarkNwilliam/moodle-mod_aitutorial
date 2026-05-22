# 🏗️ AI Tutorial Generator - Moodle Integration Architecture

## System Architecture Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│                        MOODLE LMS                                │
│                                                                  │
│  ┌────────────────────────────────────────────────────────┐     │
│  │                 Student Browser                       │     │
│  │                                                        │     │
│  │  ┌──────────────────────────────────────────────┐    │     │
│  │  │  mod/aitutorial/view.php                     │    │     │
│  │  │  ┌────────────────────────────────────┐     │    │     │
│  │  │  │  PDF Upload Form                   │     │    │     │
│  │  │  │  [Choose File] [Mode: Both]        │     │    │     │
│  │  │  │  [Generate Tutorial]               │     │    │     │
│  │  │  └────────────────────────────────────┘     │    │     │
│  │  │                                              │    │     │
│  │  │  ┌────────────────────────────────────┐     │    │     │
│  │  │  │  Job Status Cards                  │     │    │     │
│  │  │  │  ✓ Job #123: Completed (100%)      │     │    │     │
│  │  │  │    📹 Video Player                 │     │    │     │
│  │  │  │    📊 Poster Viewer                │     │    │     │
│  │  │  └────────────────────────────────────┘     │    │     │
│  │  └──────────────────────────────────────────────┘    │     │
│  │                                                        │     │
│  │  JavaScript: job_tracker.js (polls every 5s)          │     │
│  └────────────────────────────────────────────────────────┘     │
│                                                                  │
│  ┌──────────────────────┐    ┌────────────────────────┐        │
│  │  mod_form.php        │    │  pluginfile.php        │        │
│  │  (Activity Config)   │    │  (File Serving)        │        │
│  └──────────────────────┘    └────────────────────────┘        │
│                                                                  │
│  ┌───────────────────────────────────────────────────────┐    │
│  │  lib.php + locallib.php                               │    │
│  │  ├─ aitutorial_add_instance()                         │    │
│  │  ├─ aitutorial_trigger_generation() ─────────────┐   │    │
│  │  ├─ aitutorial_complete_job()                     │   │    │
│  │  └─ aitutorial_send_notification()                │   │    │
│  └───────────────────────────────────────────────────┼───┘    │
│                                                       │        │
│  ┌───────────────────────────────────────────────────┐│        │
│  │  db/                                               ││        │
│  │  ├─ mdl_aitutorial (activity instances)          ││        │
│  │  └─ mdl_aitutorial_jobs (generation jobs)        ││        │
│  └───────────────────────────────────────────────────┘│        │
│                                                       │        │
└───────────────────────────────────────────────────────┼────────┘
                                                        │
                        HTTP POST (multipart form)      │
                                                        │
┌───────────────────────────────────────────────────────▼────────┐
│                  BACKEND API SERVICE (FastAPI)                 │
│                  http://localhost:8000                          │
│                                                                 │
│  ┌───────────────────────────────────────────────────┐        │
│  │  POST /api/generate                               │        │
│  │  ├─ Receive PDF file                             │        │
│  │  ├─ Validate inputs                              │        │
│  │  ├─ Create job record                            │        │
│  │  └─ Start async process_generation()             │        │
│  └───────────────────────────────────────────────────┘        │
│                                                                 │
│  ┌───────────────────────────────────────────────────┐        │
│  │  async process_generation(job_id)                 │        │
│  │  ├─ Extract text from PDF (PyPDF2)               │        │
│  │  ├─ Convert PDF to images (pdf2image)            │        │
│  │  ├─ Generate audio (Edge TTS)                    │        │
│  │  ├─ Create video (FFmpeg)                        │        │
│  │  └─ Generate poster (LaTeX + Gemini AI)          │        │
│  └───────────────────────────────────────────────────┘        │
│                                                                 │
│  ┌───────────────────────────────────────────────────┐        │
│  │  GET /api/status/{job_id}                         │        │
│  │  └─ Return job status + progress                  │        │
│  └───────────────────────────────────────────────────┘        │
│                                                                 │
│  ┌───────────────────────────────────────────────────┐        │
│  │  GET /api/download/{job_id}/{type}                │        │
│  │  └─ Stream video/poster file                      │        │
│  └───────────────────────────────────────────────────┘        │
│                                                                 │
│  Temporary Storage: /tmp/aitutorial_jobs/{job_id}/             │
│  ├─ input.pdf                                                   │
│  ├─ images/                                                     │
│  ├─ audio_*.mp3                                                 │
│  ├─ output_video.mp4                                            │
│  └─ poster.pdf                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Data Flow: PDF Upload to Generated Content

### Step-by-Step Process

```
1. STUDENT UPLOADS PDF
   └─> Browser → Moodle form → mod/aitutorial/view.php
       └─> Validates file (size, type)
           └─> Stores in Moodle file system (draft area)
               └─> Creates job record in mdl_aitutorial_jobs
                   └─> Calls aitutorial_trigger_generation()

2. MOODLE SENDS TO BACKEND
   └─> locallib.php: aitutorial_trigger_generation()
       └─> Copies PDF to temp directory
           └─> HTTP POST to http://localhost:8000/api/generate
               ├─ job_id
               ├─ generation_mode (video/poster/both)
               ├─ voice (TTS voice ID)
               └─ pdf_file (multipart)

3. BACKEND PROCESSES PDF
   └─> FastAPI receives request
       └─> Validates inputs
           └─> Saves PDF to /tmp/aitutorial_jobs/{job_id}/input.pdf
               └─> Creates job in memory
                   └─> Launches async process_generation()
                       └─> Returns 200 OK immediately

4. ASYNC GENERATION (Background Task)
   └─> Step 4a: Text Extraction
       │   └─> PyPDF2 reads PDF
       │       └─> Extracts text from each page
       │           └─> Returns text_by_page[]
       │
       ├─> Step 4b: Video Generation (if requested)
       │   ├─> Convert PDF pages to images (pdf2image)
       │   │   └─> dpi=150, resize to 1280x720
       │   │
       │   ├─> Generate audio for each page (Edge TTS)
       │   │   └─> text_chunk → en-US-GuyNeural → audio_N.mp3
       │   │   └─> Parallel processing (asyncio.gather)
       │   │
       │   └─> Create video (FFmpeg)
       │       ├─> For each page: image + audio → segment_N.mp4
       │       └─> Concatenate all segments → output_video.mp4
       │
       └─> Step 4c: Poster Generation (if requested)
           ├─> Extract key content (full_text)
           ├─> (Optional) Call Gemini AI for summarization
           ├─> Generate poster JSON structure
           ├─> Create LaTeX code (baposter class)
           └─> Compile to PDF (pdflatex)

5. MOODLE POLLS FOR STATUS
   └─> JavaScript: job_tracker.js (every 5 seconds)
       └─> GET /mod/aitutorial/api/status.php?jobid=123
           └─> GET http://localhost:8000/api/status/123
               └─> Returns: {status, progress, video_url, poster_url}
                   └─> Updates progress bar in UI
                       └─> When status="completed", reloads page

6. BACKEND DOWNLOADS FILES TO MOODLE
   └─> Moodle cron (every 60 seconds)
       └─> lib/cron.php: aitutorial_cron()
           └─> For each processing job:
               ├─> GET /api/status/{job_id}
               ├─> If completed:
               │   ├─> Download video: GET /api/download/{job_id}/video
               │   ├─> Download poster: GET /api/download/{job_id}/poster
               │   ├─> Store in Moodle file system
               │   ├─> Update job record (status=completed)
               │   └─> Send notification to student
               └─> If failed:
                   ├─> Update job record (status=failed)
                   └─> Send error notification

7. STUDENT VIEWS RESULTS
   └─> Student revisits activity page
       └─> Sees completed job card
           ├─> Video player (HTML5 <video> tag)
           │   └─> Streams from Moodle file system
           │       └─> pluginfile.php → generated_video area
           │
           └─> Poster viewer (iframe)
               └─> Streams from Moodle file system
                   └─> pluginfile.php → generated_poster area
```

---

## Database Schema

### Table: `mdl_aitutorial`
Main activity instances (one per course section).

```sql
CREATE TABLE mdl_aitutorial (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    course BIGINT NOT NULL,              -- FK to mdl_course
    name VARCHAR(255) NOT NULL,           -- Activity name
    intro TEXT,                           -- Description
    introformat SMALLINT DEFAULT 0,       -- Intro format (HTML/Moodle)
    timecreated BIGINT NOT NULL,
    timemodified BIGINT NOT NULL,
    
    INDEX idx_course (course)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Table: `mdl_aitutorial_jobs`
Generation job tracking (one per student submission).

```sql
CREATE TABLE mdl_aitutorial_jobs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    aitutorialid BIGINT NOT NULL,         -- FK to mdl_aitutorial
    userid BIGINT NOT NULL,               -- FK to mdl_user
    courseid BIGINT NOT NULL,             -- FK to mdl_course
    sourcefileid BIGINT NOT NULL,         -- FK to mdl_files (uploaded PDF)
    
    job_type VARCHAR(50) NOT NULL,        -- 'video', 'poster', 'both'
    status VARCHAR(20) NOT NULL,          -- 'pending', 'processing', 'completed', 'failed'
    progress INT DEFAULT 0,               -- 0-100 percentage
    
    video_fileid BIGINT,                  -- FK to mdl_files (generated video)
    poster_fileid BIGINT,                 -- FK to mdl_files (generated poster)
    
    error_message TEXT,                   -- Error details if failed
    timecreated BIGINT NOT NULL,
    timemodified BIGINT NOT NULL,
    timecompleted BIGINT,                 -- Timestamp when completed
    
    INDEX idx_aitutorial (aitutorialid),
    INDEX idx_user (userid),
    INDEX idx_course (courseid),
    INDEX idx_status (status),
    INDEX idx_sourcefile (sourcefileid),
    INDEX idx_videofile (video_fileid),
    INDEX idx_posterfile (poster_fileid),
    
    FOREIGN KEY (aitutorialid) REFERENCES mdl_aitutorial(id),
    FOREIGN KEY (userid) REFERENCES mdl_user(id),
    FOREIGN KEY (courseid) REFERENCES mdl_course(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Moodle File Storage Integration

### File Areas

Moodle uses a virtual file system. Generated files are stored in:

```
mdl_files table:
├─ contextid:     Module context ID
├─ component:     'mod_aitutorial'
├─ filearea:      'generated_video' or 'generated_poster'
├─ itemid:        Job ID
├─ filepath:      '/'
├─ filename:      'tutorial_video_123.mp4'
└─ userid:        Student ID
```

### File Serving Flow

```
Student clicks "Download Video"
  └─> Browser requests: /pluginfile.php/{contextid}/mod_aitutorial/generated_video/{jobid}/tutorial_video_123.mp4
      └─> pluginfile.php receives request
          ├─> Validates context and permissions
          ├─> Checks user capability (mod/aitutorial:view)
          ├─> Verifies file belongs to user's job
          └─> Calls send_stored_file()
              └─> Streams file to browser with correct MIME type
```

---

## Security Model

### Authentication & Authorization

```
Capability Checks:
├─ mod/aitutorial:addinstance
│   └─ Who: Teachers, Managers
│   └─ What: Create new AI Tutorial activities
│
├─ mod/aitutorial:view
│   └─ Who: All users (students, teachers, managers)
│   └─ What: View activity page and generated content
│
├─ mod/aitutorial:submit
│   └─ Who: Students, Teachers
│   └─ What: Upload PDFs and trigger generation
│
└─ mod/aitutorial:manage
    └─ Who: Teachers, Managers
    └─ What: View all student submissions, manage jobs
```

### File Security

```
Uploaded PDFs:
├─ Stored in Moodle draft file area
├─ Only accessible to uploading user and teachers
├─ Validated for type (.pdf) and size (max 50MB)
└─ Deleted after processing (24-hour cleanup)

Generated Files:
├─ Videos: Only accessible to owner + teachers
├─ Posters: Only accessible to owner + teachers
└─ Served through pluginfile.php with capability checks
```

### API Security

```
Backend Service:
├─ Runs on localhost (not exposed to internet)
├─ Moodle communicates via internal network
├─ No authentication required (firewall protects)
└─ In production: Use HTTPS + API key authentication
```

---

## Cron System

### Moodle Cron Configuration

```php
// In version.php
$plugin->cron = 60;  // Run every 60 seconds
```

### Moodle Cron Schedule (System)

```bash
# Add to server crontab (runs every minute)
* * * * * php /path/to/moodle/admin/cli/cron.php >/dev/null 2>&1
```

### Cron Execution Flow

```
Moodle Cron Trigger (every 60s)
  └─> mod/aitutorial/lib/cron.php: aitutorial_cron()
      ├─> Fetch all processing jobs
      ├─> For each job:
      │   ├─> Check timeout (2 hours max)
      │   │   └─> If timed out: mark as failed
      │   │
      │   ├─> Poll backend: GET /api/status/{job_id}
      │   │   ├─> Update progress in database
      │   │   └─> If status changed:
      │   │       ├─> completed: Download files, store in Moodle
      │   │       └─> failed: Log error message
      │   │
      │   └─> Send notification to student
      │
      └─> Log completion message
```

---

## API Reference

### Moodle → Backend API

#### 1. Start Generation

```http
POST http://localhost:8000/api/generate
Content-Type: multipart/form-data

job_id: 123
generation_mode: both
voice: en-US-GuyNeural
pdf_file: (binary PDF)

Response 200:
{
    "status": "accepted",
    "job_id": 123,
    "message": "Generation started"
}
```

#### 2. Check Status

```http
GET http://localhost:8000/api/status/123

Response 200:
{
    "job_id": 123,
    "status": "processing",
    "progress": 45,
    "video_url": null,
    "poster_url": null,
    "error": null
}
```

#### 3. Download File

```http
GET http://localhost:8000/api/download/123/video

Response 200:
Content-Type: video/mp4
Content-Disposition: attachment; filename="tutorial_video.mp4"
[Binary video data]
```

---

## Performance Considerations

### Generation Time Estimates

| PDF Size | Pages | Video Only | Poster Only | Both |
|----------|-------|------------|-------------|------|
| Small | 5-10 | 1-2 min | 1 min | 2-3 min |
| Medium | 10-50 | 3-8 min | 2 min | 5-10 min |
| Large | 50-100 | 10-20 min | 3 min | 13-23 min |
| Very Large | 100-200 | 20-40 min | 5 min | 25-45 min |

### Optimization Strategies

1. **Parallel Audio Generation**:
   - Uses `asyncio.gather()` for concurrent TTS requests
   - 4-8 pages processed simultaneously

2. **FFmpeg Ultrafast Preset**:
   - `-preset ultrafast` for quick encoding
   - Trade-off: Larger file size, faster generation

3. **Image Optimization**:
   - DPI reduced to 150 (from 300)
   - Resized to 1280x720 (HD, not 4K)

4. **Caching**:
   - Audio files cached in `/tmp/aitutorial_jobs/`
   - Reused if same PDF uploaded again (future feature)

---

## Deployment Checklist

### Server Requirements

- [ ] Moodle 4.0+ installed
- [ ] PHP 8.0+ with curl extension
- [ ] Python 3.8+ installed
- [ ] FFmpeg installed (`ffmpeg -version`)
- [ ] LaTeX installed (`pdflatex --version`)
- [ ] 4GB+ RAM (for parallel processing)
- [ ] 10GB+ disk space (for temp files and outputs)

### Installation Steps

- [ ] Copy plugin to `moodle/mod/aitutorial`
- [ ] Run `./deploy_moodle_plugin.sh /path/to/moodle`
- [ ] Complete Moodle database upgrade
- [ ] Configure API keys in admin settings
- [ ] Start backend service
- [ ] Test with sample PDF
- [ ] Verify video plays in browser
- [ ] Verify poster displays in iframe
- [ ] Test notifications
- [ ] Configure cron job

### Production Hardening

- [ ] Move backend to separate server
- [ ] Enable HTTPS for backend API
- [ ] Add API key authentication
- [ ] Configure CORS properly
- [ ] Set up monitoring and alerting
- [ ] Configure log rotation
- [ ] Set up backup strategy for generated files
- [ ] Load test with concurrent users

---

## Future Enhancements

### Phase 2 Features

1. **RAG Integration**:
   - Use LlamaIndex to extract key concepts
   - Generate structured content summaries
   - Improve poster content quality

2. **WebSocket Support**:
   - Replace polling with real-time updates
   - Show live progress updates
   - Reduce server load

3. **Multi-Document Support**:
   - Combine multiple PDFs into single video
   - Generate composite posters
   - Cross-reference content

4. **Video Editing**:
   - Trim start/end of video
   - Add custom intro/outro
   - Insert subtitles/captions

### Phase 3 Features

1. **Analytics Dashboard**:
   - Track generation statistics
   - Most popular topics
   - Average generation time
   - Success/failure rates

2. **Collaborative Features**:
   - Share generated tutorials
   - Group generations
   - Public gallery

3. **Advanced Customization**:
   - Choose video themes
   - Custom poster templates
   - Voice selection per generation
   - Background music for videos

4. **Mobile Support**:
   - Moodle mobile app integration
   - Mobile-optimized video player
   - Push notifications for completion
