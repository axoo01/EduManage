document.addEventListener('DOMContentLoaded', () => {
    console.log('script.js loaded');

    // Sidebar Toggle
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const dashboardSection = document.querySelector('.dashboard-section');

    if (sidebar && sidebarToggle && dashboardSection) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            dashboardSection.classList.toggle('full-width');
        });
    }

    // Search Input Update
    window.updateSearchInput = function() {
        const searchBy = document.getElementById('search_by');
        const searchValue = document.getElementById('search_value');
        if (searchBy && searchValue) {
            if (searchBy.value === 'enrollment_date') {
                searchValue.type = 'date';
                searchValue.placeholder = 'Select date';
            } else {
                searchValue.type = 'text';
                searchValue.placeholder = searchBy.value === 'student_id' ? 'Enter Student ID' : 'Enter Full Name';
            }
        }
    };

    // Initialize search input
    if (document.getElementById('search_by')) {
        updateSearchInput();
    }

    // Login/Registration Form Toggle
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const errorMessage = document.getElementById('error-message');
    const regErrorMessage = document.getElementById('reg-error-message');
    const formTitle = document.getElementById('form-title');
    const toggleLink = document.getElementById('toggle-link');
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const success = urlParams.get('success');

    if (loginForm && registerForm && errorMessage && regErrorMessage && formTitle && toggleLink) {
        window.toggleForm = function() {
            if (loginForm.style.display === 'block') {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
                formTitle.textContent = 'Sign Up';
                toggleLink.innerHTML = 'Already have an account? <span class="toggle-action">Log In</span>';
                errorMessage.textContent = '';
                regErrorMessage.textContent = '';
            } else {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                formTitle.textContent = 'Log In';
                toggleLink.innerHTML = 'Don’t have an account? <span class="toggle-action">Sign Up</span>';
                errorMessage.textContent = '';
                regErrorMessage.textContent = '';
            }
        };

        // Handle success/error messages with slight delay to ensure DOM is ready
        setTimeout(() => {
            if (success === 'registered') {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                formTitle.textContent = 'Log In';
                toggleLink.innerHTML = 'Don’t have an account? <span class="toggle-action">Sign Up</span>';
                errorMessage.textContent = 'Registration successful! Please log in.';
                errorMessage.className = 'success-message';
                errorMessage.style.display = 'block';
                errorMessage.style.visibility = 'visible';
                errorMessage.style.opacity = '1';
            } else if (error) {
                registerForm.style.display = 'block';
                loginForm.style.display = 'none';
                formTitle.textContent = 'Sign Up';
                toggleLink.innerHTML = 'Already have an account? <span class="toggle-action">Log In</span>';
                regErrorMessage.className = 'error-message';
                regErrorMessage.style.display = 'block';
                regErrorMessage.style.visibility = 'visible';
                regErrorMessage.style.opacity = '1';
                if (error === 'username_exists') {
                    regErrorMessage.textContent = 'This username is already registered';
                } else if (error === 'password_mismatch') {
                    regErrorMessage.textContent = 'Passwords do not match';
                } else if (error === 'database') {
                    regErrorMessage.textContent = 'Database error, please try again';
                } else if (error === 'invalid') {
                    loginForm.style.display = 'block';
                    registerForm.style.display = 'none';
                    formTitle.textContent = 'Log In';
                    toggleLink.innerHTML = 'Don’t have an account? <span class="toggle-action">Sign Up</span>';
                    errorMessage.textContent = 'Invalid username or password';
                    errorMessage.className = 'error-message';
                    errorMessage.style.display = 'block';
                    errorMessage.style.visibility = 'visible';
                    errorMessage.style.opacity = '1';
                } else if (error === 'unauthenticated') {
                    loginForm.style.display = 'block';
                    registerForm.style.display = 'none';
                    formTitle.textContent = 'Log In';
                    toggleLink.innerHTML = 'Don’t have an account? <span class="toggle-action">Sign Up</span>';
                    errorMessage.textContent = 'Please log in to access the dashboard';
                    errorMessage.className = 'error-message';
                    errorMessage.style.display = 'block';
                    errorMessage.style.visibility = 'visible';
                    errorMessage.style.opacity = '1';
                }
            }
        }, 100); // 100ms delay to ensure DOM is fully loaded
    }

    // Tab Switching for Features and About
    if (document.querySelector('.tab-buttons')) {
        console.log('Tab buttons detected');
        window.showTab = function(tabId) {
            console.log('showTab called with tabId:', tabId);
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });

            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(button => {
                button.classList.remove('active');
            });

            const selectedContent = document.getElementById(tabId);
            if (selectedContent) {
                selectedContent.classList.add('active');
            } else {
                console.warn('Tab content not found for tabId:', tabId);
            }

            const selectedButton = document.querySelector(`.tab-btn[onclick="showTab('${tabId}')"]`);
            if (selectedButton) {
                selectedButton.classList.add('active');
            }

            const tabSection = document.getElementById('tab-section');
            if (tabSection) {
                tabSection.scrollIntoView({ behavior: 'smooth' });
            }
        };

        const initializeTab = () => {
            const hash = window.location.hash;
            console.log('URL hash:', hash);

            if (hash === '#home') {
                console.log('Skipping tab initialization for #home');
                const homeSection = document.getElementById('home');
                if (homeSection) {
                    homeSection.scrollIntoView({ behavior: 'smooth' });
                }
                return;
            }

            const urlParams = new URLSearchParams(window.location.search);
            let tab = urlParams.get('tab');
            console.log('URL tab parameter:', tab);

            if (!tab) {
                if (hash.includes('features')) {
                    tab = 'features';
                } else if (hash.includes('about')) {
                    tab = 'about';
                } else if (hash === '#tab-section') {
                    tab = 'features';
                }
            }

            if (tab && ['features', 'about'].includes(tab)) {
                showTab(tab);
            } else {
                showTab('features');
                console.log('Defaulting to features tab');
            }
        };

        initializeTab();
        window.addEventListener('hashchange', initializeTab);
    }

    // Report Print
    if (document.getElementById('print-report-btn')) {
        document.getElementById('print-report-btn').addEventListener('click', () => {
            console.log('Print Report button clicked');
            window.print();
        });
    }

    // Sticky Navbar
    window.addEventListener('scroll', () => {
        const navbar = document.getElementById('navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
});