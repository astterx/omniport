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

include_once dirname(__FILE__) . '/../../classes/OmniportFinance.php';
include_once dirname(__FILE__) . '/../../classes/OmniportSettings.php';
include_once dirname(__FILE__) . '/../../classes/OmniportRequest.php';
include_once dirname(__FILE__) . '/../../classes/OmniportNotification.php';

/**
 * @since 1.5.0
 */
class OmniportValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0
            || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0
            || !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer
        // changed his address just before the end of the checkout process
        $authorized = false;

        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == OmniportSettings::MODULE_NAME) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;

        $minimumDeposit = OmniportRequest::getAmountByCartId($cart->id);
        $message = 'Minimum deposit value: ' . $minimumDeposit;

        $status = OmniportFinance::calculateOrderStatus($cart->id);

        $deposit = OmniportNotification::getDepositValue($cart->id);

        $this->module->validateOrder(
            $cart->id,
            Configuration::get($status),
            $deposit,
            $this->module->displayName,
            $message,
            array(),
            (int)$currency->id,
            false,
            $customer->secure_key
        );
        Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.
            '&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
    }
}
