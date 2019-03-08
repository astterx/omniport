<?php
/**
 * NOTICE OF LICENSE
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS O
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * @author    Sandu Velea <veleassandu@gmail.com>
 * @copyright Sandu Velea
 * @license See above
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once dirname(__FILE__) . '/classes/OmniportProduct.php';
include_once dirname(__FILE__) . '/classes/OmniportSettings.php';
include_once dirname(__FILE__) . '/classes/OmniportRequest.php';

class Omniport extends PaymentModule
{
    /** @var string */
    protected $html = '';
    /** @var array */
    protected $postErrors = array();
    /** @var int */
    public $installationID;
    /** @var string */
    public $apiKey;
    /** @var string */
    public $omniportUrl;
    /** @var bool */
    public $maintenanceMode = false;

    /**
     * Omniport constructor.
     */
    public function __construct()
    {
        $this->name = 'omniport';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.7';
        $this->author = 'Sandu Velea';
        $this->is_eu_compatible = true;

        $config = Configuration::getMultiple(
            array(
                'OMNIPORT_INST_ID',
                'OMNIPORT_API_KEY',
                'OMNIPORT_PROD_URL',
                'OMNIPORT_TEST_URL',
                'OMNIPORT_LIVE',
                'OMNIPORT_MAINTENANCE_ONLY',
                'OMNIPORT_FOOTER_LEGAL_INFO',
            )
        );

        if (!empty($config['OMNIPORT_INST_ID'])) {
            $this->installationID = $config['OMNIPORT_INST_ID'];
        }

        if (!empty($config['OMNIPORT_API_KEY'])) {
            $this->apiKey = $config['OMNIPORT_API_KEY'];
        }

        if (isset($config['OMNIPORT_LIVE'])
            && !empty($config['OMNIPORT_PROD_URL'])
            && !empty($config['OMNIPORT_TEST_URL'])) {
            $this->omniportUrl = $config['OMNIPORT_LIVE'] ? $config['OMNIPORT_PROD_URL'] : $config['OMNIPORT_TEST_URL'];
        }

        if (!empty($config['OMNIPORT_MAINTENANCE_ONLY'])) {
            $this->maintenanceMode = (bool)$config['OMNIPORT_MAINTENANCE_ONLY'];
        }

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Omniport finance');
        $this->description = $this->l('Accept payments for your products via Omniport credit.');
        $this->confirmUninstall = $this->l('Are you sure about removing this module?');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        if (!isset($this->installationID) || !isset($this->apiKey)) {
            $this->warning = $this->l(
                'Omniport Installation ID and API Key must be configured before using this module.'
            );
        }

        $this->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $this->module_key = 'e2749bace5be69148fb4f577aaba7c55';
        $this->author_address = '0x11c2e82bB172981DC15C88e7B45eDc70C239F32c';
    }

    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('header')
            || !$this->registerHook('payment')
            || !$this->registerHook('paymentOptions')
            || !$this->registerHook('displayPaymentEU')
            || !$this->registerHook('displayFooter')
        ) {
            return false;
        }

        $this->createOrderStatuses();
        $this->createEmailTemplate();

        include(dirname(__FILE__) . '/sql/install.php');

        return true;
    }

    public function uninstall()
    {
        if (!Configuration::deleteByName('OMNIPORT_INST_ID')
            || !Configuration::deleteByName('OMNIPORT_API_KEY')
            || !Configuration::deleteByName('OMNIPORT_PROD_URL')
            || !Configuration::deleteByName('OMNIPORT_TEST_URL')
            || !Configuration::deleteByName('OMNIPORT_LIVE')
            || !Configuration::deleteByName('OMNIPORT_MIN_DEPOSIT')
            || !Configuration::deleteByName('OMNIPORT_MAX_DEPOSIT')
            || !Configuration::deleteByName('OMNIPORT_MIN_ORDER')
            || !Configuration::deleteByName('OMNIPORT_MAINTENANCE_ONLY')
            || !Configuration::deleteByName('OMNIPORT_FOOTER_LEGAL_INFO')
            || !Configuration::deleteByName('OMNIPORT_DEPOSIT_EMAIL_TEMPLATE')
            || !parent::uninstall()) {
            return false;
        }

        include(dirname(__FILE__) . '/sql/uninstall.php');

        return true;
    }

    private function createOrderStatuses()
    {
        foreach (OmniportSettings::getWorldPayOrderStatuses() as $configName => $state) {
            if (!Configuration::get($configName)) {
                $orderState = new OrderState();
                $orderState->name = array();
                foreach (Language::getLanguages() as $language) {
                    $orderState->name[$language['id_lang']] = $state['name'];
                }

                $orderState->module_name = OmniportSettings::MODULE_NAME;
                $orderState->invoice = $state['invoice'];
                $orderState->send_mail = $state['send_mail'];
                $orderState->color = $state['color'];
                $orderState->hidden = $state['hidden'];
                $orderState->logable = $state['logable'];
                $orderState->delivery = $state['delivery'];
                $orderState->shipped = $state['shipped'];
                $orderState->paid = $state['paid'];
                $orderState->deleted = $state['deleted'];

                if ($orderState->add()) {
                    $source = $this->getLocalPath() . '/views/img/omniport.gif';
                    $destination = $this->getLocalPath() . '/../../img/os/' . (int)$orderState->id . '.gif';

                    copy($source, $destination);
                }

                Configuration::updateValue($configName, (int)$orderState->id);
            }
        }
    }

    protected function postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('OMNIPORT_INST_ID')) {
                $this->postErrors[] = $this->l('Installation ID required.');
            }

            if (!Tools::getValue('OMNIPORT_API_KEY')) {
                $this->postErrors[] = $this->l('API Key is required.');
            }

            if (!Tools::getValue('OMNIPORT_PROD_URL')) {
                $this->postErrors[] = $this->l('Omniport URL for production usage is required.');
            }

            if (!Tools::getValue('OMNIPORT_TEST_URL')) {
                $this->postErrors[] = $this->l('Omniport URL for test usage is required.');
            }

            if (!Tools::getValue('OMNIPORT_MIN_DEPOSIT')) {
                $this->postErrors[] = $this->l('Omniport Minimum deposit is required.');
            }

            if (!Tools::getValue('OMNIPORT_MAX_DEPOSIT')) {
                $this->postErrors[] = $this->l('Omniport Maximum deposit is required.');
            }

            if (!Tools::getValue('OMNIPORT_MIN_ORDER')) {
                $this->postErrors[] = $this->l('Omniport Minimum order amount is required.');
            }
        }
    }

    protected function postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('OMNIPORT_INST_ID', Tools::getValue('OMNIPORT_INST_ID'));
            Configuration::updateValue('OMNIPORT_API_KEY', Tools::getValue('OMNIPORT_API_KEY'));
            Configuration::updateValue('OMNIPORT_TEST_URL', Tools::getValue('OMNIPORT_TEST_URL'));
            Configuration::updateValue('OMNIPORT_PROD_URL', Tools::getValue('OMNIPORT_PROD_URL'));
            Configuration::updateValue('OMNIPORT_LIVE', Tools::getValue('OMNIPORT_LIVE'));
            Configuration::updateValue('OMNIPORT_MIN_DEPOSIT', Tools::getValue('OMNIPORT_MIN_DEPOSIT'));
            Configuration::updateValue('OMNIPORT_MAX_DEPOSIT', Tools::getValue('OMNIPORT_MAX_DEPOSIT'));
            Configuration::updateValue('OMNIPORT_MIN_ORDER', Tools::getValue('OMNIPORT_MIN_ORDER'));
            Configuration::updateValue('OMNIPORT_MAINTENANCE_ONLY', Tools::getValue('OMNIPORT_MAINTENANCE_ONLY'));
            Configuration::updateValue('OMNIPORT_FOOTER_LEGAL_INFO', Tools::getValue('OMNIPORT_FOOTER_LEGAL_INFO'));
            $emailContent = nl2br(Tools::getValue('OMNIPORT_DEPOSIT_EMAIL_TEMPLATE'));
            $this->createEmailTemplate($emailContent);
        }

        $this->html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    /**
     * @return mixed
     */
    protected function displayOmniport()
    {
        $this->smarty->assign(
            array(
                'notif_url' =>
                    Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' .
                    $this->name . '/notification.php',
                'accept_url'
                => $this->context->link->getModuleLink($this->name, 'validation', array(), true),
                'decline_url'
                => $this->context->link->getModuleLink($this->name, 'declined', array(), true),
            )
        );

        return $this->display(__FILE__, 'infos.tpl');
    }

    /**
     * @return string
     */
    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->postValidation();
            if (!count($this->postErrors)) {
                $this->postProcess();
            } else {
                foreach ($this->postErrors as $err) {
                    $this->html .= $this->displayError($err);
                }
            }
        } else {
            $this->html .= '<br />';
        }

        $this->html .= $this->displayOmniport();

        if (Tools::isSubmit('addOmniportProduct') || Tools::isSubmit('updateomniport')) {
            return $this->renderOmniportProductForm();
        }

        if (Tools::isSubmit('saveOmniportProduct')) {
            $this->processSaveOmniportProduct();
        }

        $this->html .= $this->renderForm();
        $this->html .= $this->renderList();

        return $this->html;
    }

    /**
     * @return bool
     */
    protected function processSaveOmniportProduct()
    {
        $omniportProduct = new OmniportProduct();
        $idOmniportProduct = (int)Tools::getValue('id_omniport_product');
        if ($idOmniportProduct) {
            $omniportProduct = new OmniportProduct(($idOmniportProduct));
        }

        $omniportProduct->code = Tools::getValue('code');
        $omniportProduct->label = Tools::getValue('label');
        $omniportProduct->repayment_period = Tools::getValue('repayment_period');
        $omniportProduct->is_default = Tools::getValue('is_default');
        $omniportProduct->active = Tools::getValue('active');

        if ($omniportProduct->validateFields()) {
            if ($omniportProduct->is_default) {
                OmniportProduct::resetDefaultProduct();
            }

            $omniportProduct->save();

            $this->html .= $this->displayConfirmation($this->l('Omniport product saved!'));

            $this->_clearCache('*');

            return true;
        }

        $this->html .= $this->displayError($this->l('An error occurred while attempting to save Omniport product!'));

        return false;
    }

    public function hookHeader()
    {
        if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'order') {
            $this->context->controller->addCSS(($this->_path) . 'views/css/omniport.css', 'all');
            $this->context->controller->addJS($this->_path . 'views/js/omniport.js');
        }

        $this->context->smarty->assign(
            array(
                'minimum_product_value' => Configuration::get('OMNIPORT_MIN_ORDER'),
                'maximum_deposit'       => Configuration::get('OMNIPORT_MAX_DEPOSIT'),
            )
        );
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function hookDisplayFooter($params)
    {
        if (Configuration::get('OMNIPORT_FOOTER_LEGAL_INFO')) {
            $this->context->smarty->assign(
                array(
                    'company' => Configuration::get('BLOCKCONTACTINFOS_COMPANY'),
                )
            );

            return $this->display(__FILE__, 'footer.tpl');
        }
    }

    /**
     * @param $params
     *
     * @return array
     */
    public function hookDisplayPaymentEU($params)
    {

        if (!$this->active) {
            return array();
        }

        if ($params['cart']->getOrderTotal() <= Configuration::get('OMNIPORT_MIN_ORDER')) {
            return array();
        }

        if ($this->maintenanceMode
            && !in_array(Tools::getRemoteAddr(), explode(',', Configuration::get('PS_MAINTENANCE_IP')))
        ) {
            return array();
        }

        $payment_options = array(
            'cta_text' => $this->l('Omniport credit'),
            'logo'     => Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/omniport.png'),
            'action'   => $this->context->link->getModuleLink($this->name, 'validation', array(), true),
        );

        return $payment_options;
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return array();
        }

        if (!$this->checkCurrency($params['cart'])) {
            return array();
        }

        if ($params['cart']->getOrderTotal() <= Configuration::get('OMNIPORT_MIN_ORDER')) {
            return array();
        }

        if ($this->maintenanceMode
            && !in_array(Tools::getRemoteAddr(), explode(',', Configuration::get('PS_MAINTENANCE_IP')))) {
            return array();
        }

        $this->context->smarty->assign(
            $this->getTemplateVarInfos()
        );
        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->trans('Omniport credit', array(), 'Modules.Omniport.Shop'))
            ->setAction($this->omniportUrl)
            ->setForm($this->getPaymentForm());
        $payment_options = array(
            $newOption
        );

        return $payment_options;
    }

    /**
     * @return string
     */
    private function getPaymentForm()
    {
        $this->context->smarty->assign($this->getTemplateVars());

        return Context::getContext()->smarty->fetch(dirname(__FILE__) . '/views/templates/front/payment_execution.tpl');
    }

    /**
     * @return array
     */
    private function getTemplateVars()
    {
        $cart = Context::getContext()->cart;
        $customer_default_group_id = (int)$this->context->customer->id_default_group;
        $customer_default_group = new Group($customer_default_group_id);

        if ((bool)Configuration::get('PS_TAX') === true
            && $this->context->country->display_tax_label
            && !(Validate::isLoadedObject($customer_default_group)
                && (bool)$customer_default_group->price_display_method === true)
        ) {
            $taxIncluded = true;
        } else {
            $taxIncluded = false;
        }

        $minDeposit =
            round($cart->getOrderTotal(true, Cart::BOTH) * Configuration::get('OMNIPORT_MIN_DEPOSIT') / 100, 2);
        $defaultProduct = OmniportProduct::getProducts(true, 1);
        $defaultInstalment = !empty($defaultProduct[0]['repayment_period'])
            ? round(($cart->getOrderTotal(true, Cart::BOTH) - $minDeposit) / $defaultProduct[0]['repayment_period'], 2)
            : 0;

        $maxDeposit =
            round($cart->getOrderTotal(true, Cart::BOTH) * Configuration::get('OMNIPORT_MAX_DEPOSIT') / 100, 2);
        $minDepositPercent = Configuration::get('OMNIPORT_MIN_DEPOSIT');
        $maxDepositPercent = Configuration::get('OMNIPORT_MAX_DEPOSIT');
        $defaultRepayment = !empty($defaultProduct[0]['repayment_period']) ? $defaultProduct[0]['repayment_period'] : 0;
        $financeProducts = OmniportProduct::getProducts(true);

        $cartTotal = $cart->getOrderTotal(true, Cart::BOTH);

        return array(
            'omniport_url' => $this->omniportUrl,
            'min_deposit' => number_format($minDeposit, 2, '.', ','),
            'max_deposit' => number_format($maxDeposit, 2, '.', ','),
            'min_deposit_p' => $minDepositPercent,
            'max_deposit_p' => $maxDepositPercent,
            'tax_incl' => $taxIncluded,
            'finance_products' => $financeProducts,
            'cart_total' => $cartTotal,
            'default_installment' => number_format($defaultInstalment, 2, '.', ','),
            'default_repayment' => $defaultRepayment,
            'cart_id' => $cart->id,
            'api_key' => $this->apiKey,
            'installation_id' => $this->installationID,
            'unique_reference' => uniqid(),
            'total' => number_format($cartTotal, 2, '.', ',')
        );
    }

    /**
     * @return mixed
     */
    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Omniport settings'),
                    'icon'  => 'icon-cogs',
                ),
                'input'  => array(
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('Installation ID'),
                        'name'     => 'OMNIPORT_INST_ID',
                        'required' => true,
                    ),
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('API Key'),
                        'name'     => 'OMNIPORT_API_KEY',
                        'required' => true,
                    ),
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('Production URL'),
                        'name'     => 'OMNIPORT_PROD_URL',
                        'required' => true,
                    ),
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('Test URL'),
                        'name'     => 'OMNIPORT_TEST_URL',
                        'required' => true,
                    ),
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('Minimum deposit (%)'),
                        'name'     => 'OMNIPORT_MIN_DEPOSIT',
                        'required' => true,
                    ),
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('Maximum deposit (%)'),
                        'name'     => 'OMNIPORT_MAX_DEPOSIT',
                        'required' => true,
                    ),
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('Minimum order amount'),
                        'name'     => 'OMNIPORT_MIN_ORDER',
                        'required' => true,
                    ),
                    array(
                        'type'         => 'textarea',
                        'label'        => $this->l('Deposit email content'),
                        'name'         => 'OMNIPORT_DEPOSIT_EMAIL_TEMPLATE',
                        'autoload_rte' => true,
                        'cols'         => 60,
                        'rows'         => 10,
                        'hint'         => $this->l('You can customize your emails content by using the following tags:')
                            . ' {shop_url}, {shop_name}, {firstname}, {lastname}, {deposit_amount}',
                    ),
                    array(
                        'type'   => 'switch',
                        'label'  => $this->l('Show footer legal info'),
                        'name'   => 'OMNIPORT_FOOTER_LEGAL_INFO',
                        'values' => array(
                            array(
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                        ),
                    ),
                    array(
                        'type'   => 'switch',
                        'label'  => $this->l('Production usage'),
                        'name'   => 'OMNIPORT_LIVE',
                        'values' => array(
                            array(
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                        ),
                    ),
                    array(
                        'type'   => 'switch',
                        'label'  => $this->l('Maintenance mode'),
                        'name'   => 'OMNIPORT_MAINTENANCE_ONLY',
                        'desc'   => $this->l('Show payment method only to IP\'s from Maintenance Mode'),
                        'values' => array(
                            array(
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name
            . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }

    /**
     * @return mixed
     */
    public function renderOmniportProductForm()
    {
        $idOmniportProduct = (int)Tools::getValue('id_omniport_product');
        $legendTitle = $this->l('Add new Omniport product');
        if ($idOmniportProduct) {
            $legendTitle = $this->l('Edit Omniport product');
        }

        $omniportProduct = new OmniportProduct($idOmniportProduct);

        $fieldsForm = array(
            'form' => array(
                'legend'  => array(
                    'title' => $legendTitle,
                    'icon'  => 'icon-cogs',
                ),
                'input'   => array(
                    array(
                        'type' => 'hidden',
                        'name' => 'id_omniport_product',
                    ),
                    array(
                        'type'     => 'text',
                        'name'     => 'code',
                        'label'    => $this->l('Code'),
                        'required' => true,
                    ),
                    array(
                        'type'     => 'text',
                        'name'     => 'label',
                        'label'    => $this->l('Product label'),
                        'required' => true,
                    ),
                    array(
                        'type'     => 'text',
                        'name'     => 'repayment_period',
                        'label'    => $this->l('Repayment term'),
                        'required' => true,
                    ),
                    array(
                        'type'   => 'switch',
                        'name'   => 'is_default',
                        'label'  => $this->l('Default'),
                        'values' => array(
                            array(
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                        ),
                    ),
                    array(
                        'type'   => 'switch',
                        'name'   => 'active',
                        'label'  => $this->l('Active'),
                        'values' => array(
                            array(
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                        ),
                    ),
                ),
                'submit'  => array(
                    'title' => $this->l('Save'),
                ),
                'buttons' => array(
                    array(
                        'href'  => $this->currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                        'title' => $this->l('Back to list'),
                        'icon'  => 'process-icon-back',
                    ),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'saveOmniportProduct';
        $helper->currentIndex = $this->currentIndex;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $fieldsValues = array(
            'id_omniport_product' => $idOmniportProduct,
            'code'                => $omniportProduct->code,
            'label'               => $omniportProduct->label,
            'repayment_period'    => $omniportProduct->repayment_period,
            'is_default'          => $omniportProduct->is_default,
            'active'              => $omniportProduct->active,
        );

        $helper->tpl_vars = array(
            'fields_value' => $fieldsValues,
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        );

        $form = $helper->generateForm(array($fieldsForm));

        Context::getContext()->smarty->assign('token', Tools::getAdminTokenLite('AdminModules'));

        return $form;
    }

    /**
     * @return mixed
     */
    public function renderList()
    {
        $omniportProducts = OmniportProduct::getProducts();

        $fields = array(
            'code'             => array(
                'title'   => $this->l('Code'),
                'orderby' => false,
            ),
            'label'            => array(
                'title'   => $this->l('Product label'),
                'orderby' => false,
            ),
            'repayment_period' => array(
                'title'   => $this->l('Repayment term'),
                'orderby' => false,
            ),
            'is_default'       => array(
                'title'   => $this->l('Default'),
                'active'  => 'status',
                'type'    => 'bool',
                'class'   => 'fixed-width-xs',
                'align'   => 'center',
                'ajax'    => true,
                'orderby' => false,
                'search'  => false,
            ),
            'active'           => array(
                'title'   => $this->l('Active'),
                'active'  => 'status',
                'type'    => 'bool',
                'class'   => 'fixed-width-xs',
                'align'   => 'center',
                'ajax'    => true,
                'orderby' => false,
                'search'  => false,
            ),
        );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->toolbar_btn['new'] = array(
            'href' => $this->currentIndex . '&addOmniportProduct&token=' . Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Add New'),
        );
        $helper->simple_header = false;
        $helper->listTotal = count($omniportProducts);
        $helper->identifier = 'id_omniport_product';
        $helper->table = $this->name;
        $helper->actions = array('edit', 'delete');
        $helper->show_toolbar = true;
        $helper->module = $this;

        $helper->title = $this->l('Omniport finance products');
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->currentIndex;
        $helper->positon_identifier = '';
        $helper->position_group_identifier = 0;

        return $helper->generateList($omniportProducts, $fields);
    }

    /**
     * @return array
     */
    public function getConfigFieldsValues()
    {
        return array(
            'OMNIPORT_INST_ID'                =>
                Tools::getValue('OMNIPORT_INST_ID', Configuration::get('OMNIPORT_INST_ID')),
            'OMNIPORT_API_KEY'                =>
                Tools::getValue('OMNIPORT_API_KEY', Configuration::get('OMNIPORT_API_KEY')),
            'OMNIPORT_PROD_URL'               =>
                Tools::getValue('OMNIPORT_PROD_URL', Configuration::get('OMNIPORT_PROD_URL')),
            'OMNIPORT_TEST_URL'               =>
                Tools::getValue('OMNIPORT_TEST_URL', Configuration::get('OMNIPORT_TEST_URL')),
            'OMNIPORT_LIVE'                   =>
                Tools::getValue('OMNIPORT_LIVE', Configuration::get('OMNIPORT_LIVE')),
            'OMNIPORT_MIN_DEPOSIT'            =>
                Tools::getValue('OMNIPORT_MIN_DEPOSIT', Configuration::get('OMNIPORT_MIN_DEPOSIT')),
            'OMNIPORT_MAX_DEPOSIT'            =>
                Tools::getValue('OMNIPORT_MAX_DEPOSIT', Configuration::get('OMNIPORT_MAX_DEPOSIT')),
            'OMNIPORT_MIN_ORDER'              =>
                Tools::getValue('OMNIPORT_MIN_ORDER', Configuration::get('OMNIPORT_MIN_ORDER')),
            'OMNIPORT_MAINTENANCE_ONLY'       =>
                Tools::getValue('OMNIPORT_MAINTENANCE_ONLY', Configuration::get('OMNIPORT_MAINTENANCE_ONLY')),
            'OMNIPORT_FOOTER_LEGAL_INFO'      =>
                Tools::getValue('OMNIPORT_FOOTER_LEGAL_INFO', Configuration::get('OMNIPORT_FOOTER_LEGAL_INFO')),
            'OMNIPORT_DEPOSIT_EMAIL_TEMPLATE' =>
                Tools::getValue(
                    'OMNIPORT_DEPOSIT_EMAIL_TEMPLATE',
                    Configuration::get('OMNIPORT_DEPOSIT_EMAIL_TEMPLATE')
                ),
        );
    }

    /**
     * @param int       $id_cart
     * @param int       $id_order_state
     * @param float     $amount_paid
     * @param string    $payment_method
     * @param null      $message
     * @param array     $transaction
     * @param null      $currency_special
     * @param bool      $dont_touch_amount
     * @param bool      $secure_key
     * @param Shop|null $shop
     *
     * @return bool
     */
    public function validateOrder(
        $id_cart,
        $id_order_state,
        $amount_paid,
        $payment_method = 'Unknown',
        $message = null,
        $transaction = array(),
        $currency_special = null,
        $dont_touch_amount = false,
        $secure_key = false,
        Shop $shop = null
    ) {
        if ($this->active) {
            $context = Context::getContext();
            $shop = $shop != null ? $shop : new Shop((int)$context->shop->id);
            $result = parent::validateOrder(
                (int)$id_cart,
                (int)$id_order_state,
                (float)$amount_paid,
                $payment_method,
                $message,
                $transaction,
                $currency_special,
                $dont_touch_amount,
                $secure_key,
                $shop
            );
            if ($result === true) {
                //send mail to customer to remeber it has to pay a deposit
                $params = array(
                    '{firstname}'             => $context->customer->firstname,
                    '{lastname}'              => $context->customer->lastname,
                    '{deposit_amount}'        => OmniportRequest::getAmountByCartId($id_cart),
                    '{deposit_email_content}' => nl2br(Configuration::get('OMNIPORT_DEPOSIT_EMAIL_TEMPLATE')),
                );

                if (in_array(
                    $id_order_state,
                    array(
                        Configuration::get(OmniportSettings::PS_OS_CREDIT_PENDING),
                        Configuration::get(OmniportSettings::PS_OS_CREDIT_APROVED_DEPOSIT_PENDING),
                    )
                )) {
                    Mail::Send(
                        (int)$context->language->id,
                        'deposit',
                        $this->trans('Finance approved, deposit payment required'),
                        $params,
                        $context->customer->email,
                        $context->customer->firstname . ' ' . $context->customer->lastname,
                        null,
                        null,
                        null,
                        null,
                        dirname(__FILE__) . '/mails/'
                    );
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getTemplateVarInfos()
    {
        $cart = $this->context->cart;
        $minDeposit = round($cart->getOrderTotal(true, Cart::BOTH) * Configuration::get('OMNIPORT_MIN_DEPOSIT')/100, 2);
        $maxDeposit = round($cart->getOrderTotal(true, Cart::BOTH) * Configuration::get('OMNIPORT_MAX_DEPOSIT')/100, 2);
        $defaultProduct = OmniportProduct::getProducts(true, 1);
        $defaultInstalment = !empty($defaultProduct[0]['repayment_period'])
            ? round(($cart->getOrderTotal(true, Cart::BOTH) - $minDeposit) / $defaultProduct[0]['repayment_period'], 2)
            : 0;

        return array(
            'nbProducts'          => $cart->nbProducts(),
            'cust_currency'       => $cart->id_currency,
            'total'               => $cart->getOrderTotal(true, Cart::BOTH),
            'action_url'          => Configuration::get('OMNIPORT_LIVE')
                ? Configuration::get('OMNIPORT_PROD_URL')
                : Configuration::get('OMNIPORT_TEST_URL'),
            'api_key'             => Configuration::get('OMNIPORT_API_KEY'),
            'installation_id'     => Configuration::get('OMNIPORT_INST_ID'),
            'min_deposit'         => $minDeposit,
            'max_deposit'         => $maxDeposit,
            'min_deposit_percent' => Configuration::get('OMNIPORT_MIN_DEPOSIT'),
            'max_deposit_percent' => Configuration::get('OMNIPORT_MAX_DEPOSIT'),
            'default_instalment'  => $defaultInstalment,
            'default_repayment'   => !empty($defaultProduct[0]['repayment_period'])
                ? $defaultProduct[0]['repayment_period'] : 0,
            'finance_products'    => OmniportProduct::getProducts(true),
        );
    }

    /**
     * @param $cart
     *
     * @return bool
     */
    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    private function createEmailTemplate($emailContent = null)
    {
        if (null == $emailContent && file_exists(dirname(__FILE__) . '/mails/email_demo.html')) {
            $emailContent = Tools::file_get_contents(dirname(__FILE__) . '/mails/email_demo.html');
        }

        Configuration::updateValue('OMNIPORT_DEPOSIT_EMAIL_TEMPLATE', $emailContent, true);

        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $iso = Language::getIsoById($lang->id);
        Omniport::initDirectory('mails/' . $iso);
        // update html email template
        $fp = fopen('../modules/' . OmniportSettings::MODULE_NAME . '/mails/' . $iso . '/deposit.html', 'w+');
        fwrite($fp, $emailContent);
        fclose($fp);
        // update text email template
        $fp = fopen('../modules/' . OmniportSettings::MODULE_NAME . '/mails/' . $iso . '/deposit.txt', 'w+');
        fwrite($fp, strip_tags($emailContent));
        fclose($fp);
    }

    /**
     * @param        $path
     * @param string $right
     */
    public static function initRight($path, $right = '0777')
    {
        $prems = Tools::substr(sprintf('%o', fileperms(dirname(__FILE__) . '/' . $path)), -4);
        if ($prems != $right) {
            @chmod(dirname(__FILE__) . '/' . $path, octdec((int)$right));
        }
    }

    /**
     * @param        $path
     * @param string $right
     */
    public static function initDirectory($path, $right = '0777')
    {
        if (!is_dir(dirname(__FILE__) . '/' . $path)) {
            mkdir(dirname(__FILE__) . '/' . $path);
            $fp = fopen(dirname(__FILE__) . '/' . $path . '/index.php', 'w+');
            fwrite($fp, "<?php die();");
            fclose($fp);
        }

        self::initRight($path, $right);
    }
}
