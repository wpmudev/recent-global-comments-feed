<?php

/*
Plugin Name: Recent Global Comments Feed
Plugin URI: 
Description: RSS2 feeds
Author: Andrew Billits (Incsub)
Version: 1.0.5
Author URI:
*/ 

/* 
Copyright 2007-2009 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require_once('../wp-load.php');

//------------------------------------------------------------------------//
//---Config---------------------------------------------------------------//
//------------------------------------------------------------------------//
$number = $_GET['number'];
if ( empty( $number ) ) {
	$number = '25';
}
//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//
$query = "SELECT * FROM " . $wpdb->base_prefix . "site_comments WHERE site_id = '" . $current_site->id . "' AND blog_public = '1' AND comment_approved = '1' AND comment_type != 'pingback' ORDER BY comment_date_stamp DESC LIMIT " . $number;
$comments = $wpdb->get_results( $query, ARRAY_A );

if ( count( $comments ) > 0 ) {
	$last_published_post_date_time = $wpdb->get_var("SELECT comment_date_gmt FROM " . $wpdb->base_prefix . "site_comments WHERE site_id = '" . $current_site->id . "' AND blog_public = '1' AND comment_approved = '1' AND comment_type != 'pingback' ORDER BY comment_date_stamp DESC LIMIT 1");
} else {
	$last_published_post_date_time = time();
}

header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
$more = 1;


echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?' . '>';
?>
<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
>

<channel>
	<title><?php bloginfo_rss('name'); ?> <?php _e('Comments'); ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php bloginfo_rss("description") ?></description>
	<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', $last_published_post_date_time, false); ?></pubDate>
	<?php the_generator( 'rss2' ); ?>
	<language><?php echo get_option('rss_language'); ?></language>
    <?php
	//--------------------------------------------------------------------//
	if ( count( $comments ) > 0 ) {
		foreach ($comments as $comment) {
			$post_title = $wpdb->get_var("SELECT post_title FROM " . $wpdb->base_prefix . $comment['blog_id'] . "_posts WHERE ID = '" . $comment['comment_post_id'] . "'");
			if ( !empty( $comment['comment_author_user_id'] ) && $comment['comment_author_user_id'] > 0 ) {
				$author_display_name = $wpdb->get_var("SELECT display_name FROM " . $wpdb->base_prefix . "users WHERE ID = '" . $comment['comment_author_user_id'] . "'");
			}
			if ( !empty( $author_user_login ) ) {
				$comment_author = $author_display_name;
			} else {
				$comment_author = $comment['comment_author_email'];
			}
			?>
			<item>
				<title><?php _e('Comments on'); ?>: <?php echo stripslashes( $post_title ); ?></title>
				<link><?php echo $comment['comment_post_permalink']; ?>#comment-<?php echo $comment['comment_id']; ?></link>

                <dc:creator><?php echo $comment['comment_author']; ?></dc:creator>
				<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', $comment['comment_date_gmt'], false); ?></pubDate>
                
                <guid isPermaLink="false"><?php echo $comment['comment_post_permalink']; ?>#comment-<?php echo $comment['comment_id']; ?></guid>
                <description><![CDATA[<?php echo stripslashes( strip_tags( $comment['comment_content'] ) ); ?>]]></description>
			</item>
			<?php
		}
	}
	//--------------------------------------------------------------------//
	?>
</channel>
</rss>
<?php
//------------------------------------------------------------------------//
?>
