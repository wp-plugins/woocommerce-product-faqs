<?php

$wc_version = get_woocommerce_version();

if($wc_version < 2.0){

	echo '<div class="panel" id="tab-faqs">';

}

//$post is the product
global $post;


//these $args are for retrieving the faqs
//todo: think about making these flexible

//get the terms of this product
$terms = wp_get_post_terms( get_the_id(), 'product_cat', array('fields'=>'ids') );

$args = array(

	'nopaging'		  => true,

	'posts_per_page'  => -1,

	'order'           => 'DESC',

	'post_type'       => self::$post_type,

	'post_status'     => 'publish',

	'orderby'		  => 'menu_order',

	//this is the true association between product and faq
	'meta_query' 	  => array(

		'relation'		=> 'OR',

		//exact match on the product ID
		array(

			'key'   => '_' . self::$post_type . '_product',

			'value' => $post->ID,

		),
		
		//match FAQs that exist on all products
		array(

				'key'   => '_' . self::$post_type . '_product',

				'value' => 'all',

		),

		array(

				'key'   => '_' . self::$post_type . '_categories',

				'value' => '0',

		),

		array(

				'key'   => '_' . self::$post_type . '_categories',

				'value' => $terms ,

				'compare' => 'IN'

		)

	),

);

$preview = false;

//if we are 'previewing' or viewing a faq
if( isset( $_GET['faq-preview'] ) ) {

	//check its post status to see if it is indeed unpublished
	if( get_post_status( $_GET['faq-preview'] ) != 'publish') {

		//if so, override our above args to only retrieve the
		//preview faq
		$args = array(

			'post_type' => self::$post_type,

			'post__in' => array( (int)$_GET['faq-preview'] ),

			'post_status' => 'any'

			);

		//and set the $preview variable
		//to 'preview' for use later on
		$preview = 'preview';

	}

	else{

		/*
		* otherwise, just set the $preview variable
		* equal to the faq post id
		* this comes into play if an admin has visited the preview link
		* for a faq that has already been approved
		* or if we are just viewing a faq
		*/
		$preview = $_GET['faq-preview'];
		
	}

}

elseif( isset( $_GET['faq-view'] ) ) {

	$preview = $_GET['faq-view'];
}

//keep the product for use in the inner loops
global $post;
$product = $post;

//create the query
$faqs = new WP_Query( $args );

//should we expand all faqs by default?
$expand = get_option( self::$option_prefix . 'expand_faqs', false );

//if the query retrieved some posts
if( $faqs->have_posts() ) {

	//faqs wrapper
	echo '<div class="woo-faqs">';

	//wrapper title, with title of product
	echo '<h3>' . __( 'FAQs for ', self::$plugin_slug ) . $post->post_title . '</h3>';

	//counter for even/odd class
	$c = 0;

	//loop the faqs
	while ( $faqs->have_posts() ) : $faqs->the_post();

		//we use this quite a few times
		$faq_id = get_the_ID();
		//first, get the 'comments' (answers)
		$args = array( 'post_id' => $faq_id, 'order' => 'ASC' );

		$comments = get_comments( $args );

		//single faq wrapper
		echo '<div class="single-woo-faq';

		if( $expand ) echo ' show';

		//for css targeting
		echo ' faq-' . $faq_id;

		//even/odd targeting
		if( $c % 2 == 0 ) echo ' even';

		else echo ' odd';

		//if we are TRULY previewing a faq,
		//this allows for css targeting of preview
		if( $preview == 'preview' ) echo ' preview';

		//otherwise, it is a view
		//which we still target with css
		elseif( $preview == $faq_id ) echo ' view';

		//if there are/are not answers,
		//set the class for targeting
		if( $comments ) echo ' answered';

		else echo ' unanswerered';

		//close single faq wrapper beginning div
		echo '">';

		//the content is the question, which is the title
		echo '<span itemprop="comment" itemscope itemtype="http://schema.org/UserComments"' .

		 '" class="faq-question"><a title="' . __('Click to view the answer!', self::$plugin_slug ) . '">' . __( 'Q:', self::$plugin_slug ) .

		 ' ' . get_the_content();

		//if we are TRULY previewing a faq,
		//echo that fact so the admin knows
		if( $preview == 'preview' ) echo ' (' . __( 'Pending Approval', self::$plugin_slug ) . ') ';

		//close the single faq title
		echo '</a></span>';

		//the 'content' of the faq is the answers
		//and for the admin, also the reply form
		echo '<div class="faq-content">';

		//get the name of the asker
		echo '<div class="faq-author authorship author" itemprop="creator" itemscope itemtype="http://schema.org/Person">';

		$author_name = get_post_meta( $faq_id, '_' . self::$post_type . '_author_name', true );

		echo '<span class="asked-by-on">' . __( '— Asked', self::$plugin_slug ) . ' ';

		if( $author_name ) {

			echo __( 'by', self::$plugin_slug ) . ' </span>';

			echo '<span itemprop="name">' . $author_name . '</span>';

		}

		echo ' ' . __( 'on', self::$plugin_slug );

		if( !$author_name ) echo '</span>';

		echo '<span itemprop="commentTime">' . ' ' . get_the_date() . '</span>';

		echo '</div>';

		//global $withcomments;

		//$withcomments=1;

		//comments_template();

		//list the answers, if any
		if( $comments ) wp_list_comments( array( 'type' => 'all', 'callback' => array( $this, 'comment_callback' ) ), $comments );

		//otherwise echo a message stating that no answers exist yet
		else echo '<p class="comment-list awaiting-response">' . __( 'This question has not yet been responded to.', self::$plugin_slug) . '</p>';

		//wrapper for answer form
		echo '<div class="faq-comment">';

		//todo: move this cap to the product author?
		$answer_caps = apply_filters( self::$option_prefix . 'answer_caps', 'edit_post' );

		//we don't want to allow answers on an unpublished
		//faq, or allow unauthorized users to answer
		if( is_user_logged_in() && current_user_can( $answer_caps,  $product->ID ) && get_post_status( $faq_id ) == 'publish' ) {

			$args = array(

				'id_form'           => 'commentform',
				'id_submit'         => 'submit',
				'title_reply'       => __( 'Leave an Answer', self::$plugin_slug ),
				'title_reply_to'    => __( 'Leave an Answer to %s', self::$plugin_slug ),
				'cancel_reply_link' => __( 'Cancel Answer', self::$plugin_slug ),
				'label_submit'      => __( 'Post Answer', self::$plugin_slug ),

				'comment_field' =>  '<p class="comment-form-comment"><label for="comment">' . _x( 'Answer', 'noun', self::$plugin_slug ) .
				'</label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true">' .
				'</textarea></p>',

				'must_log_in' => '',

				'logged_in_as' => '',

				'comment_notes_before' => '',

				'comment_notes_after' => '',

				'fields' => apply_filters( 'comment_form_default_fields', array() ),
			);

			comment_form($args);
			
		}

		elseif( is_user_logged_in() && current_user_can( $answer_caps, $product->ID ) ) {

			echo '<form id="quick-approve-faq" action="" method="post">';

			echo '<input id="qaf_post_id" type="hidden" name="post_id" value="' . $faq_id . '" />';

			echo '<input id="qaf_nonce" type="hidden" name="nonce" value="'. wp_create_nonce( 'publish-post_' . $faq_id ) . '" />';

			echo '<input type="submit" name="approve_faq" value="' . __( 'Approve this FAQ', self::$plugin_slug ) . '" />';

			echo '</form>';

		}

		//end wrapper for faq answer form
		echo '</div>';

		//end wrapper for faq 'content'
		echo '</div>';

		//end wrapper for single faq
		echo '</div>';

		//increase even/odd counter
		$c++;

	//end the faq loop
	endwhile;

	// Reset Query
	wp_reset_postdata();

	//ending wrapper for faqs section
	echo '</div>';
}
