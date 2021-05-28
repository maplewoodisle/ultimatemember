<?php if ( ! defined( 'ABSPATH' ) ) exit;


function um_pre_template_locations_wpml( $template_locations, $template_name, $module, $template_path ) {
	$language_codes = um_wpml_get_languages_codes();

	if ( $language_codes['default'] != $language_codes['current'] ) {
		$lang = $language_codes['current'];

		$ml_template_locations = array_map( function( $item ) use ( $template_path, $lang ) {
			return str_replace( trailingslashit( $template_path ), trailingslashit( $template_path ) . $lang . '/', $item );
		}, $template_locations );

		$template_locations = array_merge( $ml_template_locations, $template_locations );
	}

	return $template_locations;
}
add_filter( 'um_pre_template_locations_common_locale_integration', 'um_pre_template_locations_wpml', 10, 4 );



/**
 * @return array
 */
function um_wpml_get_languages_codes() {
	global $sitepress;

	return [
		'default' => $sitepress->get_locale_from_language_code( $sitepress->get_default_language() ),
		'current' => $sitepress->get_locale_from_language_code( $sitepress->get_current_language() ),
	];
}