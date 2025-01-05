<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['wallet_address'])) {
    header("Location: index.php");
    exit;
}

// Obtener la información del usuario
$userQuery = $conn->prepare("SELECT wallet_address, balance, referral_code FROM Users WHERE wallet_address = ?");
$userQuery->bind_param("s", $_SESSION['wallet_address']);
$userQuery->execute();
$userResult = $userQuery->get_result();
$userData = $userResult->fetch_assoc();

// Obtener la cantidad de recompensa por referido desde la base de datos
$rewardQuery = $conn->prepare("SELECT reward_amount FROM settings_refferal WHERE description = 'Reward'");
$rewardQuery->execute();
$rewardResult = $rewardQuery->get_result();
$rewardData = $rewardResult->fetch_assoc();
$rewardAmount = $rewardData['reward_amount'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - USDT Rewards</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        function copyReferralText() {
            var copyText = document.getElementById("referral-text").textContent;
            var textArea = document.createElement("textarea");
            textArea.value = copyText;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand("copy");
            document.body.removeChild(textArea);

            var notification = document.getElementById("copy-notification");
            notification.style.display = "block";
            setTimeout(function() {
                notification.style.display = "none";
            }, 3000);
        }
    </script>
</head>
<body>
    <div id="copy-notification" class="copy-notification-unique">Referral text copied!</div>
    <div class="container-profile-unique">
        <h1>User Profile</h1>
        <div class="balance-box-unique">
            <p><strong>Balance:</strong> <?php echo number_format($userData['balance'], 6); ?> USDT</p>
        </div>
        <div class="wallet-box-unique">
            <p><strong>Wallet Address:</strong> <?php echo htmlspecialchars($userData['wallet_address']); ?></p>
        </div>
        <div class="referral-box-unique">
            <p><strong>Your Referral Code:</strong></p>
            <input type="text" id="referral-code" value="<?php echo htmlspecialchars($userData['referral_code']); ?>" readonly>
            <button onclick="copyReferralText()">Copy Text</button>
            <p id="referral-text">
                SIGN UP AND EARN <?php echo number_format($rewardAmount, 6); ?> USDT FOR INVITING ON <a href="https://t.me/YOURLINK" target="_blank">https://t.me/YOURLINK</a>.
                REGISTER WITH THE FOLLOWING CODE <?php echo htmlspecialchars($userData['referral_code']); ?> AND EARN 90% GENERATED BY YOUR REFERRAL.
            </p>
        </div>
        <button class="button-back-unique" onclick="window.location.href='faucet.php'">
            <i class="fas fa-arrow-left"></i> Back
        </button>
    </div>
</body>
</html>