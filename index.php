<?php
session_start();
require_once 'db.php'; // Asegúrate de que este archivo contiene la correcta configuración de tu base de datos.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Función para generar un código de invitación aleatorio de 5 dígitos
function generateReferralCode() {
    return sprintf("%05d", mt_rand(1, 99999));
}

// Inicialización de variables para el título del sitio y el mensaje de bienvenida
$siteTitle = 'EARN USDT'; // Valor predeterminado
$welcomeMessage = 'Welcome to our Tether Faucet! Please disable any ad blockers to ensure full functionality of the platform.'; // Valor predeterminado

// Obtener el título del sitio y el mensaje de bienvenida desde la base de datos
$contentQuery = $conn->prepare("SELECT content_key, content_value FROM SiteContent WHERE content_key IN ('site_title', 'welcome_message')");
if ($contentQuery === false) {
    die('Error en la consulta SQL: ' . htmlspecialchars($conn->error));
}
$contentQuery->execute();
$result = $contentQuery->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['content_key'] == 'site_title') {
        $siteTitle = $row['content_value'];
    } elseif ($row['content_key'] == 'welcome_message') {
        $welcomeMessage = $row['content_value'];
    }
}

// Comprobar si el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $wallet_address = $_POST['wallet_address'];
    $referral_code = $_POST['referral_code'] ?? null;
    $user_ip = $_SERVER['REMOTE_ADDR'];

    // Verificar si la dirección de wallet ya está registrada
    $query = $conn->prepare("SELECT id FROM Users WHERE wallet_address = ?");
    if ($query === false) {
        die('Error en la consulta SQL: ' . htmlspecialchars($conn->error));
    }
    $query->bind_param("s", $wallet_address);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        // La dirección de wallet ya está registrada, manejar como un login
        $_SESSION['wallet_address'] = $wallet_address;
        header("Location: faucet.php");
        exit;
    } else {
        // Generar un nuevo código de referido para el usuario
        $new_referral_code = generateReferralCode();

        // Registro nuevo, verifica la IP para multicuentas
        $ip_check = $conn->prepare("SELECT id FROM Users WHERE ip_address = ?");
        if ($ip_check === false) {
            die('Error en la consulta SQL: ' . htmlspecialchars($conn->error));
        }
        $ip_check->bind_param("s", $user_ip);
        $ip_check->execute();
        $ip_result = $ip_check->get_result();

        if ($ip_result->num_rows > 0) {
            echo "Registro denegado: se ha detectado múltiples cuentas desde la misma dirección IP.";
        } else {
            // Asignar el código de referido solo si se proporciona y es válido
            $referred_by = null;
            if ($referral_code) {
                $referrerQuery = $conn->prepare("SELECT id FROM Users WHERE referral_code = ?");
                if ($referrerQuery === false) {
                    die('Error en la consulta SQL: ' . htmlspecialchars($conn->error));
                }
                $referrerQuery->bind_param("s", $referral_code);
                $referrerQuery->execute();
                $referrerResult = $referrerQuery->get_result();

                if ($referrerRow = $referrerResult->fetch_assoc()) {
                    $referred_by = $referrerRow['id'];
                } else {
                    echo "Código de referido no válido.";
                }
            }

            // Insertar el nuevo usuario en la base de datos
            $insert = $conn->prepare("INSERT INTO Users (wallet_address, ip_address, referral_code, referred_by, registration_date) VALUES (?, ?, ?, ?, NOW())");
            if ($insert === false) {
                die('Error en la consulta SQL: ' . htmlspecialchars($conn->error));
            }

            $insert->bind_param("ssss", $wallet_address, $user_ip, $new_referral_code, $referred_by);
            $insert->execute();

            if ($insert->affected_rows === 1) {
                $_SESSION['wallet_address'] = $wallet_address; // Guarda la wallet en la sesión
                
                // Si existe un código de referido, manejar la recompensa
                if ($referred_by) {
                    // Obtener la cantidad de recompensa
                    $rewardQuery = $conn->prepare("SELECT reward_amount FROM settings_refferal WHERE description = 'Recompensa por referir a un nuevo usuario'");
                    if ($rewardQuery === false) {
                        die('Error en la consulta SQL: ' . htmlspecialchars($conn->error));
                    }
                    $rewardQuery->execute();
                    $rewardResult = $rewardQuery->get_result();

                    if ($rewardRow = $rewardResult->fetch_assoc()) {
                        $rewardAmount = $rewardRow['reward_amount'];

                        // Actualizar el balance del usuario que hizo la referencia
                        $updateBalance = $conn->prepare("UPDATE Users SET balance = balance + ? WHERE id = ?");
                        if ($updateBalance === false) {
                            die('Error en la consulta SQL: ' . htmlspecialchars($conn->error));
                        }
                        $updateBalance->bind_param("di", $rewardAmount, $referred_by);
                        $updateBalance->execute();
                    }
                }

                // Redirige al usuario a faucet.php
                header("Location: faucet.php");
                exit;
            } else {
                echo "Error al registrar el usuario.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($siteTitle); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container-index">
        <div class="header-index">
            <h1><?php echo htmlspecialchars($siteTitle); ?></h1>
            <p><?php echo htmlspecialchars($welcomeMessage); ?></p>
        </div>
        <form method="POST" class="login-form-index">
            <input type="text" name="wallet_address" placeholder="Please enter your Tether wallet FAUCETPAY address" required>
            <input type="text" name="referral_code" placeholder="Enter your referral code (optional)">
            <button type="submit">LOGIN / SIGN UP</button>
</center>
        </form>
    </div>
</body>
</html>
