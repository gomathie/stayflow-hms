<?php
/**
 * Request limiter for the forms attackers hammer: sign-in (guest and back
 * office), password reset, account creation, contact and newsletter.
 *
 * Loaded before every web request through php.ini (auto_prepend_file), so the
 * application itself is not modified. Counts POSTs per visitor address in small
 * files under the system temp folder. If anything about the limiter fails, the
 * request is allowed: it must never be the reason the site is down.
 *
 * Limits are deliberately loose enough for a front desk sharing one internet
 * connection. Adjust them in $rules below.
 */

if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    return;
}

(static function () {
    // rule name => [max requests, window in seconds]
    $rules = array(
        'bo-login' => array(10, 600),
        'bo-forgot' => array(5, 3600),
        'login' => array(10, 600),
        'register' => array(10, 3600),
        'password' => array(5, 3600),
        'contact' => array(6, 3600),
        'newsletter' => array(10, 3600),
    );

    $in = static function ($key) {
        return isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : null);
    };
    $controller = strtolower((string) $in('controller'));

    $rule = null;
    if ($controller === 'adminlogin' && $in('submitLogin') !== null) {
        $rule = 'bo-login';
    } elseif ($controller === 'adminlogin' && $in('submitForgot') !== null) {
        $rule = 'bo-forgot';
    } elseif ($in('SubmitLogin') !== null) {
        $rule = 'login';
    } elseif ($in('SubmitCreate') !== null || $in('submitAccount') !== null || $in('submitGuestAccount') !== null) {
        $rule = 'register';
    } elseif ($controller === 'password' && $in('email') !== null) {
        $rule = 'password';
    } elseif ($controller === 'contact' && $in('submitMessage') !== null) {
        $rule = 'contact';
    } elseif ($in('submitNewsletter') !== null) {
        $rule = 'newsletter';
    }
    if ($rule === null) {
        return;
    }

    list($max, $window) = $rules[$rule];
    // Apache's mod_remoteip has already replaced the proxy's address with the visitor's
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    if ($ip === '') {
        return;
    }

    $dir = sys_get_temp_dir().'/sb-ratelimit';
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return;
    }
    $file = $dir.'/'.$rule.'-'.hash('sha256', $ip);
    $handle = @fopen($file, 'c+');
    if (!$handle) {
        return;
    }

    $now = time();
    $blocked = false;
    $retry = $window;
    if (flock($handle, LOCK_EX)) {
        $hits = array();
        foreach (explode("\n", (string) stream_get_contents($handle)) as $line) {
            if ((int) $line > $now - $window) {
                $hits[] = (int) $line;
            }
        }
        if (count($hits) >= $max) {
            $blocked = true;
            $retry = max(1, min($hits) + $window - $now);
        } else {
            $hits[] = $now;
        }
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, implode("\n", $hits));
        flock($handle, LOCK_UN);
    }
    fclose($handle);

    // Occasionally clear out files nobody has touched for a day
    if (mt_rand(1, 200) === 1) {
        foreach ((array) glob($dir.'/*') as $old) {
            if (@filemtime($old) < $now - 86400) {
                @unlink($old);
            }
        }
    }

    if (!$blocked) {
        return;
    }

    $minutes = (int) ceil($retry / 60);
    $message = 'Too many attempts. Please wait '.$minutes.' minute'.($minutes === 1 ? '' : 's').' and try again.';
    error_log('[salisberg-ratelimit] blocked '.$rule.' from '.$ip);
    header('Retry-After: '.$retry);
    header('Cache-Control: no-store');
    if ($in('ajax') !== null) {
        // The back office sign-in form only shows a message that arrives as a
        // normal (200) JSON reply in this shape; any other status makes it
        // print "TECHNICAL ERROR" instead.
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('hasErrors' => true, 'errors' => array($message)));
    } else {
        http_response_code(429);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            .'<title>Please wait</title></head><body style="font-family:system-ui,sans-serif;max-width:32rem;margin:15vh auto;padding:0 1rem;color:#12352c">'
            .'<h1 style="font-size:1.4rem">Please wait a moment</h1><p>'.htmlspecialchars($message, ENT_QUOTES, 'UTF-8').'</p>'
            .'<p><a href="javascript:history.back()" style="color:#8a6a22">Go back</a></p></body></html>';
    }
    exit;
})();
