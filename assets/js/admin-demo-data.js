/**
 * Admin Demo Data Page JavaScript
 *
 * @package APD
 * @since 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * APD Demo Data Module
	 */
	var APDDemoData = {
		/**
		 * Configuration from localized script.
		 */
		config: window.apdDemoData || {},

		/**
		 * Initialize the demo data page.
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind event handlers.
		 */
		bindEvents: function() {
			$('#apd-generate-form').on('submit', this.handleGenerate.bind(this));
			$('#apd-delete-form').on('submit', this.handleDelete.bind(this));

			// Toggle count inputs based on checkbox state
			$('#apd-generate-form').on('change', 'input[type="checkbox"]', function() {
				var $row = $(this).closest('.apd-form-row');
				var $number = $row.find('input[type="number"]');
				$number.prop('disabled', !this.checked);
			});
		},

		/**
		 * Handle generate form submission.
		 *
		 * @param {Event} e Submit event.
		 */
		handleGenerate: function(e) {
			e.preventDefault();

			var self = this;
			var $form = $(e.currentTarget);
			var $btn = $('#apd-generate-btn');
			var $progress = $('#apd-progress');
			var $results = $('#apd-results');

			// Check if at least one option is selected
			if (!$form.find('input[type="checkbox"]:checked').length) {
				alert(this.config.strings.error || 'Please select at least one data type to generate.');
				return;
			}

			// Disable form
			$form.addClass('is-loading');
			$btn.prop('disabled', true);

			// Show progress
			$results.hide();
			$progress.show().addClass('is-active');
			this.updateProgress(0, this.config.strings.generating);

			// Collect form data
			var formData = {
				action: 'apd_generate_demo',
				nonce: this.config.generateNonce,
				generate_users: $form.find('[name="generate_users"]').is(':checked') ? 1 : 0,
				generate_categories: $form.find('[name="generate_categories"]').is(':checked') ? 1 : 0,
				generate_tags: $form.find('[name="generate_tags"]').is(':checked') ? 1 : 0,
				generate_listings: $form.find('[name="generate_listings"]').is(':checked') ? 1 : 0,
				generate_reviews: $form.find('[name="generate_reviews"]').is(':checked') ? 1 : 0,
				generate_inquiries: $form.find('[name="generate_inquiries"]').is(':checked') ? 1 : 0,
				generate_favorites: $form.find('[name="generate_favorites"]').is(':checked') ? 1 : 0,
				users_count: parseInt($form.find('[name="users_count"]').val(), 10) || 5,
				tags_count: parseInt($form.find('[name="tags_count"]').val(), 10) || 10,
				listings_count: parseInt($form.find('[name="listings_count"]').val(), 10) || 25
			};

			// Simulate progress for better UX
			var progress = 0;
			var progressInterval = setInterval(function() {
				if (progress < 90) {
					progress += Math.random() * 15;
					progress = Math.min(progress, 90);
					self.updateProgress(progress, self.getProgressText(progress, formData));
				}
			}, 500);

			// Send AJAX request
			$.ajax({
				url: this.config.ajaxUrl,
				type: 'POST',
				data: formData,
				success: function(response) {
					clearInterval(progressInterval);
					self.updateProgress(100, self.config.strings.success);

					setTimeout(function() {
						$progress.removeClass('is-active').hide();

						if (response.success) {
							self.showResults(response.data, 'success');
							self.updateStats(response.data.counts);
						} else {
							self.showResults({
								message: response.data.message || self.config.strings.error
							}, 'error');
						}

						$form.removeClass('is-loading');
						$btn.prop('disabled', false);
					}, 500);
				},
				error: function() {
					clearInterval(progressInterval);
					$progress.removeClass('is-active').hide();

					self.showResults({
						message: self.config.strings.error
					}, 'error');

					$form.removeClass('is-loading');
					$btn.prop('disabled', false);
				}
			});
		},

		/**
		 * Handle delete form submission.
		 *
		 * @param {Event} e Submit event.
		 */
		handleDelete: function(e) {
			e.preventDefault();

			var self = this;

			// Confirm deletion
			if (!confirm(this.config.strings.confirmDelete)) {
				return;
			}

			var $form = $(e.currentTarget);
			var $btn = $('#apd-delete-btn');
			var $progress = $('#apd-progress');
			var $results = $('#apd-results');

			// Disable button
			$btn.prop('disabled', true).text(this.config.strings.deleting);

			// Show progress
			$results.hide();
			$progress.show().addClass('is-active');
			this.updateProgress(50, this.config.strings.deleting);

			// Send AJAX request
			$.ajax({
				url: this.config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'apd_delete_demo',
					nonce: this.config.deleteNonce
				},
				success: function(response) {
					self.updateProgress(100, self.config.strings.success);

					setTimeout(function() {
						$progress.removeClass('is-active').hide();

						if (response.success) {
							self.showDeleteResults(response.data);
							self.updateStats(response.data.counts);
							// Update delete section to show no data message
							self.updateDeleteSection();
						} else {
							self.showResults({
								message: response.data.message || self.config.strings.error
							}, 'error');
						}

						$btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Delete All Demo Data');
					}, 500);
				},
				error: function() {
					$progress.removeClass('is-active').hide();

					self.showResults({
						message: self.config.strings.error
					}, 'error');

					$btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Delete All Demo Data');
				}
			});
		},

		/**
		 * Update progress bar and text.
		 *
		 * @param {number} percent Progress percentage.
		 * @param {string} text    Progress text.
		 */
		updateProgress: function(percent, text) {
			$('.apd-progress-bar-fill').css('width', percent + '%');
			$('.apd-progress-text').text(text);
		},

		/**
		 * Get progress text based on current stage.
		 *
		 * @param {number} percent  Progress percentage.
		 * @param {object} formData Form data with selected options.
		 * @return {string} Progress text.
		 */
		getProgressText: function(percent, formData) {
			var strings = this.config.strings;

			if (percent < 15 && formData.generate_users) {
				return strings.generatingUsers || 'Creating users...';
			} else if (percent < 30 && formData.generate_categories) {
				return strings.generatingCats || 'Creating categories...';
			} else if (percent < 40 && formData.generate_tags) {
				return strings.generatingTags || 'Creating tags...';
			} else if (percent < 65 && formData.generate_listings) {
				return strings.generatingList || 'Creating listings...';
			} else if (percent < 80 && formData.generate_reviews) {
				return strings.generatingReviews || 'Creating reviews...';
			} else if (percent < 90 && formData.generate_inquiries) {
				return strings.generatingInq || 'Creating inquiries...';
			} else if (formData.generate_favorites) {
				return strings.generatingFavs || 'Creating favorites...';
			}

			return strings.generating || 'Generating demo data...';
		},

		/**
		 * Show results message.
		 *
		 * @param {object} data Response data.
		 * @param {string} type 'success' or 'error'.
		 */
		showResults: function(data, type) {
			var $results = $('#apd-results');
			var html = '';

			if (type === 'success' && data.created) {
				html = '<h3><span class="dashicons dashicons-yes"></span> ' + (data.message || this.config.strings.success) + '</h3>';
				html += '<ul>';

				var labels = {
					users: 'Users created',
					categories: 'Categories created',
					tags: 'Tags created',
					listings: 'Listings created',
					reviews: 'Reviews created',
					inquiries: 'Inquiries created',
					favorites: 'Favorites added'
				};

				for (var key in data.created) {
					if (data.created.hasOwnProperty(key) && data.created[key] > 0) {
						html += '<li><span class="dashicons dashicons-yes"></span> ' + labels[key] + ': ' + data.created[key] + '</li>';
					}
				}

				html += '</ul>';
			} else {
				html = '<p>' + (data.message || this.config.strings.error) + '</p>';
			}

			$results.html(html).removeClass('success error').addClass(type).show();
		},

		/**
		 * Show delete results.
		 *
		 * @param {object} data Response data.
		 */
		showDeleteResults: function(data) {
			var $results = $('#apd-results');
			var html = '<h3><span class="dashicons dashicons-yes"></span> ' + (data.message || 'All demo data deleted.') + '</h3>';

			if (data.deleted) {
				html += '<ul>';

				var labels = {
					users: 'Users deleted',
					categories: 'Categories deleted',
					tags: 'Tags deleted',
					listings: 'Listings deleted',
					reviews: 'Reviews deleted',
					inquiries: 'Inquiries deleted',
					favorites: 'Favorites cleared'
				};

				for (var key in data.deleted) {
					if (data.deleted.hasOwnProperty(key) && data.deleted[key] > 0) {
						html += '<li><span class="dashicons dashicons-yes"></span> ' + labels[key] + ': ' + data.deleted[key] + '</li>';
					}
				}

				html += '</ul>';
			}

			$results.html(html).removeClass('error').addClass('success').show();
		},

		/**
		 * Update stats table with new counts.
		 *
		 * @param {object} counts Count data by type.
		 */
		updateStats: function(counts) {
			if (!counts) return;

			var total = 0;

			for (var type in counts) {
				if (counts.hasOwnProperty(type)) {
					var $cell = $('.apd-stat-count[data-type="' + type + '"]');
					if ($cell.length) {
						$cell.text(this.formatNumber(counts[type]));
					}
					total += counts[type];
				}
			}

			$('.apd-stat-total').text(this.formatNumber(total));
		},

		/**
		 * Update the delete section after deletion.
		 */
		updateDeleteSection: function() {
			var $section = $('.apd-demo-delete');
			$section.find('.apd-warning, #apd-delete-form').remove();
			$section.append(
				'<p class="apd-no-data">' +
				'<span class="dashicons dashicons-yes-alt"></span> ' +
				'No demo data found. Your directory contains only real content.' +
				'</p>'
			);
		},

		/**
		 * Format number with locale separators.
		 *
		 * @param {number} num Number to format.
		 * @return {string} Formatted number.
		 */
		formatNumber: function(num) {
			return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		APDDemoData.init();
	});

})(jQuery);
