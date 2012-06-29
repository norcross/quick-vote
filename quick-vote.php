<?php 
/* 
Plugin Name: Quick Vote
Plugin URI: http://andrewnorcross.com/plugins/
Description: Allows for simple up or down voting on a post via AJAX
Version: 1.00
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
		add_action		( 'wp_enqueue_scripts',			array( $this, 'scripts'		) );
		add_filter		( 'the_content',				array( $this, 'buttons'		),	25);
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
	 * Set and localize the ajax call to count
	 *
	 * @return QuickVote
	 */


	public function scripts() {
		wp_enqueue_style( 'qvote', plugins_url('/css/qvote.css', __FILE__) );
		wp_enqueue_script( 'qvote-ajax', plugins_url('/js/qvote.ajax.js', __FILE__) , array('jquery'), null, true );
		wp_localize_script( 'qvote-ajax', 'QVoteAJAX', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}

	/**
	 * Process the count function
	 *
	 * @return QuickVote
	 */

	public function qvote_count() {

		$post_id	= $_POST['postid'];
		$vtype		= $_POST['vtype'];
		$count		= $_POST['count'];
		
		$ret = array();

		if(empty($post_id) ) {
			$ret['success'] = false;
			$ret['error']	= 'NO_POST_ID';
			$ret['err_msg']	= 'No Post ID could be found.';
		}


		if(empty($vtype) ) {
			$ret['success'] = false;
			$ret['error']	= 'NO_VOTE_TYPE';
			$ret['err_msg']	= 'Could not determine the vote type.';
		}
		
		if(isset($vtype) && $vtype == 'upvote' ) {
			$upvote	= $count + 1;
			update_post_meta($post_id, '_qvote_up', $upvote);
			$ret['success'] = true;
			$ret['message']	= 'One Up Vote';
			$ret['action']	= 'vote_up';
			$ret['vote']	= intval($upvote);
		}

		if(isset($vtype) && $vtype == 'downvote' ) {
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
	 * display voting buttons
	 *
	 * @return QuickVote
	 */
	 
	public function buttons( $content ) {

		global $post;
		$post_id	= $post->ID;

		$upvote		= get_post_meta($post->ID, '_qvote_up', true);
		$downvote	= get_post_meta($post->ID, '_qvote_down', true);
		
		$up_value	= ($upvote < 1 ? 0 : $upvote);
		$dn_value	= ($downvote < 1 ? 0 : $downvote);		
				
		if ( is_single() ) {
			$buttons = '<div class="qvote_display">';
			$buttons .= '<p class="qvote_block">';
			$buttons .= '<input type="button" rel="upvote" class="qvote upvote upvote_'.$post_id.'" id="'.$post_id.'" value="'.$up_value.'" />';
			$buttons .= '<span id="upvote_count_'.$post_id.'" class="qvote_count upvote_count">'.$up_value.'</span>';
			$buttons .= '</p>';			
			$buttons .= '<p class="qvote_block">';
			$buttons .= '<input type="button" rel="downvote" class="qvote downvote downvote_'.$post_id.'" id="'.$post_id.'" value="'.$dn_value.'" />';
			$buttons .= '<span id="downvote_count_'.$post_id.'" class="qvote_count downvote_count">'.$dn_value.'</span>';
			$buttons .= '</p>';
			$buttons .= '</div>';
		}
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