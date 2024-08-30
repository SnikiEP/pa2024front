<header>
    <script src="/assets/js/translation.js"></script>

    <nav class="navbar" role="navigation" aria-label="main navigation">
        <div class="navbar-brand">
            <a class="navbar-item" href="/index.php">
                <img src="/assets/img/atd_logo.png" alt="ATD logo">
            </a>
            <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navbar">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </a>
        </div>

        <div id="navbar" class="navbar-menu">
            <div class="navbar-start">
                <a class="navbar-item" data-translate="home" href="../index.php">Home</a>
                <a class="navbar-item" data-translate="about_us" href="/public/about.php">About us</a>
                <a class="navbar-item" data-translate="contact_us" href="/public/contact.php">Contact us</a>
            </div>

            <div class="navbar-end">
                <div class="navbar-item">
                    <div class="select is-rounded">
                        <select id="lang_switch">
                        </select>
                    </div>
                    <?php if (isset($_SESSION['username'])): ?>
                        <p class="navbar-item welcome-message" data-translate="welcome"><?= 'Welcome, ' . htmlspecialchars($_SESSION['username']); ?></p>
                        <a class="navbar-item" data-translate="my_profile" href="/public/myprofil.php">My profile</a>
                        <a class="navbar-item" data-translate="calendar" href="../public/calendar.php">Calendar</a>
                        <a class="navbar-item" data-translate="tickets" href="/public/tickets.php">Tickets</a>
                        <a class="navbar-item" href="/public/collecte.php">Collecte</a>
                        <a class="navbar-item" href="/public/stock.php">Stock</a>
                        <a class="navbar-item" data-translate="events" href="/public/events.php">Events</a>
                        <a class="navbar-item" data-translate="vehicles" href="/public/vehicles.php">Vehicles</a>

                        <?php if (in_array('ROLE_ADMIN', $_SESSION['role'])): ?>
                            <a class="navbar-item" data-translate="admin_panel" href="/admin/index.php">Admin panel</a>
                        <?php endif; ?>
                        <a class="button is-info" data-translate="logout" href="/public/logout.php">Logout</a>
                    <?php else: ?>
                        <div class="buttons">
                            <a class="button is-info" data-translate="join_us" href="/public/register.php"><strong>Join us</strong></a>
                            <a class="button is-light" data-translate="log_in" href="/public/login.php">Log in</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>
