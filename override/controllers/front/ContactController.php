<?php
/**
 * Contact form: when "Allow file uploading" is switched off in
 * Customers > Customer Service, the stock controller only hides the field
 * on the page and would still store a file sent to it directly. This drops
 * any such file before the stock code runs.
 */
class ContactController extends ContactControllerCore
{
    public function postProcess()
    {
        if (!Configuration::get('PS_CUSTOMER_SERVICE_FILE_UPLOAD')) {
            unset($_FILES['fileUpload']);
        }

        if (Tools::isSubmit('submitMessage')) {
            // Links are refused: staff read these messages and should never be
            // one click from an address a stranger typed. Markup is stripped
            // first so a link cannot hide inside a tag.
            $linkFound = false;
            foreach (array('message', 'user_name', 'phone', 'subject') as $field) {
                if (isset($_POST[$field]) && is_string($_POST[$field])) {
                    $_POST[$field] = strip_tags($_POST[$field]);
                    if (self::containsLink($_POST[$field])) {
                        $linkFound = true;
                    }
                }
            }
            if ($linkFound) {
                $this->errors[] = Tools::displayError('Links cannot be sent through this form. Please remove the web address and send your message again.');

                return;
            }

            // The form has no Title or Send To field: the title is the start of
            // the message, and the message goes to the shop's customer service
            // contact (or the first contact), which staff read under
            // Customers > Customer Service.
            if (!trim((string) Tools::getValue('subject'))) {
                $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) Tools::getValue('message'))));
                $title = Tools::substr($text, 0, 70);
                if (Tools::strlen($text) > 70) {
                    // end on a whole word
                    $title = preg_replace('/\s+\S*$/u', '', $title).'...';
                }
                $_POST['subject'] = $title;
            }
            if (!(int) Configuration::get('PS_CUSTOMER_SERVICE_DISPLAY_CONTACT')
                && !Validate::isLoadedObject(new Contact((int) Configuration::get('PS_CUSTOMER_SERVICE_CONTACT')))
            ) {
                $contacts = Contact::getContacts($this->context->language->id);
                if ($contacts) {
                    Configuration::updateValue('PS_CUSTOMER_SERVICE_CONTACT', (int) $contacts[0]['id_contact']);
                }
            }
        }

        parent::postProcess();
    }

    /**
     * True for anything that looks like a web address: a scheme (http://,
     * ftp://, javascript:), "www.", or a bare name ending in a common domain
     * suffix. Plain email addresses (name@host) are not links.
     */
    protected static function containsLink($text)
    {
        $text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', ' ', $text);

        return (bool) preg_match(
            '~(?:[a-z][a-z0-9+.\-]{1,15}://|\b(?:javascript|data|vbscript):|\bwww\d?\.|\b[a-z0-9][a-z0-9\-]*(?:\.[a-z0-9\-]+)*\.(?:com|net|org|info|biz|io|ru|cn|xyz|top|club|online|site|shop|app|link|click|live|icu|tk|ml|ga|cf|gh|uk|ly|gl)\b)~i',
            $text
        );
    }
}
