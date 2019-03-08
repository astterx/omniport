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

class OmniportNotification extends ObjectModel
{
    /** @var int */
    public $id_omniport_notification;

    /** @var Int */
    public $id_cart;

    /** @var Int */
    public $response_credit_request_id;

    /** @var float */
    public $response_finance_deposit;

    /** @var string */
    public $response_status;

    /** @var string */
    public $response_finance_code;

    /** @var  DateTime */
    public $created;

    /** @var array */
    public static $definition = array(
        'table'          => 'omniport_notifications',
        'primary'        => 'id_omniport_notification',
        'multilang'      => false,
        'multilang_shop' => false,
        'fields'         => array(
            'id_cart'                    => array(
                'type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedInt',
            ),
            'response_credit_request_id' => array(
                'type' => self::TYPE_INT, 'required' => false, 'validate' => 'isUnsignedInt',
            ),
            'response_status'            => array(
                'type' => self::TYPE_STRING, 'required' => false, 'size' => 50,
            ),
            'response_finance_code'      => array(
                'type' => self::TYPE_STRING, 'required' => false, 'size' => 100,
            ),
            'response_finance_deposit'   => array(
                'type' => self::TYPE_FLOAT, 'required' => false, 'validate' => 'isFloat',
            ),
            'created'                    => array(
                'type' => self::TYPE_DATE,
            ),
        ),
    );

    /**
     * @param $cartId
     *
     * @return bool
     */
    public static function isApproved($cartId)
    {
        $cartId = (int)$cartId;
        $notification = Db::getInstance()->getValue(
            "SELECT o.id_omniport_notification FROM " . _DB_PREFIX_ . "omniport_notifications o 
            WHERE o.id_cart = {$cartId} AND o.response_status = '" . OmniportSettings::OMNIPORT_CREDIT_APPROVED . "'"
        );
        if ($notification) {
            return true;
        }

        return false;
    }

    /**
     * @param $cartId
     *
     * @return bool
     */
    public static function isDeclined($cartId)
    {
        $cartId = (int)$cartId;
        $notification = Db::getInstance()->getValue(
            "SELECT o.id_omniport_notification FROM " . _DB_PREFIX_ . "omniport_notifications o 
            WHERE o.id_cart = {$cartId} AND o.response_status = '" . OmniportSettings::OMNIPORT_CREDIT_DECLINED . "'"
        );
        if ($notification) {
            return true;
        }

        return false;
    }

    /**
     * @param $cartId
     *
     * @return mixed
     */
    public static function getDepositValue($cartId)
    {
        $cartId = (int)$cartId;

        return Db::getInstance()->getValue(
            "SELECT COALESCE(o.response_finance_deposit, 0) FROM " ._DB_PREFIX_. "omniport_notifications o
            WHERE o.id_cart = {$cartId} AND o.response_status = '" . OmniportSettings::OMNIPORT_CREDIT_APPROVED . "'"
        );
    }
}
