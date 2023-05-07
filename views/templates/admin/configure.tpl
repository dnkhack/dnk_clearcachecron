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

<div class="panel">
	<h3><i class="icon icon-calendar"></i> {l s='Log' mod='dnk_clearcachecron'}</h3>
	<div class="log_content">
		{$log_content}
	</div>
</div>

<div class="panel">
	<h3><i class="icon icon-info-sign"></i> {l s='Short guide' mod='dnk_clearcachecron'}</h3>

	<h4><strong>{l s='Two ways to use this module' mod='dnk_clearcachecron'}</strong></h4><br />
	<p>
		<strong>{l s='1) Use the link below with your Cron service.' mod='dnk_clearcachecron'}</strong><br />
		{l s='Сron is a Unix system tool that provides time-based job scheduling.' mod='dnk_clearcachecron'}<br />
		<a target="_blank" href="{$cron_link}">{$cron_link}</a><br />
		<hr>
	</p>
	<p>
		<strong>{l s='OR...' mod='dnk_clearcachecron'}</strong><br />
		<strong>{l s='2) Emulate Cron provides you with a cron-like tool that will call a set of cleaning methods with chosen time interval.' mod='dnk_clearcachecron'}</strong>
		<ul>
			<li>{l s='enable Emulate Cron' mod='dnk_clearcachecron'}</li>
			<li>{l s='set the period' mod='dnk_clearcachecron'}</li>
			<li>{l s='set time to run the job' mod='dnk_clearcachecron'}</li>

		</ul>
	</p>
</div>

<div class="panel">
	<h3><i class="icon icon-file-text-alt"></i> {l s='Documentation' mod='dnk_clearcachecron'}</h3>
	<p>
		{l s='You can get a PDF documentation to configure this module' mod='dnk_clearcachecron'} :
	<ul>
		<li><a href="/modules/dnk_clearcachecron/guide_en.pdf" target="_blank">{l s='English' mod='dnk_clearcachecron'}</a></li>
	</ul>
	</p>
</div>

<div class="panel">
	<h3><i class="icon icon-thumbs-up"></i> {l s='Prestashop-World Team' mod='dnk_clearcachecron'}</h3>
	{l s='This team: ' mod='dnk_clearcachecron'}
	<ul>
		<li>{l s='Fix Prestashop modules and themes bugs' mod='dnk_clearcachecron'}</li>
		<li>{l s='Improve management and site' mod='dnk_clearcachecron'}</li>
		<li>{l s='Will boost your sales!' mod='dnk_clearcachecron'}</li>
	</ul>
</div>

{include file='./prestashop.world.tpl'}