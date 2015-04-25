<?php
/*
Plugin Name: Feed Plugin Template
Version: 1.0
Description: 配信用フィードプラグイン用のテンプレート
Author: Digitalcube
Author URI: https://digitalcube.jp
Plugin URI: https://digitalcube.jp
Text Domain: custom-feed
Domain Path: /languages
*/

namespace customfeed;

Custom_Feed::get_instance();
register_activation_hook( __FILE__, function(){
	Custom_Feed::get_instance()->init();
	flush_rewrite_rules();
} );

class Custom_Feed {
	private $feed_name    = '';
	private $revision_key = '';
	private $revision_first_value = 1;
	private $status_key   = '';
	private $status       = array(
								'create' => 1,
								'update' => 2,
								'delete' => 3
								);
	
	private static $instance = null;

	private final function __construct() {
		$this->feed_name    = __NAMESPACE__;
		$this->revision_key = '_' . $this->feed_name . '_revision_id';
		$this->status_key   = '_' . $this->feed_name . '_feed_status';
		add_action( 'init'               , array( $this, 'init' ) );
		add_action( 'publish_post'       , array( $this, 'publish_post_revision' ) );
		add_action( 'publish_post'       , array( $this, 'publish_post_status' ) );
		add_action( 'publish_to_publish' , array( $this, 'publish_post_revision' ) );
		add_action( 'publish_to_publish' , array( $this, 'publish_post_status' ) );
		add_action( 'save_post'          , array( $this, 'save_post' ), 10, 2 );
		add_action( 'wp_trash_post'      , array( $this, 'trash_feed_status' ) );
		add_action( 'pre_get_posts'      , array( $this, 'exclude_category' ) );
		add_filter( 'the_content'        , array( $this, 'strip_related_post' ) );
	} 

	private final function __clone() {}

	public static function get_instance() {
		if(is_null(self::$instance)) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function get_property($name){
		return $this->$name;
	}

	public function init() {
		add_feed( $this->feed_name, function(){
			load_template( dirname(__FILE__) . '/feed-rss2.php' );
		});
	}

	public function publish_post_revision( $post_id ) {
		$revision = get_post_meta( $post_id, $this->revision_key, true );
		if ( $revision === '' ) {
			update_post_meta( $post_id, $this->revision_key, $this->revision_first_value );
		} else {
			$revision = intval($revision);
			update_post_meta( $post_id, $this->revision_key, ++$revision );
		}
	}
	
	public function publish_post_status( $post_id ) {
		$revision = get_post_meta( $post_id, $this->status_key, true );
		if ( $revision === '' ) {
			update_post_meta( $post_id, $this->status_key, $this->status['create'] );
		} else {
			update_post_meta( $post_id, $this->status_key, $this->status['update'] );
		}
	}

	public function save_post( $post_id, $post ) {
		if ( $post->post_status === 'private' ) {
			update_post_meta( $post_id, $this->status_key, $this->status['delete'] );
		}
	}

	public function trash_feed_status( $post_id ) {
		update_post_meta($post_id, $this->status_key, $this->status['delete']);
	}

	public function exclude_category( $query ) {
		if ( $query->is_main_query() && $query->is_feed( $this->feed_name ) ) {
			$cat = get_category_by_slug('pr');
			if ( $cat ) {
				$query->set( 'category__not_in', array($cat->term_id) );
			}
		}
	}

	public function strip_related_post( $content ) {
		if ( !is_admin() && is_feed( $this->feed_name ) ) {
			$content = trim(preg_replace(
				'/^(.*)<p>【関連記事】.*$/ims' ,
				'$1',
				$content
			));
			$content = trim(preg_replace(
				'/^(.*)<strong>【関連記事】.*$/ims' ,
				'$1',
				$content
			));
			$content = trim(preg_replace(
				'/^(.*)【関連記事】.*$/ims' ,
				'$1',
				$content
			));
		}
		return $content;
	}

	public function get_status($post_id) {
		return get_post_meta( $post_id, $this->revision_key, true );
	}

	public function get_revision($post_id) {
		$revision = get_post_meta( $post_id, $this->status_key, true );
		if ( $revision === '' ) {
			$revision = $this->revision_first_value;
		}
		return $revision;
	}
}
