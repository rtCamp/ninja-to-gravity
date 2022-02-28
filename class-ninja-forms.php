<?php
/**
 * Ninja_Forms class file. 
 * This fclass handles the NF to GF conversion process.
 * 
 * @package ninja-to-gravity
 */

namespace Ninja2Gravity;

use GFAPI;
use GF_Webhooks;

/**
 * The Class that handles Ninja forms to GravityForms conversion.
 */
class Ninja_Forms {


	/**
	 * The mapping from Ninja form key to Ninja form field.
	 * 
	 * @var array $mapping
	 */
	private static $mapping = [];

	/**
	 * The mapping from Ninja form key to Ninja form field.
	 * 
	 * @var array $mapping
	 */
	private static $next_available_index = 1;

	/**
	 * Get all the Ninja forms present in the site and return them.
	 * 
	 * @return array Returns a list of forms.
	 */
	public static function get_forms() {

		$forms = [];

		if ( ! function_exists( 'Ninja_Forms' ) ) {
			return [];
		}

		foreach ( Ninja_Forms()->form()->get_forms() as $form ) {
			$forms[] = Ninja_Forms()->form( $form->get_id() );
		}

		return $forms;
	}


	public static function add_field( &$form, $field_args, $field_key = '' ) {
		
		$field_args['id'] = self::$next_available_index;

		if ( '' !== $field_key ) {
			self::$mapping[ $field_key ] = [
				'order' => self::$next_available_index,
				'field' => $field_args['label'] ?? '',
			];
		}

		self::$next_available_index++;
		
		$gf_field = \GF_Fields::create( $field_args );
	
		$form['fields'][] = $gf_field;
	}

	/**
	 * Get the contents of .nff file and parse the file and returns the array.
	 * 
	 * @param string $file The name of export file.
	 * 
	 * @return array The parsed array containing the data.
	 */
	public static function nff_to_export( $file ) {

		if ( ! file_exists( $file ) ) {
			return false;
		}

		$nff = file_get_contents( $file ); // phpcs:ignore

		if ( empty( $nff ) ) {
			return false;
		}

		return json_decode( $nff, true );
	}

	/**
	 * Check if a condition can be converted to a default value.
	 * 
	 * @param array $condition The condition to be checked.
	 * 
	 * @return bool The result of the check.
	 */
	public static function can_condition_be_converted_to_default_value( $condition ) {
		$when = $condition['when'];
		$then = $condition['then'];
		if ( 1 === count( $when ) && 1 === count( $then ) ) {
			if ( $when[0]['key'] === $then[0]['key'] && '' === $when[0]['value'] ) {
				return true;
			}
		}
		return false;
	} 

	/**
	 * Calculate the widths of all the fields in the form.
	 * 
	 * @param array $form_export The form data.
	 * 
	 * @return array The mapping from field ID to the width.
	 */
	public static function calculate_field_widths( $form_export ) {
		$spans = [];
		foreach ( $form_export['settings']['formContentData'] as $content_data ) {
			foreach ( $content_data['formContentData'] as $row ) { // Row.
				foreach ( $row['cells'] as $cell ) { // Column.
					$percent_width = intval( $cell['width'] );
					$column_width  = round( $percent_width / 100.0 * 12 ); // 12 column layout.
					foreach ( $cell['fields'] as $field ) { // Individual field.
						$spans[ $field ] = $column_width;
					}
				}
			}
		}
		return $spans;
	}

	/**
	 * Stores the index of first item in a page and the page title.
	 * 
	 * @param array $form_export The form data.
	 * 
	 * @return array The mapping from index to the page title.
	 */
	public static function get_page_titles_and_indexes( $form_export ) {
		$page_data = [];
		$order     = 0;
		foreach ( $form_export['settings']['formContentData'] as $content_data ) {
			$page_data[ $order ] = $content_data['title'];
			$count               = 0;
			foreach ( $content_data['formContentData'] as $row ) { // Row.
				foreach ( $row['cells'] as $cell ) { // Column.
					$count += count( $cell['fields'] );
				}
			}
			$order += $count;
		}
		return $page_data;
	}
	
	/**
	 * The main method to craete a gravity form out of the export data.
	 * 
	 * @param array $form_export The export array from Ninja forms.
	 * 
	 * @return int/bool The ID of the form if it was created, else false. 
	 */
	public static function convert_to_gravity_form( $form_export ) {

		$gravity_form = [];

		$gravity_form['title'] = $form_export['settings']['title'];

		$defaults   = [];
		$conditions = $form_export['settings']['conditions'] ?? [];
		foreach ( $conditions as $condition ) {
			if ( self::can_condition_be_converted_to_default_value( $condition ) ) {
				$defaults[ $condition['when'][0]['key'] ] = $condition['then'][0]['value'];
			}
		}

		$page_data = self::get_page_titles_and_indexes( $form_export ); // Stores the index of first item in a page and the page title.
		$spans     = self::calculate_field_widths( $form_export );

		foreach ( $form_export['fields'] as $order => $nf_field ) {

			if ( 1 < count( $page_data ) && in_array( $order, array_keys( $page_data ), true ) ) { // More than 1 parts, means it's a multi step form.
				if ( 0 !== $order ) {
					$arguments = [
						'type' => 'page',
					];
	
					self::add_field( $gravity_form, $arguments );
				}
				
				$arguments = [
					'type'  => 'section',
					'label' => $page_data[ $order ],
				];

				self::add_field( $gravity_form, $arguments );
			}


			$type = self::field_mapping( $nf_field['type'] );

			if ( empty( $type ) ) {
				continue;
			}

			if ( 'submit' === $type ) {
				$gravity_form['button'] = [
					'type'     => 'text',
					'text'     => $nf_field['label'],
					'imageUrl' => '',
				];
				continue;
			}
			
			$css_classes = [
				sprintf( '%s-field', $type ),
			];

			$is_required = $nf_field['required'] ?? '';

			$arguments = [
				'type'                 => $type,
				'layoutGridColumnSpan' => $spans[ $nf_field['key'] ],
				'isRequired'           => ( '1' === $is_required || 1 === $is_required ),
				'placeholder'          => $nf_field['placeholder'] ?? '',
				'cssClass'             => implode( ' ', $css_classes ),
			];
			
			if ( 'section' !== $type ) {
				$arguments['label'] = $nf_field['label'];
			}

			if ( in_array( $type, [ 'checkbox', 'select', 'multiselect', 'radio' ], true ) ) {
				$arguments = self::process_options( $nf_field, $arguments );
			}

			if ( 'html' === $type ) {
				$arguments['content'] = $nf_field['default'];
			}

			$default = $nf_field['default'] ?? '';

			if ( false !== strpos( $default, '{querystring:' ) ) {
				
				$arguments['allowsPrepopulate'] = true;
				$arguments['inputName']         = str_replace( [ '{querystring:', '}' ], '', $default );
				$default                        = '';

			}
			
			$default = self::convert_merge_tags( $default );

			
			$default_from_condition = $defaults[ $nf_field['key'] ] ?? '';

			if ( ! empty( $default_from_condition ) ) {
				$default = $default_from_condition;
			}

			$arguments['defaultValue'] = $default;

			self::add_field( $gravity_form, $arguments, $nf_field['key'] );

		}

		$gravity_form['confirmations'] = [];
		$gravity_form['notifications'] = [];
		$webhooks                      = [];

		// Process and migrate all ninja form actions.
		foreach ( $form_export['actions'] as $action ) {
			if ( 'redirect' === $action['type'] ) {
				$gravity_form['confirmations'][] = [
					'id'   => wp_generate_uuid4(),
					'name' => $action['label'],
					'type' => 'redirect',
					'url'  => self::maybe_prepend_base_url( $action['redirect_url'] ),
				];
			} elseif ( 'email' === $action['type'] ) {
				$from_address = ! empty( $action['from_address'] ) ? $action['from_address'] : '{admin_email}';
				$reply_to     = ! empty( $action['reply_to'] ) ? $action['reply_to'] : '{admin_email}';
				$from_address = self::convert_merge_tags( $from_address );
				$reply_to     = self::convert_merge_tags( $reply_to );
				$notification = [
					'id'                => wp_generate_uuid4(),
					'isActive'          => ( '1' === $action['active'] ),
					'name'              => $action['label'],
					'service'           => 'wordpress',
					'event'             => 'form_submission',
					'toType'            => 'email',
					'cc'                => $action['cc'],
					'bcc'               => $action['bcc'],
					'subject'           => $action['email_subject'],
					'message'           => self::convert_merge_tags( $action['email_message'] ),
					'from'              => $from_address,
					'fromName'          => $action['from_name'],
					'replyTo'           => $reply_to,
					'disableAutoformat' => false,
					'enableAttachments' => false,
				];
				$matches      = [];
				$to_email     = self::convert_merge_tags( $action['to'] );
				preg_match( '/{.*:(\d+)}/', $to_email, $matches );

				if ( ! empty( $matches ) ) {
					$notification['toType'] = 'field';
					$notification['to']     = $matches[1];
				} else {
					$notification['toType'] = 'email';
					$notification['to']     = $to_email;
				}
				$gravity_form['notifications'][] = $notification; 
			} elseif ( 'webhooks' === $action['type'] ) {
				$meta = [
					'feedName'        => $action['label'],
					'requestURL'      => $action['wh-remote-url'],
					'requestMethod'   => strtoupper( $action['wh-remote-method'] ),
					'requestFormat'   => 'form',
					'requestBodyType' => 'select_fields',
					'fieldValues'     => [],
				];
				foreach ( $action['wh-args'] as $field ) {
					$gf_field = [
						'key'          => 'gf_custom',
						'value'        => 'gf_custom',
						'custom_key'   => $field['key'],
						'custom_value' => self::convert_merge_tags( $field['value'] ),
					];

					$meta['fieldValues'][] = $gf_field;
				}
				$webhooks[] = $meta;
			}
		}

		$form_id = GFAPI::add_form( $gravity_form );
		
		$webhook_handler = new GF_Webhooks();
		foreach ( $webhooks as $webhook ) {
			$webhook_handler->insert_feed( $form_id, true, $webhook );
		}
		
		if ( empty( $form_id ) ) {
			return false;
		}

		return $form_id;

	}

	/**
	 * If the URL is relative, then the full permalink will be fetched and returned, 
	 * external links will be returned as is.
	 * 
	 * @param string $link The link to be validated.
	 * 
	 * @return string the modified link.
	 */
	public static function maybe_prepend_base_url( $link ) {
		if ( 0 === strpos( $link, '/' ) ) {
			$post = get_page_by_path( $link, OBJECT, [ 'post', 'page' ] ); // phpcs:ignore
			if ( false !== $post ) {
				return get_permalink( $post );
			}
		}
		return $link;
	}

	/**
	 * Process fields with multiple options, such as radio and checkboxes.
	 * 
	 * @param array $field The field data array.
	 * @param array $arguments The arguments that needs to be changed based on the field type.
	 * 
	 * @return array The modified array of arguments.
	 */
	public static function process_options( $field, $arguments ) {
		$options       = $field['options'] ?? [];
		$field_id      = self::$next_available_index;
		$new_arguments = [];

		if ( ! empty( $options ) ) {
			$choices = [];
			foreach ( $options as $option ) {
				array_push(
					$choices,
					[
						'text'       => $option['label'],
						'value'      => $option['value'],
						'isSelected' => ( '1' === ( $option['selected'] ?? '' ) ),
					]
				);
			}
			$new_arguments['choices'] = $choices;
		} else {
			$new_arguments['labelPlacement'] = 'hidden_label';
			$new_arguments['choices']        = [
				[
					'text'       => $field['label'],
					'value'      => $field['key'],
					'isSelected' => 'unchecked' !== $field['default_value'],
				],
			];
		}

		// Set inputs key if the field is checkbox.
		if ( 'checkbox' === $arguments['type'] ) {
			$inputs = [];
			foreach ( $new_arguments['choices'] as $index => $choice ) {
				array_push(
					$inputs,
					[
						'id'    => $field_id . '.' . ( $index + 1 ),
						'label' => $choice['text'],
						'name'  => '',
					]
				);
			}
			$new_arguments['inputs']            = $inputs;
			$new_arguments['enableChoiceValue'] = true;
		}

		return array_merge( $arguments, $new_arguments );
	}

	/**
	 * Contains a Ninja Forms field to Gravity forms field mapping.
	 * 
	 * @param string $nf_field The Ninja form field name.
	 * 
	 * @return string The corresponding Gravity form field name.
	 */
	public static function field_mapping( $nf_field = null ) {

		$mapping = [
			'address'         => 'text',
			'address2'        => 'text',
			'button'          => null,
			'checkbox'        => 'checkbox',
			'city'            => 'text',
			'confirm'         => 'text',
			'date'            => 'date',
			'email'           => 'email',
			'firstname'       => 'text',
			'file_upload'     => 'fileupload',
			'html'            => 'html',
			'hidden'          => 'hidden',
			'lastname'        => 'text',
			'listcheckbox'    => 'checkbox',
			'listcountry'     => 'select',
			'listimage'       => null,
			'listmultiselect' => 'multiselect',
			'listradio'       => 'radio',
			'listselect'      => 'select',
			'liststate'       => 'select',
			'note'            => 'textarea',
			'number'          => 'number',
			'password'        => 'password',
			'passwordconfirm' => 'password',
			'phone'           => 'phone',
			'product'         => 'product',
			'quantity'        => 'quantity',
			'recaptcha'       => 'captcha',
			'recaptchav3'     => 'captcha',
			'repeater'        => 'repeater',
			'shipping'        => 'shipping',
			'spam'            => null,
			'starrating'      => null,
			'submit'          => 'submit',
			'terms'           => null,
			'textarea'        => 'textarea',
			'textbox'         => 'text',
			'total'           => 'total',
			'unknown'         => null,
			'zip'             => 'text',
			'hr'              => 'section',
		];

		if ( empty( $nf_field ) ) {
			return $mapping;
		}

		if ( empty( $mapping[ $nf_field ] ) ) {
			return false;
		}

		return $mapping[ $nf_field ];

	}

	/**
	 * Convert merge tags from Ninja forms syntax to Gravityforms Syntax.
	 * 
	 * @param string $text The text that needs to be converted.
	 * 
	 * @return string The converted text. 
	 */
	public static function convert_merge_tags( $text ) {

		while ( false !== strpos( $text, '{field:' ) ) {
			$start_index = strpos( $text, '{field:', 0 ) + strlen( '{field:' );
			$end_index   = strpos( $text, '}', $start_index );
			$field_name  = substr( $text, $start_index, $end_index - $start_index );
			$replacement = self::convert_field_merge_tag( $field_name );
			$text        = preg_replace( '/{field:[0-9a-zA-Z_]*}/', $replacement, $text, 1 );
		}

		$text = str_replace( '{other:date}', '{date_dmy}', $text );
		$text = str_replace( '{other:time}', '', $text );
		$text = str_replace( '{wp:post_url}', '{embed_url}', $text );
		$text = str_replace( '{wp:admin_email}', '{admin_email}', $text );
		$text = str_replace( '{fields_table}', '{all_fields}', $text );
		return $text;
	}

	/**
	 * Takes in a Ninja Forms field value placeholder and converts it to a GravityForms placeholder.
	 *  
	 * @param string $field The placeholder that needs to be converted.
	 * 
	 * @return string The converted placeholder. 
	 */
	public static function convert_field_merge_tag( $field ) {
		$field    = self::$mapping[ $field ];
		$gf_field = sprintf( '{%s:%s}', $field['field'], $field['order'] );
		return $gf_field;
	}

}
