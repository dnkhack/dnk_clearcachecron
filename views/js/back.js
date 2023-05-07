/**
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 website only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please contact us for extra customization service at an affordable price
 *
 *  @author DNK Soft <i@prestashop.world>
 *  @copyright  2021-2022 DNK Soft
 *  @license    Valid for 1 website (or project) for each purchase of license
 */

function dnk_emulate_cron () {
    if ($('#DNK_CLEARCACHECRON_NON_CRON_on')[0].checked) {
        $('#DNK_CLEARCACHECRON_PERIOD').removeAttr('disabled');
        $('#DNK_CLEARCACHECRON_TIME').removeAttr('disabled');
    }
    else {
        $('#DNK_CLEARCACHECRON_PERIOD').attr('disabled','disabled');
        $('#DNK_CLEARCACHECRON_TIME').attr('disabled','disabled');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    $('.help-box').popover();
        dnk_emulate_cron();
    $('input[name="DNK_CLEARCACHECRON_NON_CRON"]').on('click', dnk_emulate_cron);
});
