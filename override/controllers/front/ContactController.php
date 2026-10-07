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

        parent::postProcess();
    }
}
