<?php
/**
 * ACF Field for Advanced Custom Fields 5.x
 *
 * @since 1.1
 */
class CPAC_ACF_Column_ACF_Field_ACF5 extends CPAC_ACF_Column_ACF_Field {

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

		// Exclude "break newline" formatting from automatic formatting because X-Editable handles automatic line-breaking of textareas via "white-space: pre-wrap"
		if ( 'textarea' == $field['type'] && 'br' != $field['new_lines'] ) {
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
	 * @since 1.3
	 */
	public function sort_by_label( $a, $b ) {
		return ( $a['label'] == $b['label'] ) ? 0 : ( ( $a['label'] < $b['label'] ) ? -1 : 1 );
	}

	/**
	 * @see CPAC_Column::display_settings()
	 * @since 1.0
	 */
	public function display_settings() {

		$optiongroups = array();

		// Needs NOT be empty in order to trigger location rules set by ACF
		$match_args = true;

		if ( $this->storage_model->key ) {

			// Applies to all
			add_filter( 'acf/location/rule_match/user_type', '__return_true', 16 );

			// Applies to pages
			if ( 'page' == $this->storage_model->key ) {
				add_filter( 'acf/location/rule_match/page', '__return_true', 16 );
				add_filter( 'acf/location/rule_match/page_type', '__return_true', 16 );
				add_filter( 'acf/location/rule_match/page_parent', '__return_true', 16 );
				add_filter( 'acf/location/rule_match/page_template', '__return_true', 16 );
			}

			// Applies to posts only
			if ( 'post' == $this->storage_model->key ) {
				add_filter( 'acf/location/rule_match/post_format', '__return_true', 16 );
			}

			// Applies to specific metatypes
			switch ( $this->storage_model->type ) {
				case 'post' :
					add_filter( 'acf/location/rule_match/post_type', '__return_true', 16 );
					add_filter( 'acf/location/rule_match/post', '__return_true', 16 );
					add_filter( 'acf/location/rule_match/post_category', '__return_true', 16 );
					add_filter( 'acf/location/rule_match/post_status', '__return_true', 16 );
					add_filter( 'acf/location/rule_match/post_taxonomy', '__return_true', 16 );
				break;
				case 'media' :
					add_filter( 'acf/location/rule_match/post_type', '__return_true', 16 );
					add_filter( 'acf/location/rule_match/post', '__return_true', 16 );
					add_filter( 'acf/location/rule_match/post_category', '__return_true', 16 );
					add_filter( 'acf/location/rule_match/post_status', '__return_true', 16 );
					add_filter( 'acf/location/rule_match/post_taxonomy', '__return_true', 16 );

					add_filter( 'acf/location/rule_match/attachment', '__return_true', 16 );
				break;
				case 'taxonomy' :
					add_filter( 'acf/location/rule_match/taxonomy', '__return_true', 16 );
				break;
				case 'user' :
					add_filter( 'acf/location/rule_match/user_form', '__return_true', 16 );
					add_filter( 'acf/location/rule_match/user_role', '__return_true', 16 );
				break;
				case 'comment' :
					add_filter( 'acf/location/rule_match/comment', '__return_true', 16 );
				break;
			}

		}

		$groups = acf_get_field_groups( $match_args );

		// Remove all location filters for the next storage_model
		remove_filter( 'acf/location/rule_match/page', '__return_true', 16 );
		remove_filter( 'acf/location/rule_match/page_type', '__return_true', 16 );
		remove_filter( 'acf/location/rule_match/page_parent', '__return_true', 16 );
		remove_filter( 'acf/location/rule_match/page_template', '__return_true', 16 );

		remove_filter( 'acf/location/rule_match/post_format', '__return_true', 16 );

		remove_filter( 'acf/location/rule_match/post_type', '__return_true', 16 );
		remove_filter( 'acf/location/rule_match/post', '__return_true', 16 );
		remove_filter( 'acf/location/rule_match/post_category', '__return_true', 16 );
		remove_filter( 'acf/location/rule_match/post_status', '__return_true', 16 );
		remove_filter( 'acf/location/rule_match/post_taxonomy', '__return_true', 16 );

		remove_filter( 'acf/location/rule_match/attachment', '__return_true', 16 );

		remove_filter( 'acf/location/rule_match/taxonomy', '__return_true', 16 );

		remove_filter( 'acf/location/rule_match/user_form', '__return_true', 16 );
		remove_filter( 'acf/location/rule_match/user_role', '__return_true', 16 );

		remove_filter( 'acf/location/rule_match/comment', '__return_true', 16 );

		foreach ( $groups as $group_id => $group ) {
			$options = array();

			$fields = acf_get_fields( $group );

			foreach ( $fields as $field ) {
				if ( in_array( $field['type'], array( 'tab' ) ) ) {
					continue;
				}

				$options[ $field['key'] ] = array(
					'type' => $field['type'],
					'label' => $field['label']
				);
			}

			if ( ! empty( $options ) ) {

				// when using ACF lite with the php export, all group ID's are zero.
				if ( ! empty( $group['ID'] ) ) {
					$group_id = $group['ID'];
				}

				$optiongroups[ $group_id ] = array(
					'title' 	=> $group['title'],
					'options' 	=> $options
				);
			}
		}

		// sort field by option label
		foreach ( $optiongroups as $k => $optiongroup ) {
			uasort( $optiongroups[ $k ]['options'], array( $this, 'sort_by_label' ) );
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
									<option data-field-type="<?php echo esc_attr( $option['type'] ); ?>" value="<?php echo $field_key ?>"<?php selected( $field_key, $this->get_field_key() ) ?>><?php echo $option['label'] ? $option['label'] : __( 'empty label', 'codepress-admin-columns' ); ?></option>
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