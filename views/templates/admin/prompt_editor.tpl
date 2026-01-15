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

<div class="prompt-editor-container">
	<div class="row">
		<div class="col-lg-12">
			<div class="form-group">
				<label class="control-label">{l s='Select Field Type' mod='mlcategoryaidescription'}</label>
				<select id="prompt-field-type" class="form-control fixed-width-xl">
					{foreach from=$field_types key=field_key item=field_name}
					<option value="{$field_key|escape:'htmlall':'UTF-8'}">{$field_name|escape:'htmlall':'UTF-8'}</option>
					{/foreach}
				</select>
			</div>
		</div>
	</div>

	{* Language tabs *}
	<ul class="nav nav-tabs" id="prompt-language-tabs">
		{foreach from=$languages item=lang name=langLoop}
		<li{if $smarty.foreach.langLoop.first} class="active"{/if}>
			<a href="#prompt-lang-{$lang.id_lang|escape:'htmlall':'UTF-8'}" data-toggle="tab">
				{if isset($lang.iso_code)}
					<img src="../img/l/{$lang.id_lang|escape:'htmlall':'UTF-8'}.jpg" alt="{$lang.iso_code|escape:'htmlall':'UTF-8'}" class="imgm">
				{/if}
				{$lang.name|escape:'htmlall':'UTF-8'}
			</a>
		</li>
		{/foreach}
	</ul>

	{* Language tab content *}
	<div class="tab-content" id="prompt-tab-content">
		{foreach from=$languages item=lang name=langLoop}
		<div class="tab-pane{if $smarty.foreach.langLoop.first} active{/if}" id="prompt-lang-{$lang.id_lang|escape:'htmlall':'UTF-8'}">
			<div class="form-group" style="margin-top: 15px;">
				{foreach from=$field_types key=field_key item=field_name name=fieldLoop}
				<div class="prompt-textarea-wrapper" data-field="{$field_key|escape:'htmlall':'UTF-8'}" data-lang="{$lang.id_lang|escape:'htmlall':'UTF-8'}" {if $field_key != 'description'}style="display:none;"{/if}>
					<textarea 
						class="form-control prompt-textarea" 
						id="prompt_{$field_key|escape:'htmlall':'UTF-8'}_{$lang.id_lang|escape:'htmlall':'UTF-8'}"
						name="prompt[{$field_key|escape:'htmlall':'UTF-8'}][{$lang.id_lang|escape:'htmlall':'UTF-8'}]"
						rows="12"
						placeholder="{l s='Enter your prompt template here...' mod='mlcategoryaidescription'}"
					>{if isset($prompts[$field_key][$lang.id_lang])}{$prompts[$field_key][$lang.id_lang]|escape:'htmlall':'UTF-8'}{/if}</textarea>
				</div>
				{/foreach}
			</div>
		</div>
		{/foreach}
	</div>

	<div class="form-group" style="margin-top: 15px;">
		<button type="button" class="btn btn-primary" id="btn-save-prompts">
			<i class="icon icon-save"></i> {l s='Save Prompts' mod='mlcategoryaidescription'}
		</button>
		<button type="button" class="btn btn-default" id="btn-reset-prompt">
			<i class="icon icon-refresh"></i> {l s='Reset to Default' mod='mlcategoryaidescription'}
		</button>
		<button type="button" class="btn btn-info" id="btn-preview-prompt">
			<i class="icon icon-eye"></i> {l s='Preview' mod='mlcategoryaidescription'}
		</button>
	</div>

	<div id="prompt-preview-result" class="alert alert-info" style="display:none; margin-top: 15px;">
		<strong>{l s='Resolved Prompt Preview:' mod='mlcategoryaidescription'}</strong>
		<pre id="prompt-preview-content" style="margin-top: 10px; white-space: pre-wrap;"></pre>
	</div>
</div>

<script type="text/javascript">
(function() {
	document.addEventListener('DOMContentLoaded', function() {
		var fieldSelect = document.getElementById('prompt-field-type');
		
		// Function to update visible textareas based on selected field
		function updateVisibleTextareas() {
			if (!fieldSelect) return;
			
			var selectedField = fieldSelect.value;
			
			// Hide all prompt textareas
			document.querySelectorAll('.prompt-textarea-wrapper').forEach(function(wrapper) {
				wrapper.style.display = 'none';
			});
			
			// Show selected field textareas (for all languages)
			document.querySelectorAll('.prompt-textarea-wrapper[data-field="' + selectedField + '"]').forEach(function(wrapper) {
				wrapper.style.display = 'block';
			});
		}
		
		if (fieldSelect) {
			fieldSelect.addEventListener('change', updateVisibleTextareas);
			
			// Initialize on page load
			updateVisibleTextareas();
		}

		// Handle tab switching - ensure textareas stay visible when changing language
		var tabLinks = document.querySelectorAll('#prompt-language-tabs a[data-toggle="tab"]');
		tabLinks.forEach(function(tabLink) {
			tabLink.addEventListener('shown.bs.tab', updateVisibleTextareas);
			// Also support older Bootstrap
			$(tabLink).on('shown.bs.tab', updateVisibleTextareas);
		});

		// Save prompts button
		var saveBtn = document.getElementById('btn-save-prompts');
		if (saveBtn) {
			saveBtn.addEventListener('click', function() {
				var prompts = {};
				document.querySelectorAll('.prompt-textarea').forEach(function(textarea) {
					var parts = textarea.id.replace('prompt_', '').split('_');
					var fieldType = parts[0];
					var idLang = parts[1];
					
					if (!prompts[fieldType]) {
						prompts[fieldType] = {};
					}
					prompts[fieldType][idLang] = textarea.value;
				});

				fetch('{$ajax_url|escape:'javascript':'UTF-8'}', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: 'action=savePrompts&prompts=' + encodeURIComponent(JSON.stringify(prompts))
				})
				.then(function(response) { return response.json(); })
				.then(function(data) {
					if (data.success) {
						showSuccessMessage('{l s='Prompts saved successfully!' mod='mlcategoryaidescription' js=1}');
					} else {
						showErrorMessage(data.error || '{l s='Error saving prompts' mod='mlcategoryaidescription' js=1}');
					}
				})
				.catch(function(error) {
					showErrorMessage('{l s='Error saving prompts' mod='mlcategoryaidescription' js=1}');
				});
			});
		}
	});
})();
</script>
