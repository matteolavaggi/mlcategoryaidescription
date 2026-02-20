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
		var ajaxUrl = window.mlcategoryai_ajax_url || '{$ajax_url|escape:'javascript':'UTF-8'}';
		var ajaxToken = window.mlcategoryai_token || '';
		
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

		// Save prompts button - using FormData for PS 1.7.x compatibility
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

				// Use FormData for better PS 1.7.x compatibility
				var formData = new FormData();
				formData.append('action', 'savePrompts');
				formData.append('token', ajaxToken);
				formData.append('prompts', JSON.stringify(prompts));

				// Disable button while saving
				saveBtn.disabled = true;
				var originalHtml = saveBtn.innerHTML;
				saveBtn.innerHTML = '<i class="icon icon-spinner icon-spin"></i> {l s='Saving...' mod='mlcategoryaidescription' js=1}';

				fetch(ajaxUrl, {
					method: 'POST',
					body: formData
				})
				.then(function(response) { return response.json(); })
				.then(function(data) {
					saveBtn.disabled = false;
					saveBtn.innerHTML = originalHtml;
					if (data.success) {
						showSuccessMessage('{l s='Prompts saved successfully!' mod='mlcategoryaidescription' js=1}');
					} else {
						showErrorMessage(data.error || '{l s='Error saving prompts' mod='mlcategoryaidescription' js=1}');
					}
				})
				.catch(function(error) {
					saveBtn.disabled = false;
					saveBtn.innerHTML = originalHtml;
					console.error('Save prompts error:', error);
					showErrorMessage('{l s='Error saving prompts' mod='mlcategoryaidescription' js=1}');
				});
			});
		}

		// Reset to default button
		var resetBtn = document.getElementById('btn-reset-prompt');
		if (resetBtn) {
			resetBtn.addEventListener('click', function() {
				if (!confirm('{l s='Are you sure you want to reset all prompts to default? This will overwrite your custom prompts.' mod='mlcategoryaidescription' js=1}')) {
					return;
				}

				// Disable button while resetting
				resetBtn.disabled = true;
				var originalHtml = resetBtn.innerHTML;
				resetBtn.innerHTML = '<i class="icon icon-spinner icon-spin"></i> {l s='Resetting...' mod='mlcategoryaidescription' js=1}';

				var formData = new FormData();
				formData.append('action', 'resetPrompts');
				formData.append('token', ajaxToken);

				fetch(ajaxUrl, {
					method: 'POST',
					body: formData
				})
				.then(function(response) { return response.json(); })
				.then(function(data) {
					resetBtn.disabled = false;
					resetBtn.innerHTML = originalHtml;
					if (data.success) {
						showSuccessMessage('{l s='Prompts reset to default successfully! Reloading page...' mod='mlcategoryaidescription' js=1}');
						setTimeout(function() {
							location.reload();
						}, 1500);
					} else {
						showErrorMessage(data.error || data.message || '{l s='Error resetting prompts' mod='mlcategoryaidescription' js=1}');
					}
				})
				.catch(function(error) {
					resetBtn.disabled = false;
					resetBtn.innerHTML = originalHtml;
					console.error('Reset prompts error:', error);
					showErrorMessage('{l s='Error resetting prompts' mod='mlcategoryaidescription' js=1}');
				});
			});
		}

		// Preview prompt button
		var previewBtn = document.getElementById('btn-preview-prompt');
		if (previewBtn) {
			previewBtn.addEventListener('click', function() {
				// Get current field type and active language tab
				var selectedField = fieldSelect ? fieldSelect.value : 'description';
				var activeTab = document.querySelector('#prompt-language-tabs li.active a');
				var idLang = 1; // Default
				
				if (activeTab) {
					var href = activeTab.getAttribute('href');
					var match = href.match(/prompt-lang-(\d+)/);
					if (match) {
						idLang = parseInt(match[1], 10);
					}
				}

				// Get the prompt template from the visible textarea
				var textarea = document.querySelector('.prompt-textarea-wrapper[data-field="' + selectedField + '"][data-lang="' + idLang + '"] .prompt-textarea');
				if (!textarea) {
					showErrorMessage('{l s='Could not find prompt textarea' mod='mlcategoryaidescription' js=1}');
					return;
				}

				var promptTemplate = textarea.value;
				if (!promptTemplate.trim()) {
					showErrorMessage('{l s='Prompt template is empty' mod='mlcategoryaidescription' js=1}');
					return;
				}

				// Get first selected category or use a default
				var categoryCheckbox = document.querySelector('.category-checkbox:checked');
				var idCategory = categoryCheckbox ? categoryCheckbox.value : 0;

				if (!idCategory) {
					showErrorMessage('{l s='Please select a category first to preview the prompt' mod='mlcategoryaidescription' js=1}');
					return;
				}

				// Disable button while loading
				previewBtn.disabled = true;
				var originalHtml = previewBtn.innerHTML;
				previewBtn.innerHTML = '<i class="icon icon-spinner icon-spin"></i> {l s='Loading...' mod='mlcategoryaidescription' js=1}';

				var formData = new FormData();
				formData.append('action', 'previewPrompt');
				formData.append('token', ajaxToken);
				formData.append('id_category', idCategory);
				formData.append('id_lang', idLang);
				formData.append('prompt_template', promptTemplate);

				fetch(ajaxUrl, {
					method: 'POST',
					body: formData
				})
				.then(function(response) { return response.json(); })
				.then(function(data) {
					previewBtn.disabled = false;
					previewBtn.innerHTML = originalHtml;
					
					var previewResult = document.getElementById('prompt-preview-result');
					var previewContent = document.getElementById('prompt-preview-content');
					
					if (data.success && previewResult && previewContent) {
						previewContent.textContent = data.resolved_prompt;
						previewResult.style.display = 'block';
						previewResult.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
					} else {
						showErrorMessage(data.error || '{l s='Error previewing prompt' mod='mlcategoryaidescription' js=1}');
					}
				})
				.catch(function(error) {
					previewBtn.disabled = false;
					previewBtn.innerHTML = originalHtml;
					console.error('Preview prompt error:', error);
					showErrorMessage('{l s='Error previewing prompt' mod='mlcategoryaidescription' js=1}');
				});
			});
		}
	});
})();
</script>
