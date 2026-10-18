<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
final class Extensions
{
    private array $hooks = [];
    public function discover(string $type): array
    {
        $items = [];
        foreach (glob(FRIENDS_ROOT . '/' . $type . '/*/manifest.json') as $path) {
            $manifest = json_decode(file_get_contents($path), true);
            $id = basename(dirname($path));
            if (is_array($manifest) && preg_match('/^[a-z][a-z0-9-]{0,39}$/D', $id) && ($manifest['api'] ?? 0) === 1) $items[$id] = $manifest;
        }
        return $items;
    }
    public function on(string $event, callable $listener): void { $this->hooks[$event][] = $listener; }
    public function emit(string $event, mixed ...$arguments): void { foreach ($this->hooks[$event] ?? [] as $listener) $listener(...$arguments); }
    public function boot(App $app): void
    {
        foreach ($this->discover('plugins') as $id => $manifest) {
            if ($app->setting('plugin.' . $id, '0') === '1' && is_file($file = FRIENDS_ROOT . '/plugins/' . $id . '/plugin.php')) (require $file)($this, $app);
        }
    }
}
