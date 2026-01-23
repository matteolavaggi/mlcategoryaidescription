{*
 * 2010-2026 2win.agency
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 wesite only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please contact us for extra customization service at an affordable price
 *
 * @author    2win.agency
 * @copyright 2010-2026 2win.agency
 * @license   Valid for 1 website (or project) for each purchase of license
 *            International Registered Trademark & Property of 2win.agency
 *}

{* Batch Generation Panel *}
<div class="panel" id="mlcategoryai-batch-panel">
	<h3><i class="icon icon-magic"></i> {l s='AI Content Generation' mod='mlcategoryaidescription'}</h3>

	{* Current Job Status *}
	{if $current_job}
	<div class="alert alert-warning" id="mlcategoryai-current-job">
		<p><strong>{l s='Job in Progress' mod='mlcategoryaidescription'}</strong></p>
		<p>
			{l s='Status:' mod='mlcategoryaidescription'} <span id="job-status">{$current_job.status|escape:'htmlall':'UTF-8'}</span><br>
			{l s='Progress:' mod='mlcategoryaidescription'} <span id="job-progress">{$current_job.processed_items|escape:'htmlall':'UTF-8'}</span> / <span id="job-total">{$current_job.total_items|escape:'htmlall':'UTF-8'}</span>
		</p>
		<div class="progress" style="margin-bottom: 15px;">
			<div class="progress-bar" role="progressbar" id="job-progress-bar"
				style="width: {if $current_job.total_items > 0}{$current_job.processed_items / $current_job.total_items * 100|intval}{else}0{/if}%">
			</div>
		</div>
		<button type="button" class="btn btn-warning" id="btn-pause-job" data-job-id="{$current_job.id_job|escape:'htmlall':'UTF-8'}">
			<i class="icon icon-pause"></i> {l s='Pause' mod='mlcategoryaidescription'}
		</button>
		<button type="button" class="btn btn-success" id="btn-resume-job" data-job-id="{$current_job.id_job|escape:'htmlall':'UTF-8'}" style="display:none;">
			<i class="icon icon-play"></i> {l s='Resume' mod='mlcategoryaidescription'}
		</button>
		<button type="button" class="btn btn-danger" id="btn-cancel-job" data-job-id="{$current_job.id_job|escape:'htmlall':'UTF-8'}">
			<i class="icon icon-times"></i> {l s='Cancel' mod='mlcategoryaidescription'}
		</button>
	</div>
	{/if}

	{* New Job Form *}
	<div id="mlcategoryai-new-job-form" {if $current_job}style="display:none;"{/if}>
		<div class="row">
			<div class="col-lg-6">
				<div class="form-group">
					<label class="control-label">{l s='Select Categories' mod='mlcategoryaidescription'}</label>
					
					{* Search and controls *}
					<div class="mlcatai-category-controls">
						<div class="input-group" style="margin-bottom: 10px;">
							<span class="input-group-addon"><i class="icon icon-search"></i></span>
							<input type="text" id="category-search" class="form-control" placeholder="{l s='Search categories...' mod='mlcategoryaidescription'}">
							<span class="input-group-btn">
								<button type="button" class="btn btn-default" id="clear-category-search" title="{l s='Clear search' mod='mlcategoryaidescription'}">
									<i class="icon icon-times"></i>
								</button>
							</span>
						</div>
						<div class="btn-group btn-group-sm" style="margin-bottom: 10px;">
							<button type="button" class="btn btn-default" id="select-all-categories">
								<i class="icon icon-check-square-o"></i> {l s='Select All' mod='mlcategoryaidescription'}
							</button>
							<button type="button" class="btn btn-default" id="deselect-all-categories">
								<i class="icon icon-square-o"></i> {l s='Deselect All' mod='mlcategoryaidescription'}
							</button>
							<button type="button" class="btn btn-default" id="expand-all-categories">
								<i class="icon icon-plus-square-o"></i> {l s='Expand All' mod='mlcategoryaidescription'}
							</button>
							<button type="button" class="btn btn-default" id="collapse-all-categories">
								<i class="icon icon-minus-square-o"></i> {l s='Collapse All' mod='mlcategoryaidescription'}
							</button>
						</div>
					</div>
					
					{* Category tree container *}
					<div id="category-tree-container" class="mlcatai-category-tree">
						{function name=categoryTree categories=[] level=0}
							{foreach from=$categories item=category}
							<div class="mlcatai-tree-node" data-id="{$category.id_category|escape:'htmlall':'UTF-8'}" data-level="{$level|escape:'htmlall':'UTF-8'}">
								<div class="mlcatai-tree-item" style="padding-left: {($level * 20)|escape:'htmlall':'UTF-8'}px;">
									{if isset($category.children) && $category.children|@count > 0}
									<span class="mlcatai-tree-toggle" data-expanded="true">
										<i class="icon icon-minus-square-o"></i>
									</span>
									{else}
									<span class="mlcatai-tree-toggle-placeholder"></span>
									{/if}
									<label class="mlcatai-tree-label">
										<input type="checkbox" class="category-checkbox" value="{$category.id_category|escape:'htmlall':'UTF-8'}" data-name="{$category.name|escape:'htmlall':'UTF-8'|lower}">
										<span class="mlcatai-tree-name">{$category.name|escape:'htmlall':'UTF-8'}</span>
										{if isset($category.last_generated) && $category.last_generated}
										<span class="mlcatai-gen-info" title="{if $category.generation_type == 'full'}{l s='Full generation (all fields)' mod='mlcategoryaidescription'}{else}{l s='Partial generation' mod='mlcategoryaidescription'}{/if}">
											{$category.last_generated|escape:'htmlall':'UTF-8'} {if $category.generation_type == 'full'}full{else}partial{/if}
										</span>
										{/if}
									</label>
									{if isset($category.children) && $category.children|@count > 0}
									<button type="button" class="btn btn-xs btn-link mlcatai-select-children" title="{l s='Select all subcategories' mod='mlcategoryaidescription'}">
										<i class="icon icon-sitemap"></i>
									</button>
									{/if}
								</div>
								{if isset($category.children) && $category.children|@count > 0}
								<div class="mlcatai-tree-children">
									{call name=categoryTree categories=$category.children level=$level+1}
								</div>
								{/if}
							</div>
							{/foreach}
						{/function}
						{call name=categoryTree categories=$categories_tree level=0}
					</div>
					
					<p class="help-block">
						<span id="selected-categories-count">0</span> {l s='categories selected' mod='mlcategoryaidescription'}
					</p>
				</div>
			</div>
			<div class="col-lg-6">
				<div class="form-group">
					<label class="control-label">{l s='Select Languages' mod='mlcategoryaidescription'}</label>
					<div class="checkbox">
						<label>
							<input type="checkbox" id="select-all-languages" checked>
							<strong>{l s='Select All' mod='mlcategoryaidescription'}</strong>
						</label>
					</div>
					{foreach from=$languages item=lang}
					<div class="checkbox">
						<label>
							<input type="checkbox" name="languages[]" value="{$lang.id_lang|escape:'htmlall':'UTF-8'}" class="lang-checkbox" checked>
							{$lang.name|escape:'htmlall':'UTF-8'}
						</label>
					</div>
					{/foreach}
				</div>

				<div class="form-group">
					<label class="control-label">{l s='Fields to Generate' mod='mlcategoryaidescription'}</label>
					<div class="checkbox">
						<label>
							<input type="checkbox" name="fields[]" value="description" class="field-checkbox" checked>
							{l s='Description' mod='mlcategoryaidescription'}
						</label>
					</div>
					<div class="checkbox">
						<label>
							<input type="checkbox" name="fields[]" value="meta_title" class="field-checkbox" checked>
							{l s='Meta Title' mod='mlcategoryaidescription'}
						</label>
					</div>
					<div class="checkbox">
						<label>
							<input type="checkbox" name="fields[]" value="meta_description" class="field-checkbox" checked>
							{l s='Meta Description' mod='mlcategoryaidescription'}
						</label>
					</div>
					{if isset($has_meta_keywords) && $has_meta_keywords}
					<div class="checkbox">
						<label>
							<input type="checkbox" name="fields[]" value="meta_keywords" class="field-checkbox">
							{l s='Meta Keywords' mod='mlcategoryaidescription'}
						</label>
					</div>
					{/if}
					<div class="checkbox">
						<label>
							<input type="checkbox" name="fields[]" value="link_rewrite" class="field-checkbox">
							{l s='Friendly URL (SEO slug)' mod='mlcategoryaidescription'}
						</label>
					</div>
				</div>

				<div class="form-group">
					<label class="control-label">{l s='Write Mode' mod='mlcategoryaidescription'}</label>
					<select id="write-mode-select" class="form-control">
						<option value="fill_missing">{l s='Fill missing only - Keep existing content' mod='mlcategoryaidescription'}</option>
						<option value="overwrite">{l s='Overwrite - Replace all content' mod='mlcategoryaidescription'}</option>
					</select>
				</div>

				<div class="form-group">
					<label class="control-label">{l s='Processing Mode' mod='mlcategoryaidescription'}</label>
					<select id="processing-mode-select" class="form-control">
						<option value="browser">{l s='Browser - Process now (keep this tab open)' mod='mlcategoryaidescription'}</option>
						<option value="background">{l s='Background - Create job for cron processing' mod='mlcategoryaidescription'}</option>
					</select>
					<p class="help-block" id="processing-mode-help">
						<span id="help-browser">{l s='Processing will happen in this browser tab. Do not close until complete.' mod='mlcategoryaidescription'}</span>
						<span id="help-background" style="display:none;">{l s='Job will be queued. Configure cron to process automatically in background.' mod='mlcategoryaidescription'}</span>
					</p>
				</div>

				{* Google Translate Mode *}
				{if isset($google_translate_enabled) && $google_translate_enabled && $google_translate_configured}
				<div class="form-group" id="google-translate-mode-group">
					<label class="control-label">
						{l s='Translation Mode' mod='mlcategoryaidescription'}
						<span class="badge badge-success" style="margin-left: 5px;">
							<i class="icon icon-globe"></i> {l s='Google Translate' mod='mlcategoryaidescription'}
						</span>
					</label>
					<div class="checkbox">
						<label>
							<input type="checkbox" id="use-google-translate" value="1" checked>
							<strong>{l s='Use Google Translate' mod='mlcategoryaidescription'}</strong>
						</label>
					</div>
					<p class="help-block">
						{l s='Generate content in primary language using OpenAI, then translate to other languages using Google Translate API. This is faster and more cost-effective.' mod='mlcategoryaidescription'}
					</p>
					
					<div id="google-translate-details" class="well well-sm" style="margin-top: 10px;">
						<div class="row">
							<div class="col-xs-6">
								<strong>{l s='Primary Language:' mod='mlcategoryaidescription'}</strong><br>
								{foreach from=$languages item=lang}
									{if $lang.id_lang == $primary_language_id}
										<span class="label label-primary">{$lang.name|escape:'htmlall':'UTF-8'}</span>
									{/if}
								{/foreach}
								<small class="text-muted">({l s='OpenAI generation' mod='mlcategoryaidescription'})</small>
							</div>
							<div class="col-xs-6">
								<strong>{l s='Translate to:' mod='mlcategoryaidescription'}</strong><br>
								{assign var="translate_count" value=0}
								{foreach from=$languages item=lang}
									{if in_array($lang.id_lang, $translate_language_ids)}
										<span class="label label-info">{$lang.name|escape:'htmlall':'UTF-8'}</span>
										{assign var="translate_count" value=$translate_count+1}
									{/if}
								{/foreach}
								{if $translate_count == 0}
									<span class="text-warning">{l s='No languages configured' mod='mlcategoryaidescription'}</span>
								{/if}
							</div>
						</div>
						<input type="hidden" id="primary-language-id" value="{$primary_language_id|escape:'htmlall':'UTF-8'}">
					</div>
				</div>
				{elseif isset($google_translate_enabled) && $google_translate_enabled && !$google_translate_configured}
				<div class="alert alert-warning" style="margin-top: 10px;">
					<i class="icon icon-exclamation-triangle"></i>
					{l s='Google Translate is enabled but API key is not configured. Please configure it in the Translation Settings section below.' mod='mlcategoryaidescription'}
				</div>
				{/if}
			</div>
		</div>

		<div class="panel-footer">
			<button type="button" class="btn btn-primary" id="btn-start-generation">
				<i class="icon icon-rocket"></i> {l s='Start Generation' mod='mlcategoryaidescription'}
			</button>
			<button type="button" class="btn btn-info" id="btn-start-background" style="display:none;">
				<i class="icon icon-clock-o"></i> {l s='Queue for Background' mod='mlcategoryaidescription'}
			</button>
			<button type="button" class="btn btn-default" id="btn-test-api">
				<i class="icon icon-plug"></i> {l s='Test OpenAI Connection' mod='mlcategoryaidescription'}
			</button>
			{if isset($google_translate_enabled) && $google_translate_enabled && $google_translate_configured}
			<button type="button" class="btn btn-default" id="btn-test-google-api">
				<i class="icon icon-globe"></i> {l s='Test Google Translate' mod='mlcategoryaidescription'}
			</button>
			{/if}
		</div>
	</div>

	{* Progress Display (shown during generation) *}
	<div id="mlcategoryai-progress" style="display:none;">
		<div class="progress">
			<div class="progress-bar progress-bar-striped active" role="progressbar" id="generation-progress-bar" style="width: 0%">
				<span id="generation-progress-text">0%</span>
			</div>
		</div>
		<div id="generation-log" class="well" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
		</div>
	</div>
</div>

{* Placeholder Reference Panel *}
<div class="panel">
	<h3><i class="icon icon-info-circle"></i> {l s='Available Placeholders' mod='mlcategoryaidescription'}</h3>
	<p>{l s='Use these placeholders in your prompt templates. They will be replaced with actual data at generation time.' mod='mlcategoryaidescription'}</p>
	<table class="table table-bordered table-striped">
		<thead>
			<tr>
				<th>{l s='Placeholder' mod='mlcategoryaidescription'}</th>
				<th>{l s='Description' mod='mlcategoryaidescription'}</th>
				<th>{l s='Example' mod='mlcategoryaidescription'}</th>
			</tr>
		</thead>
		<tbody>
			<tr><td><code>{literal}{category_name}{/literal}</code></td><td>{l s='Category name in target language' mod='mlcategoryaidescription'}</td><td>Men's Shoes</td></tr>
			<tr><td><code>{literal}{category_description}{/literal}</code></td><td>{l s='Current category description' mod='mlcategoryaidescription'}</td><td>Browse our collection...</td></tr>
			<tr><td><code>{literal}{category_meta_title}{/literal}</code></td><td>{l s='Current meta title' mod='mlcategoryaidescription'}</td><td>Men's Shoes - MyShop</td></tr>
			<tr><td><code>{literal}{category_meta_description}{/literal}</code></td><td>{l s='Current meta description' mod='mlcategoryaidescription'}</td><td>Shop the best...</td></tr>
			{if isset($has_meta_keywords) && $has_meta_keywords}
			<tr><td><code>{literal}{category_meta_keywords}{/literal}</code></td><td>{l s='Current meta keywords' mod='mlcategoryaidescription'}</td><td>shoes, mens, footwear</td></tr>
			{/if}
			<tr><td><code>{literal}{category_link_rewrite}{/literal}</code></td><td>{l s='Current friendly URL slug' mod='mlcategoryaidescription'}</td><td>mens-shoes</td></tr>
			<tr><td><code>{literal}{parent_category_name}{/literal}</code></td><td>{l s='Parent category name' mod='mlcategoryaidescription'}</td><td>Footwear</td></tr>
			<tr><td><code>{literal}{site_name}{/literal}</code></td><td>{l s='Shop name' mod='mlcategoryaidescription'}</td><td>MyShop</td></tr>
			<tr><td><code>{literal}{site_description}{/literal}</code></td><td>{l s='Shop meta description' mod='mlcategoryaidescription'}</td><td>Your online store...</td></tr>
			<tr><td><code>{literal}{product_count}{/literal}</code></td><td>{l s='Number of products in category' mod='mlcategoryaidescription'}</td><td>42</td></tr>
			<tr><td><code>{literal}{first_products:N}{/literal}</code></td><td>{l s='First N products from category' mod='mlcategoryaidescription'}</td><td>{literal}{first_products:10}{/literal}</td></tr>
			<tr><td><code>{literal}{random_products:N}{/literal}</code></td><td>{l s='N random products from category' mod='mlcategoryaidescription'}</td><td>{literal}{random_products:5}{/literal}</td></tr>
			<tr><td><code>{literal}{language_code}{/literal}</code></td><td>{l s='Target language ISO code (auto-injected)' mod='mlcategoryaidescription'}</td><td>en, fr, de</td></tr>
			<tr><td><code>{literal}{language_name}{/literal}</code></td><td>{l s='Target language name (auto-injected)' mod='mlcategoryaidescription'}</td><td>English, Français</td></tr>
		</tbody>
	</table>
</div>

{* Initialize JavaScript with AJAX configuration *}
<script type="text/javascript">
	// Set global variables for the back.js script
	window.mlcategoryai_ajax_url = '{$ajax_url|escape:'javascript':'UTF-8'}';
	window.mlcategoryai_token = '{$ajax_token|escape:'javascript':'UTF-8'}';
	
	console.log('ML Category AI: Config loaded', {
		ajax_url: window.mlcategoryai_ajax_url,
		token_set: !!window.mlcategoryai_token
	});
</script>
