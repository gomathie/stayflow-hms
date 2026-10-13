<?php
/*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @property CustomerThread $object
 */
class AdminCustomerThreadsControllerCore extends AdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->table = 'customer_thread';
        $this->className = 'CustomerThread';
        $this->lang = false;

        $contact_array = array();
        $contacts = Contact::getContacts($this->context->language->id);

        foreach ($contacts as $contact) {
            $contact_array[$contact['id_contact']] = $contact['name'];
        }

        $language_array = array();
        $languages = Language::getLanguages();
        foreach ($languages as $language) {
            $language_array[$language['id_lang']] = $language['name'];
        }

        $icon_array = array(
            CustomerThread::QLO_CUSTOMER_THREAD_STATUS_OPEN => array(
                'class' => 'icon-circle text-success',
                'alt' => $this->l('Open')
            ),
            CustomerThread::QLO_CUSTOMER_THREAD_STATUS_CLOSED => array(
                'class' => 'icon-circle text-danger',
                'alt' => $this->l('Closed')
            ),
            CustomerThread::QLO_CUSTOMER_THREAD_STATUS_PENDING1 => array(
                'class' => 'icon-circle text-warning',
                'alt' => $this->l('In progress')
            ),
            CustomerThread::QLO_CUSTOMER_THREAD_STATUS_PENDING2 => array(
                'class' => 'icon-circle text-warning',
                'alt' => $this->l('Waiting for guest')
            ),
        );

        $status_array = array();
        foreach ($icon_array as $k => $v) {
            $status_array[$k] = $v['alt'];
        }

        // START send access query information to the admin controller
        $this->access_select = ' SELECT a.`id_customer_thread` FROM '._DB_PREFIX_.'customer_thread a';
        $this->access_join = ' LEFT JOIN '._DB_PREFIX_.'orders ord ON (a.id_order = ord.id_order)';
        $this->access_join .= ' LEFT JOIN '._DB_PREFIX_.'htl_booking_detail hbd ON (hbd.id_order = ord.id_order)';
        if ($acsHtls = HotelBranchInformation::getProfileAccessedHotels($this->context->employee->id_profile, 1, 1)) {
            $this->access_where = ' WHERE IF(a.`id_order`, hbd.`id_hotel` IN ('.implode(',', $acsHtls).'), 1)';
        } else {
            $this->access_where = ' WHERE a.`id_order` = 0';
        }

        $this->fields_list = array(
            'customer' => array(
                'title' => $this->l('From'),
                'filter_key' => 'customer',
                'callback' => 'getCustomerLink',
                'tmpTableFilter' => true,
            ),
            'email' => array(
                'title' => $this->l('Email'),
                'filter_key' => 'a!email',
            ),
            'phone' => array(
                'title' => $this->l('Phone'),
                'filter_key' => 'a!phone',
                'optional' => true,
                'visible_default' => true,
            ),
            'subject' => array(
                'title' => $this->l('Message'),
                'filter_key' => 'a!subject',
                'tmpTableFilter' => true,
                'callback' => 'getMessageLink',
            ),
            'status' => array(
                'title' => $this->l('Status'),
                'type' => 'select',
                'list' => $status_array,
                'icon' => $icon_array,
                'align' => 'center',
                'filter_key' => 'a!status',
                'filter_type' => 'string',
            ),
            'employee' => array(
                'title' => $this->l('Handled by'),
                'filter_key' => 'employee',
                'tmpTableFilter' => true,
                'optional' => true,
                'hint' => $this->l('The first employee to reply becomes responsible. They can then forward the message to another employee if needed.')
            ),
            'date_upd' => array(
                'title' => $this->l('Received'),
                'havingFilter' => true,
                'type' => 'datetime',
                'optional' => true,
            ),
            'id_order' => array(
                'title' => $this->l('ID Order'),
                'filter_key' => 'a!id_order',
                'optional' => false,
                'visible_default' => false,
                'displayed' => false,
            ),
        );

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            ),
        );

        $this->shopLinkType = 'shop';
        $arr = array();
        if ($contacts = Contact::getContacts($this->context->language->id)) {
            foreach ($contacts as $contact) {
                $arr[] = array('email_message' => $contact['id_contact'], 'name' => $contact['name']);
            }
        }

        $this->fields_options = array(
            'contact' => array(
                'title' => $this->l('Contact form settings'),
                'fields' => array(
                    'PS_CUSTOMER_SERVICE_SIGNATURE' => array(
                        'title' => $this->l('Default message'),
                        'hint' => $this->l('Please fill out the message fields that appear by default when you answer a thread on the customer service page.'),
                        'type' => 'textareaLang',
                        'lang' => true
                    ),
                     'PS_MAIL_EMAIL_MESSAGE' => array(
                        'title' => $this->l('Send messages from order page to'),
                        'desc' => $this->l('Where customers send messages from the order page.'),
                        'validation' => 'isUnsignedId',
                        'type' => 'select',
                        'cast' => 'intval',
                        'identifier' => 'email_message',
                        'list' => $arr
                    ),
                    'PS_CUSTOMER_SERVICE_DISPLAY_CONTACT' => array(
                        'title' => $this->l('Select contact on contact page'),
                        'hint' => $this->l('Allow guest to select the contact while sending message from the contact page.'),
                        'type' => 'bool'
                    ),
                    'PS_CUSTOMER_SERVICE_CONTACT' => array(
                        'title' => $this->l('Send messages from contact page to'),
                        'desc' => $this->l('Where customers send messages from the Contact page.'),
                        'validation' => 'isUnsignedId',
                        'type' => 'select',
                        'cast' => 'intval',
                        'identifier' => 'email_message',
                        'list' => $arr
                    ),
                    'PS_CUSTOMER_SERVICE_DISPLAY_NAME' => array(
                        'title' => $this->l('Display name on contact page'),
                        'hint' => $this->l('Display name field on contact page.'),
                        'type' => 'bool',
                    ),
                    'PS_CUSTOMER_SERVICE_REQUIRED_NAME' => array(
                        'title' => $this->l('Set name as required'),
                        'hint' => $this->l('Set name as required while submitting message from the contact page.'),
                        'type' => 'bool'
                    ),
                    'PS_CUSTOMER_SERVICE_DISPLAY_PHONE' => array(
                        'title' => $this->l('Display phone on contact page'),
                        'hint' => $this->l('Display phone field on contact page.'),
                        'type' => 'bool',
                    ),
                    'PS_CUSTOMER_SERVICE_REQUIRED_PHONE' => array(
                        'title' => $this->l('Set phone as required'),
                        'hint' => $this->l('Set phone as required while submitting message from the contact page.'),
                        'type' => 'bool'
                    ),
                    'PS_CUSTOMER_SERVICE_FILE_UPLOAD' => array(
                        'title' => $this->l('Allow file uploading'),
                        'hint' => $this->l('Allow customers to upload files using the contact page.'),
                        'type' => 'bool'
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'name' => 'submitOptionsCustomerService'
                )
            ),
        );

        parent::__construct();
        $this->_new_list_header_design = true;
        $this->list_no_link = true;
    }

    public function getCustomerLink($customer, $tr)
    {
        $customer = Tools::safeOutput(html_entity_decode($customer, ENT_QUOTES, 'UTF-8'));
        if ($tr['id_customer']) {
            return '<a href="'.$this->context->link->getAdminLink('AdminCustomers').'&viewcustomer&id_customer='.(int) $tr['id_customer'].'">'.$customer.'</a>';
        }

        return $customer;
    }

    /** The message title opens the conversation; shown as text, never as a link inside */
    public function getMessageLink($subject, $tr)
    {
        $text = Tools::safeOutput(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
        $url = self::$currentIndex.'&id_customer_thread='.(int) $tr['id_customer_thread'].'&viewcustomer_thread&token='.$this->token;

        return '<a href="'.$url.'"><strong>'.$text.'</strong></a>';
    }

    public function setMedia()
    {
        parent::setMedia();
        if (!$this->display || $this->display == 'list') {
            $this->addJS(_PS_JS_DIR_.'admin/customer_thread.js');
        }
    }

    public function renderList()
    {
        $this->addRowAction('view');
        $this->addRowAction('delete');

        $this->_select = '
			IF(a.`user_name`="", CONCAT(c.`firstname`," ",c.`lastname`), a.`user_name`) AS customer, cl.`name` as contact, l.`name` as language, group_concat(message) as messages,
            cm.private, IF(COUNT(CASE WHEN cm.`private` = 1 THEN 1 END), 1, 0) AS `has_private`,
			(
				SELECT IFNULL(CONCAT(LEFT(e.`firstname`, 1),". ",e.`lastname`), "--")
				FROM `'._DB_PREFIX_.'customer_message` cm2
				INNER JOIN '._DB_PREFIX_.'employee e
					ON e.`id_employee` = cm2.`id_employee`
				WHERE cm2.id_employee > 0
					AND cm2.`id_customer_thread` = a.`id_customer_thread`
				ORDER BY cm2.`date_add` DESC LIMIT 1
			) AS last_employee,
            IFNULL(CONCAT(LEFT(emp.`firstname`, 1),". ",emp.`lastname`), "--") AS employee';

        $this->_join = '
            LEFT JOIN `'._DB_PREFIX_.'employee` emp
                ON emp.`id_employee` = a.`id_employee`
			LEFT JOIN `'._DB_PREFIX_.'customer` c
				ON c.`id_customer` = a.`id_customer`
			LEFT JOIN `'._DB_PREFIX_.'customer_message` cm
				ON cm.`id_customer_thread` = a.`id_customer_thread`
			LEFT JOIN `'._DB_PREFIX_.'lang` l
				ON l.`id_lang` = a.`id_lang`
			LEFT JOIN `'._DB_PREFIX_.'contact_lang` cl
				ON (cl.`id_contact` = a.`id_contact` AND cl.`id_lang` = '.(int)$this->context->language->id.')';

        $this->_group = 'GROUP BY cm.id_customer_thread';
        $this->_orderBy = 'id_customer_thread';
        $this->_orderWay = 'DESC';

        return parent::renderList();
    }

    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    public function printOptinIcon($value, $customer)
    {
        return ($value ? '<i class="icon-check"></i>' : '<i class="icon-remove"></i>');
    }

    public function postProcess()
    {
        if ($this->tabAccess['edit'] === 1) {
            if (Tools::isSubmit('submitOptionsCustomerService')) {
                $this->processUpdateOptions();
                if (!Tools::getValue('PS_CUSTOMER_SERVICE_DISPLAY_NAME')) {
                    Configuration::updateValue('PS_CUSTOMER_SERVICE_REQUIRED_NAME', 0);
                }

                if (!Tools::getValue('PS_CUSTOMER_SERVICE_DISPLAY_PHONE')) {
                    Configuration::updateValue('PS_CUSTOMER_SERVICE_REQUIRED_PHONE', 0);
                }
            }
        } else {
            $this->errors[] = Tools::displayError('You do not have permission to edit this.');
        }

        if ($id_customer_thread = (int)Tools::getValue('id_customer_thread')) {
            if (($id_contact = (int)Tools::getValue('id_contact'))) {
                Db::getInstance()->execute('
					UPDATE '._DB_PREFIX_.'customer_thread
					SET id_contact = '.(int)$id_contact.'
					WHERE id_customer_thread = '.(int)$id_customer_thread
                );
            }
            if ($id_status = (int)Tools::getValue('setstatus')) {
                Db::getInstance()->execute('
					UPDATE '._DB_PREFIX_.'customer_thread
					SET status = "'.(int) $id_status.'"
					WHERE id_customer_thread = '.(int)$id_customer_thread.' LIMIT 1
				');
            }
            $ct = new CustomerThread($id_customer_thread);
            if ($id_employee = (int) Tools::getValue('id_employee_forward')) {
                $messages = Db::getInstance()->getRow('
					SELECT ct.*, cm.*, cl.name subject, CONCAT(e.firstname, \' \', e.lastname) employee_name,
						CONCAT(c.firstname, \' \', c.lastname) customer_name, c.firstname
					FROM '._DB_PREFIX_.'customer_thread ct
					LEFT JOIN '._DB_PREFIX_.'customer_message cm
						ON (ct.id_customer_thread = cm.id_customer_thread)
					LEFT JOIN '._DB_PREFIX_.'contact_lang cl
						ON (cl.id_contact = ct.id_contact AND cl.id_lang = '.(int)$this->context->language->id.')
					LEFT OUTER JOIN '._DB_PREFIX_.'employee e
						ON e.id_employee = cm.id_employee
					LEFT OUTER JOIN '._DB_PREFIX_.'customer c
						ON (c.email = ct.email)
					WHERE ct.id_customer_thread = '.(int)Tools::getValue('id_customer_thread').'
					ORDER BY cm.id_employee, cm.private, cm.date_add DESC
				');
                $output = $this->displayMessage($messages, true, (int)Tools::getValue('id_employee_forward'));
                $cm = new CustomerMessage();
                $cm->id_employee = (int)$this->context->employee->id;
                $cm->id_customer_thread = (int)Tools::getValue('id_customer_thread');
                $cm->ip_address = (int)ip2long(Tools::getRemoteAddr());
                $current_employee = $this->context->employee;
                $id_employee = (int)Tools::getValue('id_employee_forward');
                $employee = new Employee($id_employee);
                $email = Tools::getValue('email');
                $message = Tools::getValue('message_forward');
                if (($error = $cm->validateField('message', $message, null, array(), true)) !== true) {
                    $this->errors[] = $error;
                } elseif ($id_employee && $employee && Validate::isLoadedObject($employee)) {
                    $params = array(
                        '{messages}' => stripslashes($output),
                        '{employee}' => $current_employee->firstname.' '.$current_employee->lastname,
                        '{comment}' => stripslashes(Tools::nl2br($_POST['message_forward'])),
                        '{firstname}' => $employee->firstname,
                        '{lastname}' => $employee->lastname,
                    );

                    if (Mail::Send(
                        $this->context->language->id,
                        'forward_msg',
                        Mail::l('Fwd: Customer message', $this->context->language->id),
                        $params,
                        $employee->email,
                        $employee->firstname.' '.$employee->lastname,
                        $current_employee->email,
                        $current_employee->firstname.' '.$current_employee->lastname,
                        null, null, _PS_MAIL_DIR_, true)
                    ) {
                        $ct->id_employee = $employee->id;
                        $ct->save();
                        $cm->id_employee = (int)$employee->id;
                        $cm->private = 1;
                        $cm->message = $this->l('Message forwarded to').' '.$employee->firstname.' '.$employee->lastname."\n".$this->l('Comment:').' '.$message;
                        $cm->add();
                    }
                } elseif ($email && Validate::isEmail($email)) {
                    $params = array(
                        '{messages}' => Tools::nl2br(stripslashes($output)),
                        '{employee}' => $current_employee->firstname.' '.$current_employee->lastname,
                        '{comment}' => stripslashes($_POST['message_forward']),
                        '{firstname}' => '',
                        '{lastname}' => '',
                    );

                    if (Mail::Send(
                        $this->context->language->id,
                        'forward_msg',
                        Mail::l('Fwd: Customer message', $this->context->language->id),
                        $params, $email, null,
                        $current_employee->email, $current_employee->firstname.' '.$current_employee->lastname,
                        null, null, _PS_MAIL_DIR_, true)) {
                        $cm->message = $this->l('Message forwarded to').' '.$email."\n".$this->l('Comment:').' '.$message;
                        $cm->add();
                    }
                } else {
                    $this->errors[] = '<div class="alert error">'.Tools::displayError('The email address is invalid.').'</div>';
                }
            }
            if (Tools::isSubmit('submitReply') || Tools::isSubmit('submitReplyAndClose')) {
                ShopUrl::cacheMainDomainForShop((int)$ct->id_shop);

                $cm = new CustomerMessage();
                $cm->id_employee = (int)$this->context->employee->id;
                if (!$ct->id_employee) {
                    $ct->id_employee = $this->context->employee->id;
                }
                $cm->id_customer_thread = $ct->id;
                $cm->ip_address = (int)ip2long(Tools::getRemoteAddr());
                $cm->message = Tools::getValue('reply_message');
                if (($error = $cm->validateField('message', $cm->message, null, array(), true)) !== true) {
                    $this->errors[] = $error;
                } elseif (isset($_FILES) && !empty($_FILES['joinFile']['name']) && $_FILES['joinFile']['error'] != 0) {
                    $this->errors[] = Tools::displayError('An error occurred during the file upload process.');
                } elseif ($cm->add()) {
                    $file_attachment = null;
                    if (!empty($_FILES['joinFile']['name'])) {
                        $file_attachment['content'] = file_get_contents($_FILES['joinFile']['tmp_name']);
                        $file_attachment['name'] = $_FILES['joinFile']['name'];
                        $file_attachment['mime'] = $_FILES['joinFile']['type'];
                    }
                    $customer = new Customer($ct->id_customer);

                    //#ct == id_customer_thread    #tc == token of thread   <== used in the synchronization imap
                    $contact = new Contact((int)$ct->id_contact, (int)$ct->id_lang);

                    if (Validate::isLoadedObject($contact)) {
                        $from_name = $contact->name;
                        $from_email = $contact->email;
                    } else {
                        $from_name = null;
                        $from_email = null;
                    }

                    if ($ct->id_order) {
                        $link = Tools::url(
                            $this->context->link->getPageLink('order-detail', true, (int)$ct->id_lang, null, false, $ct->id_shop),
                            'id_order='.$ct->id_order
                        );
                        $objOrder = new Order($ct->id_order);
                        $message = $cm->message;
                        if (Configuration::get('PS_MAIL_TYPE', null, null, $objOrder->id_shop) != Mail::TYPE_TEXT) {
                            $message = Tools::nl2br($cm->message);
                        }

                        $objCustomer = new Customer($objOrder->id_customer);
                        $idShop = $objOrder->id_shop;
                        $params = array(
                            '{lastname}' => $objCustomer->lastname,
                            '{firstname}' => $objCustomer->firstname,
                            '{id_order}' => $objOrder->id,
                            '{order_name}' => $objOrder->getUniqReference(),
                            '{message}' => $message
                        );
                        $toEmail = $objCustomer->email;
                        $toName = $objCustomer->firstname.' '.$objCustomer->lastname;
                        $title = Mail::l('New message regarding your booking', (int)$objOrder->id_lang);
                        $template = 'order_merchant_comment';
                        $idLang = $objOrder->id_lang;
                    } else {
                        $link = Tools::url(
                            $this->context->link->getPageLink('contact', true, (int)$ct->id_lang, null, false, $ct->id_shop),
                            'token='.$ct->token
                        );
                        $headingText = $this->l('Hi').' '.$ct->user_name.',';
                        $idShop = $ct->id_shop;
                        $params = array(
                            '{reply}' => Tools::nl2br(Tools::getValue('reply_message')),
                            '{link}' => $link,
                            '{headingtext}' => $headingText,
                            '{contact_name}' => $contact->name,
                        );
                        if (!empty(trim($ct->subject))) {
                            $params['{subject}'] = $ct->subject;
                        }

                        $idLang = $ct->id_lang;
                        $toEmail = Tools::getValue('msg_email');
                        $toName = null;
                        $template = 'reply_msg';
                        $title = Mail::l('An answer to your message is available', $ct->id_lang);
                        if ($customer->id) {
                            $headingText .= ' '.$customer->firstname.' '.$customer->lastname;
                        }
                        $headingText .= ',';
                    }

                    if (Mail::Send(
                        (int) $idLang,
                        $template,
                        $title,
                        $params,
                        $toEmail,
                        $toName,
                        $from_email,
                        $from_name,
                        $file_attachment,
                        null,
                        _PS_MAIL_DIR_,
                        true,
                        $idShop
                    )) {
                        if (Tools::isSubmit('submitReplyAndClose')) {
                            $ct->status = CustomerThread::QLO_CUSTOMER_THREAD_STATUS_CLOSED;
                        } else {
                            $ct->status = CustomerThread::QLO_CUSTOMER_THREAD_STATUS_OPEN;
                        }

                        $ct->update();
                    }
                    Tools::redirectAdmin(
                        self::$currentIndex.'&id_customer_thread='.(int)$id_customer_thread.'&viewcustomer_thread&token='.Tools::getValue('token')
                    );
                } else {
                    $this->errors[] = Tools::displayError('An error occurred. Your message was not sent. Please contact your system administrator.');
                }
            } else if (Tools::isSubmit('submitReplyAsPrivate')) {
                if (!$ct->id_employee) {
                    $ct->id_employee = $this->context->employee->id;
                    $ct->update();
                }
                ShopUrl::cacheMainDomainForShop((int)$ct->id_shop);
                $cm = new CustomerMessage();
                $cm->id_employee = (int)$this->context->employee->id;
                $cm->id_customer_thread = $ct->id;
                $cm->ip_address = (int)ip2long(Tools::getRemoteAddr());
                $cm->message = Tools::getValue('reply_message');
                $cm->private = 1;
                $cm->read = 1;
                if (($error = $cm->validateField('message', $cm->message, null, array(), true)) !== true) {
                    $this->errors[] = $error;
                } elseif (!$cm->add()) {
                    $this->errors[] = Tools::displayError('Some error occured while saving the message');
                }
            }
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        if (isset($_GET['filename']) && (bool)Tools::file_get_contents($this->context->link->getMediaLink(_THEME_PROD_PIC_DIR_.$_GET['filename'])) && Validate::isFileName($_GET['filename'])) { // by webkul
            AdminCustomerThreadsController::openUploadedFile();
        }

        return parent::initContent();
    }

    protected function openUploadedFile()
    {
        $filename = $_GET['filename'];

        $extensions = array(
            '.txt' => 'text/plain',
            '.rtf' => 'application/rtf',
            '.doc' => 'application/msword',
            '.docx'=> 'application/msword',
            '.pdf' => 'application/pdf',
            '.zip' => 'multipart/x-zip',
            '.png' => 'image/png',
            '.jpeg' => 'image/jpeg',
            '.gif' => 'image/gif',
            '.jpg' => 'image/jpeg',
        );

        $extension = false;
        foreach ($extensions as $key => $val) {
            if (substr(Tools::strtolower($filename), -4) == $key || substr(Tools::strtolower($filename), -5) == $key) {
                $extension = $val;
                break;
            }
        }

        if (!$extension || !Validate::isFileName($filename)) {
            die(Tools::displayError());
        }

        if (ob_get_level() && ob_get_length() > 0) {
            ob_end_clean();
        }
        header('Content-Type: '.$extension);
        header('Content-Disposition:attachment;filename="'.$filename.'"');
        readfile(_PS_UPLOAD_DIR_.$filename);
        die;
    }

    public function renderKpis()
    {
        $time = time();
        $kpis = array();

        $all = CustomerThread::getTotalCustomerThreads();
        $pending = (int)AdminStatsController::getPendingMessages();
        $open = CustomerThread::getTotalCustomerThreads('status='.CustomerThread::QLO_CUSTOMER_THREAD_STATUS_OPEN);

        $helper = new HelperKpi();
        $helper->id = 'box-messages';
        $helper->icon = 'icon-envelope';
        $helper->color = 'color1';
        $helper->title = $this->l('Total threads');
        $helper->tooltip = $this->l('Total number of threads.');
        $helper->value = $all;
        $this->kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-pending-messages';
        $helper->icon = 'icon-envelope';
        $helper->color = 'color2';
        $helper->title = $this->l('Threads pending', null, null, false);
        $helper->value = $pending + $open;
        $helper->tooltip = $this->l('Total number of threads having "open", "pending 1" and "pending 2" statuses.');
        $this->kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-age';
        $helper->icon = 'icon-time';
        $helper->color = 'color3';
        $helper->title = $this->l('Average Response Time', null, null, false);
        $helper->tooltip = $this->l('The average response time.');
        $helper->subtitle = $this->l('30 days', null, null, false);
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=avg_msg_response_time';
        $this->kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-messages-per-thread';
        $helper->icon = 'icon-copy';
        $helper->color = 'color4';
        $helper->title = $this->l('Average Messages per Thread', null, null, false);
        $helper->subtitle = $this->l('30 day', null, null, false);
        $helper->tooltip = $this->l('The average number of messages in each thread.');
        $helper->source = $this->context->link->getAdminLink('AdminStats').'&ajax=1&action=getKpi&kpi=messages_per_thread';
        $this->kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-pending-messages';
        $helper->icon = 'icon-envelope';
        $helper->color = 'color1';
        $helper->title = $this->l('Customer messages', null, null, false);
        $helper->tooltip = $this->l('Total number of messages sent by customers.');
        $helper->value = (int) CustomerMessage::getTotalCustomerMessages('cm.`id_employee` = 0');
        $this->kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-pending-messages';
        $helper->icon = 'icon-envelope';
        $helper->color = 'color4';
        $helper->title = $this->l('Employee messages', null, null, false);
        $helper->tooltip = $this->l('Total number of messages sent by employees.');
        $helper->value = (int) CustomerMessage::getTotalCustomerMessages('cm.`id_employee` != 0');
        $this->kpis[] = $helper;


        $helper = new HelperKpi();
        $helper->id = 'box-pending-messages';
        $helper->icon = 'icon-envelope';
        $helper->color = 'color2';
        $helper->title = $this->l('Unread threads', null, null, false);
        $helper->tooltip = $this->l('Total number of messages having open status.');
        $helper->value = (int) $open;
        $this->kpis[] = $helper;

        $helper = new HelperKpi();
        $helper->id = 'box-pending-messages';
        $helper->icon = 'icon-envelope';
        $helper->color = 'color3';
        $helper->title = $this->l('Closed threads', null, null, false);
        $helper->value = (int) ($all - ($open + $pending));
        $helper->tooltip = $this->l('Total number of messages having closed status.');
        $this->kpis[] = $helper;

        $contacts = CustomerThread::getContacts();
        $categories = Contact::getCategoriesContacts();
        if ($contacts) {
            $contacts = array_column($contacts, null, 'id_contact');
        }

        if ($categories) {
            foreach ($categories as $idContact => $category) {
                $helper = new HelperKpi();
                $helper->id = 'box-pending-messages';
                $helper->icon = 'icon-envelope';
                $helper->color = 'color'.(($idContact+1)%3 ? ($idContact+1)%3 : '4');
                $helper->title = $category['name'];
                if (isset($contacts[$category['id_contact']])) {
                    $helper->href = self::$currentIndex.'&token='.$this->token.'&id_customer_thread='.$contacts[$category['id_contact']]['id_customer_thread'].'&viewcustomer_thread';
                    if ($contacts[$category['id_contact']]['total'] > 1) {
                        $helper->value = $contacts[$category['id_contact']]['total'].' '.$this->l('New messages');
                    } else {
                        $helper->value = $contacts[$category['id_contact']]['total'].' '.$this->l('New message');
                    }
                } else {
                    $helper->value = $this->l('No new messages');
                }

                $helper->tooltip = $this->l('Total number of messages sent to').' '.$category['name'];
                $this->kpis[] = $helper;
            }
        }

        return parent::renderKpis();
    }

    public function renderView()
    {
        if (!$id_customer_thread = (int)Tools::getValue('id_customer_thread')) {
            return;
        }

        if (!($thread = $this->loadObject())) {
            return;
        }
        $this->context->cookie->{'customer_threadFilter_cl!id_contact'} = $thread->id_contact;

        $employees = Employee::getEmployees();

        $messages = CustomerThread::getMessageCustomerThreads($id_customer_thread);

        foreach ($messages as $key => $mess) {
            if ($mess['id_employee']) {
                $employee = new Employee($mess['id_employee']);
                $messages[$key]['employee_image'] = $employee->getImage();
            }
            if (isset($mess['file_name']) && $mess['file_name'] != '') {
                $messages[$key]['file_name'] = _THEME_PROD_PIC_DIR_.$mess['file_name'];
            } else {
                unset($messages[$key]['file_name']);
            }

            if ($mess['id_product']) {
                $product = new Product((int)$mess['id_product'], false, $this->context->language->id);
                if (Validate::isLoadedObject($product)) {
                    $messages[$key]['product_name'] = $product->name;
                    $messages[$key]['booking_product'] = $product->booking_product;
                    if ($product->booking_product) {
                        $messages[$key]['product_link'] = $this->context->link->getAdminLink('AdminProducts').'&updateproduct&id_product='.(int)$product->id;
                    } else {
                        $messages[$key]['product_link'] = $this->context->link->getAdminLink('AdminNormalProducts').'&updateproduct&id_product='.(int)$product->id;
                    }
                }
            }
        }

        $contacts = Contact::getContacts($this->context->language->id);
        $actions = array();

        if ($thread->status != CustomerThread::QLO_CUSTOMER_THREAD_STATUS_CLOSED) {
            $actions[CustomerThread::QLO_CUSTOMER_THREAD_STATUS_CLOSED] = array(
                'href' => self::$currentIndex.'&viewcustomer_thread&setstatus='.CustomerThread::QLO_CUSTOMER_THREAD_STATUS_CLOSED.'&id_customer_thread='.(int)Tools::getValue('id_customer_thread').'&viewmsg&token='.$this->token,
                'label' => $this->l('Mark as "handled"'),
                'name' => 'setstatus',
                'value' => CustomerThread::QLO_CUSTOMER_THREAD_STATUS_CLOSED
            );
        } else {
            $actions[CustomerThread::QLO_CUSTOMER_THREAD_STATUS_OPEN] = array(
                'href' => self::$currentIndex.'&viewcustomer_thread&setstatus='.CustomerThread::QLO_CUSTOMER_THREAD_STATUS_OPEN.'&id_customer_thread='.(int)Tools::getValue('id_customer_thread').'&viewmsg&token='.$this->token,
                'label' => $this->l('Re-open'),
                'name' => 'setstatus',
                'value' => CustomerThread::QLO_CUSTOMER_THREAD_STATUS_OPEN
            );
        }

        if ($thread->status != CustomerThread::QLO_CUSTOMER_THREAD_STATUS_PENDING1) {
            $actions[CustomerThread::QLO_CUSTOMER_THREAD_STATUS_PENDING1] = array(
                'href' => self::$currentIndex.'&viewcustomer_thread&setstatus='.CustomerThread::QLO_CUSTOMER_THREAD_STATUS_PENDING1.'&id_customer_thread='.(int)Tools::getValue('id_customer_thread').'&viewmsg&token='.$this->token,
                'label' => $this->l('Mark as "pending 1" (will be answered later)'),
                'name' => 'setstatus',
                'value' => CustomerThread::QLO_CUSTOMER_THREAD_STATUS_PENDING1
            );
        } else {
            $actions[CustomerThread::QLO_CUSTOMER_THREAD_STATUS_PENDING1] = array(
                'href' => self::$currentIndex.'&viewcustomer_thread&setstatus='.CustomerThread::QLO_CUSTOMER_THREAD_STATUS_OPEN.'&id_customer_thread='.(int)Tools::getValue('id_customer_thread').'&viewmsg&token='.$this->token,
                'label' => $this->l('Disable pending status'),
                'name' => 'setstatus',
                'value' => CustomerThread::QLO_CUSTOMER_THREAD_STATUS_OPEN
            );
        }

        if ($thread->status != CustomerThread::QLO_CUSTOMER_THREAD_STATUS_PENDING2) {
            $actions[CustomerThread::QLO_CUSTOMER_THREAD_STATUS_PENDING2] = array(
                'href' => self::$currentIndex.'&viewcustomer_thread&setstatus='.CustomerThread::QLO_CUSTOMER_THREAD_STATUS_PENDING2.'&id_customer_thread='.(int)Tools::getValue('id_customer_thread').'&viewmsg&token='.$this->token,
                'label' => $this->l('Mark as "pending 2" (will be answered later)'),
                'name' => 'setstatus',
                'value' => CustomerThread::QLO_CUSTOMER_THREAD_STATUS_PENDING2
            );
        } else {
            $actions[CustomerThread::QLO_CUSTOMER_THREAD_STATUS_PENDING2] = array(
                'href' => self::$currentIndex.'&viewcustomer_thread&setstatus='.CustomerThread::QLO_CUSTOMER_THREAD_STATUS_OPEN.'&id_customer_thread='.(int)Tools::getValue('id_customer_thread').'&viewmsg&token='.$this->token,
                'label' => $this->l('Disable pending status'),
                'name' => 'setstatus',
                'value' => CustomerThread::QLO_CUSTOMER_THREAD_STATUS_OPEN
            );
        }

        if ($thread->id_customer) {
            $customer = new Customer($thread->id_customer);
            $orders = Order::getCustomerOrders($customer->id);
            if ($orders && count($orders)) {
                $total_ok = 0;
                $orders_ok = array();
                foreach ($orders as $key => $order) {
                    if ($order['valid']) {
                        $orders_ok[] = $order;
                        $total_ok += $order['total_paid_real']/$order['conversion_rate'];
                    }
                    $orders[$key]['date_add'] = Tools::displayDate($order['date_add']);
                    $orders[$key]['total_paid_real'] = Tools::displayPrice($order['total_paid_real'], new Currency((int)$order['id_currency']));
                }
            }

            $products = $customer->getBoughtProducts();
            if ($products && count($products)) {
                foreach ($products as $key => $product) {
                    $products[$key]['date_add'] = Tools::displayDate($product['date_add'], null, true);
                }
            }
        }
        $timeline_items = $this->getTimeline($messages, $thread->id_order);
        $first_message = $messages[0];

        if (!$messages[0]['id_employee']) {
            unset($messages[0]);
        }

        $contact = '';
        foreach ($contacts as $c) {
            if ($c['id_contact'] == $thread->id_contact) {
                $contact = $c['name'];
            }
        }

        $this->context->smarty->assign('display', 'view');
        $this->tpl_view_vars = array(
            'id_customer_thread' => $id_customer_thread,
            'thread' => $thread,
            'actions' => $actions,
            'employees' => $employees,
            'current_employee' => $this->context->employee,
            'messages' => $messages,
            'first_message' => $first_message,
            'contact' => $contact,
            'orders' => isset($orders) ? $orders : false,
            'customer' => isset($customer) ? $customer : false,
            'products' => isset($products) ? $products : false,
            'total_ok' => isset($total_ok) ?  Tools::displayPrice($total_ok, $this->context->currency) : false,
            'orders_ok' => isset($orders_ok) ? $orders_ok : false,
            'count_ok' => isset($orders_ok) ? count($orders_ok) : false,
            'PS_CUSTOMER_SERVICE_SIGNATURE' => str_replace('\r\n', "\n", Configuration::get('PS_CUSTOMER_SERVICE_SIGNATURE', (int)$thread->id_lang)),
            'timeline_items' => $timeline_items,
        );

        return parent::renderView();
    }

    public function getTimeline($messages, $id_order)
    {
        $timeline = array();
        foreach ($messages as $message) {
            $product = new Product((int)$message['id_product'], false, $this->context->language->id);
            $content = '';
            if (!$message['private']) {
                $customerName = false;
                if (!$message['id_customer']) {
                    $customerName = $message['user_name'];
                } else if ($message['customer_name']) {
                    $customerName = $message['customer_name'];
                }

                if ($message['id_employee'] && $customerName) {
                    $content .= $this->l('Message to: ').' <span class="badge">'.($customerName).'</span><br/><br/>';
                }
            } else {
                $content .= '<span class="label label-info">'.$this->l('Private').'</span><br/><br/>';
            }

            if (Validate::isLoadedObject($product)) {
                if ($product->booking_product) {
                    $content .= $this->l('Room type:');
                } else {
                    $content .= $this->l('Product:');
                }

                $content .= ' <span class="label label-info">'.$product->name.'</span><br/><br/>';
            }
            $content .= Tools::safeOutput($message['message']);

            $timeline[$message['date_add']][] = array(
                'arrow' => 'left',
                'background_color' => '',
                'icon' => 'icon-envelope',
                'content' => $content,
                'date' => $message['date_add'],
            );
        }

        $order = new Order((int)$id_order);
        if (Validate::isLoadedObject($order)) {
            $order_history = $order->getHistory($this->context->language->id);
            foreach ($order_history as $history) {
                $link_order = $this->context->link->getAdminLink('AdminOrders').'&vieworder&id_order='.(int)$order->id;

                $content = '<a class="badge" target="_blank" href="'.Tools::safeOutput($link_order).'">'.$this->l('Order').' #'.(int)$order->id.'</a><br/><br/>';

                $content .= '<span>'.$this->l('Status:').' '.$history['ostate_name'].'</span>';

                $timeline[$history['date_add']][] = array(
                    'arrow' => 'right',
                    'alt' => true,
                    'background_color' => $history['color'],
                    'icon' => 'icon-credit-card',
                    'content' => $content,
                    'date' => $history['date_add'],
                    'see_more_link' => $link_order,
                );
            }
        }
        krsort($timeline);
        return $timeline;
    }

     public function initToolbarTitle()
    {
        parent::initToolbarTitle();
        switch ($this->display) {
            case 'view':
                if (!($thread = $this->loadObject())) {
                    return;
                }

                if ($thread->id) {
                    $this->toolbar_title[] = sprintf($this->l('Title: %1$s'), $thread->subject);
                }
                break;
        }
    }

    protected function displayMessage($message, $email = false, $id_employee = null)
    {
        $tpl = $this->createTemplate('message.tpl');

        $contacts = Contact::getContacts($this->context->language->id);
        foreach ($contacts as $contact) {
            $contact_array[$contact['id_contact']] = array('id_contact' => $contact['id_contact'], 'name' => $contact['name']);
        }
        $contacts = $contact_array;

        if (!$email) {
            if (!empty($message['id_product']) && empty($message['employee_name'])) {
                $id_order_product = Order::getIdOrderProduct((int)$message['id_customer'], (int)$message['id_product']);
            }
        }
        $message['date_add'] = Tools::displayDate($message['date_add'], null, true);
        $message['user_agent'] = strip_tags($message['user_agent']);
        // Links in a customer's message stay plain text: staff must not be one
        // click from a link a stranger sent. The template escapes the text.
        $message['message'] = html_entity_decode($message['message'], ENT_QUOTES, 'UTF-8');

        $is_valid_order_id = true;
        $order = new Order((int)$message['id_order']);

        if (!Validate::isLoadedObject($order)) {
            $is_valid_order_id = false;
        }

        $tpl->assign(array(
            'thread_url' => Tools::getAdminUrl(basename(_PS_ADMIN_DIR_).'/'.
                $this->context->link->getAdminLink('AdminCustomerThreads').'&amp;id_customer_thread='
                .(int)$message['id_customer_thread'].'&amp;viewcustomer_thread=1'),
            'link' => $this->context->link,
            'current' => self::$currentIndex,
            'token' => $this->token,
            'message' => $message,
            'id_order_product' => isset($id_order_product) ? $id_order_product : null,
            'email' => $email,
            'id_employee' => $id_employee,
            'PS_SHOP_NAME' => Configuration::get('PS_SHOP_NAME'),
            'file_name' => (bool)Tools::file_get_contents($this->context->link->getMediaLink(_THEME_PROD_PIC_DIR_.$message['file_name'])), // by webkul
            'contacts' => $contacts,
            'is_valid_order_id' => $is_valid_order_id
        ));

        return $tpl->fetch();
    }

    protected function displayButton($content)
    {
        return '<div><p>'.$content.'</p></div>';
    }

    /**
     * AdminController::getList() override
     * @see AdminController::getList()
     *
     * @param int         $id_lang
     * @param string|null $order_by
     * @param string|null $order_way
     * @param int         $start
     * @param int|null    $limit
     * @param int|bool    $id_lang_shop
     *
     * @throws PrestaShopException
     */
    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);

        $nb_items = count($this->_list);
        for ($i = 0; $i < $nb_items; ++$i) {
            if (isset($this->_list[$i]['messages'])) {
                $this->_list[$i]['messages'] = Tools::htmlentitiesDecodeUTF8($this->_list[$i]['messages']);
            }
        }
    }

    public function ajaxProcessMarkAsRead()
    {
        if ($this->tabAccess['edit'] !== 1) {
            throw new PrestaShopException(Tools::displayError('You do not have permission to edit this.'));
        }

        $id_thread = Tools::getValue('id_thread');
        $messages = CustomerThread::getMessageCustomerThreads($id_thread);
        if (count($messages)) {
            Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'customer_message` set `read` = 1 WHERE `id_employee` = '.(int)$this->context->employee->id.' AND `id_customer_thread` = '.(int)$id_thread);
        }
    }
}
