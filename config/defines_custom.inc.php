<?php
/**
 * Salisberg settings that the platform reads before its own defaults.
 *
 * config.inc.php loads this file, when it exists, ahead of defines.inc.php,
 * and every default there is wrapped in "if (!defined(...))". Setting a value
 * here therefore changes it without editing a vendor file (AGENTS.md, rule 11).
 */

/**
 * jQuery: 1.12.4, the last release of the 1.x line, in place of 1.11.0 (2014).
 * The file is js/jquery/jquery-1.12.4.min.js. To go back, delete this line.
 */
define('_PS_JQUERY_VERSION_', '1.12.4');
