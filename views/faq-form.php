<?php

//create our html variable
$html='';

//require antispam if the user so desires
if( $this->use_antispam() ){

	$this->require_antispam();

}

//handle the submission if it posted
if( isset( $_POST['submit_faq'] ) && $_POST['submit_faq'] ) {

	$result = $this->handle_submission( $this->use_antispam() );

}

//the title of the section
$html .= '<h2 class="faq-title">Have a Question? Submit it here!</h2>';

//set these up so we don't have to check if they are empty in the echo's
$author_name = '';

$author_email = '';

$faq_content = '';

//handle the submission results
if(isset($result) && $result){

	$result_message = '';

	$html .= '<div class="faq-result';

	//error messages
	if($result['type']=='error') {

		//error class for the results div
		$html .= ' woocommerce-error faq-error';

		//we need to repopulate the form if it generated an error
		$author_name = $_POST['faq_author_name'];

		$author_email = $_POST['faq_author_email'];

		$faq_content = $_POST['faq_content'];

		//error list
		$result_message .= '<ul class="faq-errors">';

			//if the result is an error, $result contains an array of errors in $result['errors']
			//each error message is setup like array('input_name'=>'error_message')
			foreach($result['errors'] as $key => $error){

				//so add them as list items
				$result_message .= '<li class="single-faq-error">' . $error . '</li>';

			}
		//close the error messages
		$result_message .= '</ul>';

	}

	//success message
	else{
		//success class
		$html.=' woocommerce-message faq-success';

		//if it's a success, $result['message'] only holds the single string message
		$result_message = $result['message'];

	}

	//add the $result_message to the $html variable.
	//at this point, it doesn't matter if it is a success or an error,
	//as $result_message is just an html string
	$html.='">'.$result_message.'</div>';

}

//if we don't have a result, create an empty string so we can still use $result without warnings

$result = '';

//create the form
$html .='<form method="POST" action="#tab-faqs" class="faq-form">';

//and the inputs
$html .= '<p><label for="faq_author_name">Your Name:</label> <abbr class="required" title="required">*</abbr><br />';

//each input goes through should_display_error, 
//which checks if the current input's name exists in the result variable.
//should_display_errors checks if $result['errors'] is an array, 
//so we don't need to worry about that here.
$html .= '<input class="' . $this->should_display_error($result,'faq_author_name') . '" id="faq-author-name-input" value="' . $author_name . '" required="required" type="text" name="faq_author_name" placeholder="Your Name:" /></p>';

$html .= '<p><label for="faq_author_email">Your Email:</label> <abbr class="required" title="required">*</abbr><br />';

$html .= '<input class="' . $this->should_display_error($result,'faq_author_email') . '"  id="faq-author-email-input" value="' . $author_email . '" required="required" type="email" name="faq_author_email" placeholder="Your Email:" /></p>';

$html .= '<p><label for="faq_content">Your Question:</label> <abbr class="required" title="required">*</abbr><br />';

$html .= '<textarea class="' . $this->should_display_error($result,'faq_content') . '"  placeholder="Your Question:" required="required" id="faq-content-input" name="faq_content" />'.$faq_content.'</textarea></p>';

//if we are using antispam, call the AYAH class' html getter and add it to our html
if($this->use_antispam()) $html .= $this->ayah->getPublisherHTML();

//otherwise, use honeypot (hidden with css)
else $html .= '<input type="text" name="primary_email" id="poohbear" />';

$html .= '<input type="submit" name="submit_faq" value="Submit" />';

$html .= '</form>';

//output the html
echo $html;

if($wc_version < 2.0){

	echo '</div>';
	
}