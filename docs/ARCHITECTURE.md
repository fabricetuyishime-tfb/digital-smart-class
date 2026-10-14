# Digital Smart Class — Architecture

## Stack
- Backend: PHP 8+, PDO, MySQL/MariaDB
- Frontend: semantic HTML5, CSS3, small vanilla JavaScript
- Asset delivery: PHP file gateway for enrolled learners
- Auth: PHP sessions with RBAC guards for student, teacher, admin, and accountant roles

## Portals
- Public: home, catalog, course details, about, contact, authentication
- Student: dashboard, purchase flow, payments, learning room
- Teacher: courses, modules, lessons, materials, students
- Admin: users, teachers, courses, payments, enrollments, reports
- Accountant: verification and finance reporting

## Content security
Uploaded videos and documents are not exposed directly. The learning room requests them through `student/file.php`, which checks authentication, enrollment, and active/completed payment state before streaming. Free-preview lessons remain accessible. On Apache, `uploads/.htaccess` also blocks direct browser access.