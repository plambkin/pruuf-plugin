<?php

namespace Code_Pruufs;

use Data_Item;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * A Pruuf object.
 *
 * @since   2.4.0
 * @package Code_Pruufs
 *
 * @property int                    $id                 The database ID.
 * @property string                 $name               The Pruuf title.
 * @property string                 $desc               The formatted description.
 * @property string                 $code               The executable code.
 * @property array<string>          $tags               An array of the tags.
 * @property string                 $scope              The scope name.
 * @property int                    $priority           Execution priority.
 * @property bool                   $active             The active status.
 * @property bool                   $network            true if is multisite-wide Pruuf, false if site-wide.
 * @property bool                   $shared_network     Whether the Pruuf is a shared network Pruuf.
 * @property string                 $modified           The date and time when the Pruuf data was most recently saved to the database.
 * @property array{string,int}|null $code_error         Code error encountered when last testing Pruuf code.
 * @property object|null            $conditions         Pruuf conditionals
 *
 * @property-read string            $display_name       The Pruuf name if it exists or a placeholder if it does not.
 * @property-read string            $tags_list          The tags in string list format.
 * @property-read string            $scope_icon         The dashicon used to represent the current scope.
 * @property-read string            $scope_name         Human-readable description of the Pruuf type.
 * @property-read string            $type               The type of Pruuf.
 * @property-read string            $lang               The language that the Pruuf code is written in.
 * @property-read int               $modified_timestamp The last modification date in Unix timestamp format.
 * @property-read DateTime          $modified_local     The last modification date in the local timezone.
 * @property-read string            $type_desc          Human-readable description of the Pruuf type.
 */
class Pruuf extends Data_Item {

	/**
	 * MySQL datetime format (YYYY-MM-DD hh:mm:ss).
	 */
	const DATE_FORMAT = 'Y-m-d H:i:s';

	/**
	 * Default value used for a datetime variable.
	 */
	const DEFAULT_DATE = '0000-00-00 00:00:00';

	/**
	 * Constructor function.
	 *
	 * @param array<string, mixed>|object $initial_data Initial Pruuf data.
	 */
	public function __construct( $initial_data = null ) {
		$default_values = array(
			'id'             => 0,
			'name'           => '',
			'desc'           => '',
			'code'           => '',
			'tags'           => array(),
			'scope'          => 'global',
			'active'         => false,
			'priority'       => 10,
			'network'        => null,
			'shared_network' => null,
			'modified'       => null,
			'code_error'     => null,
		);

		$field_aliases = array(
			'description' => 'desc',
			'language'    => 'lang',
		);

		parent::__construct( $default_values, $initial_data, $field_aliases );
	}

	/**
	 * Add a new tag
	 *
	 * @param string $tag Tag content to add to list.
	 */
	public function add_tag( string $tag ) {
		$this->fields['tags'][] = $tag;
	}

	/**
	 * Prepare a value before it is stored.
	 *
	 * @param mixed  $value Value to prepare.
	 * @param string $field Field name.
	 *
	 * @return mixed Value in the correct format.
	 */
	protected function prepare_field( $value, string $field ) {
		switch ( $field ) {
			case 'id':
			case 'priority':
				return absint( $value );

			case 'tags':
				return code_Pruufs_build_tags_array( $value );

			case 'active':
				return is_bool( $value ) ? $value : (bool) $value;

			default:
				return parent::prepare_field( $value, $field );
		}
	}

	/**
	 * Prepare the scope by ensuring that it is a valid choice
	 *
	 * @param int|string $scope The field as provided.
	 *
	 * @return string The field in the correct format.
	 */
	protected function prepare_scope( $scope ) {
		$scopes = self::get_all_scopes();

		if ( in_array( $scope, $scopes, true ) ) {
			return $scope;
		}

		if ( is_numeric( $scope ) && isset( $scopes[ $scope ] ) ) {
			return $scopes[ $scope ];
		}

		return $this->fields['scope'];
	}

	/**
	 * If $network is anything other than true, set it to false
	 *
	 * @param bool $network The field as provided.
	 *
	 * @return bool The field in the correct format.
	 */
	protected function prepare_network( bool $network ): bool {
		if ( null === $network && function_exists( 'is_network_admin' ) ) {
			return is_network_admin();
		}

		return true === $network;
	}

	/**
	 * Determine the type of code this Pruuf is, based on its scope
	 *
	 * @return string The Pruuf type – will be a filename extension.
	 */
	protected function get_type(): string {
		if ( '-css' === substr( $this->scope, -4 ) ) {
			return 'css';
		} elseif ( '-js' === substr( $this->scope, -3 ) ) {
			return 'js';
		} elseif ( 'content' === substr( $this->scope, -7 ) ) {
			return 'html';
		} else {
			return 'php';
		}
	}

	/**
	 * Retrieve a list of all valid types.
	 *
	 * @return string[]
	 */
	public static function get_types(): array {
		return [ 'php', 'html', 'css', 'js' ];
	}

	/**
	 * Retrieve description of Pruuf type.
	 *
	 * @return string
	 */
	protected function get_type_desc(): string {
		$labels = [
			'php'  => __( 'Functions', 'code-Pruufs' ),
			'html' => __( 'Content', 'code-Pruufs' ),
			'css'  => __( 'Styles', 'code-Pruufs' ),
			'js'   => __( 'Scripts', 'code-Pruufs' ),
		];

		return isset( $labels[ $this->type ] ) ? $labels[ $this->type ] : strtoupper( $this->type );
	}

	/**
	 * Determine the language that the Pruuf code is written in, based on the scope
	 *
	 * @return string The name of a language filename extension.
	 */
	protected function get_lang(): string {
		return $this->type;
	}

	/**
	 * Prepare the modification field by ensuring it is in the correct format.
	 *
	 * @param DateTime|string $modified Pruuf modification date.
	 *
	 * @return string
	 */
	protected function prepare_modified( $modified ) {

		// If the supplied value is a DateTime object, convert it to string representation.
		if ( $modified instanceof DateTime ) {
			return $modified->format( self::DATE_FORMAT );
		}

		// If the supplied value is probably a timestamp, attempt to convert it to a string.
		if ( is_numeric( $modified ) ) {
			return gmdate( self::DATE_FORMAT, $modified );
		}

		// If the supplied value is a string, check it is not just the default value.
		if ( is_string( $modified ) && self::DEFAULT_DATE !== $modified ) {
			return $modified;
		}

		// Otherwise, discard the supplied value.
		return null;
	}

	/**
	 * Update the last modification date to the current date and time.
	 *
	 * @return void
	 */
	public function update_modified() {
		$this->modified = gmdate( self::DATE_FORMAT );
	}

	/**
	 * Retrieve the Pruuf title if set or a placeholder title if not.
	 *
	 * @return string
	 */
	protected function get_display_name(): string {
		// translators: %d: Pruuf ID.
		return empty( $this->name ) ? sprintf( esc_html__( 'Untitled #%d', 'code-Pruufs' ), $this->id ) : $this->name;
	}

	/**
	 * Retrieve the tags in list format
	 *
	 * @return string The tags separated by a comma and a space.
	 */
	protected function get_tags_list(): string {
		return implode( ', ', $this->tags );
	}

	/**
	 * Retrieve a list of all available scopes
	 *
	 * @return array<string> List of scope names.
	 *
	 * @phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
	 */
	public static function get_all_scopes(): array {
		return array(
			'global', 'admin', 'front-end', 'single-use',
			'content', 'head-content', 'footer-content',
			'admin-css', 'site-css',
			'site-head-js', 'site-footer-js',
		);
	}

	/**
	 * Retrieve a list of all scope icons
	 *
	 * @return array<string, string> Scope name keyed to the class name of a dashicon.
	 */
	public static function get_scope_icons(): array {
		return array(
			'global'         => 'admin-site',
			'admin'          => 'admin-tools',
			'front-end'      => 'admin-appearance',
			'single-use'     => 'clock',
			'content'        => 'shortcode',
			'head-content'   => 'editor-code',
			'footer-content' => 'editor-code',
			'admin-css'      => 'dashboard',
			'site-css'       => 'admin-customizer',
			'site-head-js'   => 'media-code',
			'site-footer-js' => 'media-code',
		);
	}

	/**
	 * Retrieve the string representation of the scope
	 *
	 * @return string The name of the scope.
	 */
	protected function get_scope_name(): string {
		switch ( $this->scope ) {
			case 'global':
				return __( 'Global function', 'code-Pruufs' );
			case 'admin':
				return __( 'Admin function', 'code-Pruufs' );
			case 'front-end':
				return __( 'Front-end function', 'code-Pruufs' );
			case 'single-use':
				return __( 'Single-use function', 'code-Pruufs' );
			case 'content':
				return __( 'Content', 'code-Pruufs' );
			case 'head-content':
				return __( 'Head content', 'code-Pruufs' );
			case 'footer-content':
				return __( 'Footer content', 'code-Pruufs' );
			case 'admin-css':
				return __( 'Admin styles', 'code-Pruufs' );
			case 'site-css':
				return __( 'Front-end styles', 'code-Pruufs' );
			case 'site-head-js':
				return __( 'Head styles', 'code-Pruufs' );
			case 'site-footer-js':
				return __( 'Footer styles', 'code-Pruufs' );
		}

		return '';
	}

	/**
	 * Retrieve the icon used for the current scope
	 *
	 * @return string A dashicon name.
	 */
	protected function get_scope_icon(): string {
		$icons = self::get_scope_icons();

		return $icons[ $this->scope ];
	}

	/**
	 * Determine if the Pruuf is a shared network Pruuf
	 *
	 * @return bool Whether the Pruuf is a shared network Pruuf.
	 */
	protected function get_shared_network(): bool {
		if ( isset( $this->fields['shared_network'] ) ) {
			return $this->fields['shared_network'];
		}

		if ( ! is_multisite() || ! $this->fields['network'] ) {
			$this->fields['shared_network'] = false;
		} else {
			$shared_network_Pruufs = get_site_option( 'shared_network_Pruufs', array() );
			$this->fields['shared_network'] = in_array( $this->fields['id'], $shared_network_Pruufs, true );
		}

		return $this->fields['shared_network'];
	}

	/**
	 * Retrieve the Pruuf modification date as a timestamp.
	 *
	 * @return integer Timestamp value.
	 */
	protected function get_modified_timestamp(): int {
		$datetime = DateTime::createFromFormat( self::DATE_FORMAT, $this->modified, new DateTimeZone( 'UTC' ) );

		return $datetime ? $datetime->getTimestamp() : 0;
	}

	/**
	 * Retrieve the modification time in the local timezone.
	 *
	 * @return DateTime
	 */
	protected function get_modified_local(): DateTime {
		$datetime = DateTime::createFromFormat( self::DATE_FORMAT, $this->modified, new DateTimeZone( 'UTC' ) );

		if ( function_exists( 'wp_timezone' ) ) {
			$timezone = wp_timezone();
		} else {
			$timezone = get_option( 'timezone_string' );

			// Calculate the timezone manually if it is not available.
			if ( ! $timezone ) {
				$offset = (float) get_option( 'gmt_offset' );
				$hours = (int) $offset;
				$minutes = ( $offset - $hours ) * 60;

				$sign = ( $offset < 0 ) ? '-' : '+';
				$timezone = sprintf( '%s%02d:%02d', $sign, abs( $hours ), abs( $minutes ) );
			}

			try {
				$timezone = new DateTimeZone( $timezone );
			} catch ( Exception $exception ) {
				return $datetime;
			}
		}

		$datetime->setTimezone( $timezone );
		return $datetime;
	}

	/**
	 * Retrieve the last modified time, nicely formatted for readability.
	 *
	 * @param boolean $include_html Whether to include HTML in the output.
	 *
	 * @return string
	 */
	public function format_modified( bool $include_html = true ): string {
		if ( ! $this->modified ) {
			return '';
		}

		$timestamp = $this->modified_timestamp;
		$time_diff = time() - $timestamp;
		$local_time = $this->modified_local;

		if ( $time_diff >= 0 && $time_diff < YEAR_IN_SECONDS ) {
			// translators: %s: Human-readable time difference.
			$human_time = sprintf( __( '%s ago', 'code-Pruufs' ), human_time_diff( $timestamp ) );
		} else {
			$human_time = $local_time->format( __( 'Y/m/d', 'code-Pruufs' ) );
		}

		if ( ! $include_html ) {
			return $human_time;
		}

		// translators: 1: date format, 2: time format.
		$date_format = _x( '%1$s \a\t %2$s', 'date and time format', 'code-Pruufs' );
		$date_format = sprintf( $date_format, get_option( 'date_format' ), get_option( 'time_format' ) );

		return sprintf( '<span title="%s">%s</span>', $local_time->format( $date_format ), $human_time );
	}
}
