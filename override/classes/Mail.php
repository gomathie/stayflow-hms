<?php
/**
 * One mailer for the whole application: every email goes through
 * Mail::Send() with the settings under Advanced Parameters > E-mail.
 *
 * A guest recorded at the desk without an email address is given a
 * placeholder ending in Customer::NO_EMAIL_DOMAIN (see
 * AdminCustomersController::processAdd). Nothing is sent to those: the
 * address does not exist, and a send would only fail or bounce.
 */
class Mail extends MailCore
{
    public static function Send(
        $id_lang,
        $template,
        $subject,
        $template_vars,
        $to,
        $to_name = null,
        $from = null,
        $from_name = null,
        $file_attachment = null,
        $mode_smtp = null,
        $template_path = _PS_MAIL_DIR_,
        $die = false,
        $id_shop = null,
        $bcc = null,
        $reply_to = null,
        $reply_to_name = ''
    ) {
        if (is_string($to) && Customer::hasNoEmail($to)) {
            return true;
        }

        return parent::Send(
            $id_lang,
            $template,
            $subject,
            $template_vars,
            $to,
            $to_name,
            $from,
            $from_name,
            $file_attachment,
            $mode_smtp,
            $template_path,
            $die,
            $id_shop,
            $bcc,
            $reply_to,
            $reply_to_name
        );
    }
}
