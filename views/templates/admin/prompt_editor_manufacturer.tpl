{*
 * 2010-2026 2win.agency
 *
 * @author    2win.agency
 * @copyright 2010-2026 2win.agency
 *}

<div class="manufacturer-prompt-editor-container">
	<div class="row">
		<div class="col-lg-12">
			<div class="form-group">
				<label class="control-label">{l s='Select Field Type' mod='mlcategoryaidescription'}</label>
				<select id="prompt-mfr-field-type" class="form-control fixed-width-xl">
					{foreach from=$field_types key=field_key item=field_name}
					<option value="{$field_key|escape:'htmlall':'UTF-8'}">{$field_name|escape:'htmlall':'UTF-8'}</option>
					{/foreach}
				</select>
			</div>
		</div>
	</div>

	<ul class="nav nav-tabs" id="prompt-mfr-language-tabs">
		{foreach from=$languages item=lang name=langLoop}
		<li{if $smarty.foreach.langLoop.first} class="active"{/if}>
			<a href="#prompt-mfr-lang-{$lang.id_lang|escape:'htmlall':'UTF-8'}" data-toggle="tab">
				{if isset($lang.iso_code)}
					<img src="../img/l/{$lang.id_lang|escape:'htmlall':'UTF-8'}.jpg" alt="{$lang.iso_code|escape:'htmlall':'UTF-8'}" class="imgm">
				{/if}
				{$lang.name|escape:'htmlall':'UTF-8'}
			</a>
		</li>
		{/foreach}
	</ul>

	<div class="tab-content" id="prompt-mfr-tab-content">
		{foreach from=$languages item=lang name=langLoop}
		<div class="tab-pane{if $smarty.foreach.langLoop.first} active{/if}" id="prompt-mfr-lang-{$lang.id_lang|escape:'htmlall':'UTF-8'}">
			<div class="form-group" style="margin-top: 15px;">
				{foreach from=$field_types key=field_key item=field_name name=fieldLoop}
				<div class="prompt-mfr-textarea-wrapper" data-field="{$field_key|escape:'htmlall':'UTF-8'}" data-lang="{$lang.id_lang|escape:'htmlall':'UTF-8'}" {if $field_key != 'description'}style="display:none;"{/if}>
					<textarea
						class="form-control prompt-mfr-textarea"
						data-field="{$field_key|escape:'htmlall':'UTF-8'}"
						data-lang="{$lang.id_lang|escape:'htmlall':'UTF-8'}"
						id="mfr_prompt_{$field_key|escape:'htmlall':'UTF-8'}_{$lang.id_lang|escape:'htmlall':'UTF-8'}"
						name="mfr_prompt[{$field_key|escape:'htmlall':'UTF-8'}][{$lang.id_lang|escape:'htmlall':'UTF-8'}]"
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
		<button type="button" class="btn btn-primary" id="btn-save-mfr-prompts">
			<i class="icon icon-save"></i> {l s='Save Prompts' mod='mlcategoryaidescription'}
		</button>
		<button type="button" class="btn btn-default" id="btn-reset-mfr-prompt">
			<i class="icon icon-refresh"></i> {l s='Reset to Default' mod='mlcategoryaidescription'}
		</button>
		<button type="button" class="btn btn-info" id="btn-preview-mfr-prompt">
			<i class="icon icon-eye"></i> {l s='Preview' mod='mlcategoryaidescription'}
		</button>
	</div>

	<div id="prompt-mfr-preview-result" class="alert alert-info" style="display:none; margin-top: 15px;">
		<strong>{l s='Resolved Prompt Preview:' mod='mlcategoryaidescription'}</strong>
		<pre id="prompt-mfr-preview-content" style="margin-top: 10px; white-space: pre-wrap;"></pre>
	</div>
</div>

<script type="text/javascript">
(function() {
	document.addEventListener('DOMContentLoaded', function() {
		var fieldSelect = document.getElementById('prompt-mfr-field-type');
		var ajaxUrl = window.mlcategoryai_ajax_url || '{$ajax_url|escape:'javascript':'UTF-8'}';
		var ajaxToken = window.mlcategoryai_token || '';
		var entityType = 'manufacturer';

		function updateVisibleTextareas() {
			if (!fieldSelect) return;
			var selectedField = fieldSelect.value;
			document.querySelectorAll('.prompt-mfr-textarea-wrapper').forEach(function(wrapper) {
				wrapper.style.display = 'none';
			});
			document.querySelectorAll('.prompt-mfr-textarea-wrapper[data-field="' + selectedField + '"]').forEach(function(wrapper) {
				wrapper.style.display = 'block';
			});
		}

		if (fieldSelect) {
			fieldSelect.addEventListener('change', updateVisibleTextareas);
			updateVisibleTextareas();
		}

		var tabLinks = document.querySelectorAll('#prompt-mfr-language-tabs a[data-toggle="tab"]');
		tabLinks.forEach(function(tabLink) {
			tabLink.addEventListener('shown.bs.tab', updateVisibleTextareas);
			if (typeof jQuery !== 'undefined') {
				jQuery(tabLink).on('shown.bs.tab', updateVisibleTextareas);
			}
		});

		var saveBtn = document.getElementById('btn-save-mfr-prompts');
		if (saveBtn) {
			saveBtn.addEventListener('click', function() {
				var prompts = {};
				document.querySelectorAll('.prompt-mfr-textarea').forEach(function(textarea) {
					var fieldType = textarea.getAttribute('data-field');
					var idLang = textarea.getAttribute('data-lang');
					if (!fieldType || !idLang) return;
					if (!prompts[fieldType]) {
						prompts[fieldType] = {};
					}
					prompts[fieldType][idLang] = textarea.value;
				});

				var formData = new FormData();
				formData.append('action', 'savePrompts');
				formData.append('token', ajaxToken);
				formData.append('entity_type', entityType);
				formData.append('prompts', JSON.stringify(prompts));

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

		var resetBtn = document.getElementById('btn-reset-mfr-prompt');
		if (resetBtn) {
			resetBtn.addEventListener('click', function() {
				if (!confirm('{l s='Reset manufacturer prompts to defaults? Custom manufacturer prompts will be removed.' mod='mlcategoryaidescription' js=1}')) {
					return;
				}

				resetBtn.disabled = true;
				var originalHtml = resetBtn.innerHTML;
				resetBtn.innerHTML = '<i class="icon icon-spinner icon-spin"></i> {l s='Resetting...' mod='mlcategoryaidescription' js=1}';

				var formData = new FormData();
				formData.append('action', 'resetPrompts');
				formData.append('token', ajaxToken);
				formData.append('entity_type', entityType);

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

		var previewBtn = document.getElementById('btn-preview-mfr-prompt');
		if (previewBtn) {
			previewBtn.addEventListener('click', function() {
				var selectedField = fieldSelect ? fieldSelect.value : 'description';
				var activeTab = document.querySelector('#prompt-mfr-language-tabs li.active a');
				var idLang = 1;
				if (activeTab) {
					var href = activeTab.getAttribute('href');
					var match = href.match(/prompt-mfr-lang-(\d+)/);
					if (match) {
						idLang = parseInt(match[1], 10);
					}
				}

				var textarea = document.querySelector('.prompt-mfr-textarea-wrapper[data-field="' + selectedField + '"][data-lang="' + idLang + '"] .prompt-mfr-textarea');
				if (!textarea) {
					showErrorMessage('{l s='Could not find prompt textarea' mod='mlcategoryaidescription' js=1}');
					return;
				}

				var promptTemplate = textarea.value;
				if (!promptTemplate.trim()) {
					showErrorMessage('{l s='Prompt template is empty' mod='mlcategoryaidescription' js=1}');
					return;
				}

				var mfrCheckbox = document.querySelector('.manufacturer-checkbox:checked');
				var idManufacturer = mfrCheckbox ? mfrCheckbox.value : 0;

				if (!idManufacturer) {
					showErrorMessage('{l s='Please select a manufacturer first to preview the prompt' mod='mlcategoryaidescription' js=1}');
					return;
				}

				previewBtn.disabled = true;
				var originalHtml = previewBtn.innerHTML;
				previewBtn.innerHTML = '<i class="icon icon-spinner icon-spin"></i> {l s='Loading...' mod='mlcategoryaidescription' js=1}';

				var formData = new FormData();
				formData.append('action', 'previewPrompt');
				formData.append('token', ajaxToken);
				formData.append('entity_type', entityType);
				formData.append('id_manufacturer', idManufacturer);
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

					var previewResult = document.getElementById('prompt-mfr-preview-result');
					var previewContent = document.getElementById('prompt-mfr-preview-content');

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
