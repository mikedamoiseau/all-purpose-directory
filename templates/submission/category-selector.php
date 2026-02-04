<?php
/**
 * Category Selector Template.
 *
 * Template for rendering the category selection in the submission form.
 * Uses a hierarchical dropdown for selecting categories.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/submission/category-selector.php
 *
 * @package APD\Templates
 * @since   1.0.0
 *
 * @var \WP_Term[] $categories          Available categories.
 * @var array      $category_options    Hierarchical category options for dropdown.
 * @var int[]      $selected_categories Currently selected category IDs.
 * @var array      $errors              Validation errors for this field.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_errors = ! empty( $errors );
?>

<div class="apd-field apd-field--frontend <?php echo $has_errors ? 'apd-field--has-error' : ''; ?>"
	data-field-name="listing_categories"
	data-field-type="select">
	<label class="apd-field__label" for="apd-field-listing-categories">
		<?php esc_html_e( 'Category', 'all-purpose-directory' ); ?>
	</label>
	<div class="apd-field__input">
		<select id="apd-field-listing-categories"
			name="listing_categories[]"
			class="apd-field__select apd-field__select--categories"
			multiple
			aria-describedby="apd-field-listing-categories-desc">
			<?php foreach ( $category_options as $option ) : ?>
				<option value="<?php echo absint( $option['id'] ); ?>"
					<?php selected( in_array( $option['id'], $selected_categories, true ) ); ?>>
					<?php echo esc_html( str_repeat( '&mdash; ', $option['depth'] ) . $option['name'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
	<p id="apd-field-listing-categories-desc" class="apd-field__description">
		<?php esc_html_e( 'Select one or more categories that best describe your listing.', 'all-purpose-directory' ); ?>
	</p>
	<?php if ( $has_errors ) : ?>
		<div class="apd-field__errors" role="alert" aria-live="polite">
			<?php foreach ( $errors as $error ) : ?>
				<p class="apd-field__error"><?php echo esc_html( $error ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<?php
/**
 * Alternative: Checkbox-based category selector.
 * Uncomment below and comment above select to use checkboxes instead.
 */
/*
<div class="apd-field apd-field--frontend apd-field--checkbox-group <?php echo $has_errors ? 'apd-field--has-error' : ''; ?>"
	data-field-name="listing_categories"
	data-field-type="checkbox_group">
	<fieldset class="apd-field__fieldset">
		<legend class="apd-field__legend">
			<?php esc_html_e( 'Categories', 'all-purpose-directory' ); ?>
		</legend>
		<div class="apd-field__input">
			<div class="apd-field__checkbox-options apd-field__checkbox-options--hierarchical">
				<?php foreach ( $category_options as $option ) : ?>
					<div class="apd-field__checkbox-option" style="margin-left: <?php echo absint( $option['depth'] * 20 ); ?>px;">
						<label class="apd-field__checkbox-label">
							<input type="checkbox"
								name="listing_categories[]"
								value="<?php echo absint( $option['id'] ); ?>"
								class="apd-field__checkbox"
								<?php checked( in_array( $option['id'], $selected_categories, true ) ); ?>>
							<span class="apd-field__checkbox-text">
								<?php echo esc_html( $option['name'] ); ?>
							</span>
						</label>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</fieldset>
	<p class="apd-field__description">
		<?php esc_html_e( 'Select one or more categories that best describe your listing.', 'all-purpose-directory' ); ?>
	</p>
	<?php if ( $has_errors ) : ?>
		<div class="apd-field__errors" role="alert" aria-live="polite">
			<?php foreach ( $errors as $error ) : ?>
				<p class="apd-field__error"><?php echo esc_html( $error ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
*/
?>
