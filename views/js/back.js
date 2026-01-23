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
 * Compatible with PS 1.7.x and PS 8.x (ES5 syntax for older browsers)
 */
(function () {
    'use strict';

    // Helper function to safely add event listener
    function addEvent(id, event, handler) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener(event, handler);
        }
    }

    // Helper function to get direct children matching selector
    function getDirectChild(parent, selector) {
        var children = parent.children;
        for (var i = 0; i < children.length; i++) {
            if (children[i].matches && children[i].matches(selector)) {
                return children[i];
            }
        }
        return null;
    }

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
            addEvent('btn-start-generation', 'click', function () {
                self.startGeneration('browser');
            });

            // Start background button
            addEvent('btn-start-background', 'click', function () {
                self.startGeneration('background');
            });

            // Processing mode toggle
            addEvent('processing-mode-select', 'change', function () {
                var mode = this.value;
                var browserBtn = document.getElementById('btn-start-generation');
                var backgroundBtn = document.getElementById('btn-start-background');
                var helpBrowser = document.getElementById('help-browser');
                var helpBackground = document.getElementById('help-background');

                if (mode === 'background') {
                    if (browserBtn) browserBtn.style.display = 'none';
                    if (backgroundBtn) backgroundBtn.style.display = 'inline-block';
                    if (helpBrowser) helpBrowser.style.display = 'none';
                    if (helpBackground) helpBackground.style.display = 'inline';
                } else {
                    if (browserBtn) browserBtn.style.display = 'inline-block';
                    if (backgroundBtn) backgroundBtn.style.display = 'none';
                    if (helpBrowser) helpBrowser.style.display = 'inline';
                    if (helpBackground) helpBackground.style.display = 'none';
                }
            });

            // Test API button
            addEvent('btn-test-api', 'click', function () {
                self.testApiConnection();
            });

            // Test Google Translate API button
            addEvent('btn-test-google-api', 'click', function () {
                self.testGoogleApiConnection();
            });

            // Google Translate toggle
            addEvent('use-google-translate', 'change', function () {
                var details = document.getElementById('google-translate-details');
                if (details) {
                    details.style.display = this.checked ? 'block' : 'none';
                }
            });

            // Pause job button
            addEvent('btn-pause-job', 'click', function () {
                var jobId = this.getAttribute('data-job-id');
                self.pauseJob(jobId);
            });

            // Resume job button
            addEvent('btn-resume-job', 'click', function () {
                var jobId = this.getAttribute('data-job-id');
                self.resumeJob(jobId);
            });

            // Cancel job button
            addEvent('btn-cancel-job', 'click', function () {
                var jobId = this.getAttribute('data-job-id');
                if (confirm('Are you sure you want to cancel this job?')) {
                    self.cancelJob(jobId);
                }
            });

            // Category tree: Select all categories
            addEvent('select-all-categories', 'click', function () {
                var checkboxes = document.querySelectorAll('.category-checkbox');
                for (var i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].checked = true;
                }
                self.updateCategoryCount();
            });

            // Category tree: Deselect all categories
            addEvent('deselect-all-categories', 'click', function () {
                var checkboxes = document.querySelectorAll('.category-checkbox');
                for (var i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].checked = false;
                }
                self.updateCategoryCount();
            });

            // Category tree: Expand all
            addEvent('expand-all-categories', 'click', function () {
                var toggles = document.querySelectorAll('.mlcatai-tree-toggle');
                for (var i = 0; i < toggles.length; i++) {
                    var toggle = toggles[i];
                    var node = toggle.closest('.mlcatai-tree-node');
                    if (node) {
                        var children = node.querySelector('.mlcatai-tree-children');
                        if (children) {
                            children.classList.remove('collapsed');
                            toggle.setAttribute('data-expanded', 'true');
                            toggle.innerHTML = '<i class="icon icon-minus-square-o"></i>';
                        }
                    }
                }
            });

            // Category tree: Collapse all
            addEvent('collapse-all-categories', 'click', function () {
                var toggles = document.querySelectorAll('.mlcatai-tree-toggle');
                for (var i = 0; i < toggles.length; i++) {
                    var toggle = toggles[i];
                    var node = toggle.closest('.mlcatai-tree-node');
                    if (node) {
                        var children = node.querySelector('.mlcatai-tree-children');
                        if (children) {
                            children.classList.add('collapsed');
                            toggle.setAttribute('data-expanded', 'false');
                            toggle.innerHTML = '<i class="icon icon-plus-square-o"></i>';
                        }
                    }
                }
            });

            // Category tree: Toggle expand/collapse
            var treeToggles = document.querySelectorAll('.mlcatai-tree-toggle');
            for (var t = 0; t < treeToggles.length; t++) {
                treeToggles[t].addEventListener('click', function () {
                    var node = this.closest('.mlcatai-tree-node');
                    if (node) {
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
                    }
                });
            }

            // Category tree: Select all subcategories button
            var selectChildrenBtns = document.querySelectorAll('.mlcatai-select-children');
            for (var s = 0; s < selectChildrenBtns.length; s++) {
                selectChildrenBtns[s].addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var node = this.closest('.mlcatai-tree-node');
                    if (node) {
                        var checkboxes = node.querySelectorAll('.category-checkbox');
                        var allChecked = true;
                        for (var c = 0; c < checkboxes.length; c++) {
                            if (!checkboxes[c].checked) {
                                allChecked = false;
                                break;
                            }
                        }
                        for (var c2 = 0; c2 < checkboxes.length; c2++) {
                            checkboxes[c2].checked = !allChecked;
                        }
                        self.updateCategoryCount();
                    }
                });
            }

            // Category tree: Update count on checkbox change
            var categoryCheckboxes = document.querySelectorAll('.category-checkbox');
            for (var cb = 0; cb < categoryCheckboxes.length; cb++) {
                categoryCheckboxes[cb].addEventListener('change', function () {
                    self.updateCategoryCount();
                });
            }

            // Category search
            var searchInput = document.getElementById('category-search');
            var searchTimeout = null;
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    var query = this.value.toLowerCase().trim();
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function () {
                        self.filterCategories(query);
                    }, 150);
                });
            }

            // Clear search button
            addEvent('clear-category-search', 'click', function () {
                var input = document.getElementById('category-search');
                if (input) {
                    input.value = '';
                    self.filterCategories('');
                }
            });

            // Select all languages
            addEvent('select-all-languages', 'change', function () {
                var checked = this.checked;
                var checkboxes = document.querySelectorAll('.lang-checkbox');
                for (var i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].checked = checked;
                }
            });

            // Initialize category count and row colors
            this.updateCategoryCount();
            this.applyAlternatingRowColors();
        },

        applyAlternatingRowColors: function () {
            // Apply alternating row colors to ALL visible rows (regardless of hierarchy)
            var items = document.querySelectorAll('.mlcatai-tree-item');
            var visibleIndex = 0;
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var node = item.closest('.mlcatai-tree-node');
                // Only count visible items
                if (node && !node.classList.contains('search-hidden')) {
                    item.classList.remove('row-odd', 'row-even');
                    item.classList.add(visibleIndex % 2 === 0 ? 'row-even' : 'row-odd');
                    visibleIndex++;
                }
            }
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
            var i, node, name, checkbox, categoryName, parent, toggle, prevSibling;

            // Remove all highlights
            for (i = 0; i < names.length; i++) {
                names[i].classList.remove('search-match');
            }

            if (!query) {
                // Show all nodes
                for (i = 0; i < nodes.length; i++) {
                    nodes[i].classList.remove('search-hidden');
                }
                this.applyAlternatingRowColors();
                return;
            }

            // Hide all first
            for (i = 0; i < nodes.length; i++) {
                nodes[i].classList.add('search-hidden');
            }

            // Show matching nodes and their ancestors
            for (i = 0; i < nodes.length; i++) {
                node = nodes[i];
                // Get direct child .mlcatai-tree-item
                var treeItem = getDirectChild(node, '.mlcatai-tree-item');
                if (treeItem) {
                    name = treeItem.querySelector('.mlcatai-tree-name');
                    checkbox = treeItem.querySelector('.category-checkbox');
                    if (name && checkbox) {
                        categoryName = checkbox.getAttribute('data-name') || '';
                        if (categoryName.indexOf(query) !== -1) {
                            // Show this node
                            node.classList.remove('search-hidden');
                            name.classList.add('search-match');

                            // Show all ancestors
                            parent = node.parentElement;
                            while (parent) {
                                if (parent.classList && parent.classList.contains('mlcatai-tree-node')) {
                                    parent.classList.remove('search-hidden');
                                }
                                if (parent.classList && parent.classList.contains('mlcatai-tree-children')) {
                                    parent.classList.remove('collapsed');
                                    prevSibling = parent.previousElementSibling;
                                    if (prevSibling) {
                                        toggle = prevSibling.querySelector('.mlcatai-tree-toggle');
                                        if (toggle) {
                                            toggle.setAttribute('data-expanded', 'true');
                                            toggle.innerHTML = '<i class="icon icon-minus-square-o"></i>';
                                        }
                                    }
                                }
                                parent = parent.parentElement;
                            }

                            // Show all descendants
                            var descendants = node.querySelectorAll('.mlcatai-tree-node');
                            for (var d = 0; d < descendants.length; d++) {
                                descendants[d].classList.remove('search-hidden');
                            }
                        }
                    }
                }
            }

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
                    if (pauseBtn) pauseBtn.style.display = 'none';
                    if (resumeBtn) resumeBtn.style.display = 'inline-block';
                }

                // Only auto-resume if job was running AND was started in browser mode
                // Check for a marker that indicates browser mode was active
                if (statusSpan && statusSpan.textContent === 'running') {
                    // Don't auto-resume - user must click Resume button
                    // This prevents background jobs from being hijacked
                    console.log('[MLCATAI] Found running job, but NOT auto-resuming. Use Resume button.');
                    if (pauseBtn) pauseBtn.style.display = 'none';
                    if (resumeBtn) resumeBtn.style.display = 'inline-block';
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
            var categoryIds = [];
            for (var i = 0; i < categoryCheckboxes.length; i++) {
                categoryIds.push(categoryCheckboxes[i].value);
            }

            if (categoryIds.length === 0) {
                alert('Please select at least one category');
                return;
            }

            // Collect selected languages
            var langCheckboxes = document.querySelectorAll('.lang-checkbox:checked');
            var languageIds = [];
            for (var l = 0; l < langCheckboxes.length; l++) {
                languageIds.push(langCheckboxes[l].value);
            }

            if (languageIds.length === 0) {
                alert('Please select at least one language');
                return;
            }

            // Collect selected fields
            var fieldCheckboxes = document.querySelectorAll('.field-checkbox:checked');
            var fields = [];
            for (var f = 0; f < fieldCheckboxes.length; f++) {
                fields.push(fieldCheckboxes[f].value);
            }

            if (fields.length === 0) {
                alert('Please select at least one field to generate');
                return;
            }

            var writeMode = document.getElementById('write-mode-select').value;

            // Check for Google Translate mode
            var useGoogleTranslate = false;
            var primaryLanguageId = 0;
            var gtCheckbox = document.getElementById('use-google-translate');
            var primaryLangInput = document.getElementById('primary-language-id');

            if (gtCheckbox && gtCheckbox.checked && primaryLangInput) {
                useGoogleTranslate = true;
                primaryLanguageId = parseInt(primaryLangInput.value, 10);
            }

            // Build request data
            var requestData = {
                category_ids: categoryIds,
                language_ids: languageIds,
                fields: fields,
                write_mode: writeMode
            };

            // Add GT parameters if enabled
            if (useGoogleTranslate) {
                requestData.use_google_translate = 1;
                requestData.primary_language_id = primaryLanguageId;
            }

            // Create job
            console.log('[MLCATAI] Creating job with mode:', mode, 'GT:', useGoogleTranslate);
            this.ajaxRequest('createJob', requestData, function (response) {
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
                        for (var i = 0; i < response.batch_results.length; i++) {
                            var result = response.batch_results[i];
                            // v1.7.0: items now have 'fields' array instead of 'field_type'
                            var fieldInfo = result.fields ? result.fields.join(', ') : (result.field_type || 'all fields');
                            if (result.skipped) {
                                self.log('⏭ Skipped: Category ' + result.id_category + ', Lang ' + result.id_lang + ' (' + fieldInfo + ')');
                            } else if (result.success) {
                                self.log('✓ Generated: Category ' + result.id_category + ', Lang ' + result.id_lang + ' (' + fieldInfo + ')');
                            } else {
                                self.log('✗ Error: Category ' + result.id_category + ' - ' + result.error);
                            }
                        }
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
                    var pauseBtn = document.getElementById('btn-pause-job');
                    var resumeBtn = document.getElementById('btn-resume-job');
                    var statusSpan = document.getElementById('job-status');
                    if (pauseBtn) pauseBtn.style.display = 'none';
                    if (resumeBtn) resumeBtn.style.display = 'inline-block';
                    if (statusSpan) statusSpan.textContent = 'paused';
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
                    var pauseBtn = document.getElementById('btn-pause-job');
                    var resumeBtn = document.getElementById('btn-resume-job');
                    var statusSpan = document.getElementById('job-status');
                    if (pauseBtn) pauseBtn.style.display = 'inline-block';
                    if (resumeBtn) resumeBtn.style.display = 'none';
                    if (statusSpan) statusSpan.textContent = 'running';
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
            if (!btn) return;

            var originalHtml = btn.innerHTML;

            btn.innerHTML = '<i class="icon icon-spinner icon-spin"></i> Testing...';
            btn.disabled = true;

            this.ajaxRequest('testConnection', {}, function (response) {
                // Always reset button state first
                btn.innerHTML = originalHtml;
                btn.disabled = false;

                // Show result message
                if (response && response.success) {
                    alert('✓ API connection successful!');
                } else {
                    var errorMsg = (response && response.message) ? response.message : 'Unknown error';
                    alert('✗ API connection failed:\n\n' + errorMsg);
                }
            });
        },

        testGoogleApiConnection: function () {
            var btn = document.getElementById('btn-test-google-api');
            if (!btn) return;

            var originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="icon icon-spinner icon-spin"></i> Testing...';
            btn.disabled = true;

            this.ajaxRequest('testGoogleApi', {}, function (response) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;

                if (response && response.success) {
                    alert('✓ Google Translate API connection successful!');
                } else {
                    var errorMsg = (response && response.message) ? response.message : 'Unknown error';
                    alert('✗ Google Translate API connection failed:\n\n' + errorMsg);
                }
            });
        },

        showProgress: function () {
            var form = document.getElementById('mlcategoryai-new-job-form');
            var progress = document.getElementById('mlcategoryai-progress');
            if (form) form.style.display = 'none';
            if (progress) progress.style.display = 'block';
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
            if (!logDiv) {
                return;
            }

            // Prevent duplicate completion messages
            if (document.getElementById('mlcatai-completion-panel')) {
                return;
            }

            // Add completion buttons below the log
            var buttonsDiv = document.createElement('div');
            buttonsDiv.id = 'mlcatai-completion-panel';
            buttonsDiv.style.cssText = 'margin-top: 15px; text-align: center; padding: 15px; background: #dff0d8; border-radius: 4px;';
            buttonsDiv.innerHTML =
                '<strong style="color: #3c763d; font-size: 16px;">✓ Generation Complete!</strong><br><br>' +
                '<button type="button" class="btn btn-success btn-lg" id="btn-close-reload" style="margin-right: 10px;">' +
                '<i class="icon icon-refresh"></i> Close & Reload Page</button>' +
                '<button type="button" class="btn btn-default" id="btn-view-log-after">' +
                '<i class="icon icon-file-text-o"></i> View Full Debug Log</button>';

            logDiv.parentNode.appendChild(buttonsDiv);

            // Bind button events
            var reloadBtn = document.getElementById('btn-close-reload');
            if (reloadBtn) {
                reloadBtn.addEventListener('click', function () {
                    location.reload();
                });
            }

            var viewLogBtn = document.getElementById('btn-view-log-after');
            if (viewLogBtn) {
                viewLogBtn.addEventListener('click', function () {
                    // Scroll to debug log section and open it
                    var debugLogBtn = document.getElementById('btn-view-debug-log');
                    if (debugLogBtn) {
                        debugLogBtn.scrollIntoView({ behavior: 'smooth' });
                        debugLogBtn.click();
                    }
                });
            }

            // Remove active striped animation from progress bar
            var progressBar = document.getElementById('generation-progress-bar');
            if (progressBar) {
                progressBar.classList.remove('active');
                progressBar.classList.remove('progress-bar-striped');
                progressBar.classList.add('progress-bar-success');
            }
        },

        ajaxRequest: function (action, data, callback) {
            var self = this;
            var formData = new FormData();

            formData.append('action', action);
            formData.append('token', this.token);

            // Add data fields
            var keys = Object.keys(data);
            for (var i = 0; i < keys.length; i++) {
                var key = keys[i];
                var value = data[key];
                if (Array.isArray(value)) {
                    for (var j = 0; j < value.length; j++) {
                        formData.append(key + '[]', value[j]);
                    }
                } else {
                    formData.append(key, value);
                }
            }

            fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (responseData) {
                    if (callback) {
                        callback(responseData);
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

    // Wait for module content to be available (PS 1.7.x loads content after DOMContentLoaded)
    function waitForElement(selector, callback, maxAttempts) {
        var attempts = 0;
        maxAttempts = maxAttempts || 50; // 5 seconds max

        function check() {
            attempts++;
            var element = document.querySelector(selector);
            if (element) {
                callback();
            } else if (attempts < maxAttempts) {
                setTimeout(check, 100);
            } else {
                console.log('[MLCATAI] Module content not found after ' + maxAttempts + ' attempts');
            }
        }
        check();
    }

    // Initialize the module
    function initModule() {
        if (window.mlcategoryai_initialized) {
            return;
        }
        var panel = document.getElementById('mlcategoryai-batch-panel');
        if (panel) {
            window.mlcategoryai_initialized = true;
            console.log('[MLCATAI] Module content found, initializing...');
            MlCategoryAi.init();
        }
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        // Wait for module panel to exist before initializing
        waitForElement('#mlcategoryai-batch-panel', initModule);
    });

    // Also try on window load as fallback
    window.addEventListener('load', initModule);

    // If document is already loaded (script loaded late), init immediately
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        // Use setTimeout to ensure panel is in DOM
        setTimeout(initModule, 0);
    }
})();
