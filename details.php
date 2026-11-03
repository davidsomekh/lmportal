<?php
require_once __DIR__ . '/../LM/db.php';

function validateLicense(string $productKey): bool
{
    try {
        $database = new DB();
        return $database->productKeyActive($productKey);
    } catch (Throwable $error) {
        error_log('License validation failed: ' . $error->getMessage());
        return false;
    }
}

function getCustomer($key)
{
	$db_key = new DB();
    return  $db_key->getKeyCompany($key);
}

function isKeyMaker($key)
{
	$db_key = new DB();
    return  $db_key->isProductKeyMaker($key);
}

function isKeyActivated($productKey)
{
    $db_key = new DB();
    return  $db_key->isProductKeyActivated($productKey);
	
}

function getLastActivation($productKey)
{
    $db_key = new DB();
    return $db_key->getKeyLastActivation($productKey);
}

function getLicenseFeatures($productKey)
{
    $db_key = new DB();
    $features = $db_key->getProductKeyFeatures($productKey);

    if (!is_array($features)) {
        return [];
    }

    usort($features, function ($first, $second) {
        return (int) ($first['NUMBER'] ?? 0) <=> (int) ($second['NUMBER'] ?? 0);
    });

    return $features;
}

function getFeatureName($featureNumber)
{
    static $featureNames = [
        1 => 'Basic SolidCAM License',
        2 => 'SolidWorks + SolidCAM Integration',
        3 => 'Inventor + InventorCAM Integration',
        4 => 'AutoCAD + InventorCAM Integration',
        5 => 'Basic Mill 2.5D (face, profile, pocket, drill, slot)',
        6 => 'Advanced 2.5D – Translated Surface',
        7 => 'Pocket recognition',
        8 => 'Hole recognition',
        9 => 'Mill 3D Module',
        10 => 'HSM full module',
        11 => 'HSM – rough operations only',
        12 => 'iMachining 2D Module',
        13 => 'iMachining 3D Module',
        14 => 'Turning Module',
        15 => 'HSS Module',
        16 => 'Simultaneous 4x',
        17 => 'Simultaneous 5x',
        18 => 'Machine Simulation Module',
        19 => '5x drill module',
        20 => 'Conversion of HSM to 5x operation',
        21 => 'WireCut 2x Module',
        22 => 'WireCut 4x Module',
        23 => 'STL Cutting module',
        24 => 'Encrypted posts only',
        25 => 'Cannot produce G-Code',
        26 => 'SolidCAM Express 2.5D Module',
        27 => 'SolidCAM Express HSS Module',
        28 => 'Drill recognition',
        29 => 'Probe operation',
        30 => 'External NC Verification (Vericut, G-Navi, etc.)',
        31 => 'Tool data management (TDM)',
        32 => 'Wintool software',
        33 => 'SolidWorks Embedded Full (Part, Assemblies, Drawings)',
        34 => 'SolidWorks Embedded Parts/Assembly',
        35 => 'SolidWorks Embedded parts only',
        36 => 'SolidCAM for education use',
        37 => 'Profile only (profile/drill operations)',
        38 => 'SolidCAM Editor',
        39 => 'Multi Turret sync',
        40 => 'Internal Use License / Trial',
        41 => 'Sim. Turning',
        42 => 'Probe reduced',
        43 => 'Reduced Mill 3D (drilling + engraving only)',
        44 => 'Prismatic HSM',
        45 => 'Swarf Machining',
        46 => '4x indexial',
        47 => '5x indexial',
        48 => 'C-Wrap 4x',
        49 => 'Mill turn back spindle',
        60 => 'MultiBlade 5x',
        61 => 'Port 5x',
        62 => 'Contour 5x',
        63 => 'Xpress plus',
        64 => 'Chamfer recognition',
        65 => 'Zoller',
        66 => 'Screw Machining',
        67 => 'Multi-Axis Roughing',
        68 => 'Swiss-Type',
        69 => 'Multi-depth Drill',
        79 => 'ToolTable',
        80 => 'Simulator',
        81 => 'Breaking',
        82 => 'Trimming',
        83 => 'Eureka',
        84 => 'Vericut',
        85 => 'Cimco',
        86 => 'HSM Xpress',
        87 => 'Cimco Add-On',
        88 => 'Editor Light',
        89 => 'Mazak',
        90 => 'Auto32',
        91 => 'SolidEdge',
        92 => 'Tiangong CAD',
        93 => 'Xpress 5X',
        94 => 'Xpress MillTurn',
        95 => 'Maker Version',
        96 => 'Additive',
        97 => 'Thread Whirling',
        98 => 'CAM Assist',
        99 => 'CAM Assist for Gov Cloud',
        201 => 'iMachining 2D core (add-on)',
    ];

    return $featureNames[$featureNumber] ?? 'Feature #' . $featureNumber;
}

function parseLicenseDate($date)
{
    $date = trim((string) $date);

    foreach (['d-m-Y', 'Y-m-d', 'd/m/Y', 'Y/m/d'] as $format) {
        $parsedDate = DateTime::createFromFormat('!' . $format, $date);
        $errors = DateTime::getLastErrors();

        if (
            $parsedDate !== false &&
            ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
        ) {
            return $parsedDate;
        }
    }

    return null;
}

function formatDisplayDate($date)
{
    $parsedDate = parseLicenseDate($date);

    return $parsedDate ? $parsedDate->format('F j, Y') : trim((string) $date);
}

$rawProductKey = $_GET['key'] ?? $_GET['product_key'] ?? $_POST['product_key'] ?? '';
$productKey = is_string($rawProductKey) ? trim($rawProductKey) : '';

if ($productKey === '') {
    header('Location: index.php');
    exit;
}

if (!validateLicense($productKey)) {
    $query = http_build_query([
        'error' => 'license_not_found',
        'key' => $productKey,
    ]);
    header('Location: index.php?' . $query);
    exit;
}

$isActivated = isKeyActivated($productKey);
$isMaker = $isActivated && isKeyMaker($productKey);

$customer = getCustomer($productKey);

$activation = $isActivated ? getLastActivation($productKey) : null;
$activationDate = '';
if (!empty($activation['TIMESTAMP'])) {
    $activationDate = (new DateTime('@' . (int) $activation['TIMESTAMP']))
        ->setTimezone(new DateTimeZone('Asia/Jerusalem'))
        ->format('F j, Y');
}

$features = getLicenseFeatures($productKey);

$expirationDates = [];
foreach ($features as $feature) {
    $parsedDate = parseLicenseDate($feature['EXPIRATION_DATE'] ?? '');
    if ($parsedDate) {
        $expirationDates[$parsedDate->format('Y-m-d')] = $parsedDate;
    }
}
ksort($expirationDates);
$nextExpiration = $expirationDates ? reset($expirationDates) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SolidCAM Key Details</title>
    <link rel="icon" href="favicon.ico">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 36px 20px;
            color: #2b171b;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 15% 15%, rgba(215, 25, 32, 0.14), transparent 34%),
                radial-gradient(circle at 85% 85%, rgba(255, 205, 209, 0.5), transparent 30%),
                #fff9fa;
        }

        .container {
            width: min(100%, 760px);
            margin: 0 auto;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo {
            width: 190px;
            height: auto;
            display: block;
        }

        .brand strong,
        .brand span {
            display: block;
        }

        .brand span {
            margin-top: 2px;
            color: #806c70;
            font-size: 13px;
        }

        .back {
            color: #715c61;
            text-decoration: none;
            font-size: 14px;
        }

        .back:hover {
            color: #d71920;
        }

        .card {
            padding: 26px;
            border: 1px solid #ecdde0;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 24px 70px rgba(89, 23, 31, 0.14);
            backdrop-filter: blur(16px);
            animation: card-in 0.5s ease both;
        }

        .summary {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 24px;
            border-bottom: 1px solid #f0e3e5;
        }

        h1 {
            margin: 0 0 8px;
            font-size: clamp(24px, 5vw, 34px);
        }

        .key {
            margin: 0;
            color: #755f64;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
        }

        .expires {
            margin: 8px 0 0;
            color: #c61520;
            font-size: 15px;
            font-weight: 700;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
            padding: 9px 13px;
            border: 1px solid;
            border-radius: 999px;
            font: 750 13px inherit;
        }

        .active {
            color: #087f69;
            border-color: rgba(8, 127, 105, 0.28);
            background: rgba(8, 127, 105, 0.08);
        }

        .activation {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
            min-width: 0;
        }

        .activation-info {
            display: grid;
            grid-template-columns: auto auto;
            gap: 6px 12px;
            margin: 0;
            padding: 12px 14px;
            border: 1px solid rgba(8, 127, 105, 0.18);
            border-radius: 14px;
            background: rgba(8, 127, 105, 0.04);
            font-size: 13px;
        }

        .activation-info dt {
            color: #806c70;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            align-self: center;
        }

        .activation-info dd {
            margin: 0;
            font-weight: 650;
            overflow-wrap: anywhere;
        }

        .inactive {
            color: #c61520;
            border-color: rgba(198, 21, 32, 0.28);
            background: rgba(198, 21, 32, 0.07);
        }

        .details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin: 22px 0 28px;
        }

        .detail {
            padding: 15px;
            border-radius: 14px;
            background: #fff5f6;
        }

        .detail span {
            display: block;
            margin-bottom: 5px;
            color: #806c70;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .detail strong {
            font-size: 15px;
            overflow-wrap: anywhere;
        }

        .detail.with-icon {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .detail.with-icon > div {
            min-width: 0;
        }

        .detail-icon {
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            padding: 8px;
            border-radius: 11px;
            fill: none;
            stroke: #d71920;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
            background: rgba(215, 25, 32, 0.08);
        }

        h2 {
            margin: 0 0 15px;
            font-size: 18px;
        }

        .features {
            display: grid;
            gap: 11px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 15px;
            border: 1px solid #eee0e2;
            border-radius: 14px;
            background: #fffafb;
        }

        .icon {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 10px;
            font-weight: 800;
            color: <?= $isActivated ? '#087f69' : '#8a7479' ?>;
            background: <?= $isActivated ? 'rgba(8, 127, 105, 0.08)' : 'rgba(138, 116, 121, 0.1)' ?>;
        }

        .feature-text {
            min-width: 0;
            flex: 1;
        }

        .feature strong,
        .feature small {
            display: block;
        }

        .feature small {
            margin-top: 3px;
            color: #806c70;
            line-height: 1.35;
        }

        .availability {
            color: <?= $isActivated ? '#087f69' : '#8a7479' ?>;
            font-size: 12px;
            font-weight: 700;
        }

        .notice {
            margin: 18px 0 0;
            color: #806c70;
            font-size: 12px;
            line-height: 1.5;
        }

        .status.inactive {
            display: inline-flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
            min-width: 196px;
            padding: 14px 18px;
            border: 0;
            border-radius: 16px;
            color: white;
            cursor: pointer;
            text-align: left;
            background: linear-gradient(135deg, #ef2a33 0%, #c4121d 58%, #9d1018 100%);
            box-shadow:
                0 12px 28px rgba(196, 18, 29, 0.32),
                inset 0 1px 0 rgba(255, 255, 255, 0.18);
            transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
        }

        .status.inactive:hover {
            filter: brightness(1.05);
            transform: translateY(-2px);
            box-shadow:
                0 16px 34px rgba(196, 18, 29, 0.38),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .status.inactive:focus-visible {
            outline: 3px solid rgba(215, 25, 32, 0.28);
            outline-offset: 3px;
        }

        .status-kicker {
            color: rgba(255, 255, 255, 0.78);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .status-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 17px;
            font-weight: 800;
            line-height: 1.2;
        }

        .status-action::after {
            content: "→";
            font-size: 18px;
            transition: transform 0.2s;
        }

        .status.inactive:hover .status-action::after {
            transform: translateX(3px);
        }

        .dialog-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 30;
            place-items: center;
            padding: 20px;
            background: rgba(32, 12, 16, 0.46);
            backdrop-filter: blur(8px);
        }

        .dialog-overlay.visible {
            display: grid;
        }

        .dialog {
            position: relative;
            width: min(100%, 460px);
            padding: 24px;
            border: 1px solid #ecdde0;
            border-radius: 22px;
            background: white;
            box-shadow: 0 28px 80px rgba(89, 23, 31, 0.24);
            animation: card-in 0.28s ease both;
        }

        .dialog-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 10px;
        }

        .dialog h2 {
            margin: 0;
        }

        .dialog-close {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border: 0;
            border-radius: 10px;
            color: #715c61;
            font: 700 20px/1 inherit;
            cursor: pointer;
            background: #fff5f6;
        }

        .dialog-close:hover {
            color: #c61520;
        }

        .dialog p {
            margin: 0 0 18px;
            color: #806c70;
            font-size: 14px;
            line-height: 1.45;
        }

        .activate-fields {
            display: grid;
            gap: 14px;
        }

        .activate-fields label {
            display: block;
            margin-bottom: 6px;
            color: #806c70;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .activate-fields input[type="email"],
        .activate-fields input[type="file"] {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #dfcdd1;
            border-radius: 12px;
            color: #2b171b;
            font: inherit;
            background: #fffafb;
        }

        .activate-fields input[type="email"]:focus {
            outline: none;
            border-color: #d71920;
            box-shadow: 0 0 0 3px rgba(215, 25, 32, 0.16);
            background: white;
        }

        .file-name {
            display: block;
            margin-top: 6px;
            color: #806c70;
            font-size: 12px;
        }

        .help-link {
            display: inline-block;
            margin-top: 8px;
            padding: 0;
            border: 0;
            color: #c61520;
            font: 700 13px inherit;
            text-decoration: underline;
            text-underline-offset: 2px;
            cursor: pointer;
            background: none;
        }

        .help-link:hover {
            color: #9d1018;
        }

        .help-overlay {
            z-index: 40;
            padding: 16px;
        }

        .help-dialog {
            width: min(100%, 920px);
            max-height: calc(100vh - 32px);
            overflow: auto;
            padding: 18px;
        }

        .help-dialog img {
            display: block;
            width: 100%;
            height: auto;
            border-radius: 14px;
            background: #fff5f6;
        }

        .activate-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
        }

        .activate-submit {
            padding: 11px 16px;
            border: 0;
            border-radius: 12px;
            color: white;
            font: 700 14px inherit;
            cursor: pointer;
            background: linear-gradient(135deg, #e12830, #a70f18);
        }

        .activate-submit:disabled,
        .dialog-close:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .activate-result {
            display: none;
            margin: 16px 0 0;
            padding: 14px 16px;
            border: 1px solid transparent;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 750;
            line-height: 1.45;
        }

        .activate-result.success,
        .activate-result.error {
            display: block;
        }

        .activate-result.success {
            color: #046652;
            border-color: rgba(8, 127, 105, 0.22);
            background: #e8f8f3;
            box-shadow: 0 8px 22px rgba(8, 127, 105, 0.12);
        }

        .activate-result.error {
            color: #b10f1c;
            border-color: rgba(198, 21, 32, 0.18);
            background: #fff1f3;
        }

        .dialog.busy::after {
            content: "Generating license…";
            display: grid;
            place-items: center;
            position: absolute;
            inset: 0;
            border-radius: inherit;
            color: #2b171b;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.86);
        }

        @keyframes card-in {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 560px) {
            .summary {
                flex-direction: column;
            }

            .status.inactive {
                width: 100%;
            }

            .activation {
                align-items: flex-start;
                width: 100%;
            }

            .details {
                grid-template-columns: 1fr;
            }

            .feature {
                align-items: flex-start;
            }

            .availability {
                display: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <main class="container">
        <div class="topbar">
            <div class="brand">
                <img
                    class="logo"
                    src="SolidCAM-AI-Logo-w-subline.svg"
                    alt="SolidCAM AI"
                >
            </div>
            <a class="back" href="index.php">← Check another key</a>
        </div>

        <section class="card">
            <div class="summary">
                <div>
                    <h1>License details</h1>
                    <p class="key"><?= htmlspecialchars($productKey, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($nextExpiration): ?>
                        <p class="expires"><?= $nextExpiration < new DateTime('today') ? 'Expired' : 'Expires' ?> <?= $nextExpiration->format('M j, Y') ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($isActivated): ?>
                    <div class="activation">
                        <span class="status active">● Activated</span>
                        <?php if ($activation): ?>
                            <dl class="activation-info">
                                <dt>Activated on</dt>
                                <dd><?= $activationDate !== '' ? $activationDate : '—' ?></dd>
                                <dt>Computer</dt>
                                <dd><?= htmlspecialchars($activation['COMP_NAME'] ?: '—', ENT_QUOTES, 'UTF-8') ?></dd>
                                <dt>Email</dt>
                                <dd><?= htmlspecialchars($activation['EMAIL'] ?: '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            </dl>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <button type="button" class="status inactive" id="activateNowBtn">
                        <span class="status-kicker">Not activated</span>
                        <span class="status-action">Activate now</span>
                    </button>
                <?php endif; ?>
            </div>

            <div class="details">
                <div class="detail with-icon">
                    <svg class="detail-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <path d="M3.27 6.96 12 12.01l8.73-5.05M12 22.08V12"/>
                    </svg>
                    <div>
                        <span>Product</span>
                        <strong><?= $isMaker ? 'SolidCAM Maker' : 'SolidCAM' ?></strong>
                    </div>
                </div>
                <div class="detail with-icon">
                    <svg class="detail-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <div>
                        <span>Customer</span>
                        <strong><?= $customer ?></strong>
                    </div>
                </div>
            </div>

            <div>
                <h2>Included features</h2>
                <?php if ($features): ?>
                    <ul class="features">
                    <?php foreach ($features as $feature):
                        $featureNumber = (int) ($feature['NUMBER'] ?? 0);
                        $expirationDate = trim((string) ($feature['EXPIRATION_DATE'] ?? ''));
                        $network = !empty($feature['NETWORK']) ? 'Network license' : 'Standalone';
                        $users = max(1, (int) ($feature['USERS'] ?? 1));
                        $metadata = [
                            'Feature #' . $featureNumber,
                            $network,
                            $users . ' ' . ($users === 1 ? 'user' : 'users'),
                        ];
                        if ($expirationDate !== '') {
                            $metadata[] = 'Expires ' . formatDisplayDate($expirationDate);
                        }
                    ?>
                        <li class="feature">
                            <span class="icon" aria-hidden="true"><?= 1 > 0 ? '✓' : '—' ?></span>
                            <span class="feature-text">
                                <strong><?= htmlspecialchars(getFeatureName($featureNumber), ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars(implode(' · ', $metadata), ENT_QUOTES, 'UTF-8') ?></small>
                            </span>
                            <span class="availability"><?= 1 > 0 ? 'Available' : 'Locked' ?></span>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="detail">No features were found for this license.</div>
                <?php endif; ?>
            </div>

        </section>
    </main>
    <?php if (!$isActivated): ?>
        <div class="dialog-overlay" id="activateOverlay" hidden>
            <div class="dialog" id="activateDialog" role="dialog" aria-modal="true" aria-labelledby="activateTitle">
                <div class="dialog-header">
                    <h2 id="activateTitle">Activate this license</h2>
                    <button type="button" class="dialog-close" id="activateClose" aria-label="Close">×</button>
                </div>
                <p>Enter the email for this product key and upload the computer’s .finger file. A license file will download if activation succeeds.</p>
                <form id="activateForm" autocomplete="off">
                    <div class="activate-fields">
                        <div>
                            <label for="email">Email</label>
                            <input
                                id="email"
                                type="email"
                                placeholder="name@company.com"
                                required
                            >
                        </div>
                        <div>
                            <label for="finger-file">Fingerprint file</label>
                            <input
                                id="finger-file"
                                type="file"
                                accept=".finger"
                                required
                            >
                            <span class="file-name" id="fingerFileName">No file selected</span>
                            <button type="button" class="help-link" id="fingerprintHelpBtn">
                                How do I generate a fingerprint?
                            </button>
                        </div>
                    </div>
                    <div class="activate-result" id="activateResult"></div>
                    <div class="activate-actions">
                        <button type="submit" class="activate-submit" id="activateSubmit">Generate license</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="dialog-overlay help-overlay" id="helpOverlay" hidden>
            <div class="dialog help-dialog" id="helpDialog" role="dialog" aria-modal="true" aria-labelledby="helpTitle">
                <div class="dialog-header">
                    <h2 id="helpTitle">How do I generate a fingerprint?</h2>
                    <button type="button" class="dialog-close" id="helpClose" aria-label="Close">×</button>
                </div>
                <img
                    src="fingerprint_example.png"
                    alt="Example of generating a SolidCAM fingerprint file"
                >
            </div>
        </div>
        <script>
            const productKey = <?= json_encode($productKey, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const activateNowBtn = document.getElementById("activateNowBtn");
            const overlay = document.getElementById("activateOverlay");
            const dialog = document.getElementById("activateDialog");
            const activateForm = document.getElementById("activateForm");
            const emailInput = document.getElementById("email");
            const fingerInput = document.getElementById("finger-file");
            const fingerFileName = document.getElementById("fingerFileName");
            const activateSubmit = document.getElementById("activateSubmit");
            const activateClose = document.getElementById("activateClose");
            const activateResult = document.getElementById("activateResult");
            const fingerprintHelpBtn = document.getElementById("fingerprintHelpBtn");
            const helpOverlay = document.getElementById("helpOverlay");
            const helpClose = document.getElementById("helpClose");
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            let isBusy = false;

            function openDialog() {
                overlay.hidden = false;
                overlay.classList.add("visible");
                emailInput.focus();
            }

            function closeDialog() {
                if (isBusy) return;
                overlay.classList.remove("visible");
                overlay.hidden = true;
            }

            function setBusy(busy) {
                isBusy = busy;
                activateSubmit.disabled = busy;
                activateClose.disabled = busy;
                dialog.classList.toggle("busy", busy);
            }

            function setResult(message, isError) {
                activateResult.textContent = message;
                activateResult.classList.toggle("error", Boolean(message) && isError);
                activateResult.classList.toggle("success", Boolean(message) && !isError);
            }

            function openHelp() {
                helpOverlay.hidden = false;
                helpOverlay.classList.add("visible");
            }

            function closeHelp() {
                helpOverlay.classList.remove("visible");
                helpOverlay.hidden = true;
            }

            activateNowBtn.addEventListener("click", openDialog);
            activateClose.addEventListener("click", closeDialog);
            overlay.addEventListener("click", (event) => {
                if (event.target === overlay) closeDialog();
            });
            fingerprintHelpBtn.addEventListener("click", openHelp);
            helpClose.addEventListener("click", closeHelp);
            helpOverlay.addEventListener("click", (event) => {
                if (event.target === helpOverlay) closeHelp();
            });
            document.addEventListener("keydown", (event) => {
                if (event.key !== "Escape") return;
                if (!helpOverlay.hidden) {
                    closeHelp();
                    return;
                }
                closeDialog();
            });
            fingerInput.addEventListener("change", () => {
                fingerFileName.textContent = fingerInput.files[0]
                    ? fingerInput.files[0].name
                    : "No file selected";
            });
            activateForm.addEventListener("submit", (event) => {
                event.preventDefault();
                submitActivation();
            });

            function submitActivation() {
                const email = emailInput.value.trim();
                const fingerFile = fingerInput.files[0];

                if (!email) {
                    setResult("Please enter an email.", true);
                    return;
                }

                if (!emailRegex.test(email)) {
                    setResult("Please enter a valid email address.", true);
                    return;
                }

                if (!fingerFile) {
                    setResult("Please load a fingerprint file.", true);
                    return;
                }

                if (!fingerFile.name.toLowerCase().endsWith(".finger")) {
                    setResult("Please upload a .finger file.", true);
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    const cleanedFileContent = String(event.target.result).replace(/\s/g, "");
                    offlineActivate(cleanedFileContent, email, productKey);
                };
                reader.onerror = function () {
                    setResult("The fingerprint file could not be read.", true);
                };
                reader.readAsText(fingerFile);
            }

            function offlineActivate(str, email, productKey) {
                setBusy(true);
                setResult("", false);

                const url = "/LM/activate.php?string=" + encodeURIComponent(str);
                const dataString = JSON.stringify({ email: email.trim(), pkey: productKey.trim() });
                const body = new URLSearchParams();
                body.append("offline_activation_details", dataString);

                fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                    },
                    body,
                })
                    .then(async (response) => {
                        const responseText = await response.text();
                        let parsed;

                        try {
                            parsed = JSON.parse(responseText);
                        } catch (error) {
                            throw new Error(responseText || "Activation failed.");
                        }

                        if (!response.ok) {
                            throw new Error(
                                typeof parsed === "string" ? parsed : (responseText || "Activation failed.")
                            );
                        }

                        const licenseContent = typeof parsed === "string"
                            ? parsed
                            : JSON.stringify(parsed);
                        const blob = new Blob([licenseContent], { type: "application/json" });
                        const link = document.createElement("a");
                        link.href = URL.createObjectURL(blob);
                        link.download = "solidcam_license.lic";
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(link.href);
                        setResult("License generated. The .lic file should start downloading.", false);
                    })
                    .catch((error) => {
                        setResult(error.message || "Activation failed.", true);
                    })
                    .finally(() => {
                        setBusy(false);
                    });
            }
        </script>
    <?php endif; ?>
</body>
</html>
