<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/sjb-global.css">
    <link rel="stylesheet" href="../CSS/sjb-register.css">
    <title>Register | St. John Bosco Parish, Makati City, Philippines</title>
</head>
<body>
    <header>

        <img src="../IMAGES/sjb-logo.png" alt="logo">
  

        <nav class="navbar">
            <ul>
                <li><a href="../HTML/sjb-home.html">HOME</a></li>

                <li class="dropdown">
                    <a href="../HTML/sjb-about.html">ABOUT</a>
                        <div class="dropdown-menu">
                            <a href="../HTML/sjb-about.html#mission">Mission & Vision</a>
                            <a href="../HTML/sjb-about.html#timeline">Timeline</a>
                            <a href="../HTML/sjb-about.html#patron">Our Patron Saint</a>
                            <a href="../HTML/sjb-about.html#mhoc">Mary Help of Christians</a>
                            <a href="../HTML/sjb-about.html#sal-community">The Salesian Community</a>
                            <a href="../HTML/sjb-about.html#council">Parish Pastoral Council</a>
                        </div>
                </li>

                <li class="dropdown">
                    <a href="../HTML/sjb-services.html">SERVICES</a>
                        <div class="dropdown-menu">
                            <a href="../HTML/sjb-services.html#mass-schedules">Mass Schedules</a>
                            <a href="../HTML/sjb-services.html#store">Parish Office and Store</a>
                            <a href="../HTML/sjb-services.html#baptism">Baptism</a>
                            <a href="../HTML/sjb-services.html#weddings">Weddings</a>
                            <a href="../HTML/sjb-services.html#funerals">Funerals</a>
                        </div>
                </li>

                <li class="dropdown">
                    <a href="../HTML/sjb-articles.html">ARTICLES</a>
                        <div class="dropdown-menu">
                            <a href="../HTML/sjb-articles.html#devotion">Devotion</a>
                            <a href="../HTML/sjb-articles.html#sanctity">Liturgical Instructions</a>
                            <a href="../HTML/sjb-articles.html#lectio-divina">Lectio Divina</a>
                        </div>
                </li>

                <li class="dropdown">
                    <a href="../PHP/sjb-contact.php#contact-form">CONTACT US</a>
                        <div class="dropdown-menu">
                            <a href="../PHP/sjb-contact.php#contact-form">Contact Form</a>
                            <a href="../PHP/sjb-contact.php#mass-blessing-request">Mass & Blessing Request Form</a>
                            <a href="../PHP/sjb-contact.php#donation">Donation / Love Offering</a>
                        </div>
                </li>

                <li class="sign-in-btn">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="../PHP/sjb-logout.php" title="Logout" class="logout-btn">Logout</a>
                    <?php else: ?>
                        <a href="../PHP/sjb-register.php" title="Sign In / Register" class="active">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>

    </header>

    <!-- REGISTER SECTION -->
    <section class="signin-section">
        <div class="signin-container">
            
            <!-- Register Form -->
            <div class="form-wrapper register-form-wrapper">
                <h2>Register</h2>
                <form id="register-form" action="sjb-register-process.php" method="post">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" id="register-firstname" name="firstname" required>
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="lastname" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" required>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>

                    <input type="submit" class="btn-submit" value="Register">
                </form>

                <p class="login-signup-text">
                    Already have an account? <a href="../PHP/sjb-login-form.php" class="signup-link">Log In here</a>
                </p>
            </div>

        </div>
    </section>

    <!-- FOOTER -->

    <footer class="footer">

    <div class="footer-defi">
        <h2>St. John Bosco Parish</h2>
        <p>Antonio Arnaiz Avenue corner Amorsolo Street</p>
        <p>Pio del Pilar, 1230 Makati City, Metro Manila, Philippines</p>
        <p>Contact Us:</p>
        <p><a href="tel:(+632) 8894-5932">(+632) 8894-5932 to 34</a></p>
        <p><a href="tel:(+63) 945-551-0931">(+63) 945-551-0931</a></p>
        <p>(+632) 8815-2844 (Telefax)</p>
        <p><a href="mailto:info@sjbmakati.com">info@sjbmakati.com</a></p>
        <span>Copyright © 2025. All Rights Reserved.<br>
            Managed by Social Communications Ministry</span>
    </div>

    <div class="map-container">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.8523228205436!2d121.01243837627293!3d14.550436278300499!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c913c65ac6b1%3A0x1cb5a45f2e690a11!2sSt.%20John%20Bosco%20Parish%20Church%20-%20Makati%20City%20(Archdiocese%20of%20Manila)!5e0!3m2!1sen!2sph!4v1776674077717!5m2!1sen!2sph" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>

    <div class="footer-links">
        <p><b>OTHER LINKS:</b></p>
            <li><a href="https://www.vatican.va/content/vatican/it.html">THE VATICAN</a></li>
            <li><a href="https://rcam.org/">ARCHDIOCESE of Manila</a></li>
            <li><a href="https://www.infoans.org/en">SALESIAN INT'L NEWS AGENCY (ANS)</a></li>
            <li><a href="https://www.bosco.link/">SALESIAN NEWS HUB (EAO)</a></li>
            <li><a href="https://www.sdb.org/">SALESIANS OF DON BOSCO (main)</a></li>
            <li><a href="https://sdb.org.ph/fin/">SALESIANS OF DON BOSCO (FIN)</a></li>
            <li><a href="http://dbfis.org/">SALESIANS OF DON BOSCO (FIS)</a></li>
            <li><a href="http://www.fmafil.org/">SALESIAN SISTERS OF DON BOSCO</a></li>
            <li><a href="https://salesianmissions.org/">SALESIAN MISSIONS PHILIPPINES</a></li>
            <li><a href="https://cbcponline.net/">CBCP</a></li>
            <li><a href="https://www.wordandlife.org/">WORD & LIFE</a></li>
    </div>

    </footer>

    <script>
    document.getElementById('register-form').addEventListener('submit', function(e) {

    e.preventDefault();

    const form = this;
    const formData = new FormData(form);

    fetch("sjb-register-process.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        alert("Registration Successful!");
        form.reset();

        window.location.href = "sjb-login-form.php";
    })
    .catch(error => {
        console.error(error);
        alert("Something went wrong!");
    });

    });
</script>
</body>
</html>
