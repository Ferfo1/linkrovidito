<?php
// Adatbázis kapcsolódás
$host = '192.168.8.165:6033';
$dbname = 'url_shortener';
$username = 'szirony'; // változtasd meg az adatbázisod felhasználónevét
$password = 'szirony';     // állítsd be a jelszót ha szükséges

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Adatbázis kapcsolat sikertelen: " . $e->getMessage());
}

// Funkció a rövid kód generálásához
function generateShortCode($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $shortCode = '';
    for ($i = 0; $i < $length; $i++) {
        $shortCode .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $shortCode;
}

// URL rövidítése
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $longUrl = $_POST['long_url'];
    $customCode = isset($_POST['custom_code']) ? $_POST['custom_code'] : null;

    if (filter_var($longUrl, FILTER_VALIDATE_URL)) {
        // Ellenőrizzük, hogy létezik-e már ez az URL vagy az egyedi kód
        if ($customCode) {
            $stmt = $pdo->prepare("SELECT short_code FROM urls WHERE short_code = :short_code");
            $stmt->execute(['short_code' => $customCode]);
            $existingCode = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingCode) {
                echo "Ez a kód már foglalt, kérlek válassz másikat.";
                exit();
            }

            $shortCode = $customCode;
        } else {
            // Ha nincs egyedi kód, generálunk egy újat
            $shortCode = generateShortCode();
        }

        // Mentjük az adatbázisba
        $stmt = $pdo->prepare("INSERT INTO urls (long_url, short_code) VALUES (:long_url, :short_code)");
        $stmt->execute(['long_url' => $longUrl, 'short_code' => $shortCode]);

        $shortUrl = "http://yourdomain.com/" . $shortCode;
        echo "Rövidített URL: <a href='$shortUrl'>$shortUrl</a>";
    } else {
        echo "Érvénytelen URL!";
    }
}

// Eredeti URL visszaállítása a rövid kódból
if (isset($_GET['code'])) {
    $shortCode = $_GET['code'];

    // Keresés a rövid kód alapján
    $stmt = $pdo->prepare("SELECT long_url FROM urls WHERE short_code = :short_code");
    $stmt->execute(['short_code' => $shortCode]);
    $urlData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($urlData) {
        header("Location: " . $urlData['long_url']);
        exit();
    } else {
        echo "URL nem található!";
    }
}
?>

<!-- URL beküldő form -->
<form method="POST">
    <label for="long_url">Hosszú URL:</label>
    <input type="text" id="long_url" name="long_url" required><br>

    <label for="custom_code">Egyedi kód (opcionális):</label>
    <input type="text" id="custom_code" name="custom_code"><br>

    <button type="submit">Rövidítés</button>
</form>
