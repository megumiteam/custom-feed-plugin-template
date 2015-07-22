<?php
/*
Plugin Name: yahooネタりか配信フィード。
Version: 1.0
Description: エンドポイントは/feed?type=yahoo-netarica
Author: Digitalcube
Author URI: https://digitalcube.jp
Plugin URI: https://digitalcube.jp
Text Domain: custom-feed
Domain Path: /languages
*/

namespace yahoo_netarica;

Custom_Feed::get_instance();
class Custom_Feed {
	private $feed_name;
	private $categories = array(
							'geinou'        => '芸能',
							'topic'         => '時事ネタ',
							'trend'         => '最新トレンド',
							'love'          => '恋愛',
							'beauty'        => '美容',
							'gourmet'       => 'グルメ',
							'travel'        => '旅行',
							'entertainment' => '映画・音楽',
							'anime'         => 'アニメ',
							'omoshiro'      => 'おもしろネタ',
							'neta'          => '雑学・裏ワザ',
							);
	
	private static $instance = null;

	private final function __construct() {
		$this->feed_name    = __NAMESPACE__;
		add_action( 'do_feed_rss2'       , array( $this, 'do_feed_rss2' ), 1 );
		add_action( 'save_post'          , array( $this, 'save_post' ) );
		add_action( 'pre_get_posts'      , array( $this, 'exclude_category' ) );
		add_filter( 'the_content'        , array( $this, 'strip_related_post' ) );
		add_action( 'add_meta_boxes', function(){
				add_meta_box(
					'yahoo_feed' . $this->feed_name,
					'Yahoo ねたりか Category',
					array( $this, 'add_meta_boxes' ),
					'post',
					'side'
				);
			} );
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

	public function do_feed_rss2() {
		if ( isset($_GET['type']) && $_GET['type'] == $this->feed_name) {
			load_template( dirname(__FILE__) . '/feed-rss2.php' );
			exit;
		}
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
	
	public function item_category() {
		if ( get_post_meta( get_the_ID(), '_yahoo_feed_category_' . $this->feed_name, true ) ) {
			return '<category>'.intval( get_post_meta( get_the_ID(), '_yahoo_feed_category_' . $this->feed_name, true ) ).'</category>';
		} else {
			return '';
		}
	}

	public function add_meta_boxes( $post ) {
			wp_nonce_field( 'yahoo_netarica_category_' . $this->feed_name, 'yahoo_netarica_category_nonce_' . $this->feed_name );
			$value = get_post_meta( $post->ID, '_yahoo_netarica_category_' . $this->feed_name, true );

			if ( empty( $value ) ) {
				//$value = '';
			}
			echo '<ul>';
			foreach ( $this->categories as $key => $cat ) {
				printf(
					'<li><label><input type="radio" name="%1$s" value="%2$s" %4$s /> %3$s</label></li>',
					esc_attr('yahoo_netarica_category_' . $this->feed_name),
					esc_attr($key),
					esc_html($cat),
					( $value === $key ) ? 'checked="checked"' : ''
				);
			}
			echo '</ul>';
	}

	public function save_post( $post_id ) {

		if ( ! isset( $_POST['yahoo_netarica_category_nonce_' . $this->feed_name] ) ) {
			return;
		}
		
		if ( ! wp_verify_nonce( $_POST['yahoo_netarica_category_nonce_' . $this->feed_name], 'yahoo_netarica_category_' . $this->feed_name ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['yahoo_netarica_category_' . $this->feed_name] ) ) {
			return;
		}

		if ( array_key_exists( $_POST['yahoo_netarica_category_' . $this->feed_name], $this->categories ) ) {
			update_post_meta( $post_id, '_yahoo_netarica_category_' . $this->feed_name, $_POST['yahoo_netarica_category_' . $this->feed_name] );
		}
	}
}
