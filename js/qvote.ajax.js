jQuery(document).ready(function($) {


// ajax for upvotes
	$('input.qvote').click(function() {
			
		// now the ajax
    	var postid	= $(this).attr('id');
    	var vtype	= $(this).attr('rel');
		var count	= $(this).prop('value');
    	var data	= {
			action: 'qvote_count',
			postid: postid,
			vtype:	vtype,
			count:	count
      	};

    	jQuery.post(QVoteAJAX.ajaxurl, data, function(response) {

			try {
				var obj = jQuery.parseJSON(response);
			}
			catch(e) {
				console.log('Catch E Error');
			} 

			if(obj.success == true && obj.action == 'vote_up') {
				$('input.upvote_' + postid + '').val(obj.vote);
				$('span#upvote_count_' + postid + '').replaceWith('<span id="upvote_count__' + postid + '" class="qvote_count upvote_count just_voted">' + obj.vote + '</span>');
				$('div.qvote_display').after('<div id="vote_reply" class="vote_success"><p>Thanks for voting!</p></div>');

				$('input.upvote_' + postid + '').addClass('voted');
				$('input.upvote_' + postid + '').attr('disabled', 'disabled');
				$('input.downvote_' + postid + '').addClass('voted');				
				$('input.downvote_' + postid + '').attr('disabled', 'disabled');
			}

			if(obj.success == true && obj.action == 'vote_down') {
				$('input.downvote_' + postid + '').val(obj.vote);
				$('span#downvote_count_' + postid + '').replaceWith('<span id="downvote_count__' + postid + '" class="qvote_count downvote_count just_voted">' + obj.vote + '</span>');				
				$('div.qvote_display').after('<div id="vote_reply" class="vote_success"><p>Thanks for voting!</p></div>');
				
				$('input.upvote_' + postid + '').addClass('voted');
				$('input.upvote_' + postid + '').attr('disabled', 'disabled');
				$('input.downvote_' + postid + '').addClass('voted');				
				$('input.downvote_' + postid + '').attr('disabled', 'disabled');

			}

			if (obj.success == false) {
				console.log('Other Error');
			}
	  	});
  	});

});