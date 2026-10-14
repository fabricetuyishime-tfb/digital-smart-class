# DIGITAL SMART CLASS — Professional E-Learning Platform

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)
![Status](https://img.shields.io/badge/Status-Active-success)

A complete, production-grade online learning platform built strictly using **Pure PHP 8+, HTML5, CSS3, and MySQL**.
No React. No Java. No Spring Boot. No Node.js. No Bootstrap required. 100% lightweight, secure, and native web architecture.

---

## 🌟 1. Core Technology Stack

| Layer | Technology |
| :--- | :--- |
| **Backend** | Pure PHP 8+ (PDO Prepared Statements, Sessions, Password Hashing) |
| **Frontend** | HTML5 Semantic Markup |
| **Design** | Modern Custom CSS3 (Modular: `style.css`, `auth.css`, `dashboard.css`, `responsive.css`) |
| **Database** | MySQL 5.7+ / 8.0+ / MariaDB (`digital_smart_class`) |
| **Server** | Apache / XAMPP / Built-in PHP Development Server |
| **Tooling** | Windows Command Prompt (CMD) + VS Code |

### Native PHP Features Implemented
- **PHP Sessions:** User login and session state management.
- **PDO (PHP Data Objects):** Parameterized queries preventing SQL Injection.
- **`password_hash()` & `password_verify()`:** Secure BCrypt password encryption.
- **PHP File Uploads:** Validated storage for payment proofs, teacher CVs, certificates, and lesson notes.
- **PHP Authorization (RBAC):** Strict role-based page guards (`student-auth.php`, `teacher-auth.php`, `admin-auth.php`, `accountant-auth.php`).
- **Strict Backend Content Protection:** `learning.php` checks database enrollment before rendering paid videos and documents.

---

## 👥 2. Four Platform Roles

```
DIGITAL SMART CLASS
│
├── STUDENT       (Discovers courses, pays via MTN MoMo, submits proof, studies)
├── TEACHER       (Submits application, awaits admin approval, creates courses & lessons)
├── ADMIN         (Full control: approves teachers, manages courses, oversees finances)
└── ACCOUNTANT    (Reviews MTN MoMo/Airtel transaction IDs, approves enrollments, audits ledger)
```

---

## 📚 3. Flagship Course Catalog

### Course 01: Understand YouTube — Beginner to Confident Creator
- **Category:** YouTube & Content Creation
- **Level:** Beginner
- **Duration:** 4 Weeks (6 Hours)
- **Lessons:** 10 In-Depth Lessons
- **Tuition:** `30,000 RWF`
- **Curriculum:**
  1. Understanding YouTube Ecosystem
  2. Creating a YouTube Channel
  3. Setting Up Your Profile & Channel Art
  4. YouTube Studio Basics
  5. Finding Content Ideas
  6. Creating Video Titles
  7. Writing Descriptions
  8. Creating Thumbnails
  9. Understanding Views and Subscribers
  10. Basic Channel Growth Strategies

### Course 02: YouTube Shorts Creation — From Idea to Short
- **Category:** Content Creation
- **Level:** Beginner → Intermediate
- **Duration:** 4 Weeks (5 Hours)
- **Lessons:** 12 Practical Lessons
- **Tuition:** `30,000 RWF`
- **Curriculum:**
  1. Finding Shorts Ideas
  2. Creating Strong Scroll-Stopping Hooks
  3. Writing Short Scripts
  4. Recording Shorts with a Smartphone
  5. Editing Vertical Videos (Mobile & PC)
  6. Adding Dynamic Captions
  7. Adding Music & Trending Sound Effects
  8. Creating Attractive Shorts Packaging
  9. Publishing & Scheduling Shorts
  10. Understanding Shorts Analytics
  11. Shorts Monetization & Ad Revenue
  12. Scaling to Daily Vertical Output

### Course 03: Make Long Videos With AI — Complete AI Video Creation
- **Category:** AI & Content Creation
- **Level:** Intermediate
- **Duration:** 6 Weeks (8 Hours)
- **Lessons:** 15 Masterclass Lessons
- **Tuition:** `40,000 RWF`
- **Curriculum:**
  1. Finding Long-Video Ideas with High RPM
  2. Planning Content Structure & Narrative Arcs
  3. AI-Assisted Research & Fact Checking
  4. Creating Scripts with AI (10 to 20 Minutes)
  5. Voice-Over Creation with Neural TTS
  6. Audio Cleanup & Equalization
  7. AI-Generated Visuals & B-Roll Sourcing
  8. Motion Graphics & Animating Static Images
  9. Video Editing on the Multi-Track Timeline
  10. Sound Design & Cinematic Atmosphere
  11. Creating High-CTR AI Thumbnails
  12. Writing Titles & Descriptions
  13. Publishing Long Videos with Chapters
  14. Understanding Long-Form Analytics
  15. Scaling Automated Long-Form Production

---

## 💳 4. MTN Mobile Money Payment Workflow

```
SELECT COURSE
      ↓
BUY COURSE (student/buy-course.php)
      ↓
PAYMENT INSTRUCTIONS (*182*8*1*0788000001# — DIGITAL SMART CLASS LTD)
      ↓
MAKE PAYMENT ON PHONE
      ↓
COPY PAYMENT SMS MESSAGE
      ↓
SUBMIT PAYMENT PROOF (student/payment.php)
      ↓
STATUS = PENDING
      ↓
ADMIN / ACCOUNTANT REVIEWS (admin/payments.php or accountant/payments.php)
      ↓
 ┌───────────────┐
 │               │
APPROVED       REJECTED
 │               │
 ↓               ↓
ENROLLMENT      LOCKED
ACTIVATED
 │
 ↓
COURSE UNLOCKED (student/learning.php)
 │
 ├── Video Player with Duration Tracking
 ├── Lesson Notes [ Read Notes ]
 ├── Learning Materials [ Download PDF ] [ View Presentation ]
 └── [ MARK AS COMPLETED ] Button updates Progress
```

---

## 🔒 5. Strict Backend Protection Rule

Paid courses **are NOT hidden with CSS or JavaScript**.
When a student visits:
`student/learning.php?course_id=2`

PHP executes this server-side validation:
```
1. Is user logged in?           --> If NO, redirect to login.php
2. Is user a student or admin?  --> If NO, redirect to index.php
3. Does enrollment exist in DB? --> If NO, redirect to course-details.php
4. Is enrollment status active? --> If NO (pending), redirect to payment.php
5. YES --> Render video player, lesson notes, and learning materials.
```

---

## 📁 6. Complete Project Structure

```
digital-smart-class/
│
├── index.php                         <- Homepage (Hero, Why Us, Featured Courses)
├── login.php                         <- Secure authentication with demo quick-fill
├── register.php                      <- Student registration
├── logout.php                        <- Session destruction
├── courses.php                       <- Public course catalog
├── course-details.php                <- Course landing page with [ BUY COURSE ]
├── about.php                         <- Mission, local payment story, values
├── contact.php                       <- Contact form, phone, location in Kigali
│
├── config/
│   └── database.php                  <- PDO connection with preview fallback
│
├── includes/
│   ├── header.php                    <- Common header & metadata
│   ├── footer.php                    <- Footer & script tags
│   ├── navbar.php                    <- Brand navigation with notification bell
│   ├── auth.php                      <- Base auth helpers
│   ├── student-auth.php              <- Student guard
│   ├── teacher-auth.php              <- Teacher guard + admin approval check
│   ├── admin-auth.php                <- Super Admin guard
│   ├── accountant-auth.php           <- Accountant guard (finance only)
│   └── functions.php                 <- Money formatting (RWF) & XSS escaping
│
├── student/
│   ├── dashboard.php                 <- Stats, Continue Learning, Recommendations
│   ├── courses.php                   <- Course catalog
│   ├── course-details.php            <- Details view
│   ├── buy-course.php                <- MTN MoMo step-by-step instructions
│   ├── payment.php                   <- Submit transaction proof
│   ├── payments.php                  <- Student transaction ledger
│   ├── my-courses.php                <- Enrolled active courses
│   ├── learning.php                  <- Video room, notes, downloads, completion
│   ├── progress.php                  <- 90% watch threshold progress tracking
│   ├── notifications.php             <- Notifications list
│   └── profile.php                   <- Profile settings
│
├── teacher/
│   ├── dashboard.php                 <- My Courses, Student counts, Published
│   ├── application.php               <- Teacher registration & application form
│   ├── courses.php                   <- Assigned courses list
│   ├── create-course.php             <- Create course form
│   ├── edit-course.php               <- Edit title, description, pricing
│   ├── modules.php                   <- Add and manage course modules
│   ├── lessons.php                   <- Add lessons, video URLs, and notes
│   ├── materials.php                 <- Upload PDFs, presentations, presets
│   ├── students.php                  <- View students enrolled in their courses
│   └── profile.php                   <- Teacher profile
│
├── admin/
│   ├── dashboard.php                 <- Platform metrics (Students, Teachers, Revenue)
│   ├── students.php                  <- Manage student accounts
│   ├── teachers.php                  <- Manage teachers
│   ├── teacher-applications.php      <- Review CVs and approve/reject instructors
│   ├── courses.php                   <- Moderate course catalog
│   ├── categories.php                <- Add course categories
│   ├── payments.php                  <- 1-click payment verification engine
│   ├── enrollments.php               <- Student enrollments roster
│   ├── progress.php                  <- Student progress audit
│   ├── accountants.php               <- Manage accountant accounts
│   ├── reports.php                   <- Financial revenue analysis
│   ├── notifications.php             <- Broadcast alerts
│   └── settings.php                  <- MoMo codes and completion thresholds
│
├── accountant/
│   ├── dashboard.php                 <- Financial overview
│   ├── payments.php                  <- Verify payments
│   ├── approved.php                  <- Approved transactions list
│   ├── rejected.php                  <- Rejected transactions list
│   ├── revenue.php                   <- Revenue breakdown
│   └── reports.php                   <- Financial reports
│
├── assets/
│   ├── css/
│   │   ├── style.css                 <- Core layout & typography
│   │   ├── auth.css                  <- Login & registration styles
│   │   ├── dashboard.css             <- Stats, cards, video player & curriculum
│   │   └── responsive.css            <- Mobile and tablet breakpoints
│   └── js/
│       └── main.js                   <- UI transitions and alert dismissals
│
├── uploads/
│   ├── course-thumbnails/
│   ├── videos/
│   ├── documents/
│   ├── presentations/
│   ├── teacher-documents/
│   └── profiles/
│
├── database/
│   └── digital_smart_class.sql       <- 14 Tables + pre-seeded data
│
├── setup.bat                         <- 1-click CMD launcher & DB importer
└── README.md
```

---

## 💬 Support &amp; Contact

The platform exposes a single official support channel through WhatsApp, wired everywhere
(footer, floating launcher, contact page, payment screens, and admin slash settings).

| Channel | Value |
| :--- | :--- |
| **WhatsApp / Phone** | +250 798 532 388 (`0798532388`) |
| **Support email** | contact@digitalsmart.rw |
| **Hours** | Monday – Saturday, 08:00 – 18:00 |

To change the support number across the whole site, edit the helpers at the top of
`includes/functions.php`:

```php
function support_whatsapp_number() { return '250798532388'; } // 0798532388
function support_phone_display()   { return '+250 798 532 388'; }
function support_email()           { return 'contact@digitalsmart.rw'; }
```

WhatsApp links are generated with `support_whatsapp_url($message)`, which builds a
`https://wa.me/...` deep link and can pre-fill the first message.

---

## 🔑 7. Default Demo Accounts

All pre-configured accounts use the password: **`Password@123`**

| Role | Email | Password | Access Details |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `erc@gmail.com` | `Password@123` | Full system control, approves teachers & payments |
| **Approved Teacher** | `teacher@digitalsmart.rw` | `Password@123` | Full teaching privileges, courses & student roster |
| **Applicant Teacher** | `newteacher@digitalsmart.rw` | `Password@123` | Shows application pending review by administrator |
| **Enrolled Student** | `student@digitalsmart.rw` | `Password@123` | Course 2 active & unlocked; Course 1 pending |
| **Financial Accountant** | `accountant@digitalsmart.rw` | `Password@123` | Payment verification & revenue reports (no user/course tampering) |

---

## 💻 8. Command Prompt (CMD) Quick Run

### Option 1: Double-Click `setup.bat`
An interactive CMD menu will let you start the server or import the database in 1 click.

### Option 2: Run via PHP Built-in Server
```cmd
cd /d "C:\Users\TFB\OneDrive\Desktop\sdc"
C:\xampp\php\php.exe -d upload_max_filesize=512M -d post_max_size=512M -d memory_limit=512M -S localhost:8000 -t .
```
Then visit:
👉 **`http://localhost:8000`**

The `-d` overrides matter for teaching: PHP's default `upload_max_filesize` is only 2M,
which rejects real lesson videos. In Docker these limits are applied automatically from
[`docker/php.ini`](docker/php.ini), which the image copies into `conf.d`.

If PHP is already added to your PATH, you can use `php -S localhost:8000 -t .`
instead. The included `setup.bat` detects XAMPP automatically, even when PHP is
not on the Windows PATH.

### Option 3: Run via XAMPP Apache
```cmd
if not exist "C:\xampp\htdocs\digital-smart-class" mkdir "C:\xampp\htdocs\digital-smart-class"
xcopy /E /I /Y "C:\Users\TUYISHIME Fabrice\OneDrive\Desktop\sdc" "C:\xampp\htdocs\digital-smart-class"
```
Import database in CMD:
```cmd
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS digital_smart_class CHARACTER SET utf8mb4;"
C:\xampp\mysql\bin\mysql.exe -u root digital_smart_class < database\digital_smart_class.sql
```
Then visit:
👉 **`http://localhost/digital-smart-class/`**

### Option 4: Docker (recommended for hosting)
Vercel's static hosting cannot execute PHP, so the application must run on a PHP
host. This repository ships a `Dockerfile` (PHP 8.2 built-in server + MySQL) and a
`docker-compose.yml` for a full local stack.

```bash
docker compose up --build
# open http://localhost:8080
```

See [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) for Railway, Render, Fly.io, and
shared-hosting instructions, plus environment variables and the `/health.php`
check.

## Manual Smoke Tests

Run these checks after importing the database and starting Apache/MySQL:

1. Sign in as a student and confirm the student dashboard opens.
2. Sign in as a teacher, create a course, and confirm it appears under **My Courses**.
3. Create a module and lesson for that course; try both a video URL and a small MP4 upload, then confirm the lesson appears under **Video → Uploaded** and opens through `student/file.php`.
4. Upload a PDF/presentation through **Course Materials** and confirm it is listed and viewable; delete it and confirm the file disappears from `uploads/`.
5. As a student, buy a *different* course (submit a payment reference), then confirm `student/learning.php` is blocked while pending and unlocked after the accountant/admin **approves** the payment.
6. Open the course as an enrolled student and verify the video loads, materials download, and lesson progress can be saved.
7. With two teacher accounts, try opening the other teacher's course/module by changing the URL ID; expect a not-found response and no changes to their content. On a database failure, the page should show a generic message while technical details go to the PHP error log.
