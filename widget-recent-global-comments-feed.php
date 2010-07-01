<?php
/*
Plugin Name: Recent Comments Feed Widget
Description:
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

//------------------------------------------------------------------------//
//---Config---------------------------------------------------------------//
//------------------------------------------------------------------------//
$recent_global_comments_feed_widget_main_blog_only = 'yes'; //Either 'yes' or 'no'
//------------------------------------------------------------------------//
//---Hook-----------------------------------------------------------------//
//------------------------------------------------------------------------//

//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//
function widget_recent_global_comments_feed_init() {
	global $wpdb, $recent_global_comments_feed_widget_main_blog_only;
		
	// Check for the required API functions
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;

	// This saves options and prints the widget's config form.
	function widget_recent_global_comments_feed_control() {
		global $wpdb;
		$options = $newoptions = get_option('widget_recent_global_comments_feed');
		if ( $_POST['recent-global-comments-feed-submit'] ) {
			$newoptions['recent-global-comments-feed-title'] = $_POST['recent-global-comments-feed-title'];
			$newoptions['recent-global-comments-feed-rss-image'] = $_POST['recent-global-comments-feed-rss-image'];
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
                </select>
                </label>
				<label for="recent-global-comments-feed-rss-image" style="line-height:35px;display:block;"><?php _e('RSS Image', 'widgets'); ?>:<br />
                <select name="recent-global-comments-feed-rss-image" id="recent-global-comments-feed-rss-image" style="width:95%;">
                <option value="show" <?php if ($options['recent-global-comments-feed-rss-image'] == 'show'){ echo 'selected="selected"'; } ?> ><?php _e('Show'); ?></option>
                <option value="hide" <?php if ($options['recent-global-comments-feed-rss-image'] == 'hide'){ echo 'selected="selected"'; } ?> ><?php _e('Hide'); ?></option>
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
	            echo $before_title . '<a href="http://' . $current_site->domain . $current_site->path . 'wp-content/recent-global-comments-feed.php" >' . __($options['recent-global-comments-feed-title']) . '</a>' . $after_title;
			} else {
	            echo $before_title . '<a href="http://' . $current_site->domain . $current_site->path . 'wp-content/recent-global-comments-feed.php" ><img src="http://' . $current_site->domain . $current_site->path . 'wp-includes/images/rss.png" /> ' . __($options['recent-global-comments-feed-title']) . '</a>' . $after_title;
			}
			?>
		<?php echo $after_widget; ?>
<?php
	}
	// Tell Dynamic Sidebar about our new widget and its control
	if ( $recent_global_comments_feed_widget_main_blog_only == 'yes' ) {
		if ( $wpdb->blogid == 1 ) {
			register_sidebar_widget(array(__('Recent Global Comments Feed'), 'widgets'), 'widget_recent_global_comments_feed');
			register_widget_control(array(__('Recent Global Comments Feed'), 'widgets'), 'widget_recent_global_comments_feed_control');
		}
	} else {
		register_sidebar_widget(array(__('Recent Global Comments Feed'), 'widgets'), 'widget_recent_global_comments_feed');
		register_widget_control(array(__('Recent Global Comments Feed'), 'widgets'), 'widget_recent_global_comments_feed_control');
	}
}

add_action('widgets_init', 'widget_recent_global_comments_feed_init');

?>