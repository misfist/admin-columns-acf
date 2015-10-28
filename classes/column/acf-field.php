<?php
/**
 * ACF Field for Advanced Custom Fields 4.x
 *
 * @since 1.1
 * @abstract
 */
abstract class CPAC_ACF_Column_ACF_Field extends CPAC_Column {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.1
	 */
	public function init() {

		parent::init();

		// Properties
		$this->properties['type']			= 'column-acf_field';
		$this->properties['label']			= __( 'Advanced Custom Fields', 'codepress-admin-columns' );
		$this->properties['is_cloneable']	= true;
		$this->properties['group']			= 'acf';

		// Options
		$this->options['field']				= '';

		$this->options['image_size']		= '';
		$this->options['image_size_w']		= 80;
		$this->options['image_size_h']		= 80;

		$this->options['excerpt_length']	= 15;

		// used by user fieldtype
		$this->options['display_author_as'] = '';

		// used by repeater fieldtype
		$this->options['sub_field'] = '';
	}

	/**
	 * Get field type from linked ACF field
	 *
	 * @since 1.1
	 */
	public function get_field_type() {
 		$field = $this->get_field();
 		return isset( $field['type'] ) ? $field['type'] : false;
	}

	/**
	 * Get field label from linked ACF field
	 *
	 * @since 1.3
	 */
	public function get_field_label() {

 		$field = $this->get_field();
 		if ( ! isset( $field['label'] ) ) {
 			return false;
 		}
		return $field['label'];
	}

	/**
	 * Get Field key from linked ACF field
	 *
	 * @since 1.1
	 *
	 * @param string ACF field Key
	 */
	public function get_field_key() {
		return $this->options->field;
	}

	/**
	 * Get the ACF field object
	 *
	 * @since 1.1
	 * @abstract
	 */
	public function get_field() {
		return $this->get_field_key() ? cpac_get_acf_field( $this->get_field_key() ) : false;
	}

	/**
	 * @since NEWVERSION
	 */
	public function save( $id, $value ) {
		update_field( $this->get_field_key(), $value, $this->get_formatted_id( $id ) );
	}

	/**
	 * Get subfield type
	 *
	 * @since 1.2
	 */
	public function get_sub_field_type() {
		$field = $this->get_sub_field();
		return isset( $field['type'] ) ? $field['type'] : false;
	}

	/**
	 * Get subfield type
	 *
	 * @since NEWVERSION
	 */
	public function get_sub_field() {
		if ( empty( $this->options->sub_field ) || ! ( $field = cpac_get_acf_field( $this->options->sub_field ) ) ) {
			return false;
		}
		return $field;
	}

	/**
	 * Get formatted ID
	 *
	 * @since 1.2.2
	 */
	public function get_formatted_id( $id ) {

		if ( 'taxonomy' == $this->storage_model->type ) {
			$id = $this->storage_model->taxonomy . '_' . $id;
		}
		elseif ( 'user' == $this->storage_model->type ) {
			$id = 'user_' . $id;
		}
		elseif ( 'comment' == $this->storage_model->type ) {
			$id = 'comment_' . $id;
		}
		return $id;
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.1
	 */
	public function get_raw_value( $id, $single = true ) {

		$rawvalue = get_field( $this->get_field_key(), $this->get_formatted_id( $id ), false );

		if ( $rawvalue === false || $rawvalue === NULL ) {
			$rawvalue = '';
		}

		return $rawvalue;
	}

	/**
	 * Get additional item data for a specific item for this column
	 *
	 * @since 1.0
	 *
	 * @param mixed $post Optional. Post ID or post object. Defaults to current post
	 * @return array Additional item data
	 */
	public function get_item_data( $post = NULL ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return array();
		}

		$itemdata = array();

		if ( $this->get_field_type() == 'file' ) {
			$itemdata['url'] = wp_get_attachment_url( $this->get_raw_value( $post->ID ) );
		}

		return $itemdata;
	}

	/**
	 * Format ACF Value
	 *
	 * @since 1.0
	 *
	 * @param mixed $value Unformatted value
	 * @param array $acf_field ACF field options
	 * @param int $id Item ID
	 * @return mixed Formatted value
	 */
	public function format_acf_value( $value, $field, $id ) {

		$originalvalue = $value;

		switch ( $field['type'] ) {
			case 'select':
			case 'checkbox':
			case 'radio':
				$values = (array) $value;
				foreach ( $values as $index => $value ) {
					if ( isset( $field['choices'][ $value ] ) ) {
						$values[ $index ] = $field['choices'][ $value ];
					}
				}
				$value = implode( ', ', $values );

				break;
			case 'true_false':
				$value = $this->get_asset_image( $value == '1' ? 'checkmark.png' : 'no.png' );
				break;
			case 'image':
				$value = implode( $this->get_thumbnails( $value, array(
					'image_size'	=> $this->options->image_size,
					'image_size_w'	=> $this->options->image_size_w,
					'image_size_h'	=> $this->options->image_size_h,
				)));
				break;
			case 'file':
				if ( $value ) {
					$attachment = get_attached_file( $value );

					if ( ! $attachment ) {
						$value = '<em>' . __( 'Invalid attachment', 'codepress-admin-columns' ) . '</em>';
					}
					else {
						$value = '<a href="' . esc_attr( wp_get_attachment_url( $value ) ) . '" target="_blank">' . basename( $attachment ) . '</a>';
					}
				}
				break;
			case 'page_link':
			case 'post_object':
			case 'relationship':
				$values = (array) $value;

				foreach ( $values as $index => $value ) {
					if ( ! $value ) {
						unset( $values[ $index ] );
						continue;
					}

					$title = get_the_title( $value );
					if ( $link = get_edit_post_link( $value ) ) {
						$title = "<a href='{$link}'>{$title}</a>";
					}

					$values[ $index ] = $title;
				}

				$value = implode( ', ', $values );
				break;
			case 'password':
				$pwchar = '&#9679;';
				$pwchar_length = strlen( $pwchar );
				$value = str_pad( '', strlen( $value ) * $pwchar_length, $pwchar );
				break;
			case 'taxonomy':
				$values = (array) $value;
				foreach ( $values as $index => $value ) {
					$term = get_term( $value, $field['taxonomy'] );

					if ( ! is_wp_error( $term ) && $term ) {

						$name = '';
						if ( $edit_link = get_edit_term_link( $term->term_id, $term->taxonomy ) ) {
							$name = "<a href='{$edit_link}'>{$term->name}</a>";
						}

						$values[ $index ] = $name;
					}
				}

				$value = implode( ', ', $values );
				break;
			case 'textarea':
				if ( ! empty( $this->options->excerpt_length ) ) {
					$value = $this->get_shortened_string( $value, $this->options->excerpt_length );
				}
				break;
			case 'url':
				if ( $value ) {
					$value = '<a target="_blank" href="' . $value . '">' . str_replace( array( 'http://', 'https://' ), '', $value ) . '</a>';
				}
				break;
			case 'user':
				$values = (array) $value;
				foreach ( $values as $index => $value ) {
					if ( $user = get_userdata( $value ) ) {
						$name = $this->get_display_name( $user->ID );
						$link = get_edit_user_link( $user->ID );
						$values[ $index ] = $link ? "<a href='{$link}'>{$name}</a>" : $name;
					}
				}

				$value = implode( ', ', $values );
				break;
			case 'date_picker':
				if ( $value ) {

					// PHP 5.3.0 and higher
					if ( method_exists( 'DateTime', 'createFromFormat' ) && ( $date = DateTime::createFromFormat( $this->parse_jquery_dateformat( $field['return_format'] ), $value ) ) ) {
						$value = $date->format( $this->parse_jquery_dateformat( $field['display_format'] ) );
					}

					// PHP 5.2.0 and lower
					else {
						$value = $this->get_date( $value );
					}
				}
				break;
			case 'wysiwyg':
				$value = $this->get_shortened_string( $value, $this->options->excerpt_length );
				break;
			case 'text':
				$value = html_entity_decode( $value );
				break;
			case 'textarea':
				$value = esc_html( $this->get_shortened_string( $value, $this->options->excerpt_length ) );
				break;
			case 'email':
				$value = "<a href='mailto:{$value}'>{$value}</a>";
				break;
			case 'google_map':
				$map_data = array();
				if ( ! empty( $value['address'] ) ) {
					$map_data[] = $value['address'];
				}
				if ( ! empty( $value['lat'] ) ) {
					$map_data[] = $value['lat'];
				}
				if ( ! empty( $value['lng'] ) ) {
					$map_data[] = $value['lng'];
				}
				$value = implode( "<br/>\n", $map_data );
				break;
			case 'color_picker':
				$value = $this->get_color_for_display( $value );
				break;
			case 'gallery':
				$values = (array) $value;

				foreach ( $values as $index => $value ) {
					if ( ! $value ) {
						unset( $values[ $index ] );
						continue;
					}

					$image = implode( $this->get_thumbnails( $value, array(
						'image_size'	=> $this->options->image_size,
						'image_size_w'	=> $this->options->image_size_w,
						'image_size_h'	=> $this->options->image_size_h,
					) ) );

					$values[ $index ] = '<div class="cacie-item" data-cacie-id="' . esc_attr( $value ) . '">' . $image . '</div>';
				}
				$value = implode( '', $values );
				break;
			case 'flexible_content':
				$value = '';
				if ( ! empty( $field['layouts'] ) && function_exists( 'acf_get_value' ) ) {

					if ( $field_values = acf_get_value( $this->get_formatted_id( $id ), $field ) ) {

						$labels = array();
						foreach ( $field['layouts'] as $layout ) {
							$labels[ $layout['name'] ] = $layout['label'];
						}

						$layouts = array();
						foreach ( $field_values as $values ) {
							$layouts[ $values['acf_fc_layout'] ] = array(
								'count' => empty( $layouts[ $values['acf_fc_layout'] ] ) ? 1 : ++$layouts[ $values['acf_fc_layout'] ]['count'],
								'label' => $labels[ $values['acf_fc_layout'] ]
							);
						}

						$output = array();
						foreach ( $layouts as $layout ) {
							$label = $layout['label'];

							if ( $layout['count'] > 1 ) {
								$label .= '<span class="cpac-rounded">' . $layout['count'] . '</span>';
							}

							$output[] = $label;
						}
						$value = implode( '<br/>', $output );
					}
				}
				break;
			case 'repeater':
				$value = '';

				if ( ! empty( $field['sub_fields'] ) && function_exists( 'acf_get_value' ) ) {

					if ( $field_values = acf_get_value( $this->get_formatted_id( $id ), $field ) ) {

						$sub_fields = array();
						foreach ( $field['sub_fields'] as $k => $sub_field ) {
							$sub_fields[ $sub_field['key'] ] = $sub_field;
						}

						$output = array();

						foreach ( $field_values as $values ) {
							foreach ( $values as $field_key => $v ) {
								if ( ! isset( $sub_fields[ $field_key ] ) ) {
									continue;
								}
								// subfield selected?
								if ( ! empty( $this->options->sub_field ) && ( $this->options->sub_field !== $field_key ) ) {
									continue;
								}
								$output[] = '<div class="cacie-item" data-cacie-id="' . esc_attr( $field_key ) . '">' . $this->format_acf_value( $v, $sub_fields[ $field_key ], $id ) . '</div>';
							}
						}
						$value = implode( '', $output );
					}
				}
				break;
		}

		if ( is_array( $value ) ) {
			$value = '(array)';
		}

		// deprecated
		$value = apply_filters( 'cpac/acf/column-acf_field/format_acf_value', $value, $field, $id, $this );

		/**
		 * Filter the ACF value before displaying in the column
		 *
		 * @since 1.2.2
		 *
		 * @param string $value ACF value
		 * @param array $field ACF field properties
		 * @param int $id Post ID
		 * @param string $originalvalue Original ACF value
		 * @param object $this Column Object
		 */
		$value = apply_filters( 'cac/acf/format_acf_value', $value, $field, $id, $originalvalue, $this );

		$prepend = ! empty( $field['prepend'] ) ? $field['prepend'] . ' ' : '';
		$append = ! empty( $field['append'] ) ? $field['append'] . ' ' : '';

		// remove &nbsp; characters
		$prepend = str_replace( chr( 194 ) . chr( 160 ), ' ', $prepend );
		$append = str_replace( chr( 194 ) . chr( 160 ), ' ', $append );

		$value = $prepend . $value . $append;

		return $value;
	}

	/**
	 * @see CPAC_Column::get_sorting_value()
	 */
	public function get_sorting_value( $id ) {

		$value = $this->get_raw_value( $id );

		if ( $value ) {
			switch ( $this->get_field_type() ) {

				case 'page_link' :
				case 'post_object' :
				case 'relationship' :
					$value = (array) $value;
					$value = get_post_field( 'post_title', $value[0] );
				break;

				case 'user' :
					$value = (array) $value;
					if ( $user = get_userdata( $value[0] ) ) {
						$value = $user->display_name;
					}
				break;

				case 'taxonomy' :
					$value = (array) $value;
					if ( isset( $field['taxonomy'] ) && ( $term = get_term_by( 'id', $value[0], $field['taxonomy'] ) ) ) {
						$value = $term->name;
					}
				break;
			}
		}

		if ( is_array( $value ) ) {
			$value = $this->recursive_implode( '', $value );
		}

		return $value;
	}

	/**
	 * Translate a jQuery date format to the PHP date format
	 *
	 * @since 1.1
	 *
	 * @param string $format jQuery date format
	 * @return string PHP date format
	 */
	public function parse_jquery_dateformat( $format )	{
		$replace = array(
			'^dd^d' => 'j',
			'dd' => 'd',
			'DD' => 'l',
			'o' => 'z',
			'MM' => 'F',
			'^mm^m' => 'n',
			'mm' => 'm',
			'yy' => 'Y'
		);

		$replace_from = array();
		$replace_to = array();

		foreach ( $replace as $from => $to ) {
			$replace_from[] = '/' . $from . '/';
			$replace_to[] = $to;
		}

		return preg_replace( $replace_from, $replace_to, $format );
	}

	/**
	 * @since 1.1
	 */
	public function display_field_sub_field_picker() {

		$sub_fields = array();

		if ( $field = $this->get_field() ) {
			if ( ! empty( $field['sub_fields'] ) ) {
				foreach ( $field['sub_fields'] as $sub_field ) {
					$sub_fields[ $sub_field['key'] ] = $sub_field['label'];
				}
			}
		}
		?>
		<tr class="column-sub_field">
			<?php $this->label_view( __( 'Sub Field', 'codepress-admin-columns' ), __( 'Select a repeater sub field.', 'codepress-admin-columns' ), 'sub_field' ); ?>
			<td class="input">
			<?php if ( $sub_fields ) : ?>
				<select name="<?php $this->attr_name( 'sub_field' ); ?>" id="<?php $this->attr_id( 'sub_field' ); ?>">
				<?php foreach ( $sub_fields as $key => $label ) : ?>
					<option value="<?php echo $key; ?>"<?php selected( $key, $this->options->sub_field ) ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
				</select>
			<?php else : ?>
				<?php _e( 'No ACF subfields available.', 'codepress-admin-columns' ); ?>
			<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Display field specific settings
	 *
	 * @since 1.2
	 */
	protected function display_optional_settings( $field_type ) {

		switch ( $field_type ) {
			case 'image' :
			case 'gallery' :
				$this->display_field_preview_size();
			break;
			case 'textarea' :
				$this->display_field_excerpt_length();
			break;
			case 'user':
				$this->display_field_user_format();
			break;
			case 'repeater' :
				$this->display_field_sub_field_picker();

				$this->display_optional_settings( $this->get_sub_field_type() );
			break;
		}
	}
}