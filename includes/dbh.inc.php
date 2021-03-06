<?php

include "credentials.inc.php";

$conn = mysqli_connect($servername, $dBUsername, $dBPassword);

if ($conn->connect_error) {
    http_response_code(500);
    exit();
}
$sql = "CREATE DATABASE IF NOT EXISTS " . $dBName;
if (!$conn->query($sql)) {
    http_response_code(500);
    exit();
}

$conn->select_db($dBName);

$sql = "CREATE TABLE IF NOT EXISTS parts_map (
    playground_id int(11),
    part_id int(11),
    amount int(3)
)";

if (!$conn->query($sql)) {
    http_response_code(500);
    exit();
}

$sql = "CREATE TABLE IF NOT EXISTS parts (
    part_id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
    name VARCHAR(30)
)";

if (!$conn->query($sql)) {
    http_response_code(500);
    exit();
}

$sql = "CREATE TABLE IF NOT EXISTS reviews (
    playground_id int(11),
    nickname varchar(16),
    ip varchar(16),
    rating TINYINT UNSIGNED,
    comment TEXT,
    review_date DATE
)";

if (!$conn->query($sql)) {
    http_response_code(500);
    exit();
}

$sql = "CREATE TABLE IF NOT EXISTS pictures (
    playground_id int(11),
    path TINYTEXT
)";

if (!$conn->query($sql)) {
    http_response_code(500);
    exit();
}

$sql = "CREATE TABLE IF NOT EXISTS playgrounds (
    id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
    name VARCHAR(30),
    lat FLOAT,
    lng FLOAT,
    age_from int(3),
    age_to int(3),
    always_open BIT(1),
    catering_available BIT(1),
    ip varchar(16),
    upload_date DATE
)";

if (!$conn->query($sql)) {
    http_response_code(500);
    exit();
}


$defaultparts = array("Tafeltennistafel", "Goal", "Basket", "Bankje / Picknicktafel", "Schommel", "Draaimolen", "Zandbak", "Kabelbaan", "Waterpomp", "Parcours", "Wipwap", "Wipkip", "Glijbaan", "Rekstok", "Speelhuisje", "Klimtoestel", "Trampoline", "Springkussen");
sort($defaultparts);

foreach ($defaultparts as $part) {
    $sql = "SELECT part_id FROM parts WHERE name='" . $part . "'";
    $result = $conn->query($sql);

    if (!$result) {
        http_response_code(500);
        exit();
    }
    $potentialValue = $result->fetch_row()[0];

    if (empty($potentialValue)) {
        $sql = "INSERT INTO  parts (name) VALUES (?)";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            http_response_code(500);
            exit();
        } else {
            mysqli_stmt_bind_param($stmt, "s", $part);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

$ip = getenv('HTTP_CLIENT_IP') ?:
    getenv('HTTP_X_FORWARDED_FOR') ?:
    getenv('HTTP_X_FORWARDED') ?:
    getenv('HTTP_FORWARDED_FOR') ?:
    getenv('HTTP_FORWARDED') ?:
    getenv('REMOTE_ADDR');
