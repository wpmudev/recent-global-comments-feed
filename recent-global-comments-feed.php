<?php
/*
Plugin Name: Recent Global Comments Feed
Plugin URI: http://premium.wpmudev.org/project/recent-global-comments-feed
Description: An RSS feed of all the latest comments from across your entire site.
Author: Paul Menard (Incsub), Ivan Shaovchev & Andrew Billits (Incsub)
Author URI: http://ivan.sh
Version: 1.0.6.2
Network: true
WDP ID: 71
*/ 

/* 
Copyright 2007-2011 Incsub (http://incsub.com)

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

// Support for WPMU DEV Dashboard plugin
include_once( dirname(__FILE__) . '/lib/dash-notices/wpmudev-dash-notification.php');

//------------------------------------------------------------------------//
//---Config---------------------------------------------------------------//
//------------------------------------------------------------------------//

$recent_global_comments_feed_widget_main_blog_only = 'yes'; //Either 'yes' or 'no'

//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//

function recent_global_comments_feed() {
    global $wpdb, $current_site;
    $number = ( empty( $_GET['number'] ) ) ? 25 : intval($_GET['number']);

    $query = $wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "site_comments WHERE site_id = %d AND blog_public = '1' AND comment_approved = '1' AND comment_type != 'pingback' ORDER BY comment_date_stamp DESC LIMIT %d", $current_site->id, $number);
    $comments = $wpdb->get_results( $query, ARRAY_A );

    if ( count( $comments ) > 0 ) {
        $last_published_post_date_time = $wpdb->get_var($wpdb->prepare("SELECT comment_date_gmt FROM " . $wpdb->base_prefix . "site_comments WHERE site_id = %d AND blog_public = %s AND comment_approved = %s AND comment_type != %s ORDER BY comment_date_stamp DESC LIMIT %d",  $current_site->id, '1', '1', 'pingback', 1));
    } else {
        $last_published_post_date_time = time();
    }
    
    header( 'HTTP/1.0 200 OK' );
    header( 'Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
    $more = 1;

    echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

    <rss version="2.0"
        xmlns:content="http://purl.org/rss/1.0/modules/content/"
        xmlns:wfw="http://wellformedweb.org/CommentAPI/"
        xmlns:dc="http://purl.org/dc/elements/1.1/"
        xmlns:atom="http://www.w3.org/2005/Atom"
        xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
        xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
        <?php do_action('rss2_ns'); ?>
    >

    <channel>
        <title><?php bloginfo_rss('name'); ?> <?php _e('Comments'); ?></title>
        <atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
        <link><?php bloginfo_rss('url') ?></link>
        <description><?php bloginfo_rss("description") ?></description>
        <pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', $last_published_post_date_time, false); ?></pubDate>
        <?php the_generator( 'rss2' ); ?>
        <language><?php bloginfo_rss( 'language' ); ?></language>
        <?php
        if ( count( $comments ) > 0 ) {
            foreach ($comments as $comment) {
                $post_title = $wpdb->get_var($wpdb->prepare("SELECT post_title FROM " . $wpdb->base_prefix . $comment['blog_id'] . "_posts WHERE ID = %d", $comment['comment_post_id']));
                if ( !empty( $comment['comment_author_user_id'] ) && $comment['comment_author_user_id'] > 0 ) {
                    $author_display_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM " . $wpdb->base_prefix . "users WHERE ID = %d", $comment['comment_author_user_id']));
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
        ?>
    </channel>
    </rss>
    <?php
}
add_action( 'do_feed_recent-global-comments', 'recent_global_comments_feed' );

/**
 * Custom rewrite rules for the feed
 *
 * @param <type> $wp_rewrite
 * @return void
 */
function recent_global_comments_feed_rewrite( $wp_rewrite ) {
    $feed_rules = array(
        'feed/(.+)' => 'index.php?feed=' . $wp_rewrite->preg_index(1),
        '(.+).xml'  => 'index.php?feed='. $wp_rewrite->preg_index(1)
    );
    $wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;
}
add_filter( 'generate_rewrite_rules', 'recent_global_comments_feed_rewrite' );

/**
 * Widget
 * @global <type> $wpdb
 * @global <type> $recent_global_comments_feed_widget_main_blog_only
 * @return <type> 
 */
function widget_recent_global_comments_feed_init() {
	global $wpdb, $recent_global_comments_feed_widget_main_blog_only;

	// Check for the required API functions
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;

	// This saves options and prints the widget's config form.
	function widget_recent_global_comments_feed_control() {
		global $wpdb;
		$options = $newoptions = get_option('widget_recent_global_comments_feed');
		if ( isset( $_POST['recent-global-comments-feed-submit'] ) ) {
			$newoptions['recent-global-comments-feed-title'] 		= sanitize_text_field($_POST['recent-global-comments-feed-title']);
			$newoptions['recent-global-comments-feed-rss-image'] 	= esc_url($_POST['recent-global-comments-feed-rss-image']);
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_recent_global_comments_feed', $options);
		}
		if ( empty( $options['recent-global-comments-feed-title'] ) ) {
			$options['recent-global-comments-feed-title'] = __('Site Comments Feed');
		}
		if ( empty( $options['recent-global-comments-feed-rss-image'] ) ) {
			$options['recent-global-comments-feed-rss-image'] = 'show';
		}
        ?>
        <div style="text-align:left">
            <label for="recent-global-comments-feed-title" style="line-height:35px;display:block;"><?php _e('Title', 'widgets'); ?>:<br />
                <input class="widefat" id="recent-global-comments-feed-title" name="recent-global-comments-feed-title" value="<?php echo $options['recent-global-comments-feed-title']; ?>" type="text" style="width:95%;">
            </label>
            <label for="recent-global-comments-feed-rss-image" style="line-height:35px;display:block;"><?php _e('RSS Image', 'widgets'); ?>:<br />
                <select name="recent-global-comments-feed-rss-image" id="recent-global-comments-feed-rss-image" style="width:95%;">
                    <option value="show" <?php if ( isset( $options['recent-global-comments-feed-rss-image'] ) && $options['recent-global-comments-feed-rss-image'] == 'show'){ echo 'selected="selected"'; } ?> ><?php _e('Show'); ?></option>
                    <option value="hide" <?php if ( isset( $options['recent-global-comments-feed-rss-image'] ) && $options['recent-global-comments-feed-rss-image'] == 'hide'){ echo 'selected="selected"'; } ?> ><?php _e('Hide'); ?></option>
                </select>
            </label>
            <input type="hidden" name="recent-global-comments-feed-submit" id="recent-global-comments-feed-submit" value="1" />
        </div>
	<?php
	}
    // This prints the widget
	function widget_recent_global_comments_feed($args) {
		global $wpdb, $current_site;
		extract($args);
		$defaults = array('count' => 10, 'username' => 'wordpress');
		$options = (array) get_option('widget_recent_global_comments_feed');

		foreach ( $defaults as $key => $value )
			if ( !isset($options[$key]) )
				$options[$key] = $defaults[$key];
		?>

		<?php echo $before_widget; ?>
			<?php
			if ( $options['recent-global-comments-feed-rss-image'] == 'hide' ) {
	            echo $before_title . '<a href="http://' . $current_site->domain . $current_site->path . '?feed=recent-global-comments" >' . __($options['recent-global-comments-feed-title']) . '</a>' . $after_title;
			} else {
	            echo $before_title . '<a href="http://' . $current_site->domain . $current_site->path . '?feed=recent-global-comments" ><img src="http://' . $current_site->domain . $current_site->path . 'wp-includes/images/rss.png" /> ' . __($options['recent-global-comments-feed-title']) . '</a>' . $after_title;
			}
			?>
		<?php echo $after_widget; ?>
        <?php
	}
	// Tell Dynamic Sidebar about our new widget and its control
	if ( $recent_global_comments_feed_widget_main_blog_only == 'yes' ) {
		if ( $wpdb->blogid == 1 ) {
            wp_register_sidebar_widget( 'recent_global_comments_feed', __( 'Recent Global Comments Feed', 'recent_global_comments_feed' ), 'widget_recent_global_comments_feed' );
            wp_register_widget_control( 'recent_global_comments_feed', __( 'Recent Global Comments Feed', 'recent_global_comments_feed' ), 'widget_recent_global_comments_feed_control' );
		}
	} else {
        wp_register_sidebar_widget( 'recent_global_comments_feed', __( 'Recent Global Comments Feed', 'recent_global_comments_feed' ), 'widget_recent_global_comments_feed' );
        wp_register_widget_control( 'recent_global_comments_feed', __( 'Recent Global Comments Feed', 'recent_global_comments_feed' ), 'widget_recent_global_comments_feed_control' );
	}
}
add_action('widgets_init', 'widget_recent_global_comments_feed_init');
