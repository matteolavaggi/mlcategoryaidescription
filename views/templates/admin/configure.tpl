{*
* 2007-2026 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2026 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
	<h3><i class="icon icon-shield"></i> {l s='ML Google SEO NoIndex' mod='mlgooglenoindex'}</h3>
	<p>
		<strong>{l s='Optimize your Google crawl budget!' mod='mlgooglenoindex'}</strong><br />
		{l s='This module adds' mod='mlgooglenoindex'} <code>&lt;meta name="robots" content="noindex,follow"&gt;</code> {l s='to filtered and paginated pages.' mod='mlgooglenoindex'}
	</p>
	<br />
	<div class="alert alert-info">
		<p><strong>{l s='How it works:' mod='mlgooglenoindex'}</strong></p>
		<ul>
			<li><strong>{l s='Pagination:' mod='mlgooglenoindex'}</strong> {l s='Pages with ?page=2+ or ?p=2+ will be noindexed' mod='mlgooglenoindex'}</li>
			<li><strong>{l s='Order & Sort:' mod='mlgooglenoindex'}</strong> {l s='Product ordering and sorting parameters will trigger noindex' mod='mlgooglenoindex'}</li>
			<li><strong>{l s='Currency:' mod='mlgooglenoindex'}</strong> {l s='Currency change parameters will trigger noindex' mod='mlgooglenoindex'}</li>
			<li><strong>{l s='Search:' mod='mlgooglenoindex'}</strong> {l s='Search result pages will be noindexed' mod='mlgooglenoindex'}</li>
			<li><strong>{l s='Price Filters:' mod='mlgooglenoindex'}</strong> {l s='Price range filter parameters will trigger noindex' mod='mlgooglenoindex'}</li>
			<li><strong>{l s='Items Per Page:' mod='mlgooglenoindex'}</strong> {l s='Product count per page parameters will trigger noindex' mod='mlgooglenoindex'}</li>
			<li><strong>{l s='Tracking:' mod='mlgooglenoindex'}</strong> {l s='UTM and ad platform tracking parameters (optional)' mod='mlgooglenoindex'}</li>
			<li><strong>{l s='Custom:' mod='mlgooglenoindex'}</strong> {l s='Add your own parameters to trigger noindex' mod='mlgooglenoindex'}</li>
		</ul>
	</div>
</div>

<div class="row">
	<div class="col-lg-6">
		{if isset($ps_faceted_installed) && $ps_faceted_installed}
		<div class="alert alert-success">
			<i class="icon icon-check"></i> 
			<strong>{l s='PS Faceted Search Detected!' mod='mlgooglenoindex'}</strong><br />
			{l s='The native PrestaShop Faceted Search module is active. You can enable noindex for filtered pages.' mod='mlgooglenoindex'}
		</div>
		{else}
		<div class="alert alert-warning">
			<i class="icon icon-warning"></i> 
			<strong>{l s='PS Faceted Search Not Detected' mod='mlgooglenoindex'}</strong><br />
			{l s='The native ps_facetedsearch module is not installed or not active.' mod='mlgooglenoindex'}
		</div>
		{/if}
	</div>
	<div class="col-lg-6">
		{if isset($amazingfilter_installed) && $amazingfilter_installed}
		<div class="alert alert-success">
			<i class="icon icon-check"></i> 
			<strong>{l s='AmazingFilter Detected!' mod='mlgooglenoindex'}</strong><br />
			{l s='The AmazingFilter module is installed and active. You can enable noindex for filtered pages.' mod='mlgooglenoindex'}
		</div>
		{else}
		<div class="alert alert-warning">
			<i class="icon icon-warning"></i> 
			<strong>{l s='AmazingFilter Not Detected' mod='mlgooglenoindex'}</strong><br />
			{l s='The AmazingFilter module is not installed or not active.' mod='mlgooglenoindex'}
		</div>
		{/if}
	</div>
</div>

<div class="panel">
	<h3><i class="icon icon-tags"></i> {l s='Parameter Reference' mod='mlgooglenoindex'}</h3>
	<p>{l s='The following URL patterns will trigger noindex when their respective options are enabled:' mod='mlgooglenoindex'}</p>
	<table class="table table-bordered">
		<thead>
			<tr>
				<th style="width: 25%">{l s='Option' mod='mlgooglenoindex'}</th>
				<th>{l s='Parameters / Patterns' mod='mlgooglenoindex'}</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><strong>{l s='Pagination' mod='mlgooglenoindex'}</strong></td>
				<td><code>?page=2+</code>, <code>?p=2+</code></td>
			</tr>
			<tr>
				<td><strong>{l s='Order & Sort' mod='mlgooglenoindex'}</strong></td>
				<td><code>?order=*</code>, <code>?orderby=*</code>, <code>?orderway=*</code></td>
			</tr>
			<tr>
				<td><strong>{l s='Currency' mod='mlgooglenoindex'}</strong></td>
				<td><code>?id_currency=*</code>, <code>?SubmitCurrency</code></td>
			</tr>
			<tr>
				<td><strong>{l s='Search' mod='mlgooglenoindex'}</strong></td>
				<td><code>?s=*</code>, <code>?q=*</code>, <code>?search_query=*</code>, <code>controller=search</code></td>
			</tr>
			<tr>
				<td><strong>{l s='Price Filters' mod='mlgooglenoindex'}</strong></td>
				<td><code>?from=*</code>, <code>?to=*</code>, <code>?price_min=*</code>, <code>?price_max=*</code></td>
			</tr>
			<tr>
				<td><strong>{l s='Items Per Page' mod='mlgooglenoindex'}</strong></td>
				<td><code>?n=*</code>, <code>?resultsPerPage=*</code></td>
			</tr>
			{if isset($ps_faceted_installed) && $ps_faceted_installed}
			<tr>
				<td><strong>{l s='PS Faceted Search' mod='mlgooglenoindex'}</strong></td>
				<td><code>?id_attribute_group=*</code>, <code>?id_feature=*</code>, {l s='attribute/feature URL patterns' mod='mlgooglenoindex'}</td>
			</tr>
			{/if}
			{if isset($amazingfilter_installed) && $amazingfilter_installed}
			<tr>
				<td><strong>{l s='AmazingFilter' mod='mlgooglenoindex'}</strong></td>
				<td><code>/f-*</code>, <code>?af=*</code>, <code>?from-xhr</code></td>
			</tr>
			{/if}
			<tr>
				<td><strong>{l s='Tracking Params' mod='mlgooglenoindex'}</strong></td>
				<td><code>?utm_*</code>, <code>?gclid</code>, <code>?fbclid</code>, <code>?msclkid</code>, <code>?twclid</code>, <code>?li_fat_id</code>, <code>?mc_cid</code>, <code>?mc_eid</code>, <code>?dclid</code></td>
			</tr>
		</tbody>
	</table>
</div>

<div class="panel">
	<h3><i class="icon icon-lightbulb-o"></i> {l s='SEO Best Practices' mod='mlgooglenoindex'}</h3>
	<div class="alert alert-info">
		<ul style="margin-bottom: 0;">
			<li>{l s='This module uses' mod='mlgooglenoindex'} <code>noindex,follow</code> {l s='to prevent duplicate content while preserving link equity.' mod='mlgooglenoindex'}</li>
			<li>{l s='Tracking parameters are disabled by default - enable only if you don\'t use canonical tags.' mod='mlgooglenoindex'}</li>
			<li>{l s='The HTTP Header option adds X-Robots-Tag which some crawlers prefer over meta tags.' mod='mlgooglenoindex'}</li>
			<li>{l s='Monitor Google Search Console for changes in crawl stats after enabling this module.' mod='mlgooglenoindex'}</li>
		</ul>
	</div>
</div>
