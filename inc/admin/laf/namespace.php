<?php
/**
 * Administration interface look and feel.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Admin\Laf;

use PressbooksMix\Assets;

/**
 * Add a custom message in admin footer
 */
function add_footer_link() {

	printf(
		'<p id="footer-left" class="alignleft"><span id="footer-thankyou">%s <a href="http://pressbooks.com">Pressbooks</a></span> &bull; <a href="http://pressbooks.com/about">%s</a> &bull; <a href="http://pressbooks.com/help">%s</a> &bull; <a href="%s">%s</a> &bull; <a href="http://pressbooks.com/contact">%s</a></p>',
		__( 'Powered by', 'pressbooks' ),
		__( 'About', 'pressbooks' ),
		__( 'Help', 'pressbooks' ),
		admin_url( 'options.php?page=pressbooks_diagnostics' ),
		__( 'Diagnostics', 'pressbooks' ),
		__( 'Contact', 'pressbooks' )
	);

	if ( current_user_can( 'edit_posts' ) ) {
		// Embed the blog_id in the admin interface so we can debug things more easily
		global $blog_id;
		echo "<!-- blog_id: $blog_id -->";
	}
}

/**
 * Replaces 'WordPress' with 'Pressbooks' in titles of admin pages.
 */
function admin_title( $admin_title ) {
	$title = str_replace( 'WordPress', 'Pressbooks', $admin_title );
	return $title;
}

/**
 * Removes some default WordPress Admin Sidebar items and adds our own
 */
function replace_book_admin_menu() {

	global $menu, $submenu;

	// Modify $menu and $submenu global arrays to do some tasks, such as adding a new separator, moving items from one menu into another, and reordering sub-menu items.

	if ( isset( $menu[60] ) ) {
		$menu[13] = $menu[60]; // Relocate Appearance
		unset( $menu[60] );
	}

	if ( isset( $menu[10] ) ) {
		$menu[68] = $menu[10]; // Relocate Media
		unset( $menu[10] );
	}

	if ( isset( $menu[25] ) ) {
		$menu[69] = $menu[25]; // Relocate Comments
		unset( $menu[25] );
	}

	// Remove items we don't want the user to see.
	remove_submenu_page( 'index.php', 'my-sites.php' );
	remove_submenu_page( 'options-general.php', 'options-general.php' );
	remove_submenu_page( 'options-general.php', 'options-writing.php' );
	remove_submenu_page( 'options-general.php', 'options-reading.php' );
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	remove_submenu_page( 'options-general.php', 'options-media.php' );
	remove_submenu_page( 'options-general.php', 'options-permalink.php' );

	remove_menu_page( 'edit.php?post_type=part' );
	remove_menu_page( 'edit.php' );
	remove_menu_page( 'edit.php?post_type=front-matter' );
	remove_menu_page( 'edit.php?post_type=back-matter' );
	remove_menu_page( 'edit.php?post_type=metadata' );
	remove_menu_page( 'link-manager.php' );
	remove_menu_page( 'edit.php?post_type=page' );
	add_theme_page(
		__( 'Theme Options', 'pressbooks' ), __( 'Theme Options', 'pressbooks' ), 'edit_others_posts', 'pressbooks_theme_options', [
		'\Pressbooks\Modules\ThemeOptions\ThemeOptions',
		'render',
		]
	);

	remove_submenu_page( 'tools.php', 'tools.php' );
	remove_submenu_page( 'tools.php', 'import.php' );
	remove_submenu_page( 'tools.php', 'export.php' );

	remove_submenu_page( 'edit.php?post_type=chapter', 'edit.php?post_type=chapter' );

	// Organize
	$organize_page = add_submenu_page( 'edit.php?post_type=chapter', __( 'Organize', 'pressbooks' ), __( 'Organize', 'pressbooks' ), 'edit_posts', 'pressbooks', __NAMESPACE__ . '\display_organize' );
	add_action(
		'admin_enqueue_scripts', function ( $hook ) use ( $organize_page ) {
			if ( $hook === $organize_page ) {
				wp_enqueue_style( 'pb-organize' );
				wp_enqueue_script( 'jquery-blockui' );
				wp_enqueue_script( 'pb-organize' );
				wp_localize_script(
					'pb-organize', 'PB_OrganizeToken', [
					// Ajax nonces
					'orderNonce' => wp_create_nonce( 'pb-update-book-order' ),
					'exportNonce' => wp_create_nonce( 'pb-update-book-export' ),
					'wordCountNonce' => wp_create_nonce( 'pb-update-word-count-for-export' ),
					'showTitleNonce' => wp_create_nonce( 'pb-update-book-show-title' ),
					'privacyNonce' => wp_create_nonce( 'pb-update-book-privacy' ),
					'private' => __( 'Private', 'pressbooks' ),
					'published' => __( 'Published', 'pressbooks' ),
					'public' => __( 'Public', 'pressbooks' ),
					]
				);
			}
		}
	);
	if ( current_user_can( 'edit_posts' ) ) {
		$add_chapter = $submenu['edit.php?post_type=chapter'][10];
		unset( $submenu['edit.php?post_type=chapter'][10] );
		$add_part = $submenu['edit.php?post_type=part'][10];
		$add_front_matter = $submenu['edit.php?post_type=front-matter'][10];
		$add_back_matter = $submenu['edit.php?post_type=back-matter'][10];
		array_push( $submenu['edit.php?post_type=chapter'], $add_part, $add_chapter, $add_front_matter, $add_back_matter );
	} else {
		unset( $submenu['edit.php?post_type=chapter'][10] );
	}
	if ( is_super_admin() ) {
		// If network administrator, give the option to see chapter, front matter and back matter types.
		$front_matter_types = $submenu['edit.php?post_type=front-matter'][15];
		$back_matter_types = $submenu['edit.php?post_type=back-matter'][15];
		if ( isset( $submenu['edit.php?post_type=chapter'][15] ) ) {
			$chapter_types = $submenu['edit.php?post_type=chapter'][15];
			unset( $submenu['edit.php?post_type=chapter'][15] );
			array_push(
				$submenu['edit.php?post_type=chapter'],
				$chapter_types,
				$front_matter_types,
				$back_matter_types
			);
		} else {
			array_push(
				$submenu['edit.php?post_type=chapter'],
				$front_matter_types,
				$back_matter_types
			);
		}
	}
	add_submenu_page( 'edit.php?post_type=chapter', __( 'Trash' ), __( 'Trash' ), 'delete_posts', 'trash', __NAMESPACE__ . '\display_trash' );

	// Book Information
	$metadata = new \Pressbooks\Metadata();
	$meta = $metadata->getMetaPost();
	if ( ! empty( $meta ) ) {
		$book_info_url = 'post.php?post=' . absint( $meta->ID ) . '&action=edit';
	} else {
		$book_info_url = 'post-new.php?post_type=metadata';
	}
	$bookinfo_page = add_menu_page( __( 'Book Info', 'pressbooks' ), __( 'Book Info', 'pressbooks' ), 'edit_posts', $book_info_url, '', 'dashicons-info', 12 );
	add_action(
		'admin_enqueue_scripts', function ( $hook ) use ( $bookinfo_page ) {
			if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
				if ( 'metadata' === get_post_type() ) {
					wp_enqueue_script( 'pb-metadata' );
					wp_localize_script(
						'pb-metadata', 'PB_BookInfoToken', [
						'bookInfoMenuId' => preg_replace( '|[^a-zA-Z0-9_:.]|', '-', $bookinfo_page ),
						]
					);
				}
			}
		}
	);

	// Export
	$export_page = add_menu_page( __( 'Export', 'pressbooks' ), __( 'Export', 'pressbooks' ), 'edit_posts', 'pb_export', __NAMESPACE__ . '\display_export', 'dashicons-migrate', 14 );
	add_action(
		'admin_enqueue_scripts', function ( $hook ) use ( $export_page ) {
			if ( $hook === $export_page ) {
				wp_enqueue_style( 'pb-export' );
				wp_enqueue_script( 'pb-export' );
				wp_localize_script(
					'pb-export', 'PB_ExportToken', [
					'mobiConfirm' => __( 'EPUB is required for MOBI export. Would you like to reenable it?', 'pressbooks' ),
					]
				);
			}
		}
	);

	// Publish
	$option = get_option( 'pressbooks_ecommerce_links', \Pressbooks\Admin\PublishOptions::getDefaults() );
	$page = new \Pressbooks\Admin\PublishOptions( $option );
	$page->init();
	wp_cache_delete( 'pressbooks_ecommerce_links_version', 'options' );
	$version = get_option( 'pressbooks_ecommerce_links_version', 0 );
	if ( $version < $page::VERSION ) {
		$page->upgrade( $version );
		update_option( 'pressbooks_ecommerce_links_version', $page::VERSION, false );
	}

	add_menu_page( __( 'Publish', 'pressbooks' ), __( 'Publish', 'pressbooks' ), 'edit_posts', 'pb_publish', [ $page, 'render' ], 'dashicons-products', 16 );

	// Privacy
	add_options_page( __( 'Sharing and Privacy Settings', 'pressbooks' ), __( 'Sharing &amp; Privacy', 'pressbooks' ), 'manage_options', 'pressbooks_sharingandprivacy_options', __NAMESPACE__ . '\display_privacy_settings' );

	// Export
	$option = get_option( 'pressbooks_export_options', \Pressbooks\Admin\ExportOptions::getDefaults() );
	$page = new \Pressbooks\Admin\ExportOptions( $option );
	$page->init();
	wp_cache_delete( 'pressbooks_export_options_version', 'options' );
	$version = get_option( 'pressbooks_export_options_version', 0 );
	if ( $version < $page::VERSION ) {
		$page->upgrade( $version );
		update_option( 'pressbooks_export_options_version', $page::VERSION, false );
	}

	add_options_page( __( 'Export Settings', 'pressbooks' ), __( 'Export', 'pressbooks' ), 'manage_options', 'pressbooks_export_options', [ $page, 'render' ] );

	// Import
	$import_page = add_management_page( __( 'Import', 'pressbooks' ), __( 'Import', 'pressbooks' ), 'edit_posts', 'pb_import', __NAMESPACE__ . '\display_import' );
	add_action( 'admin_enqueue_scripts', function ( $hook ) use ( $import_page ) {
		if ( $hook === $import_page ) {
			wp_enqueue_script( 'pb-import' );
		}
	} );

	// Clone a Book
	if ( \Pressbooks\Cloner::isEnabled() ) {
		$cloner_page = add_submenu_page( 'options.php', __( 'Clone a Book', 'pressbooks' ), __( 'Clone a Book', 'pressbooks' ), 'edit_posts', 'pb_cloner', __NAMESPACE__ . '\display_cloner' );
		add_action( 'admin_enqueue_scripts', function ( $hook ) use ( $cloner_page ) {
			if ( $hook === $cloner_page ) {
				wp_enqueue_style( 'pb-cloner' );
				wp_enqueue_script( 'pb-cloner' );
			}
		} );
	}

	// Catalog
	add_submenu_page( 'index.php', __( 'My Catalog', 'pressbooks' ), __( 'My Catalog', 'pressbooks' ), 'read', 'pb_catalog', '\Pressbooks\Catalog::addMenu' );
}

/**
 * When user clicks [Front-Matter Types / Back-Matter Types] sub-menus, then they should be highlighted
 *
 * @param string $file
 *
 * @return string
 */
function fix_parent_file( $file ) {

	$haystack = [
		'edit.php?post_type=front-matter',
		'edit.php?post_type=back-matter',
	];
	if ( in_array( $file, $haystack, true ) ) {
		$file = 'edit.php?post_type=chapter';
	}

	return $file;
}

/**
 * When user clicks [Text] menu, then the [Organize] sub-menu should be highlighted
 *
 * @param string $submenu_file
 * @param string $parent_file
 *
 * @return string
 */
function fix_submenu_file( $submenu_file, $parent_file ) {

	if ( empty( $submenu_file ) && empty( $_REQUEST['post_type'] ) && $parent_file === 'edit.php?post_type=chapter' ) {
		$submenu_file = 'pressbooks';
	}

	return $submenu_file;
}

function network_admin_menu() {
	$option = get_site_option( 'pressbooks_sharingandprivacy_options', \Pressbooks\Admin\Network\SharingAndPrivacyOptions::getDefaults() );
	$page = new \Pressbooks\Admin\Network\SharingAndPrivacyOptions( $option );
	$page->init();
	$version = get_site_option( 'pressbooks_sharingandprivacy_options_version', 0 );
	if ( $version < $page::VERSION ) {
		$page->upgrade( $version );
		update_site_option( 'pressbooks_sharingandprivacy_options_version', $page::VERSION );
	}

	add_submenu_page(
		'settings.php', __( 'Sharing and Privacy Settings', 'pressbooks' ), __( 'Sharing &amp; Privacy', 'pressbooks' ), 'manage_network', 'pressbooks_sharingandprivacy_options', [
		$page,
		'render',
		]
	);
}

/**
 * Fix extraneous menus on WordPress Admin sidebar
 */
function fix_root_admin_menu() {

	remove_menu_page( 'edit.php?post_type=part' );
	remove_menu_page( 'edit.php?post_type=chapter' );
	remove_menu_page( 'edit.php?post_type=front-matter' );
	remove_menu_page( 'edit.php?post_type=back-matter' );
	remove_menu_page( 'edit.php?post_type=metadata' );

	// Catalog
	add_submenu_page( 'index.php', __( 'My Catalog', 'pressbooks' ), __( 'My Catalog', 'pressbooks' ), 'read', 'pb_catalog', '\Pressbooks\Catalog::addMenu' );
}


/**
 * Displays the Organize page.
 *
 * @todo Rewrite organize page by extending \WP_List_Table class
 * @see http://wordpress.org/extend/plugins/custom-list-table-example/
 */
function display_organize() {
	require( PB_PLUGIN_DIR . 'templates/admin/organize.php' );
}

/**
 * Displays the trash page.
 */
function display_trash() {
	require( PB_PLUGIN_DIR . 'templates/admin/trash.php' );
}

/**
 * Displays the Export Admin Page
 */
function display_export() {
	require( PB_PLUGIN_DIR . 'templates/admin/export.php' );
}

/**
 * Displays the Clone a Book Page
 */
function display_cloner() {
	require( PB_PLUGIN_DIR . 'templates/admin/cloner.php' );
}

/**
 * Displays the Import Admin Page
 */
function display_import() {
	require( PB_PLUGIN_DIR . 'templates/admin/import.php' );
}


/**
 * Replace WP logo in menu bar with PB one and add links to About page, Contact page, and forums
 *
 * @param \WP_Admin_Bar $wp_admin_bar The admin bar object as it currently exists
 */
function replace_menu_bar_branding( $wp_admin_bar ) {

	$wp_admin_bar->remove_menu( 'wp-logo' );
	$wp_admin_bar->remove_menu( 'documentation' );
	$wp_admin_bar->remove_menu( 'feedback' );
	$wp_admin_bar->add_menu(
		[
			'id' => 'wp-logo',
			'title' => '<span class="ab-icon"></span>',
			'href' => ( 'https://pressbooks.com/about' ),
			'meta' => [
				'title' => __( 'About Pressbooks', 'pressbooks' ),
			],
		]
	);

	if ( is_user_logged_in() ) {
		// Add "About WordPress" link
		$wp_admin_bar->add_menu(
			[
				'parent' => 'wp-logo',
				'id' => 'about',
				'title' => __( 'About Pressbooks', 'pressbooks' ),
				'href' => 'https://pressbooks.com/about',
			]
		);
	}

	// Add WordPress.org link
	$wp_admin_bar->add_menu(
		[
			'parent' => 'wp-logo-external',
			'id' => 'wporg',
			'title' => __( 'Pressbooks.com', 'pressbooks' ),
			'href' => 'https://pressbooks.com',
		]
	);

	// Add forums link
	$wp_admin_bar->add_menu(
		[
			'parent' => 'wp-logo-external',
			'id' => 'support-forums',
			'title' => __( 'Help', 'pressbooks' ),
			'href' => 'https://pressbooks.com/help',
		]
	);

	// Add feedback link
	$wp_admin_bar->add_menu(
		[
			'parent' => 'wp-logo-external',
			'id' => 'contact',
			'title' => __( 'Contact', 'pressbooks' ),
			'href' => 'https://pressbooks.com/contact',
		]
	);

}

/**
 * Rearrange ordering of Admin bar menu elements for our purposes
 *
 * @param \WP_Admin_Bar $wp_admin_bar
 */
function replace_menu_bar_my_sites( $wp_admin_bar ) {

	$wp_admin_bar->remove_menu( 'my-sites' );

	// Don't show for logged out users or single site mode.
	if ( ! is_user_logged_in() || ! is_multisite() ) {
		return;
	}

	// Show only when the user has at least one site, or they're a super admin.
	if ( count( $wp_admin_bar->user->blogs ) < 1 && ! is_super_admin() ) {
		return;
	}

	$wp_admin_bar->add_menu(
		[
			'id' => 'my-books',
			'title' => __( 'My Catalog', 'pressbooks' ),
			'href' => admin_url( 'index.php?page=pb_catalog' ),
		]
	);

	$wp_admin_bar->add_node(
		[
			'parent' => 'my-books',
			'id' => 'add-new-book',
			'title' => __( 'Add A New Book', 'pressbooks' ),
			'href' => network_home_url( 'wp-signup.php' ),
		]
	);

	if ( \Pressbooks\Cloner::isEnabled() ) {
		$href = home_url( 'wp-admin/options.php?page=pb_cloner', 'relative' );
		if ( ! \Pressbooks\Book::isBook() ) {
			$blogs = get_blogs_of_user( get_current_user_id() );
			foreach ( $blogs as $blog ) {
				if ( ! is_main_site( $blog->userblog_id ) ) {
					$href = get_blogaddress_by_id( $blog->userblog_id ) . 'wp-admin/options.php?page=pb_cloner';
					break;
				}
			}
		}
		$wp_admin_bar->add_node(
			[
				'parent' => 'my-books',
				'id' => 'clone-a-book',
				'title' => __( 'Clone A Book', 'pressbooks' ),
				'href' => $href,
			]
		);
	}

	if ( is_super_admin() ) {

		$wp_admin_bar->add_group(
			[
				'parent' => 'my-books',
				'id' => 'my-books-super-admin',
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => 'my-books-super-admin',
				'id' => 'pb-network-admin',
				'title' => __( 'Network Admin', 'pressbooks' ),
				'href' => network_admin_url(),
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => 'pb-network-admin',
				'id' => 'pb-network-admin-d',
				'title' => __( 'Dashboard', 'pressbooks' ),
				'href' => network_admin_url(),
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => 'pb-network-admin',
				'id' => 'pb-network-admin-s',
				'title' => __( 'Sites', 'pressbooks' ),
				'href' => network_admin_url( 'sites.php' ),
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => 'pb-network-admin',
				'id' => 'pb-network-admin-u',
				'title' => __( 'Users', 'pressbooks' ),
				'href' => network_admin_url( 'users.php' ),
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => 'pb-network-admin',
				'id' => 'pb-network-admin-v',
				'title' => __( 'Visit Network', 'pressbooks' ),
				'href' => network_home_url(),
			]
		);
	}

	// Add site links
	$wp_admin_bar->add_group(
		[
			'parent' => 'my-books',
			'id' => 'my-books-list',
			'meta' => [
				'class' => is_super_admin() ? 'ab-sub-secondary' : '',
			],
		]
	);

	foreach ( (array) $wp_admin_bar->user->blogs as $blog ) {

		$blavatar = '<span class="blavatar"/></span>';

		$blogname = empty( $blog->blogname ) ? $blog->domain : $blog->blogname;
		$menu_id = 'blog-' . $blog->userblog_id;

		$admin_url = get_admin_url( $blog->userblog_id );

		$wp_admin_bar->add_menu(
			[
				'parent' => 'my-books-list',
				'id' => $menu_id,
				'title' => $blavatar . $blogname,
				'href' => $admin_url,
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => $menu_id,
				'id' => $menu_id . '-d',
				'title' => __( 'Dashboard', 'pressbooks' ),
				'href' => $admin_url,
			]
		);

		if ( current_user_can_for_blog( $blog->userblog_id, 'edit_posts' ) ) {
			$wp_admin_bar->remove_menu( $menu_id . '-n' );
			$wp_admin_bar->remove_menu( $menu_id . '-c' );
		}

		$wp_admin_bar->add_menu(
			[
				'parent' => $menu_id,
				'id' => $menu_id . '-v',
				'title' => __( 'Visit Site', 'pressbooks' ),
				'href' => get_home_url( $blog->userblog_id, '/' ),
			]
		);
	}

}


/**
 * Remove Updates item from admin menu
 *
 * @param \WP_Admin_Bar $wp_admin_bar
 */
function remove_menu_bar_update( $wp_admin_bar ) {
	$wp_admin_bar->remove_menu( 'updates' );
}


/**
 * Remove New Content item from admin menu
 *
 * @param \WP_Admin_Bar $wp_admin_bar
 */
function remove_menu_bar_new_content( $wp_admin_bar ) {
	$wp_admin_bar->remove_menu( 'new-content' );
}


/**
 * Edit form hacks
 */
function edit_form_hacks() {
	default_meta_checkboxes();
	transform_category_selection_box();
}


/**
 * @param \WP_Customize_Manager $wp_customize
 *
 * @see http://codex.wordpress.org/Plugin_API/Action_Reference/customize_register
 */
function customize_register( $wp_customize ) {
	$wp_customize->remove_section( 'static_front_page' );
}


/**
 * Default selections for checkboxes created by custom_metadata class.
 */
function default_meta_checkboxes() {

	global $pagenow;
	if ( 'post-new.php' === $pagenow ) {
		?>
		<script type="text/javascript">
			jQuery('#pb_export').attr('checked', 'checked');
			jQuery('#pb_show_title').attr('checked', 'checked');
		</script>
		<?php
	}
}


/**
 * Transforms the category selection meta box from checkboxes to radio buttons to ensure only one item
 */
function transform_category_selection_box() {

	$base = get_bloginfo( 'url' );

	if ( ( $base . '/wp-admin/post-new.php?post_type=front-matter' ) === 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) {
		$term = get_term_by( 'slug', 'miscellaneous', 'front-matter-type' );
	} elseif ( ( $base . '/wp-admin/post-new.php?post_type=back-matter' ) === 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) {
		$term = get_term_by( 'slug', 'miscellaneous', 'back-matter-type' );
	} elseif ( ( $base . '/wp-admin/post-new.php?post_type=chapter' ) === 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) {
		$term = get_term_by( 'slug', 'standard', 'chapter-type' );
	}

	?>
	<script type="text/javascript">
		jQuery('.category-tabs, .category-pop').remove();
		jQuery('input:checkbox[id^="in-front-matter-type"]').each(function () {
			jQuery(this).replaceWith(jQuery(this).clone(true).attr('type', 'radio'));
		});
		jQuery('input:checkbox[id^="in-back-matter-type"]').each(function () {
			jQuery(this).replaceWith(jQuery(this).clone(true).attr('type', 'radio'));
		});
		jQuery('input:checkbox[id^="in-chapter-type"]').each(function () {
			jQuery(this).replaceWith(jQuery(this).clone(true).attr('type', 'radio'));
		});
		<?php if ( isset( $term ) ) :  ?>
		jQuery('input:radio[id="in-front-matter-type-<?php echo $term->term_id; ?>"]').attr('checked', 'checked');
		jQuery('input:radio[id="in-back-matter-type-<?php echo $term->term_id; ?>"]').attr('checked', 'checked');
		jQuery('input:radio[id="in-chapter-type-<?php echo $term->term_id; ?>"]').attr('checked', 'checked');
		<?php endif; ?>
	</script>
	<?php
}

function disable_customizer() {
	return 'no-customize-support';
}

/**
 * Init event called at admin_init
 * Instantiates various sub-classes, remove meta boxes from post pages & registers custom post status.
 */
function init_css_js() {
	$assets = new Assets( 'pressbooks', 'plugin' );

	// This is to work around JavaScript dependency errors
	global $concatenate_scripts;
	$concatenate_scripts = false;

	// Note: Will auto-register a dependency $handle named 'colors'
	wp_admin_css_color(
		'pb_colors', 'Pressbooks', $assets->getPath( 'styles/colors-pb.css' ), apply_filters(
			'pressbooks_admin_colors', [
			'#b40026',
			'#d4002d',
			'#e9e9e9',
			'#dfdfdf',
			]
		)
	);

	wp_deregister_style( 'pressbooks-book' ); // Theme's CSS

	wp_enqueue_style( 'pressbooks-admin', $assets->getPath( 'styles/pressbooks.css' ) );

	if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'pb_catalog' ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'pressbooks-catalog', $assets->getPath( 'styles/catalog.css' ) );
		wp_enqueue_script( 'color-picker', $assets->getPath( 'scripts/color-picker.js' ), [ 'wp-color-picker' ] );
		wp_enqueue_script( 'select2-js', $assets->getPath( 'scripts/select2.js' ), [ 'jquery' ] );
	}

	if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'pressbooks_theme_options' ) {
		wp_enqueue_style( 'select2', $assets->getPath( 'styles/select2.css' ) );
		wp_enqueue_style( 'theme-options', $assets->getPath( 'styles/theme-options.css' ) );
		wp_enqueue_script( 'select2-js', $assets->getPath( 'scripts/select2.js' ), [ 'jquery' ] );
		wp_enqueue_script( 'theme-options-js', $assets->getPath( 'scripts/theme-options.js' ), [ 'jquery' ] );
	}

	if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'pressbooks_export_options' ) {
		wp_enqueue_script( 'pressbooks/theme-lock', $assets->getPath( 'scripts/theme-lock.js' ), [ 'jquery' ] );
		wp_localize_script(
			'pressbooks/theme-lock', 'PB_ThemeLockToken', [
			// Strings
			'confirmation' => __( 'Are you sure you want to unlock your theme? This will update your book to the most recent version of your selected theme, which may change your book&rsquo;s appearance and page count. Once you save your settings on this page, this action will NOT be reversable!', 'pressbooks' ),
			]
		);
	}

	if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'pb_custom_css' ) {
		wp_enqueue_style( 'pb-custom-css', $assets->getPath( 'styles/custom-css.css' ) );
	}

	// Don't let other plugins override our scripts
	$bad_scripts = [ 'jquery-blockui', 'jquery-bootstrap', 'pb-organize', 'pb-feedback', 'pb-cloner', 'pb-export', 'pb-metadata', 'pb-import' ];
	array_walk(
		$bad_scripts, function ( $value, $key ) {
			wp_deregister_script( $value );
		}
	);

	// Enqueue later, on-the-fly, using action: admin_print_scripts-
	wp_register_script( 'jquery-blockui', $assets->getPath( 'scripts/blockui.js' ), [ 'jquery', 'jquery-ui-core' ] );
	wp_register_script( 'pb-cloner', $assets->getPath( 'scripts/cloner.js' ), [ 'jquery' ] );
	wp_register_script( 'pb-export', $assets->getPath( 'scripts/export.js' ), [ 'jquery' ] );
	wp_register_script( 'pb-organize', $assets->getPath( 'scripts/organize.js' ), [ 'jquery', 'jquery-ui-core', 'jquery-blockui' ] );
	wp_register_script( 'pb-metadata', $assets->getPath( 'scripts/book-information.js' ), [ 'jquery' ] );
	wp_register_script( 'pb-import', $assets->getPath( 'scripts/import.js' ), [ 'jquery' ] );

	wp_register_style( 'pb-cloner', $assets->getPath( 'styles/cloner.css' ) );
	wp_register_style( 'pb-export', $assets->getPath( 'styles/export.css' ) );
	wp_register_style( 'pb-organize', $assets->getPath( 'styles/organize.css' ) );

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-core' );
	wp_enqueue_script( 'jquery-ui-sortable' );
}

/* ------------------------------------------------------------------------ *
 * Privacy
 * ------------------------------------------------------------------------ */

/**
 * Privacy settings initialization
 */
function privacy_settings_init() {
	add_settings_section(
		'privacy_settings_section',
		'',
		__NAMESPACE__ . '\privacy_settings_section_callback',
		'privacy_settings'
	);
	add_settings_field(
		'blog_public',
		__( 'Book Visibility', 'pressbooks' ),
		__NAMESPACE__ . '\privacy_blog_public_callback',
		'privacy_settings',
		'privacy_settings_section'
	);
	add_settings_field(
		'permissive_private_content',
		__( 'Private Content', 'pressbooks' ),
		__NAMESPACE__ . '\privacy_permissive_private_content_callback',
		'privacy_settings',
		'privacy_settings_section'
	);
	add_settings_field(
		'disable_comments',
		__( 'Disable Comments', 'pressbooks' ),
		__NAMESPACE__ . '\privacy_disable_comments_callback',
		'privacy_settings',
		'privacy_settings_section'
	);
	$sharingandprivacy = get_site_option( 'pressbooks_sharingandprivacy_options' );
	if ( ! empty( $sharingandprivacy['allow_redistribution'] ) ) {
		add_settings_field(
			'latest_files_public',
			__( 'Share Latest Export Files', 'pressbooks' ),
			__NAMESPACE__ . '\privacy_latest_files_public_callback',
			'privacy_settings',
			'privacy_settings_section'
		);
	}
	register_setting(
		'privacy_settings',
		'blog_public',
		__NAMESPACE__ . '\privacy_blog_public_sanitize'
	);
	register_setting(
		'privacy_settings',
		'permissive_private_content',
		__NAMESPACE__ . '\privacy_permissive_private_content_sanitize'
	);
	register_setting(
		'privacy_settings',
		'pressbooks_sharingandprivacy_options',
		__NAMESPACE__ . '\privacy_disable_comments_sanitize'
	);
	register_setting(
		'privacy_settings',
		'pbt_redistribute_settings',
		__NAMESPACE__ . '\privacy_pbt_redistribute_settings_sanitize'
	);

}


/**
 * Privacy settings section callback
 */
function privacy_settings_section_callback() {
	echo '<p>' . __( 'Sharing and Privacy settings', 'pressbooks' ) . '.</p>'; // TK
}


/**
 * Privacy settings, blog_public field callback
 *
 * @param $args
 */
function privacy_blog_public_callback( $args ) {
	$blog_public = get_option( 'blog_public' );
	$html = '<input type="radio" id="blog-public" name="blog_public" value="1" ';
	if ( $blog_public ) {
		$html .= 'checked="checked" ';
	}
	$html .= '/>';
	$html .= '<label for="blog-public"> ' . __( 'Public. I would like this book to be visible to everyone.', 'pressbooks' ) . '</label><br />';
	$html .= '<input type="radio" id="blog-public" name="blog_public" value="0" ';
	if ( ! $blog_public ) {
		$html .= 'checked="checked" ';
	}
	$html .= '/>';
	$html .= '<label for="blog-norobots"> ' . __( 'Private. I would like this book to be accessible only to people I invite.', 'pressbooks' ) . '</label>';
	echo $html;
}

/**
 * Privacy settings, permissive_private_content field callback
 *
 * @param $args
 */
function privacy_permissive_private_content_callback( $args ) {
	$permissive_private_content = absint( get_option( 'permissive_private_content' ) );
	$subscriber = get_role( 'subscriber' );
	$contributor = get_role( 'contributor' );
	$author = get_role( 'author' );
	if ( ! empty( $permissive_private_content ) ) { // If permissive private content is set to true, adjust capabilities
		$subscriber->add_cap( 'read_private_posts' );
		$contributor->add_cap( 'read_private_posts' );
		$author->add_cap( 'read_private_posts' );
	} else {
		$subscriber->remove_cap( 'read_private_posts' );
		$contributor->remove_cap( 'read_private_posts' );
		$author->remove_cap( 'read_private_posts' );
	} ?>
	<p><?php _e( 'Who can see private front matter, chapters and back matter?', 'pressbooks' ); ?></p>
	<fieldgroup>
		<input type="radio" id="standard-private-content" name="permissive_private_content" value="0" <?php checked( $permissive_private_content, 0 ); ?>/>
		<label for="standard-private-content"><?php _e( 'Only logged in editors and administrators.', 'pressbooks' ); ?></label><br/>
		<input type="radio" id="permissive-private-content" name="permissive_private_content" value="1" <?php checked( $permissive_private_content, 1 ); ?>/>
		<label for="permissive-private-content"><?php _e( 'All logged in users including subscribers.', 'pressbooks' ); ?></label>
	</fieldgroup>
<?php }

/**
 * Privacy settings, disable_comments field callback
 *
 * @param $args
 */
function privacy_disable_comments_callback( $args ) {
	$options = get_option( 'pressbooks_sharingandprivacy_options', [ 'disable_comments' => 1 ] );
	$html = '<input type="radio" id="disable-comments" name="pressbooks_sharingandprivacy_options[disable_comments]" value="1" ';
	if ( $options['disable_comments'] ) {
		$html .= 'checked="checked" ';
	}
	$html .= '/>';
	$html .= '<label for="disable-comments"> ' . __( 'Yes. I want to automatically disable comments, trackbacks and pingbacks on all front matter, chapters and back matter.', 'pressbooks' ) . '</label><br />';
	$html .= '<input type="radio" id="enable-comments" name="pressbooks_sharingandprivacy_options[disable_comments]" value="0" ';
	if ( ! $options['disable_comments'] ) {
		$html .= 'checked="checked" ';
	}
	$html .= '/>';
	$html .= '<label for="enable-comments"> ' . __( 'No. I want to leave comments, trackbacks and pingbacks enabled on all front matter, chapters and back matter unless I disable them manually.', 'pressbooks' ) . '</label>';
	echo $html;
}

/**
 * Sharing settings, latest_files_public field callback
 *
 * @param $args
 */
function privacy_latest_files_public_callback( $args ) {
	$blog_public = get_option( 'pbt_redistribute_settings' );
	$html = '<input type="radio" id="latest_files_public" name="pbt_redistribute_settings[latest_files_public]" value="1" ';
	if ( $blog_public['latest_files_public'] ) {
		$html .= 'checked="checked" ';
	}
	$html .= '/>';
	$html .= '<label for="latest_files_public"> ' . __( 'Yes. I would like the latest export files to be available on the homepage for free, to everyone.', 'pressbooks' ) . '</label><br />';
	$html .= '<input type="radio" id="latest_files_private" name="pbt_redistribute_settings[latest_files_public]" value="0" ';
	if ( ! $blog_public['latest_files_public'] ) {
		$html .= 'checked="checked" ';
	}
	$html .= '/>';
	$html .= '<label for="latest_files_private"> ' . __( 'No. I would like the latest export files to only be available to administrators.', 'pressbooks' ) . '</label>';
	echo $html;
}

/**
 * Privacy settings, blog_public field sanitization
 *
 * @param $input
 *
 * @return string
 */
function privacy_blog_public_sanitize( $input ) {
	return absint( $input );
}

/**
 * Privacy settings, permissive_private_content field sanitization
 *
 * @param $input
 *
 * @return string
 */
function privacy_permissive_private_content_sanitize( $input ) {
	return absint( $input );
}

/**
 * Privacy settings, disable_comments field sanitization
 *
 * @param $input
 *
 * @return string
 */
function privacy_disable_comments_sanitize( $input ) {
	$output['disable_comments'] = absint( $input['disable_comments'] );
	return $output;
}

/**
 * Privacy settings, pbt_redistribute_settings field sanitization
 *
 * @param $input
 *
 * @return string
 */
function privacy_pbt_redistribute_settings_sanitize( $input ) {
	$output['latest_files_public'] = absint( $input['latest_files_public'] );
	return $output;
}

/**
 * Display Privacy settings
 */
function display_privacy_settings() {
	?>
	<div class="wrap">
		<h2><?php _e( 'Sharing and Privacy Settings', 'pressbooks' ); ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'privacy_settings' );
			do_settings_sections( 'privacy_settings' ); ?>
			<?php submit_button(); ?>
		</form>
	</div>

	<?php
}

/* ------------------------------------------------------------------------ *
 * Misc
 * ------------------------------------------------------------------------ */

/**
 * Hook for add_action( 'admin_notices', ... ) Echo $_SESSION['pb_notices'] if any.
 *
 * @global array $_SESSION ['pb_errors'] *
 * @global array $_SESSION ['pb_notices']
 */
function admin_notices() {

	if ( ! empty( $_SESSION['pb_errors'] ) ) {
		// Array-ify the error(s).
		if ( ! is_array( $_SESSION['pb_errors'] ) ) {
			$_SESSION['pb_errors'] = [ $_SESSION['pb_errors'] ];
		}
		// Print the error(s).
		foreach ( $_SESSION['pb_errors'] as $msg ) {
			echo '<div class="error"><p>' . $msg . '</p></div>';
		}
		// Destroy the session.
		unset( $_SESSION['pb_errors'] );
	}

	if ( ! empty( $_SESSION['pb_notices'] ) ) {
		// Array-ify the notice(s).
		if ( ! is_array( $_SESSION['pb_notices'] ) ) {
			$_SESSION['pb_notices'] = [ $_SESSION['pb_notices'] ];
		}
		// Print the notice(s).
		foreach ( $_SESSION['pb_notices'] as $msg ) {
			echo '<div class="updated"><p>' . $msg . '</p></div>';
		}
		// Destroy the session.
		unset( $_SESSION['pb_notices'] );
	}
}
