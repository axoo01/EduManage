<?php
// Start session at the top
session_start();

// Debug: Log session status
file_put_contents('debug.log', date('Y-m-d H:i:s') . ": Session ID: " . session_id() . ", Username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'none') . "\n", FILE_APPEND);

// Only check session for non-login/register actions
if (!isset($_POST['action']) || ($_POST['action'] !== 'login' && $_POST['action'] !== 'register')) {
    if (!isset($_SESSION['username'])) {
        header("Location: login.html?error=unauthenticated");
        exit;
    }
}

// Database connection using Render's PostgreSQL DATABASE_URL
try {
    $database_url = getenv("DATABASE_URL");
    if (!$database_url) {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ": DATABASE_URL not set\n", FILE_APPEND);
        header("Location: login.html?error=database");
        exit();
    }
    $url = parse_url($database_url);
    if (!$url || !isset($url['host']) || !isset($url['user']) || !isset($url['pass']) || !isset($url['path'])) {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ": Invalid DATABASE_URL format: " . $database_url . "\n", FILE_APPEND);
        header("Location: login.html?error=database");
        exit();
    }
    $host = $url["host"];
    $username = $url["user"];
    $password = $url["pass"];
    $dbname = substr($url["path"], 1);
    $port = isset($url["port"]) ? $url["port"] : 5432;
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ": DB Connection failed: " . $e->getMessage() . "\n", FILE_APPEND);
    echo "DB Connection Error: " . $e->getMessage(); // Temporary debug
    header("Location: login.html?error=database");
    exit();
}

if (!file_exists('vendor/autoload.php')) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ": vendor/autoload.php not found\n", FILE_APPEND);
    header("Location: login.html?error=dependency");
    exit();
}
require_once 'vendor/autoload.php';

$action = isset($_POST['action']) ? $_POST['action'] : '';
$success_message = '';
$error_message = '';
$students = [];
$total_students = 0;
$search_performed = false;
$logged_in_user = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';

// Calculate stats for dashboard
$new_enrollments = 0;
$reports_generated = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE EXTRACT(MONTH FROM enrollment_date) = EXTRACT(MONTH FROM CURRENT_DATE) AND EXTRACT(YEAR FROM enrollment_date) = EXTRACT(YEAR FROM CURRENT_DATE)");
    $new_enrollments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $reports_generated = 5; // Placeholder
} catch (PDOException $e) {
    $error_message = "Failed to fetch stats: " . $e->getMessage();
    echo "Stats Error: " . $e->getMessage(); // Temporary debug
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'login') {
        $user = $_POST['username'];
        $pass = $_POST['password'];

        try {
            $stmt = $pdo->prepare("SELECT username, password FROM users WHERE username = ?");
            $stmt->execute([$user]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && password_verify($pass, $row['password'])) {
                $_SESSION['username'] = $user;
                file_put_contents('debug.log', date('Y-m-d H:i:s') . ": Login successful for $user, Session set\n", FILE_APPEND);
                header("Location: manage.php?section=dashboard");
                exit;
            } else {
                file_put_contents('debug.log', date('Y-m-d H:i:s') . ": Login failed for $user, invalid credentials\n", FILE_APPEND);
                header("Location: login.html?error=invalid");
                exit;
            }
        } catch (PDOException $e) {
            file_put_contents('debug.log', date('Y-m-d H:i:s') . ": Login DB error: " . $e->getMessage() . "\n", FILE_APPEND);
            echo "Login DB Error: " . $e->getMessage(); // Temporary debug
            header("Location: login.html?error=database");
            exit;
        }
    } elseif ($action === 'register') {
        $user = $_POST['username'];
        $pass = $_POST['password'];
        $confirm_pass = $_POST['confirm_password'];

        if ($pass !== $confirm_pass) {
            file_put_contents('debug.log', date('Y-m-d H:i:s') . ": Registration failed for $user: Passwords do not match\n", FILE_APPEND);
            header("Location: login.html?error=password_mismatch");
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT username FROM users WHERE username = ?");
            $stmt->execute([$user]);
            if ($stmt->fetch()) {
                file_put_contents('debug.log', date('Y-m-d H:i:s') . ": Registration failed for $user: Username exists\n", FILE_APPEND);
                header("Location: login.html?error=username_exists");
                exit;
            }

            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$user, $hashed_password]);
            file_put_contents('debug.log', date('Y-m-d H:i:s') . ": Registration successful for $user\n", FILE_APPEND);
            header("Location: login.html?success=registered");
            exit;
        } catch (PDOException $e) {
            file_put_contents('debug.log', date('Y-m-d H:i:s') . ": Registration DB error: " . $e->getMessage() . "\n", FILE_APPEND);
            echo "Registration DB Error: " . $e->getMessage(); // Temporary debug
            header("Location: login.html?error=database");
            exit;
        }
    } elseif ($action === 'add_student' || $action === 'edit_student') {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $dob = $_POST['date_of_birth'];
        $enrollment_date = $_POST['enrollment_date'];

        try {
            if ($action === 'add_student') {
                $stmt = $pdo->prepare("INSERT INTO students (first_name, last_name, email, date_of_birth, enrollment_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$first_name, $last_name, $email, $dob, $enrollment_date]);
                $success_message = "Student added successfully";
            } elseif ($action === 'edit_student') {
                $student_id = $_POST['student_id'];
                $stmt = $pdo->prepare("UPDATE students SET first_name = ?, last_name = ?, email = ?, date_of_birth = ?, enrollment_date = ? WHERE student_id = ?");
                $stmt->execute([$first_name, $last_name, $email, $dob, $enrollment_date, $student_id]);
                $success_message = "Student updated successfully";
            }
        } catch (PDOException $e) {
            $error_message = "Operation failed: " . $e->getMessage();
            echo "Student Operation DB Error: " . $e->getMessage(); // Temporary debug
        }
    } elseif ($action === 'delete_student') {
        $student_id = $_POST['student_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $success_message = "Student deleted successfully";
        } catch (PDOException $e) {
            $error_message = "Deletion failed: " . $e->getMessage();
            echo "Delete Student DB Error: " . $e->getMessage(); // Temporary debug
        }
    } elseif ($action === 'search_students') {
        $search_by = $_POST['search_by'];
        $search_value = filter_input(INPUT_POST, 'search_value', FILTER_SANITIZE_STRING);

        $query = "SELECT student_id, first_name, last_name, email, date_of_birth, enrollment_date FROM students WHERE 1=1";
        $params = [];

        if (!empty($search_value)) {
            if ($search_by === 'student_id') {
                $query .= " AND student_id = :search_value";
                $params[':search_value'] = $search_value;
            } elseif ($search_by === 'name') {
                $query .= " AND CONCAT(first_name, ' ', last_name) ILIKE :search_value";
                $params[':search_value'] = "%$search_value%";
            } elseif ($search_by === 'enrollment_date') {
                $query .= " AND enrollment_date = :search_value";
                $params[':search_value'] = $search_value;
            }
        }

        $query .= " ORDER BY last_name, first_name";

        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total_students = count($students);
            $search_performed = true;
        } catch (PDOException $e) {
            $error_message = "Search failed: " . $e->getMessage();
            echo "Search DB Error: " . $e->getMessage(); // Temporary debug
            $students = [];
            $total_students = 0;
        }
    }

    if ($action === 'generate_pdf' && !empty($students)) {
        try {
            $pdf = new \TCPDF();
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, 'EduManage Student Report', 0, 1, 'C');
            $pdf->Ln(10);

            $html = '<table border="1"><tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Date of Birth</th><th>Enrollment Date</th></tr>';
            foreach ($students as $student) {
                $html .= "<tr><td>{$student['student_id']}</td><td>{$student['first_name']}</td><td>{$student['last_name']}</td><td>{$student['email']}</td><td>{$student['date_of_birth']}</td><td>{$student['enrollment_date']}</td></tr>";
            }
            $html .= '</table>';
            $pdf->writeHTML($html);
            $pdf->Output('student_report.pdf', 'D');
            exit;
        } catch (Exception $e) {
            $error_message = "PDF generation failed: " . $e->getMessage();
            file_put_contents('pdf_error.log', date('Y-m-d H:i:s') . ': ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            echo "PDF Error: " . $e->getMessage(); // Temporary debug
        }
    }
}

if (!$search_performed) {
    try {
        $stmt = $pdo->query("SELECT student_id, first_name, last_name, email, date_of_birth, enrollment_date FROM students ORDER BY last_name, first_name");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_students = count($students);
    } catch (PDOException $e) {
        $error_message = "Failed to fetch students: " . $e->getMessage();
        echo "Fetch Students DB Error: " . $e->getMessage(); // Temporary debug
    }
}

$edit_student = null;
if (isset($_GET['edit_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $edit_student = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Failed to fetch student: " . $e->getMessage();
        echo "Fetch Student DB Error: " . $e->getMessage(); // Temporary debug
    }
}

$active_section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <h1 class="sidebar-logo">EduManage</h1>
        <ul class="sidebar-menu">
            <li><a href="manage.php?section=dashboard" class="<?php echo $active_section === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="manage.php?section=add_student" class="<?php echo $active_section === 'add_student' ? 'active' : ''; ?>">Add Student</a></li>
            <li><a href="manage.php?section=report" class="<?php echo $active_section === 'report' ? 'active' : ''; ?>">View Report</a></li>
            <li><a href="index.html?tab=features#tab-section">Features</a></li>
            <li><a href="index.html?tab=about#tab-section">About</a></li>
        </ul>
        <ul class="sidebar-menu sidebar-footer">
            <li><a href="logout.php">Log Out</a></li>
        </ul>
        <button id="sidebar-toggle" class="sidebar-toggle">☰</button>
    </aside>

    <!-- Top Navbar -->
    <nav id="navbar" class="navbar">
        <div class="nav-container">
            <span class="welcome-message">Welcome, <?php echo htmlspecialchars($logged_in_user); ?></span>
            <div class="nav-links">
                <a href="index.html#home" class="home-icon" title="Home">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4B5563" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <section class="dashboard-section">
            <div class="dashboard-container">
                <?php if ($success_message): ?>
                    <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>

                <?php if ($active_section === 'dashboard'): ?>
                    <h2>Student Dashboard</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1E6A6E" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <h3>Total Students</h3>
                            <p><?php echo $total_students; ?></p>
                        </div>
                        <div class="stat-card">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1E6A6E" stroke-width="2">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                            </svg>
                            <h3>New Enrollments</h3>
                            <p><?php echo $new_enrollments; ?> this month</p>
                        </div>
                        <div class="stat-card">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1E6A6E" stroke-width="2">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <polyline points="1 20 1 14 7 14"></polyline>
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                            </svg>
                            <h3>Reports Generated</h3>
                            <p><?php echo $reports_generated; ?></p>
                        </div>
                    </div>
                    <div class="quick-actions">
                        <a href="manage.php?section=add_student" class="btn-cta">Add Student</a>
                        <a href="manage.php?section=report" class="btn-cta">View Report</a>
                    </div>
                <?php elseif ($active_section === 'add_student' || $edit_student): ?>
                    <h2><?php echo $edit_student ? 'Edit Student' : 'Add New Student'; ?></h2>
                    <div class="dashboard-card">
                        <form class="dashboard-form" action="manage.php?section=add_student" method="POST">
                            <input type="hidden" name="action" value="<?php echo $edit_student ? 'edit_student' : 'add_student'; ?>">
                            <?php if ($edit_student): ?>
                                <input type="hidden" name="student_id" value="<?php echo $edit_student['student_id']; ?>">
                            <?php endif; ?>
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo $edit_student ? htmlspecialchars($edit_student['first_name']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo $edit_student ? htmlspecialchars($edit_student['last_name']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo $edit_student ? htmlspecialchars($edit_student['email']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $edit_student ? htmlspecialchars($edit_student['date_of_birth']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="enrollment_date">Enrollment Date</label>
                                <input type="date" id="enrollment_date" name="enrollment_date" value="<?php echo $edit_student ? htmlspecialchars($edit_student['enrollment_date']) : ''; ?>" required>
                            </div>
                            <button type="submit" class="btn-cta"><?php echo $edit_student ? 'Update Student' : 'Add Student'; ?></button>
                            <?php if ($edit_student): ?>
                                <a href="manage.php?section=add_student" class="btn-action">Cancel</a>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php elseif ($active_section === 'report'): ?>
                    <h2>Student Report</h2>
                    <div class="dashboard-card">
                        <form class="dashboard-form search-form" action="manage.php?section=report" method="POST" id="search-form">
                            <input type="hidden" name="action" value="search_students">
                            <div class="search-group">
                                <div class="search-inputs">
                                    <div class="form-group">
                                        <label for="search_by">Search By</label>
                                        <select id="search_by" name="search_by" onchange="updateSearchInput()">
                                            <option value="student_id">Student ID</option>
                                            <option value="name">Name</option>
                                            <option value="enrollment_date">Enrollment Date</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="search_value">Search Value</label>
                                        <input type="text" id="search_value" name="search_value" placeholder="Enter search value">
                                    </div>
                                </div>
                                <button type="submit" class="search-btn" title="Search">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                </button>
                            </div>
                        </form>
                        <div class="report-actions">
                            <?php if ($search_performed && !empty($students)): ?>
                                <form action="manage.php?section=report" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="generate_pdf">
                                    <?php foreach ($students as $index => $student): ?>
                                        <?php foreach ($student as $key => $value): ?>
                                            <input type="hidden" name="students[<?php echo $index; ?>][<?php echo $key; ?>]" value="<?php echo htmlspecialchars($value); ?>">
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                    <button type="submit" class="btn-action">Download PDF</button>
                                </form>
                            <?php endif; ?>
                            <button id="print-report-btn" class="btn-action">Print Report</button>
                        </div>
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Email</th>
                                    <th>Date of Birth</th>
                                    <th>Enrollment Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="7">No students found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td><?php echo htmlspecialchars($student['date_of_birth']); ?></td>
                                            <td><?php echo htmlspecialchars($student['enrollment_date']); ?></td>
                                            <td>
                                                <a href="manage.php?section=add_student&edit_id=<?php echo $student['student_id']; ?>" class="btn-action edit">Edit</a>
                                                <form action="manage.php?section=report" method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_student">
                                                    <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                                    <button type="submit" class="btn-action delete" onclick="return confirm('Are you sure you want to delete this student?');">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>© 2025 EduManage. All rights reserved.</p>
        <p>Contact: support@edumanage.com</p>
    </footer>

    <script src="script.js"></script>
</body>
</html>