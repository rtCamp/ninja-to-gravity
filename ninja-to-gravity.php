<?php
/**
 * Plugin Name: Ninja To Gravity
 * Plugin URI: http://ninjaforms.com/?utm_source=Ninja+Forms+Plugin&utm_medium=readme
 * Description: Plugin to migrate forms from Ninja forms to Gravity Forms.
 * Version: 0.1
 * Author: rtCamp, Dishit Pala, Vijayaraghavan M
 * Author URI: https://rtcamp.com/
 * Text Domain: ninja-to-gravity
 * Domain Path: /lang/
 * 
 * @package ninja-to-gravity
 */

/**
 * Don't call this file directly.
 */
if ( ! class_exists( 'WP' ) ) {
	die();
}

require_once plugin_dir_path( __FILE__ ) . 'class-ninja-forms.php';

/**
 * Add 'Import Ninja Form' Tab to Settings.
 * 
 * @param array $setting_tabs The Array of tabs.
 * 
 * @return array Array of tabs after modification.
 */
function add_import_from_ninja_forms_tab( $setting_tabs ) {

	if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {

		if ( GFCommon::current_user_can_any( 'gravityforms_create_form' ) ) {
			$icon               = '<svg xmlns="http://www.w3.org/2000/svg" width="1024" height="650.533" viewBox="0 0 1024 650.533"><path fill="#FFF" d="M736.026 397.46c-1.39-44.462-25.698-87.232-67.81-110.484-110.608-61.07-256.143-61.844-370.774-1.53-43.4 22.84-68.72 63.107-70.14 112.013h508.724z"/><path fill="#414242" d="M227.3 397.46c1.42-48.907 26.742-88.902 70.14-111.74 114.634-60.314 260.165-60.087 370.78.982 42.108 23.252 66.417 66.295 67.804 110.756H837.14c.106 0 .18-6.723.18-10.384 0-53.77-12.003-104.446-33.362-150.14-3.35-8.693-7.93-17.54-13.157-26.395 42.53-14.31 84.17-22.31 115.02-28.59 37.56-7.65 59.1-12.6 46.5-22.91-27.39-22.42-62.26-46.71-62.26-46.71s140.11-115.79 0-80.74c-26.41 6.6-49.58 16.5-69.91 28.14-39.08 22.38-67.47 51.32-86.87 76.56C668.89 71.87 579.96 31.96 481.67 31.96c-196.424 0-355.658 158.69-355.658 355.11 0 3.657.07 10.384.18 10.384H227.3z"/><path fill="#414242" d="M383.03 397.46c-.41-27.853-16.23-50.26-35.728-50.26s-35.32 22.407-35.73 50.26h71.46zm268.883 0c-.41-27.853-16.23-50.26-35.73-50.26-19.495 0-35.317 22.407-35.728 50.26h71.458zm-535.92 199.932H91.79L47.6 529.146s.44 12.105.44 22.75v45.496H25v-100.62h25.228l43.163 67.665s-.43-11.373-.43-22.02v-45.644H116v100.62zm32.967-100.62h23.622V597.39H148.96zm147.573 100.62h-24.206l-44.185-68.246s.436 12.105.436 22.75v45.496h-23.04v-100.62h25.23l43.16 67.665s-.436-11.373-.436-22.02v-45.644h23.04v100.62zm56.442-2.04c0 10.64-2.188 18.08-6.27 22.746-3.79 4.374-10.06 7.436-18.23 7.436-6.997 0-13.704-1.454-15.017-2.187l.872-17.354c1.896.732 4.668 1.314 7.875 1.314 5.688 0 7.29-4.376 7.29-11.666v-98.86h23.48v98.58zm123.512 2.04H450.82l-7.143-20.27h-35.873l-7.145 20.27h-24.5l37.91-100.765h24.49l37.91 100.765zm-38.205-38.643l-12.542-36.17-12.54 36.17h25.082zm115.643-54.83h-43.162v39.51H551.3v7.14h-40.537v46.81h-7.873V496.77h51.035m75.395 102.37c-27.268 0-51.04-19.832-51.04-51.912 0-32.084 23.772-51.92 51.04-51.92 27.412 0 51.037 19.835 51.037 51.918 0 32.08-23.625 51.913-51.038 51.913m0-96.83c-22.9 0-42.73 16.48-42.73 44.77 0 28 19.98 44.62 42.72 44.62 23.04 0 42.87-16.63 42.87-44.63 0-28.14-19.84-44.77-42.88-44.77m141.16 95.08H760.4l-29.744-46.67h-12.83v46.66h-7.87v-100.6h23.34c18.95 0 31.344 10.21 31.344 26.97 0 16.77-12.1 24.79-24.786 26.68l30.622 46.95zm-21.58-89.24c-5.4-4.09-11.96-4.23-19.98-4.23h-11.08v39.66h11.08c8.02 0 14.58-.15 19.97-4.09 5.54-4.08 7.44-9.18 7.44-15.75 0-6.56-1.9-11.66-7.44-15.6m158.66 89.25h-7.88l-8.61-87.35-37.18 89.1h-.73l-37.32-89.24-8.31 87.49h-7.88l10.2-100.62h9.04l34.57 85.89 34.71-85.89h9.18m69.42 102.37c-19.54 0-28.58-14-30.77-18.08l6.12-4.38c3.65 6.7 12.4 15.6 25.38 15.6s23.477-8.02 23.477-21.14-13.42-18.09-19.25-20.42c-5.835-2.34-12.396-5.11-16.48-7.29-3.94-2.19-14.58-8.75-14.58-23.33s12.687-24.79 29.6-24.79c16.92 0 25.38 11.66 26.25 13.12l-5.54 4.81c-3.787-4.82-9.62-10.95-21.29-10.95s-20.994 6.13-20.994 17.51c0 11.38 7.87 15.9 15.02 18.96 7.14 3.06 18.37 7.73 20.705 9.04 2.33 1.32 14.58 8.02 14.58 23.33s-12.69 28-32.23 28"/></svg>';
			$setting_tabs['40'] = array(
				'name'  => 'import_ninja_form',
				'label' => __( 'Import Ninja Form', 'ninja-to-gravity' ),
				'icon'  => $icon,
			);
		}
	}

	return $setting_tabs;

} 

add_filter( 'gform_export_menu', 'add_import_from_ninja_forms_tab' );

/**
 * Render the form to import forms from Ninja Forms to Gravity Forms. 
 */
function add_ninja_form_option() {
	if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
		wp_die( 'You do not have permission to access this page' );
	}

	if ( isset( $_POST['import_forms'] ) ) {

		check_admin_referer( 'gf_import_forms', 'gf_import_forms_nonce' );

		if ( ! empty( $_FILES['gf_import_file']['tmp_name'][0] ) ) {

			// Set initial count to 0.
			$count = 0;

			$forms        = array();
			$import_files = $_FILES['gf_import_file']['tmp_name']; // phpcs:ignore
			
			// Loop through each uploaded file.
			foreach ( $import_files as $import_file ) {

				$export = \Ninja2Gravity\Ninja_Forms::nff_to_export( $import_file );
				$form   = \Ninja2Gravity\Ninja_Forms::convert_to_gravity_form( $export );

				if ( ! empty( $form ) ) {
					$count ++;
					$forms[] = [ 'id' => $form ];
				}
			}

			if ( 0 === $count ) {
				$error_message = sprintf(
					// translators: Ignore the %s, they're placeholders for anchor tags.
					esc_html__( 'Forms could not be imported. Please make sure your files have the .json extension, and that they were generated by the %1$sGravity Forms Export form%2$s tool.', 'ninja-to-gravity' ),
					'<a href="admin.php?page=gf_export&view=export_form">',
					'</a>'
				);
				GFCommon::add_error_message( $error_message );
			} elseif ( '-1' == $count ) {
				GFCommon::add_error_message( esc_html__( 'Forms could not be imported. Your export file is not compatible with your current version of Gravity Forms.', 'ninja-to-gravity' ) );
			} else {
				$form_text = 1 < $count ? esc_html__( 'forms', 'ninja-to-gravity' ) : esc_html__( 'form', 'ninja-to-gravity' );
				$edit_link = 1 === $count ? "<a href='admin.php?page=gf_edit_forms&id={$forms[0]['id']}'>" . esc_html__( 'Edit Form', 'ninja-to-gravity' ) . '</a>' : '';
				// translators: %d - Count, %s - form/forms depending upon the count.
				GFCommon::add_message( sprintf( esc_html__( 'Gravity Forms imported %1$d %2$s successfully', 'ninja-to-gravity' ), $count, $form_text ) . ". $edit_link" );
			}
		}

		if ( ! empty( $_POST['gf_import_id'] ) ) {
			$form_id = filter_input( INPUT_POST, 'gf_import_id', FILTER_SANITIZE_SPECIAL_CHARS );
			$export  = Ninja_Forms()->form( $form_id )->export_form( true );
			$form_id = \Ninja2Gravity\Ninja_Forms::convert_to_gravity_form( $export );
			if ( false === $form_id ) {
				$error_message = sprintf(
					// translators: Ignore the %s, they're placeholders for anchor tags.
					esc_html__( 'Forms could not be imported. Please make sure your files have the .json extension, and that they were generated by the %1$sGravity Forms Export form%2$s tool.', 'ninja-to-gravity' ),
					'<a href="admin.php?page=gf_export&view=export_form">',
					'</a>'
				);
				GFCommon::add_error_message( $error_message );
			} else {
				$edit_link = "<a href='admin.php?page=gf_edit_forms&id={$form_id}'>" . esc_html__( 'Edit Form', 'ninja-to-gravity' ) . '</a>';
				GFCommon::add_message( esc_html__( 'Gravity Forms imported 1 form successfully.', 'ninja-to-gravity' ) . ' ' . $edit_link );
			}
		}
	}
	GFExport::page_header();

	$ninja_forms = [];
	if ( function_exists( 'Ninja_Forms' ) ) {
		$ninja_forms = Ninja_Forms()->form()->get_forms();
	}
	?>
	<div class="gform-settings__content">
		<form method="post" enctype="multipart/form-data" class="gform_settings_form">
			<?php wp_nonce_field( 'gf_import_forms', 'gf_import_forms_nonce' ); ?>
			<div class="gform-settings-panel gform-settings-panel--full">
				<header class="gform-settings-panel__header"><legend class="gform-settings-panel__title"><?php esc_html_e( 'Import Ninja Forms', 'ninja-to-gravity' ); ?></legend></header>
				<div class="gform-settings-panel__content">
					<div class="gform-settings-description">
						<?php
						echo esc_html__( 'Select the Ninja Forms export files you would like to import. Please make sure your files have the .nff extension, and that they were generated by the Ninja Forms Export form tool. When you click the import button below, Gravity Forms will import the forms.', 'ninja-to-gravity' );
						?>
					</div>
					<table class="form-table">
						<tr valign="top">

							<th scope="row">
								<label for="gf_import_file"><?php esc_html_e( 'Select Files', 'ninja-to-gravity' ); ?></label> <?php gform_tooltip( 'import_select_file' ); ?>
							</th>
							<td><input type="file" name="gf_import_file[]" id="gf_import_file" multiple /></td>
						</tr>
						<?php if ( ! empty( $ninja_forms ) ) : ?>
							<tr valign="top">
								<th scope="row">
									<label for="gf_import_id"><?php esc_html_e( 'Select Form', 'ninja-to-gravity' ); ?></label> <?php gform_tooltip( 'import_select_file' ); ?>
								</th>
								<td>
									<select name="gf_import_id" id="gf_import_id">
										<option value="" selected disabled><?php echo esc_html__( 'Select a form', 'ninja-to-gravity' ); ?></option>
										<?php foreach ( $ninja_forms as $form ) : ?>
											<option value="<?php echo esc_attr( $form->get_id() ); ?>"><?php echo esc_html( $form->get_setting( 'title' ) ); ?></option>
										<?php endforeach; ?>
									</select> 
								</td>
							</tr>
						<?php endif; ?>

					</table>
					<br /><br />
					<input type="submit" value="<?php esc_attr_e( 'Import', 'ninja-to-gravity' ); ?>" name="import_forms" class="button large primary" />
				</div>
			</div>
		</form>
	</div>
	<?php

	GFExport::page_footer();

}

add_action( 'gform_export_page_import_ninja_form', 'add_ninja_form_option' );

