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

## Teacher onboarding
Teacher applications are submitted through `teacher/application.php` and reviewed from the admin teacher-applications page. Approval activates the account and assigns the teacher role; login reconciles a legacy pending teacher account if its application is already approved. Rejected applications remain able to sign in to view their status and submit an update. Teacher-only pages verify the account role and active status against the database on each guarded request. Application files are downloaded through an authenticated admin endpoint rather than exposed as public upload URLs.

## Payments
Students submit a transaction reference for a published course. The server takes the amount and currency from that course record and records the payment and pending enrollment together in a database transaction. Administrators and accountants can approve or reject only pending payments; approval and course activation are committed together, and the student is notified of the result. Payment proofs are served through an authenticated admin/accountant endpoint. Payment submissions and reviews require the database and do not simulate successful writes when it is unavailable.

## Notifications
Signed-in users of every role can review and mark their own notifications as read from `notifications.php`. The student notifications URL redirects to this shared inbox for compatibility. Administrators send broadcasts from `admin/notifications.php`; individual notification records remain scoped to their recipient.

## Content security
Uploaded videos and documents are not exposed directly. The learning room requests them through `student/file.php`, which checks authentication, enrollment, and active/completed payment state before streaming. Free-preview lessons remain accessible. On Apache, `uploads/.htaccess` also blocks direct browser access.