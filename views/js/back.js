/**
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
 */

/**
 * ML Category AI Description - Back Office JavaScript
 */
(function () {
    'use strict';

    var MlCategoryAi = {
        ajaxUrl: null,
        token: null,
        currentJobId: null,
        isProcessing: false,

        init: function () {
            this.ajaxUrl = window.mlcategoryai_ajax_url || '';
            this.token = window.mlcategoryai_token || '';

            this.bindEvents();
            this.checkExistingJob();
        },

        bindEvents: function () {
            var self = this;

            // Start generation button
            document.getElementById('btn-start-generation')?.addEventListener('click', function () {
                self.startGeneration('browser');
            });

            // Start background button
            document.getElementById('btn-start-background')?.addEventListener('click', function () {
                self.startGeneration('background');
            });

            // Processing mode toggle
            document.getElementById('processing-mode-select')?.addEventListener('change', function () {
                var mode = this.value;
                var browserBtn = document.getElementById('btn-start-generation');
                var backgroundBtn = document.getElementById('btn-start-background');
                var helpBrowser = document.getElementById('help-browser');
                var helpBackground = document.getElementById('help-background');

                if (mode === 'background') {
                    browserBtn.style.display = 'none';
                    backgroundBtn.style.display = 'inline-block';
                    helpBrowser.style.display = 'none';
                    helpBackground.style.display = 'inline';
                } else {
                    browserBtn.style.display = 'inline-block';
                    backgroundBtn.style.display = 'none';
                    helpBrowser.style.display = 'inline';
                    helpBackground.style.display = 'none';
                }
            });

            // Test API button
            document.getElementById('btn-test-api')?.addEventListener('click', function () {
                self.testApiConnection();
            });

            // Pause job button
            document.getElementById('btn-pause-job')?.addEventListener('click', function () {
                var jobId = this.getAttribute('data-job-id');
                self.pauseJob(jobId);
            });

            // Resume job button
            document.getElementById('btn-resume-job')?.addEventListener('click', function () {
                var jobId = this.getAttribute('data-job-id');
                self.resumeJob(jobId);
            });

            // Cancel job button
            document.getElementById('btn-cancel-job')?.addEventListener('click', function () {
                var jobId = this.getAttribute('data-job-id');
                if (confirm('Are you sure you want to cancel this job?')) {
                    self.cancelJob(jobId);
                }
            });

            // Category tree: Select all categories
            document.getElementById('select-all-categories')?.addEventListener('click', function () {
                var checkboxes = document.querySelectorAll('.category-checkbox');
                checkboxes.forEach(function (cb) {
                    cb.checked = true;
                });
                self.updateCategoryCount();
            });

            // Category tree: Deselect all categories
            document.getElementById('deselect-all-categories')?.addEventListener('click', function () {
                var checkboxes = document.querySelectorAll('.category-checkbox');
                checkboxes.forEach(function (cb) {
                    cb.checked = false;
                });
                self.updateCategoryCount();
            });

            // Category tree: Expand all
            document.getElementById('expand-all-categories')?.addEventListener('click', function () {
                var toggles = document.querySelectorAll('.mlcatai-tree-toggle');
                toggles.forEach(function (toggle) {
                    var node = toggle.closest('.mlcatai-tree-node');
                    var children = node.querySelector('.mlcatai-tree-children');
                    if (children) {
                        children.classList.remove('collapsed');
                        toggle.setAttribute('data-expanded', 'true');
                        toggle.innerHTML = '<i class="icon icon-minus-square-o"></i>';
                    }
                });
            });

            // Category tree: Collapse all
            document.getElementById('collapse-all-categories')?.addEventListener('click', function () {
                var toggles = document.querySelectorAll('.mlcatai-tree-toggle');
                toggles.forEach(function (toggle) {
                    var node = toggle.closest('.mlcatai-tree-node');
                    var children = node.querySelector('.mlcatai-tree-children');
                    if (children) {
                        children.classList.add('collapsed');
                        toggle.setAttribute('data-expanded', 'false');
                        toggle.innerHTML = '<i class="icon icon-plus-square-o"></i>';
                    }
                });
            });

            // Category tree: Toggle expand/collapse
            document.querySelectorAll('.mlcatai-tree-toggle').forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    var node = this.closest('.mlcatai-tree-node');
                    var children = node.querySelector('.mlcatai-tree-children');
                    if (children) {
                        var isExpanded = this.getAttribute('data-expanded') === 'true';
                        if (isExpanded) {
                            children.classList.add('collapsed');
                            this.setAttribute('data-expanded', 'false');
                            this.innerHTML = '<i class="icon icon-plus-square-o"></i>';
                        } else {
                            children.classList.remove('collapsed');
                            this.setAttribute('data-expanded', 'true');
                            this.innerHTML = '<i class="icon icon-minus-square-o"></i>';
                        }
                    }
                });
            });

            // Category tree: Select all subcategories button
            document.querySelectorAll('.mlcatai-select-children').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var node = this.closest('.mlcatai-tree-node');
                    var checkboxes = node.querySelectorAll('.category-checkbox');
                    var allChecked = Array.from(checkboxes).every(function (cb) {
                        return cb.checked;
                    });
                    checkboxes.forEach(function (cb) {
                        cb.checked = !allChecked;
                    });
                    self.updateCategoryCount();
                });
            });

            // Category tree: Update count on checkbox change
            document.querySelectorAll('.category-checkbox').forEach(function (cb) {
                cb.addEventListener('change', function () {
                    self.updateCategoryCount();
                });
            });

            // Category search
            var searchInput = document.getElementById('category-search');
            var searchTimeout = null;
            searchInput?.addEventListener('input', function () {
                var query = this.value.toLowerCase().trim();
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    self.filterCategories(query);
                }, 150);
            });

            // Clear search button
            document.getElementById('clear-category-search')?.addEventListener('click', function () {
                var searchInput = document.getElementById('category-search');
                if (searchInput) {
                    searchInput.value = '';
                    self.filterCategories('');
                }
            });

            // Select all languages
            document.getElementById('select-all-languages')?.addEventListener('change', function () {
                var checkboxes = document.querySelectorAll('.lang-checkbox');
                checkboxes.forEach(function (cb) {
                    cb.checked = this.checked;
                }, this);
            });

            // Initialize category count and row colors
            this.updateCategoryCount();
            this.applyAlternatingRowColors();
        },

        applyAlternatingRowColors: function () {
            // Apply alternating row colors to ALL visible rows (regardless of hierarchy)
            var items = document.querySelectorAll('.mlcatai-tree-item');
            var visibleIndex = 0;
            items.forEach(function (item) {
                var node = item.closest('.mlcatai-tree-node');
                // Only count visible items
                if (!node.classList.contains('search-hidden')) {
                    item.classList.remove('row-odd', 'row-even');
                    item.classList.add(visibleIndex % 2 === 0 ? 'row-even' : 'row-odd');
                    visibleIndex++;
                }
            });
        },

        updateCategoryCount: function () {
            var count = document.querySelectorAll('.category-checkbox:checked').length;
            var countSpan = document.getElementById('selected-categories-count');
            if (countSpan) {
                countSpan.textContent = count;
            }
        },

        filterCategories: function (query) {
            var nodes = document.querySelectorAll('.mlcatai-tree-node');
            var names = document.querySelectorAll('.mlcatai-tree-name');

            // Remove all highlights
            names.forEach(function (name) {
                name.classList.remove('search-match');
            });

            if (!query) {
                // Show all nodes
                nodes.forEach(function (node) {
                    node.classList.remove('search-hidden');
                });
                this.applyAlternatingRowColors();
                return;
            }

            // Hide all first
            nodes.forEach(function (node) {
                node.classList.add('search-hidden');
            });

            // Show matching nodes and their ancestors
            nodes.forEach(function (node) {
                var name = node.querySelector(':scope > .mlcatai-tree-item .mlcatai-tree-name');
                var checkbox = node.querySelector(':scope > .mlcatai-tree-item .category-checkbox');
                if (name && checkbox) {
                    var categoryName = checkbox.getAttribute('data-name') || '';
                    if (categoryName.includes(query)) {
                        // Show this node
                        node.classList.remove('search-hidden');
                        name.classList.add('search-match');

                        // Show all ancestors
                        var parent = node.parentElement;
                        while (parent) {
                            if (parent.classList && parent.classList.contains('mlcatai-tree-node')) {
                                parent.classList.remove('search-hidden');
                            }
                            if (parent.classList && parent.classList.contains('mlcatai-tree-children')) {
                                parent.classList.remove('collapsed');
                                var toggle = parent.previousElementSibling?.querySelector('.mlcatai-tree-toggle');
                                if (toggle) {
                                    toggle.setAttribute('data-expanded', 'true');
                                    toggle.innerHTML = '<i class="icon icon-minus-square-o"></i>';
                                }
                            }
                            parent = parent.parentElement;
                        }

                        // Show all descendants
                        var descendants = node.querySelectorAll('.mlcatai-tree-node');
                        descendants.forEach(function (desc) {
                            desc.classList.remove('search-hidden');
                        });
                    }
                }
            });

            // Re-apply alternating colors after filtering
            this.applyAlternatingRowColors();
        },

        checkExistingJob: function () {
            var jobPanel = document.getElementById('mlcategoryai-current-job');
            if (jobPanel) {
                var pauseBtn = document.getElementById('btn-pause-job');
                var resumeBtn = document.getElementById('btn-resume-job');
                var statusSpan = document.getElementById('job-status');

                if (statusSpan && statusSpan.textContent === 'paused') {
                    pauseBtn.style.display = 'none';
                    resumeBtn.style.display = 'inline-block';
                }

                // Only auto-resume if job was running AND was started in browser mode
                // Check for a marker that indicates browser mode was active
                if (statusSpan && statusSpan.textContent === 'running') {
                    // Don't auto-resume - user must click Resume button
                    // This prevents background jobs from being hijacked
                    console.log('[MLCATAI] Found running job, but NOT auto-resuming. Use Resume button.');
                    pauseBtn.style.display = 'none';
                    resumeBtn.style.display = 'inline-block';
                }
            }
        },

        startGeneration: function (mode) {
            var self = this;

            // Get mode from parameter or from select dropdown
            if (!mode) {
                var modeSelect = document.getElementById('processing-mode-select');
                mode = modeSelect ? modeSelect.value : 'browser';
            }

            console.log('[MLCATAI] startGeneration called with mode:', mode);

            // Collect selected categories from checkboxes
            var categoryCheckboxes = document.querySelectorAll('.category-checkbox:checked');
            var categoryIds = Array.from(categoryCheckboxes).map(function (cb) {
                return cb.value;
            });

            if (categoryIds.length === 0) {
                alert('Please select at least one category');
                return;
            }

            // Collect selected languages
            var langCheckboxes = document.querySelectorAll('.lang-checkbox:checked');
            var languageIds = Array.from(langCheckboxes).map(function (cb) {
                return cb.value;
            });

            if (languageIds.length === 0) {
                alert('Please select at least one language');
                return;
            }

            // Collect selected fields
            var fieldCheckboxes = document.querySelectorAll('.field-checkbox:checked');
            var fields = Array.from(fieldCheckboxes).map(function (cb) {
                return cb.value;
            });

            if (fields.length === 0) {
                alert('Please select at least one field to generate');
                return;
            }

            var writeMode = document.getElementById('write-mode-select').value;

            // Create job
            console.log('[MLCATAI] Creating job with mode:', mode);
            this.ajaxRequest('createJob', {
                category_ids: categoryIds,
                language_ids: languageIds,
                fields: fields,
                write_mode: writeMode
            }, function (response) {
                console.log('[MLCATAI] createJob response, mode is:', mode);
                if (response.success) {
                    self.currentJobId = response.job_id;

                    if (mode === 'background') {
                        // Background mode: just show confirmation and reload
                        console.log('[MLCATAI] Background mode - showing alert');
                        alert('Job #' + response.job_id + ' created!\n\nThe job is now queued for background processing.\nConfigure cron to process automatically, or resume from the Job Queue panel.');
                        location.reload();
                    } else {
                        // Browser mode: start processing immediately
                        console.log('[MLCATAI] Browser mode - starting processing');
                        self.showProgress();
                        self.log('Job created with ID: ' + response.job_id);
                        self.log('Starting generation...');
                        self.processNextBatch();
                    }
                } else {
                    alert('Error: ' + response.error);
                }
            });
        },

        processNextBatch: function () {
            var self = this;

            if (!this.currentJobId) {
                return;
            }

            this.isProcessing = true;

            this.ajaxRequest('processJob', {
                job_id: this.currentJobId
            }, function (response) {
                if (response.success) {
                    self.updateProgress(response.progress_percent || 0, response.processed, response.total);

                    // Log batch results
                    if (response.batch_results) {
                        response.batch_results.forEach(function (result) {
                            if (result.skipped) {
                                self.log('⏭ Skipped: Category ' + result.id_category + ', Lang ' + result.id_lang + ', ' + result.field_type);
                            } else if (result.success) {
                                self.log('✓ Generated: Category ' + result.id_category + ', Lang ' + result.id_lang + ', ' + result.field_type);
                            } else {
                                self.log('✗ Error: Category ' + result.id_category + ' - ' + result.error);
                            }
                        });
                    }

                    if (response.completed) {
                        self.isProcessing = false;
                        self.log('');
                        self.log('=====================================');
                        self.log('=== GENERATION COMPLETE ===');
                        self.log('=====================================');
                        self.log('Total processed: ' + response.processed);
                        self.log('Total failed: ' + response.failed);
                        self.log('');
                        self.log('Click "Close & Reload" to update the page.');
                        self.updateProgress(100, response.processed, response.total);
                        self.showCompletionButtons();
                    } else {
                        // Continue with next batch
                        setTimeout(function () {
                            self.processNextBatch();
                        }, 500);
                    }
                } else {
                    self.isProcessing = false;
                    self.log('Error: ' + response.error);
                    alert('Error processing batch: ' + response.error);
                }
            });
        },

        pauseJob: function (jobId) {
            var self = this;

            this.ajaxRequest('pauseJob', { job_id: jobId }, function (response) {
                if (response.success) {
                    self.isProcessing = false;
                    document.getElementById('btn-pause-job').style.display = 'none';
                    document.getElementById('btn-resume-job').style.display = 'inline-block';
                    document.getElementById('job-status').textContent = 'paused';
                    self.log('Job paused');
                } else {
                    alert('Error: ' + response.message);
                }
            });
        },

        resumeJob: function (jobId) {
            var self = this;

            this.ajaxRequest('resumeJob', { job_id: jobId }, function (response) {
                if (response.success) {
                    document.getElementById('btn-pause-job').style.display = 'inline-block';
                    document.getElementById('btn-resume-job').style.display = 'none';
                    document.getElementById('job-status').textContent = 'running';
                    self.currentJobId = jobId;
                    self.log('Job resumed');
                    self.processNextBatch();
                } else {
                    alert('Error: ' + response.message);
                }
            });
        },

        cancelJob: function (jobId) {
            var self = this;

            this.ajaxRequest('cancelJob', { job_id: jobId }, function (response) {
                if (response.success) {
                    self.isProcessing = false;
                    self.log('Job cancelled');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            });
        },

        testApiConnection: function () {
            var self = this;
            var btn = document.getElementById('btn-test-api');
            var originalHtml = btn.innerHTML;

            btn.innerHTML = '<i class="icon icon-spinner icon-spin"></i> Testing...';
            btn.disabled = true;

            this.ajaxRequest('testConnection', {}, function (response) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;

                if (response.success) {
                    alert('✓ API connection successful!');
                } else {
                    alert('✗ API connection failed:\n\n' + response.message);
                }
            });
        },

        showProgress: function () {
            document.getElementById('mlcategoryai-new-job-form').style.display = 'none';
            document.getElementById('mlcategoryai-progress').style.display = 'block';
        },

        updateProgress: function (percent, processed, total) {
            var progressBar = document.getElementById('generation-progress-bar');
            var progressText = document.getElementById('generation-progress-text');

            if (progressBar) {
                progressBar.style.width = percent + '%';
            }
            if (progressText) {
                progressText.textContent = percent + '% (' + processed + '/' + total + ')';
            }

            // Also update the job panel if visible
            var jobProgressBar = document.getElementById('job-progress-bar');
            var jobProgress = document.getElementById('job-progress');
            var jobTotal = document.getElementById('job-total');

            if (jobProgressBar) {
                jobProgressBar.style.width = percent + '%';
            }
            if (jobProgress) {
                jobProgress.textContent = processed;
            }
            if (jobTotal) {
                jobTotal.textContent = total;
            }
        },

        log: function (message) {
            var logDiv = document.getElementById('generation-log');
            if (logDiv) {
                var timestamp = new Date().toLocaleTimeString();
                logDiv.innerHTML += '[' + timestamp + '] ' + message + '\n';
                logDiv.scrollTop = logDiv.scrollHeight;
            }
        },

        showCompletionButtons: function () {
            var logDiv = document.getElementById('generation-log');
            if (logDiv) {
                // Add completion buttons below the log
                var buttonsDiv = document.createElement('div');
                buttonsDiv.style.cssText = 'margin-top: 15px; text-align: center; padding: 15px; background: #dff0d8; border-radius: 4px;';
                buttonsDiv.innerHTML =
                    '<strong style="color: #3c763d; font-size: 16px;">✓ Generation Complete!</strong><br><br>' +
                    '<button type="button" class="btn btn-success btn-lg" id="btn-close-reload" style="margin-right: 10px;">' +
                    '<i class="icon icon-refresh"></i> Close & Reload Page</button>' +
                    '<button type="button" class="btn btn-default" id="btn-view-log-after">' +
                    '<i class="icon icon-file-text-o"></i> View Full Debug Log</button>';

                logDiv.parentNode.appendChild(buttonsDiv);

                // Bind button events
                document.getElementById('btn-close-reload').addEventListener('click', function () {
                    location.reload();
                });

                document.getElementById('btn-view-log-after').addEventListener('click', function () {
                    // Scroll to debug log section and open it
                    var viewLogBtn = document.getElementById('btn-view-debug-log');
                    if (viewLogBtn) {
                        viewLogBtn.scrollIntoView({ behavior: 'smooth' });
                        viewLogBtn.click();
                    }
                });

                // Remove active striped animation from progress bar
                var progressBar = document.getElementById('generation-progress-bar');
                if (progressBar) {
                    progressBar.classList.remove('active');
                    progressBar.classList.remove('progress-bar-striped');
                    progressBar.classList.add('progress-bar-success');
                }
            }
        },

        ajaxRequest: function (action, data, callback) {
            var self = this;
            var formData = new FormData();

            formData.append('action', action);
            formData.append('token', this.token);

            // Add data fields
            Object.keys(data).forEach(function (key) {
                var value = data[key];
                if (Array.isArray(value)) {
                    value.forEach(function (item) {
                        formData.append(key + '[]', item);
                    });
                } else {
                    formData.append(key, value);
                }
            });

            fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (callback) {
                        callback(data);
                    }
                })
                .catch(function (error) {
                    console.error('AJAX Error:', error);
                    if (callback) {
                        callback({ success: false, error: error.message });
                    }
                });
        }
    };

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        MlCategoryAi.init();
    });
})();
