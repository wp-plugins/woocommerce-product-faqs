<?php
//we need this to add our tab to the save action
global $woocommerce_settings;

//get our default recaptcha keys
//$recaptcha_options = $this->get_default_recaptchas();

//get this plugin's recaptcha keys
$wc_version = get_woocommerce_version();

if( $wc_version < 2.0 ) {

	$title = 'name';

}

else{

	$title = 'title';

}

//put it into the global
$woocommerce_settings['faqs'] = $this->settings;