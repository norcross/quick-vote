jQuery(document).ready(function($) {

// **************************************************************
//  disable inputs for voted posts based on cookies
// **************************************************************

	$('div.qvote_disable').each(function(index, element) {
		$(this).find('input.qvote').addClass('voted');
		$(this).find('input.qvote').attr('disabled', 'disabled');
	});

	$('div.qvote_display p.qvote_text').each(function(index, element) {
		var vtype	= $(this).find('span.vote_type').attr('id');

		if(vtype == 'upvote') {
			$(this).replaceWith('<p class="qvote_text"><span class="vote_type">You previously voted this up.</span></p>');
		}

		if(vtype == 'downvote') {
			$(this).replaceWith('<p class="qvote_text"><span class="vote_type">You previously voted this down.</span></p>');
		}


	});

	
// **************************************************************
//  process voting
// **************************************************************

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
				$('span#upvote_count_' + postid + '').replaceWith('<span id="upvote_count_' + postid + '" class="qvote_count upvote_count just_voted">' + obj.vote + '</span>');
				$('div.vote_success').delay(400).show(400);

				$('input.upvote_' + postid + '').addClass('voted');
				$('input.upvote_' + postid + '').addClass('voted');
				$('input.upvote_' + postid + '').attr('disabled', 'disabled');
				$('input.downvote_' + postid + '').addClass('voted');				
				$('input.downvote_' + postid + '').attr('disabled', 'disabled');
			}

			if(obj.success == true && obj.action == 'vote_down') {
				$('input.downvote_' + postid + '').val(obj.vote);
				$('span#downvote_count_' + postid + '').replaceWith('<span id="downvote_count_' + postid + '" class="qvote_count downvote_count just_voted">' + obj.vote + '</span>');				
				$('div.vote_success').delay(400).show(400);
//				$('div.qvote_display').after('<div id="vote_reply" class="vote_success"><p>Thanks for voting!</p></div>').delay(400).fadeIn(400);
				
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