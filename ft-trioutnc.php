<?php
/*
Plugin Name: FT TriOutNC
Plugin URI: http://fullthrottledevelopment.com/plugins/ft-trioutnc
Description: Currently allows you to place most recent TriOutNC checkin in your WordPress sidebar.
Version: 0.1
Author: Glenn Ansley
Author URI: http://gravatar.com/profiles/glennansley

Copyright 2010 Glenn Ansley

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

#### CONSTANTS ####

	// Plugin Version Number
	define('FT_TriOutNC_VERSION', '0.1');
	
	// Define plugin path
	if ( !defined( 'WP_CONTENT_DIR' ) ) {
		define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	}
	define( 'FT_TriOutNC_PATH' , WP_CONTENT_DIR . '/plugins/' . plugin_basename( dirname(__FILE__) ) );
	
	// Define plugin URL
	if ( !defined( 'WP_CONTENT_URL') ) {
		define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
	}
	define( 'FT_TriOutNC_URL' , WP_CONTENT_URL . '/plugins/' . plugin_basename( dirname(__FILE__) ) );

#### WIDGET CLASS ####
	class FT_TriOutNC_RecentCheckins_Widget extends WP_Widget {
	
		// Define Widget
		function FT_TriOutNC_RecentCheckins_Widget() {
			$widget_ops = array( 'classname' => 'ft_trioutnc_recentcheckins_widget', 'description' => __( "Displays your most recent TriOutNC Checkins." ) );
			$this->WP_Widget('ft_trioutnc_recentcheckins_widget', __('Recent TrioutNC Checkins'), $widget_ops);
		}
		
		// Display Widget on frontend
		function widget( $args, $instance ) {
			
			extract($args);
			
			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->number );
			
			$text = apply_filters( 'widget_text', $instance['text'], $instance );
			
			echo $before_widget;
			
			if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } ?>
				
			<div class="ft_trioutnc_recentcheckins_widget" id="ft_trioutnc_recentcheckins_widget_<?php echo $this->number; ?>" ><?php echo $instance['widget']; ?></div>
			
			<?php
			echo $after_widget;
		}
		
		// Called when widget is saved
		function update( $new_instance, $old_instance ) {

			// Grab vars from $new_instance
			$instance['title'] 			= isset( $new_instance['title'] ) ? strip_tags( stripslashes( $new_instance['title'] ) ) : '';
			$instance['number'] 		= isset( $new_instance['number'] ) ? absint( $new_instance['number'] ) : 5;
			$instance['widgettype'] 	= isset( $new_instance['widgettype'] ) ? strip_tags( stripslashes( $new_instance['widgettype'] ) ) : 'googlemap';
			$instance['trioutemail'] 	= ( isset( $new_instance['trioutemail'] ) && '' != $new_instance['trioutemail'] ) ? strip_tags( stripslashes( $new_instance['trioutemail'] ) ) : $old_instance['trioutemail'];
			$instance['uid'] 			= isset( $old_instance['uid'] ) ? $old_instance['uid'] : 0;
			$instance['nav']			= isset( $new_instance['nav'] ) ? absint( $new_instance['nav'] ) : 1;
			$instance['width']			= isset( $new_instance['width'] ) ? absint( $new_instance['width'] ) : 1;
			$instance['height']			= isset( $new_instance['height'] ) ? absint( $new_instance['height'] ) : 1;
			$instance['border']			= isset( $new_instance['border'] ) ? strip_tags( stripslashes( $new_instance['border'] ) ) : '';
			$instance['autherror'] 		= false;
			$instance['widget']			= isset( $old_instance['widget'] ) ? $old_instance['widget'] : '';
			
			// Maybe Change Triout User			
			if ( ( $instance['trioutemail'] != $old_instance['trioutemail'] ) || '' != $instance['trioutemail'] && ( isset( $new_instance['trioutpass'] ) && '' != $new_instance['trioutpass'] ) ) {
				if ( !$instance['uid'] = $this->authenticate_triout_user( $instance['trioutemail'], $new_instance['trioutpass'] ) ) {
					$instance['autherror'] 		= __( 'Username / Password Incorrect' );
					$instance['trioutemail'] 	= $old_instance['trioutemail'];
					$instance['uid'] 			= $old_instance['uid'];
				}
				
			}

			if ( 0 != $instance['uid'] )
				$instance['widget'] = $this->create_triout_widget( $instance );
			else
				$instance['widget'] = "<p>No user found</p>";
			
			return $instance;
		}

		// Widget Options in admin
		function form( $instance ) {
			
			// Set vars from $instance
			$title 			= isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
			$number 		= isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
			$widgettype		= isset( $instance['widgettype'] ) ? esc_attr( $instance['widgettype'] ) : 'map';
			$trioutemail	= isset ($instance['trioutemail'] ) ? esc_attr( $instance['trioutemail'] ) : '';
			$uid 			= isset( $instance['uid'] ) ? absint( $instance['uid'] ) : false;
			$nav 			= isset( $instance['nav'] ) ? absint( $instance['nav'] ) : 1;
			$width 			= isset( $instance['width'] ) ? absint( $instance['width'] ) : 150;
			$height 		= isset( $instance['height'] ) ? absint( $instance['height'] ) : 150;
			$border 		= isset( $instance['border'] ) ? esc_attr( $instance['border'] ) : 'aaaaaa';
			
			// Set Authentication vars
			$display = '';
			if ( $uid && 0 != $uid ) { 
				$display = 'style="display:none;"';
			}
			
			// Print error if username / password was incorrect
			if ( isset( $instance['autherror'] ) && $instance['autherror'] )
				echo "<p class='error'>" . $instance['autherror'] . "</p>";
			?>
			
			<p class='description'><?php _e( '"Share Your Location" must be enabled for this to work' ); ?></p>
			
			<h3>Triout User</h3>
			<?php 
			if ( isset( $trioutemail ) && '' != $trioutemail )
				echo "<p>" . $trioutemail . "</p>";
			?>
			<!-- Show authentication only if we UID is not set or we are changing the user -->
			<div id='ft_trioutnc_recentcheckins_authentication_<?php echo $this->number; ?>' <?php echo $display; ?>>

				<p><?php _e( 'Username and Password will not be stored. It is only needed once.' ); ?></p>

				<!-- Email -->
				<p><label for="<?php echo $this->get_field_id('trioutemail'); ?>"><?php _e('TriOut Email:'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('trioutemail'); ?>" name="<?php echo $this->get_field_name('trioutemail'); ?>" type="text" value="" /></p>	
	
				<!-- Password -->
				<p><label for="<?php echo $this->get_field_id('trioutpass'); ?>"><?php _e('TriOut Password:'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('trioutpass'); ?>" name="<?php echo $this->get_field_name('trioutpass'); ?>" type="password" value="" /></p>
			</div>
			
			<a href='#' id="ft_triout_recentcheckins_change_user_<?php echo $this->number; ?>" onClick="jQuery( '#ft_triout_recentcheckins_cancel_change_user_<?php echo $this->number; ?>' ).show();jQuery( this ).hide();jQuery( '#ft_trioutnc_recentcheckins_authentication_<?php echo $this->number; ?>' ).show();return false;" >Change User</a>
			<a href='#' style='display:none;' id="ft_triout_recentcheckins_cancel_change_user_<?php echo $this->number; ?>" onClick="jQuery( this ).hide();jQuery( '#ft_triout_recentcheckins_change_user_<?php echo $this->number; ?>' ).show();jQuery( '#ft_trioutnc_recentcheckins_authentication_<?php echo $this->number; ?>' ).hide();return false;" >Cancel Change User</a>
			
			<h3>Triout Widget Options</h3>

			<!-- Title -->
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

			<!-- Type of Widget -->
			<p><?php _e('Type of Widget:'); ?><br />
				<label for="<?php echo $this->get_field_id('widgettype'); ?>_map"><input id="<?php echo $this->get_field_id('widgettype'); ?>" name="<?php echo $this->get_field_name('widgettype'); ?>" value='map' type="radio" <?php checked( $widgettype, 'map' ); ?> /> Google Map</label>&nbsp;&nbsp;
				<label for="<?php echo $this->get_field_id('widgettype'); ?>_list"><input id="<?php echo $this->get_field_id('widgettype'); ?>" name="<?php echo $this->get_field_name('widgettype'); ?>" value='list' type="radio" <?php checked( $widgettype, 'list' ); ?> /> List</label>
			</p>

			<!-- Navigation-->
			<p><?php _e('Show Map Navigation?'); ?></label><br />
				<label for="<?php echo $this->get_field_id('nav'); ?>_map"><input id="<?php echo $this->get_field_id('nav'); ?>" name="<?php echo $this->get_field_name('nav'); ?>" value='0' type="radio" <?php checked( $nav, 0 ); ?> /> No</label>&nbsp;&nbsp;
				<label for="<?php echo $this->get_field_id('nav'); ?>_list"><input id="<?php echo $this->get_field_id('nav'); ?>" name="<?php echo $this->get_field_name('nav'); ?>" value='1' type="radio" <?php checked( $nav, 1 ); ?> /> Yes</label>
			</p>

			<p>
				<?php _e( 'Number of Locations:' ); ?><br />
				<select id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>">
					<?php
					for( $i=1;$i<=10;$i++ ) {
						echo "<option value='" . $i . "' " . selected( $i, $number, false ) . " >" . $i . "</option>";
					}
					?>
				</select>
			</p>
			<!-- Width -->
			<p><label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('Width in px:'); ?></label><br />
			<input class="widefat" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo $width; ?>" style='width:80px;' /></p>	
	
			<!-- Height -->
			<p><label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Height in px:'); ?></label><br />
			<input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo $height; ?>" style='width:80px;' /></p>	
	
			<!-- Border -->
			<p><label for="<?php echo $this->get_field_id('border'); ?>"><?php _e('Border Color (eg: aaaaa ):'); ?></label><br />
			<input class="widefat" id="<?php echo $this->get_field_id('border'); ?>" name="<?php echo $this->get_field_name('border'); ?>" type="text" value="<?php echo $border; ?>" style='width:100px;' /></p>	
	
			<?php
		}
		
		// Ping Triout for User Authentication (returns ID)
		function authenticate_triout_user( $trioutemail, $trioutpass ){

			// Include HTTP API
			if( !class_exists( 'WP_Http' ) ){
				include_once( ABSPATH . WPINC . '/class-http.php' );
			}

			// Set Vars
			$url = 'http://api.TriOutNC.com/v1/?login';
			$headers = array( 'Authorization' => 'Basic ' . base64_encode( "$trioutemail:$trioutpass" ) );
			$body = array( 'status' => 'Testing FullThrottle WordPress Widget' );			
			
			// Send Request
			$request = new WP_Http();
			$result = $request->request( $url, array( 'method' => 'POST', 'body' => $body, 'headers' => $headers ) );
			
			// Return ID or False (0)
			return absint( $result['body'] );
			
		}
		
		// Creates the triout widget
		function create_triout_widget( $data ) {
						
			$frame .= "<iframe src='http://trioutnc.com/u/" . $data['widgettype'] . ".php?user=" . $data['uid'] . "&history=" . $data['number'] . "&nav=" . $data['nav'] . "' ";
			$frame .= "width='" . $data['width'] . "' height='" . $data['height'] . "' frameborder='0' style='border:1px solid #" . $data['border'] . ";' ></iframe>";
			$frame .= "<p style='font-family:Arial;font-size: small;padding-top:0;margin-top: 3px;'><a href='http://TriOutNC.com/u/" . $data['uid'] . "' target='new' style='text-decoration: none;color: #ED000F;'>Find Me</a> on <a href='http://TriOutNC.com/' target='new' style='text-decoration: none;color: #ED000F;'>TriOut!</a></p>";
			
			return $frame;
		}
	
	}
	
	// Register widget
	function ft_trioutnc_recentcheckins_register_widget(){
		register_widget( 'FT_TriOutNC_RecentCheckins_Widget' );
	}
	add_action( 'widgets_init', 'ft_trioutnc_recentcheckins_register_widget' );

#### JAVASCRIPT ####
?>