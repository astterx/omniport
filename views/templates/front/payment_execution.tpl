{*
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
*}
<form action="{$omniport_url|escape:'html':'UTF-8'}" method="post" class="form-horizontal" id="omniport-credit-app">
    <div class="box cheque-box">
        <table>
            <tr>
                <td colspan="2">{l s="Please specify the deposit amount that you wish to pay." d="Modules.Omniport"}
                    {l s="The accepted value is between" d="Modules.Omniport"}
                    <span id="min-deposit" data-value="{$min_deposit|escape:'htmlall':'UTF-8'}">&pound;{$min_deposit|escape:'htmlall':'UTF-8'}</span>
                    ({l s='%min_deposit_p%% minimum' d='Modules.Omniport' sprintf=['%min_deposit_p%' => $min_deposit_p]})
                    {l s="and" d="Modules.Omniport"}
                    <span id="max-deposit" data-value="{$max_deposit|escape:'htmlall':'UTF-8'}">&pound;{$max_deposit|escape:'htmlall':'UTF-8'}</span>
                    ({l s='%max_deposit_p%% maximum' d='Modules.Omniport' sprintf=['%max_deposit_p%' => $max_deposit_p|escape:'htmlall':'UTF-8']})
                    {if $tax_incl|escape:'htmlall':'UTF-8'}{l s="taxes included" d="Modules.Omniport"}{else}{l s="taxes excluded" d="Modules.Omniport"}{/if}.
                </td>
            </tr>
            <tr>
                <td>
                    <div class="form-group">
                        <label for="finance_product">{l s="Finance period" d="Modules.Omniport"}</label>
                        <div class="input-group">
                            <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
                        <select id="finance_product" class="form-control form-control-select" name="Finance[Code]">
                            {foreach from=$finance_products item=product}
                                <option value="{$product.code|escape:'htmlall':'UTF-8'}" data-month="{$product.repayment_period|escape:'htmlall':'UTF-8'}"
                                        data-product="{$product.code|escape:'htmlall':'UTF-8'}">{$product.label|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
                            </select>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <label for="deposit">{l s="Deposit amount" d="Modules.Omniport"}</label>
                        <div class="input-group">
                            <div class="input-group-addon">&pound;</div>
                            <input id="deposit" type="text" name="Finance[Deposit]"
                                   value="{$min_deposit|escape:'htmlall':'UTF-8'}" class="form-control"/>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    {l s="This is a" d="Modules.Omniport"} &pound;{l s="%cart_total% loan, your monthly repayments will be" d="Modules.Omniport" sprintf=['%cart_total%' => $cart_total]}
                    &pound;<span class="installment-amount">
                        {$default_installment|escape:'htmlall':'UTF-8'}</span> for <span class="loan-period">{$default_repayment|escape:'htmlall':'UTF-8'}</span> {l s="months" d="Omniport.Modules"}.
                    {l s="The total amount payable is" d="Modules.Omniport"} &pound;{$cart_total|escape:'htmlall':'UTF-8'}
                    {l s="The cost for credit (interest and charges) is &pound;0.00. APR 0.00% representative." d="Modules.Omniport"}
                </td>
            </tr>
            <tr>
                <td class="color-column border-cell"><strong>{l s="APR" d="Modules.Omniport"}</strong></td>
                <td class="border-cell">0.00%</td>
            </tr>
            <tr>
                <td class="border-cell"><strong>{l s="Installment amount" d="Modules.Omniport"}</strong></td>
                <td class="border-cell">&pound;<span class="installment-amount">{$default_installment|escape:'htmlall':'UTF-8'}</span></td>
            </tr>
            <tr>
                <td class="border-cell color-column">
                    <strong>{l s="Repayment term" d="Modules.Omniport"}</strong>
                </td>
                <td class="border-cell">
                    <span class="loan-period">{$default_repayment|escape:'htmlall':'UTF-8'}</span> {l s="months" d="Modules.Omniport"}
                </td>
            </tr>
            <tr>
                <td class="border-cell"><strong>{l s="Total Interest charged" d="Modules.Omniport"}</strong></td>
                <td class="border-cell">&pound;0.00</td>
            </tr>
            <tr>
                <td class="border-cell color-column">
                    <strong>{l s="Deposit amount" d="Modules.Omniport"}</strong>
                </td>
                <td class="border-cell">
                    &pound;<span class="deposit-amount">{$min_deposit|escape:'htmlall':'UTF-8'}</span>
                </td>
            </tr>
            <tr>
                <td class="border-cell">
                    <strong>{l s="Total amount payable" d="Modules.Omniport"}</strong>
                </td>
                <td class="border-cell">
                    &pound;<span id="total-payable" data-total="{$cart_total|escape:'htmlall':'UTF-8'}">{$total|escape:'htmlall':'UTF-8'}</span>
                </td>
            </tr>
        </table>

        <input type="hidden" name="Identification[api_key]" value="{$api_key|escape:'htmlall':'UTF-8'}"/>
        <input type="hidden" id="uniqueRef" name="Identification[RetailerUniqueRef]" value="{$unique_reference|escape:'htmlall':'UTF-8'}"/>
        <input type="hidden" name="Identification[InstallationID]" value="{$installation_id|escape:'htmlall':'UTF-8'}"/>
        <input type="hidden" name="Goods[0][Description]" value="Car parts - cart id {$cart_id|escape:'htmlall':'UTF-8'}"/>
        <input type="hidden" name="Goods[0][Price]" value="{$cart_total|escape:'htmlall':'UTF-8'}"/>
    </div>
</form>
<br/>