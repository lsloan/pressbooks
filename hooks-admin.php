<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

use Pressbooks\Book;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -------------------------------------------------------------------------------------------------------------------
// Includes
// -------------------------------------------------------------------------------------------------------------------

require( PB_PLUGIN_DIR . 'inc/admin/analytics/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/admin/customcss/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/admin/dashboard/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/admin/diagnostics/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/admin/fonts/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/admin/laf/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/admin/metaboxes/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/admin/networkmanagers/namespace.php' );
require( PB_PLUGIN_DIR . 'inc/admin/plugins/namespace.php' );

// -------------------------------------------------------------------------------------------------------------------
// Recycle, reduce, reuse
// -------------------------------------------------------------------------------------------------------------------

$is_book = Book::isBook();

// -------------------------------------------------------------------------------------------------------------------
// Look & feel of admin interface and Dashboard
// -------------------------------------------------------------------------------------------------------------------

// PressBook-ify the admin bar
add_action( 'admin_bar_menu', '\Pressbooks\Admin\Laf\replace_menu_bar_branding', 11 );
add_action( 'admin_bar_menu', '\Pressbooks\Admin\Laf\replace_menu_bar_my_sites', 21 );
add_action( 'admin_bar_menu', '\Pressbooks\Admin\Laf\remove_menu_bar_update', 41 );
add_action( 'admin_bar_menu', '\Pressbooks\Admin\Laf\remove_menu_bar_new_content', 71 );

// Add contact Info
add_filter( 'admin_footer_text', '\Pressbooks\Admin\Laf\add_footer_link' );

// Dashboard settings
add_action( 'admin_init', '\Pressbooks\Admin\Dashboard\dashboard_options_init' );
add_action( 'network_admin_menu', '\Pressbooks\Admin\Dashboard\add_menu', 2 );
add_action( 'admin_menu', '\Pressbooks\Admin\Dashboard\add_menu', 1 );
add_action( 'admin_menu', '\Pressbooks\Admin\Diagnostics\add_menu', 30 );

if ( $is_book ) {
	// Aggressively replace default interface
	add_action( 'init', [ '\Pressbooks\Modules\SearchAndReplace\SearchAndReplace', 'init' ] );
	add_action( 'init', [ '\Pressbooks\Modules\ThemeOptions\ThemeOptions', 'init' ] );
	add_action( 'admin_init', '\Pressbooks\Redirect\redirect_away_from_bad_urls' );
	add_action( 'admin_menu', '\Pressbooks\Admin\Laf\replace_book_admin_menu', 1 );
	add_action( 'admin_menu', [ '\Pressbooks\Admin\Delete\Book', 'init' ] );
	add_filter( 'parent_file', '\Pressbooks\Admin\Laf\fix_parent_file' );
	add_filter( 'submenu_file', '\Pressbooks\Admin\Laf\fix_submenu_file', 10, 2 );
	add_action( 'wp_dashboard_setup', '\Pressbooks\Admin\Dashboard\replace_dashboard_widgets' );
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
	add_action( 'customize_register', '\Pressbooks\Admin\Laf\customize_register', 1000 );
	add_filter( 'all_plugins', '\Pressbooks\Admin\Plugins\filter_plugins', 10 );
	// Disable theme customizer
	add_action( 'admin_body_class', '\Pressbooks\Admin\Laf\disable_customizer' );

} else {
	// Fix extraneous menus
	add_action( 'admin_menu', '\Pressbooks\Admin\Laf\fix_root_admin_menu', 1 );
}

if ( is_network_admin() ) {
	add_action( 'wp_network_dashboard_setup', '\Pressbooks\Admin\Dashboard\replace_network_dashboard_widgets' );
}

if ( true === is_main_site() ) {
	add_action( 'wp_dashboard_setup', '\Pressbooks\Admin\Dashboard\replace_root_dashboard_widgets' );
}

// Javascript, Css
add_action( 'admin_init', '\Pressbooks\Admin\Laf\init_css_js' );

// Hacks
add_action( 'edit_form_advanced', '\Pressbooks\Admin\Laf\edit_form_hacks' );

// Google Analytics
add_action( 'network_admin_menu', '\Pressbooks\Admin\Analytics\add_network_menu' );
add_action( 'admin_init', '\Pressbooks\Admin\Analytics\network_analytics_settings_init' );
if ( $is_book ) {
	switch_to_blog( 1 );
	$ga_mu_site_specific_allowed = get_option( 'ga_mu_site_specific_allowed', '' );
	restore_current_blog();
	if ( isset( $ga_mu_site_specific_allowed ) && '' !== $ga_mu_site_specific_allowed && '0' !== $ga_mu_site_specific_allowed ) {
		add_action( 'admin_menu', '\Pressbooks\Admin\Analytics\add_menu' );
		add_action( 'admin_init', '\Pressbooks\Admin\Analytics\analytics_settings_init' );
	}
}
add_action( 'admin_head', '\Pressbooks\Admin\Analytics\print_admin_analytics' );

// Privacy settings
add_action( 'network_admin_menu', '\Pressbooks\Admin\Laf\network_admin_menu' );
if ( ! is_network_admin() ) {
	add_action( 'admin_init', '\Pressbooks\Admin\Laf\privacy_settings_init' );
}

//  Replaces 'WordPress' with 'Pressbooks' in titles of admin pages.
add_filter( 'admin_title', '\Pressbooks\Admin\Laf\admin_title' );

// Echo our notices, if any
add_action( 'admin_notices', '\Pressbooks\Admin\Laf\admin_notices' );

// Network Manager routines
add_filter( 'admin_body_class', '\Pressbooks\Admin\NetworkManagers\admin_body_class' );
add_action( 'network_admin_menu', '\Pressbooks\Admin\NetworkManagers\add_menu', 1 );
add_action( 'wp_ajax_pb_update_admin_status', '\Pressbooks\Admin\NetworkManagers\update_admin_status' );
add_action( 'admin_init', '\Pressbooks\Admin\NetworkManagers\restrict_access' );
add_action( 'admin_menu', '\Pressbooks\Admin\NetworkManagers\hide_menus' );
add_action( 'admin_bar_menu', '\Pressbooks\Admin\NetworkManagers\hide_admin_bar_menus', 999 );
if ( ! $is_book ) {
	add_action( 'network_admin_menu', '\Pressbooks\Admin\NetworkManagers\hide_network_menus' );
}

// -------------------------------------------------------------------------------------------------------------------
// Posts, Meta Boxes
// -------------------------------------------------------------------------------------------------------------------

add_action('init', function() { // replace default title filtering with our custom one that allows certain tags
	remove_filter( 'title_save_pre', 'wp_filter_kses' );
	add_filter( 'title_save_pre', 'Pressbooks\Sanitize\filter_title' );
});

add_action( 'admin_menu', function () {
	remove_meta_box( 'pageparentdiv', 'chapter', 'normal' );
	remove_meta_box( 'submitdiv', 'metadata', 'normal' );
	remove_meta_box( 'submitdiv', 'author', 'normal' );
	remove_meta_box( 'submitdiv', 'part', 'normal' );
} );

add_action( 'custom_metadata_manager_init_metadata', '\Pressbooks\Admin\Metaboxes\add_meta_boxes' );

if ( $is_book ) {
	add_action( 'admin_enqueue_scripts', '\Pressbooks\Admin\Metaboxes\add_metadata_styles' );
	add_action( 'save_post', '\Pressbooks\Book::consolidatePost', 10, 2 );
	add_action( 'save_post_metadata', '\Pressbooks\Admin\Metaboxes\upload_cover_image', 10, 2 );
	add_action( 'save_post_metadata', '\Pressbooks\Admin\Metaboxes\add_required_data', 20, 2 );
	add_action( 'added_post_meta', '\Pressbooks\Admin\Metaboxes\title_update', 10, 4 );
	add_action( 'updated_post_meta', '\Pressbooks\Admin\Metaboxes\title_update', 10, 4 );
	add_action( 'updated_post_meta', '\Pressbooks\L10n\install_book_locale', 10, 4 );
	add_action( 'save_post', '\Pressbooks\Book::deleteBookObjectCache', 1000 );
	add_action( 'wp_trash_post', '\Pressbooks\Book::deletePost' );
	add_action( 'wp_trash_post', '\Pressbooks\Book::deleteBookObjectCache', 1000 );
	add_filter( 'mce_external_languages', '\Pressbooks\Editor\add_languages' );
	add_filter( 'tiny_mce_before_init', '\Pressbooks\Editor\mce_before_init_insert_formats' );
	add_filter( 'tiny_mce_before_init', '\Pressbooks\Editor\mce_valid_word_elements' );
	add_filter( 'tiny_mce_before_init', '\Pressbooks\Editor\mce_table_editor_options' );
	add_filter( 'mce_external_plugins', '\Pressbooks\Editor\mce_button_scripts' );
	add_filter( 'mce_buttons_2', '\Pressbooks\Editor\mce_buttons_2' );
	add_filter( 'mce_buttons_3', '\Pressbooks\Editor\mce_buttons_3', 11 );
	add_filter( 'wp_link_query_args', '\Pressbooks\Editor\customize_wp_link_query_args' );
	add_filter( 'wp_link_query', '\Pressbooks\Editor\add_anchors_to_wp_link_query', 1, 2 );
	add_action( 'edit_form_after_title', '\Pressbooks\Metadata\add_expanded_metadata_box' );
}

// -------------------------------------------------------------------------------------------------------------------
// Ajax
// -------------------------------------------------------------------------------------------------------------------

// Book Organize Page
add_action( 'wp_ajax_pb_update_chapter', '\Pressbooks\Book::updateChapter' );
add_action( 'wp_ajax_pb_update_front_matter', '\Pressbooks\Book::updateFrontMatter' );
add_action( 'wp_ajax_pb_update_back_matter', '\Pressbooks\Book::updateBackMatter' );
add_action( 'wp_ajax_pb_update_export_options', '\Pressbooks\Book::updateExportOptions' );
add_action( 'wp_ajax_pb_update_word_count_for_export', '\Pressbooks\Book::ajaxWordCount' );
add_action( 'wp_ajax_pb_update_privacy_options', '\Pressbooks\Book::updatePrivacyOptions' );
add_action( 'wp_ajax_pb_update_show_title_options', '\Pressbooks\Book::updateShowTitleOptions' );
add_action( 'wp_ajax_pb_update_global_privacy_options', '\Pressbooks\Book::updateGlobalPrivacyOptions' );
// Book Information Page
add_action( 'wp_ajax_pb_delete_cover_image', '\Pressbooks\Admin\Metaboxes\delete_cover_image' );
// Convert MS Word Footnotes
add_action( 'wp_ajax_pb_ftnref_convert', '\Pressbooks\Shortcodes\Footnotes\Footnotes::convertWordFootnotes' );
// Load CSS into Custom CSS textarea
add_action( 'wp_ajax_pb_load_css_from', '\Pressbooks\Admin\CustomCss\load_css_from' );
// User Catalog Page
add_action( 'wp_ajax_pb_delete_catalog_logo', '\Pressbooks\Catalog::deleteLogo' );

// -------------------------------------------------------------------------------------------------------------------
// Custom Css
// -------------------------------------------------------------------------------------------------------------------

add_action( 'admin_menu', '\Pressbooks\Admin\CustomCss\add_menu' );
add_action( 'load-post.php', '\Pressbooks\Admin\CustomCss\redirect_css_editor' );

// -------------------------------------------------------------------------------------------------------------------
// SASS
// -------------------------------------------------------------------------------------------------------------------

add_action( 'update_option_pressbooks_global_typography', '\Pressbooks\Admin\Fonts\update_font_stacks' );
add_action( 'update_option_pressbooks_theme_options_web', '\Pressbooks\Admin\Fonts\update_font_stacks' );

if ( $is_book ) {

	// Look & Feel
	add_action( 'after_switch_theme', '\Pressbooks\Admin\Fonts\update_font_stacks' );

	// Posts, Meta Boxes
	add_action( 'updated_postmeta', function ( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( 'pb_language' === $meta_key ) {
			\Pressbooks\Book::deleteBookObjectCache();
			\Pressbooks\Admin\Fonts\update_font_stacks();
		}
	}, 10, 4 );

	// Init
	add_action( 'admin_init', '\Pressbooks\Admin\Fonts\fix_missing_font_stacks' );
	add_action( 'admin_init', '\Pressbooks\Editor\add_editor_style' );

	// Overrides
	add_filter( 'pb_epub_css_override', [ '\Pressbooks\Modules\ThemeOptions\EbookOptions', 'scssOverrides' ] );
	add_filter( 'pb_pdf_css_override', [ '\Pressbooks\Modules\ThemeOptions\PDFOptions', 'scssOverrides' ] );
	add_filter( 'pb_web_css_override', [ '\Pressbooks\Modules\ThemeOptions\WebOptions', 'scssOverrides' ] );
}

// Theme Lock
add_action( 'admin_init', '\Pressbooks\Theme\Lock::restrictThemeManagement' );
add_action( 'update_option_pressbooks_export_options', '\Pressbooks\Theme\Lock::toggleThemeLock', 10, 3 );

// -------------------------------------------------------------------------------------------------------------------
// "Catch-all" routines, must come after taxonomies and friends
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', '\Pressbooks\Modules\Export\Export::formSubmit', 50 );
add_action( 'init', '\Pressbooks\Modules\Import\Import::formSubmit', 50 );
add_action( 'init', '\Pressbooks\CustomCss::formSubmit', 50 );
add_action( 'init', '\Pressbooks\Catalog::formSubmit', 50 );
add_action( 'init', '\Pressbooks\Cloner::formSubmit', 50 );

// -------------------------------------------------------------------------------------------------------------------
// Leftovers
// -------------------------------------------------------------------------------------------------------------------

if ( $is_book ) {

	add_action( 'post_edit_form_tag', function () {
		echo ' enctype="multipart/form-data"';
	} );

	// Disable all pointers (i.e. tooltips) all the time, see \WP_Internal_Pointers()
	add_action( 'admin_init', function () {
		remove_action( 'admin_enqueue_scripts', [ 'WP_Internal_Pointers', 'enqueue_scripts' ] );
	} );

	// Fix for "are you sure you want to leave page" message when editing a part
	add_action( 'admin_enqueue_scripts', function () {
		if ( 'part' === get_post_type() ) {
			wp_dequeue_script( 'autosave' );
		}
	} );

	// Hide welcome screen
	add_action( 'load-index.php', function () {
		$user_id = get_current_user_id();
		if ( get_user_meta( $user_id, 'show_welcome_panel', true ) ) {
			update_user_meta( $user_id, 'show_welcome_panel', 0 );
		}
	} );

	// Disable live preview
	add_filter( 'theme_action_links', function ( $actions ) {
		unset( $actions['preview'] );
		return $actions;
	} );

}

// Hide WP update nag
add_action( 'admin_menu', function () {
	remove_action( 'admin_notices', 'update_nag', 3 );
	remove_filter( 'update_footer', 'core_update_footer' );
} );

// Plugin Recommendations
add_filter( 'install_plugins_tabs', '\Pressbooks\Utility\install_plugins_tabs' );
add_filter( 'plugins_api', '\Pressbooks\Utility\hijack_recommended_tab', 10, 3 );
add_filter( 'gettext', '\Pressbooks\Utility\change_recommendations_sentence', 10, 3 );

// Theme check
add_action( 'admin_init', '\Pressbooks\Theme\check_required_themes' );
