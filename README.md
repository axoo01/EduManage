# Student Management System
A web-based application for managing student records, built with PHP, MySQL, HTML, CSS, and JavaScript. 
Features secure user authentication, student CRUD operations, PDF report generation, and a responsive dashboard.
## Features

- User Authentication: Secure login/registration with session management; prevents dashboard access for invalid credentials.
- Student Management: Add, edit, delete, and search students (by ID, name, or enrollment date).
- PDF Reports: Generate and download student reports using TCPDF.
- Responsive Dashboard: Customizable background (#E5E7EB), with stats and quick actions.
- Navigation: Tabbed interface (Features, About) with smooth scrolling and URL hash support.
- Dynamic Search: Input adapts to search type (e.g., date picker for enrollment date).
- Sidebar: Collapsible, hidden on non-dashboard pages (index.html, login.html).

## Tech Stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL
- Libraries: TCPDF (PDF generation)
- Fonts: Inter (Google Fonts)

## Troubleshooting

- Login Fails: Verify manage.php credentials and Users table. Check login.html alerts.
- Sidebar Visible: Ensure index.html/login.html lack body.dashboard.
- PDF Errors: Confirm vendor/autoload.php exists. Check pdf_error.log.
- Tabs Broken: Open index.html console (F12) for showTab() errors.

## Security Note
Passwords are stored as plain text. For production, hash passwords using PHP’s password_hash() and password_verify().


