<?php
namespace yahoo_netarica;

class Custom_FeedTest extends \WP_UnitTestCase {
	private $feed;

	public function setUp() {
		parent::setUp();
		$this->feed = Custom_Feed::get_instance();
		
		global $wp_rewrite;

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure('/archives/%post_id%');
	}

    /**
     * @test
     * フィードのページヘアクセスするとis_feedが返るかテスト
     */
	function is_rewrite() {
		$this->factory->post->create();
		$this->go_to( '/feed?type=' . $this->feed->get_property('feed_name') );

		$this->assertQueryTrue( 'is_feed' );
	}

    /**
     * @test
     * 記事内の【関連記事】以下を削除するテスト
     */
	function delete_related_post() {
		global $post;
		global $wp_query;
		$wp_query->is_feed = true;
		$wp_query->query_vars['feed']  = $this->feed->get_property('feed_name');

		$content = '<p>pretext</p>';
		$content .= '<p>【関連記事】</p>';
		$content .= '<p>aftertext</p>';

		$args = array( 'post_content' => $content );
		$post_id = $this->factory->post->create( $args );
		$post = get_post( $post_id );
		setup_postdata( $post );
		
		$this->expectOutputString( '<p>pretext</p>' );
		the_content();
	}

    /**
     * @test
     * PRカテゴリが配信されていないかのテスト
     */
	function strip_pr_category() {
		$cat_id  = $this->factory->category->create( array('slug' => 'pr') );
		$post_ids = $this->factory->post->create_many( 5 );
		$this->factory->post->create_many( 5, array( 'post_category' => array( $cat_id ) ) );	
		$this->go_to( '/feed?type=' . $this->feed->get_property('feed_name') );
		
		$roop_post_id = array();
		while( have_posts() ) {
			the_post();
			$roop_post_id[] = get_the_ID();
		}

		$this->assertEquals(array_multisort($post_ids), array_multisort($roop_post_id));
	}

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * feed-rss2.phpにてPHPエラーが発生していないかテスト
     */
	 function error_check() {
	 	$user_id  = $this->factory->user->create();
		$post_ids = $this->factory->post->create_many( 5, array( 'post_author' =>  $user_id) );
		
		$this->go_to( '/feed?type=' . $this->feed->get_property('feed_name') );
		
		require_once( dirname(__FILE__) . '/../feed-rss2.php' );
	 }

}
