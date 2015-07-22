<?php
/**
 * Sample Feed
 **/

//アイキャッチ
function get_enclosure_rss($post_id){
	$images = wp_get_attachment_url(get_post_thumbnail_id($post_id));
	$the_list = '';
	if (empty($images)) {
		return $the_list;
	}
	$data = preg_split( '/wp-content/', $images );
	$size = file_exists( WP_CONTENT_DIR . $data[1] ) ? filesize( WP_CONTENT_DIR . $data[1] ) : '';
	$caption = get_post( get_post_thumbnail_id($post_id) )->post_excerpt;
	$the_list .= sprintf('<enclosure url="%s" type="image/jpeg" length="%s" yj:caption="%s" />'."\n", esc_attr( $images ), esc_attr( $size ), esc_attr ( $caption ) );
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
			$list_item = '<yj:related><yj:link yj:url="%s"><![CDATA[%s]]></yj:link></yj:related>'."\n";
			$the_list .= sprintf(
				$list_item,
				esc_attr(get_permalink($relatedpost['ID'])),
				esc_attr(get_the_title($relatedpost['ID'])));
		}
	} elseif ( function_exists('st_get_related_posts') ) {
		$relatedposts = st_get_related_posts('post_id='.intval($post_id).'&number=5&format=array');
		if ( !is_array($relatedposts) || empty($relatedposts))
			return;

		$the_list = '';
		foreach ( $relatedposts as $relatedpost ) {
			$list_item = '<yj:related><yj:link yj:url="%s"><![CDATA[%s]]></yj:link></yj:related>'."\n";
			$the_list .= sprintf(
				$list_item,
				esc_attr(get_permalink($relatedpost->ID)),
				esc_attr($relatedpost->post_title));
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

function update_link( $matches ) {
	return '<img' . $matches[1] . 'src="' . $matches[2] . '" ' . $matches[3].'><cite>'.get_the_title().'</cite>';
}

// ここからフィード
nocache_headers();
header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'."\n"; ?>
<rss version="2.0" xmlns:yj="http://cmspf.yahoo.co.jp/rss" yj:version="1.0">
  <channel>
    <title><?php bloginfo_rss('name'); ?></title>
    <link><?php bloginfo_rss('url') ?></link>
    <description><?php bloginfo_rss("description") ?></description>
    <lastBuildDate><?php echo date_iso8601(get_lastpostmodified('blog')); ?></lastBuildDate>
<?php
while (have_posts()) :
	the_post();
	$post_id = intval(get_the_ID());
	$pub_date = get_post_time('Y-m-d H:i:s', false);
	$mod_date = get_post_modified_time('Y-m-d H:i:s', false);
	$content = get_the_content_feed('rss2');
	$content = preg_replace_callback( '#<img([^>]*)src=["\']([^"\']+)["\']([^>]*)>#i', 'update_link', $content, -1 );
	
	$allowed_html = array(
		'br' => array(),
		'p' => array(),
		'h2' => array(),
		'h3' => array(),
		'cite' => array(),
		'blockquote' => array(),
		'img' => array(
			'width' => array(),
			'height' => array(),
			'src' => array(),
			'alt' => array(),
			'caption' => array(),
		),
		'strong' => array(),
		'video' => array(
			'src' => array(),
		),
		'iframe' => array(
			'id' => array(),
			'scrolling' => array(),
			'frameborder' => array(),
			'allowtransparency' => array(),
			'style' => array(),
			'src' => array(),
			'width' => array(),
			'height' => array(),
		),
	);

	$content = wp_kses(
			$content,
			$allowed_html,
			array('http', 'https')
		);
?>
    <item>
      <title><![CDATA[<?php the_title_rss() ?>]]></title>
      <link><?php the_permalink_rss() ?></link>
      <author><?php the_author(); ?></author>
      <?php yahoo_netarica\Custom_Feed::get_instance()->item_category(); ?>
      <guid><?php the_ID(); ?></guid>
      <pubDate><?php echo date_iso8601($mod_date); ?></pubDate>
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
