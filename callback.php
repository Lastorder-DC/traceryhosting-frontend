<?php

require "twitteroauth/autoload.php";
require "credentials.php";

use Abraham\TwitterOAuth\TwitterOAuth;

$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);


$pdo = new PDO('mysql:dbname=traceryhosting;host=127.0.0.1;charset=utf8mb4', 'tracery_php', DB_PASSWORD);

$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


session_set_cookie_params(2678000);
session_start();

function login_failure()
{
  $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
    die('Error! Couldn\'t log in. <a href="//bot.lastorder.xyz">Retry</a>');
}

function login_denied()
{
  $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
    die('Error! Your ID is not allowed to login this site. If you want to make twitter bots like @vending_toaru, use <a href="https://cheapbotsdonequick.com">Cheap Bots, Done Quick!</a>');
}

$request_token = array();
$request_token['oauth_token'] = $_SESSION['oauth_token'];
$request_token['oauth_token_secret'] = $_SESSION['oauth_token_secret'];

if ((isset($_REQUEST['oauth_token']) && $request_token['oauth_token'] !== $_REQUEST['oauth_token']) 
  || (isset($_GET['denied'])) 
  || !isset($_GET['oauth_token'])) {
    // Abort! Something is wrong.



    login_failure();
}
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $request_token['oauth_token'], $request_token['oauth_token_secret']);
$access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $_REQUEST['oauth_verifier']));

if (!(isset($access_token["oauth_token"])) || !(isset($access_token["oauth_token_secret"])))
{
  login_failure();
}

if($access_token["screen_name"] !== "Lastorder_KB" && $access_token["screen_name"] !== "vending_toaru" && $access_token["screen_name"] !== "Uiharu_KB")
{
  login_denied();
}

//todo verify that we succeeded


//var_dump($access_token);

//die();



  $stmt = $pdo->prepare('INSERT INTO traceries (token,token_secret, screen_name, user_id) VALUES(:token, :token_secret, :screen_name, :user_id) ON DUPLICATE KEY UPDATE token=:token2, token_secret=:token_secret2, screen_name=:screen_name2, user_id=:user_id2');

  $stmt->execute(array('token' => $access_token["oauth_token"], 
                       'token_secret' => $access_token["oauth_token_secret"], 
                       'screen_name' => $access_token["screen_name"],
                       'token2' => $access_token["oauth_token"], 
                       'token_secret2' => $access_token["oauth_token_secret"], 
                       'screen_name2' => $access_token["screen_name"],
                       'user_id' => $access_token["user_id"],
                       'user_id2' => $access_token["user_id"]
                      ));




$_SESSION['oauth_token'] = $access_token["oauth_token"]; //this should be this already?

$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
$user_data = $connection->get("users/show", array("screen_name" => $access_token["screen_name"]));

$_SESSION['profile_pic'] = $user_data->profile_image_url; 
$_SESSION['screen_name'] =  $access_token["screen_name"]; 
$_SESSION['user_id'] = $access_token["user_id"];

if (!(isset($user_data) || !(isset($user_data->profile_image_url))))
{
  login_failure(); 
}
if (isset($_SERVER['HTTPS']) &&
    ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
    isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
    $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
  $protocol = 'https://';
}
else {
  $protocol = 'http://';
}
$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
header("Location: $protocol$host$uri");
die();


?>

