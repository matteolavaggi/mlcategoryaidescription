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

{* Job Status & Cron Dashboard *}
<div class="panel" id="mlcategoryai-job-dashboard">
	<h3><i class="icon icon-tasks"></i> {l s='Background Jobs & Cron' mod='mlcategoryaidescription'}</h3>

	{* Active/Pending Jobs Section *}
	<div class="row">
		<div class="col-lg-8">
			<h4><i class="icon icon-list-alt"></i> {l s='Job Queue' mod='mlcategoryaidescription'}</h4>

			{if $pending_jobs && count($pending_jobs) > 0}
			<table class="table table-bordered table-striped" id="job-queue-table">
				<thead>
					<tr>
						<th style="width: 50px;">{l s='ID' mod='mlcategoryaidescription'}</th>
						<th>{l s='Status' mod='mlcategoryaidescription'}</th>
						<th>{l s='Progress' mod='mlcategoryaidescription'}</th>
						<th>{l s='Created' mod='mlcategoryaidescription'}</th>
						<th>{l s='Last Update' mod='mlcategoryaidescription'}</th>
						<th style="width: 150px;">{l s='Actions' mod='mlcategoryaidescription'}</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$pending_jobs item=job}
					<tr data-job-id="{$job.id_job|intval}">
						<td>#{$job.id_job|intval}</td>
						<td>
							{if $job.status == 'running'}
								{if $job.is_stuck}
								<span class="label label-warning" title="{l s='No updates for 5+ minutes. May need cron to resume.' mod='mlcategoryaidescription'}">
									<i class="icon icon-exclamation-triangle"></i> {l s='Stuck' mod='mlcategoryaidescription'}
								</span>
								{else}
								<span class="label label-info">
									<i class="icon icon-spinner icon-spin"></i> {l s='Running' mod='mlcategoryaidescription'}
								</span>
								{/if}
							{elseif $job.status == 'pending'}
							<span class="label label-default">{l s='Pending' mod='mlcategoryaidescription'}</span>
							{elseif $job.status == 'paused'}
							<span class="label label-warning">{l s='Paused' mod='mlcategoryaidescription'}</span>
							{elseif $job.status == 'completed'}
							<span class="label label-success">{l s='Completed' mod='mlcategoryaidescription'}</span>
							{elseif $job.status == 'failed'}
							<span class="label label-danger">{l s='Failed' mod='mlcategoryaidescription'}</span>
							{/if}
						</td>
						<td>
							<div class="progress" style="margin-bottom: 0; min-width: 100px;">
								{assign var="progress_pct" value=0}
								{if $job.total_items > 0}
									{assign var="progress_pct" value=($job.processed_items / $job.total_items * 100)|round}
								{/if}
								<div class="progress-bar {if $job.status == 'completed'}progress-bar-success{elseif $job.status == 'failed'}progress-bar-danger{else}progress-bar-info{/if}"
									role="progressbar" style="width: {$progress_pct|intval}%">
								</div>
							</div>
							<small>{$job.processed_items|intval} / {$job.total_items|intval}</small>
						</td>
						<td>
							<small>{$job.created_at|escape:'htmlall':'UTF-8'}</small>
						</td>
						<td>
							<small>{$job.updated_at|escape:'htmlall':'UTF-8'}</small>
							{if $job.is_stuck}
							<br><span class="text-warning"><i class="icon icon-clock-o"></i> {l s='Idle' mod='mlcategoryaidescription'}</span>
							{/if}
						</td>
						<td>
							{if $job.status == 'running' || $job.status == 'pending'}
							<button type="button" class="btn btn-xs btn-success btn-resume-browser" data-job-id="{$job.id_job|intval}"
								title="{l s='Resume in browser' mod='mlcategoryaidescription'}">
								<i class="icon icon-play"></i>
							</button>
							<button type="button" class="btn btn-xs btn-warning btn-pause-job" data-job-id="{$job.id_job|intval}"
								title="{l s='Pause' mod='mlcategoryaidescription'}">
								<i class="icon icon-pause"></i>
							</button>
							{elseif $job.status == 'paused'}
							<button type="button" class="btn btn-xs btn-success btn-resume-browser" data-job-id="{$job.id_job|intval}"
								title="{l s='Resume' mod='mlcategoryaidescription'}">
								<i class="icon icon-play"></i>
							</button>
							{/if}
							<button type="button" class="btn btn-xs btn-danger btn-delete-job" data-job-id="{$job.id_job|intval}"
								title="{l s='Delete' mod='mlcategoryaidescription'}">
								<i class="icon icon-trash"></i>
							</button>
						</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
			{else}
			<div class="alert alert-info">
				<i class="icon icon-info-circle"></i>
				{l s='No active or pending jobs. Create a new generation job below.' mod='mlcategoryaidescription'}
			</div>
			{/if}
		</div>

		{* Cron Setup Section *}
		<div class="col-lg-4">
			<div class="panel" style="background: #f9f9f9;">
				<h4><i class="icon icon-clock-o"></i> {l s='Cron Setup' mod='mlcategoryaidescription'}</h4>

				{if $cron_enabled}
				<p class="text-success"><i class="icon icon-check-circle"></i> {l s='Cron processing is enabled' mod='mlcategoryaidescription'}</p>
				{else}
				<p class="text-warning"><i class="icon icon-exclamation-triangle"></i> {l s='Cron processing is disabled' mod='mlcategoryaidescription'}</p>
				<p><small>{l s='Enable it in the settings below to allow background processing.' mod='mlcategoryaidescription'}</small></p>
				{/if}

				<div class="form-group">
					<label>{l s='Cron URL' mod='mlcategoryaidescription'}</label>
					<div class="input-group">
						<input type="text" class="form-control" id="cron-url-field" value="{$cron_url|escape:'htmlall':'UTF-8'}" readonly>
						<span class="input-group-btn">
							<button type="button" class="btn btn-default" id="btn-copy-cron-url" title="{l s='Copy to clipboard' mod='mlcategoryaidescription'}">
								<i class="icon icon-copy"></i>
							</button>
						</span>
					</div>
				</div>

				<div class="form-group">
					<label>{l s='Crontab Example' mod='mlcategoryaidescription'}</label>
					<pre style="font-size: 11px; white-space: pre-wrap; word-break: break-all;">* * * * * curl -s "{$cron_url|escape:'htmlall':'UTF-8'}" > /dev/null</pre>
					<small class="text-muted">{l s='This runs every minute. Adjust as needed.' mod='mlcategoryaidescription'}</small>
				</div>

				<hr>

				<h5>{l s='How it works' mod='mlcategoryaidescription'}</h5>
				<ol style="padding-left: 20px; font-size: 12px;">
					<li>{l s='Create a job using the form below' mod='mlcategoryaidescription'}</li>
					<li>{l s='Choose "Background (Cron)" processing mode' mod='mlcategoryaidescription'}</li>
					<li>{l s='The cron will automatically process pending jobs' mod='mlcategoryaidescription'}</li>
					<li>{l s='Web mode: 5 minute timeout per run. CLI mode: unlimited.' mod='mlcategoryaidescription'}</li>
					<li>{l s='Lock mechanism prevents concurrent executions' mod='mlcategoryaidescription'}</li>
				</ol>

				<div class="alert alert-success" style="font-size: 12px;">
					<strong>{l s='CLI Mode (Recommended for large jobs):' mod='mlcategoryaidescription'}</strong><br>
					<code style="display: block; margin-top: 5px; background: #f5f5f5; padding: 5px; font-size: 11px; word-break: break-all; overflow-wrap: break-word;">php {$smarty.const._PS_ROOT_DIR_|escape:'htmlall':'UTF-8'}/modules/mlcategoryaidescription/cron-cli.php</code>
					<small class="text-muted">{l s='No timeout, runs until job completes.' mod='mlcategoryaidescription'}</small>
				</div>

				<div class="alert alert-info" style="font-size: 12px;">
					<strong>{l s='Tip:' mod='mlcategoryaidescription'}</strong>
					{l s='If a browser job gets stuck (you closed the tab), cron will automatically resume it!' mod='mlcategoryaidescription'}
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
(function() {
	// Copy cron URL
	document.getElementById('btn-copy-cron-url')?.addEventListener('click', function() {
		var urlField = document.getElementById('cron-url-field');
		urlField.select();
		document.execCommand('copy');
		this.innerHTML = '<i class="icon icon-check"></i>';
		var btn = this;
		setTimeout(function() {
			btn.innerHTML = '<i class="icon icon-copy"></i>';
		}, 2000);
	});

	// Resume in browser
	document.querySelectorAll('.btn-resume-browser').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var jobId = this.getAttribute('data-job-id');
			window.MlCategoryAiJobResume = jobId;
			// Scroll to generation panel and trigger resume
			document.getElementById('mlcategoryai-batch-panel')?.scrollIntoView({ behavior: 'smooth' });
			// The back.js will pick up MlCategoryAiJobResume on init
			location.reload();
		});
	});

	// Pause job
	document.querySelectorAll('.btn-pause-job').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var jobId = this.getAttribute('data-job-id');
			fetch(window.mlcategoryai_ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: 'action=pauseJob&job_id=' + jobId
			}).then(function() { location.reload(); });
		});
	});

	// Delete job
	document.querySelectorAll('.btn-delete-job').forEach(function(btn) {
		btn.addEventListener('click', function() {
			if (!confirm('{l s='Delete this job?' mod='mlcategoryaidescription' js=1}')) return;
			var jobId = this.getAttribute('data-job-id');
			fetch(window.mlcategoryai_ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: 'action=deleteJob&job_id=' + jobId
			}).then(function() { location.reload(); });
		});
	});
})();
</script>
