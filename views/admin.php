<?php
//we need this to add our tab to the save action
global $woocommerce_settings;

//get our default recaptcha keys
//$recaptcha_options = $this->get_default_recaptchas();

//get this plugin's recaptcha keys
$wc_version = get_woocommerce_version();

if($wc_version < 2.0){
	$title = 'name';
	$publisher = get_option('woocommerce_faqs_publisher_key');
	$scoring = get_option('woocommerce_faqs_publisher_key');
}
else{
	$title = 'title';
	$publisher = woocommerce_settings_get_option( $this->option_prefix . 'publisher_key' );
	$scoring = woocommerce_settings_get_option( $this->option_prefix . 'scoring_key' );
}
//if we have some defaults, but none from this plugin, update the db to use the defaults
/*
if($recaptcha_options && (!$private && !$public)){
	$private = $recaptcha_options[0];
	$public = $recaptcha_options[1];
	update_option($this->option_prefix.'recaptcha_private_key',$private);
	update_option($this->option_prefix.'recaptcha_public_key',$public);
}*/

//create the settings fields
$settings[] = array(
	$title=>'Anti-Spam Settings',
	'type'=>'title',
	'id'=>$this->option_prefix . 'antispam',
	'desc'=>'Please choose your Anti-Spam settings.' .
	' If you choose to disable AYAH antispam, an invisible "honeypot" antispam method will be used.'.
	sprintf(' Get your API keys %shere%s','<a target="_blank" href="http://portal.areyouahuman.com/signup/basic">', '</a>.')
	);
$settings[]=array(
	$title=>'Use Are You A Human antispam?',
	'id'=>$this->option_prefix . 'use_antispam',
	'type'=>'checkbox',
	'default'=>'yes'
	);
$settings[]=array(
	$title=>'Publisher Key',
	'id'=>$this->option_prefix . 'publisher_key',
	'type'=>'text',
	'default'=>$publisher
	);
$settings[]=array(
	$title=>'Public Key',
	'id'=>$this->option_prefix . 'scoring_key',
	'type'=>'text',
	'default'=>$scoring
	);
$settings[]=array(
	'type'=>'sectionend',
	'id'=>$this->option_prefix . 'antispam'
	);
//put it into the global
$woocommerce_settings['faqs']=$settings;
//for use in display_settings()
$this->settings = $settings;