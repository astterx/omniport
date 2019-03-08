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

include_once dirname(__FILE__) . '/../../config/config.inc.php';
include_once dirname(__FILE__) . '/../../init.php';
include_once dirname(__FILE__) . '/classes/OmniportRequest.php';
include_once dirname(__FILE__) . '/classes/OmniportSettings.php';
include_once dirname(__FILE__) . '/classes/OmniportNotification.php';

$data = Tools::getAllValues();

if (!empty($data) && !empty($data['api_key']) && !empty($data['InstallationID'])) {
    $apiKey = Configuration::get('OMNIPORT_API_KEY');
    $installationId = Configuration::get('OMNIPORT_INST_ID');
    if ($apiKey == $apiKey && $installationId == $data['InstallationID']) {
        if (!empty($data['Identification']['RetailerUniqueRef'])) {
            $retailerUniqueRef = $data['Identification']['RetailerUniqueRef'];
            $omniportRequest = OmniportRequest::getInstance($retailerUniqueRef);

            if ($omniportRequest->id_cart) {
                $deposit = isset($data['Finance']['Deposit']) ? $data['Finance']['Deposit'] : 0;
                $code = isset($data['Finance']['Code']) ? $data['Finance']['Code'] : null;
                $status = isset($data['Status']) ? $data['Status'] : null;
                $requestId = isset($data['CreditRequestID']) ? $data['CreditRequestID'] : null;
                $now = new DateTime();
                $notification = new OmniportNotification();
                $notification->id_cart = (int)$omniportRequest->id_cart;
                $notification->response_finance_deposit = (float)$deposit;
                $notification->response_finance_code = pSQL($code);
                $notification->response_status = pSQL($status);
                $notification->response_credit_request_id = (int)$requestId;
                $notification->created = $now->format('Y-m-d H:i:s');

                if ($notification->validateFields()) {
                    $notification->save();

                    if (in_array(
                        $notification->response_status,
                        array(
                            OmniportSettings::OMNIPORT_CREDIT_APPROVED,
                            OmniportSettings::OMNIPORT_CREDIT_DECLINED
                        )
                    )) {
                        $orderId = Db::getInstance()->getValue(
                            "SELECT o.id_order FROM " . _DB_PREFIX_ . "orders o 
                            WHERE o.id_cart = {$notification->id_cart}"
                        );

                        if ($orderId) {
                            $order = new Order($orderId);
                            switch ($notification->response_status) {
                                case 'APPROVED':
                                    $order->setCurrentState(
                                        Configuration::get(OmniportSettings::PS_OS_CREDIT_APROVED_DEPOSIT_PENDING)
                                    );
                                    break;
                                case 'CREDIT CHECK DECLINED':
                                    $order->setCurrentState(
                                        Configuration::get(OmniportSettings::PS_OS_CREDIT_DECLINED)
                                    );
                                    break;
                            }

                            die('ok');
                        }
                    }
                }
            }
        }
    }
}

die('Yes, it works!');
