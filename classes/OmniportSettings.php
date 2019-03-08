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

class OmniportSettings
{
    /** @const String */
    const MODULE_NAME = 'omniport';

    /** @const String */
    const PS_OS_CREDIT_APROVED_DEPOSIT_PAID = 'PS_OS_OMNIPORT_DEPOSIT_PAID';

    /** @const String */
    const PS_OS_CREDIT_APROVED_DEPOSIT_PAID_LABEL = 'Credit approved, deposit paid';

    /** @const String */
    const PS_OS_CREDIT_APROVED_DEPOSIT_PENDING = 'PS_OS_OMNIPORT_DEPOSIT_PENDING';

    /** @const String */
    const PS_OS_CREDIT_APROVED_DEPOSIT_PENDING_LABEL = 'Credit approved, pending deposit payment';

    /** @const String */
    const PS_OS_CREDIT_PENDING = 'PS_OS_OMNIPORT_CREDIT_PENDING';

    /** @const String */
    const PS_OS_CREDIT_PENDING_LABEL = 'Waiting finance approval';

    /** @const String */
    const PS_OS_CREDIT_DECLINED = 'PS_OS_OMNIPORT_CREDIT_DECLINED';

    /** @const String */
    const PS_OS_CREDIT_DECLINED_LABEL = 'Credit declined';

    /** @const String */
    const OMNIPORT_CREDIT_APPROVED = 'APPROVED';

    /** @const String */
    const OMNIPORT_CREDIT_DECLINED = 'CREDIT CHECK DECLINED';

    /**
     * @return array
     */
    public static function getWorldPayOrderStatuses()
    {
        return array(
            static::PS_OS_CREDIT_PENDING       => array(
                'name'      => static::PS_OS_CREDIT_PENDING_LABEL,
                'color'     => '#e6e5ff',
                'invoice'   => false,
                'send_mail' => false,
                'hidden'    => false,
                'logable'   => false,
                'delivery'  => false,
                'shipped'   => false,
                'paid'      => false,
                'deleted'   => false,
            ),
            static::PS_OS_CREDIT_APROVED_DEPOSIT_PENDING       => array(
                'name'      => static::PS_OS_CREDIT_APROVED_DEPOSIT_PENDING_LABEL,
                'color'     => '#acabff',
                'invoice'   => true,
                'send_mail' => false,
                'hidden'    => false,
                'logable'   => false,
                'delivery'  => false,
                'shipped'   => false,
                'paid'      => false,
                'deleted'   => false,
            ),
            static::PS_OS_CREDIT_APROVED_DEPOSIT_PAID     => array(
                'name'      => static::PS_OS_CREDIT_APROVED_DEPOSIT_PAID_LABEL,
                'color'     => '#9391ff',
                'invoice'   => true,
                'send_mail' => true,
                'hidden'    => false,
                'logable'   => false,
                'delivery'  => false,
                'shipped'   => false,
                'paid'      => true,
                'deleted'   => false,
            ),
            static::PS_OS_CREDIT_DECLINED => array(
                'name'      => static::PS_OS_CREDIT_DECLINED_LABEL,
                'color'     => '#e3c4ff',
                'invoice'   => false,
                'send_mail' => true,
                'hidden'    => false,
                'logable'   => false,
                'delivery'  => false,
                'shipped'   => false,
                'paid'      => false,
                'deleted'   => false,
            ),
        );
    }
}
