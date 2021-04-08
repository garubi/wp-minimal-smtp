<?php 
/*
* Plugin Name: Minimal SMTP
* Plugin URI: https://github.com/garubi/wp-minimal-smtp
* Description: Minimal plugin to enable WP to send emails using SMTP. Stores parameters in config.php. Run from mu-plugins too. See Readme or plugin's page for configuration guidelines. Use wp-cli 'test-email' command to test your configuration
* Version: 1.0
* Author: Stefano Garuti
* Author URI: https://github.com/garubi/
* GitHub Plugin URI: https://github.com/garubi/wp-minimal-smtp
*/

/**
 * Copyright (c) `date "+%Y"` . All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */
 
 /**
 * This function will connect wp_mail to your authenticated
 * SMTP server. 
 *
 * Values are constants set in wp-config.php.
 * Example:
 * define( 'MINSMTP_ON',           true );                 // Enable the plugin
 * define( 'MINSMTP_SMTP_HOST',   'smtp.example.com' );    // The hostname of the mail server - REQUIRED
 * define( 'MINSMTP_SMTP_PORT',   '25' );                  // SMTP port number - likely to be 25, 465 or 587 - REQUIRED
 * define( 'MINSMTP_SMTP_FROM',   'website@example.com' ); // SMTP From email address
 * define( 'MINSMTP_SMTP_NAME',   'e.g Website Name' );    // SMTP From name
 *
 * define( 'MINSMTP_SMTP_AUTH',    true );                 // Use SMTP authentication (true|false). defaults to 'false'
 * define( 'MINSMTP_SMTP_USER',   'user@example.com' );    // Username to use for SMTP authentication - REQUIRED if MINSMTP_SMTP_AUTH is 'true'
 * define( 'MINSMTP_SMTP_PASS',   'smtp password' );       // Password to use for SMTP authentication - REQUIRED if MINSMTP_SMTP_AUTH is 'true'
 *
 * define( 'MINSMTP_SMTP_SECURE', 'tls' );                 // Encryption system to use - ssl or tls. use 'false' or let empty for none encryption
 * define( 'MINSMTP_SMTPAutoTLS', true );                  // If use the TLS automatically if the server reports to support it. Use 'true' o 'false'. if empty defaults to 'true'
 *
 * define( 'SMTP_DEBUG',   0 );                             // for debugging purposes. set between 0 and 4
 *
 */
 
add_action('admin_notices', 'minsmtp_missing_parameter_admin_notice');
add_action('admin_notices', 'minsmtp_inactive_admin_notice');

function minsmtp_inactive_admin_notice(){
    if( !defined( 'MINSMTP_ON' ) || true !== MINSMTP_ON ){
        $message = 'SMTP Minimal plugin: the plugin is installed but NOT active:<br>In order to activate it you have to define the MINSMTP_ON contant as "true"';
        ?>
        <div class="notice notice-info">
            <p><strong><?php echo $message ?></strong></p>
        </div>
        <?php
    }

}

function minsmtp_missing_parameter_admin_notice( ) {
    if( !defined( 'MINSMTP_ON' ) || true !== MINSMTP_ON ) return;
    $missing = array();
    if( !defined( 'MINSMTP_SMTP_HOST' ) ) $missing[] = 'MINSMTP_SMTP_HOST';
    if( defined( 'MINSMTP_SMTP_AUTH' ) && true == MINSMTP_SMTP_AUTH ){
        if( !defined( 'MINSMTP_SMTP_USER' ) ) $missing[] = 'MINSMTP_SMTP_USER';
        if( !defined( 'MINSMTP_SMTP_PASS' ) ) $missing[] = 'MINSMTP_SMTP_PASS';
    } 
    if( !defined( 'MINSMTP_SMTP_PORT' ) ) $missing[] = 'MINSMTP_SMTP_PORT';
    
    if( count( $missing ) > 0 ){
        $message = 'SMTP Minimal plugin: can\'t send emails because the following parameters are missing:<br>';
        $message .= join( ', ', $missing );
        $message .= '<br>You should setup the missing parameters in your WP configuration';
        ?>
        <div class="notice notice-error">
            <p><strong><?php echo $message ?></strong></p>
        </div>
        <?php
    }
 }


add_action( 'phpmailer_init', 'minsmtp_send_smtp_email' );
function minsmtp_send_smtp_email( $phpmailer ) {
    if( true !== MINSMTP_ON ) return $phpmailer;
    
    if ( ! is_object( $phpmailer ) ) {
		$phpmailer = (object) $phpmailer;
	}
    
	$phpmailer->Mailer     = 'smtp';
	$phpmailer->Host       = MINSMTP_SMTP_HOST;
    $phpmailer->Port       = MINSMTP_SMTP_PORT;

    if( defined( 'MINSMTP_SMTP_AUTH' ) && true == MINSMTP_SMTP_AUTH ){
        $phpmailer->SMTPAuth = MINSMTP_SMTP_AUTH;
        $phpmailer->Username   = MINSMTP_SMTP_USER;
    	$phpmailer->Password   = MINSMTP_SMTP_PASS;
    }
	else{
        $phpmailer->SMTPAuth = false;
    }
    
	$phpmailer->SMTPSecure = ( !defined( 'MINSMTP_SMTP_SECURE' ) || false == MINSMTP_SMTP_SECURE )? false : MINSMTP_SMTP_SECURE;
    $phpmailer->SMTPAutoTLS = ( !defined( 'MINSMTP_SMTPAutoTLS' ) || true == MINSMTP_SMTPAutoTLS )? true : false;
    
	if( defined('MINSMTP_SMTP_FROM') ){
        $phpmailer->From = MINSMTP_SMTP_FROM;
    }
	if( defined('MINSMTP_SMTP_NAME') ){
        $phpmailer->FromName   = MINSMTP_SMTP_NAME;
    }
    if( defined( 'MINSMTP_DEBUG' ) )  $phpmailer->SMTPDebug = MINSMTP_DEBUG;
}

function minsmtp_cli_debug_activate( $phpmailer ){
    if ( ! is_object( $phpmailer ) ) {
        $phpmailer = (object) $phpmailer;
    }
    
    $phpmailer->Debugoutput = 'html';
}
/**
 * Send an email to an address of your choice using the settings of SMTP Minimal plugin
 */
function minsmtp_send_test_email( $args = array() ) {
    if( !defined( 'MINSMTP_ON' ) || true !== MINSMTP_ON ) return WP_CLI::warning( 'SMTP Minimal plugin: the plugin is installed but NOT active. Nothing to test' );
    
    if( defined( 'MINSMTP_DEBUG' ) && MINSMTP_DEBUG > 0  ){
        add_action( 'phpmailer_init', 'minsmtp_cli_debug_activate' );
    }
    
    
	if( count( $args ) < 1) return WP_CLI::warning( 'Write the email address after the "test-email" command' );
    if( count( $args ) > 1) return WP_CLI::warning( 'You can send only to one address at time' );
    $to = $args[0];
    if( ! is_email( $to ) ) return WP_CLI::warning( 'This does not appear to be a valid email address' );
    
    $body = " This is the test mail from ".get_bloginfo('name');
    $subject = "Test email from ".get_bloginfo('name');
    
    $test_email = wp_mail( $to, $subject, $body );
    
    if( $test_email ){
        return WP_CLI::success( 'Email has been sent!' );
    }
    else{
         return WP_CLI::warning( 'Email not sent!' );
    }

}

// Add the command.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'test-email', 'minsmtp_send_test_email' );
}