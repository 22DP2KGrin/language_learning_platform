// Admin Configuration
const ADMIN_CONFIG = {
    // Admin credentials (in a real application, these should be stored on the server)
    credentials: {
        email: 'admin@languageplatform.com',
        // In a real application, password should be hashed and stored on the server
        password: 'Admin@2024Secure',
        username: 'PlatformAdmin'
    },
    
    // Roles and permissions
    permissions: {
        canApproveUsers: true,
        canManageCourses: true,
        canManageContent: true,
        canViewAnalytics: true,
        canManageAdmins: false, // Only super-admin can manage other admins
        canModerateContent: true,
        canAccessReports: true
    },

    // Admin functions
    functions: {
        // User Management
        userManagement: {
            approveNewUsers: true,
            suspendUsers: true,
            deleteUsers: true,
            viewUserDetails: true,
            editUserRoles: true
        },
        
        // Content Management
        contentManagement: {
            createCourses: true,
            editCourses: true,
            deleteCourses: true,
            moderateComments: true,
            manageCategories: true
        },
        
        // Analytics and Reports
        analytics: {
            viewUserStats: true,
            viewCourseStats: true,
            viewRevenueStats: true,
            generateReports: true,
            exportData: true
        },
        
        // System Settings
        systemSettings: {
            managePlatformSettings: true,
            manageEmailTemplates: true,
            manageNotifications: true,
            viewSystemLogs: true
        }
    }
};

// Function to validate admin credentials
function validateAdminCredentials(email, password) {
    if (!email || !password) {
        return {
            success: false,
            message: 'Email and password are required'
        };
    }

    // Check email
    if (email !== ADMIN_CONFIG.credentials.email) {
        return {
            success: false,
            message: 'Invalid admin email'
        };
    }

    // Check password
    if (password !== ADMIN_CONFIG.credentials.password) {
        return {
            success: false,
            message: 'Invalid password'
        };
    }

    // If all checks pass
    return {
        success: true,
        message: 'Login successful',
        admin: {
            email: ADMIN_CONFIG.credentials.email,
            username: ADMIN_CONFIG.credentials.username,
            permissions: ADMIN_CONFIG.permissions,
            functions: ADMIN_CONFIG.functions
        }
    };
}

// Function to check admin session
function checkAdminSession() {
    const adminSession = localStorage.getItem('adminSession');
    if (!adminSession) {
        return false;
    }

    try {
        const session = JSON.parse(adminSession);
        // Check if session has expired (24 hours)
        if (session.timestamp && (Date.now() - session.timestamp) < 24 * 60 * 60 * 1000) {
            return true;
        }
        // If session expired, remove it
        localStorage.removeItem('adminSession');
        return false;
    } catch (error) {
        console.error('Error checking admin session:', error);
        return false;
    }
}

// Function to create admin session
function createAdminSession(adminData) {
    const session = {
        admin: adminData,
        timestamp: Date.now()
    };
    localStorage.setItem('adminSession', JSON.stringify(session));
}

// Function to logout admin
function logoutAdmin() {
    localStorage.removeItem('adminSession');
    window.location.href = 'admin_login.html';
}

// Export functions and configuration
window.ADMIN_CONFIG = ADMIN_CONFIG;
window.validateAdminCredentials = validateAdminCredentials;
window.checkAdminSession = checkAdminSession;
window.createAdminSession = createAdminSession;
window.logoutAdmin = logoutAdmin; 