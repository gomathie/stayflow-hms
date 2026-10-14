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

class AdminInvoicesControllerCore extends AdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'invoice';

        parent::__construct();

        $this->fields_options = array(
            'general' => array(
                'title' =>    $this->l('Invoice options'),
                'fields' =>    array(
                    'PS_INVOICE' => array(
                        'title' => $this->l('Enable invoices'),
                        'desc' => $this->l('If enabled, your customers will receive an invoice for their purchase(s).'),
                        'cast' => 'intval',
                        'type' => 'bool'
                    ),
                    'PS_INVOICE_TAXES_BREAKDOWN' => array(
                        'title' => $this->l('Enable tax breakdown'),
                        'desc' => $this->l('Show a summary of tax rates when there are several taxes.'),
                        'cast' => 'intval',
                        'type' => 'bool'
                    ),
                    'PS_PDF_IMG_INVOICE' => array(
                        'title' => $this->l('Enable room type image'),
                        'hint' => $this->l('Adds an image before product name on the invoice'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'type' => 'bool'
                    ),
                    'PS_INVOICE_PREFIX' => array(
                        'title' => $this->l('Invoice prefix'),
                        'desc' => $this->l('Prefix used for invoice name (e.g. #IN00001).'),
                        'size' => 6,
                        'type' => 'textLang'
                    ),
                    'PS_INVOICE_USE_YEAR' => array(
                        'title' => $this->l('Add current year to invoice number'),
                        'cast' => 'intval',
                        'type' => 'bool'
                    ),
                    'PS_INVOICE_RESET' => array(
                        'title' => $this->l('Reset Invoice progressive number at beginning of the year'),
                        'cast' => 'intval',
                        'type' => 'bool'
                    ),
                    'PS_INVOICE_YEAR_POS' => array(
                        'title' => $this->l('Position of the year number'),
                        'cast' => 'intval',
                        'show' => true,
                        'required' => false,
                        'type' => 'radio',
                        'validation' => 'isBool',
                        'choices' => array(
                            0 => $this->l('After the progressive number'),
                            1 => $this->l('Before the progressive number')
                        )
                    ),
                    'PS_INVOICE_START_NUMBER' => array(
                        'title' => $this->l('Invoice number'),
                        'desc' => sprintf($this->l('The next invoice will begin with this number, and then increase with each additional invoice. Set to 0 if you want to keep the current number (which is #%s).'), Order::getLastInvoiceNumber() + 1),
                        'size' => 6,
                        'type' => 'text',
                        'cast' => 'intval'
                    ),
                    'PS_INVOICE_LEGAL_FREE_TEXT' => array(
                        'title' => $this->l('Legal free text'),
                        'desc' => $this->l('Use this field to display additional text on your invoice, like specific legal information. It will appear below the payment methods summary.'),
                        'size' => 50,
                        'type' => 'textareaLang',
                    ),
                    'PS_INVOICE_FREE_TEXT' => array(
                        'title' => $this->l('Footer text'),
                        'desc' => $this->l('This text will appear at the bottom of the invoice, below your company details.'),
                        'size' => 50,
                        'type' => 'textLang',
                    ),
                    'PS_INVOICE_MODEL' => array(
                        'title' => $this->l('Invoice model'),
                        'desc' => $this->l('Choose an invoice model.'),
                        'type' => 'select',
                        'identifier' => 'value',
                        'list' => $this->getInvoicesModels()
                    ),
                    'PS_PDF_USE_CACHE' => array(
                        'title' => $this->l('Use the disk as cache for PDF invoices'),
                        'desc' => $this->l('Saves memory but slows down the PDF generation.'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'type' => 'bool'
                    )
                ),
                'submit' => array('title' => $this->l('Save'))
            )
        );
    }

    public function initFormByDate()
    {
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Print every invoice between two dates'),
                'icon' => 'icon-calendar'
            ),
            'input' => array(
                array(
                    'type' => 'date',
                    'label' => $this->l('From'),
                    'name' => 'date_from',
                    'maxlength' => 10,
                    'required' => true,
                    'hint' => $this->l('Format: 2011-12-31 (inclusive).')
                ),
                array(
                    'type' => 'date',
                    'label' => $this->l('To'),
                    'name' => 'date_to',
                    'maxlength' => 10,
                    'required' => true,
                    'hint' => $this->l('Format: 2012-12-31 (inclusive).')
                )
            ),
            'submit' => array(
                'title' => $this->l('Generate PDF file by date'),
                'id' => 'submitPrint',
                'icon' => 'process-icon-download-alt'
            )
        );

        $this->fields_value = array(
            'date_from' => date('Y-m-d'),
            'date_to' => date('Y-m-d')
        );

        $this->table = 'invoice_date';
        $this->show_toolbar = false;
        $this->show_form_cancel_button = false;
        $this->toolbar_title = $this->l('Print PDF invoices');
        return parent::renderForm();
    }

    public function initFormByStatus()
    {
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Print every invoice of bookings in a status'),
                'icon' => 'icon-time'
            ),
            'input' => array(
                array(
                    'type' => 'checkboxStatuses',
                    'label' => $this->l('Order statuses'),
                    'name' => 'id_order_state',
                    'values' => array(
                        'query' => OrderState::getOrderStates($this->context->language->id),
                        'id' => 'id_order_state',
                        'name' => 'name'
                    ),
                    'hint' => $this->l('You can also export orders which have not been charged yet.')
                )
            ),
            'submit' => array(
                'title' => $this->l('Generate PDF file by status'),
                'id' => 'submitPrint2',
                'icon' => 'process-icon-download-alt'
            )
        );

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT COUNT(DISTINCT(o.id_order)) AS nbOrders, o.current_state as id_order_state
			FROM `'._DB_PREFIX_.'order_invoice` oi
			LEFT JOIN `'._DB_PREFIX_.'orders` o ON oi.id_order = o.id_order
            INNER JOIN `'._DB_PREFIX_.'htl_booking_detail` hbd ON (oi.id_order = hbd.id_order)
			WHERE o.id_shop IN('.implode(', ', Shop::getContextListShopID()).')
            '.HotelBranchInformation::addHotelRestriction(false, 'hbd').'
			AND oi.number > 0
			GROUP BY o.current_state
		 ');

        $status_stats = array();
        foreach ($result as $row) {
            $status_stats[$row['id_order_state']] = $row['nbOrders'];
        }

        $this->tpl_form_vars = array(
            'statusStats' => $status_stats,
            'style' => ''
        );

        $this->table = 'invoice_status';
        $this->show_toolbar = false;
        return parent::renderForm();
    }

    public function initContent()
    {
        $this->display = 'edit';
        $this->initTabModuleList();
        $this->initToolbar();
        $this->initPageHeaderToolbar();
        $this->content .= $this->renderInvoiceList();
        $this->content .= $this->initFormByDate();
        $this->content .= $this->initFormByStatus();
        $this->table = 'invoice';
        // numbering, prefix and layout settings are for the developer team
        if ($this->context->employee->isSuperAdmin()) {
            $this->content .= $this->renderOptions();
        }

        $this->context->smarty->assign(array(
            'content' => $this->content,
            'url_post' => self::$currentIndex.'&token='.$this->token,
            'show_page_header_toolbar' => $this->show_page_header_toolbar,
            'page_header_toolbar_title' => $this->page_header_toolbar_title,
            'page_header_toolbar_btn' => $this->page_header_toolbar_btn
        ));
    }

    /** How many invoices the list shows at most */
    const LIST_LIMIT = 200;

    /** The choices on the list's form, cleaned */
    protected function getListFilters()
    {
        $status = (string) Tools::getValue('sb_status', 'all');
        $from = (string) Tools::getValue('sb_from');
        $to = (string) Tools::getValue('sb_to');
        $isDay = function ($day) {
            return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) && Validate::isDate($day);
        };

        return array(
            'q' => Tools::substr(trim((string) Tools::getValue('sb_q')), 0, 64),
            'status' => in_array($status, array('all', 'unpaid', 'part', 'paid'), true) ? $status : 'all',
            'from' => $isDay($from) ? $from : '',
            'to' => $isDay($to) ? $to : '',
        );
    }

    /**
     * The invoices themselves, newest first: number, booking, guest, stay,
     * total, paid, what is still owed, and the PDF.
     */
    public function renderInvoiceList()
    {
        $filters = $this->getListFilters();
        $where = '';
        if ($filters['q'] !== '') {
            // % and _ are wildcards in LIKE: taken out, the rest is escaped
            $like = pSQL(str_replace(array('%', '_'), '', $filters['q']));
            $digits = (int) preg_replace('/\D+/', '', $filters['q']);
            $where .= ' AND (o.`reference` LIKE \'%'.$like.'%\'
                OR CONCAT(c.`firstname`, \' \', c.`lastname`) LIKE \'%'.$like.'%\'
                OR c.`phone` LIKE \'%'.$like.'%\''
                .($digits ? ' OR oi.`number` = '.$digits : '').')';
        }
        if ($filters['from'] !== '') {
            $where .= ' AND oi.`date_add` >= \''.pSQL($filters['from']).' 00:00:00\'';
        }
        if ($filters['to'] !== '') {
            $where .= ' AND oi.`date_add` <= \''.pSQL($filters['to']).' 23:59:59\'';
        }
        $owed = '(o.`total_paid_tax_incl` - o.`total_paid_real`)';
        if ($filters['status'] == 'paid') {
            $where .= ' AND '.$owed.' <= 0.009';
        } elseif ($filters['status'] == 'part') {
            $where .= ' AND '.$owed.' > 0.009 AND o.`total_paid_real` > 0.009';
        } elseif ($filters['status'] == 'unpaid') {
            $where .= ' AND '.$owed.' > 0.009 AND o.`total_paid_real` <= 0.009';
        }

        $rows = Db::getInstance()->executeS(
            'SELECT oi.`id_order_invoice`, oi.`date_add`, o.`id_order`, o.`reference`, o.`id_currency`, o.`id_lang`, o.`id_shop`,
                o.`total_paid_tax_incl`, o.`total_paid_real`,
                CONCAT(c.`firstname`, \' \', c.`lastname`) AS `guest`, c.`phone`,
                MIN(hbd.`date_from`) AS `stay_from`, MAX(hbd.`date_to`) AS `stay_to`
            FROM `'._DB_PREFIX_.'order_invoice` oi
            INNER JOIN `'._DB_PREFIX_.'orders` o ON o.`id_order` = oi.`id_order`
            INNER JOIN `'._DB_PREFIX_.'htl_booking_detail` hbd ON hbd.`id_order` = oi.`id_order`
            LEFT JOIN `'._DB_PREFIX_.'customer` c ON c.`id_customer` = o.`id_customer`
            WHERE o.`id_shop` IN ('.implode(', ', array_map('intval', Shop::getContextListShopID())).')
                '.HotelBranchInformation::addHotelRestriction(false, 'hbd').'
                AND oi.`number` > 0'.$where.'
            GROUP BY oi.`id_order_invoice`
            ORDER BY oi.`date_add` DESC, oi.`number` DESC
            LIMIT '.((int) self::LIST_LIMIT + 1)
        );
        $rows = (array) $rows;
        $limited = count($rows) > self::LIST_LIMIT;
        if ($limited) {
            array_pop($rows);
        }

        $idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $sum = array('total' => 0.0, 'paid' => 0.0, 'balance' => 0.0);
        $invoices = array();
        foreach ($rows as $row) {
            $total = (float) $row['total_paid_tax_incl'];
            $paid = (float) $row['total_paid_real'];
            $balance = max(0, Tools::ps_round($total - $paid, 2));
            $sum['total'] += $total;
            $sum['paid'] += $paid;
            $sum['balance'] += $balance;
            $invoice = new OrderInvoice((int) $row['id_order_invoice']);
            $invoices[] = array(
                'id_order_invoice' => (int) $row['id_order_invoice'],
                'id_order' => (int) $row['id_order'],
                'number' => $invoice->getInvoiceNumberFormatted((int) $row['id_lang'], (int) $row['id_shop']),
                'date' => HTMLTemplateInvoice::dateInWords($row['date_add']),
                'reference' => $row['reference'],
                'guest' => $row['guest'],
                'phone' => $row['phone'],
                'stay' => HTMLTemplateInvoice::dateInWords($row['stay_from']).' - '.HTMLTemplateInvoice::dateInWords($row['stay_to']),
                'total' => Tools::displayPrice($total, (int) $row['id_currency']),
                'paid' => Tools::displayPrice($paid, (int) $row['id_currency']),
                'balance' => Tools::displayPrice($balance, (int) $row['id_currency']),
                'status' => ($balance <= 0.009) ? 'paid' : (($paid > 0.009) ? 'part' : 'unpaid'),
            );
        }

        $this->context->smarty->assign(array(
            'sb_invoices' => $invoices,
            'sb_filters' => $filters,
            'sb_statuses' => array(
                'all' => $this->l('All'),
                'unpaid' => $this->l('Not yet paid'),
                'part' => $this->l('Partly paid'),
                'paid' => $this->l('Paid'),
            ),
            'sb_totals' => array(
                'count' => count($invoices),
                'total' => Tools::displayPrice($sum['total'], $idCurrency),
                'paid' => Tools::displayPrice($sum['paid'], $idCurrency),
                'balance' => Tools::displayPrice($sum['balance'], $idCurrency),
            ),
            'sb_limited' => $limited,
            'sb_limit' => (int) self::LIST_LIMIT,
            'sb_token' => $this->token,
            'sb_self' => self::$currentIndex.'&token='.$this->token,
            'sb_booking_link' => $this->context->link->getAdminLink('AdminOrders').'&vieworder&id_order=',
            'sb_pdf_link' => $this->context->link->getAdminLink('AdminPdf').'&submitAction=generateInvoicePDF&id_order_invoice=',
        ));

        return $this->context->smarty->fetch(_PS_ADMIN_DIR_.'/themes/'.$this->bo_theme.'/template/controllers/invoices/list.tpl');
    }

    public function initToolbarTitle()
    {
        $this->toolbar_title = array_unique($this->breadcrumbs);
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        unset($this->page_header_toolbar_btn['cancel']);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAddinvoice_date')) {
            if (!Validate::isDate(Tools::getValue('date_from'))) {
                $this->errors[] = $this->l('Invalid "From" date');
            }

            if (!Validate::isDate(Tools::getValue('date_to'))) {
                $this->errors[] = $this->l('Invalid "To" date');
            }

            if (!count($this->errors)) {
                if (count(OrderInvoice::getByDateInterval(Tools::getValue('date_from'), Tools::getValue('date_to')))) {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminPdf').'&submitAction=generateInvoicesPDF&date_from='.urlencode(Tools::getValue('date_from')).'&date_to='.urlencode(Tools::getValue('date_to')));
                }

                $this->errors[] = $this->l('No invoice has been found for this period.');
            }
        } elseif (Tools::isSubmit('submitAddinvoice_status')) {
            if (!is_array($status_array = Tools::getValue('id_order_state')) || !count($status_array)) {
                $this->errors[] = $this->l('You must select at least one order status.');
            } else {
                foreach ($status_array as $id_order_state) {
                    if (count(OrderInvoice::getByStatus((int)$id_order_state))) {
                        Tools::redirectAdmin($this->context->link->getAdminLink('AdminPdf').'&submitAction=generateInvoicesPDF2&id_order_state='.implode('-', $status_array));
                    }
                }

                $this->errors[] = $this->l('No invoice has been found for this status.');
            }
        } else {
            parent::postProcess();
        }
    }

    public function beforeUpdateOptions()
    {
        if ((int)Tools::getValue('PS_INVOICE_START_NUMBER') != 0 && (int)Tools::getValue('PS_INVOICE_START_NUMBER') <= Order::getLastInvoiceNumber()) {
            $this->errors[] = $this->l('Invalid invoice number.').Order::getLastInvoiceNumber().')';
        }
    }

    protected function getInvoicesModels()
    {
        $models = array(
            array(
                'value' => 'invoice',
                'name' => 'invoice'
            )
        );

        $templates_override = $this->getInvoicesModelsFromDir(_PS_THEME_DIR_.'pdf/');
        $templates_default = $this->getInvoicesModelsFromDir(_PS_PDF_DIR_);

        foreach (array_merge($templates_default, $templates_override) as $template) {
            $template_name = basename($template, '.tpl');
            $models[] = array('value' => $template_name, 'name' => $template_name);
        }
        return $models;
    }

    protected function getInvoicesModelsFromDir($directory)
    {
        $templates = false;

        if (is_dir($directory)) {
            $templates = glob($directory.'invoice-*.tpl');
        }

        if (!$templates) {
            $templates = array();
        }

        return $templates;
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addJS(_PS_JS_DIR_.'admin/invoices.js');
    }

}
