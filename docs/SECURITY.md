# Security Model

## Protected content
- Course videos and materials are streamed through `student/file.php`.
- The gateway validates enrollment and payment state before returning bytes.
- Free preview lessons remain publicly streamable.
- Admin users can access all course content.

## Uploads
- Upload names are randomized to reduce guessing.
- On Apache, `uploads/.htaccess` disables direct browser access.
- PHP's gateway reads files from disk independently of public web routing.

## Notes for local development
The PHP built-in server ignores `.htaccess`. Use Apache/XAMPP locally when validating upload protection; the PHP gateway still enforces enrollment checks in both environments.