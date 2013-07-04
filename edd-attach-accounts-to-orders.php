<?php
 /**
 * Plugin Name:         Easy Digital Downloads Attach Accounts to Orders
 * Plugin URI:          http://www.chriscct7.com
 * Description:         Attach users to orders
 * Author:              Chris Christoff
 * Author URI:          http://www.chriscct7.com
 *
 * Contributors:        chriscct7
 *
 * Version:             1.0
 * Requires at least:   3.5.0
 * Tested up to:        3.6 Beta 3
 *
 * Text Domain:         edd_ead
 * Domain Path:         /languages/
 *
 * @category            Plugin
 * @copyright           Copyright © 2013 Chris Christoff
 * @author              Chris Christoff
 * @package             EDDEAD
 */

 
 /* Future TODO's 
  * Log system info on run
  * Display system info post run
  * Suppress emails on setups where admin emailed on new user creation 
 */

function operator($createusers = true){
	 masterfunction($createusers,'-1');
	 masterfunction($createusers,'0');
     masterfunction($createusers,null);
}
function masterfunction($createusers = true,$num)
{
	// Log System Info on run in next version
	$timeout = 600;
	if( !ini_get( 'safe_mode' ) ){
	set_time_limit( $timeout );
	}
	$log = new KLogger(dirname(__FILE__), KLogger::INFO);
    // Start Klogger
	if($createusers){
	$log->logDebug('Create users: True');
	}
	else{
	$log->logDebug('Create users: False');
	}
	$log->logNotice('Conversion started');
	$posts = new WP_Query( array(
        'post_type' => 'edd_payment',
        'posts_per_page' => '-1',
        'post_status' => array(
            'publish',
            'complete',
            'refunded' 
        ),
        'meta_key' => '_edd_payment_user_id',
        'meta_value' => $num, 
        'fields' => 'ids' 
    ) );
    $posts = $posts->posts;
	// Create counters
	$newusercounter = 0;
	$attachedcounter = 0;
	$errorcounter = 0;
    if ( $posts == null ) {
        $log->logNotice('No Posts Found'); // Probably want this in UI
    } 
	else {
	if($createusers == false){
		$log->logNotice('Create users is off. Orders whose email does not have a matching user will be logged and skipped');
	}
        foreach ( $posts as $id ) {
            $meta  = get_post_meta( $id );
            // get the value of the user email
            $email = $meta[ '_edd_payment_user_email' ][ 0 ];
            if ( ( $email != null ) && ( validateEmailAddress( $email ) == true ) ) {
                // see if user exists:
                if ( get_user_by( 'email', $email ) ) {
                    // user exists with that email
                    $user = get_user_by( 'email', $email );
					// add correct id to order
						update_post_meta( $id, '_edd_payment_user_id', $user->ID );
						$metaunser                = unserialize( $meta[ '_edd_payment_meta' ][ 0 ] );
						$metaunser[ 'user_id' ]   = $user->ID; //set uid in order to id
						$metasecondid             = unserialize( $metaunser[ 'user_info' ] );
						$metasecondid[ 'id' ]     = $user->ID; //set uid in order to id
						$metasecondid             = serialize( $metasecondid );
						$metaunser[ 'user_info' ] = $metasecondid;
						update_post_meta( $id, '_edd_payment_meta', $metaunser );
						$log->logNotice('User number '.$user->ID.' assigned to order: '.$id);
						$attachedcounter++;
                } 
				else {
					if ($createusers == true){
						// We need to create users
						// First we need a unique username
						$username        = generateUsername( $email ); 
						// Second we need a unique password
						$random_password = wp_generate_password( $length = 12, $include_standard_special_chars = false );
						// Then create user:
						$user            = wp_create_user( $username, $random_password, $email );
						// And then notify the user of their new account
						$emailtouser           = wp_new_user_notification($username, $random_password);
						// Now insert the correct data
						$user            = get_user_by( 'email', $email );
						update_post_meta( $id, '_edd_payment_user_id', "$user->ID" );
						$metaunser                = unserialize( $meta[ '_edd_payment_meta' ][ 0 ] );
						$metaunser[ 'user_id' ]   = $user->ID; //set uid in order to id
						$metasecondid             = unserialize( $metaunser[ 'user_info' ] );
						$metasecondid[ 'id' ]     = $user->ID; //set uid in order to id
						$metasecondid             = serialize( $metasecondid );
						$metaunser[ 'user_info' ] = $metasecondid;
						update_post_meta( $id, '_edd_payment_meta', $metaunser );
						$log->logNotice('User number '.$user->ID.' assigned to order: '.$id);
						$newusercounter++;
						$attachedcounter++;
					}
					else{
					$log->logNotice('User not found for order: '.$id);
					$errorcounter++;
					}
                }
            } 
			else {
                // log email is not good. Likely a typo on the email.
                if ( ( $email == null ) ) {
					$log->logNotice('Email on Order: '.$id. 'is empty.');
					$errorcounter++;
                } 
				else {
                    $log->logNotice('Email on Order: '.$id. 'is not a valid email.');
					$errorcounter++;
                }
            }
        }
    }
	$log->logNotice('Conversion complete! ');
	$log->logNotice('Number of created users: '.$newusercounter);
	$log->logNotice('Number of orders attached to users: '.$attachedcounter);
	$log->logNotice('Number of errors: '.$errorcounter);
	$log->logNotice('-----------------------');
}
function validateEmailAddress( $email )
{
    //Perform a basic syntax-Check
    //If this check fails, there's no need to continue
    if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
        return false;
    }
    //extract host
    list( $user, $host ) = explode( "@", $email );
    //check, if host is accessible
    if ( !checkdnsrr( $host, "MX" ) && !checkdnsrr( $host, "A" ) ) {
        return false;
    }
    return true;
    // valid returns true, invalid false
}
function generateUsername( $email )
{
    // Lets remove everything after the @
    // Example: chriscct7@gmail.com becomes chriscct7
    $username = array_shift( explode( '@', $email ) );
    if ( !username_exists( $username ) ) {
        // if right off the bat we have a good username, return it
        return $username;
    }
    $counter = 1;
    // We need a copy of the username so that the username doesn't become:
    // username123456789101112.....
    $copy    = $username;
    do {
        $username = $copy;
        $username = $username . $counter;
        $counter++;
    } 
	while ( username_exists( $username ) ); // While a user exists with this username, run the loop again
    return $username;
	}


function eaato_require_edd() {
	require_once 'edd-functions.php';
	require_once 'lib/KLogger.php';
	is_edd_active('1.6');
}
add_action('init', 'eaato_require_edd');
function eaato_menu() {
	add_submenu_page( 'edit.php?post_type=download','Attach Accounts to Orders', 'Attach Accounts to Orders', 'manage_options', 'eaato', 'eaato_page' );
}
add_action('admin_menu', 'eaato_menu');

function eaato_page() { ?>
	<div class="wrap">
	 	<h3><?php _e('System Check: '); ?></h3>
			<table class="form-table">
				<tbody>
					<tr valign="top">	
						<th scope="row" valign="top">
							<?php _e('Safe Mode'); ?>
						</th>
						<td>
						<?php $test = ini_get('safe_mode');
							  $test =!($test) ?  'True' : 'False'; // Pass: Fail
					    ?>
							<?php if ($test){ ?>
							<span style="color:green;"><?php _e('Pass'); ?></span>
							<?php } else { ?>
							<span style="color:red;"><?php _e('Fail'); ?></span>
							<?php }?>
						</td>
					</tr>
					<tr valign="top">	
						<th scope="row" valign="top">
							<?php _e('PHP Version > 5.3?'); ?>
						</th>
						<td>
						<?php $test  = version_compare(phpversion(), '5.3', '>=');
						$test = ($test >= 0) ? true : false;
					    ?>
							<?php if ($test){ ?>
							<span style="color:green;"><?php _e('Pass'); ?></span>
							<?php } else { ?>
							<span style="color:red;"><?php _e('Fail'); ?></span>
							<?php }?>
						</td>
					</tr>
					<tr valign="top">	
						<th scope="row" valign="top">
							<?php _e('WP Version > 3.4?'); ?>
						</th>
						<td>
						<?php  $test = get_bloginfo( 'version' );
							   $test  = version_compare($test, '3.4', '>=');
							   $test = ($test >= 0) ? 'True' : 'False';
					    ?>
							<?php if ($test){ ?>
							<span style="color:green;"><?php _e('Pass'); ?></span>
							<?php } else { ?>
							<span style="color:red;"><?php _e('Fail'); ?></span>
							<?php }?>
						</td>
					</tr>
					<tr valign="top">	
						<th scope="row" valign="top">
							<?php _e('Memory Limit:'); ?>
						</th>
						<td>
						<?php  $test = ini_get( 'memory_limit' );
					    ?>
							<span style="color:blue;"><?php _e($test); ?></span>
							<span style="color:blue;"><?php _e('- Not checked but may cause issues if too low'); ?></span>
						</td>
					</tr>
					<tr valign="top">	
						<th scope="row" valign="top">
							<?php _e('Max Time Limit:'); ?>
						</th>
						<td>
						<?php  $test = ini_get('max_execution_time');
					    ?>
							<span style="color:blue;"><?php _e($test); ?></span>
						    <span style="color:blue;"><?php _e('- Not checked but may cause issues if too low'); ?></span>
						</td>
					</tr>
				</tbody>
			</table>
			<h3><?php _e('Run Program: '); ?></h3>
			<form method="post" action="">
			<p><span>The plugin will match the emails of previous orders, with already registered users, and assign those users to the product.
			However, if there is no registered user with the email on the order, the program can automatically create an account for that user,
			and assign the order to the new user. The user in this case will be sent an email automatically with their login details. If you do not choose to allow the program to create new user accounts, the orders with no matching registered users will be logged in a file for review, inside this plugin's folder. <br /><br />
			
			In addition, other errors, warnings and messages will appear in this file. I highly recommend backing up the postmeta table of your database before proceeding to use this plugin. You can use a WP backup solution, like backupbuddy, or your host's PHPMyAdmin (for CPanel accounts) Operations tab, to back up this table. We are not responsible for any data loss caused by this plugin.<br /><br />
			
			In addition, we recommend viewing the log file after running this program found in the plugin's folder. The process can take up to 6 minutes, during which we strongly recommend not navigating around the backend of the WP site.<br /><br />
			
			Before running this program, please check the system info above. If there are any FAILS, do not run this program. Make sure that you only click the button 1 time (DO NOT DOUBLE CLICK), and only 1 time. You will be notified when the plugin is finished (that will be when a javascript alert appears).<br />
			</span></p>
			<input type="checkbox" name="tos" value="tos"> I agree I have read the above, and waive Chriscct7 of any liability. I agree I have been advised to backup my database prior to usage. <br /><br />
			<?php submit_button('Run And Create Accounts','primary','submitwaccs',false); ?>
			<?php submit_button('Run And Do Not Create Accounts','primary','submitwoaccs',false); ?>
			</form>
			Status:
			<?php
			if (!isset($_POST['tos']) && (isset($_POST['submitwaccs']) || isset($_POST['submitwoaccs']))){
				echo '<script type="text/javascript">alert("You must accept TOS!");</script>'; 
			}
			else{
			if( isset($_POST['submitwaccs'])){
				operator(true);
				echo '<script type="text/javascript">alert("Done!");</script>'; 
			}
			if( isset($_POST['submitwoaccs'])){
				operator(false);
				echo '<script type="text/javascript">alert("Done!");</script>'; 
			}
			}
}