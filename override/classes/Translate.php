<?php
/**
 * Shows "Salisberg" wherever the vendor's product name appears in the text of
 * the back office, modules and PDFs.
 *
 * Every piece of text on those screens passes through one of the functions
 * below, so the name is replaced here once instead of in each of the files
 * that mention it. Only finished text is touched: file names, class names,
 * JavaScript events, the qlo_ table prefix and web addresses such as
 * qloapps.com never pass through here and are left as they are (AGENTS.md,
 * rule 12).
 *
 * To undo, delete this file and redeploy.
 */
class Translate extends TranslateCore
{
    const BRAND = 'Salisberg';

    public static function getAdminTranslation($string, $class = 'AdminTab', $addslashes = false, $htmlentities = true, $sprintf = null)
    {
        return self::rebrand(parent::getAdminTranslation($string, $class, $addslashes, $htmlentities, $sprintf));
    }

    public static function getModuleTranslation($module, $string, $source, $sprintf = null, $addslashes = false, $language = null)
    {
        return self::rebrand(parent::getModuleTranslation($module, $string, $source, $sprintf, $addslashes, $language));
    }

    public static function getPdfTranslation($string, $sprintf = null, $language = null)
    {
        return self::rebrand(parent::getPdfTranslation($string, $sprintf, $language));
    }

    /**
     * Replaces the product name as a word. A name followed by a slash, or by
     * a dot and a letter, is part of a web address and is kept.
     */
    protected static function rebrand($text)
    {
        if (!is_string($text) || stripos($text, 'qloapps') === false) {
            return $text;
        }

        return preg_replace('/\bQlo[Aa]pps\b(?!\.[A-Za-z]|\/)/', self::BRAND, $text);
    }
}
