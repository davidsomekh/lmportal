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

function getKeyCreatedDate($productKey)
{
    $db_key = new DB();
    $timestamp = $db_key->getKeyCreatedTimestamp($productKey);

    if ($timestamp <= 0) {
        return '';
    }

    return (new DateTime('@' . $timestamp))
        ->setTimezone(new DateTimeZone('Asia/Jerusalem'))
        ->format('M j, Y');
}

function getLicenseFeatures($productKey)
{
    $db_key = new DB();
    $features = $db_key->getProductKeyFeatures($productKey);

    if (!is_array($features)) {
        return [];
    }

    // Feature 1 (Basic SolidCAM License) is on every license, so it is not listed.
    $features = array_values(array_filter($features, function ($feature) {
        return (int) ($feature['NUMBER'] ?? 0) !== 1;
    }));

    // CAD integration features first, then editor/simulator modes, then the rest.
    $groupOrder = ['integration' => 0, 'mode' => 1, '' => 2];
    usort($features, function ($first, $second) use ($groupOrder) {
        $firstNumber = (int) ($first['NUMBER'] ?? 0);
        $secondNumber = (int) ($second['NUMBER'] ?? 0);

        return [$groupOrder[getFeatureGroup($firstNumber)], $firstNumber]
            <=> [$groupOrder[getFeatureGroup($secondNumber)], $secondNumber];
    });

    return $features;
}

function getFeatureGroup($featureNumber)
{
    static $integrationFeatures = [2, 3, 4, 33, 34, 35, 91, 92];
    static $modeFeatures = [38, 80, 88];

    if (in_array($featureNumber, $integrationFeatures, true)) {
        return 'integration';
    }

    if (in_array($featureNumber, $modeFeatures, true)) {
        return 'mode';
    }

    return '';
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

function getFeatureTerms($feature)
{
    $users = max(1, (int) ($feature['USERS'] ?? 1));
    $expirationDate = trim((string) ($feature['EXPIRATION_DATE'] ?? ''));

    return [
        'network' => !empty($feature['NETWORK']) ? 'Network license' : 'Standalone',
        'users' => $users . ' ' . ($users === 1 ? 'user' : 'users'),
        'expires' => $expirationDate !== '' ? 'Expires ' . formatDisplayDate($expirationDate) : '',
    ];
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
$isMaker = isKeyMaker($productKey);

$customer = getCustomer($productKey);

$createdDate = getKeyCreatedDate($productKey);

$activation = $isActivated ? getLastActivation($productKey) : null;
$activationDate = '';
if (!empty($activation['TIMESTAMP'])) {
    $activationDate = (new DateTime('@' . (int) $activation['TIMESTAMP']))
        ->setTimezone(new DateTimeZone('Asia/Jerusalem'))
        ->format('F j, Y');
}

$features = getLicenseFeatures($productKey);

// Terms that are identical on every feature are shown once under the key
// instead of on each feature row.
$termValues = [];
foreach ($features as $feature) {
    foreach (getFeatureTerms($feature) as $term => $value) {
        $termValues[$term][$value] = true;
    }
}
$sharedTerms = [];
foreach ($termValues as $term => $values) {
    if (count($values) === 1) {
        $sharedTerms[$term] = array_key_first($values);
    }
}

$sharedExpiration = isset($sharedTerms['expires'])
    ? parseLicenseDate($features[0]['EXPIRATION_DATE'] ?? '')
    : null;
$isPermanent = $features && ($sharedTerms['expires'] ?? null) === '';
$daysLeft = $sharedExpiration
    ? (int) (new DateTime('today'))->diff($sharedExpiration)->format('%r%a')
    : null;
// Header license type: "Standalone", or "Network · N users".
$licenseType = '';
if (($sharedTerms['network'] ?? '') === 'Standalone') {
    $licenseType = 'Standalone';
} elseif (($sharedTerms['network'] ?? '') === 'Network license') {
    $licenseType = 'Network' . (isset($sharedTerms['users']) ? ' · ' . $sharedTerms['users'] : '');
}

$isMaker = $isMaker || in_array(95, array_map('intval', array_column($features, 'NUMBER')), true);

// The Maker Version feature (95) is shown as the badge next to the title.
// Internal Use License / Trial (40) unlocks every feature; it is shown as a
// banner instead of a list row.
$isAllFeatures = in_array(40, array_map('intval', array_column($features, 'NUMBER')), true);

$listedFeatures = array_values(array_filter($features, function ($feature) use ($isMaker, $isAllFeatures) {
    $number = (int) ($feature['NUMBER'] ?? 0);
    return !($isMaker && $number === 95) && !($isAllFeatures && $number === 40);
}));
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

        .license-info {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px 11px;
            margin-top: 6px;
        }

        .license-type {
            color: #5f4a4f;
            font-size: 14px;
            font-weight: 600;
        }

        .expires {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 11px;
            border: 1px solid #eadfe1;
            border-radius: 999px;
            color: #4a3539;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.3;
            white-space: nowrap;
            background: #faf5f6;
        }

        .expires .days-left {
            font-weight: 700;
        }

        .expires.soon {
            border-color: rgba(180, 110, 0, 0.3);
            color: #9a5b00;
            background: #fff6e5;
        }

        .expires.permanent {
            border-color: rgba(8, 127, 105, 0.25);
            color: #087f69;
            font-weight: 600;
            background: #ecf8f4;
        }

        .expires.expired {
            border-color: rgba(198, 21, 32, 0.22);
            color: #c61520;
            background: #fff1f3;
        }

        .expires svg {
            width: 15px;
            height: 15px;
            flex: 0 0 auto;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .created {
            margin: 18px 0 0;
            color: #5f4a4f;
            font-size: 14px;
            font-weight: 600;
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

        .maker-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 9px;
            border: 1px solid #ddd3f1;
            border-radius: 999px;
            color: #5b3fa8;
            font-size: 12.5px;
            font-weight: 700;
            line-height: 1.5;
            white-space: nowrap;
            background: #f4f0fc;
        }

        .maker-badge svg {
            width: 13px;
            height: 13px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .details {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 14px;
            margin: 22px 0 28px;
        }

        .details:last-child,
        .details:has(+ .created) {
            margin-bottom: 0;
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
            margin: 0;
            font-size: 16px;
        }

        .features-box {
            padding: 16px 18px 12px;
            border: 1px solid #f0e3e5;
            border-radius: 16px;
        }

        .features-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .features-count {
            padding: 3px 10px;
            border-radius: 999px;
            color: #5f4a4f;
            font-size: 12px;
            font-weight: 700;
            background: #f6eeef;
        }

        .all-features {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border: 1px solid rgba(8, 127, 105, 0.2);
            border-radius: 14px;
            background: linear-gradient(135deg, #effaf6 0%, #f7fcfa 100%);
        }

        .all-features + .features {
            margin-top: 12px;
        }

        .all-features svg {
            width: 40px;
            height: 40px;
            flex: 0 0 auto;
            padding: 9px;
            border-radius: 12px;
            fill: none;
            stroke: #087f69;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
            background: rgba(8, 127, 105, 0.1);
        }

        .all-features strong,
        .all-features span {
            display: block;
        }

        .all-features strong {
            color: #065f4f;
            font-size: 15px;
        }

        .all-features span {
            margin-top: 2px;
            color: #4f6b64;
            font-size: 13px;
            line-height: 1.4;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            column-gap: 38px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .feature {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 6px 0;
            font-size: 14px;
            line-height: 1.4;
        }

        .check {
            width: 16px;
            height: 16px;
            flex: 0 0 auto;
            margin-top: 2px;
            fill: none;
            stroke: #16a34a;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .feature-text {
            min-width: 0;
        }

        .feature small {
            display: block;
            color: #806c70;
            font-size: 12px;
        }

        .feature-tag {
            display: inline-block;
            margin-left: 6px;
            padding: 1px 7px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 750;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            vertical-align: 1px;
        }

        .feature.integration .feature-name,
        .feature.mode .feature-name {
            font-weight: 700;
        }

        .integration .feature-tag {
            color: #3d5a80;
            background: #e4ebf4;
        }

        .mode .feature-tag {
            color: #8a5a12;
            background: #f5e8d2;
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

            .features {
                grid-template-columns: minmax(0, 1fr);
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
                    <?php if ($licenseType !== '' || $sharedExpiration || $isPermanent || $isMaker): ?>
                        <div class="license-info">
                            <?php if ($licenseType !== ''): ?>
                                <span class="license-type"><?= htmlspecialchars($licenseType, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($sharedExpiration): ?>
                                <span class="expires<?= $daysLeft < 0 ? ' expired' : ($daysLeft <= 30 ? ' soon' : '') ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/>
                                        <path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>
                                    </svg>
                                    <?= $daysLeft < 0 ? 'Expired' : 'Expires' ?> <?= $sharedExpiration->format('M j, Y') ?> ·
                                    <span class="days-left"><?php
                                        if ($daysLeft === 0) {
                                            echo 'today';
                                        } elseif ($daysLeft > 0) {
                                            echo $daysLeft . ($daysLeft === 1 ? ' day' : ' days') . ' left';
                                        } else {
                                            echo -$daysLeft . ($daysLeft === -1 ? ' day' : ' days') . ' ago';
                                        }
                                    ?></span>
                                </span>
                            <?php elseif ($isPermanent): ?>
                                <span class="expires permanent">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                        <path d="m9 12 2 2 4-4"/>
                                    </svg>
                                    Perpetual license
                                </span>
                            <?php endif; ?>
                            <?php if ($isMaker): ?>
                                <span class="maker-badge">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                    </svg>
                                    SolidCAM Maker
                                </span>
                            <?php endif; ?>
                        </div>
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
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <div>
                        <span>Customer</span>
                        <strong><?= $customer ?></strong>
                    </div>
                </div>
            </div>

            <?php if ($listedFeatures || !$isMaker || $isAllFeatures): ?>
            <div class="features-box">
                <div class="features-header">
                    <h2>Included features</h2>
                    <?php if ($isAllFeatures): ?>
                        <span class="features-count">All features</span>
                    <?php elseif ($listedFeatures): ?>
                        <span class="features-count"><?= count($listedFeatures) ?> <?= count($listedFeatures) === 1 ? 'feature' : 'features' ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($isAllFeatures): ?>
                    <div class="all-features">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <path d="m9 12 2 2 4-4"/>
                        </svg>
                        <div>
                            <strong>All features are unlocked</strong>
                            <span>Internal use / trial license — every SolidCAM module is available on this key.</span>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($listedFeatures): ?>
                    <ul class="features">
                    <?php foreach ($listedFeatures as $feature):
                        $featureNumber = (int) ($feature['NUMBER'] ?? 0);
                        $metadata = [];
                        foreach (getFeatureTerms($feature) as $term => $value) {
                            if (!isset($sharedTerms[$term]) && $value !== '') {
                                $metadata[] = $value;
                            }
                        }
                        $featureName = getFeatureName($featureNumber);
                        $featureGroup = getFeatureGroup($featureNumber);
                        $groupTags = ['integration' => 'CAD', 'mode' => 'Mode'];
                    ?>
                        <li class="feature <?= $featureGroup ?>" title="<?= htmlspecialchars(implode(' · ', array_merge([$featureName, '#' . $featureNumber], $metadata)), ENT_QUOTES, 'UTF-8') ?>">
                            <svg class="check" viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="9.5"/>
                                <path d="m8 12.5 2.7 2.7L16 9.8"/>
                            </svg>
                            <span class="feature-text">
                                <span class="feature-name"><?= htmlspecialchars($featureName, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($featureGroup !== ''): ?>
                                    <span class="feature-tag"><?= $groupTags[$featureGroup] ?></span>
                                <?php endif; ?>
                                <?php if ($metadata): ?>
                                    <small><?= htmlspecialchars(implode(' · ', $metadata), ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php elseif (!$isAllFeatures): ?>
                    <p class="feature">No features were found for this license.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($createdDate !== ''): ?>
                <p class="created">License created: <?= $createdDate ?></p>
            <?php endif; ?>
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
