<?php


require_once 'vendor/autoload.php';
// init configuration
$clientID = '253392017960-mgnt19kl700c4qq4br1ejm9nbsu2s1da.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'https://tipaplayer.jaidul.com/google-redirect-page';

// create Client Request to access Google API
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

// echo $client->createAuthUrl();
if (isset($_GET['scope']) && isset($_GET['authuser'])) {
    
    $site_url = site_url();
    global $wpdb;
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token['access_token']);

    // get profile info
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    // echo '<pre>';
    // print_r($google_account_info);
    // die;
    $name       = $google_account_info['givenName'];
    $surname    = $google_account_info['familyName'];
    $full_name  = $google_account_info['name'];
    $email      = $google_account_info['email'];
    $picture    = $google_account_info['picture'];
    $tip_users  = $wpdb->prefix . 'tip_users';
    $user       = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tip_users WHERE email = '$email'"));

    if ($user) {
        // user sign in 
        session_start();
        $_SESSION['tip_user_token'] = $user->id;
        wp_redirect($site_url . '/dashboard');
        exit();
    } else {
        // create user 
        $user_params = array(
            'name'              => $full_name,
            'email'             => $email,
            'provider_type'     => 'google',
            'type'              => 2,
            'verify_email'      => date('d-m-Y'),
        );
        $wpdb->insert($tip_users, $user_params);
        $user_id = $wpdb->insert_id;
        session_start();
        $_SESSION['tip_user_token'] = $user_id;
        wp_redirect($site_url . '/dashboard');
        exit();
    }
}
