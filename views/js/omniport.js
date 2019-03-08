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

$(document).ready(function () {
    var repaymentPeriod = $('#finance_product option:selected').data('month');
    var depositAmount = parseFloat($('#deposit').val());
    var totalOrder = parseFloat($('#total-payable').data('total'));
    var maxDepositAmount = parseFloat($('#max-deposit').data('value'));
    var uniqueReference = $('#uniqueRef').val();

    $('#deposit').on('change', function () {
        changeFinanceInfo();
    });

    $('#finance_product').on('change', function () {
        changeFinanceInfo();
    });

    function changeFinanceInfo() {
        repaymentPeriod = $('#finance_product option:selected').data('month');
        depositAmount = parseFloat($('#deposit').val()).toFixed(2);
        if (isNaN(depositAmount) || depositAmount === 'undefined' || depositAmount > maxDepositAmount) {
            alert('Deposit amount is not a valid value!');
            depositAmount = $('#min-deposit').data('value');
            $('#deposit').val(depositAmount);
        }
        $('.deposit-amount').html(depositAmount);
        $('.loan-period').html(repaymentPeriod);
        var instalmentAmount = parseFloat((totalOrder - depositAmount) / repaymentPeriod).toFixed(2);
        $('.installment-amount').html(instalmentAmount);
    }

    $('#omniport-credit-app').on('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var _this = this;
        var productCode = $('#finance_product option:selected').data('product');
        $.ajax({
            type: "POST",
            url: 'modules/omniport/ajax.php',
            cache: false,
            data: {
                depositAmount: depositAmount,
                productCode: productCode,
                uniqueReference: uniqueReference
            },
            success: function (result) {
                result = JSON.parse(result);
                if (result.message === "success") {
                    _this.submit();
                } else {
                    alert("ERROR: Please try again!");
                }
            },
            error: function () {
                alert("ERROR: Please try again!");
            }
        });
    });
});