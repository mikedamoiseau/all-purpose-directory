/**
 * All Purpose Directory - Frontend Scripts
 *
 * Handles AJAX filtering, form submissions, URL state management,
 * and loading states for listing search and filter functionality.
 *
 * @package APD
 */

(function() {
    'use strict';

    /**
     * APD Filter Module
     *
     * Handles all filter-related functionality including AJAX filtering,
     * URL state management, and UI updates.
     */
    const APDFilter = {

        /**
         * Configuration from WordPress.
         */
        config: window.apdFrontend || {},

        /**
         * Cache for DOM elements.
         */
        elements: {
            form: null,
            results: null,
            activeFilters: null,
            loadingIndicator: null,
            resultsCount: null,
        },

        /**
         * Current state.
         */
        state: {
            isLoading: false,
            currentRequest: null,
            debounceTimer: null,
        },

        /**
         * Initialize the filter module.
         */
        init: function() {
            this.cacheElements();

            if (!this.elements.form) {
                return;
            }

            this.bindEvents();
            this.handleInitialState();
        },

        /**
         * Cache DOM elements for performance.
         */
        cacheElements: function() {
            this.elements.form = document.querySelector('.apd-search-form');
            this.elements.results = document.querySelector('.apd-listings-results, .apd-listing-archive__listings');
            this.elements.activeFilters = document.querySelector('.apd-active-filters');
            this.elements.resultsCount = document.querySelector('.apd-results-count');

            // Create loading indicator if not exists
            if (this.elements.form && !document.querySelector('.apd-loading-indicator')) {
                const indicator = document.createElement('div');
                indicator.className = 'apd-loading-indicator';
                indicator.setAttribute('aria-hidden', 'true');
                indicator.innerHTML = '<span class="apd-loading-spinner"></span><span class="apd-loading-text">' +
                    (this.config.i18n?.loading || 'Loading...') + '</span>';
                this.elements.form.appendChild(indicator);
                this.elements.loadingIndicator = indicator;
            }
        },

        /**
         * Bind event listeners.
         */
        bindEvents: function() {
            const form = this.elements.form;

            if (!form) {
                return;
            }

            // Form submission
            form.addEventListener('submit', this.handleSubmit.bind(this));

            // Select changes (immediate filter)
            form.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', this.handleFilterChange.bind(this));
            });

            // Checkbox changes (immediate filter)
            form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', this.handleFilterChange.bind(this));
            });

            // Range inputs (debounced)
            form.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('input', this.handleRangeInput.bind(this));
            });

            // Date inputs (immediate filter)
            form.querySelectorAll('input[type="date"]').forEach(input => {
                input.addEventListener('change', this.handleFilterChange.bind(this));
            });

            // Search input (debounced)
            form.querySelectorAll('input[type="search"], input[type="text"]').forEach(input => {
                input.addEventListener('input', this.handleSearchInput.bind(this));
            });

            // Clear filters button
            const clearBtn = form.querySelector('.apd-search-form__clear');
            if (clearBtn) {
                clearBtn.addEventListener('click', this.handleClearFilters.bind(this));
            }

            // Active filter removal
            document.addEventListener('click', (e) => {
                if (e.target.closest('.apd-active-filters__remove')) {
                    e.preventDefault();
                    this.handleRemoveFilter(e.target.closest('.apd-active-filters__remove'));
                }
            });

            // Browser back/forward navigation
            window.addEventListener('popstate', this.handlePopState.bind(this));

            // Pagination links (if using AJAX)
            document.addEventListener('click', (e) => {
                const paginationLink = e.target.closest('.apd-pagination a, .pagination a');
                if (paginationLink && this.elements.form?.dataset.ajax === 'true') {
                    e.preventDefault();
                    this.handlePagination(paginationLink);
                }
            });
        },

        /**
         * Handle initial state from URL.
         */
        handleInitialState: function() {
            // URL state is already applied by PHP on page load
            // This is called in case we need to do any JS-specific initialization
        },

        /**
         * Handle form submission.
         *
         * @param {Event} e - Submit event.
         */
        handleSubmit: function(e) {
            const form = this.elements.form;

            // Check if AJAX is enabled
            if (form.dataset.ajax !== 'true') {
                return; // Allow normal form submission
            }

            e.preventDefault();
            this.performFilter();
        },

        /**
         * Handle filter control changes.
         *
         * @param {Event} e - Change event.
         */
        handleFilterChange: function(e) {
            const form = this.elements.form;

            if (form.dataset.ajax !== 'true') {
                form.submit();
                return;
            }

            this.performFilter();
        },

        /**
         * Handle range input with debounce.
         *
         * @param {Event} e - Input event.
         */
        handleRangeInput: function(e) {
            this.debounce(() => {
                this.handleFilterChange(e);
            }, 500);
        },

        /**
         * Handle search input with debounce.
         *
         * @param {Event} e - Input event.
         */
        handleSearchInput: function(e) {
            this.debounce(() => {
                this.handleFilterChange(e);
            }, 300);
        },

        /**
         * Handle clear filters button.
         *
         * @param {Event} e - Click event.
         */
        handleClearFilters: function(e) {
            const form = this.elements.form;

            if (form.dataset.ajax !== 'true') {
                return; // Allow normal navigation
            }

            e.preventDefault();

            // Reset form
            form.reset();

            // Clear URL parameters and reload
            const baseUrl = this.config.archiveUrl || window.location.pathname;
            this.updateUrl(baseUrl);
            this.performFilter();
        },

        /**
         * Handle removing a single active filter.
         *
         * @param {HTMLElement} removeLink - The remove link element.
         */
        handleRemoveFilter: function(removeLink) {
            const url = removeLink.href;

            if (this.elements.form?.dataset.ajax === 'true') {
                this.updateUrl(url);
                this.updateFormFromUrl();
                this.performFilter();
            } else {
                window.location.href = url;
            }
        },

        /**
         * Handle browser back/forward navigation.
         *
         * @param {PopStateEvent} e - PopState event.
         */
        handlePopState: function(e) {
            if (this.elements.form?.dataset.ajax === 'true') {
                this.updateFormFromUrl();
                this.performFilter(false); // Don't push state again
            }
        },

        /**
         * Handle pagination link clicks.
         *
         * @param {HTMLElement} link - Pagination link element.
         */
        handlePagination: function(link) {
            const url = new URL(link.href);
            const paged = url.searchParams.get('paged') || 1;

            this.updateUrl(link.href);
            this.performFilter(false, parseInt(paged, 10));

            // Scroll to top of results
            if (this.elements.results) {
                this.elements.results.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },

        /**
         * Perform AJAX filter request.
         *
         * @param {boolean} pushState - Whether to push state to history.
         * @param {number} paged - Page number.
         */
        performFilter: function(pushState = true, paged = 1) {
            const form = this.elements.form;

            if (!form || this.state.isLoading) {
                return;
            }

            // Cancel any pending request
            if (this.state.currentRequest) {
                this.state.currentRequest.abort();
            }

            // Show loading state
            this.setLoading(true);

            // Build form data
            const formData = new FormData(form);
            formData.append('action', 'apd_filter_listings');
            formData.append('_apd_nonce', this.config.filterNonce || '');
            formData.append('paged', paged.toString());

            // Create abort controller for this request
            const controller = new AbortController();
            this.state.currentRequest = controller;

            // Build URL for state
            if (pushState) {
                const newUrl = this.buildUrlFromForm(form);
                this.updateUrl(newUrl);
            }

            // Perform AJAX request
            fetch(this.config.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData,
                signal: controller.signal,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateResults(data.data);
                } else {
                    this.showError(data.data?.message || this.config.i18n?.error);
                }
            })
            .catch(error => {
                if (error.name !== 'AbortError') {
                    console.error('APD Filter Error:', error);
                    this.showError(this.config.i18n?.error);
                }
            })
            .finally(() => {
                this.setLoading(false);
                this.state.currentRequest = null;
            });
        },

        /**
         * Update the results container with new content.
         *
         * @param {Object} data - Response data.
         */
        updateResults: function(data) {
            // Update listings
            if (this.elements.results && data.html) {
                this.elements.results.innerHTML = data.html;
            }

            // Update results count
            if (this.elements.resultsCount) {
                const count = data.found_posts || 0;
                let countText;

                if (count === 0) {
                    countText = this.config.i18n?.noResults || 'No listings found.';
                } else if (count === 1) {
                    countText = this.config.i18n?.oneResultFound || '1 listing found';
                } else {
                    countText = (this.config.i18n?.resultsFound || '%d listings found').replace('%d', count);
                }

                this.elements.resultsCount.textContent = countText;
            }

            // Update active filters display
            this.updateActiveFilters(data.active_filters);

            // Trigger custom event for other scripts
            document.dispatchEvent(new CustomEvent('apd:filtered', {
                detail: data,
            }));
        },

        /**
         * Update the active filters display.
         *
         * @param {Object} activeFilters - Active filters data.
         */
        updateActiveFilters: function(activeFilters) {
            // For now, we rely on PHP rendering the active filters
            // In a full implementation, we'd rebuild the chips here
        },

        /**
         * Set loading state.
         *
         * @param {boolean} isLoading - Whether loading is in progress.
         */
        setLoading: function(isLoading) {
            this.state.isLoading = isLoading;

            const form = this.elements.form;
            const results = this.elements.results;

            if (isLoading) {
                form?.classList.add('apd-search-form--loading');
                results?.classList.add('apd-listings-results--loading');
                results?.setAttribute('aria-busy', 'true');

                if (this.elements.loadingIndicator) {
                    this.elements.loadingIndicator.setAttribute('aria-hidden', 'false');
                }
            } else {
                form?.classList.remove('apd-search-form--loading');
                results?.classList.remove('apd-listings-results--loading');
                results?.setAttribute('aria-busy', 'false');

                if (this.elements.loadingIndicator) {
                    this.elements.loadingIndicator.setAttribute('aria-hidden', 'true');
                }
            }
        },

        /**
         * Show an error message.
         *
         * @param {string} message - Error message.
         */
        showError: function(message) {
            if (this.elements.results) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'apd-filter-error';
                errorDiv.setAttribute('role', 'alert');
                const p = document.createElement('p');
                p.textContent = message || 'An error occurred.';
                errorDiv.appendChild(p);
                this.elements.results.innerHTML = '';
                this.elements.results.appendChild(errorDiv);
            }
        },

        /**
         * Build URL from form data.
         *
         * @param {HTMLFormElement} form - The form element.
         * @returns {string} The URL with query parameters.
         */
        buildUrlFromForm: function(form) {
            const formData = new FormData(form);
            const params = new URLSearchParams();

            for (const [key, value] of formData.entries()) {
                if (value && value.toString().trim() !== '') {
                    // Handle array parameters (checkboxes)
                    if (key.endsWith('[]')) {
                        params.append(key, value.toString());
                    } else {
                        params.set(key, value.toString());
                    }
                }
            }

            const baseUrl = form.action || this.config.archiveUrl || window.location.pathname;
            const queryString = params.toString();

            return queryString ? baseUrl + '?' + queryString : baseUrl;
        },

        /**
         * Update the browser URL.
         *
         * @param {string} url - New URL.
         */
        updateUrl: function(url) {
            if (window.history && window.history.pushState) {
                window.history.pushState({ apd: true }, '', url);
            }
        },

        /**
         * Update form values from current URL.
         */
        updateFormFromUrl: function() {
            const form = this.elements.form;
            if (!form) {
                return;
            }

            const params = new URLSearchParams(window.location.search);

            // Reset form first
            form.reset();

            // Set values from URL
            params.forEach((value, key) => {
                const input = form.querySelector('[name="' + key + '"], [name="' + key + '[]"]');

                if (!input) {
                    return;
                }

                if (input.type === 'checkbox') {
                    // Handle checkbox groups
                    const checkboxes = form.querySelectorAll('[name="' + key + '"]');
                    checkboxes.forEach(cb => {
                        cb.checked = params.getAll(key).includes(cb.value);
                    });
                } else if (input.type === 'select-multiple') {
                    // Handle multi-select
                    const values = params.getAll(key);
                    Array.from(input.options).forEach(opt => {
                        opt.selected = values.includes(opt.value);
                    });
                } else {
                    input.value = value;
                }
            });
        },

        /**
         * Debounce function execution.
         *
         * @param {Function} func - Function to debounce.
         * @param {number} wait - Wait time in ms.
         */
        debounce: function(func, wait) {
            clearTimeout(this.state.debounceTimer);
            this.state.debounceTimer = setTimeout(func, wait);
        },
    };

    /**
     * Initialize on DOM ready.
     */
    document.addEventListener('DOMContentLoaded', function() {
        APDFilter.init();
    });

    // Expose to global scope for external access
    window.APDFilter = APDFilter;

})();
