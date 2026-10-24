# 🌟 GatherPoint — Community Activity Booking System

An intuitive, full-stack community activity reservation system engineered with **Native PHP (OOP)**, **MySQL via PDO**, and a responsive interface powered by **Bootstrap 5.3.3**.

---

## 🧭 Step-by-Step User Journey

### 1️⃣ Member Experience 👤
* **Explore Activities 🔍:** Browse upcoming community workshops, sports sessions, and creative classes directly on the homepage, checking date, time, and remaining seats in real time.
* **Create an Account 📝:** Register securely via `register.php` with built-in password validation and hashing.
* **Log In 🔐:** Authenticate via `login.php` with session-based access control and automatic role redirection.
* **Book a Session 📅:** Select an available time slot and click `Book Now`. Enter optional notes and reserve instantly with race-condition prevention (preventing overbooking and duplicate registrations).
* **Track Bookings 📋:** Access `my_reservations.php` to monitor live reservation status (`Pending`, `Confirmed`, `Rejected`, `Cancelled`) or cancel upcoming appointments anytime.

---

### 2️⃣ Administrator Portal 🛠️
* **Dashboard Overview 📊:** Monitor real-time center statistics, total reservations, active users, and session metrics.
* **Manage Reservations 📝:** Search, filter, approve, or reject user bookings on `reservations.php`.
* **Manage Time Slots ⏰:** Create new activity sessions with custom capacities via a modal popup, monitor occupancy, and safely delete obsolete slots with confirmation modals on `slots.php`.
* **User Management 👥:** Review registered members, toggle active/inactive account access, and promote/demote roles between `User` and `Admin` on `users.php`.

---

## ⚙️ Core Technical Highlights

* **Full CRUD Implementation 🔄:**
  * **Create:** Account registration, slot scheduling, and activity booking.
  * **Read:** Dynamic homepage catalog, admin data tables, and user booking histories.
  * **Update:** Reservation approval/rejection/cancellation, and member role/status toggles.
  * **Delete:** Time slot deletion with interactive confirmation dialogs and cascade safety.
* **Security & Reliability 🛡️:**
  * Prepared statements with PDO across all queries to prevent SQL Injection.
  * Secure password hashing using native PHP mechanisms.
  * Authentication guards (`auth_guard.php`) protecting administrative routes.
  * Database transaction locks (`FOR UPDATE`) in booking workflows to eliminate double-booking.
* **Responsive UI 🎨:** Modern card-based UI built with Bootstrap 5.3.3, Bootstrap Icons, and customized Google Fonts (`Plus Jakarta Sans`).

---

## 🚀 Local Setup & Installation

1. **Start Local Server:**
   * Launch **XAMPP** (or your preferred PHP stack) and start both `Apache` and `MySQL`.
2. **Database Configuration:**
   * Open **phpMyAdmin**.
   * Create a new database named `community_center_booking`.
   * Import the provided `schema.sql` to generate all tables and constraints.
3. **Deploy Project:**
   * Place the project directory inside your web root:
     ```text
     htdocs/community-activity-booking/
     ```
4. **Run the Application:**
   * Navigate in your browser to:
     ```text
     http://localhost/community-activity-booking/
     ```

---

## 📁 Project Architecture

```text
community-activity-booking/
├── admin/
│   ├── dashboard.php       # System metrics & analytics
│   ├── reservations.php    # Booking approval & management
│   ├── slots.php           # Scheduling & session controls
│   └── users.php           # Account moderation & permissions
├── classes/
│   ├── TimeSlot.php        # Scheduling & capacity OOP logic
│   ├── User.php            # Authentication & account OOP logic
│   └── Reservation.php     # Booking workflows & state management
├── config/
│   └── db.php              # PDO database connection class (DBConn)
├── includes/
│   ├── auth_guard.php      # Session & role access gates
│   ├── header.php          # Shared layout & navigation sidebar
│   └── footer.php          # Scripts & closing tags
├── book.php                # Booking checkout & confirmation
├── index.php               # Public homepage & activity discovery
├── login.php               # User login form & auth handler
├── logout.php              # Session termination
├── my_reservations.php     # Member reservation history & cancellation
├── register.php            # User registration form & validation
├── schema.sql              # Database schema & table definitions
└── README.md               # Project documentation