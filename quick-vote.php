<?php 
/* 
Plugin Name: Quick Vote
Plugin URI: http://andrewnorcross.com/plugins/
Description: Allows for simple up or down voting on a post via AJAX
Version: 1.02
Author: Andrew Norcross
Author URI: http://andrewnorcross.com

    Copyright 2012 Andrew Norcross

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/ 



// Start up the engine 
class QuickVote
{
	/**
	 * Static property to hold our singleton instance
	 * @var QuickVote
	 */
	static $instance = false;


	/**
	 * This is our constructor, which is private to force the use of
	 * getInstance() to make this a Singleton
	 *
	 * @return QuickVote
	 */
	private function __construct() {
		add_action		( 'admin_menu',					array( $this, 'admin_menu'	) );
		add_action		( 'admin_init', 				array( $this, 'settings'	) );
		add_action		( 'wp_enqueue_scripts',			array( $this, 'scripts'		) );
		add_action		( 'admin_head', 				array( $this, 'css_head'	) );
		add_filter		( 'manage_edit-post_columns',	array( $this, 'show_columns') );
		add_action		( 'manage_posts_custom_column',	array( $this, 'votes_column'),	10, 2 );
		add_filter		( 'the_content',				array( $this, 'post_button'	),	25);
		add_filter		( 'the_content',				array( $this, 'page_button'	),	25);
//		add_action		( 'do_meta_boxes',				array( $this, 'metabox'		),	10,	2 );
		add_action		( 'wp_ajax_qvote_count',		array( $this, 'qvote_count'	) );
		add_action		( 'wp_ajax_nopriv_qvote_count',	array( $this, 'qvote_count'	) );
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return QuickVote
	 */
	 
	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

	/**
	 * build out settings page
	 *
	 * @return QuickVote
	 */

	public function admin_menu() {
	    add_submenu_page('options-general.php', 'QuickVote Settings', 'QuickVote Settings', 'manage_options', 'qvote-settings', array( $this, 'qvote_display' ));
	}

	/**
	 * Register settings
	 *
	 * @return QuickVote
	 */


	public function settings() {
		register_setting( 'qvote_options', 'qvote_options');
	}

	/**
	 * CSS in the head for the settings page
	 *
	 * @return QuickVote
	 */

	public function css_head() { ?>
		<style type="text/css">

		div#icon-qvote {
			background:url(<?php echo plugins_url('/img/qvote_icon.png', __FILE__); ?>) no-repeat 0 0!important;
		}

		div.qvote_options {
			padding:1em;
		}
		
		table.qvote-table {
			margin:0 0 30px 0;
		}
		
		div.qvote_form_text {
			margin:0 0 20px 0;
		}
		
		div.qvote_form_options input.qvote_checkbox {
			margin:0 5px 0 0;
		}
		
		th#upvote,
		th#downvote {
			width:40px;
			text-align:center;
		}
		
		span.vote_col {
			text-align:center;
			display:block;
			width:auto;
			margin:0 auto;
		}
		
		</style>

	<?php }

	/**
	 * Display vote totals in post column
	 *
	 * @return QuickVote
	 */


	public function show_columns( $columns ) {
			$columns['upvote']		= __( 'Up' );
			$columns['downvote']	= __( 'Down' );
		
		return $columns;
	}

	public function votes_column ( $column_name, $post_id ) {
//		if ( 'upvote' != $column_name || 'downvote' != $column_name )
//			return;
		
		global $post;	 
		switch ( $column_name ) {
			case 'upvote':
				$vote	= get_post_meta($post->ID, '_qvote_up', true);
				$voted	= (!empty($vote) && $vote > 0 ? $vote : 0);
				echo '<span class="vote_col">'.$voted.'</span>';
				break;
			case 'downvote':
				$vote	= get_post_meta($post->ID, '_qvote_down', true);
				$voted	= (!empty($vote) && $vote > 0 ? $vote : 0);
				echo '<span class="vote_col">'.$voted.'</span>';
				break;
		}			
	}


	/**
	 * Display main options page structure
	 *
	 * @return QuickVote
	 */
	 
	public function qvote_display() { ?>
	
		<div class="wrap">
    	<div class="icon32" id="icon-qvote"><br></div>
		<h2><?php _e('Quick Vote Settings') ?></h2>
        
	        <div class="qvote_options">
            	<div class="qvote_form_text">
            	<p><?php _e('Info about the plugin will go here.') ?></p>
                </div>
                
                <div class="qvote_form_options">
	            <form method="post" action="options.php">
			    <?php
                settings_fields( 'qvote_options' );
				$qvote_options	= get_option('qvote_options');
				?>
        
				<p>
                <label for="qvote_options[post]"><input type="checkbox" id="qvote_post" name="qvote_options[post]" class="qvote_checkbox" value="true" <?php checked( $qvote_options['post'], 'true' ); ?>/><?php _e('Display the voting buttons on single posts.') ?></label>
                </p>

				<p>
                <label for="qvote_options[page]"><input type="checkbox" id="qvote_page" name="qvote_options[page]" class="qvote_checkbox" value="true" <?php checked( $qvote_options['page'], 'true' ); ?>/><?php _e('Display the voting buttons on single pages.') ?></label>
                </p>
   
	    		<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
				</form>
                </div>
    
            </div>
        
        </div>    
	
	<?php }

	/**
	 * Set and localize the ajax call to count
	 *
	 * @return QuickVote
	 */


	public function scripts() {
		wp_enqueue_style( 'qvote', plugins_url('/css/qvote.css', __FILE__) );
		wp_enqueue_script( 'qvote-ajax', plugins_url('/js/qvote.ajax.js', __FILE__) , array('jquery'), null, true );
		wp_localize_script( 'qvote-ajax', 'QVoteAJAX', array(
			'ajaxurl'	=> admin_url( 'admin-ajax.php' ),
			'nonce'		=> wp_create_nonce( 'qvote_nonce' )
			) );
	}

	/**
	 * Process the count function
	 *
	 * @return QuickVote
	 */

	public function qvote_count() {

		$domain		= get_bloginfo('url');
		$post_id	= $_POST['postid'];
		$vtype		= $_POST['vtype'];
		$count		= $_POST['count'];
		
		$ret = array();

		check_ajax_referer( 'qvote_nonce', 'nonce' );

		if(empty($post_id) ) {
			$ret['success'] = false;
			$ret['error']	= 'NO_POST_ID';
			$ret['err_msg']	= 'No Post ID could be found.';
			echo json_encode($ret);
			die();	
		}


		if(empty($vtype) ) {
			$ret['success'] = false;
			$ret['error']	= 'NO_VOTE_TYPE';
			$ret['err_msg']	= 'Could not determine the vote type.';
			echo json_encode($ret);
			die();
		}
		
		if(isset($vtype) && $vtype == 'upvote' ) {
			// set cookie
			if (!isset($_COOKIE['qvote_'.$post_id.'']))
				setcookie('qvote_'.$post_id.'', 'upvote', time()+3600*24*365, COOKIEPATH, COOKIE_DOMAIN, false);
	
			// process vote
			$upvote	= $count + 1;
			update_post_meta($post_id, '_qvote_up', $upvote);
			$ret['success'] = true;
			$ret['message']	= 'One Up Vote';
			$ret['action']	= 'vote_up';
			$ret['vote']	= intval($upvote);
		}

		if(isset($vtype) && $vtype == 'downvote' ) {
			// set cookie
			if (!isset($_COOKIE['qvote_'.$post_id.'']))
				setcookie('qvote_'.$post_id.'', 'downvote', time()+3600*24*365, COOKIEPATH, COOKIE_DOMAIN, false);
			
			// process vote
			$downvote	= $count + 1;
			update_post_meta($post_id, '_qvote_down', $downvote);
			$ret['success'] = true;
			$ret['message']	= 'One Down Vote';
			$ret['action']	= 'vote_down';
			$ret['vote']	= intval($downvote);
		}

		echo json_encode($ret);
		die();		
				
	}

	/**
	 * display voting buttons for posts
	 *
	 * @return QuickVote
	 */
	 
	public function post_button( $content ) {
	
		$qvote_options	= get_option('qvote_options');

		if($qvote_options['post'] !== 'true' )
			return $content;

		if(!is_singular('post') )
			return $content;
			
			global $post;
			$post_id	= $post->ID;
	
			$upvote		= get_post_meta($post->ID, '_qvote_up', true);
			$downvote	= get_post_meta($post->ID, '_qvote_down', true);
			
			$up_value	= ($upvote < 1 ? 0 : $upvote);
			$dn_value	= ($downvote < 1 ? 0 : $downvote);		

			$voted		= (isset($_COOKIE['qvote_'.$post_id.'']) ? 'qvote_disable' : '');
			$voted_up	= (isset($_COOKIE['qvote_'.$post_id.'']) && $_COOKIE['qvote_'.$post_id.''] == 'upvote' ? 'qvote_show' : '');			
			$voted_down	= (isset($_COOKIE['qvote_'.$post_id.'']) && $_COOKIE['qvote_'.$post_id.''] == 'downvote' ? 'qvote_show' : '');						
			$vote_type	= (isset($_COOKIE['qvote_'.$post_id.'']) ? '<p class="qvote_block qvote_text"><span class="vote_type" id="'.$_COOKIE['qvote_'.$post_id.''].'"></span></p>' : '');

				$buttons = '<div class="qvote_display '.$voted.'">';
				$buttons .= '<p class="qvote_block '.$voted_up.'">';
				$buttons .= '<input type="button" rel="upvote" class="qvote upvote upvote_'.$post_id.'" id="'.$post_id.'" value="'.$up_value.'" />';
				$buttons .= '<span id="upvote_count_'.$post_id.'" class="qvote_count upvote_count">'.$up_value.'</span>';
				$buttons .= '</p>';			
				$buttons .= '<p class="qvote_block '.$voted_down.'">';
				$buttons .= '<input type="button" rel="downvote" class="qvote downvote downvote_'.$post_id.'" id="'.$post_id.'" value="'.$dn_value.'" />';
				$buttons .= '<span id="downvote_count_'.$post_id.'" class="qvote_count downvote_count">'.$dn_value.'</span>';
				$buttons .= '</p>';
				$buttons .= $vote_type;
				$buttons .= '</div>';
				$buttons .= '<div class="vote_success" style="display:none;"><p>Thanks for voting!</p></div>';
				
			// Returns the buttons.
			return $content.$buttons;

	}

	/**
	 * display voting buttons for posts
	 *
	 * @return QuickVote
	 */
	 
	public function page_button( $content ) {

		$qvote_options	= get_option('qvote_options');

		if($qvote_options['page'] !== 'true' )
			return $content;

		if(!is_singular('page') )
			return $content;
			
			global $post;
			$post_id	= $post->ID;
	
			$upvote		= get_post_meta($post->ID, '_qvote_up', true);
			$downvote	= get_post_meta($post->ID, '_qvote_down', true);
			
			$up_value	= ($upvote < 1 ? 0 : $upvote);
			$dn_value	= ($downvote < 1 ? 0 : $downvote);		

			$voted		= (isset($_COOKIE['qvote_'.$post_id.'']) ? 'qvote_disable' : '');
			$voted_up	= (isset($_COOKIE['qvote_'.$post_id.'']) && $_COOKIE['qvote_'.$post_id.''] == 'upvote' ? 'qvote_show' : '');			
			$voted_down	= (isset($_COOKIE['qvote_'.$post_id.'']) && $_COOKIE['qvote_'.$post_id.''] == 'downvote' ? 'qvote_show' : '');						
			$vote_type	= (isset($_COOKIE['qvote_'.$post_id.'']) ? '<p class="qvote_block qvote_text"><span class="vote_type" id="'.$_COOKIE['qvote_'.$post_id.''].'"></span></p>' : '');

				$buttons = '<div class="qvote_display '.$voted.'">';
				$buttons .= '<p class="qvote_block '.$voted_up.'">';
				$buttons .= '<input type="button" rel="upvote" class="qvote upvote upvote_'.$post_id.'" id="'.$post_id.'" value="'.$up_value.'" />';
				$buttons .= '<span id="upvote_count_'.$post_id.'" class="qvote_count upvote_count">'.$up_value.'</span>';
				$buttons .= '</p>';			
				$buttons .= '<p class="qvote_block '.$voted_down.'">';
				$buttons .= '<input type="button" rel="downvote" class="qvote downvote downvote_'.$post_id.'" id="'.$post_id.'" value="'.$dn_value.'" />';
				$buttons .= '<span id="downvote_count_'.$post_id.'" class="qvote_count downvote_count">'.$dn_value.'</span>';
				$buttons .= '</p>';
				$buttons .= $vote_type;
				$buttons .= '</div>';
				$buttons .= '<div class="vote_success" style="display:none;"><p>Thanks for voting!</p></div>';
	
			// Returns the buttons.
			return $content.$buttons;

	}

	/**
	 * build out meta box
	 *
	 * @return QuickVote
	 */

	public function metabox( $page, $context ) {
		
		$args = array(
			'public'   => true
		); 
		
		$types = get_post_types($args);
    	
		if ( in_array( $page,  $types ) && 'side' == $context )
			add_meta_box('qvote_post_display', __('Voting'), array(&$this, 'qvote_post_display'), $page, $context, 'high');
	}


/// end class
}


// Instantiate our class
$QuickVote = QuickVote::getInstance();