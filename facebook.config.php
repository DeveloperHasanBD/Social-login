
<?php

require_once 'vendor/autoload.php';
session_start();


$fb = new \Facebook\Facebook([
    'app_id'      => '1280670239278919',
    'app_secret'     => '4d44963a0ffbc967f7bd5060fbed33ca',
    'default_graph_version'  => 'v2.10'
]);

$helper         = $fb->getRedirectLoginHelper();
$permissions    = ['email']; // optional
if (!isset($_GET['scope'])) {

    try {
    
        if (isset($_SESSION['facebook_access_token'])) {
            $accessToken = $_SESSION['facebook_access_token'];
        } else {
            $accessToken = $helper->getAccessToken();
        }
    } catch (Facebook\Exceptions\facebookResponseException $e) {
        // When Graph returns an error
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }
    if (isset($accessToken)) {
        if (isset($_SESSION['facebook_access_token'])) {

            $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
        } else {

            // getting short-lived access token
            $_SESSION['facebook_access_token'] = (string) $accessToken;
            // OAuth 2.0 client handler
            $oAuth2Client = $fb->getOAuth2Client();
            // Exchanges a short-lived access token for a long-lived one
            $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($_SESSION['facebook_access_token']);
            $_SESSION['facebook_access_token'] = (string) $longLivedAccessToken;
            // setting default access token to be used in script
            $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
        }
        // getting basic info about user
        try {
            $profile_request = $fb->get('/me?fields=name,first_name,last_name,email');
            $requestPicture = $fb->get('/me/picture?redirect=false&height=200'); //getting user picture
            // $picture = $requestPicture->getGraphUser();
            $profile = $profile_request->getGraphUser();
            // $fbid = $profile->getProperty('id');           // To Get Facebook ID
            $name       = $profile->getProperty('name');   // To Get Facebook full name
            $surname    = $profile->getProperty('last_name');   // To Get Facebook full name
            $email      = $profile->getProperty('email');    //  To Get Facebook email
            // $fbpic = "<img src='".$picture['url']."' class='img-rounded'/>";

            $tip_users  = $wpdb->prefix . 'tip_users';
            $user       = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tip_users WHERE email = '$email'"));
            // echo '<pre>';
            // print_r($name);
            // print_r($profile);
            // die;
            if ($user) {
                // user sign in 
                session_start();
                unset($_SESSION['facebook_access_token']);
                unset($_SESSION['set_login']);
                $_SESSION['tip_user_token'] = $user->id;
                wp_redirect('https://tipaplayer.jaidul.com/dashboard');
                exit();
            } else {
                // create user 

                $user_params = array(
                    'name'              => $name,
                    'email'             => $email,
                    'provider_type'     => 'facebook',
                    'type'              => 2,
                    'verify_email'      => date('d-m-Y'),
                );
                $wpdb->insert($tip_users, $user_params);
                $user_id = $wpdb->insert_id;
                session_start();
                unset($_SESSION['facebook_access_token']);
                unset($_SESSION['set_login']);
                $_SESSION['tip_user_token'] = $user_id;
                wp_redirect('https://tipaplayer.jaidul.com/dashboard');
                exit();
            }
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            session_destroy();
            // redirecting user back to app login page
            exit;
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
    } else {
        $loginUrl = $helper->getLoginUrl('https://tipaplayer.jaidul.com/', $permissions);
    }
}