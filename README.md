# Battleship

**Battleship** is a simple old-style naval battle game, playable online either in multiplayer mode or against an AI opponent.  
This project has both educational and recreational purposes.

---

## 🔧 Technologies Used

- PHP  
- MySQL  
- JavaScript  
- HTML  
- CSS  

> No external frameworks or libraries were used.

---

## 🚀 Getting Started

### Requirements

- A web server with PHP support (e.g., Apache, XAMPP)
- MySQL or compatible database

### Installation

1. Copy all project files to a directory on your server (e.g., `/var/www/html/battleship`).
2. Create a MySQL database (structure will be provided via `.sql` file in a future update).
3. Inside the project root, create a folder named `config/`.
4. Inside `config/`, create a file named `database.php` and insert the following content, adjusting credentials as needed:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'DBUser');
define('DB_PASS', 'DBPw');
define('DB_NAME', 'DBName');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch(PDOException $e) {
    die("Connection error: " . DB_NAME . " - " . DB_USER . " - " . DB_PASS . " - " . $e->getMessage());
}
?>

##🧪 Project Status

This project is **still under development**.
So far, it has been tested on Firefox and Chrome browsers, under Windows and Linux desktop environments.
Tests on macOS and iPhone are still pending.

##📄 License

This project is released as open source.
Contributions are welcome!
The specific license will be added in a future update — suggestions are appreciated (MIT, GPL, etc.).

##👤 Author

Stefano Gaviglia
Feel free to fork, suggest improvements, or report issues via the GitHub repository.

##🤝 Contributions

If you'd like to contribute, feel free to open a pull request or suggest improvements via issues.
