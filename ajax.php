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

include_once('../../config/config.inc.php');
include_once('../../init.php');
include_once('classes/OmniportRequest.php');

$context = Context::getContext();

if ($context->customer->isLogged()) {
    $cartId = (int)$context->cart->id;
    $depositAmount = (float)Tools::getValue('depositAmount');
    $productCode = pSQL(Tools::getValue('productCode'));
    $uniqueReference = pSQL(Tools::getValue('uniqueReference'));

    if ($depositAmount > 0 && $cartId) {
        $now = new DateTime();
        $omniportRequest = OmniportRequest::getInstance($uniqueReference);
        $omniportRequest->id_cart = $cartId;
        $omniportRequest->request_finance_deposit = $depositAmount;
        $omniportRequest->product_code = $productCode;
        $omniportRequest->created = $now->format('Y-m-d H:i:s');

        if ($omniportRequest->validateFields()) {
            $omniportRequest->save();

            die(Tools::jsonEncode(array('message' => 'success')));
        }
    }

    die(Tools::jsonEncode(array('message' => 'fail')));
}

die('Yes, it works!');
