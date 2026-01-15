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

{* API Latency Benchmark Panel *}
<div class="panel" id="mlcategoryai-benchmark-panel">
	<h3><i class="icon icon-dashboard"></i> {l s='API Latency Benchmark' mod='mlcategoryaidescription'}</h3>
	<p class="help-block">{l s='Test response time of different models with a minimal prompt. Useful to find the fastest model for your use case.' mod='mlcategoryaidescription'}</p>

	<div class="row">
		<div class="col-lg-6">
			<div class="form-group">
				<label class="control-label">{l s='Models to Test' mod='mlcategoryaidescription'}</label>
				<input type="text" class="form-control benchmark-model" id="benchmark-model-1" value="gpt-4o-mini" placeholder="Model 1">
				<input type="text" class="form-control benchmark-model" id="benchmark-model-2" value="gpt-4o" placeholder="Model 2" style="margin-top: 5px;">
				<input type="text" class="form-control benchmark-model" id="benchmark-model-3" value="gpt-4-turbo" placeholder="Model 3" style="margin-top: 5px;">
				<input type="text" class="form-control benchmark-model" id="benchmark-model-4" value="gpt-3.5-turbo" placeholder="Model 4" style="margin-top: 5px;">
				<input type="text" class="form-control benchmark-model" id="benchmark-model-5" value="" placeholder="Model 5 (optional)" style="margin-top: 5px;">
			</div>
		</div>
		<div class="col-lg-6">
			<div class="form-group">
				<label class="control-label">{l s='Test Prompt' mod='mlcategoryaidescription'}</label>
				<input type="text" class="form-control" id="benchmark-prompt" value="Say hello in 3 words." placeholder="Simple test prompt">
				<p class="help-block">{l s='Keep it short for accurate latency measurement' mod='mlcategoryaidescription'}</p>
			</div>
			<button type="button" class="btn btn-primary" id="btn-run-benchmark">
				<i class="icon icon-play"></i> {l s='Run Benchmark' mod='mlcategoryaidescription'}
			</button>
			<button type="button" class="btn btn-default" id="btn-clear-benchmark" style="margin-left: 5px;">
				<i class="icon icon-trash"></i> {l s='Clear Results' mod='mlcategoryaidescription'}
			</button>
		</div>
	</div>

	{* Results Table *}
	<div id="benchmark-results" style="margin-top: 20px; display: none;">
		<h4><i class="icon icon-bar-chart"></i> {l s='Results' mod='mlcategoryaidescription'}</h4>
		<table class="table table-bordered table-striped" id="benchmark-results-table">
			<thead>
				<tr>
					<th style="width: 30%;">{l s='Model' mod='mlcategoryaidescription'}</th>
					<th style="width: 15%;">{l s='Latency' mod='mlcategoryaidescription'}</th>
					<th style="width: 15%;">{l s='Tokens' mod='mlcategoryaidescription'}</th>
					<th style="width: 10%;">{l s='Status' mod='mlcategoryaidescription'}</th>
					<th>{l s='Response' mod='mlcategoryaidescription'}</th>
				</tr>
			</thead>
			<tbody id="benchmark-results-body">
			</tbody>
		</table>
	</div>

	{* Progress indicator *}
	<div id="benchmark-progress" style="margin-top: 20px; display: none;">
		<div class="alert alert-info">
			<i class="icon icon-spinner icon-spin"></i>
			<span id="benchmark-progress-text">{l s='Testing models...' mod='mlcategoryaidescription'}</span>
		</div>
	</div>
</div>

<script type="text/javascript">
(function() {
	var ajaxUrl = window.mlcategoryai_ajax_url;
	var runBtn = document.getElementById('btn-run-benchmark');
	var clearBtn = document.getElementById('btn-clear-benchmark');
	var resultsDiv = document.getElementById('benchmark-results');
	var resultsBody = document.getElementById('benchmark-results-body');
	var progressDiv = document.getElementById('benchmark-progress');
	var progressText = document.getElementById('benchmark-progress-text');

	runBtn.addEventListener('click', function() {
		var models = [];
		for (var i = 1; i <= 5; i++) {
			var model = document.getElementById('benchmark-model-' + i).value.trim();
			if (model) models.push(model);
		}

		if (models.length === 0) {
			alert('Please enter at least one model to test');
			return;
		}

		var prompt = document.getElementById('benchmark-prompt').value.trim();
		if (!prompt) prompt = 'Say hello';

		// Show progress, hide results
		progressDiv.style.display = 'block';
		resultsDiv.style.display = 'none';
		resultsBody.innerHTML = '';
		runBtn.disabled = true;

		// Add placeholder rows
		models.forEach(function(model) {
			var row = document.createElement('tr');
			row.id = 'result-' + model.replace(/[^a-z0-9]/gi, '-');
			row.innerHTML = '<td><strong>' + escapeHtml(model) + '</strong></td>' +
				'<td><i class="icon icon-spinner icon-spin"></i> Testing...</td>' +
				'<td>-</td><td>-</td><td>-</td>';
			resultsBody.appendChild(row);
		});

		resultsDiv.style.display = 'block';

		// Run tests in parallel
		var completed = 0;
		var results = [];

		models.forEach(function(model, index) {
			progressText.textContent = 'Testing ' + (index + 1) + ' of ' + models.length + ' models...';

			var startTime = performance.now();

			var xhr = new XMLHttpRequest();
			xhr.open('POST', ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

			xhr.onload = function() {
				var endTime = performance.now();
				var latency = Math.round(endTime - startTime);
				var rowId = 'result-' + model.replace(/[^a-z0-9]/gi, '-');
				var row = document.getElementById(rowId);

				try {
					var response = JSON.parse(xhr.responseText);
					if (response.success) {
						row.innerHTML = '<td><strong>' + escapeHtml(model) + '</strong></td>' +
							'<td><span class="badge" style="background: ' + getLatencyColor(latency) + ';">' + latency + ' ms</span></td>' +
							'<td>' + (response.tokens_in || 0) + ' / ' + (response.tokens_out || 0) + '</td>' +
							'<td><span class="label label-success">OK</span></td>' +
							'<td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' + escapeHtml(response.content || '') + '">' + escapeHtml((response.content || '').substring(0, 100)) + '</td>';
						results.push({ model: model, latency: latency, success: true });
					} else {
						row.innerHTML = '<td><strong>' + escapeHtml(model) + '</strong></td>' +
							'<td><span class="badge" style="background: #999;">' + latency + ' ms</span></td>' +
							'<td>-</td>' +
							'<td><span class="label label-danger">Error</span></td>' +
							'<td style="color: red;">' + escapeHtml(response.error || 'Unknown error') + '</td>';
						results.push({ model: model, latency: latency, success: false });
					}
				} catch (e) {
					row.innerHTML = '<td><strong>' + escapeHtml(model) + '</strong></td>' +
						'<td>-</td><td>-</td>' +
						'<td><span class="label label-danger">Error</span></td>' +
						'<td style="color: red;">Parse error</td>';
					results.push({ model: model, latency: 0, success: false });
				}

				completed++;
				if (completed === models.length) {
					progressDiv.style.display = 'none';
					runBtn.disabled = false;
					sortResults(results);
				}
			};

			xhr.onerror = function() {
				var rowId = 'result-' + model.replace(/[^a-z0-9]/gi, '-');
				var row = document.getElementById(rowId);
				row.innerHTML = '<td><strong>' + escapeHtml(model) + '</strong></td>' +
					'<td>-</td><td>-</td>' +
					'<td><span class="label label-danger">Error</span></td>' +
					'<td style="color: red;">Network error</td>';
				completed++;
				if (completed === models.length) {
					progressDiv.style.display = 'none';
					runBtn.disabled = false;
				}
			};

			xhr.send('action=benchmarkModel&model=' + encodeURIComponent(model) + '&prompt=' + encodeURIComponent(prompt));
		});
	});

	clearBtn.addEventListener('click', function() {
		resultsBody.innerHTML = '';
		resultsDiv.style.display = 'none';
	});

	function getLatencyColor(latency) {
		if (latency < 1000) return '#27ae60'; // Green - fast
		if (latency < 3000) return '#f39c12'; // Orange - medium
		if (latency < 5000) return '#e67e22'; // Dark orange - slow
		return '#e74c3c'; // Red - very slow
	}

	function sortResults(results) {
		// Sort by latency (fastest first), move failed to end
		results.sort(function(a, b) {
			if (!a.success && !b.success) return 0;
			if (!a.success) return 1;
			if (!b.success) return -1;
			return a.latency - b.latency;
		});

		// Reorder table rows
		results.forEach(function(r) {
			var rowId = 'result-' + r.model.replace(/[^a-z0-9]/gi, '-');
			var row = document.getElementById(rowId);
			if (row) resultsBody.appendChild(row);
		});

		// Highlight fastest
		if (results.length > 0 && results[0].success) {
			var fastestRowId = 'result-' + results[0].model.replace(/[^a-z0-9]/gi, '-');
			var fastestRow = document.getElementById(fastestRowId);
			if (fastestRow) {
				fastestRow.style.backgroundColor = '#d4edda';
				var badge = fastestRow.querySelector('.badge');
				if (badge) badge.innerHTML = '🏆 ' + results[0].latency + ' ms';
			}
		}
	}

	function escapeHtml(text) {
		var div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}
})();
</script>
