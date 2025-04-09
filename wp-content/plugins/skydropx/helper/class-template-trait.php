<?php
/**
 * Template Trait
 *
 * @package  Skydropx\Helper
 */
namespace Skydropx\Helper;

trait Template_trait {

	/**
	 * Get template part
	 *
	 * @param string $slug slug for template part.
	 * @param string $name name for template part.
	 * @param array  $args Values to be passed to template.
	 * @return void
	 */
	public static function get_template_part( $slug, $name = null, $args = array() ) {
		do_action( "skydropx_get_template_part_{$slug}", $slug, $name );
		$templates = array();
		if ( isset( $name ) ) {
			$templates[] = "{$slug}-{$name}.php";
		}

		$templates[] = "{$slug}.php";
		self::get_template_path( $templates, true, false, $args );
	}

	/**
	 * Gets a plugin option
	 *
	 * @param array $template_names templates names.
	 * @param bool  $load load value.
	 * @param bool  $require_once require_once value.
	 * @param array $args Values to be passed to template.
	 * @return string
	 */
	public static function get_template_path( $template_names, $load = false, $require_once = true, $args = array() ) {
		$located = '';
		foreach ( (array) $template_names as $template_name ) {
			if ( ! $template_name ) {
				continue;
			}
			/* search file within the PLUGIN_DIR_PATH only */
			if ( file_exists( untrailingslashit( dirname( \Skydropx\Woo::MAIN_FILE ) ) . '/templates/' . $template_name ) ) {
				$located = untrailingslashit( dirname( \Skydropx\Woo::MAIN_FILE ) ) . '/templates/' . $template_name;
				break;
			}
		}

		if ( $load && '' !== $located ) {
			load_template( $located, $require_once, $args );
		}
		return $located;
	}

}