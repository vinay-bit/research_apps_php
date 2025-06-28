<?php
$current_user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="/dashboard.php" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="/logo/omotec_logo.webp" alt="OMOTEC" height="30" style="max-width: 100px;">
            </span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <a href="/dashboard.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
            </a>
        </li>

        <!-- User Management -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">User Management</span>
        </li>
        
        <li class="menu-item <?php echo (strpos($current_page, 'user') !== false) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div data-i18n="Users">Users</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'list.php' && strpos($_SERVER['REQUEST_URI'], 'users') !== false) ? 'active' : ''; ?>">
                    <a href="/users/list.php" class="menu-link">
                        <div data-i18n="All Users">All Users</div>
                    </a>
                </li>
                <?php if (hasPermission('admin')): ?>
                <li class="menu-item <?php echo ($current_page == 'create.php' && strpos($_SERVER['REQUEST_URI'], 'users') !== false) ? 'active' : ''; ?>">
                    <a href="/users/create.php" class="menu-link">
                        <div data-i18n="Add User">Add User</div>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </li>

        <!-- Admins -->
        <?php if (hasPermission('admin')): ?>
        <li class="menu-item">
            <a href="/users/list.php?type=admin" class="menu-link">
                <i class="menu-icon tf-icons bx bx-shield"></i>
                <div data-i18n="Admins">Admins</div>
            </a>
        </li>
        <?php endif; ?>

        <!-- Mentors -->
        <li class="menu-item">
            <a href="/users/list.php?type=mentor" class="menu-link">
                <i class="menu-icon tf-icons bx bx-group"></i>
                <div data-i18n="Mentors">Mentors</div>
            </a>
        </li>

        <!-- Councillors -->
        <li class="menu-item">
            <a href="/users/list.php?type=councillor" class="menu-link">
                <i class="menu-icon tf-icons bx bx-support"></i>
                <div data-i18n="Councillors">Councillors</div>
            </a>
        </li>

        <!-- RBMs -->
        <li class="menu-item">
            <a href="/users/list.php?type=rbm" class="menu-link">
                <i class="menu-icon tf-icons bx bx-briefcase"></i>
                <div data-i18n="RBMs">RBMs</div>
            </a>
        </li>

        <!-- Student Management -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Student Management</span>
        </li>
        
        <li class="menu-item <?php echo (strpos($current_page, 'student') !== false) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-user-circle"></i>
                <div data-i18n="Students">Students</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'list.php' && strpos($_SERVER['REQUEST_URI'], 'students') !== false) ? 'active' : ''; ?>">
                    <a href="/students/list.php" class="menu-link">
                        <div data-i18n="All Students">All Students</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'create.php' && strpos($_SERVER['REQUEST_URI'], 'students') !== false) ? 'active' : ''; ?>">
                    <a href="/students/create.php" class="menu-link">
                        <div data-i18n="Add Student">Add Student</div>
                    </a>
                </li>
            </ul>
        </li>
        
        <!-- Project Management -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Project Management</span>
        </li>
        
        <li class="menu-item <?php echo (strpos($current_page, 'project') !== false) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-folder"></i>
                <div data-i18n="Projects">Projects</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'list.php' && strpos($_SERVER['REQUEST_URI'], 'projects') !== false) ? 'active' : ''; ?>">
                    <a href="/projects/list.php" class="menu-link">
                        <div data-i18n="All Projects">All Projects</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'create.php' && strpos($_SERVER['REQUEST_URI'], 'projects') !== false) ? 'active' : ''; ?>">
                    <a href="/projects/create.php" class="menu-link">
                        <div data-i18n="Create Project">Create Project</div>
                    </a>
                </li>
            </ul>
        </li>
        
        <!-- Publications -->
        <li class="menu-item <?php echo (strpos($current_page, 'publication') !== false) ? 'active open' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-book"></i>
                <div data-i18n="Publications">Publications</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'list.php' && strpos($_SERVER['REQUEST_URI'], 'publications') !== false) ? 'active' : ''; ?>">
                    <a href="/publications/list.php" class="menu-link">
                        <div data-i18n="All Publications">All Publications</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'create.php' && strpos($_SERVER['REQUEST_URI'], 'publications') !== false) ? 'active' : ''; ?>">
                    <a href="/publications/create.php" class="menu-link">
                        <div data-i18n="Create Publication">Create Publication</div>
            </a>
                </li>
            </ul>
        </li>
        


        <!-- Reports -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Reports</span>
        </li>
        
        <li class="menu-item">
            <a href="#" class="menu-link">
                <i class="menu-icon tf-icons bx bx-file"></i>
                <div data-i18n="Reports">Reports</div>
                <div class="badge bg-danger rounded-pill ms-auto">Soon</div>
            </a>
        </li>

        <!-- Settings -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Settings</span>
        </li>
        
        <li class="menu-item">
            <a href="/dashboard.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-cog"></i>
                <div data-i18n="Profile">My Profile</div>
            </a>
        </li>
    </ul>
</aside>