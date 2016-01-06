<?php

/*

	Plugin Name: Syndicate Out
	Plugin URI: http://www.flutt.co.uk/development/wordpress-plugins/syndicate-out/
	Version: 0.9
	Text Domain: syndicate-out
	Domain Path: /lang
	Description: Syndicates posts made in any specified category to another WP blog using WordPress' built in XML-RPC functionality.
	Author: ConfuzzledDuck
	Author URI: http://www.flutt.co.uk/

*/

#
#  syndicate-out.php
#
#  Created by Jonathon Wardman on 09-07-2009.
#  Copyright 2009 - 2016, Jonathon Wardman. All rights reserved.
#
#  This program is free software: you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation, either version 3 of the License, or
#  (at your option) any later version.
#
#  You may obtain a copy of the License at:
#  http://www.gnu.org/licenses/gpl-3.0.txt
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.


	 // Nothing in this plugin works outside of the admin area, so don't bother
	 // loading it if we're not looking at the admin panel...
if ( is_admin() ) {

 /* Setup section. */

	 // Global constants and variables relating to posts...
	define( 'SO_OPTIONS_VERSION', 3 );

	 // Register functions...
	add_action( 'plugins_loaded', 'syndicate_out_init' );
	add_action( 'admin_menu', 'syndicate_out_menu' );
	add_action( 'admin_init', 'syndicate_out_register_settings' );
	add_action( 'save_post', 'syndicate_out_post' );
	add_action( 'before_delete_post', 'syndicate_out_post_delete' );
	add_filter( 'plugin_action_links', 'syndicate_out_settings_link', 10, 2 );

	// Register the plugin activation and delete functions...
	//register_activation_hook( __FILE__, 'syndicate_out_activate' );
	//register_uninstall_hook( __FILE__, 'syndicate_out_delete' );

 /* Admin section. */

	 // Plugin initialisation...
	function syndicate_out_init() {

		load_plugin_textdomain( 'syndicate-out', false, dirname( plugin_basename( __FILE__ ) ).'/lang/' );

	}

	 // Set up meta box actions...
	function syndicate_out_add_meta_box_actions() {

		$postTypes = array( 'post' );
		
	/**
	 * Filter the post types for which a syndication box will be shown.
	 *
	 * @since 0.9
	 *
	 * @param array $postTypes The types of post which will display a syndication box.
	 */
		$postTypes = apply_filters( 'syndicate_out_post_types', $postTypes );
		foreach ( $postTypes AS $postType ) {
			add_action( 'add_meta_boxes_'.$postType, 'syndicate_out_meta_box' );
		}

	}

	 // Admin menu...
	function syndicate_out_menu() {

		add_submenu_page( 'options-general.php', 'Syndicate Out Settings', 'Syndication', 'manage_options', 'syndicate_out', 'syndicate_out_admin' );
		syndicate_out_add_meta_box_actions();

	}

	 // Settings link on plugins page...
	function syndicate_out_settings_link( $links, $file ) {

		if ( plugin_basename( __FILE__ ) == $file ) {
			array_push( $links, '<a href="options-general.php?page=syndicate_out">'.__( 'Settings', 'syndicate-out' ).'</a>' );
		}
		return $links;

	}

	 // Register valid admin options...
	function syndicate_out_register_settings() {

		register_setting( 'syndicate-out-options', 'so_options', 'syndicate_out_sanitize_options' );

	}

	 // Admin page...
	function syndicate_out_admin() {

		if ( false === ( $syndicateOutOptions = get_option( 'so_options' ) ) ) {
			$syndicateOutOptions['group'][] = array( 'name' => null,
			                                         'category' => null,
			                                         'syndicate_category' => 'none',
			                                         'servers' => array( array( 'server' => '',
			                                                                    'username' => '',
			                                                                    'password' => '' ) ) );
		}
		$newServerRows = get_transient( 'so_new_servers' );

		require_once( 'so-options.php' );

	}

	 // Meta box (only shows when one or more group has 'post' as the trigger...
	function syndicate_out_meta_box( $post ) {

		if ( false !== ( $syndicateOutOptions = get_option( 'so_options' ) ) ) {
			if ( isset( $syndicateOutOptions['group'] ) && is_array( $syndicateOutOptions['group'] ) ) {
				$postType = syndicate_out_get_post_type( $post );
				foreach ( $syndicateOutOptions['group'] AS $syndicationGroup) {
					if ( -2 == $syndicationGroup['category'] ) {
						add_meta_box( 'syndicateoutdiv', __( 'Syndicate Post', 'syndicate-out' ), 'syndicate_out_meta_box_content', $postType, 'side', 'default', $syndicateOutOptions );
						break;
					}
				}
			}
		}

	}

	 // Meta box content...
	function syndicate_out_meta_box_content( $post, $metabox ) {

		if ( false !== ( $syndicateOutOptions = $metabox['args'] ) ) {
			if ( isset( $syndicateOutOptions['group'] ) && is_array( $syndicateOutOptions['group'] ) ) {
				$postSoMeta = get_post_meta( $post->ID, '_so_remote_posts', true );
				if ( ! empty( $postSoMeta ) ) {
					$postSoMeta = unserialize( $postSoMeta );
					$syndicatedGroups = $postSoMeta['group'];
				}
				foreach ( $syndicateOutOptions['group'] AS $syndicationGroupKey => $syndicationGroup) {
					if ( -2 == $syndicationGroup['category'] ) {
						$groupName = ( ! empty( $syndicationGroup['name'] ) ) ? htmlentities2( $syndicationGroup['name'] ) : sprintf( __( 'Syndication Group %s', 'syndicate-out' ), number_format_i18n( ( $syndicationGroupKey + 1 ) ) );
						echo '<input type="checkbox" name="so_syndicate[group]['.htmlentities2( $syndicationGroupKey ).']" value="1"'.( ( isset( $syndicatedGroups[$syndicationGroupKey] ) && ( count( $syndicatedGroups[$syndicationGroupKey] ) ) > 0 ) ? ' checked="checked"' : '' ).' /><span style="font-weight: bold;">'.esc_html( $groupName ).'</span><br />'.PHP_EOL;
						if ( is_array( $syndicationGroup['servers' ] ) ) {
							foreach ( $syndicationGroup['servers'] AS $syndicationGroupServerKey => $syndicationGroupServer ) {
								echo '<span style="margin-left: 21px;">'.esc_html( $syndicationGroupServer['server'] ).'</span><br />'.PHP_EOL;
							}
						}
					}
				}
			}
		}

	}

 /* Post / action section. */

	 // Sanitize and organise the all settings...
	function syndicate_out_sanitize_options( $options ) {

		if ( ! isset( $options['options_version'] ) ) {

	 // Delete any groups which have been flagged for deletion...
			if ( isset( $options['deletegroup'] ) ) {

				if ( $returnOptions = get_option( 'so_options' ) ) {
					foreach ( $options['deletegroup'] AS $groupKey => $buttonValue ) {
						if ( array_key_exists( $groupKey, $returnOptions['group'] ) ) {
							unset( $returnOptions['group'][$groupKey] );
						}
					}
				} else {
					$returnOptions = array( 'options_version' => SO_OPTIONS_VERSION );
				}

				return $returnOptions;

			}

	 // Update any groups which have been changed...
			$addRowsArray = array();
			$newOptions = array( 'group' => array() );
			if ( isset( $options['group'] ) && is_array( $options['group'] ) ) {
				foreach ( $options['group'] AS $groupId => $groupOptions ) {

	 // If this group isn't flagged for deletion...
					if ( ! isset( $groupOptions['deletegroup'] ) ) {

	 // Flag new rows, if required...
						if ( isset( $groupOptions['addrowbutton'] ) && is_numeric( $groupOptions['addrow'] ) && $groupOptions['addrow'] > 0 ) {
							$addRowsArray[$groupId] = $groupOptions['addrow'];
						}

	 // Group name...
						if ( ! empty( $groupOptions['name'] ) ) {
							$newOptions['group'][$groupId]['name'] = $groupOptions['name'];
						}
						
	 // Triggers and trigger category...
						switch ( $groupOptions['trigger'] ) {
							case 'all':
								$newOptions['group'][$groupId]['category'] = -1;
							break;
							case 'post':
								$newOptions['group'][$groupId]['category'] = -2;
							break;
							case 'category':
								if ( is_numeric( $groupOptions['category'] ) ) {
									$newOptions['group'][$groupId]['category'] = $groupOptions['category'];
									break;
								}
							case 'disable': default:
								$newOptions['group'][$groupId]['category'] = 'none';
							break;
						}

	 // Transmit category...
						switch ( $groupOptions['syndicate_category'] ) {
							case 'all': case 'syndication': case 'allbut':
								$newOptions['group'][$groupId]['syndicate_category'] = $groupOptions['syndicate_category'];
							break;
							default:
								$newOptions['group'][$groupId]['syndicate_category'] = 'none';
							break;
						}

	 // Featured images...
						if ( $groupOptions['featured_image'] == 'false' ) {
							$newOptions['group'][$groupId]['featured_image'] = false;
						} else {
							$newOptions['group'][$groupId]['featured_image'] = true;
						}

	 // Servers...
						foreach ( $groupOptions['servers'] AS $serverKey => $serverDetails ) {
							if ( ! empty( $serverDetails['server'] ) ) {

								$remoteServer = trim( $serverDetails['server'] );
								if ( ( 'http://' != substr( $remoteServer, 0, 7 ) ) && ( 'https://' != substr( $remoteServer, 0, 8 ) ) ) {
									$remoteServer = 'http://'.$remoteServer;
								}
								if ( '/' != substr( $remoteServer, -1 ) ) {
									$remoteServer .= '/';
								}
								$newOptions['group'][$groupId]['servers'][$serverKey]['server'] = $remoteServer;
								$newOptions['group'][$groupId]['servers'][$serverKey]['username'] = $serverDetails['username'];
								$newOptions['group'][$groupId]['servers'][$serverKey]['password'] = $serverDetails['password'];

	 // Authentication and API version...
								if ( include_once(  ABSPATH . WPINC . '/class-IXR.php' ) ) {
									if ( include_once(  ABSPATH . WPINC . '/class-wp-http-ixr-client.php' ) ) {
										$xmlrpc = new WP_HTTP_IXR_CLIENT( $remoteServer.'xmlrpc.php' );
										$xmlrpc->query( 'wp.getOptions', array( 0, $serverDetails['username'], $serverDetails['password'], array( 'software_name', 'software_version', 'so_api' ) ) );
										$xmlrpcResponse = $xmlrpc->getResponse();
										if ( null == $xmlrpcResponse ) {
											if ( -32300 == $xmlrpc->getErrorCode() ) {
												$newOptions['group'][$groupId]['servers'][$serverKey]['authenticated'] = false;
												$newOptions['group'][$groupId]['servers'][$serverKey]['api'] = __( 'API Unavailable', 'syndicate-out' );
											} else {
												$newOptions['group'][$groupId]['servers'][$serverKey]['authenticated'] = false;
												$newOptions['group'][$groupId]['servers'][$serverKey]['api'] = __( 'Unknown', 'syndicate-out' );
											}
										} else {
											if ( isset( $xmlrpcResponse['faultString'] ) ) {
												$newOptions['group'][$groupId]['servers'][$serverKey]['authenticated'] = false;
												$newOptions['group'][$groupId]['servers'][$serverKey]['api'] = __( trim( $xmlrpcResponse['faultString'], ' .' ), 'syndicate-out' );
											} else {
	         							$newOptions['group'][$groupId]['servers'][$serverKey]['authenticated'] = true;
												if ( isset( $xmlrpcResponse['so_api'] ) ) {
													$newOptions['group'][$groupId]['servers'][$serverKey]['api'] = sprintf( __( 'Syndicate Out API v%s', 'syndicate-out' ), $xmlrpcResponse['so_api']['value'] );
												} else {
													$newOptions['group'][$groupId]['servers'][$serverKey]['api'] = $xmlrpcResponse['software_name']['value'].' '.$xmlrpcResponse['software_version']['value'];
												}
											}
										}
									}
								}

							}
						}

					}

				}
			}

	 // Set the transient relating to new server rows...
			if ( count( $addRowsArray ) > 0 ) {
				set_transient( 'so_new_servers', $addRowsArray, 5 );
			}

	 // Merge old and new options to create final return...
			if ( $returnOptions = get_option( 'so_options' ) ) {
				foreach ($newOptions['group'] AS $groupKey => $newGroupOptions) {
					$returnOptions['group'][$groupKey] = $newGroupOptions;
				}
			} else {
				$returnOptions = $newOptions;
				$returnOptions['options_version'] = SO_OPTIONS_VERSION;
			}

			return $returnOptions;
		} else {
			return $options;
		}

	}

	 // Carry out the syndication on post insert...
	function syndicate_out_post( $postId ) {

		if ( wp_is_post_revision( $postId ) && ! wp_is_post_autosave( $postId ) ) {

			if ( $soOptions = get_option( 'so_options' ) ) {
				if ( isset( $soOptions['group'] ) && is_array( $soOptions['group'] ) ) {

					$activeGroups = array();

	 // Groups activated by global settings...
					foreach ( $soOptions['group'] AS $syndicationGroupKey => $syndicationGroup ) {
						$categories = get_the_category( $postId );
						if ( 0 == count( $categories ) ) {
							if ( null != $_POST['post_category'] ) {
								$categories = $_POST['post_category'];
							}
						}
						if ( ( -1 == $syndicationGroup['category'] ) || in_array( $syndicationGroup['category'], $categories ) ) {
							$activeGroups[$syndicationGroupKey] = $syndicationGroup;
						}
					}

	 // Groups activated by per-post selection...
					if ( isset( $_POST['so_syndicate']['group'] ) && is_array( $_POST['so_syndicate']['group'] ) ) {
						foreach ( $_POST['so_syndicate']['group'] AS $groupKey => $value ) {
							if ( ( '1' == $value ) && is_int( $groupKey ) ) {
								if ( ! array_key_exists( $groupKey, $activeGroups ) ) {
									$activeGroups[$groupKey] = $soOptions['group'][$groupKey];
								}
							}
						}
					}

					if ( count( $activeGroups ) > 0 ) {

	 // Get required post information...
						$postData = get_post( $postId );
						$remotePost = array( 'post_type' => syndicate_out_get_post_type( $postData ) );
						if ( ( 'page' != $remotePost['post_type'] ) && in_array( $postData->post_status, array( 'publish', 'inherit', 'future' ) ) ) {

	 // Include the required IXR libraries...
							if ( include_once(  ABSPATH . WPINC . '/class-IXR.php' ) ) {
								if ( include_once(  ABSPATH . WPINC . '/class-wp-http-ixr-client.php' ) ) {

									if ( 'inherit' == $postData->post_status ) {
										$postMetaId = $postData->post_parent;
										$postData->post_status = get_post_status( $postMetaId );
									} else {
										$postMetaId = $postId;
									}

	 // General post related stuff...
									$syndicateElements = array( 'post_status', 'post_title', 'post_excerpt',
									                            'post_content', 'post_format', 'post_password',
									                            'comment_status', 'ping_status', 'post_date_gmt' );
									foreach ( $postData AS $dataItemKey => $dataItemContent ) {
										if ( in_array( $dataItemKey, $syndicateElements ) ) {
											$remotePost[$dataItemKey] = $dataItemContent;
										}
									}

	 // Sort out scheduled dates, etc...
									if ( isset( $remotePost['post_date_gmt'] ) ) {
										$remotePost['post_date_gmt'] = new IXR_Date( strtotime( $remotePost['post_date_gmt'] ) );
									}
									if ( 'future' == $postData->post_status ) {
										if ( $parentPostDate = get_post( $postData->post_parent )->post_date_gmt ) {
											$remotePost['post_date_gmt'] = new IXR_Date( strtotime( $parentPostDate ) );
										}
									}

	 // Custom fields...
									$postMeta = has_meta( $postMetaId );
									if ( is_array( $postMeta ) ) {
										$excludeMeta = array( '_edit_last', '_edit_lock',
										                      '_thumbnail_id', '_so_remote_posts' );
										$remotePost['custom_fields'] = array();
										foreach ( $postMeta AS $metaSingle ) {
											if ( ! in_array( $metaSingle['meta_key'], $excludeMeta ) ) {
												$remotePost['custom_fields'][] = array( 'key' => $metaSingle['meta_key'],
												                                        'value' => $metaSingle['meta_value'] );
											}
										}
									}

	// Thumbnail...
									if ( has_post_thumbnail( $postMetaId ) ) {
										$thumbnailId = get_post_thumbnail_id( $postMetaId );
										$postThumbnailPath = get_attached_file( $thumbnailId );
										$remotePostThumbnail = array( 'name' => basename( $postThumbnailPath ),
										                              'type' => get_post_mime_type( $thumbnailId ),
										                              'bits' => new IXR_Base64( file_get_contents( $postThumbnailPath ) ),
										                              'overwrite' => true );
									}

	 // Tags...
									$remotePost['terms_names'] = array();
									if ( $postTags = syndicate_out_get_tags( $postId ) ) {
										$remotePost['terms_names']['post_tag'] = array();
										foreach ( $postTags AS $postTag ) {
											$remotePost['terms_names']['post_tag'][] = $postTag->name;
										}
									}

	 // Categories...
									$groupCategoryArray = array();
									foreach ( $activeGroups AS $groupKey => $groupDetails ) {
										if ( 'none' != $groupDetails['syndicate_category'] ) {
											if ( 'syndication' == $groupDetails['syndicate_category'] && ( -1 != $syndicationGroup['category'] ) ) {
												if ( $groupDetails['category'] > 0 ) {
													$groupCategoryArray[$groupKey] = array( get_cat_name( $groupDetails['category'] ) );
												}
											} else if ( ( 'all' == $groupDetails['syndicate_category'] ) || ( 'allbut' == $groupDetails['syndicate_category'] ) || ( -1 == $syndicationGroup['category'] ) ) {
												$categories = $_POST['post_category'];
												$groupCategoryArray[$groupKey] = array();
												if (is_array($categories)) {
													foreach ( $categories AS $postCategory ) {
														if ( 0 != $postCategory ) {
															if ( ( $postCategory == $groupDetails['category'] ) && ( 'allbut' == $groupDetails['syndicate_category'] ) ) {
																continue;
															}
															$groupCategoryArray[$groupKey][] = get_cat_name( $postCategory );
														}
													}
												}
											}
										}

									}

	/**
	 * Filter post content to be syndicated before it is sent to any targets.
	 *
	 * @since 0.8.4
	 *
	 * @param array $remotePost Post data to be sent to the remote servers as an array.
	 * @param string $postId ID of post being syndicated.
	 */
									$remotePost = apply_filters( 'syndicate_post_content', $remotePost, $postId );

	/**
	 * Fires before a post is syndicated to ANY destinations.
	 *
	 * @since 0.8.4
	 *
	 * @param string $postId ID of post being syndicated.
	 */
									do_action( 'syndicate_post_before_all', $postId );

	 // Publish the post to the remote blog(s)...
									if ( false !== ( $remotePostIds = unserialize( get_post_meta( $postMetaId, '_so_remote_posts', true ) ) ) ) {
										if ( ! isset( $remotePostIds['options_version'] ) ) {
											$newRemotePostIds = array( 'options_version' => SO_OPTIONS_VERSION );
											foreach ( $remotePostIds AS $serverKey => $remotePostId ) {
												$newRemotePostIds['group'][0][$serverKey] = $remotePostId;
											}
											$remotePostIds = $newRemotePostIds;
											update_post_meta( $postMetaId, '_so_remote_posts', serialize( $remotePostIds ) );
										}
										foreach ( $remotePostIds['group'] AS $groupKey => $remoteServers ) {
											$compiledGroupPost = $remotePost;
											if ( isset( $groupCategoryArray[$groupKey] ) ) {
												$compiledGroupPost['terms_names']['category'] = $groupCategoryArray[$groupKey];
											}
											foreach ( $remoteServers AS $serverKey => $remotePostId ) {
												if ( is_numeric( $remotePostId ) ) {
													if ( isset( $soOptions['group'][$groupKey]['servers'][$serverKey] ) ) {

	/**
	 * Fires before a post is syndicated to EACH destination. Fires for both
	 * posts which will be updates and for posts which will be new posts on the
	 * remote blogs.
	 *
	 * @since 0.8.4
	 *
	 * @param string $postId ID of post being syndicated.
	 * @param string $soOptions Hostname of the server being syndicated to.
	 */
														do_action( 'syndicate_post_before_server', $postId, $soOptions['group'][$groupKey]['servers'][$serverKey]['server'] );

														$thisServerPost = syndicate_out_clean_for_remote( $soOptions['group'][$groupKey]['servers'][$serverKey]['server'], $soOptions['group'][$groupKey]['servers'][$serverKey]['username'], $soOptions['group'][$groupKey]['servers'][$serverKey]['password'], $compiledGroupPost );
														$xmlrpc = new WP_HTTP_IXR_CLIENT( $soOptions['group'][$groupKey]['servers'][$serverKey]['server'].'xmlrpc.php' );

														if ( !isset( $soOptions['group'][$groupKey]['featured_image'] ) || ( true == $soOptions['group'][$groupKey]['featured_image'] ) ) {
															if ( isset( $remotePostThumbnail ) ) {
																$xmlrpc->query( 'wp.uploadFile', 1, $soOptions['group'][$groupKey]['servers'][$serverKey]['username'], $soOptions['group'][$groupKey]['servers'][$serverKey]['password'], $remotePostThumbnail );
																$uploadMediaResponse = $xmlrpc->getResponse();
																if ( isset( $uploadMediaResponse['id'] ) ) {
																	$thisServerPost['post_thumbnail'] = $uploadMediaResponse['id'];
																}
															}
														}

														if ( isset( $thisServerPost['custom_fields'] ) ) {
															$xmlrpc->query( 'wp.getPost', array(
																0,
																$soOptions['group'][ $groupKey ]['servers'][ $serverKey ]['username'],
																$soOptions['group'][ $groupKey ]['servers'][ $serverKey ]['password'],
																$remotePostId, array(
																	'custom_fields',
																),
															));
															$oldPost = $xmlrpc->getResponse();

															$customMetaIds = array();

															foreach ( $oldPost['custom_fields'] as $oldCustomField ) {
																$customMetaIds[ $oldCustomField['key'] ] = $oldCustomField['id'];
															}

															foreach ( $thisServerPost['custom_fields'] as &$customField ) {
																if ( isset( $customMetaIds[ $customField['key'] ] ) ) {
																	$customField['id'] = $customMetaIds[ $customField['key'] ];
																} else {
																	unset( $customField['id'] );
																}
															}
														}
														$xmlrpc->query( 'wp.editPost', array( 0, $soOptions['group'][$groupKey]['servers'][$serverKey]['username'], $soOptions['group'][$groupKey]['servers'][$serverKey]['password'], $remotePostId, $thisServerPost ) );

	/**
	 * Fires after a post is syndicated to EACH destination. Fires for both
	 * posts which will be updates and for posts which will be new posts on the
	 * remote blogs.
	 *
	 * @since 0.8.4
	 *
	 * @param string $postId ID of post being syndicated.
	 * @param string $soOptions Hostname of the server being syndicated to.
	 */
														do_action( 'syndicate_post_after_server', $postId, $soOptions['group'][$groupKey]['servers'][$serverKey]['server'] );

													}
												}
											}
										}
									} else {
										$remotePostInformation = array( 'options_version' => SO_OPTIONS_VERSION );
										foreach ( $activeGroups AS $groupKey => $activeGroup ) {
											$compiledGroupPost = $remotePost;
											if ( isset( $groupCategoryArray[$groupKey] ) ) {
												$compiledGroupPost['terms_names']['category'] = $groupCategoryArray[$groupKey];
											}
											foreach ( $activeGroup['servers'] AS $serverKey => $serverDetails ) {

												do_action( 'syndicate_post_before_server', $postId, $serverDetails['server'] );

												$thisServerPost = syndicate_out_clean_for_remote( $soOptions['group'][$groupKey]['servers'][$serverKey]['server'], $soOptions['group'][$groupKey]['servers'][$serverKey]['username'], $soOptions['group'][$groupKey]['servers'][$serverKey]['password'], $compiledGroupPost );
												$xmlrpc = new WP_HTTP_IXR_CLIENT( $serverDetails['server'].'xmlrpc.php' );

												if ( !isset( $soOptions['group'][$groupKey]['featured_image'] ) || ( true == $soOptions['group'][$groupKey]['featured_image'] ) ) {
													if ( isset( $remotePostThumbnail ) ) {
														$xmlrpc->query( 'wp.uploadFile', 1, $soOptions['group'][$groupKey]['servers'][$serverKey]['username'], $soOptions['group'][$groupKey]['servers'][$serverKey]['password'], $remotePostThumbnail );
														$uploadMediaResponse = $xmlrpc->getResponse();
														if ( isset( $uploadMediaResponse['id'] ) ) {
															$thisServerPost['post_thumbnail'] = $uploadMediaResponse['id'];
														}
													}
												}

												$xmlrpc->query( 'wp.newPost', array( 0, $serverDetails['username'], $serverDetails['password'], $thisServerPost ) );
												$remotePostInformation['group'][$groupKey][$serverKey] = $xmlrpc->getResponse();

												do_action( 'syndicate_post_after_server', $postId, $serverDetails['server'] );

											}
										}
										update_post_meta( $postMetaId, '_so_remote_posts', serialize( $remotePostInformation ) );
									}

	/**
	 * Fires after a post is syndicated to ALL destinations.
	 *
	 * @since 0.8.4
	 *
	 * @param string $postId ID of post being syndicated.
	 */
									do_action( 'syndicate_post_after_all', $postId );

								}
							}

						}

					}

				}
			}

		}

	}
	
	 // Checks to see if a post should be deleted remotely when the local copy of
	 // the post is deleted...
	function syndicate_out_post_delete( $postId ) {
	
		$remotePostData = get_post_meta( $postId, '_so_remote_posts', true );
		if ( !empty( $remotePostData ) ) {
			$remotePostData = unserialize( $remotePostData );
		}
		
		
	}

	 // Returns a valid post type for the given post. Either simply the post type
	 // speficied in the post (if it's not a revision), or the parent post type...
	function syndicate_out_get_post_type( $postData ) {

		if ( 'revision' == $postData->post_type ) {
			$parentPostData = get_post( $postData->post_parent );
			if ( null !== $parentPostData ) {
				return $parentPostData->post_type;
			} else {
				return $postData->post_type;
			}
		} else {
			return $postData->post_type;
		}

	}

	 // Check the post is valid for (will be accepted by) the remote server
	 // specified, and if not strip out anything which might cause problems...
	function syndicate_out_clean_for_remote( $remoteAddress, $remoteUsername, $remotePassword, $compiledGroupPost ) {

		if ( ( 'revision' == $compiledGroupPost['post_type'] ) ) {
			$compiledGroupPost['post_type'] = 'post';
		}

		return $compiledGroupPost;

	}

	 // Get a list of tags for this post...
	function syndicate_out_get_tags( $postId ) {

		$terms = get_object_term_cache( $postId, 'post_tag' );
		if ( false === $terms ) {
			$terms = wp_get_object_terms( $postId, 'post_tag' );
		}

		if ( empty( $terms ) ) {
			return false;
		}

		return $terms;

	}

 /* Maintenance section. */

	 // Activation function. Unused as of version 0.8...
	function syndicate_out_activate() {

		// Currently not used.

	}

	 // Updates the any old versions of settings to the latest version...
	function syndicate_out_update_settings( $currentSettings ) {

		$newSettings = $currentSettings;
		switch ( $currentSettings['options_version'] ) {
			case 0: case 1: # Upgrades version 0 or 1 to version 2
				unset( $newSettings['options_version'] );
				$newSettings['group'][0] = $newSettings;
			case 2: # Upgrades from version 2 to version 3; adds authenticated and api
				if ( isset( $newSettings['group'] ) && is_array( $newSettings['group'] ) ) {
					foreach ( $newSettings['group'] AS $groupId => $groupArray ) {
						if ( isset( $groupArray['servers'] ) && is_array( $groupArray['servers'] ) ) {
							foreach ( $groupArray['servers'] AS $serverId => $serverDetails ) {
								if ( ! isset( $serverDetails['authenticated'] ) ) {
									$newSettings['group'][$groupId]['servers'][$serverId]['authenticated'] = null;
								}
								if ( ! isset( $serverDetails['api'] ) ) {
									$newSettings['group'][$groupId]['servers'][$serverId]['api'] = null;
								}
							}
						}
					}
				}
			break;
		}
		$newSettings['options_version'] = SO_OPTIONS_VERSION;
		update_option( 'so_options', $newSettings );

	}

	 // Check the plugin options version and update if required...
	if ( $currentSettings = get_option( 'so_options' ) ) {
		if ( isset( $currentSettings['options_version'] ) && ( $currentSettings['options_version'] < SO_OPTIONS_VERSION ) ) {
			syndicate_out_update_settings( $currentSettings );
		}
	}

}
