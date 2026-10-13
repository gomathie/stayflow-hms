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
            // The form has no Title or Send To field: the title is the start of
            // the message, and the message goes to the shop's customer service
            // contact (or the first contact), which staff read under
            // Customers > Customer Service.
            if (!trim((string) Tools::getValue('subject'))) {
                $_POST['subject'] = Tools::substr(
                    trim(preg_replace('/\s+/', ' ', strip_tags((string) Tools::getValue('message')))),
                    0,
                    60
                );
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
}
