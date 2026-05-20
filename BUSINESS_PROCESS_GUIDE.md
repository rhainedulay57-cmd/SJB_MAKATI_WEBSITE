# Sacrament Booking System - Business Process Guide

## System Overview

This is a complete booking management system for weddings, baptisms, and funerals at St. John Bosco Parish. The system allows parishioners to book sacraments online and provides administrators with a dashboard to review, approve, or decline bookings.

---

## 1. USER FLOW (Parishioner)

### Step 1: Visit Booking Page
- Users navigate to `sjb-sacrament-booking.html`
- They see three tabs: 💍 Weddings, 👶 Baptisms, 🙏 Funerals
- A fourth tab allows them to check booking status

### Step 2: Select Sacrament Type & Fill Form
Users fill out a form specific to their needs:

#### **WEDDING BOOKING**
- Groom's and Bride's names
- Groom's email and phone
- Desired wedding date and time
- Expected guest count
- Special requests/notes

#### **BAPTISM BOOKING**
- Type of baptism (Infant or Adult)
- Candidate's name
- Parent/Guardian details (up to 2 parents)
- Godparent/Sponsor name
- Desired baptism date
- Special requests/notes

#### **FUNERAL BOOKING** (⚠️ Marked as URGENT)
- Deceased's name and date of death
- Requesting family member details
- Contact email and phone
- Desired funeral mass date and time
- Expected attendees
- Special requests/notes

### Step 3: Submit Booking
- User clicks "Submit [Sacrament] Booking"
- System validates the form
- Data is sent to backend (`submit-sacrament-booking.php`)
- A unique **Booking ID** is generated (e.g., WED-5432-2026)
- User receives a confirmation modal with their Booking ID
- Confirmation email is sent (optional - requires mail setup)

### Step 4: Check Status Anytime
- Users can visit the "Check Status" tab
- Enter their Booking ID and email
- System displays current approval status:
  - **Pending**: Awaiting admin review
  - **Approved**: Booking confirmed
  - **Declined**: Booking rejected with reason
  - **Completed**: Sacrament completed

---

## 2. ADMIN WORKFLOW (Administrator)

### Database Setup (One-time)
1. Visit `PHP/setup-sacrament-db.php` in browser
2. This creates necessary database tables:
   - `sacrament_bookings` (main booking records)
   - `wedding_bookings` (wedding-specific details)
   - `baptism_bookings` (baptism-specific details)
   - `funeral_bookings` (funeral-specific details)

### Admin Dashboard Access
1. Admin logs in with credentials:
   - Username: `admin`
   - Password: `password` (Change this!)

2. Navigate to `PHP/manage-sacrament-bookings.php`

### View Dashboard
The admin dashboard shows:
- **Statistics Cards**: Pending, Approved, Declined counts
- **Pending Bookings Table**: All bookings awaiting review
- **Booking Details**: Click on any row to see complete information

### Approve a Booking
1. Click "✓ Approve" button
2. Add optional admin notes (e.g., "Confirmed date June 15, 2026")
3. Submit
4. Status changes to "APPROVED"
5. Approval email sent to parishioner

### Decline a Booking
1. Click "✗ Decline" button
2. **Must provide reason** (e.g., "Date unavailable", "Conflicting booking")
3. Submit
4. Status changes to "DECLINED"
5. Decline email sent with reason

### Email Notifications
- **Submission**: Parishioner receives confirmation with Booking ID
- **Approval**: Parishioner receives approval notification
- **Decline**: Parishioner receives decline reason

---

## 3. DATABASE SCHEMA

### sacrament_bookings (Main Table)
```
id              - Auto-incrementing primary key
booking_id      - Unique booking identifier (WED-XXXX-2026, etc.)
sacrament_type  - ENUM('wedding', 'baptism', 'funeral')
status          - ENUM('pending', 'approved', 'declined', 'completed')
created_at      - Timestamp of submission
updated_at      - Auto-updated timestamp
admin_notes     - Notes from admin
```

### wedding_bookings
```
booking_id      - Foreign key to sacrament_bookings
groom_name      - Groom's full name
bride_name      - Bride's full name
groom_email     - Groom's email (contact)
groom_phone     - Groom's phone number
wedding_date    - Desired wedding date
wedding_time    - Preferred time
guest_count     - Estimated guests
special_requests - Additional requests
```

### baptism_bookings
```
booking_id      - Foreign key to sacrament_bookings
baptism_type    - ENUM('Infant Baptism', 'Adult Baptism')
candidate_name  - Child/Adult being baptized
parent1_name, parent1_email, parent1_phone - Primary parent/guardian
parent2_name, parent2_email, parent2_phone - Secondary parent/guardian (optional)
godparent_name  - Godparent/Sponsor
baptism_date    - Desired baptism date
special_requests - Additional requests
```

### funeral_bookings
```
booking_id      - Foreign key to sacrament_bookings
deceased_name   - Full name of deceased
death_date      - Date of death
requestor_name  - Family member requesting
relationship    - Relationship to deceased
requestor_email - Contact email
requestor_phone - Contact phone
funeral_date    - Desired funeral mass date
funeral_time    - Preferred time
funeral_attendees - Expected attendance count
special_requests - Additional requests
is_urgent       - Boolean flag (always TRUE for funerals)
```

---

## 4. BOOKING STATUS WORKFLOW

```
┌─────────────────┐
│ BOOKING SUBMITTED│
│ (User fills form)│
└────────┬────────┘
         │
         ▼
┌──────────────────────┐
│   PENDING (Default)  │
│  Awaiting Admin      │
│  Review              │
└────┬──────────┬──────┘
     │          │
     ▼          ▼
┌──────────┐ ┌─────────┐
│ APPROVED │ │DECLINED │
│          │ │         │
│ Email    │ │ Email   │
│ sent     │ │ sent    │
└──────────┘ └─────────┘
```

---

## 5. SPECIAL FEATURES

### Funeral Booking Urgency
- All funeral bookings are marked as URGENT
- Display special warning: "⏰ We respond to funeral requests same day"
- Admin should prioritize funeral requests
- Status change from `pending` to `approved` should happen immediately

### Booking ID Format
- **Wedding**: WED-[0000-9999]-2026
- **Baptism**: BAP-[0000-9999]-2026
- **Funeral**: FUN-[0000-9999]-2026

### Status Check
Users can check their booking status anytime by providing:
- Booking ID
- Email address used in booking

---

## 6. FILE STRUCTURE

```
HTML/
  ├── sjb-sacrament-booking.html    ← Customer booking form

CSS/
  ├── sjb-sacrament-booking.css     ← Booking form styling
  └── sjb-admin-bookings.css        ← Admin dashboard styling

JS/
  └── sjb-booking-system.js         ← Client-side form handling

PHP/
  ├── setup-sacrament-db.php        ← Database initialization
  ├── submit-sacrament-booking.php  ← Form submission handler
  ├── manage-sacrament-bookings.php ← Admin dashboard
  ├── get-sacrament-details.php     ← Get booking details
  └── check-booking-status.php      ← Status check API
```

---

## 7. IMPLEMENTATION CHECKLIST

### Setup Phase
- [ ] Run `setup-sacrament-db.php` to create database tables
- [ ] Update admin credentials (username/password)
- [ ] Configure email settings in PHP (optional)
- [ ] Test database connection

### Launch Phase
- [ ] Test wedding booking form
- [ ] Test baptism booking form
- [ ] Test funeral booking form
- [ ] Test admin approval/decline flow
- [ ] Test status check feature

### Ongoing
- [ ] Monitor pending bookings regularly
- [ ] Respond to urgent funeral requests within 24 hours
- [ ] Archive completed bookings
- [ ] Update admin email contact information

---

## 8. SECURITY NOTES

⚠️ **Important Security Measures:**

1. **Change Admin Password**
   - Default: `admin` / `password`
   - Change immediately in code or database

2. **Email Validation**
   - All email fields are validated
   - Users must provide valid email for status checks

3. **Date Validation**
   - Only future dates allowed
   - System prevents past date bookings

4. **Access Control**
   - Admin pages require session authentication
   - Non-admin users redirected to login

5. **SQL Injection Prevention**
   - All queries use prepared statements
   - User input properly parameterized

6. **CSRF Protection** (Optional add-on)
   - Consider adding CSRF tokens to forms
   - Implement in production environment

---

## 9. TROUBLESHOOTING

### No bookings showing in admin panel?
- Check database tables exist: `SELECT * FROM sacrament_bookings;`
- Verify admin login successful
- Clear browser cache and reload

### Booking submission fails?
- Check PHP error logs
- Verify database connection settings
- Ensure `submit-sacrament-booking.php` is accessible

### Status check not working?
- Verify booking ID and email match
- Check database for booking record
- Ensure email matches one in booking form

### Email not sending?
- Configure PHP mail() function or PHPMailer
- Update email addresses in PHP files
- Check server mail configuration

---

## 10. FUTURE ENHANCEMENTS

- [ ] Calendar view of approved bookings
- [ ] Automatic email reminders before scheduled date
- [ ] SMS notifications for urgent funerals
- [ ] Payment integration for donations
- [ ] Automated conflict detection (overlapping dates)
- [ ] Admin user management (multiple admins)
- [ ] Booking modification by parishioners
- [ ] Export bookings to calendar/PDF
- [ ] Multi-language support
- [ ] Two-factor authentication for admin

---

## Support Contact

For technical issues or questions:
- Phone: (02) 1234-5678
- Email: bookings@sjbmakati.org
- Parish Office: Antonio Arnaiz Avenue corner Amorsolo Street, Makati City
