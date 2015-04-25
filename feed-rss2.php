<?php
/**
 * Sample Feed
 **/

//アイキャッチ
function get_enclosure_rss($post_id){
	$images = wp_get_attachment_url(get_post_thumbnail_id($post_id));
	$the_list = '';
	if (empty($images))
		return;
	$the_list .= sprintf('<enclosure url="%s" type="image/jpeg" >'."\n", esc_attr( $images ) );
	return $the_list;
}

// 関連記事
function get_related_post_rss($post_id){
	$the_list = '';

	if ( function_exists('sirp_get_related_posts_id') ) {

		$relatedposts = sirp_get_related_posts_id(5);
		if ( !is_array($relatedposts) || empty($relatedposts))
			return;

		$the_list = '';
		foreach ( $relatedposts as $relatedpost ) {
			$list_item = '<relatetd><title><![CDATA[%s]]></title><url>%s</url></pol:relation>'."\n";
			$the_list .= sprintf(
				$list_item,
				esc_attr(get_the_title($relatedpost['ID'])),
				esc_attr(get_permalink($relatedpost['ID']))
				);

		}
	} elseif ( function_exists('st_get_related_posts') ) {
		$relatedposts = st_get_related_posts('post_id='.intval($post_id).'&number=5&format=array');
		if ( !is_array($relatedposts) || empty($relatedposts))
			return;

		$the_list = '';
		foreach ( $relatedposts as $relatedpost ) {
			$list_item = '<relatetd><title><![CDATA[%s]]></title><url>%s</url></pol:relation>'."\n";
			$the_list .= sprintf(
				$list_item,
				esc_attr($relatedpost->post_title),
				esc_attr(get_permalink($relatedpost->ID))
				);
		}
	} else {
		return;
	}
	return $the_list;
}

//時刻の変換
function date_iso8601($time) {
	$date = sprintf(
		'%1$sT%2$s',
		mysql2date('Y-m-d', $time, false),
		mysql2date('H:i:s+09:00', $time, false)
		);
	return $date;
}

//カテゴリ
function get_category_rss($post_id) {
	$categories = get_the_category($post_id);
	$the_list = '';
	$cat_names = array();

	if ( !empty($categories) ) foreach ( (array) $categories as $category ) {
		$cat_names[$category->term_id] = sanitize_term_field('name', $category->name, $category->term_id, 'category', 'raw');
	}
	unset($categories);

	$cat_names = array_unique($cat_names);
	foreach ( $cat_names as $cat_id => $cat_name ) {
		$the_list .= sprintf('<category><![CDATA[%s]]></category>'."\n", esc_html( $cat_name ));
		break;
	}
	unset($cat_names);

	return $the_list;
}
// ここからフィード
nocache_headers();
header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'."\n"; ?>
<rss version="2.0" 
xmlns:content="http://purl.org/rss/1.0/modules/content/" 
xmlns:wfw="http://wellformedweb.org/CommentAPI/" 
xmlns:dc="http://purl.org/dc/elements/1.1/" 
xmlns:atom="http://www.w3.org/2005/Atom" 
xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" 
xmlns:slash="http://purl.org/rss/1.0/modules/slash/">
  <channel>
    <language>ja</language>
    <title><?php bloginfo_rss('name'); ?></title>
    <link><?php bloginfo_rss('url') ?></link>
    <description><?php bloginfo_rss("description") ?></description>
    <copyright>Copyright</copyright>
    <lastBuildDate><?php echo date_iso8601(get_lastpostmodified('blog')); ?></lastBuildDate>
<?php
while (have_posts()) :
	the_post();
	$post_id = intval(get_the_ID());
	$pub_date = get_post_time('Y-m-d H:i:s', false);
	$mod_date = get_post_modified_time('Y-m-d H:i:s', false);
	$content = get_the_content_feed('rss2');
?>
    <item>
      <title><![CDATA[<?php the_title_rss() ?>]]></title>
      <link><?php the_permalink_rss() ?></link>
      <pubDate><?php echo date_iso8601($pub_date); ?></pubDate>
      <lastPubDate><?php echo date_iso8601($mod_date); ?></lastPubDate>
      <status><?php echo customfeed\Custom_Feed::get_instance()->get_status($post_id); ?></status>
      <revision><?php echo customfeed\Custom_Feed::get_instance()->get_revision($post_id); ?></revision>
      <description><![CDATA[<?php echo $content; ?>]]></description>
	  <?php echo get_related_post_rss($post_id); ?>
	  <?php echo get_enclosure_rss($post_id); ?>
    </item>
<?php endwhile ; ?>
<!-- Remove -->
<?php
//ここから削除フィード
$args = array(
	'post_type' => 'post',
	'posts_per_page' => -1,
	'post_status' => array('trash', 'private')
	);
$remove_post = get_posts( $args );
if ( $remove_post ) {
	global $post;
	foreach ( $remove_post as $post ) {
		setup_postdata( $post );
		$post_id = intval(get_the_ID());
		$pub_date = get_post_time('Y-m-d H:i:s', false);
		?>
    <item>
      <guid><?php echo $post_id; ?></guid>
      <link><?php the_permalink_rss() ?></link>
      <pubDate><?php echo date_iso8601($pub_date); ?></pubDate>
      <status><?php echo customfeed\Custom_Feed::get_instance()->get_status($post_id); ?></status>
    </item>
	<?php
	}
	wp_reset_query();
} ?>
  </channel>
</rss>
