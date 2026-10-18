<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
require_once __DIR__ . '/app/RootHosting.php';
Friends\RootHosting::requireProtection();
define('FRIENDS_ASSET_PREFIX', 'public/assets/');
require __DIR__ . '/public/index.php';
