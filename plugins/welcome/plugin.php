<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
use Friends\{App,Extensions};
use function Friends\e;
return static function (Extensions $hooks, App $app): void {
    $hooks->on('feed.before', static function () use ($app): void {
        echo '<section class="welcome"><span class="eyebrow">FRIENDS COMMUNITY</span><h1>' . e($app->t('welcome')) . '</h1><p>' . e($app->t('welcome_text')) . '</p><span class="welcome-orbit" aria-hidden="true">✳</span></section>';
    });
};
