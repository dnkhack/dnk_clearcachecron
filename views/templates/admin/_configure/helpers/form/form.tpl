{*
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
*}

{extends file="helpers/form/form.tpl"}
{block name="input_row"}
    {if $input.type == 'dnk_title'}
        <div class="form-group{if isset($input.form_group_class)} {$input.form_group_class}{/if}" {if isset($tabs) && isset($input.tab)} data-tab-id="{$input.tab}{/if}>
            <div class="col-xs-12 col-lg-12">
                <div class="dnk_title {if isset($input.class)} {$input.class}{/if}">{$input.title}</div>
            </div>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
