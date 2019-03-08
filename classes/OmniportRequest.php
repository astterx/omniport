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

class OmniportRequest extends ObjectModel
{
    /** @var int */
    public $id_omniport_request;

    /** @var  String */
    public $unique_reference;

    /** @var Int */
    public $id_cart;

    /** @var float */
    public $request_finance_deposit;

    /** @var  String */
    public $product_code;

    /** @var  DateTime */
    public $created;

    /** @var array */
    public static $definition = array(
        'table'          => 'omniport_requests',
        'primary'        => 'id_omniport_request',
        'multilang'      => false,
        'multilang_shop' => false,
        'fields'         => array(
            'unique_reference'        => array(
                'type' => self::TYPE_STRING, 'required' => true, 'size' => 50,
            ),
            'id_cart'                 => array(
                'type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedInt',
            ),
            'request_finance_deposit' => array(
                'type' => self::TYPE_FLOAT, 'required' => true, 'validate' => 'isFloat',
            ),
            'product_code'            => array(
                'type' => self::TYPE_STRING, 'required' => false, 'size' => 50,
            ),
            'created'                 => array(
                'type' => self::TYPE_DATE, 'validate' => 'isDate',
            ),
        ),
    );

    /**
     * @param $uniqueReference
     *
     * @return mixed
     */
    public static function findByUniqueReference($uniqueReference)
    {
        $uniqueReference = pSQL($uniqueReference);

        return Db::getInstance()->getValue(
            "SELECT o.id_omniport_request FROM " . _DB_PREFIX_ . "omniport_requests o 
            WHERE o.unique_reference = '{$uniqueReference}'"
        );
    }

    /**
     * @param $cartId
     *
     * @return mixed
     */
    public static function findByCartId($cartId)
    {
        $cartId = (int)$cartId;
        return Db::getInstance()->getValue(
            "SELECT o.id_omniport_request FROM " . _DB_PREFIX_ . "omniport_requests o WHERE o.id_cart = '{$cartId}'"
        );
    }

    /**
     * @param $cartId
     *
     * @return float
     */
    public static function getAmountByCartId($cartId)
    {
        $cartId = (int)$cartId;
        $amount = Db::getInstance()->getValue(
            "SELECT o.request_finance_deposit FROM " . _DB_PREFIX_ . "omniport_requests o 
            WHERE o.id_cart = '{$cartId}'"
        );

        return (float)$amount;
    }

    /**
     * @param null $uniqueReference
     *
     * @return OmniportRequest
     */
    public static function getInstance($uniqueReference = null)
    {
        if (null === $uniqueReference) {
            return new OmniportRequest();
        }

        $omniportId = OmniportRequest::findByUniqueReference($uniqueReference);
        if ($omniportId) {
            $instance = new OmniportRequest($omniportId);
        } else {
            $instance = new OmniportRequest();
            $instance->unique_reference = $uniqueReference;
        }

        return $instance;
    }
}
