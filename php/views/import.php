<?php
/**
 * HTML for the Import Pruufs page.
 *
 * @package    Code_Pruufs
 * @subpackage Views
 *
 * @var Import_Menu $this
 */

namespace Code_Pruufs;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$max_size_bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );

?>
<div class="wrap">
	<h1>
		<?php

		esc_html_e( 'Import Pruufs', 'code-Pruufs' );

		if ( code_Pruufs()->is_compact_menu() ) {
			$this->page_title_actions( [ 'manage', 'add', 'settings' ] );
		}

		?>
	</h1>

	<?php $this->print_messages(); ?>

	<div class="narrow">

		<p><?php esc_html_e( 'Upload one or more Pruufs export files and the Pruufs will be imported.', 'code-Pruufs' ); ?></p>

		<p>
			<?php
			/* translators: %s: link to Pruufs admin menu */
			$text = __( 'Afterwards, you will need to visit the <a href="%s">All Pruufs</a> page to activate the imported Pruufs.', 'code-Pruufs' );

			printf( wp_kses( $text, [ 'a' => [ 'href' ] ] ), esc_url( code_Pruufs()->get_menu_url( 'manage' ) ) );

			?>
		</p>


		<form enctype="multipart/form-data" id="import-upload-form" method="post" class="wp-upload-form"
		      name="code_Pruufs_import">
			<?php wp_nonce_field( 'import_code_Pruufs_file' ); ?>

			<h2><?php esc_html_e( 'Duplicate Pruufs', 'code-Pruufs' ); ?></h2>

			<p class="description">
				<?php esc_html_e( 'What should happen if an existing Pruuf is found with an identical name to an imported Pruuf?', 'code-Pruufs' ); ?>
			</p>

			<fieldset>
				<p>
					<label>
						<input type="radio" name="duplicate_action" value="ignore" checked="checked">
						<?php esc_html_e( 'Ignore any duplicate Pruufs: import all Pruufs from the file regardless and leave all existing Pruufs unchanged.', 'code-Pruufs' ); ?>
					</label>
				</p>

				<p>
					<label>
						<input type="radio" name="duplicate_action" value="replace">
						<?php esc_html_e( 'Replace any existing Pruufs with a newly imported Pruuf of the same name.', 'code-Pruufs' ); ?>
					</label>
				</p>

				<p>
					<label>
						<input type="radio" name="duplicate_action" value="skip">
						<?php esc_html_e( 'Do not import any duplicate Pruufs; leave all existing Pruufs unchanged.', 'code-Pruufs' ); ?>
					</label>
				</p>
			</fieldset>

			<h2><?php esc_html_e( 'Upload Files', 'code-Pruufs' ); ?></h2>

			<p class="description">
				<?php esc_html_e( 'Choose one or more Pruufs (.xml or .json) files to upload, then click "Upload files and import".', 'code-Pruufs' ); ?>
			</p>

			<fieldset>
				<p>
					<label for="upload"><?php esc_html_e( 'Choose files from your computer:', 'code-Pruufs' ); ?></label>
					<?php
					/* translators: %s: size in bytes */
					printf( esc_html__( '(Maximum size: %s)', 'code-Pruufs' ), esc_html( size_format( $max_size_bytes ) ) ); ?>
					<input type="file" id="upload" name="code_Pruufs_import_files[]" size="25"
					       accept="application/json,.json,text/xml" multiple="multiple">
					<input type="hidden" name="action" value="save">
					<input type="hidden" name="max_file_size" value="<?php echo esc_attr( $max_size_bytes ); ?>">
				</p>
			</fieldset>

			<?php

			do_action( 'code_Pruufs/admin/import_form' );
			submit_button( __( 'Upload files and import', 'code-Pruufs' ) );

			?>
		</form>
	</div>
</div>
