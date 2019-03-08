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

$sql = array();

$sql[] = "CREATE TABLE `"._DB_PREFIX_."omniport_products` (
          `id_omniport_product` int(11) NOT NULL AUTO_INCREMENT,
          `code` varchar(100) NOT NULL,
          `label` varchar(255) NOT NULL,
          `repayment_period` tinyint(2) NOT NULL DEFAULT '12',
          `is_default` tinyint(1) DEFAULT '0',
          `active` tinyint(1) NOT NULL,
          PRIMARY KEY (`id_omniport_product`)
        )";

$sql[] = "CREATE TABLE `"._DB_PREFIX_."omniport_requests` (
          `id_omniport_request` int(11) NOT NULL AUTO_INCREMENT,
          `unique_reference` varchar(50) NOT NULL,
          `id_cart` int(11) NOT NULL,
          `request_finance_deposit` decimal(12,2) NOT NULL,
          `product_code` varchar(50) DEFAULT NULL,
          `created` datetime NOT NULL,
          PRIMARY KEY (`id_omniport_request`),
          KEY `id_cart` (`id_cart`)
        )";


$sql[] = "CREATE TABLE `"._DB_PREFIX_."omniport_notifications` (
          `id_omniport_notification` int(11) NOT NULL AUTO_INCREMENT,
          `id_cart` int(11) NOT NULL,
          `response_credit_request_id` int(11) DEFAULT NULL,
          `response_status` varchar(50) DEFAULT NULL,
          `response_finance_code` varchar(100) DEFAULT NULL,
          `response_finance_deposit` decimal(12,2) DEFAULT NULL,
          `created` datetime DEFAULT NULL,
          PRIMARY KEY (`id_omniport_notification`),
          KEY `id_cart` (`id_cart`)
        )";

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
