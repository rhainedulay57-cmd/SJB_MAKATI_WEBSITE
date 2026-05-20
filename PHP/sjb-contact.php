<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/sjb-global.css">
    <link rel="stylesheet" href="../CSS/sjb-contact.css">
    <link rel="stylesheet" href="../CSS/sjb-services.css">
    <title>Contact Us | St. John Bosco Parish, Makati City, Philippines</title>
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
                            <a href="../HTML/sjb-services.html#baptism">Baptism Info</a>
                            <a href="../HTML/sjb-services.html#weddings">Weddings Info</a>
                            <a href="../HTML/sjb-services.html#funerals">Funerals Info</a>
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
                    <a href="../PHP/sjb-contact.php#contact-form" class="active">CONTACT US</a>
                        <div class="dropdown-menu">
                            <a href="../PHP/sjb-contact.php#sacrament">Sacrament Booking</a>
                            <a href="../PHP/sjb-contact.php#mass-blessing-request">Mass & Blessing Request Form</a>
                            <a href="../PHP/sjb-contact.php#donation">Donation / Love Offering</a>
                        </div>
                </li>

                <li class="sign-in-btn">
                    <a href="../PHP/sjb-register.php" title="Sign In / Register">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </a>
                </li>
            </ul>
        </nav>

    </header>

<!-- SACRAMENT BOOKING -->

<section class="sacrament-booking-container" id="sacrament">

        <div class="booking-header">
            <h1>Sacrament Booking System</h1>
            <p>Conveniently book your wedding, baptism, or funeral mass online</p>
        </div>

        <!-- TABS SECTION -->
        <div class="booking-tabs">
            <button class="tab-btn active" onclick="switchTab('wedding', this)">💍 Weddings</button>
            <button class="tab-btn" onclick="switchTab('baptism', this)">👶 Baptisms</button>
            <button class="tab-btn" onclick="switchTab('funeral', this)">🙏 Funerals</button>
            <button class="tab-btn" onclick="switchTab('status', this)">📋 Check Status</button>
        </div>

        <!-- WEDDING TAB -->
        <div id="wedding-tab" class="tab-content active">
            <h2>💍 Wedding Booking</h2>
            <form id="weddingForm" class="booking-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="groom_name">Groom's Full Name *</label>
                        <input type="text" id="groom_name" name="groom_name" required>
                    </div>
                    <div class="form-group">
                        <label for="bride_name">Bride's Full Name *</label>
                        <input type="text" id="bride_name" name="bride_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="groom_email">Groom's Email *</label>
                        <input type="email" id="groom_email" name="groom_email" required>
                    </div>
                    <div class="form-group">
                        <label for="groom_phone">Groom's Phone *</label>
                        <input type="tel" id="groom_phone" name="groom_phone" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="wedding_date">Desired Wedding Date *</label>
                        <input type="date" id="wedding_date" name="wedding_date" required>
                    </div>
                    <div class="form-group">
                        <label for="wedding_time">Preferred Time *</label>
                        <input type="time" id="wedding_time" name="wedding_time" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="guest_count">Estimated Number of Guests *</label>
                        <input type="number" id="guest_count" name="guest_count" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="special_requests">Special Requests or Notes</label>
                    <textarea id="special_requests" name="special_requests" rows="4"></textarea>
                </div>

                <button type="submit" class="submit-btn">Submit Wedding Booking</button>
            </form>
        </div>

        <!-- BAPTISM TAB -->
        <div id="baptism-tab" class="tab-content">
            <h2>👶 Baptism Booking</h2>
            <form id="baptismForm" class="booking-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="baptism_type">Type of Baptism *</label>
                        <select id="baptism_type" name="baptism_type" required>
                            <option value="">-- Select --</option>
                            <option value="Infant Baptism">Infant Baptism</option>
                            <option value="Adult Baptism">Adult Baptism</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="candidate_name">Child/Candidate's Full Name *</label>
                        <input type="text" id="candidate_name" name="candidate_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="parent1_name">Parent/Guardian 1 Name *</label>
                        <input type="text" id="parent1_name" name="parent1_name" required>
                    </div>
                    <div class="form-group">
                        <label for="parent1_email">Parent/Guardian 1 Email *</label>
                        <input type="email" id="parent1_email" name="parent1_email" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="parent1_phone">Parent/Guardian 1 Phone *</label>
                        <input type="tel" id="parent1_phone" name="parent1_phone" required>
                    </div>
                    <div class="form-group">
                        <label for="godparent_name">Godparent/Sponsor Name *</label>
                        <input type="text" id="godparent_name" name="godparent_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="baptism_date">Desired Baptism Date *</label>
                        <input type="date" id="baptism_date" name="baptism_date" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="special_requests_bap">Special Requests or Notes</label>
                    <textarea id="special_requests_bap" name="special_requests_bap" rows="4"></textarea>
                </div>

                <button type="submit" class="submit-btn">Submit Baptism Booking</button>
            </form>
        </div>

        <!-- FUNERAL TAB -->
        <div id="funeral-tab" class="tab-content">
            <h2 style="color: #8B0000;">🙏 Funeral Mass Booking</h2>
            <p style="color: #d9534f; font-weight: bold;">⏰ URGENT - We respond to funeral requests same day</p>
            
            <form id="funeralForm" class="booking-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="deceased_name">Deceased's Full Name *</label>
                        <input type="text" id="deceased_name" name="deceased_name" required>
                    </div>
                    <div class="form-group">
                        <label for="death_date">Date of Death *</label>
                        <input type="date" id="death_date" name="death_date" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="requestor_name">Family Contact Name *</label>
                        <input type="text" id="requestor_name" name="requestor_name" required>
                    </div>
                    <div class="form-group">
                        <label for="relationship">Relationship to Deceased *</label>
                        <input type="text" id="relationship" name="relationship" placeholder="e.g., Son, Daughter, Spouse" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="requestor_email">Contact Email *</label>
                        <input type="email" id="requestor_email" name="requestor_email" required>
                    </div>
                    <div class="form-group">
                        <label for="requestor_phone">Contact Phone *</label>
                        <input type="tel" id="requestor_phone" name="requestor_phone" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="funeral_date">Desired Funeral Mass Date *</label>
                        <input type="date" id="funeral_date" name="funeral_date" required>
                    </div>
                    <div class="form-group">
                        <label for="funeral_time">Preferred Time *</label>
                        <input type="time" id="funeral_time" name="funeral_time" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="funeral_attendees">Expected Number of Attendees *</label>
                        <input type="number" id="funeral_attendees" name="funeral_attendees" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="special_requests_fun">Special Requests or Notes</label>
                    <textarea id="special_requests_fun" name="special_requests_fun" rows="4"></textarea>
                </div>

                <button type="submit" class="submit-btn urgent-btn">Submit Funeral Booking (URGENT)</button>
            </form>
        </div>

        <!-- STATUS TAB -->
        <div id="status-tab" class="tab-content">
            <h2>📋 Check Booking Status</h2>
            <p style="max-width: 760px; margin: 0 auto 1.5rem; color: #555; line-height: 1.6;">
                Enter the <strong>Booking ID</strong> you received after submitting your sacrament booking, and the same email address used for your booking.
                Then click <strong>Check Status</strong> to view the current status and admin notes.
            </p>
            <div class="status-check">
                <div class="form-row">
                    <div class="form-group">
                        <label for="booking_id">Booking ID *</label>
                        <input type="text" id="booking_id" name="booking_id" placeholder="e.g., WED-5432-2026">
                    </div>
                    <div class="form-group">
                        <label for="check_email">Email Address *</label>
                        <input type="email" id="check_email" name="check_email" placeholder="your@email.com">
                    </div>
                </div>
                <button type="button" class="submit-btn" onclick="checkBookingStatus()">Check Status</button>
            </div>

            <div id="status-result" class="status-result" style="display: none;">
                <div class="status-card">
                    <h3>Your Booking Status</h3>
                    <div id="status-details"></div>
                </div>
            </div>
        </div>

</section>

<!-- SUCCESS MODAL -->
<div id="successModal" class="modal">
    <div class="modal-content success-modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>✓ Booking Submitted Successfully!</h2>
        <p>Your booking has been submitted to our admin team for approval.</p>
        <div id="bookingDetails" class="booking-confirmation"></div>
        <p style="margin-top: 20px; font-weight: bold;">Keep your <strong>Booking ID</strong> for reference. You will receive an email confirmation shortly.</p>
        <button class="submit-btn" onclick="closeModal()">Close</button>
    </div>
</div>


<!-- MASS BLESSING REQUEST -->
    <section id="mass-blessing-request">

        <div class="mass-blessing-request-container">

            <div class="mass-blessing-request-form">

                <div class="mass-request-title">
                    <h1>Mass & Blessing<br><b>Request Form</b></h1>
                </div>

                <!-- TABS FOR MASS REQUEST -->
                <div class="booking-tabs">
                    <button class="tab-btn active" onclick="switchTab('mass-form', this)">📋 Submit Request</button>
                    <button class="tab-btn" onclick="switchTab('mass-status', this)">📊 Check Status</button>
                </div>

                <!-- SUBMIT REQUEST TAB -->
                <div id="mass-form-tab" class="tab-content active">
                    <form class="mass-request-form" id="mass_req_form" onsubmit="submitMassRequest(event)">

                    <div class="mass-req-row">
                        <div class="mass-req-group">
                            <label>What service do you need? *<br><small>Check both if you need both.</small></label>

                            <div class="checkbox-group">
                                <p><input type="checkbox" name="service[]" value="Mass" required>Mass</p>
                                <p><input type="checkbox" name="service[]" value="Blessing">Blessing</p>
                            </div>
                        </div>

                        <div class="mass-req-group">
                            <label>Date Filled *</label>
                            <p><input type="date" class="short" name="date_filled" required></p>
                        </div>
                    </div>
                    <!-- ROW -->
                    <div class="mass-req-row">
                        <div class="mass-req-group">
                            <label>Type of Mass *<br><small>Check both if you need both.</small></label>

                            <div class="checkbox-group">
                                <p><input type="checkbox" name="mass_type[]" value="Outside" required>Outside</p>
                                <p><input type="checkbox" name="mass_type[]" value="Onsite">Onsite</p>
                            </div>
                        </div>

                        <div class="mass-req-group">
                            <label>No. of attendees *</label>

                            <input type="number" name="attendees" class="short" required>
                        </div>
                    </div>
                    <!-- ROW -->
                     <div class="mass-req-row">
                        <div class="mass-req-group">
                            <label>Mass Intention *<br><small>Key in N/A if no mass request.</small></label>
                            <textarea name="intention" id="" rows="3" required></textarea>
                        </div>
                     </div>
                    <!-- ROW -->
                     <div class="mass-req-row">
                        <div class="mass-req-group">
                            <label>Preffered Schedule *</label>
                            <input type="date" name="pref_sched" class="short" required>
                        </div>
                        <div class="mass-req-group">
                            <label>Time *</label>
                            <input type="time" name="pref_time" class="short" required>
                        </div>
                     </div>
                    <!-- ROW -->
                     <div class="mass-req-row">
                        <div class="mass-req-group">
                            <label>Alternative Schedule</label>
                            <input type="date" name="alter_sched" class="short">
                        </div>
                        <div class="mass-req-group">
                            <label>Time</label>
                            <input type="time" name="alter_time" class="short">
                        </div>
                     </div>
                     <!-- ROW -->
                    <div class="mass-req-row">
                        <div class="mass-req-group">
                            <input type="text" name="company_name" class="short" placeholder="Name of Company, if business">
                        </div>
                        <div class="mass-req-group">
                            <input type="text" name="company_owner" class="short" placeholder="Name of Owner">
                        </div>
                    </div>
                    <!-- ROW -->
                     <div class="mass-req-row">
                        <div class="mass-req-group">
                            <label>Complete Address of Company/Blessing/Mass Venue *</label>
                            <textarea name="address" id="" rows="3" required></textarea>
                        </div>
                     </div>
                     <!-- ROW -->
                      <div class="mass-req-row">
                        <div class="mass-req-group">
                            <label>Name of Contact Person *</label>
                            <input type="text" name="contact_person" class="short" required>
                        </div>
                        <div class="mass-req-group">
                            <label>Position/Department</label>
                            <input type="text" name="department" class="short">
                        </div>
                      </div>
                    <!-- ROW -->
                    <div class="mass-req-row">
                        <div class="mass-req-group">
                            <label>Mobile No. *</label>
                            <input type="text" name="mobile_no" class="short" required>
                        </div>
                        <div class="mass-req-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" class="short" required>
                        </div>
                    </div>

                    <input type="submit" class="submit-btn">

                    </form>
                </div>

                <!-- CHECK STATUS TAB -->
                <div id="mass-status-tab" class="tab-content">
                    <h2>Check Your Mass & Blessing Request Status</h2>
                    <p style="font-size: 0.95em; color: #666; margin-bottom: 20px;">
                        Enter the Request ID you received after submitting your mass or blessing request, and the same email address used for your submission.
                    </p>
                    
                    <div class="form-row" style="margin-bottom: 20px;">
                        <div class="form-group">
                            <label for="mass_request_id">Request ID *</label>
                            <input type="text" id="mass_request_id" placeholder="e.g., 12345" required>
                        </div>
                        <div class="form-group">
                            <label for="mass_check_email">Email Address *</label>
                            <input type="email" id="mass_check_email" placeholder="your@email.com" required>
                        </div>
                    </div>

                    <button class="submit-btn" onclick="checkMassRequestStatus()">Check Status</button>

                    <div id="mass-status-result" style="display: none; margin-top: 20px;">
                        <div id="mass-status-details"></div>
                    </div>
                </div>

            </div>

        </div>

    </section>

<!-- DONATION -->
    <section id="donation">

        <div class="donation-container">

            <div class="donation-text-container">
                <h1>Make a Donation</h1>

                <img src="../IMAGES/parish-bank-account.jpg" alt="">

                <p>For proper acknowledgement, kindly send us a photo or screenshot of your<br>transaction details to <a href="mailto:donations@sjbmakati.com">donations@sjbmakati.com</a> .</p>
            </div>

        </div>

    </section>

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
             
<script src="../JS/sjb-booking-system.js"></script>

<script>
document.getElementById("contactForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const form = this; // store form reference
    const formData = new FormData(form);

    fetch("sjb-contact-result.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        alert("Form submitted successfully!"); // ✅ alert message

        form.reset(); // ✅ clear form fields

        document.getElementById("response").innerHTML = data;
    })
});

document.getElementById("mass_req_form").addEventListener("submit", function(e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);

    fetch("mass-request-result.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        alert("Mass & Blessing request submitted successfully!");

        form.reset();
    })
    .catch(error => {
        console.error(error);
        alert("Something went wrong!");
    });
});
</script>

</body>
</html>