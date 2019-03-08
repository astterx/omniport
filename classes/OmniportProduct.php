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

class OmniportProduct extends ObjectModel
{
    /** @var int */
    public $id_omniport_product;
    /** @var string */
    public $code;
    /** @var string */
    public $label;
    /** @var Int */
    public $repayment_period;
    /** @var bool */
    public $is_default = false;
    /** @var bool */
    public $active = true;

    /** @var array */
    public static $definition = array(
        'table'          => 'omniport_products',
        'primary'        => 'id_omniport_product',
        'multilang'      => false,
        'multilang_shop' => false,
        'fields'         => array(
            'code'             => array('type' => self::TYPE_STRING, 'required' => true, 'validate' => 'isCleanHtml'),
            'label'            => array('type' => self::TYPE_STRING, 'required' => true, 'validate' => 'isCleanHtml'),
            'repayment_period' => array('type' => self::TYPE_INT, 'required' => true, 'validate' => 'isUnsignedInt'),
            'is_default'       => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'active'           => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
        ),
    );

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     */
    public static function resetDefaultProduct()
    {
        $query = 'UPDATE `'. _DB_PREFIX_ .'omniport_products` SET is_default = 0 WHERE is_default = 1';

        return Db::getInstance()->execute($query);
    }

    /**
     * @param bool $activeOnly
     * @param int  $limit
     *
     * @return array
     */
    public static function getProducts($activeOnly = false, $limit = 0)
    {
        $query = 'SELECT * FROM `' . _DB_PREFIX_ . 'omniport_products`';
        if ($activeOnly) {
            $query .= ' WHERE `active` = 1';
        }

        if ($activeOnly) {
            $query .= ' ORDER BY `is_default` DESC, repayment_period ASC';
        } else {
            $query .= ' ORDER BY `id_omniport_product` ASC';
        }

        if ($limit) {
            $query .= ' LIMIT ' . (int) $limit;
        }

        return Db::getInstance()->executeS($query);
    }
}
