# Student Management System
A web-based application for managing student records, developed as a class project to demonstrate proficiency in web development. Built with PHP, PostgreSQL, HTML, CSS, and JavaScript, it features secure user authentication, student CRUD operations, PDF report generation, and a responsive dashboard. Hosted on Render (https://edumanage-o4qb.onrender.com) with source code on GitHub 
## Features

- User Authentication: Secure login and registration with session management, preventing unauthorized dashboard access.
- Student Management: Add, edit, delete, and search students by ID, name, or enrollment date.
- PDF Reports: Generate downloadable student reports using TCPDF.
- Responsive Dashboard: Clean interface with customizable background (#E5E7EB), displaying stats (total students, new enrollments) and quick actions.
- Navigation: Tabbed interface (Features, About) with smooth scrolling and URL hash support.
- Dynamic Search: Search input adapts to type (e.g., date picker for enrollment date).
## Tech Stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP (with PDO for database interactions)
- Database: PostgreSQL (Render-hosted, migrated from local MySQL)
- Libraries: TCPDF (PDF generation)
- Fonts: Inter (Google Fonts)
- Deployment: Render (Docker-based, using Apache and pdo_pgsql)
- Version Control: Git (GitHub)

## Future Improvements
Given more time, the following enhancements would elevate the project:

- Framework Integration: Migrate to Laravel for improved MVC structure, routing, and middleware.
- Role-Based Access: Implement user roles (e.g., admin, teacher) for granular permissions.
- Advanced Search: Add filters for multiple criteria and pagination.
- API Support: Develop RESTful APIs for integration with mobile apps or external systems.

## Troubleshooting

- Login Fails: Verify DATABASE_URL in Render’s Environment tab and check users table in edumanage_db. Inspect login.html alerts in browser console (F12).
- Sidebar Visible: Ensure index.html and login.html exclude body.dashboard class.
- PDF Errors: Confirm vendor/autoload.php exists. Check pdf_error.log for TCPDF issues.
- Tabs Broken: Check index.html console for showTab() errors in script.js.
- Database Errors: Review /var/www/html/debug.log via Render’s Shell for connection issues (e.g., DATABASE_URL not set).
