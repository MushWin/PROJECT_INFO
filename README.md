# Portfolio CMS — README

A PHP-based Portfolio Content Management System built on the XAMPP stack. It allows a user to manage and publicly display their personal portfolio, including profile info, resume, projects, certifications, and a contact form.

---

## Tech Stack

| Layer     | Technology                         |
|-----------|------------------------------------|
| Server    | Apache (via XAMPP)                 |
| Backend   | PHP 8.2                            |
| Database  | MariaDB 10.4.32 (MySQL-compatible) |
| DB Access | PHP MySQLi extension               |
| Frontend  | HTML, CSS, JavaScript, Font Awesome |

---

## Requirements

- [XAMPP](https://www.apachefriends.org/) with Apache and MySQL/MariaDB running
- PHP 8.0 or higher
- A modern web browser

---

## Installation & Setup

### 1. Place the Project Files

Copy the project folder into your XAMPP web root:

```
C:\xampp\htdocs\PROJECT_INFO\
```

### 2. Import the Database

1. Open your browser and go to: `http://localhost/phpmyadmin`
2. Create a new database named `portfolio_db`
3. Select the `portfolio_db` database
4. Click the **Import** tab
5. Choose the file: `portfolio_db.sql` (found in the project root)
6. Click **Go** to import

### 3. Verify Database Configuration

Open `config.php` in the project root. The default settings are:

```php
'db' => [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',           // leave blank for default XAMPP
    'name' => 'portfolio_db',
    'port' => 3306,
    'charset' => 'utf8mb4'
]
```

Update `user` and `pass` if your MariaDB credentials differ from the XAMPP defaults.

### 4. Start XAMPP Services

Open the XAMPP Control Panel and start:
- **Apache**
- **MySQL**

---

## Accessing the System

### Public Portfolio Page
```
http://localhost/PROJECT_INFO/
```
This is the publicly visible portfolio page. No login required to view it.

### Login Page
```
http://localhost/PROJECT_INFO/login.php
```

#### Default Account

| Field    | Value          |
|----------|----------------|
| Username | `Dwin`         |
| Password | `Dw1ndw1n`     |

> **Security Note:** Change the default password after your first login. Never share credentials in public or production environments.

You can log in with either your **username** or **registered email address**.

---

## Admin Panel

After logging in, you are redirected to the public portfolio page. Click **Dashboard** in the navigation bar to access the admin panel.

```
http://localhost/PROJECT_INFO/admin/dashboard.php
```

### Admin Sidebar Pages

| Page              | URL                                    | Description                                      |
|-------------------|----------------------------------------|--------------------------------------------------|
| Dashboard         | `admin/dashboard.php`                  | Overview of portfolio status and recent activity |
| Edit Portfolio    | `admin/edit_portfolio.php`             | Create or update your portfolio content          |
| Projects          | `admin/projects.php`                   | Add, edit, or delete portfolio projects          |
| View Site         | `index.php`                            | Preview the public portfolio page                |
| Logout            | `logout.php`                           | End your session                                 |

---

## Managing Your Portfolio

### Edit Portfolio (`admin/edit_portfolio.php`)

Fill in the following fields to build your portfolio:

| Section        | Fields                                                                 |
|----------------|------------------------------------------------------------------------|
| Basic Info     | Full Name, Professional Title, Short Bio, Email, Phone, Location       |
| About Me       | Detailed biography paragraph                                           |
| Resume         | Education history, Work/org experience, Skills list                    |
| Links          | LinkedIn URL, GitHub URL, CV file upload                               |
| Profile Image  | Upload a profile photo (JPG/PNG)                                       |
| Certifications | Add certificates with title, description, date, and image              |
| Contact        | Optional custom contact section text                                   |

Click **Save Portfolio** to apply changes.

### Managing Projects (`admin/projects.php`)

- Click **Add New Project** to create a project entry
- Fill in: Title, Description, Tech Stack, Project URL, GitHub URL, Image, Sort Order
- Use **Edit** to update an existing project
- Use **Delete** to remove a project

Projects are displayed in ascending `sort_order` on the public page.

---

## Database Tables

| Table             | Purpose                                              |
|-------------------|------------------------------------------------------|
| `users`           | Stores user accounts and login credentials           |
| `portfolio`       | Stores all portfolio content for each user           |
| `projects`        | Stores individual project entries                    |
| `activity_log`    | Records login events and admin actions               |
| `contact_messages`| Stores messages submitted via the contact form       |

---

## Session & Security Notes

- Sessions expire after **30 minutes** of inactivity. You will be redirected to the login page automatically.
- Passwords are stored as **bcrypt hashes** (`password_hash` / `password_verify`).
- All database queries use **prepared statements** to prevent SQL injection.
- User input is sanitized with `htmlspecialchars` before output.

---

## Folder Structure

```
PROJECT_INFO/
├── index.php               # Public portfolio page
├── login.php               # Login page
├── logout.php              # Logout handler
├── config.php              # Database & app configuration
├── portfolio_db.sql        # Database schema and seed data
├── admin/
│   ├── dashboard.php       # Admin dashboard
│   ├── edit_portfolio.php  # Edit portfolio content
│   └── projects.php        # Manage projects
├── includes/
│   ├── db_connection.php   # Database connection setup
│   └── functions.php       # Shared utility functions
├── css/                    # Stylesheets
├── js/                     # JavaScript files
└── uploads/                # Uploaded images and files
    ├── profile/            # Profile photos
    ├── cv/                 # CV/resume files
    └── certificates/       # Certificate images
```
