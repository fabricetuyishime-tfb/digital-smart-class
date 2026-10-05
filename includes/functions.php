<?php
/**
 * Global Utility Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * HTML Escaping shorthand for XSS prevention
 */
function e($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency amounts nicely (e.g., 30,000 RWF)
 */
function format_money($amount, $currency = 'RWF') {
    return number_format((float)$amount, 0, '.', ',') . ' ' . $currency;
}

/**
 * Set a session flash message
 */
function set_flash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Get and clear a session flash message
 */
function get_flash($type) {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

/**
 * Render flash alert banner if present
 */
function render_flash_messages() {
    $types = ['success' => 'alert-success', 'error' => 'alert-danger', 'info' => 'alert-info'];
    $html = '';
    foreach ($types as $type => $cssClass) {
        $msg = get_flash($type);
        if ($msg) {
            $html .= '<div class="alert ' . $cssClass . '">' . e($msg) . '</div>';
        }
    }
    return $html;
}

/**
 * CSRF Protection Helpers
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Fallback course data if MySQL database is not yet migrated/connected
 */
function get_fallback_courses() {
    return [
        [
            'id' => 1,
            'title' => 'YouTube Creator Academy: From Absolute Beginner to Confident Creator',
            'slug' => 'youtube-creator-academy-beginner-to-confident',
            'subtitle' => 'Build an unshakeable foundation: master channel architecture, algorithm psychology, clickable thumbnails, and initial monetization.',
            'category_name' => 'YouTube & Content Creation',
            'description' => 'Step into the world of YouTube with absolute clarity. This complete blueprint takes you by the hand from having zero subscribers to launching, optimizing, and growing your first authority channel. Learn how the YouTube recommendation algorithm truly works, understand audience retention and CTR (Click-Through-Rate), design high-converting thumbnails using free tools, craft engaging titles, and deploy battle-tested channel growth strategies.',
            'level' => 'Beginner',
            'price' => 30000,
            'currency' => 'RWF',
            'duration_hours' => 6,
            'total_lessons' => 12,
            'thumbnail' => 'assets/images/course-youtube.jpg',
            'instructor' => 'David Mugisha (Lead YouTube Coach)',
            'highlights' => [
                'Understanding the YouTube Recommendation Engine & CTR',
                'Setting up YouTube Studio & Channel Art',
                'Click-worthy Thumbnails & Title Psychology',
                'Recording High-Quality Video with Everyday Gear',
                'Organic Growth Tactics to Reach Your First 1,000 Subscribers'
            ]
        ],
        [
            'id' => 2,
            'title' => 'Viral YouTube Shorts Engine: Idea, Script, Edit & 1M+ Views',
            'slug' => 'viral-youtube-shorts-engine-idea-to-views',
            'subtitle' => 'Dominate vertical video: learn scriptwriting, dynamic pacing, auto-captioning, audio hooks, and Shorts algorithm mechanics.',
            'category_name' => 'Short-Form Video & Viral Media',
            'description' => 'YouTube Shorts is the fastest organic growth engine on the internet today. In this high-energy, practical course, you will learn the exact 3-second hook framework that stops the scroll, vertical pacing techniques, mobile and desktop editing shortcuts, copyright-free sound selection, and metadata optimization.',
            'level' => 'Beginner → Intermediate',
            'price' => 30000,
            'currency' => 'RWF',
            'duration_hours' => 5,
            'total_lessons' => 10,
            'thumbnail' => 'assets/images/course-shorts.jpg',
            'instructor' => 'David Mugisha & Guest Creators',
            'highlights' => [
                'The 3-Second Scroll-Stopping Hook Blueprint',
                'Fast-Paced Vertical Editing & B-Roll Timing',
                'Kinetic Auto-Captions and Sound Design',
                'Viral Trend Identification & Competitor Analysis',
                'Monetizing Shorts via Brand Deals & YouTube Partner Program'
            ]
        ],
        [
            'id' => 3,
            'title' => 'AI-Powered Long-Form Video Mastery: Automated Production & Monetization',
            'slug' => 'ai-powered-long-form-video-mastery',
            'subtitle' => 'Plan, write, generate voiceovers, source AI visuals, and assemble high-retention long-form YouTube videos 10x faster.',
            'category_name' => 'AI & Video Automation',
            'description' => 'Harness state-of-the-art Artificial Intelligence to produce broadcast-quality 10 to 20 minute YouTube videos without burning out. This comprehensive masterclass covers niche discovery, AI-driven script research, prompt engineering for natural neural voice narration, automated visual assembly with AI b-roll and motion graphics, audio mastering, and automated thumbnail creation.',
            'level' => 'Intermediate',
            'price' => 40000,
            'currency' => 'RWF',
            'duration_hours' => 8,
            'total_lessons' => 14,
            'thumbnail' => 'assets/images/course-ai.jpg',
            'instructor' => 'AI Media Production Lab',
            'highlights' => [
                'High-RPM Niche Research using AI Analysis Prompts',
                'Crafting 15-Minute Retention-Driven Scripts with AI',
                'Hyper-Realistic Neural Voiceover Production',
                'Generating AI Images, B-Roll, and Motion Graphics',
                'Rapid Timeline Assembly & Automated Thumbnail Design'
            ]
        ]
    ];
}
