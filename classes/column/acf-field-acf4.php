<?php
/**
 * ACF Field for Advanced Custom Fields 4.x
 *
 * @since 1.1
 */
class CPAC_ACF_Column_ACF_Field_ACF4 extends CPAC_ACF_Column_ACF_Field {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.1
	 */
	public function init() {

		parent::init();
	}

	/**
	 * @see CPAC_ACF_Column_ACF_Field::get_value()
	 * @since 1.1
	 */
	public function get_value( $id ) {

		$field = $this->get_field();

		$do_format = in_array( $field['type'], array( 'text', 'select' ) );

		// Exclude "break newline" formatting from autoamtic formatting because X-Editable handles automatic line-breaking of textareas via "white-space: pre-wrap"
		if ( 'textarea' == $field['type'] && 'br' != $field['formatting'] ) {
			$do_format = true;
		}

		if ( $do_format ) {
			$rawvalue = get_field( $this->get_field_key(), $this->get_formatted_id( $id ), true );
		}
		else {
			$rawvalue = $this->get_raw_value( $id );
		}

		return $this->format_acf_value( $rawvalue, $this->get_field(), $id );
	}

	/**
	 * @see CPAC_Column::display_settings()
	 * @since 1.0
	 */
	public function display_settings() {

		// Options groups for ACF field selection
		$optiongroups = array();

		if ( $groups = apply_filters( 'acf/get_field_groups', array() ) ) {

			// Check whether the custom field group is active for this location
			$match_args = array();

			if ( ! empty( $this->storage_model->key ) ) {

				switch ( $this->storage_model->type ) {

					// @see acf_location::rule_match_post_type
					case 'post' :
						$match_args['post_type'] = $this->storage_model->key;
						break;

					// @see acf_location::rule_match_taxonomy
					case 'taxonomy' :
						$match_args['ef_taxonomy'] = $this->storage_model->taxonomy;
						break;

					// @see acf_location::rule_match_ef_user
					case 'user' :
						$match_args['ef_user'] = 'all';
						break;

					// @see acf_location::match_field_groups
					case 'media' :
						$match_args['post_type'] = 'attachment';
						break;
				}
			}

			$matchinggroups = apply_filters( 'acf/location/match_field_groups', array(), $match_args );

			foreach ( $groups as $group ) {
				if ( ! in_array( $group['id'], $matchinggroups ) ) {
					continue;
				}

				$options = array();

				$fields = apply_filters( 'acf/field_group/get_fields', array(), $group['id'] );

				foreach ( $fields as $field ) {
					if ( in_array( $field['type'], array( 'tab' ) ) ) {
						continue;
					}

					$options[ $field['key'] ] = array(
						'type' => $field['type'],
						'label' => $field['label']
					);
				}

				if ( !empty( $options ) ) {
					$optiongroups[ $group['id'] ] = array(
						'title' 	=> $group['title'],
						'options' 	=> $options
					);
				}
			}
		}
		?>

		<tr class="column_field">
			<?php $this->label_view( __( 'Field', 'codepress-admin-columns' ), __( 'Select your ACF field.', 'codepress-admin-columns' ), 'field' ); ?>
			<td class="input">

				<?php if ( ! empty( $optiongroups ) ) : ?>
					<select name="<?php $this->attr_name( 'field' ); ?>" id="<?php $this->attr_id( 'field' ); ?>">
						<?php foreach ( $optiongroups as $group ) : ?>
							<optgroup label="<?php echo esc_attr( $group['title'] ); ?>">
								<?php foreach ( $group['options'] as $field_key => $option ) : ?>
									<option data-field-type="<?php echo esc_attr( $option['type'] ); ?>" value="<?php echo $field_key ?>"<?php selected( $field_key, $this->get_field_key() ) ?>><?php echo $option['label']; ?></option>
								<?php endforeach; ?>
							</optgroup>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<?php _e( 'No ACF fields available.', 'codepress-admin-columns' ); ?>
				<?php endif; ?>

			</td>
		</tr>

		<?php $this->display_optional_settings( $this->get_field_type() ); ?>

		<?php
	}

}