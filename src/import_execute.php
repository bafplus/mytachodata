<?php
require_once __DIR__ . '/inc/db.php';

// Start session
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['user_id']);
$data = $_SESSION['import_data'] ?? null;

if (!$data) {
    die("No import data found. Please upload a .ddd file first.");
}

try {
    // 1. Connect to system DB to ensure user DB exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS mytacho_user_$userId CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // 2. Connect to the user-specific DB
    $userDb = new PDO(
        "mysql:host=$dbHost;dbname=mytacho_user_$userId;charset=utf8mb4",
        $dbUser,
        $dbPass,
        $pdoOptions
    );
    $userDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. Create tables if not exist
    $userDb->exec("
        CREATE TABLE IF NOT EXISTS driver_cards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_number VARCHAR(64),
            chip_number VARCHAR(64),
            issue_date DATETIME NULL,
            expiry_date DATETIME NULL,
            issuing_authority VARCHAR(128),
            driver_name VARCHAR(128),
            driver_birthdate DATE NULL,
            driver_licence_number VARCHAR(64),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS driver_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_id INT,
            start_time DATETIME,
            end_time DATETIME,
            activity_type VARCHAR(32),
            FOREIGN KEY (card_id) REFERENCES driver_cards(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS vehicles_used (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_id INT,
            registration_number VARCHAR(32),
            start_time DATETIME,
            end_time DATETIME,
            odometer_start INT NULL,
            odometer_end INT NULL,
            FOREIGN KEY (card_id) REFERENCES driver_cards(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS places_daily_work (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_id INT,
            date DATE,
            place_code VARCHAR(16),
            country_code VARCHAR(8),
            FOREIGN KEY (card_id) REFERENCES driver_cards(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS border_crossings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_id INT,
            timestamp DATETIME,
            country_code VARCHAR(8),
            FOREIGN KEY (card_id) REFERENCES driver_cards(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS events_faults (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_id INT,
            timestamp DATETIME,
            event_type VARCHAR(64),
            severity VARCHAR(16),
            description TEXT,
            FOREIGN KEY (card_id) REFERENCES driver_cards(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS load_unload (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_id INT,
            timestamp DATETIME,
            operation_type VARCHAR(16),
            location VARCHAR(128),
            FOREIGN KEY (card_id) REFERENCES driver_cards(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS gnss_positions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_id INT,
            timestamp DATETIME,
            latitude DECIMAL(10,7),
            longitude DECIMAL(10,7),
            FOREIGN KEY (card_id) REFERENCES driver_cards(id) ON DELETE CASCADE
        );
    ");

    // 4. Insert driver card (static info)
    $stmt = $userDb->prepare("
        INSERT INTO driver_cards 
        (card_number, chip_number, issue_date, expiry_date, issuing_authority, driver_name, driver_birthdate, driver_licence_number) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $cardNumber = $data['card_icc_identification_1']['CardNumber'] ?? null;
    $chipNumber = $data['card_chip_identification_1']['ChipIdentification'] ?? null;
    $issueDate  = $data['driver_card_application_identification_1']['IssueDate'] ?? null;
    $expiryDate = $data['driver_card_application_identification_1']['ExpiryDate'] ?? null;
    $issuer     = $data['driver_card_application_identification_1']['IssuingAuthority'] ?? null;
    $driverName = $data['card_identification_and_driver_card_holder_identification_1']['HolderName'] ?? null;
    $birthdate  = $data['card_identification_and_driver_card_holder_identification_1']['BirthDate'] ?? null;
    $licence    = $data['card_driving_licence_information_1']['DrivingLicenceNumber'] ?? null;

    $stmt->execute([
        $cardNumber,
        $chipNumber,
        $issueDate ? date('Y-m-d H:i:s', strtotime($issueDate)) : null,
        $expiryDate ? date('Y-m-d H:i:s', strtotime($expiryDate)) : null,
        $issuer,
        $driverName,
        $birthdate ? date('Y-m-d', strtotime($birthdate)) : null,
        $licence
    ]);

    $cardId = $userDb->lastInsertId();

    // 5. Insert driver activities
    if (!empty($data['card_driver_activity_1'])) {
        $stmt = $userDb->prepare("INSERT INTO driver_activity (card_id, start_time, end_time, activity_type) VALUES (?, ?, ?, ?)");
        foreach ($data['card_driver_activity_1'] as $row) {
            $stmt->execute([
                $cardId,
                $row['Start'] ?? null,
                $row['End'] ?? null,
                $row['Activity'] ?? null
            ]);
        }
    }

    // 6. Insert vehicles used
    if (!empty($data['card_vehicles_used_1'])) {
        $stmt = $userDb->prepare("INSERT INTO vehicles_used (card_id, registration_number, start_time, end_time, odometer_start, odometer_end) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($data['card_vehicles_used_1'] as $row) {
            $stmt->execute([
                $cardId,
                $row['RegistrationNumber'] ?? null,
                $row['StartTime'] ?? null,
                $row['EndTime'] ?? null,
                $row['OdometerBegin'] ?? null,
                $row['OdometerEnd'] ?? null
            ]);
        }
    }

    // TODO: Do the same for border crossings, events, load/unload, gnss etc.
    // (structure is now ready, just map the fields when confirmed from JSON)

    echo "✅ Import successful! Data has been stored for card: " . htmlspecialchars($cardNumber);

} catch (PDOException $e) {
    echo "Import failed: " . $e->getMessage();
}
