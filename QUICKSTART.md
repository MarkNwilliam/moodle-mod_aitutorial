# 🎓 AI Tutorial Generator for Moodle - Quick Start Guide

## 📖 What This Does

Transform any educational PDF into:
- **Video Tutorial** with AI narration (text-to-speech)
- **Research Poster** with professional LaTeX formatting

Perfect for students who want to convert lecture notes, textbooks, or research papers into engaging learning materials.

---

## 🚀 Quick Start (5 Minutes)

### Step 1: Install the Plugin

```bash
# From your tutorialgeneratortest directory
./deploy_moodle_plugin.sh /path/to/your/moodle

# Example:
./deploy_moodle_plugin.sh /var/www/html/moodle
```

### Step 2: Complete Moodle Setup

1. **Visit Moodle Admin**:
   ```
   https://your-moodle.com/admin/
   ```
   
2. **Click "Upgrade Moodle database"** when prompted

3. **Configure Plugin**:
   - Go to: **Site Administration → Plugins → Activity Modules → AI Tutorial Generator**
   - Enter your **Gemini API Key** (get one from [Google AI Studio](https://aistudio.google.com/))
   - Verify Backend API URL is `http://localhost:8000`
   - Click **Save**

### Step 3: Start Backend Service

```bash
# If not using systemd (manual start)
cd /path/to/tutorialgeneratortest
source venv/bin/activate
python moodle_plugin/aitutorial/api/backend_service.py &
```

Verify it's running:
```bash
curl http://localhost:8000/health
# Should return: {"status": "ok", "version": "1.0.0"}
```

### Step 4: Add Activity to Course

1. **Navigate to your course** in Moodle
2. **Turn editing on** (gear icon → Turn editing on)
3. **Add an activity or resource**
4. Select **AI Tutorial Generator**
5. Configure:
   - **Name**: "Generate Tutorial from PDF"
   - **Description**: "Upload your lecture notes or textbook chapter to generate a narrated video tutorial and research poster"
   - **Generation Mode**: Both Video & Poster
6. **Save and return to course**

### Step 5: Test as Student

1. **Click the activity** you just created
2. **Upload a PDF** (lecture notes, textbook, etc.)
3. **Select generation mode** (Video, Poster, or Both)
4. **Click "Generate Tutorial"**
5. **Wait for completion** (progress bar updates automatically)
6. **View/Download your content**!

---

## 📝 Example Workflows

### Workflow 1: Student Creates Study Video

**Scenario**: Student has 50-page lecture notes PDF and wants a video to review.

1. Student navigates to AI Tutorial activity
2. Uploads `machine_learning_lectures.pdf`
3. Selects "Video Only"
4. Waits ~5 minutes
5. Gets a 45-minute narrated video covering all slides
6. Downloads video to watch offline

**Time saved**: Instead of re-reading 50 pages, student watches a structured video with narration.

---

### Workflow 2: Teacher Creates Poster from Research Paper

**Scenario**: Teacher wants to create a visual summary of a research paper for class discussion.

1. Teacher uploads `research_paper_transformers.pdf`
2. Selects "Poster Only"
3. Waits ~2 minutes
4. Gets a professional LaTeX poster with:
   - Title and authors
   - Key concepts highlighted
   - Main results summarized
   - Visual diagrams

**Use**: Print poster for classroom discussion or share as study guide.

---

### Workflow 3: Complete Tutorial Package

**Scenario**: Student preparing for finals wants comprehensive study materials.

1. Uploads `database_systems_textbook.pdf`
2. Selects "Both Video & Poster"
3. Waits ~8 minutes
4. Receives:
   - **Video**: Full narrated walkthrough (~60 min)
   - **Poster**: One-page summary for quick review

**Use**: Watch video for deep understanding, use poster for last-minute revision.

---

## 🎯 Best Practices

### What Works Best

✅ **High-quality PDFs** with clear text  
✅ **Structured content** (headings, bullet points, sections)  
✅ **Educational materials** (lectures, textbooks, tutorials)  
✅ **10-100 pages** (optimal generation time)  

### What to Avoid

❌ **Scanned PDFs** (images without OCR - no extractable text)  
❌ **Very short documents** (< 5 pages - limited content)  
❌ **Non-educational content** (novels, forms, invoices)  
❌ **Massive documents** (> 200 pages - long generation time)  

---

## 🎬 Video Features

The generated video includes:
- **PDF pages as slides**: Each page converted to image
- **AI narration**: Text extracted and read aloud by Edge TTS
- **Structured flow**: Pages processed in order
- **Professional quality**: HD resolution (1280x720)

**Voice options** (configurable in admin settings):
- `en-US-GuyNeural` - Male US English (default)
- `en-US-JennyNeural` - Female US English
- `en-GB-SoniaNeural` - Female UK English

---

## 📊 Poster Features

Generated posters include:
- **Professional LaTeX formatting**
- **Title and summary sections**
- **Key takeaways highlighted**
- **BAposter class** for modern design

**Poster sections**:
1. Title block
2. Introduction/Overview
3. Main content (3-4 columns)
4. Results/Findings
5. Conclusion
6. References

---

## ⚙️ Configuration Examples

### Allow Only Video Generation

**Admin Settings**:
- Enable Video: ✅ Yes
- Enable Poster: ❌ No

**Result**: Students can only generate videos, not posters.

---

### Limit File Size to 20MB

**Admin Settings**:
- Maximum File Size: `20`

**Result**: Students cannot upload PDFs larger than 20MB.

---

### Change Narration Voice

**Admin Settings**:
- TTS Voice: `en-US-JennyNeural`

**Result**: All videos use female voice instead of male.

---

## 🔧 Troubleshooting

### Problem: "Backend service not responding"

**Solution**:
```bash
# Check if service is running
systemctl status aitutorial-backend.service

# If not running, start it
systemctl start aitutorial-backend.service

# Check logs
journalctl -u aitutorial-backend -f
```

---

### Problem: "Generation failed: API call failed"

**Possible causes**:
1. Backend service not running
2. Wrong API URL in Moodle settings
3. Firewall blocking port 8000

**Solution**:
```bash
# Test backend service directly
curl http://localhost:8000/health

# If working, check Moodle can reach it
curl http://your-server-ip:8000/health
```

---

### Problem: "PDF compilation failed"

**Solution**:
```bash
# Install LaTeX packages (Ubuntu/Debian)
sudo apt-get install texlive-full

# Or for macOS
brew install --cask mactex

# Verify installation
pdflatex --version
```

---

### Problem: Video generation takes too long

**Solutions**:
1. **Reduce PDF size**: Use smaller PDFs (20-50 pages optimal)
2. **Increase timeout**: Edit `locallib.php`:
   ```php
   $curl->setopt(['CURLOPT_TIMEOUT' => 120]);  // 2 minutes
   ```
3. **Optimize backend**: Reduce `MAX_WORKERS` in `main.py`

---

## 📊 Monitoring Usage

### View All Generations (Teachers)

Teachers can see all student submissions by:
1. Navigating to the activity
2. Viewing the job history section
3. Checking status of each generation

### Database Queries

```sql
-- Count generations per course
SELECT c.fullname, COUNT(j.id) as total_generations
FROM mdl_aitutorial_jobs j
JOIN mdl_course c ON j.courseid = c.id
GROUP BY c.fullname;

-- Find failed generations
SELECT j.*, u.firstname, u.lastname
FROM mdl_aitutorial_jobs j
JOIN mdl_user u ON j.userid = u.id
WHERE j.status = 'failed'
ORDER BY j.timecreated DESC;

-- Average generation time
SELECT 
    AVG(timecompleted - timecreated) as avg_seconds,
    job_type
FROM mdl_aitutorial_jobs
WHERE status = 'completed'
GROUP BY job_type;
```

---

## 🎓 Integration with Course Design

### Use Case 1: Flipped Classroom

**Workflow**:
1. Teacher assigns textbook chapter reading
2. Students generate videos from chapter
3. Students watch AI-narrated videos before class
4. Class time used for discussion and activities

**Benefit**: Students learn at their own pace with audio-visual materials.

---

### Use Case 2: Exam Preparation

**Workflow**:
1. Students upload all lecture notes
2. Generate posters for quick review
3. Generate videos for comprehensive study
4. Share materials in study groups

**Benefit**: Automated creation of multi-format study guides.

---

### Use Case 3: Accessibility Support

**Workflow**:
1. Student with visual impairment uploads materials
2. Generates video with audio narration
3. Uses video as primary learning resource
4. Poster provides tactile/Braille reference

**Benefit**: Makes content accessible to diverse learners.

---

## 📞 Getting Help

- **Documentation**: `moodle_plugin/aitutorial/README.md`
- **Backend Logs**: `journalctl -u aitutorial-backend -f`
- **Moodle Logs**: Check `Site Administration → Reports → Logs`
- **Issues**: Open a GitHub issue with:
  - Error message
  - Backend logs
  - PDF sample (if applicable)

---

## 🎉 Success Checklist

- [ ] Plugin installed in Moodle
- [ ] Backend service running
- [ ] API key configured
- [ ] Activity added to course
- [ ] Test PDF uploaded successfully
- [ ] Video generated and playable
- [ ] Poster generated and viewable
- [ ] Students informed about new tool

**You're all set! Happy learning! 🚀**
