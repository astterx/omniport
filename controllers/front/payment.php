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

/**
 * @since 1.5.0
 */
class OmniportPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $minDeposit = $cart->getOrderTotal(true, Cart::BOTH) * Configuration::get('OMNIPORT_MIN_DEPOSIT')/100;
        $maxDeposit = $cart->getOrderTotal(true, Cart::BOTH) * Configuration::get('OMNIPORT_MAX_DEPOSIT')/100;

        // start small hack because Omniport bug
        if ($minDeposit - round($minDeposit, 2) > 0) {
            $minDeposit = round($minDeposit, 2) + 0.01;
        }

        if ($maxDeposit - round($maxDeposit, 2) > 0) {
            $maxDeposit = round($maxDeposit, 2) - 0.01;
        }
        // end hack

        $defaultProduct = OmniportProduct::getProducts(true, 1);
        $defaultInstalment = !empty($defaultProduct[0]['repayment_period'])
            ? round(($cart->getOrderTotal(true, Cart::BOTH) - $minDeposit)/$defaultProduct[0]['repayment_period'], 2)
            : 0;

        $this->context->smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'currencies' => $this->module->getCurrency((int)$cart->id_currency),
            'total' => $cart->getOrderTotal(true, Cart::BOTH),
            'this_path' => $this->module->getPathUri(),
            'this_path_bw' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
            'action_url' => Configuration::get('OMNIPORT_LIVE')
                ? Configuration::get('OMNIPORT_PROD_URL') : Configuration::get('OMNIPORT_TEST_URL'),
            'api_key' => Configuration::get('OMNIPORT_API_KEY'),
            'installation_id' => Configuration::get('OMNIPORT_INST_ID'),
            'min_deposit' => $minDeposit,
            'max_deposit' => $maxDeposit,
            'min_deposit_percent' => Configuration::get('OMNIPORT_MIN_DEPOSIT'),
            'max_deposit_percent' => Configuration::get('OMNIPORT_MAX_DEPOSIT'),
            'default_instalment' => $defaultInstalment,
            'default_repayment' => !empty($defaultProduct[0]['repayment_period'])
                ? $defaultProduct[0]['repayment_period'] : 0,
            'finance_products' => OmniportProduct::getProducts(true),
            'unique_ref_code' => uniqid()
        ));

        $this->setTemplate('payment_execution.tpl');
    }
}
