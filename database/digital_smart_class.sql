-- ============================================================
-- DIGITAL SMART CLASS — Professional E-Learning Database Schema
-- Compatible with MySQL 5.7+ / 8.0+ / MariaDB / phpMyAdmin
-- Database: digital_smart_class
-- ============================================================

CREATE DATABASE IF NOT EXISTS `digital_smart_class` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `digital_smart_class`;

-- 1. ROLES TABLE
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE, -- 'admin', 'teacher', 'student', 'accountant'
    `display_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`) VALUES
(1, 'admin', 'System Administrator', 'Full platform oversight, approvals, and controls'),
(2, 'teacher', 'Course Instructor', 'Creates courses, lessons, and reviews students'),
(3, 'student', 'Student Learner', 'Discovers, purchases, and studies courses'),
(4, 'accountant', 'Financial Accountant', 'Verifies payments, tracks balances, and audits revenue')
ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`);

-- 2. USERS TABLE
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL DEFAULT 3,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NULL,
    `password` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    `qualification` VARCHAR(200) NULL,
    `experience` VARCHAR(100) NULL,
    `address` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. COURSE CATEGORIES TABLE
CREATE TABLE IF NOT EXISTS `course_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `icon` VARCHAR(50) DEFAULT 'fas fa-video',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `course_categories` (`id`, `name`, `slug`, `description`, `icon`) VALUES
(1, 'YouTube & Content Creation', 'youtube-content-creation', 'Learn YouTube from ground up, build channels, and scale audience', 'fab fa-youtube'),
(2, 'Content Creation', 'content-creation', 'Vertical video production, viral algorithms, and hooks', 'fas fa-bolt'),
(3, 'AI & Content Creation', 'ai-content-creation', 'AI scriptwriting, neural voiceovers, and automated production', 'fas fa-robot')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 4. COURSES TABLE
CREATE TABLE IF NOT EXISTS `courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `teacher_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(220) NOT NULL UNIQUE,
    `subtitle` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NOT NULL,
    `level` ENUM('Beginner', 'Beginner → Intermediate', 'Intermediate', 'Advanced', 'All Levels') DEFAULT 'Beginner',
    `duration_weeks` VARCHAR(50) DEFAULT '4 Weeks',
    `duration_hours` INT DEFAULT 6,
    `total_lessons` INT DEFAULT 10,
    `price` DECIMAL(10, 2) NOT NULL DEFAULT 30000.00,
    `currency` VARCHAR(10) DEFAULT 'RWF',
    `thumbnail` VARCHAR(255) DEFAULT 'assets/images/course-default.jpg',
    `is_featured` TINYINT(1) DEFAULT 1,
    `status` ENUM('draft', 'published', 'archived') DEFAULT 'published',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `course_categories`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. MODULES TABLE
CREATE TABLE IF NOT EXISTS `modules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `title` VARCHAR(180) NOT NULL,
    `order_num` INT DEFAULT 1,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. LESSONS TABLE
CREATE TABLE IF NOT EXISTS `lessons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `module_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `content` LONGTEXT NULL,
    `video_url` VARCHAR(255) NULL,
    `duration_minutes` INT DEFAULT 20,
    `order_num` INT DEFAULT 1,
    `is_free_preview` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. MATERIALS TABLE (PDF, Presentations, Notes, Project Files)
CREATE TABLE IF NOT EXISTS `materials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `lesson_id` INT NULL,
    `title` VARCHAR(180) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` ENUM('pdf', 'presentation', 'document', 'zip', 'notes') DEFAULT 'pdf',
    `file_size` VARCHAR(50) DEFAULT '1.5 MB',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. ENROLLMENTS TABLE
CREATE TABLE IF NOT EXISTS `enrollments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `status` ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `activated_at` TIMESTAMP NULL,
    UNIQUE KEY `user_course_unique` (`user_id`, `course_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. PAYMENTS TABLE (MTN Mobile Money, Airtel Money, Bank Transfer)
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `enrollment_id` INT NULL,
    `user_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `currency` VARCHAR(10) DEFAULT 'RWF',
    `payment_method` VARCHAR(50) DEFAULT 'MTN Mobile Money',
    `transaction_ref` VARCHAR(100) NOT NULL,
    `sender_phone` VARCHAR(30) NULL,
    `proof_file` VARCHAR(255) NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `accountant_note` TEXT NULL,
    `reviewed_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. VIDEO_PROGRESS TABLE (Tracks watch minutes & percentage)
CREATE TABLE IF NOT EXISTS `video_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `lesson_id` INT NOT NULL,
    `watched_seconds` INT DEFAULT 0,
    `total_seconds` INT DEFAULT 0,
    `watch_percentage` DECIMAL(5, 2) DEFAULT 0.00,
    `is_completed` TINYINT(1) DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `user_video_lesson` (`user_id`, `lesson_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. LESSON_PROGRESS TABLE (Mark completed status)
CREATE TABLE IF NOT EXISTS `lesson_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `lesson_id` INT NOT NULL,
    `is_completed` TINYINT(1) DEFAULT 1,
    `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_lesson_unique` (`user_id`, `lesson_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. TEACHER_APPLICATIONS TABLE (Instructor vetting workflow)
CREATE TABLE IF NOT EXISTS `teacher_applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(120) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `qualification` VARCHAR(200) NOT NULL,
    `experience` VARCHAR(100) NOT NULL,
    `address` VARCHAR(255) NULL,
    `cv_file` VARCHAR(255) NULL,
    `certificate_file` VARCHAR(255) NULL,
    `photo_file` VARCHAR(255) NULL,
    `bio` TEXT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `admin_note` TEXT NULL,
    `reviewed_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. NOTIFICATIONS TABLE
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. AUDIT_LOGS TABLE (Security and tracking)
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DEFAULT USERS (Password for all accounts is: Password@123)
-- Hash generated via password_hash('Password@123', PASSWORD_BCRYPT)
-- ============================================================
INSERT INTO `users` (`id`, `role_id`, `full_name`, `email`, `phone`, `password`, `qualification`, `experience`, `address`, `status`) VALUES
(1, 1, 'Fabrice Administrator', 'erc@gmail.com', '+250788000001', '$2y$10$R1i.kLLeoLfGeS6Dvo/VNOnhSR0jcJoY895RciECdUSdrTRzqxBDK', 'MSc Software Engineering', '10 Years Tech Leadership', 'Kigali, Rwanda', 'active'),
(2, 2, 'David Mugisha', 'teacher@digitalsmart.rw', '+250788000002', '$2y$10$R1i.kLLeoLfGeS6Dvo/VNOnhSR0jcJoY895RciECdUSdrTRzqxBDK', 'Certified YouTube Strategist', '6 Years Content Creation', 'Kigali, Rwanda', 'active'),
(3, 3, 'Fabrice Learner', 'student@digitalsmart.rw', '+250788000003', '$2y$10$R1i.kLLeoLfGeS6Dvo/VNOnhSR0jcJoY895RciECdUSdrTRzqxBDK', 'High School Graduate', 'Aspiring Creator', 'Huye, Rwanda', 'active'),
(4, 4, 'Jean-Claude Kalisa', 'accountant@digitalsmart.rw', '+250788000004', '$2y$10$R1i.kLLeoLfGeS6Dvo/VNOnhSR0jcJoY895RciECdUSdrTRzqxBDK', 'CPA Rwanda', '8 Years Financial Audit', 'Kigali, Rwanda', 'active'),
(5, 2, 'Sarah Keza (Applicant)', 'newteacher@digitalsmart.rw', '+250788000005', '$2y$10$R1i.kLLeoLfGeS6Dvo/VNOnhSR0jcJoY895RciECdUSdrTRzqxBDK', 'Video Production Diploma', '3 Years Freelancing', 'Rubavu, Rwanda', 'pending')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- SEED TEACHER APPLICATION
INSERT INTO `teacher_applications` (`id`, `user_id`, `full_name`, `email`, `phone`, `qualification`, `experience`, `address`, `bio`, `status`) VALUES
(1, 5, 'Sarah Keza', 'newteacher@digitalsmart.rw', '+250788000005', 'Video Production Diploma', '3 Years Freelancing', 'Rubavu, Rwanda', 'Passionate about teaching motion graphics and editing.', 'pending')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- ============================================================
-- SEED 3 EXACT COURSES FROM USER SPECIFICATION
-- ============================================================

-- Course 01: Understand YouTube — Beginner to Confident Creator
INSERT INTO `courses` (`id`, `category_id`, `teacher_id`, `title`, `slug`, `subtitle`, `description`, `level`, `duration_weeks`, `duration_hours`, `total_lessons`, `price`, `currency`, `thumbnail`, `is_featured`, `status`) VALUES
(1, 1, 2, 
'Understand YouTube — Beginner to Confident Creator', 
'understand-youtube-beginner-to-confident-creator', 
'Learn YouTube from the ground up. This course is designed for complete beginners who want to understand how YouTube works and start building their own channel.', 
'Learn YouTube from the ground up. This course is designed for complete beginners who want to understand how YouTube works and start building their own channel. You will gain mastery over channel setup, studio analytics, content ideation, titles, descriptions, custom thumbnails, and sustainable audience growth.', 
'Beginner', '4 Weeks', 6, 10, 30000.00, 'RWF', 'assets/images/course-youtube.jpg', 1, 'published')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Course 02: YouTube Shorts Creation — From Idea to Short
INSERT INTO `courses` (`id`, `category_id`, `teacher_id`, `title`, `slug`, `subtitle`, `description`, `level`, `duration_weeks`, `duration_hours`, `total_lessons`, `price`, `currency`, `thumbnail`, `is_featured`, `status`) VALUES
(2, 2, 2, 
'YouTube Shorts Creation — From Idea to Short', 
'youtube-shorts-creation-from-idea-to-short', 
'Learn how to create professional YouTube Shorts from an idea, record or generate content, edit it, and publish it professionally.', 
'Learn how to create professional YouTube Shorts from an idea, record or generate content, edit it, and publish it professionally. Master 3-second retention hooks, vertical pacing, kinetic caption styling, copyright-free sound selection, and viral algorithm optimization.', 
'Beginner → Intermediate', '4 Weeks', 5, 12, 30000.00, 'RWF', 'assets/images/course-shorts.jpg', 1, 'published')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Course 03: Make Long Videos With AI — Complete AI Video Creation
INSERT INTO `courses` (`id`, `category_id`, `teacher_id`, `title`, `slug`, `subtitle`, `description`, `level`, `duration_weeks`, `duration_hours`, `total_lessons`, `price`, `currency`, `thumbnail`, `is_featured`, `status`) VALUES
(3, 3, 2, 
'Make Long Videos With AI — Complete AI Video Creation', 
'make-long-videos-with-ai-complete-ai-video-creation', 
'Learn how to use AI-assisted workflows to plan, write, produce, edit, and publish professional long-form YouTube videos.', 
'Learn how to use AI-assisted workflows to plan, write, produce, edit, and publish professional long-form YouTube videos. Discover high-RPM niches, automate deep topic research, generate neural voiceovers, assemble AI b-roll and motion graphics, and perform data-driven optimization.', 
'Intermediate', '6 Weeks', 8, 15, 40000.00, 'RWF', 'assets/images/course-ai.jpg', 1, 'published')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- ============================================================
-- SEED MODULES & LESSONS (Matches specification)
-- ============================================================

-- Modules for Course 1
INSERT INTO `modules` (`id`, `course_id`, `title`, `order_num`) VALUES
(1, 1, 'Module 1: Understanding YouTube & Channel Setup', 1),
(2, 1, 'Module 2: Content Creation & Thumbnail Design', 2),
(3, 1, 'Module 3: Optimization, Analytics & Growth', 3)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Lessons for Course 1 (10 Lessons)
INSERT INTO `lessons` (`id`, `module_id`, `title`, `content`, `video_url`, `duration_minutes`, `order_num`, `is_free_preview`) VALUES
(1, 1, '1. Understanding YouTube Ecosystem', 'How the platform operates, monetization criteria, and creator fundamentals.', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 20, 1, 1),
(2, 1, '2. Creating a YouTube Channel', 'Step-by-step account configuration and brand account setup.', '', 18, 2, 0),
(3, 1, '3. Setting Up Your Profile & Channel Art', 'Branding essentials, banners, logos, and layout optimization.', '', 22, 3, 0),
(4, 1, '4. YouTube Studio Basics', 'Navigating the dashboard, content tabs, and comments.', '', 25, 4, 0),
(5, 2, '5. Finding Profitable Content Ideas', 'Research techniques for identifying viewer demand.', '', 20, 1, 0),
(6, 2, '6. Creating Engaging Video Titles', 'Title frameworks that attract high click-through rates.', '', 15, 2, 0),
(7, 2, '7. Writing Compelling Descriptions', 'SEO descriptions, timestamps, and affiliate linking.', '', 18, 3, 0),
(8, 2, '8. Creating High-CTR Thumbnails', 'Visual contrast, face psychology, and text positioning.', '', 30, 4, 0),
(9, 3, '9. Understanding Views & Subscribers', 'Interpreting traffic sources, watch time, and retention curves.', '', 25, 1, 0),
(10, 3, '10. Basic Channel Growth Strategies', 'Promotion methods, consistency, and initial 1,000 subscribers push.', '', 30, 2, 0)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Modules for Course 2 (YouTube Shorts)
INSERT INTO `modules` (`id`, `course_id`, `title`, `order_num`) VALUES
(4, 2, 'Module 1: Shorts Ideation & Strong Hooks', 1),
(5, 2, 'Module 2: Recording, Vertical Editing & Captions', 2),
(6, 2, 'Module 3: Sound Design, Publishing & Analytics', 3)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Lessons for Course 2 (12 Lessons)
INSERT INTO `lessons` (`id`, `module_id`, `title`, `content`, `video_url`, `duration_minutes`, `order_num`, `is_free_preview`) VALUES
(11, 4, '1. Finding Viral Shorts Ideas', 'Discovering trending vertical topics before they peak.', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 15, 1, 1),
(12, 4, '2. Creating Strong Scroll-Stopping Hooks', 'The first 3 seconds: visual and auditory patterns that hook viewers.', '', 18, 2, 0),
(13, 4, '3. Writing Short Scripts for Vertical Formats', 'Keeping pacing fast and removing filler words.', '', 16, 3, 0),
(14, 5, '4. Recording Shorts with a Smartphone', 'Camera settings, framing 9:16, lighting, and audio clarity.', '', 22, 1, 0),
(15, 5, '5. Editing Vertical Videos (Mobile & PC)', 'Quick-cut techniques, zoom transitions, and pacing.', '', 28, 2, 0),
(16, 5, '6. Adding Dynamic Captions', 'Generating kinetic auto-captions with custom font highlights.', '', 20, 3, 0),
(17, 5, '7. Adding Music & Trending Sound Effects', 'Sourcing viral sounds without copyright strikes.', '', 18, 4, 0),
(18, 6, '8. Creating Attractive Shorts Packaging', 'Selecting thumbnail frames and eye-catching titles.', '', 15, 1, 0),
(19, 6, '9. Publishing & Scheduling Shorts for Peak Hours', 'Best posting times, tags, and hashtag optimization.', '', 14, 2, 0),
(20, 6, '10. Understanding Shorts Analytics', 'Shown in Feed vs Viewed ratio, swipe-away rates, and retention.', '', 24, 3, 0),
(21, 6, '11. Shorts Monetization & Ad Revenue', 'YouTube Shorts Fund and ad revenue sharing mechanics.', '', 20, 4, 0),
(22, 6, '12. Scaling to Daily Vertical Output', 'Batching production systems to produce 30 Shorts a month.', '', 30, 5, 0)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Modules for Course 3 (Make Long Videos with AI)
INSERT INTO `modules` (`id`, `course_id`, `title`, `order_num`) VALUES
(7, 3, 'Module 1: Planning & AI-Assisted Research', 1),
(8, 3, 'Module 2: Scriptwriting & Voice-Over Synthesis', 2),
(9, 3, 'Module 3: AI Visuals, Video Editing & Thumbnails', 3),
(10, 3, 'Module 4: Publishing & Performance Analytics', 4)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Lessons for Course 3 (15 Lessons)
INSERT INTO `lessons` (`id`, `module_id`, `title`, `content`, `video_url`, `duration_minutes`, `order_num`, `is_free_preview`) VALUES
(23, 7, '1. Finding Long-Video Ideas with High RPM', 'Selecting lucrative niches: tech, finance, documentaries.', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 25, 1, 1),
(24, 7, '2. Planning Content Structure & Narrative Arcs', 'Hook, premise, build-up, climax, and call to action.', '', 22, 2, 0),
(25, 7, '3. AI-Assisted Research & Fact Checking', 'Prompting AI to synthesize verified deep research.', '', 20, 3, 0),
(26, 8, '4. Creating Scripts with AI (10 to 20 Minutes)', 'Multi-prompt script engineering for human conversational tone.', '', 32, 1, 0),
(27, 8, '5. Voice-Over Creation with Neural TTS', 'Generating hyper-realistic narration with proper cadence.', '', 25, 2, 0),
(28, 8, '6. Audio Cleanup & Equalization', 'Adding warmth, noise reduction, and background music leveling.', '', 20, 3, 0),
(29, 9, '7. AI-Generated Visuals & B-Roll Sourcing', 'Prompting image generators for cinematic consistency.', '', 35, 1, 0),
(30, 9, '8. Motion Graphics & Animating Static Images', 'Parallax effects, subtle zoom, and keyframe motion.', '', 28, 2, 0),
(31, 9, '9. Video Editing on the Multi-Track Timeline', 'Assembling voiceover, AI imagery, and text transitions.', '', 40, 3, 0),
(32, 9, '10. Sound Design & Cinematic Atmosphere', 'Whooshes, risers, ambient sounds, and pacing.', '', 22, 4, 0),
(33, 9, '11. Creating High-CTR AI Thumbnails', 'Prompting eye-popping thumbnail backgrounds and typography.', '', 25, 5, 0),
(34, 10, '12. Writing Titles & Descriptions for Long Form', 'Search and suggested video packaging strategies.', '', 18, 1, 0),
(35, 10, '13. Publishing Long Videos with Chapters & Cards', 'Optimizing metadata, chapter timestamps, and end screens.', '', 20, 2, 0),
(36, 10, '14. Understanding Long-Form Analytics', 'Audience retention troughs, average view duration, and CTR.', '', 26, 3, 0),
(37, 10, '15. Scaling Automated Long-Form Production', 'Building an assembly line with freelancers or AI tools.', '', 30, 4, 0)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- ============================================================
-- SEED SAMPLE MATERIALS (PDFs, Notes, Presentations)
-- ============================================================
INSERT INTO `materials` (`id`, `course_id`, `lesson_id`, `title`, `file_path`, `file_type`, `file_size`) VALUES
(1, 1, 1, 'YouTube Starter Guide & Checklist.pdf', 'uploads/documents/youtube-starter-guide.pdf', 'pdf', '2.4 MB'),
(2, 1, 8, 'High-Converting Thumbnail Templates.zip', 'uploads/documents/thumbnail-templates.zip', 'zip', '15.8 MB'),
(3, 2, 11, '50 Viral Shorts Hooks & Script Formulas.pdf', 'uploads/documents/shorts-hooks-formulas.pdf', 'pdf', '1.8 MB'),
(4, 2, 15, 'Vertical Video Editing Presets.zip', 'uploads/documents/editing-presets.zip', 'zip', '8.2 MB'),
(5, 3, 26, 'Long-Form AI Scriptwriting Prompt Masterbook.pdf', 'uploads/documents/ai-script-prompts.pdf', 'pdf', '3.1 MB'),
(6, 3, 33, 'AI Video Production Presentation Slide Deck.pdf', 'uploads/presentations/ai-production-deck.pdf', 'presentation', '5.6 MB')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- ============================================================
-- SEED ACTIVE ENROLLMENT & PAYMENT FOR DEMO STUDENT (User ID 3)
-- Course 2 is unlocked so the student can immediately test learning.php!
-- ============================================================
INSERT INTO `enrollments` (`id`, `user_id`, `course_id`, `status`, `activated_at`) VALUES
(1, 3, 2, 'active', NOW()),
(2, 3, 1, 'pending', NULL)
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

INSERT INTO `payments` (`id`, `enrollment_id`, `user_id`, `course_id`, `amount`, `currency`, `payment_method`, `transaction_ref`, `sender_phone`, `status`, `reviewed_by`) VALUES
(1, 1, 3, 2, 30000.00, 'RWF', 'MTN Mobile Money', 'MP241004.1022.B991', '+250788000003', 'approved', 1),
(2, 2, 3, 1, 30000.00, 'RWF', 'MTN Mobile Money', 'MP241004.1144.C318', '+250788000003', 'pending', NULL)
ON DUPLICATE KEY UPDATE `transaction_ref` = VALUES(`transaction_ref`);

-- Progress for Student on Course 2 (80% completed as in mockup!)
INSERT INTO `lesson_progress` (`user_id`, `course_id`, `lesson_id`, `is_completed`) VALUES
(3, 2, 11, 1),
(3, 2, 12, 1),
(3, 2, 13, 1),
(3, 2, 14, 1),
(3, 2, 15, 1)
ON DUPLICATE KEY UPDATE `is_completed` = VALUES(`is_completed`);
