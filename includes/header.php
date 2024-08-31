<header>
    <script src="/assets/js/translation.js"></script>

    <nav class="navbar" role="navigation" aria-label="main navigation">
        <div class="navbar-brand">
            <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navbar">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </a>
        </div>

        <div id="navbar" class="navbar-menu">
            <div class="navbar-start">
                <a class="navbar-item" href="/index.php">
                    <img src="/assets/img/atd_logo.png" alt="ATD logo">
                </a>                
            </div>

            <div class="navbar-end">
                <div class="navbar-item">
                    <div class="select is-rounded">
                        <select id="lang_switch"></select>
                    </div>
                    <?php if (isset($_SESSION['username'])): ?>
                        <a class="navbar-item" data-translate="my_profile" href="/public/myprofil.php">My profile</a>
                        <a class="navbar-item" data-translate="calendar" href="../public/calendar.php">Calendar</a>
                        <a class="navbar-item" href="/public/collecte.php">Collecte</a>
                        <a class="navbar-item" data-translate="donnation" href="/public/donnationdelivery.php">Donnation</a>
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

    <style>
        body, html {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        header {
            background-color: #fff; 
        }

        .navbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            padding: 0 1rem;
            font-size: 0.875rem;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .navbar-item img {
            max-height: 40px;
        }

        .navbar-burger {
            display: none;
        }

        .navbar-menu {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        .navbar-start, .navbar-end {
            display: flex;
        }

        .navbar-item {
            margin: 0 0.25rem;
            font-size: 0.875rem;
        }

        .buttons {
            display: flex;
            gap: 0.25rem;
        }

        .button {
            font-size: 0.75rem;
        }

        @media screen and (max-width: 768px) {
            .navbar-burger {
                display: block;
                cursor: pointer;
            }

            .navbar-menu {
                display: none;
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
            }

            .navbar-menu.is-active {
                display: flex;
            }

            .navbar-start, .navbar-end {
                width: 100%;
                justify-content: center;
                margin-top: 0.5rem;
            }

            .navbar-end {
                flex-direction: column;
                align-items: flex-start;
            }

            .buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</header>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const burgerIcon = document.querySelector('.navbar-burger');
        const navbarMenu = document.getElementById(burgerIcon.dataset.target);

        burgerIcon.addEventListener('click', () => {
            burgerIcon.classList.toggle('is-active');
            navbarMenu.classList.toggle('is-active');
        });
    });
</script>
