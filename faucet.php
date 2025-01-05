<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['wallet_address'])) {
    header("Location: index.php");
    exit;
}

// Obtener el balance y último tiempo de reclamo del usuario
$query = $conn->prepare("SELECT balance, last_claim_time FROM Users WHERE wallet_address = ?");
$query->bind_param("s", $_SESSION['wallet_address']);
$query->execute();
$result = $query->get_result();
$userData = $result->fetch_assoc();

$currentTime = time();
$nextClaimTime = strtotime($userData['last_claim_time']) + 2 * 60; // Asumiendo un tiempo de espera de 15 minutos por defecto  TIEMPO DE RECLAMO

// Verifica si ya es tiempo para otro reclamo
$canClaim = $currentTime >= $nextClaimTime;

// Obtener configuración del claim
$claimQuery = $conn->prepare("SELECT * FROM settings_claim WHERE id = 1");
$claimQuery->execute();
$claimConfig = $claimQuery->get_result()->fetch_assoc();

// Obtener configuración del banner pop-up
$bannerQuery = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'banner_pop_html'");
$bannerQuery->execute();
$bannerResult = $bannerQuery->get_result();
$bannerData = $bannerResult->fetch_assoc();

// Obtener claves de hCaptcha
$hcaptchaQuery = $conn->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'hcaptcha_%_key'");
$hcaptchaQuery->execute();
$hcaptchaKeys = [];
foreach ($hcaptchaQuery->get_result() as $row) {
    $hcaptchaKeys[$row['setting_key']] = $row['setting_value'];
}


// Asegúrate de incluir esta lógica dentro de la sección donde se manejan los reclamos

// Obtener el usuario referido y la configuración de la recompensa por referidos
$referralQuery = $conn->prepare("SELECT referred_by FROM Users WHERE wallet_address = ?");
$referralQuery->bind_param("s", $_SESSION['wallet_address']);
$referralQuery->execute();
$referralResult = $referralQuery->get_result()->fetch_assoc();
$referredBy = $referralResult['referred_by'];

if ($referredBy != 0) { // Asegúrate de que existe un referidor
    // Obtener el porcentaje de recompensa por referidos
    $referralPercentQuery = $conn->prepare("SELECT referral_percentage FROM settings_referral WHERE id = 1");
    $referralPercentQuery->execute();
    $referralPercentResult = $referralPercentQuery->get_result()->fetch_assoc();
    $referralPercentage = $referralPercentResult['referral_percentage'];

    // Calcular la recompensa por referidos
    $referralReward = ($claimConfig['reward_amount'] * $referralPercentage) / 100;

    // Actualizar el saldo del referidor
    $updateReferrerBalance = $conn->prepare("UPDATE Users SET balance = balance + ? WHERE id = ?");
    $updateReferrerBalance->bind_param("di", $referralReward, $referredBy);
    $updateReferrerBalance->execute();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USDT Rewards</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
    


    <script>
        // Función para detectar bloqueadores de anuncios
        function detectAdBlock() {
            var adBlockDetected = false;

            var adTest = document.createElement('div');
            adTest.innerHTML = '&nbsp;';
            adTest.className = 'adsbox';
            document.body.appendChild(adTest);

            window.setTimeout(function() {
                if (adTest.offsetHeight === 0) {
                    adBlockDetected = true;
                }
                adTest.remove();

                if (adBlockDetected) {
                    var adBlockMessage = document.createElement('div');
                    adBlockMessage.id = 'adblock-message';
                    adBlockMessage.innerHTML = `
                        <div class="adblock-popup">
                            <h2>Ad Blocker Detected</h2>
                            <p>Please disable your ad blocker to use this platform.</p>
                            <p>Refresh the page after disabling the ad blocker.</p>
                        </div>
                    `;
                    document.body.appendChild(adBlockMessage);
                }
            }, 100);
        }

        // Función para detectar Brave
        function detectBrave() {
            var isBrave = false;

            // Brave specific method
            if (navigator.brave && navigator.brave.isBrave) {
                navigator.brave.isBrave().then(function(result) {
                    if (result) {
                        isBrave = true;
                        showAdBlockMessage();
                    }
                });
            } else {
                // Fallback method
                var adTest = document.createElement('div');
                adTest.innerHTML = '&nbsp;';
                adTest.className = 'adsbox';
                document.body.appendChild(adTest);

                window.setTimeout(function() {
                    if (adTest.offsetHeight === 0) {
                        isBrave = true;
                    }
                    adTest.remove();

                    if (isBrave) {
                        showAdBlockMessage();
                    }
                }, 100);
            }
        }

        // Mostrar el mensaje de bloqueador de anuncios
        function showAdBlockMessage() {
            var adBlockMessage = document.createElement('div');
            adBlockMessage.id = 'adblock-message';
            adBlockMessage.innerHTML = `
                <div class="adblock-popup">
                    <h2>Ad Blocker Detected</h2>
                    <p>Please disable your ad blocker to use this platform.</p>
                    <p>Refresh the page after disabling the ad blocker.</p>
                </div>
            `;
            document.body.appendChild(adBlockMessage);
        }

        // Ejecutar las funciones al cargar la página
        window.onload = function() {
            detectAdBlock();
            detectBrave();
        };
    </script>
    <style>
        .adsbox {
            height: 1px;
            width: 1px;
            position: absolute;
            top: -1px;
            left: -1px;
        }

        #adblock-message {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .adblock-popup {
            background: black;
            color: red;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
        }

        .adblock-popup h2 {
            margin-bottom: 10px;
        }
    </style>
    
    
    
       <?php include_once (dirname(__FILE__) . '/pa_antiadblock_7664378.php'); ?>
    
</head>
<body>

    
    
    
    
    <div id="faucet-container">
        <h1>USDT Rewards</h1>
        <div class="referral-info" style="margin-top: 20px; text-align: center; font-size: 16px; color: #4CAF50;">
    <p>ACTIVE REFERRAL SYSTEM EARN 90% OF WHAT YOUR REFERRALS GENERATE GO TO THE PROFILE SECTION AND GET YOUR INVITATION CODE.</p>
</div>

        <p>Your balance: <span id="user-balance"><?php echo number_format($userData['balance'], 6); ?></span> USDT</p>
        <button id="claim-button" class="button-claim" onclick="showBanner()" <?php echo $canClaim ? '' : 'disabled'; ?>>
            Claim <?php echo $claimConfig['reward_amount']; ?> USDT
        </button>
        <p id="cooldown-timer" style="<?php echo $canClaim ? 'display:none;' : ''; ?>">
            Wait for the next claim: <span id="time"></span>
        </p>
        <button id="button-withdraw" onclick="window.location.href='withdraw.php'">
            <i class="fa fa-wallet"></i> Withdraw
        </button>
        <button id="button-profile" onclick="window.location.href='profile.php'">
            <i class="fa fa-user"></i> Profile
        </button>
    </div>

    <div id="banner-pop" style="display:none;">
        <span id="close-banner" onclick="closeBanner()">&times;</span>
        <?php echo $bannerData['setting_value']; ?>
        <form id="captcha-form" action="verify_captcha.php" method="POST">
            <div class="h-captcha" data-sitekey="<?php echo $hcaptchaKeys['hcaptcha_site_key']; ?>"></div>
            <button id="verify-button" type="submit">Verify</button>
        </form>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
    <div id="error-pop">
        <span id="close-error" onclick="closeError()">&times;</span>
        <p id="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
    <div id="success-pop">
        <span id="close-success" onclick="closeSuccess()">&times;</span>
        <p id="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
    </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (!<?php echo $canClaim ? 'true' : 'false'; ?>) {
            updateCooldown();
        }
    });

    function showBanner() {
        document.getElementById('banner-pop').style.display = 'block';
    }

    function closeBanner() {
        document.getElementById('banner-pop').style.display = 'none';
    }

    function closeError() {
        document.getElementById('error-pop').style.display = 'none';
    }

    function closeSuccess() {
        document.getElementById('success-pop').style.display = 'none';
    }

    function updateBalance(rewardAmount) {
        const userBalanceElement = document.getElementById('user-balance');
        const currentBalance = parseFloat(userBalanceElement.textContent);
        const newBalance = (currentBalance + rewardAmount).toFixed(6);
        userBalanceElement.textContent = newBalance;
    }

    function updateCooldown() {
        let cooldownTimer = document.getElementById('cooldown-timer');
        let timeDisplay = document.getElementById('time');
        let remainingTime = <?php echo $nextClaimTime - time(); ?>;

        let interval = setInterval(function() {
            if (remainingTime <= 0) {
                clearInterval(interval);
                document.getElementById('claim-button').disabled = false;
                cooldownTimer.style.display = 'none';
            } else {
                let minutes = Math.floor(remainingTime / 60);
                let seconds = remainingTime % 60;
                timeDisplay.textContent = `${minutes}m ${seconds}s`;
                remainingTime--;
            }
        }, 1000);
    }
    </script>
</body>
</html>
