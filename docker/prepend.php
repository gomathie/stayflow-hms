<?php
/**
 * Loaded before every PHP request (auto_prepend_file in docker/php.ini).
 * php.ini allows one prepend file, so this one loads each piece in order.
 */
require '/usr/local/share/salisberg/sentry.php';
require '/usr/local/share/salisberg/ratelimit.php';
