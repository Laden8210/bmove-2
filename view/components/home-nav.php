
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">BMoveXpress: Smart Movers</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNavDropdown">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="create-booking">HOME</a></li>
                <li class="nav-item"><a class="nav-link" href="about">ABOUT US</a></li>
                <li class="nav-item"><a class="nav-link" href="storage-size">STORAGE SIZES</a></li>
                <li class="nav-item"><a class="nav-link" href="service">SERVICES</a></li>
                <li class="nav-item"><a class="nav-link" href="online-payment">ONLINE PAYMENT</a></li>
                <li class="nav-item"><a class="nav-link" href="locations">LOCATIONS</a></li>
                <li class="nav-item"><a class="nav-link" href="inquire">INQUIRE NOW</a></li>

                <?php  if (isset($_SESSION['auth'])) { ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard">DASHBOARD</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo htmlspecialchars($username); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#">Settings</a></li>
                        <li><a class="dropdown-item" href="logout">Logout</a></li>
                    </ul>
                </li>

                <?php } else { ?>
                    <li class="nav-item"><a class="nav-link" href="login">LOGIN</a></li>
                    <li class="nav-item"><a class="nav-link" href="register">REGISTER</a></li>
                <?php } ?>
            
            </ul>
        </div>
    </div>
</nav>