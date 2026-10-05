<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('teacher');
$user = current_user();
$page_title = "Course Materials — Digital Smart Class";

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar" style="background: #0284c7;"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="sidebar-user-info">
                <h5><?= e($user['full_name']); ?></h5>
                <span>Instructor</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="<?= site_url('teacher/dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/courses.php'); ?>"><i class="fas fa-book"></i> My Courses</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/add-course.php'); ?>"><i class="fas fa-plus-circle"></i> Create Course</a></li>
            <li class="sidebar-item active"><a href="<?= site_url('teacher/materials.php'); ?>"><i class="fas fa-file-upload"></i> Course Materials</a></li>
            <li class="sidebar-item"><a href="<?= site_url('teacher/students.php'); ?>"><i class="fas fa-users"></i> Enrolled Students</a></li>
        </ul>
    </aside>

    <main class="dashboard-main">
        <div class="dashboard-title-bar">
            <div>
                <h2>Course Materials & Downloads</h2>
                <p style="color: var(--gray-500); font-size: 0.95rem;">Upload downloadable PDFs, video scripts, AI prompts, and cheat sheets.</p>
            </div>
        </div>

        <div style="background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-lg); padding: 1.75rem; margin-bottom: 2rem; max-width: 650px;">
            <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: var(--gray-900);">Upload Resource File</h4>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Material Title</label>
                    <input type="text" class="form-control" placeholder="e.g. YouTube Shorts 50 High-Converting Script Templates">
                </div>
                <div class="form-group">
                    <label class="form-label">Select Course</label>
                    <select class="form-control">
                        <option>YouTube Creator Academy</option>
                        <option>Viral YouTube Shorts Engine</option>
                        <option>AI-Powered Long-Form Video Mastery</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Attach File (PDF, ZIP, DOCX)</label>
                    <input type="file" class="form-control">
                </div>
                <button type="button" class="btn btn-primary" onclick="alert('File uploaded successfully!')">
                    <i class="fas fa-upload"></i> Upload Resource
                </button>
            </form>
        </div>

        <div class="table-card">
            <div class="table-card-header">
                <h3 style="font-size: 1.15rem; font-weight: 700;">Uploaded Course Documents</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Document Title</th>
                            <th>Course</th>
                            <th>File Type</th>
                            <th>Downloads</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-weight: 600;"><i class="far fa-file-pdf" style="color: #ef4444; margin-right: 0.5rem;"></i> YouTube Channel Optimization Checklist (PDF)</td>
                            <td>YouTube Creator Academy</td>
                            <td>PDF Document</td>
                            <td>42</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600;"><i class="far fa-file-alt" style="color: #0284c7; margin-right: 0.5rem;"></i> Top 50 Viral Hooks Formula</td>
                            <td>Viral YouTube Shorts Engine</td>
                            <td>Word DOCX</td>
                            <td>68</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600;"><i class="far fa-file-code" style="color: #7c3aed; margin-right: 0.5rem;"></i> AI Video Prompt Engineering Library</td>
                            <td>AI-Powered Long-Form Video Mastery</td>
                            <td>Markdown / Text</td>
                            <td>85</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
