<?php
namespace um\admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\admin\Admin' ) ) {


	/**
	 * Class Admin
	 * @package um\admin
	 */
	class Admin extends Admin_Functions {


		/**
		 * @var string
		 */
		var $templates_path;


		/**
		 * Admin constructor.
		 */
		function __construct() {
			parent::__construct();

			$this->templates_path = um_path . 'includes/admin/templates/';

			add_action( 'admin_init', array( &$this, 'admin_init' ), 0 );

			$prefix = is_network_admin() ? 'network_admin_' : '';
			add_filter( "{$prefix}plugin_action_links_" . um_plugin, array( &$this, 'plugin_links' ) );

			add_action( 'um_admin_do_action__user_cache', array( &$this, 'user_cache' ) );
			add_action( 'um_admin_do_action__purge_temp', array( &$this, 'purge_temp' ) );
			add_action( 'um_admin_do_action__manual_upgrades_request', array( &$this, 'manual_upgrades_request' ) );
			add_action( 'um_admin_do_action__duplicate_form', array( &$this, 'duplicate_form' ) );
			add_action( 'um_admin_do_action__um_hide_locale_notice', array( &$this, 'um_hide_notice' ) );
			add_action( 'um_admin_do_action__um_can_register_notice', array( &$this, 'um_hide_notice' ) );
			add_action( 'um_admin_do_action__um_hide_exif_notice', array( &$this, 'um_hide_notice' ) );
			add_action( 'um_admin_do_action__user_action', array( &$this, 'user_action' ) );

			add_action( 'um_admin_do_action__install_predefined_pages', array( &$this, 'install_predefined_pages' ) );

			add_filter( 'admin_body_class', array( &$this, 'admin_body_class' ), 999 );

			add_action( 'parent_file', array( &$this, 'parent_file' ), 9 );
			add_filter( 'gettext', array( &$this, 'gettext' ), 10, 4 );
			add_filter( 'post_updated_messages', array( &$this, 'post_updated_messages' ) );



			// @since 3.0
			add_action( 'load-ultimate-member_page_um-modules', array( &$this, 'handle_modules_actions' ) );
			add_action( 'load-ultimate-member_page_um_options', array( &$this, 'handle_email_notifications_actions' ) );
			add_action( 'load-users.php', array( UM()->install(), 'set_default_user_status' ) );
		}


		function handle_email_notifications_actions() {
			if ( ! isset( $_GET['tab'] ) || 'email' !== sanitize_key( $_GET['tab'] ) ) {
				return;
			}

			//remove extra query arg
			if ( ! empty( $_GET['_wp_http_referer'] ) ) {
				exit( wp_redirect( remove_query_arg( [ '_wp_http_referer', '_wpnonce' ], wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
			}
		}

		/**
		 * Handles Modules list table
		 *
		 * @since 3.0
		 *
		 * @uses Modules::activate() UM()->modules()->activate( $slug )
		 * @uses Modules::deactivate() UM()->modules()->deactivate( $slug )
		 * @uses Modules::flush_data() UM()->modules()->flush_data( $slug )
		 */
		function handle_modules_actions() {
			if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
				$redirect = remove_query_arg( [ '_wp_http_referer' ], wp_unslash( $_REQUEST['_wp_http_referer'] ) );
			} else {
				$redirect = get_admin_url( null, 'admin.php?page=um-modules' );
			}

			if ( isset( $_GET['action'] ) ) {
				switch ( sanitize_key( $_GET['action'] ) ) {
					case 'activate': {
						// Activate module
						$slugs = [];
						if ( isset( $_GET['slug'] ) ) {
							// single activate
							$slug = sanitize_key( $_GET['slug'] );

							if ( empty( $slug ) ) {
								exit( wp_redirect( $redirect ) );
							}

							check_admin_referer( 'um_module_activate' . $slug . get_current_user_id() );
							$slugs = [ $slug ];
						} elseif( isset( $_REQUEST['item'] ) ) {
							// bulk activate
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Modules', 'ultimate-member' ) ) );
							$slugs = array_map( 'sanitize_key', $_REQUEST['item'] );
						}

						if ( ! count( $slugs ) ) {
							exit( wp_redirect( $redirect ) );
						}

						$results = 0;
						foreach ( $slugs as $slug ) {
							if ( UM()->modules()->activate( $slug ) ) {
								$results++;
							}
						}

						if ( ! $results ) {
							exit( wp_redirect( $redirect ) );
						}

						exit( wp_redirect( add_query_arg( 'msg', 'a', $redirect ) ) );
						break;
					}
					case 'deactivate': {
						// Deactivate module
						$slugs = [];
						if ( isset( $_GET['slug'] ) ) {
							// single deactivate
							$slug = sanitize_key( $_GET['slug'] );

							if ( empty( $slug ) ) {
								exit( wp_redirect( $redirect ) );
							}

							check_admin_referer( 'um_module_deactivate' . $slug . get_current_user_id() );
							$slugs = [ $slug ];
						} elseif( isset( $_REQUEST['item'] ) )  {
							// bulk deactivate
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Modules', 'ultimate-member' ) ) );
							$slugs = array_map( 'sanitize_key', $_REQUEST['item'] );
						}

						if ( ! count( $slugs ) ) {
							exit( wp_redirect( $redirect ) );
						}

						$results = 0;
						foreach ( $slugs as $slug ) {
							if ( UM()->modules()->deactivate( $slug ) ) {
								$results++;
							}
						}

						if ( ! $results ) {
							exit( wp_redirect( $redirect ) );
						}

						exit( wp_redirect( add_query_arg( 'msg', 'd', $redirect ) ) );
						break;
					}
					case 'flush-data': {
						// Flush module's data
						$slugs = [];
						if ( isset( $_GET['slug'] ) ) {
							// single flush
							$slug = sanitize_key( $_GET['slug'] );

							if ( empty( $slug ) ) {
								exit( wp_redirect( $redirect ) );
							}

							check_admin_referer( 'um_module_flush' . $slug . get_current_user_id() );
							$slugs = [ $slug ];
						} elseif( isset( $_REQUEST['item'] ) )  {
							// bulk flush
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Modules', 'ultimate-member' ) ) );
							$slugs = array_map( 'sanitize_key', $_REQUEST['item'] );
						}

						if ( ! count( $slugs ) ) {
							exit( wp_redirect( $redirect ) );
						}

						$results = 0;
						foreach ( $slugs as $slug ) {
							if ( UM()->modules()->flush_data( $slug ) ) {
								$results++;
							}
						}

						if ( ! $results ) {
							exit( wp_redirect( $redirect ) );
						}

						exit( wp_redirect( add_query_arg( 'msg', 'f', $redirect ) ) );
						break;
					}
				}
			}

			//remove extra query arg
			if ( ! empty( $_GET['_wp_http_referer'] ) ) {
				exit( wp_redirect( remove_query_arg( [ '_wp_http_referer', '_wpnonce' ], wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
			}
		}


		/**
		 * Adds class to our admin pages
		 *
		 * @param $classes
		 *
		 * @return string
		 */
		function admin_body_class( $classes ) {
			if ( $this->is_um_screen() ) {
				return "$classes um-admin";
			}
			return $classes;
		}


		/**
		 *
		 */
		function manual_upgrades_request() {
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
				die();
			}

			$last_request = get_option( 'um_last_manual_upgrades_request', false );

			if ( empty( $last_request ) || time() > $last_request + DAY_IN_SECONDS ) {

				if ( is_multisite() ) {
					$blogs_ids = get_sites();
					foreach( $blogs_ids as $b ) {
						switch_to_blog( $b->blog_id );
						wp_clean_update_cache();

						UM()->plugin_updater()->um_checklicenses();

						update_option( 'um_last_manual_upgrades_request', time() );
						restore_current_blog();
					}
				} else {
					wp_clean_update_cache();

					UM()->plugin_updater()->um_checklicenses();

					update_option( 'um_last_manual_upgrades_request', time() );
				}

				$url = add_query_arg( array( 'page' => 'ultimatemember', 'update' => 'got_updates' ), admin_url( 'admin.php' ) );
			} else {
				$url = add_query_arg( array( 'page' => 'ultimatemember', 'update' => 'often_updates' ), admin_url( 'admin.php' ) );
			}
			exit( wp_redirect( $url ) );
		}


		/**
		 * Core pages installation
		 */
		function install_predefined_pages() {
			if ( ! is_admin() ) {
				die();
			}

			UM()->install()->predefined_pages();

			//check empty pages in settings
			$empty_pages = array();

			$predefined_pages = array_keys( UM()->config()->get( 'predefined_pages' ) );
			foreach ( $predefined_pages as $slug ) {
				$page_id = um_get_predefined_page_id( $slug );
				if ( ! $page_id ) {
					$empty_pages[] = $slug;
					continue;
				}

				$page = get_post( $page_id );
				if ( ! $page ) {
					$empty_pages[] = $slug;
					continue;
				}
			}

			//if there aren't empty pages - then hide pages notice
			if ( empty( $empty_pages ) ) {
				$hidden_notices = get_option( 'um_hidden_admin_notices', array() );
				$hidden_notices[] = 'wrong_pages';

				update_option( 'um_hidden_admin_notices', $hidden_notices );
			}

			$url = add_query_arg( array( 'page' => 'um_options' ), admin_url( 'admin.php' ) );
			exit( wp_redirect( $url ) );
		}


		/**
		 * Clear all users cache
		 *
		 * @param $action
		 */
		function user_cache( $action ) {
			global $wpdb;
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
				die();
			}

			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'um_cache_userdata_%'" );

			$url = add_query_arg( array( 'page' => 'ultimatemember', 'update' => 'cleared_cache' ), admin_url( 'admin.php' ) );
			exit( wp_redirect( $url ) );
		}


		/**
		 * Purge temp uploads dir
		 * @param $action
		 */
		function purge_temp( $action ) {
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
				die();
			}

			UM()->files()->remove_dir( UM()->files()->upload_temp );

			$url = add_query_arg( array( 'page' => 'ultimatemember', 'update' => 'purged_temp' ), admin_url( 'admin.php' ) );
			exit( wp_redirect( $url ) );
		}


		/**
		 * Duplicate form
		 *
		 * @param $action
		 */
		function duplicate_form( $action ) {
			if ( ! is_admin() || ! current_user_can('manage_options') ) {
				die();
			}
			if ( ! isset( $_REQUEST['post_id'] ) || ! is_numeric( $_REQUEST['post_id'] ) ) {
				die();
			}

			$post_id = absint( $_REQUEST['post_id'] );

			$n = array(
				'post_type'     => 'um_form',
				'post_title'    => sprintf( __( 'Duplicate of %s', 'ultimate-member' ), get_the_title( $post_id ) ),
				'post_status'   => 'publish',
				'post_author'   => get_current_user_id(),
			);

			$n_id = wp_insert_post( $n );

			$n_fields = get_post_custom( $post_id );
			foreach ( $n_fields as $key => $value ) {

				if ( $key == '_um_custom_fields' ) {
					$the_value = unserialize( $value[0] );
				} else {
					$the_value = $value[0];
				}

				update_post_meta( $n_id, $key, $the_value );

			}

			delete_post_meta( $n_id, '_um_core' );

			$url = admin_url( 'edit.php?post_type=um_form' );
			$url = add_query_arg( 'update', 'form_duplicated', $url );

			exit( wp_redirect( $url ) );

		}


		/**
		 * Action to hide notices in admin
		 *
		 * @param $action
		 */
		function um_hide_notice( $action ) {
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
				die();
			}

			update_option( $action, 1 );
			exit( wp_redirect( remove_query_arg( 'um_adm_action' ) ) );
		}


		/**
		 * Various user actions
		 *
		 * @param $action
		 */
		function user_action( $action ) {
			if ( ! is_admin() || ! current_user_can( 'edit_users' ) ) {
				die();
			}
			if ( ! isset( $_REQUEST['sub'] ) ) {
				die();
			}
			if ( ! isset( $_REQUEST['user_id'] ) ) {
				die();
			}

			um_fetch_user( absint( $_REQUEST['user_id'] ) );

			$subaction = sanitize_key( $_REQUEST['sub'] );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_user_action_hook
			 * @description Action on bulk user subaction
			 * @input_vars
			 * [{"var":"$subaction","type":"string","desc":"Bulk Subaction"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_user_action_hook', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_user_action_hook', 'my_admin_user_action', 10, 1 );
			 * function my_admin_user_action( $subaction ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_admin_user_action_hook', $subaction );
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_user_action_{$subaction}_hook
			 * @description Action on bulk user subaction
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_user_action_{$subaction}_hook', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_user_action_{$subaction}_hook', 'my_admin_user_action', 10 );
			 * function my_admin_user_action() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_admin_user_action_{$subaction}_hook" );

			um_reset_user();

			wp_redirect( add_query_arg( 'update', 'user_updated', admin_url( '?page=ultimatemember' ) ) );
			exit;

		}


		/**
		 * Add any custom links to plugin page
		 *
		 * @param array $links
		 *
		 * @return array
		 */
		function plugin_links( $links ) {
			$more_links[] = '<a href="http://docs.ultimatemember.com/">' . __( 'Docs', 'ultimate-member' ) . '</a>';
			$more_links[] = '<a href="'.admin_url().'admin.php?page=um_options">' . __( 'Settings', 'ultimate-member' ) . '</a>';

			$links = $more_links + $links;
			return $links;
		}


		/**
		 * Init admin action/filters + request handlers
		 */
		function admin_init() {
			if ( is_admin() && current_user_can( 'manage_options' ) && ! empty( $_REQUEST['um_adm_action'] ) ) {
				$action = sanitize_key( $_REQUEST['um_adm_action'] );

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_admin_do_action__
				 * @description Make some action on custom admin action
				 * @input_vars
				 * [{"var":"$action","type":"string","desc":"Admin Action"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_admin_do_action__', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_admin_do_action__', 'my_admin_do_action', 10, 1 );
				 * function my_admin_do_action( $action ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_admin_do_action__', $action );
				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_admin_do_action__{$action}
				 * @description Make some action on custom admin $action
				 * @input_vars
				 * [{"var":"$action","type":"string","desc":"Admin Action"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_admin_do_action__{$action}', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_admin_do_action__{$action}', 'my_admin_do_action', 10, 1 );
				 * function my_admin_do_action( $action ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( "um_admin_do_action__{$action}", $action );
			}
		}


		/**
		 * Updated post messages
		 *
		 * @param array $messages
		 *
		 * @return array
		 */
		function post_updated_messages( $messages ) {
			global $post_ID;

			$post_type = get_post_type( $post_ID );

			if ( $post_type == 'um_form' ) {
				$messages['um_form'] = array(
					0   => '',
					1   => __( 'Form updated.', 'ultimate-member' ),
					2   => __( 'Custom field updated.', 'ultimate-member' ),
					3   => __( 'Custom field deleted.', 'ultimate-member' ),
					4   => __( 'Form updated.', 'ultimate-member' ),
					5   => isset( $_GET['revision'] ) ? __( 'Form restored to revision.', 'ultimate-member' ) : false,
					6   => __( 'Form created.', 'ultimate-member' ),
					7   => __( 'Form saved.', 'ultimate-member' ),
					8   => __( 'Form submitted.', 'ultimate-member' ),
					9   => __( 'Form scheduled.', 'ultimate-member' ),
					10  => __( 'Form draft updated.', 'ultimate-member' ),
				);
			}

			return $messages;
		}


		/**
		 * Gettext filters
		 *
		 * @param $translation
		 * @param $text
		 * @param $domain
		 *
		 * @return string
		 */
		function gettext( $translation, $text, $domain ) {
			global $post;
			if ( isset( $post->post_type ) && $this->is_plugin_post_type() ) {
				$translations = get_translations_for_domain( $domain );
				if ( $text == 'Publish' ) {
					return $translations->translate( 'Create' );
				} elseif ( $text == 'Move to Trash' ) {
					return $translations->translate( 'Delete' );
				}
			}

			return $translation;
		}


		/**
		 * Fix parent file for correct highlighting
		 *
		 * @param $parent_file
		 *
		 * @return string
		 */
		function parent_file( $parent_file ) {
			global $current_screen;
			$screen_id = $current_screen->id;
			if ( strstr( $screen_id, 'um_' ) ) {
				$parent_file = 'ultimatemember';
			}
			return $parent_file;
		}


		/**
		 * @since 2.0
		 *
		 * @return core\Admin_Notices()
		 */
		function notices() {
			if ( empty( UM()->classes['admin_notices'] ) ) {
				UM()->classes['admin_notices'] = new core\Admin_Notices();
			}
			return UM()->classes['admin_notices'];
		}
	}
}
