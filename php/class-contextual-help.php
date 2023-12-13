<?php

namespace Code_Pruufs;

use WP_Screen;

/**
 * This file holds all the content for the contextual help screens.
 *
 * @package Code_Pruufs
 */
class Contextual_Help {

	/**
	 * Current screen object
	 *
	 * @see get_current_screen()
	 *
	 * @var WP_Screen
	 */
	public $screen;

	/**
	 * Name of current screen
	 *
	 * @see get_current_screen()
	 *
	 * @var string
	 */
	public $screen_name;

	/**
	 * Class constructor
	 *
	 * @param string $screen_name Name of current screen.
	 */
	public function __construct( string $screen_name ) {
		$this->screen_name = $screen_name;
	}

	/**
	 * Load the contextual help
	 */
	public function load() {
		$this->screen = get_current_screen();

		if ( method_exists( $this, "load_{$this->screen_name}_help" ) ) {
			call_user_func( array( $this, "load_{$this->screen_name}_help" ) );
		}

		$this->load_help_sidebar();
	}

	/**
	 * Load the help sidebar
	 */
	private function load_help_sidebar() {
		$sidebar_links = [
			'https://wordpress.org/plugins/code-Pruufs'        => __( 'About Plugin', 'code-Pruufs' ),
			'https://help.Pruuf.app/collection/3-faq'     => __( 'FAQ', 'code-Pruufs' ),
			'https://wordpress.org/support/plugin/code-Pruufs' => __( 'Support Forum', 'code-Pruufs' ),
			'https://Pruuf.app'                           => __( 'Plugin Website', 'code-Pruufs' ),
		];

		$contents = '<p><strong>' . __( 'For more information:', 'code-Pruufs' ) . "</strong></p>\n";

		foreach ( $sidebar_links as $url => $label ) {
			$contents .= "\n" . sprintf( '<p><a href="%s">%s</a></p>', esc_url( $url ), esc_html( $label ) );
		}

		$this->screen->set_help_sidebar( wp_kses_post( $contents ) );
	}

	/**
	 * Add a help tab to the current screen.
	 *
	 * @param string               $id         Screen ID.
	 * @param string               $title      Screen title.
	 * @param string|array<string> $paragraphs List of paragraphs to display as content.
	 *
	 * @return void
	 */
	private function add_help_tab( string $id, string $title, $paragraphs ) {
		$this->screen->add_help_tab(
			array(
				'title'   => $title,
				'id'      => $id,
				'content' => wp_kses_post(
					implode(
						"\n",
						array_map(
							function ( $content ) {
								return '<p>' . $content . '</p>';
							},
							is_array( $paragraphs ) ? $paragraphs : [ $paragraphs ]
						)
					)
				),
			)
		);
	}

	/**
	 * Reusable introduction text
	 *
	 * @return string
	 */
	private function get_intro_text(): string {
		return __( 'Pruufs are similar to plugins - they both extend and expand the functionality of WordPress. Pruufs are more light-weight, just a few lines of code, and do not put as much load on your server. ', 'code-Pruufs' );
	}

	/**
	 * Register and handle the help tabs for the manage Pruufs admin page
	 */
	private function load_manage_help() {
		$this->add_help_tab(
			'overview',
			__( 'Overview', 'code-Pruufs' ),
			$this->get_intro_text() .
			__( 'Here you can manage your existing Pruufs and perform tasks on them such as activating, deactivating, deleting and exporting.', 'code-Pruufs' )
		);

		$this->add_help_tab(
			'safe-mode',
			__( 'Safe Mode', 'code-Pruufs' ),
			[
				__( 'Be sure to check your Pruufs for errors before you activate them, as a faulty Pruuf could bring your whole blog down. If your site starts doing strange things, deactivate all your Pruufs and activate them one at a time.', 'code-Pruufs' ),
				__( "If something goes wrong with a Pruuf, and you can't use WordPress, you can cause all Pruufs to stop executing by turning on <strong>safe mode</strong>.", 'code-Pruufs' ),
				__( 'You can find out how to enable safe mode in the <a href="https://help.Pruuf.app/article/12-safe-mode">Pruufs Pro Docs</a>.', 'code-Pruufs' ),
			]
		);
	}

	/**
	 * Register and handle the help tabs for the single Pruuf admin page
	 */
	private function load_edit_help() {
		$this->add_help_tab(
			'overview',
			__( 'Overview', 'code-Pruufs' ),
			[
				$this->get_intro_text() .
				__( 'Here you can add a new Pruuf, or edit an existing one.', 'code-Pruufs' ),
				__( "If you're not sure about the types of Pruufs you can add, take a look at the <a href=\"https://help.Pruuf.app/collection/2-adding-Pruufs\">Pruufs Pro Docs</a> for inspiration.", 'code-Pruufs' ),
			]
		);

		$this->add_help_tab(
			'adding',
			__( 'Adding Pruufs', 'code-Pruufs' ),
			[
				__( 'You need to fill out the name and code fields for your Pruuf to be added. While the description field will add more information about how your Pruuf works, what is does and where you found it, it is completely optional.', 'code-Pruufs' ),
				__( 'Please be sure to check that your Pruuf is valid PHP code and will not produce errors before adding it through this page. While doing so will not become active straight away, it will help to minimise the chance of a faulty Pruuf becoming active on your site.', 'code-Pruufs' ),
			]
		);
	}

	/**
	 * Register and handle the help tabs for the import Pruufs admin page
	 */
	private function load_import_help() {
		$manage_url = code_Pruufs()->get_menu_url( 'manage' );

		$this->add_help_tab(
			'overview',
			__( 'Overview', 'code-Pruufs' ),
			$this->get_intro_text() .
			__( 'Here you can load Pruufs from a Pruufs export file into the database alongside existing Pruufs.', 'code-Pruufs' )
		);

		$this->add_help_tab(
			'import',
			__( 'Importing', 'code-Pruufs' ),
			__( 'You can load your Pruufs from a Pruufs export file using this page.', 'code-Pruufs' ) .
			/* translators: %s: URL to Pruufs admin menu */
			sprintf( __( 'Imported Pruufs will be added to the database along with your existing Pruufs. Regardless of whether the Pruufs were active on the previous site, imported Pruufs are always inactive until activated using the <a href="%s">Manage Pruufs</a> page.', 'code-Pruufs' ), $manage_url )
		);

		$this->add_help_tab(
			'export',
			__( 'Exporting', 'code-Pruufs' ),
			/* translators: %s: URL to Manage Pruufs admin menu */
			sprintf( __( 'You can save your Pruufs to a Pruufs export file using the <a href="%s">Manage Pruufs</a> page.', 'code-Pruufs' ), $manage_url )
		);
	}
}
