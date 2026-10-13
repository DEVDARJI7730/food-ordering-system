$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 'food_order_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root'; 

$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// If connecting to a remote cloud database (e.g. TiDB Cloud), enable SSL
if ($host !== 'localhost' && $host !== '127.0.0.1') {
    $ca_candidates = [
        '/etc/ssl/certs/ca-certificates.crt',       // Linux / Docker / Render
        '/etc/pki/tls/certs/ca-bundle.crt',         // RedHat / CentOS
        'C:/xampp/apache/bin/curl-ca-bundle.crt',   // Windows XAMPP
        'C:/xampp/php/extras/ssl/cacert.pem',      // Windows PHP
    ];
    foreach ($ca_candidates as $ca_path) {
        if (file_exists($ca_path)) {
            $pdo_options[PDO::MYSQL_ATTR_SSL_CA] = $ca_path;
            break;
        }
    }
    $pdo_options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

try {
    try {
        $conn = new PDO("mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4", $username, $password, $pdo_options);
    } catch (PDOException $e) {
        // Fallback for local development if default root password differs
        if ($host === 'localhost') {
            try {
                $conn = new PDO("mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4", $username, '');
                $password = '';
            } catch (PDOException $e2) {
                $conn = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, 'root');
                $conn->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                $conn->exec("USE `$db_name`");
            }
        } else {
            throw $e;
        }
    }
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Optional schema setup on local or new instances
    try {
        $conn->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $conn->exec("USE `$db_name`");
    } catch (PDOException $e) {
        // Ignored on managed cloud databases where database name is pre-allocated
    }
    
    $tables = $conn->query("SHOW TABLES LIKE 'restaurants'")->fetchAll();
    
    if (empty($tables)) {
        $schema_path = dirname(__DIR__) . '/database/schema.sql';
        if (file_exists($schema_path)) {
            $sql = file_get_contents($schema_path);
            $conn->exec($sql);
        }
    } else {
        try {
            $conn->exec("ALTER TABLE `users` MODIFY `role` ENUM('customer', 'admin', 'restaurant_owner') DEFAULT 'customer'");
        } catch (PDOException $e) {}

        try {
            $col_check = $conn->query("SHOW COLUMNS FROM `restaurants` LIKE 'commission_rate'")->fetch();
            if (!$col_check) {
                $conn->exec("ALTER TABLE `restaurants` ADD COLUMN `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 10.00 AFTER `description`");
            }
        } catch (PDOException $e) {}

        try {
            $col_check = $conn->query("SHOW COLUMNS FROM `food_items` LIKE 'restaurant_id'")->fetch();
            if (!$col_check) {
                $conn->exec("ALTER TABLE `food_items` ADD COLUMN `restaurant_id` INT DEFAULT NULL AFTER `id`");
                $conn->exec("ALTER TABLE `food_items` ADD CONSTRAINT `fk_food_items_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE");
            }
        } catch (PDOException $e) {}

        try {
            $col_check = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'restaurant_id'")->fetch();
            if (!$col_check) {
                $conn->exec("ALTER TABLE `orders` ADD COLUMN `restaurant_id` INT DEFAULT NULL AFTER `id`");
                $conn->exec("ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE SET NULL");
            }
        } catch (PDOException $e) {}

        try {
            $col_check = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'subtotal'")->fetch();
            if (!$col_check) {
                $conn->exec("ALTER TABLE `orders` ADD COLUMN `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `user_id`");
                $conn->exec("ALTER TABLE `orders` ADD COLUMN `cgst` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`");
                $conn->exec("ALTER TABLE `orders` ADD COLUMN `sgst` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `cgst`");
                $conn->exec("ALTER TABLE `orders` ADD COLUMN `delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `sgst`");
            }
        } catch (PDOException $e) {}

        try {
            $col_check = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'commission_rate'")->fetch();
            if (!$col_check) {
                $conn->exec("ALTER TABLE `orders` ADD COLUMN `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 10.00 AFTER `total_amount`");
                $conn->exec("ALTER TABLE `orders` ADD COLUMN `commission_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `commission_rate`");
                $conn->exec("ALTER TABLE `orders` ADD COLUMN `partner_earnings` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `commission_amount`");
            }
        } catch (PDOException $e) {}

        try {
            $col_check = $conn->query("SHOW COLUMNS FROM `order_items` LIKE 'restaurant_id'")->fetch();
            if (!$col_check) {
                $conn->exec("ALTER TABLE `order_items` ADD COLUMN `restaurant_id` INT DEFAULT NULL AFTER `order_id`");
                $conn->exec("ALTER TABLE `order_items` ADD CONSTRAINT `fk_order_items_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE SET NULL");
            }
        } catch (PDOException $e) {}

        try {
            $conn->exec("
                CREATE TABLE IF NOT EXISTS `restaurant_reviews` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `restaurant_id` INT NOT NULL,
                    `user_id` INT NOT NULL,
                    `order_id` INT DEFAULT NULL,
                    `rating` INT NOT NULL,
                    `review` TEXT DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            $conn->exec("
                CREATE TABLE IF NOT EXISTS `food_reviews` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `food_id` INT NOT NULL,
                    `user_id` INT NOT NULL,
                    `order_id` INT DEFAULT NULL,
                    `rating` INT NOT NULL,
                    `review` TEXT DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`food_id`) REFERENCES `food_items`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } catch (PDOException $e) {}
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
