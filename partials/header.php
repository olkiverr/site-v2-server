<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="site-header">
    <div class="logo-container" style="width: 150px;">
        <img src="/4TTJ/Zielinski%20Olivier/Site/site-v2/img/MangaMuse_White.png" alt="Mangamuse Logo" class="logo-image" style="width: 100%;">
    </div>
    <div id="search-bar" class="search-bar normal-search-bar">
        <input type="text" placeholder="Search..." class="search-input">
    </div>
    <nav id="main-nav" class="main-nav" style="background-color: #333; padding: 10px;">
        <button id="menu-toggle" class="menu-toggle" aria-label="Toggle navigation menu" style="color: #fff;">
            &#9776;
        </button>
        <div class="sidebar-container" style="display: flex; align-items: center;">
            <div class="search-bar responsive-search-bar">
                <input type="text" placeholder="Search..." class="search-input">
                <div id="responsive-user-icon" class="responsive-user-icon" style="margin-left: 15px; position: relative;">
                    <img src="/4TTJ/Zielinski%20Olivier/Site/site-v2/img/icon_user.png" alt="User Icon" class="user-icon" style="width: 30px; height: 30px;" onclick="toggleUserMenu('responsive-user-menu')">
                    <div id="responsive-user-menu" class="user-menu" style="display: none; position: absolute; top: 40px; right: 0; background-color: #333; color: #fff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);">
                        <?php if (isset($_SESSION['user'])): ?>
                            <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/account.php" style="display: block; padding: 10px; text-decoration: none; color: #fff;">Account</a>
                            <?php if ($_SESSION['is_admin'] == 1): ?>
                                <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/admin_panel.php" style="display: block; padding: 10px; text-decoration: none; color: #fff;">Admin Panel</a>
                            <?php endif; ?>
                            <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/php/logout.php" style="display: block; padding: 10px; text-decoration: none; color: #fff;">Logout</a>
                        <?php else: ?>
                            <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/login.php" style="display: block; padding: 10px; text-decoration: none; color: #fff;">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <ul id="nav-menu" class="nav-menu" style="list-style: none; gap: 15px; padding: 0; margin: 0; display: flex;">
                <li><a class="home-nav" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/index.php" style="color: #fff; text-decoration: none;">Home</a></li>
                <li><a class="about-nav" href="#" style="color: #fff; text-decoration: none;">About</a></li>
                <li><a class="services-nav" href="#" style="color: #fff; text-decoration: none;">Services</a></li>
                <li><a class="contact-nav" href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/contact.php" style="color: #fff; text-decoration: none;">Contact</a></li>
            </ul>
        </div>
    </nav>
    <div class="normal-user-icon" style="margin-left: 15px; position: relative;">
        <img src="/4TTJ/Zielinski%20Olivier/Site/site-v2/img/icon_user.png" alt="User Icon" class="user-icon" style="width: 30px; height: 30px;" onclick="toggleUserMenu('normal-user-menu')">
        <div id="normal-user-menu" class="user-menu" style="display: none; position: absolute; top: 40px; right: 0; background-color: #333; color: #fff; border-radius: 4px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);">
            <?php if (isset($_SESSION['user'])): ?>
                <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/account.php" style="display: block; padding: 10px; text-decoration: none; color: #fff;">Account</a>
                <?php if ($_SESSION['is_admin'] == 1): ?>
                    <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/admin_panel.php" style="display: block; padding: 10px; text-decoration: none; color: #fff;">Admin Panel</a>
                <?php endif; ?>
                <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/php/logout.php" style="display: block; padding: 10px; text-decoration: none; color: #fff;">Logout</a>
            <?php else: ?>
                <a href="/4TTJ/Zielinski%20Olivier/Site/site-v2/pages/login.php" style="display: block; padding: 10px; text-decoration: none; color: #fff;">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<script src="/4TTJ/Zielinski%20Olivier/Site/site-v2/js/header.js"></script>
