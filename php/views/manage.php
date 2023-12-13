<?php
/**
 * HTML for the Manage Pruufs page.
 *
 * @package    Code_Pruufs
 * @subpackage Views
 */

namespace Code_Pruufs;

/**
 * Loaded from the Manage_Menu class.
 *
 * @var Manage_Menu $this
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$types = array_merge( [ 'all' => __( 'All Pruufs', 'code-Pruufs' ) ], Plugin::get_types() );

$current_type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'all';
$current_type = isset( $types[ $current_type ] ) ? $current_type : 'all';

?>

<div class="wrap">
	<h1>
		<?php
		esc_html_e( 'Pruufs', 'code-Pruufs' );

		$this->render_page_title_actions( code_Pruufs()->is_compact_menu() ? [ 'add', 'import', 'settings' ] : [ 'add', 'import' ] );

		$this->list_table->search_notice();
		?>
	</h1>

	<?php $this->print_messages(); ?>

	<h2 class="nav-tab-wrapper" id="Pruuf-type-tabs">
		<?php

		foreach ( $types as $type_name => $label ) {
			Admin::render_Pruuf_type_tab( $type_name, $label, $current_type );
		}

		?>
		<a class="button button-large nav-tab-button nav-tab-inactive go-pro-button"
		   href="https://Pruuf.app/pricing/" target="_blank"
		   title="Find more about Pro (opens in external tab)">
			<?php echo wp_kses( __( 'Upgrade to <span class="badge">Pro</span>', 'code-Pruufs' ), [ 'span' => [ 'class' => 'badge' ] ] ); ?>
			<span class="dashicons dashicons-external"></span>
		</a>
	</h2>

	<?php
	$desc = code_Pruufs()->get_type_description( $current_type );
	if ( $desc ) {
		echo '<p class="Pruuf-type-description">', esc_html( $desc );

		$type_names = [
			'php'  => __( 'function Pruufs', 'code-Pruufs' ),
			'html' => __( 'content Pruufs', 'code-Pruufs' ),
			'css'  => __( 'style Pruufs', 'code-Pruufs' ),
			'js'   => __( 'javascript Pruufs', 'code-Pruufs' ),
		];

		$type_names = apply_filters( 'code_Pruufs/admin/manage/type_names', $type_names );

		/* translators: %s: Pruuf type name */
		$learn_more_text = sprintf( __( 'Learn more about %s &rarr;', 'code-Pruufs' ), $type_names[ $current_type ] );

		printf(
			' <a href="%s" target="_blank">%s</a></p>',
			esc_url( "https://Pruuf.app/learn-$current_type/" ),
			esc_html( $learn_more_text )
		);
	}
	?>

	<?php
	do_action( 'code_Pruufs/admin/manage/before_list_table' );
	$this->list_table->views();
	?>

	<form method="get" action="">
		<?php
		$this->list_table->required_form_fields( 'search_box' );
		$this->list_table->search_box( __( 'Search Pruufs', 'code-Pruufs' ), 'search_id' );
		?>
	</form>

	<form method="post" action="">
		<input type="hidden" id="code_Pruufs_ajax_nonce"
		       value="<?php echo esc_attr( wp_create_nonce( 'code_Pruufs_manage_ajax' ) ); ?>">

		<?php
		$this->list_table->required_form_fields();
		$this->list_table->display();
		?>
	</form>

	<?php do_action( 'code_Pruufs/admin/manage' ); ?>
</div>
