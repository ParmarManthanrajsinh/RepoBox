# RepoBox 📦

RepoBox is a lightweight, self-hosted personal cloud repository built with PHP and MySQL. Designed with a clean, GitHub-style interface, it allows users to effortlessly upload entire directory structures, preview code and images inline, and download their repository as a ZIP archive.

## Features ✨
* **GitHub-Style Navigation**: Browse through your uploaded folders with intuitive breadcrumb navigation and a hierarchical directory tree.
* **Folder Uploads**: Drag and drop or select entire folders to upload. The system automatically preserves your directory structure.
* **Inline File Viewer**: Preview source code, text files, and images directly in the browser—no need to download them first.
* **Repository Export**: Download your entire repository (or any sub-structure) as a neatly packaged ZIP file in one click.
* **GitHub Dark Theme**: A sleek, high-contrast dark interface matching the official GitHub dark aesthetics for a premium developer experience.
* **Secure Authentication**: Built-in user registration and encrypted password login.

---

## Tech Stack 🛠️
This project was built to be simple and highly compatible with classic shared hosting or local development environments.

* **Backend:** PHP 8+
* **Database:** MySQL
* **Frontend:** HTML5, Bootstrap 5 (CSS/JS), Vanilla JavaScript
* **Recommended Environment:** WAMP, XAMPP, or LAMP stack.

---

## Setup Instructions (WAMP / Localhost) 🚀

1. **Install WAMP Server** (or your preferred environment) and ensure both Apache and MySQL are running.
2. **Clone the Repository**:
   Place the `RepoBox` folder into your web server's public directory (e.g., `C:\wamp64\www\RepoBox`).
3. **Database Setup**:
   - Open phpMyAdmin (usually `http://localhost/phpmyadmin`).
   - Create a new database named `repobox`.
   - Import the provided `database.sql` file into the `repobox` database to create the required `users` and `files` tables.
4. **Configuration Check**:
   - Open `includes/db.php` in a text editor.
   - Verify the database credentials match your WAMP setup. The defaults usually work fine for WAMP (`root` with an empty password):
     ```php
     $host = 'localhost';
     $dbname = 'repobox';
     $user = 'root';
     $pass = '';
     ```
5. **Set up Uploads Folder**:
   Ensure the `uploads/` directory exists in the project root and is writable by the web server.
6. **Launch**:
   Navigate to `http://localhost/RepoBox` in your web browser. Create a new account to begin!
