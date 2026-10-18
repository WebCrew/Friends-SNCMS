<?php
/** Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
final class Upgrade
{
    public static function apply(Db $db): void
    {
        $version = (string) $db->value("SELECT value FROM f_settings WHERE name='schema_version'");
        if ($version === '3') return;
        if ($version !== '2') throw new \DomainException('Only schema 2 can be upgraded. Restore a backup for older installations.');
        // No destructive DDL. MySQL DDL commits implicitly; statements are intentionally repeatable.
        $db->executeSchema(FRIENDS_ROOT . '/database/features.sql');
        $db->query("UPDATE f_settings SET value='3' WHERE name='schema_version'");
    }
}
