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

{* Performance Statistics Panel *}
<div class="panel" id="mlcategoryai-stats-panel">
	<h3><i class="icon icon-bar-chart"></i> {l s='Performance Statistics' mod='mlcategoryaidescription'}</h3>

	{* Aggregate Statistics *}
	{if $run_stats_aggregate && $run_stats_aggregate.total_runs > 0}
	<div class="row">
		<div class="col-lg-2 col-md-4 col-sm-6">
			<div class="panel panel-stats" style="background: #f5f5f5; text-align: center; padding: 15px;">
				<div style="font-size: 24px; font-weight: bold; color: #25b9d7;">
					{$run_stats_aggregate.total_runs|intval}
				</div>
				<div style="font-size: 12px; color: #666;">
					{l s='Total Runs' mod='mlcategoryaidescription'}
				</div>
			</div>
		</div>
		<div class="col-lg-2 col-md-4 col-sm-6">
			<div class="panel panel-stats" style="background: #f5f5f5; text-align: center; padding: 15px;">
				<div style="font-size: 24px; font-weight: bold; color: #72c279;">
					{$run_stats_aggregate.total_items|intval}
				</div>
				<div style="font-size: 12px; color: #666;">
					{l s='Items Generated' mod='mlcategoryaidescription'}
				</div>
			</div>
		</div>
		<div class="col-lg-2 col-md-4 col-sm-6">
			<div class="panel panel-stats" style="background: #f5f5f5; text-align: center; padding: 15px;">
				<div style="font-size: 24px; font-weight: bold; color: #f39c12;">
					{($run_stats_aggregate.total_tokens_in + $run_stats_aggregate.total_tokens_out)|intval|number_format:0:'.':','}
				</div>
				<div style="font-size: 12px; color: #666;">
					{l s='Total Tokens' mod='mlcategoryaidescription'}
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 col-sm-6">
			<div class="panel panel-stats" style="background: #f5f5f5; text-align: center; padding: 15px;">
				<div style="font-size: 24px; font-weight: bold; color: #9b59b6;">
					{if $run_stats_aggregate.avg_execution_time_ms > 60000}
						{($run_stats_aggregate.avg_execution_time_ms / 60000)|floatval|number_format:1}m
					{elseif $run_stats_aggregate.avg_execution_time_ms > 1000}
						{($run_stats_aggregate.avg_execution_time_ms / 1000)|floatval|number_format:1}s
					{else}
						{$run_stats_aggregate.avg_execution_time_ms|intval}ms
					{/if}
				</div>
				<div style="font-size: 12px; color: #666;">
					{l s='Avg Execution Time' mod='mlcategoryaidescription'}
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-md-6 col-sm-6">
			<div class="panel panel-stats" style="background: #f5f5f5; text-align: center; padding: 15px;">
				<div style="font-size: 24px; font-weight: bold; color: #e74c3c;">
					{if $run_stats_aggregate.avg_request_time_ms > 1000}
						{($run_stats_aggregate.avg_request_time_ms / 1000)|floatval|number_format:2}s
					{else}
						{$run_stats_aggregate.avg_request_time_ms|intval}ms
					{/if}
				</div>
				<div style="font-size: 12px; color: #666;">
					{l s='Avg API Latency' mod='mlcategoryaidescription'}
				</div>
			</div>
		</div>
	</div>
	{/if}

	{* Recent Runs Table *}
	{if $run_stats && count($run_stats) > 0}
	<h4 style="margin-top: 20px;"><i class="icon icon-history"></i> {l s='Recent Runs' mod='mlcategoryaidescription'}</h4>
	<div class="table-responsive">
		<table class="table table-bordered table-striped">
			<thead>
				<tr>
					<th>{l s='Date' mod='mlcategoryaidescription'}</th>
					<th>{l s='Categories' mod='mlcategoryaidescription'}</th>
					<th>{l s='Languages' mod='mlcategoryaidescription'}</th>
					<th>{l s='Fields' mod='mlcategoryaidescription'}</th>
					<th>{l s='Processed' mod='mlcategoryaidescription'}</th>
					<th>{l s='Skipped' mod='mlcategoryaidescription'}</th>
					<th>{l s='Failed' mod='mlcategoryaidescription'}</th>
					<th>{l s='Tokens (In/Out)' mod='mlcategoryaidescription'}</th>
					<th>{l s='Time' mod='mlcategoryaidescription'}</th>
					<th>{l s='Mode' mod='mlcategoryaidescription'}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$run_stats item=run}
				<tr>
					<td>
						<span title="{$run.started_at|escape:'htmlall':'UTF-8'}">
							{$run.started_at|date_format:'%d %b %H:%M'|escape:'htmlall':'UTF-8'}
						</span>
					</td>
					<td class="text-center">{$run.categories_count|intval}</td>
					<td class="text-center">{$run.languages_count|intval}</td>
					<td class="text-center">{$run.fields_count|intval}</td>
					<td class="text-center">
						<span class="badge badge-success" style="background: #72c279;">{$run.items_processed|intval}</span>
					</td>
					<td class="text-center">
						{if $run.items_skipped > 0}
						<span class="badge badge-warning" style="background: #f39c12;">{$run.items_skipped|intval}</span>
						{else}
						<span class="text-muted">0</span>
						{/if}
					</td>
					<td class="text-center">
						{if $run.items_failed > 0}
						<span class="badge badge-danger" style="background: #e74c3c;">{$run.items_failed|intval}</span>
						{else}
						<span class="text-muted">0</span>
						{/if}
					</td>
					<td class="text-right" style="font-family: monospace; font-size: 11px;">
						{$run.tokens_input|intval|number_format:0:'.':','} / {$run.tokens_output|intval|number_format:0:'.':','}
					</td>
					<td class="text-right">
						{if $run.execution_time_ms > 60000}
							{($run.execution_time_ms / 60000)|floatval|string_format:"%.1f"}m
						{elseif $run.execution_time_ms > 1000}
							{($run.execution_time_ms / 1000)|floatval|string_format:"%.1f"}s
						{else}
							{$run.execution_time_ms|intval}ms
						{/if}
					</td>
					<td>
						{if $run.parallel_requests > 1}
						<span class="label label-info" title="{l s='Parallel processing' mod='mlcategoryaidescription'}">
							<i class="icon icon-bolt"></i> {$run.parallel_requests|intval}x
						</span>
						{else}
						<span class="label label-default">{l s='Sequential' mod='mlcategoryaidescription'}</span>
						{/if}
					</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
	{else}
	<div class="alert alert-info">
		<i class="icon icon-info-circle"></i>
		{l s='No generation runs recorded yet. Run your first batch generation to see performance statistics here.' mod='mlcategoryaidescription'}
	</div>
	{/if}

	{* Debug Log Section *}
	<hr>
	<div class="row" style="margin-top: 15px;">
		<div class="col-lg-12">
			<h4><i class="icon icon-file-text-o"></i> {l s='Debug Log' mod='mlcategoryaidescription'}</h4>
			<p class="help-block">{l s='View detailed processing logs for debugging API errors and performance issues.' mod='mlcategoryaidescription'}</p>
			<button type="button" class="btn btn-default" id="btn-view-debug-log">
				<i class="icon icon-eye"></i> {l s='View Log' mod='mlcategoryaidescription'}
			</button>
			<button type="button" class="btn btn-default" id="btn-refresh-debug-log" style="display: none;">
				<i class="icon icon-refresh"></i> {l s='Refresh' mod='mlcategoryaidescription'}
			</button>
			<button type="button" class="btn btn-warning" id="btn-clear-debug-log" style="display: none;">
				<i class="icon icon-trash"></i> {l s='Clear Log' mod='mlcategoryaidescription'}
			</button>
			<span id="debug-log-size" class="text-muted" style="margin-left: 15px;"></span>
		</div>
	</div>
	<div id="debug-log-container" style="display: none; margin-top: 15px;">
		<pre id="debug-log-content" style="max-height: 500px; overflow-y: auto; background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; font-size: 11px; font-family: 'Consolas', 'Monaco', monospace;"></pre>
	</div>
</div>

<script type="text/javascript">
(function() {
	var ajaxUrl = window.mlcategoryai_ajax_url;
	var viewBtn = document.getElementById('btn-view-debug-log');
	var refreshBtn = document.getElementById('btn-refresh-debug-log');
	var clearBtn = document.getElementById('btn-clear-debug-log');
	var logContainer = document.getElementById('debug-log-container');
	var logContent = document.getElementById('debug-log-content');
	var logSize = document.getElementById('debug-log-size');

	function loadLog() {
		logContent.textContent = 'Loading...';

		fetch(ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: 'action=getDebugLog&lines=500'
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.success) {
				logContent.textContent = data.content || '(empty log)';
				logSize.textContent = 'Size: ' + data.size_formatted;
				// Scroll to bottom
				logContent.scrollTop = logContent.scrollHeight;
			} else {
				logContent.textContent = 'Error: ' + (data.error || 'Unknown error');
			}
		})
		.catch(function(error) {
			logContent.textContent = 'Error loading log: ' + error;
		});
	}

	viewBtn.addEventListener('click', function() {
		logContainer.style.display = 'block';
		refreshBtn.style.display = 'inline-block';
		clearBtn.style.display = 'inline-block';
		viewBtn.style.display = 'none';
		loadLog();
	});

	refreshBtn.addEventListener('click', loadLog);

	clearBtn.addEventListener('click', function() {
		if (!confirm('{l s='Clear all debug logs?' mod='mlcategoryaidescription' js=1}')) return;

		fetch(ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: 'action=clearDebugLog'
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.success) {
				logContent.textContent = '(log cleared)';
				logSize.textContent = 'Size: 0 B';
			}
		});
	});
})();
</script>
