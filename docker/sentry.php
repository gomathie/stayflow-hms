<?php
/**
 * Error tracking with Sentry. Loaded before every request by docker/prepend.php.
 *
 * Does nothing unless SENTRY_DSN is set (Coolify Environment Variables), so
 * local stacks and forks send nothing. The SDK lives outside the web root
 * (installed by the Dockerfile). Personal data is not sent (send_default_pii
 * stays false). If the SDK is missing or fails, the request carries on.
 */

(static function () {
    $dsn = getenv('SENTRY_DSN');
    $autoload = '/usr/local/share/salisberg/sentry/vendor/autoload.php';
    if (!$dsn || !is_file($autoload)) {
        return;
    }
    try {
        require_once $autoload;
        \Sentry\init(array(
            'dsn' => $dsn,
            'environment' => getenv('SENTRY_ENVIRONMENT') ?: 'production',
            'release' => getenv('SENTRY_RELEASE') ?: null,
            'send_default_pii' => false,
            // The inherited code raises many notices and deprecations; report real faults only.
            'error_types' => E_ALL & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED,
            'traces_sample_rate' => (float) (getenv('SENTRY_TRACES_SAMPLE_RATE') ?: 0),
        ));
    } catch (\Throwable $e) {
        error_log('[salisberg-sentry] init failed: ' . $e->getMessage());
    }
})();
