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

{extends file='page.tpl'}

{block name='page_header_container'}{/block}

{block name='page_content'}
    <div class="box">
        <h1 class="page-heading">{l s="Ooops, your credit application was declined" d="Modules.Omniport"}</h1>
        <p>
            {l s="Dear," d="Modules.Omniport"}<br/>
            {$firstname|escape:'htmlall':'UTF-8'} {$lastname|escape:'htmlall':'UTF-8'}<br/><br/>
            {l s="Thank you for choosing to pay for your recent order through our finance option." d="Modules.Omniport"}
            <br/><br/>
            {l s="As you might already be aware, our Finance provider Omni Capital has deemed your application unsuccessful because it does not meet their current lending criteria." d="Modules.Omniport"}
            {l s="Their decision was made taking into account the information provided by you in your application, the amount of credit you applied for, together with any additional information about you that was obtained from the Credit Reference agencies." d="Modules.Omniport"}
            <br/><br/>
            {l s="Should you still like to go ahead with your order, you can choose to pay via one of the alternative payment methods by clicking the OTHER PAYMENT METHODS button below." d="Modules.Omniport"}
            <br/><br/>
            {l s="Do let us know if we can be of further assistance through our Contact Us page." d="Modules.Omniport"}
            <br/><br/>
            {l s="Regards," d="Modules.Omniport"}<br/>
            {$shop_name|escape:'htmlall':'UTF-8'}
        </p>
    </div>
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
        {l s="Other payment methods" d="Modules.Omniport"}
    </a>
{/block}

