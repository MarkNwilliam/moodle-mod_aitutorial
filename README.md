# AI Tutorial Generator - Moodle Plugin

## 🎯 Overview

**AI Tutorial Generator** is a Moodle activity module that allows students to upload educational documents (PDFs) and automatically generate:
- 📹 **Narrated video tutorials** using AI text-to-speech
- 📊 **Research posters** with professional LaTeX formatting

Powered by Google Gemini AI, Edge TTS, and your existing tutorial generator codebase.

---

## 🎓 How It Helps Students

1. **Automated Learning Materials**: Students upload lecture notes, textbooks, or research papers → get professional video tutorials and visual summaries
2. **Accessibility**: Text-to-speech narration helps auditory learners and students with disabilities
3. **Study Aids**: Generated posters serve as quick revision references
4. **Self-Paced Learning**: Students can generate custom tutorials for topics they find challenging
5. **Multi-Modal Learning**: Combines visual (images), auditory (narration), and textual (content) learning

---

## 📦 Installation

### Option 1: Manual Installation

1. **Copy the plugin to your Moodle installation**:
   ```bash
   cp -r moodle_plugin/aitutorial /path/to/moodle/mod/aitutorial
   ```

2. **Set correct permissions**:
   ```bash
   chown -R www-data:www-data /path/to/moodle/mod/aitutorial
   chmod -R 755 /path/to/moodle/mod/aitutorial
   ```

3. **Visit your Moodle admin page** to complete installation:
   ```
   https://your-moodle-site.com/admin/
   ```
   Moodle will detect the plugin and prompt you to upgrade.

4. **Configure the plugin**:
   - Go to: **Site Administration → Plugins → Activity Modules → AI Tutorial Generator**
   - Set the Backend API URL (default: `http://localhost:8000`)
   - Enter your Gemini API Key
   - Configure TTS voice and file size limits

### Option 2: Git Installation

```bash
cd /path/to/moodle/mod
git clone <your-repo-url> aitutorial
php admin/cli/upgrade.php
```

---

## 🔧 Backend Service Setup

The plugin requires a Python backend service to handle video/poster generation.

### 1. Install Python Dependencies

```bash
cd /path/to/tutorialgeneratortest
pip install -r requirements.txt
pip install fastapi uvicorn python-multipart
```

### 2. Configure Environment

Create a `.env` file:
```bash
GEMINI_API_KEY=your_actual_api_key_here
```

### 3. Start the Backend Service

```bash
cd moodle_plugin/aitutorial/api
python backend_service.py
```

The service will start on `http://localhost:8000` by default.

### 4. Test the Backend

```bash
curl http://localhost:8000/health
```

Expected response:
```json
{"status": "ok", "version": "1.0.0"}
```

---

## 🚀 Usage

### For Teachers

1. **Add the Activity**:
   - Navigate to your course
   - Turn editing on
   - Click "Add an activity or resource"
   - Select **AI Tutorial Generator**

2. **Configure the Activity**:
   - Set a name (e.g., "Generate Tutorial from Lecture Notes")
   - Add a description explaining what students should upload
   - Choose default generation mode (Video, Poster, or Both)

3. **Monitor Progress**:
   - Teachers can view all student submissions
   - Check generation status and download outputs

### For Students

1. **Navigate to the Activity**:
   - Click on the AI Tutorial Generator link in your course

2. **Upload a PDF**:
   - Select your educational document (lecture notes, textbook chapter, etc.)
   - Choose generation mode if allowed

3. **Wait for Generation**:
   - Progress bar shows real-time status
   - Generation typically takes 2-10 minutes depending on PDF size

4. **View/Download Results**:
   - **Video**: Play directly in browser or download MP4
   - **Poster**: View embedded PDF or download for printing

---

## 🗂️ File Structure

```
moodle_plugin/aitutorial/
├── api/
│   ├── backend_service.py      # FastAPI backend (Python)
│   └── status.php              # Moodle API endpoint
├── amd/src/
│   └── job_tracker.js          # JavaScript for status polling
├── classes/                    # PHP classes (future expansion)
├── db/
│   ├── access.php              # Capabilities
│   ├── install.xml             # Database schema
│   └── upgrade.php             # Upgrade script
├── lang/en/
│   └── aitutorial.php          # Language strings
├── lib/
│   └── cron.php                # Cron job handler
├── pix/                        # Icon files (add icon.png)
├── templates/                  # Mustache templates (optional)
├── lib.php                     # Core library functions
├── locallib.php                # Local library functions
├── mod_form.php                # Activity configuration form
├── view.php                    # Main activity page
├── index.php                   # Course index page
├── pluginfile.php              # File serving
├── settings.php                # Admin settings
├── styles.css                  # CSS styles
└── version.php                 # Version information
```

---

## ⚙️ Configuration Options

### Admin Settings

| Setting | Description | Default |
|---------|-------------|---------|
| **Backend API URL** | URL of the Python backend service | `http://localhost:8000` |
| **Gemini API Key** | Google Gemini API key for AI generation | _(required)_ |
| **Enable Video** | Allow video generation | ✅ Enabled |
| **Enable Poster** | Allow poster generation | ✅ Enabled |
| **Max File Size** | Maximum PDF upload size (MB) | `50` |
| **TTS Voice** | Edge TTS voice ID | `en-US-GuyNeural` |

### Available TTS Voices

- `en-US-GuyNeural` - Male (US)
- `en-US-JennyNeural` - Female (US)
- `en-GB-SoniaNeural` - Female (UK)
- `en-AU-NatashaNeural` - Female (Australia)

---

## 🔒 Security Considerations

1. **File Uploads**: 
   - Only PDF files are accepted
   - Maximum file size configurable (default 50MB)
   - Files validated before processing

2. **Access Control**:
   - Students can only view their own generations
   - Teachers/managers can view all submissions
   - Proper Moodle capability checks enforced

3. **API Security**:
   - Backend service should be behind a firewall
   - Use HTTPS in production
   - API key stored securely in Moodle config

4. **Cleanup**:
   - Temporary files automatically deleted after 24 hours
   - Generated files stored in Moodle's file system

---

## 🐛 Troubleshooting

### Backend Service Not Starting

```bash
# Check if port 8000 is in use
lsof -i :8000

# Check Python dependencies
pip install -r requirements.txt

# Check logs
python backend_service.py 2>&1 | tee backend.log
```

### Generation Fails with "API Call Failed"

1. Verify backend service is running:
   ```bash
   curl http://localhost:8000/health
   ```

2. Check API URL in Moodle admin settings
3. Verify Gemini API key is valid
4. Check backend service logs

### PDF Compilation Errors

- Ensure LaTeX packages are installed: `texlive-full` or `mactex`
- Check poster generation logs in backend service
- Verify `baposter.cls` is in the working directory

### Video Generation Timeout

- Increase timeout in `locallib.php`:
  ```php
  $curl->setopt(['CURLOPT_TIMEOUT' => 60]);  // Increase from 30
  ```
- Optimize PDF size before upload
- Reduce `MAX_WORKERS` in backend service

---

## 📊 Database Schema

### `mdl_aitutorial`
Main activity instances.

| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| course | int | Course ID |
| name | varchar | Activity name |
| intro | text | Description |
| timecreated | int | Creation timestamp |

### `mdl_aitutorial_jobs`
Generation job tracking.

| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| aitutorialid | int | Activity instance ID |
| userid | int | User who submitted |
| job_type | varchar | video/poster/both |
| status | varchar | pending/processing/completed/failed |
| progress | int | 0-100 percentage |
| video_fileid | int | Moodle file ID (video) |
| poster_fileid | int | Moodle file ID (poster) |
| error_message | text | Error details if failed |

---

## 🔄 Cron Configuration

The plugin uses Moodle's cron system to poll for job status updates.

**Default**: Runs every 60 seconds

To adjust:
```php
// In version.php
$plugin->cron = 30;  // Every 30 seconds
```

Ensure Moodle cron is configured:
```bash
# Add to crontab
* * * * * php /path/to/moodle/admin/cli/cron.php
```

---

## 🎨 Customization

### Adding New Scene Types

Edit `consolidated_videogenerator.py`:
```python
STANDALONE_SCENE_TYPES = {
    "new_diagram": "new_diagram.py",
    # ... add more
}
```

### Custom CSS

Modify `styles.css` in the plugin directory.

### Branding

Replace gradient colors in CSS:
```css
background: linear-gradient(90deg, #YOUR_COLOR_1 0%, #YOUR_COLOR_2 100%);
```

---

## 📝 Development Roadmap

### Phase 1 (Current - MVP)
- ✅ PDF upload and validation
- ✅ Video generation
- ✅ Poster generation
- ✅ Job status tracking
- ✅ Moodle file system integration

### Phase 2 (Planned)
- [ ] RAG system integration for smarter content extraction
- [ ] Multi-document support (combine multiple PDFs)
- [ ] Gradebook integration (auto-grade completed tutorials)
- [ ] Student feedback/rating system
- [ ] Bulk generation for teachers

### Phase 3 (Future)
- [ ] Real-time WebSocket updates (instead of polling)
- [ ] Video editing tools (trim, add subtitles)
- [ ] Poster template customization
- [ ] Multi-language TTS support
- [ ] Analytics dashboard for teachers

---

## 🤝 Contributing

Contributions welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Submit a pull request

---

## 📄 License

This plugin is part of the Tutorial Generator project and follows the same license.

---

## 🆘 Support

- **Issues**: Open an issue on GitHub
- **Documentation**: See `/mod/aitutorial/README.md`
- **Moodle Docs**: [Activity Modules Guide](https://docs.moodle.org/en/Activity_modules)

---

**Built for Moodle 4.0+ | Powered by Gemini AI, Edge TTS & LaTeX**
