<?php
include('includes/setup.php');

$auth = new SSOAuth\Auth(
    $configuration['session']['key'],
    $configuration['session']['name'],
    $configuration['session']['domain'],
    $configuration['session']['path'],
    $configuration['session']['idletime'],
    $configuration['session']['lifetime'],
    $configuration['session']['secure']
);

if ($auth->checkLogin()) {
    // if the user comes to the login page from another page go there.
    if (isset($_SERVER['HTTP_X_TARGET']) && !empty($_SERVER['HTTP_X_TARGET'])) {
        if ($_SERVER['HTTP_X_TARGET'] !== $configuration['paths']['loginurl']) {
            header('Location: ' . $_SERVER['HTTP_X_TARGET']);
            exit();
        }
    }

    // User has come to the login page directly, display success.
    //
    $twig_var_arr['logouturl'] = $configuration['paths']['logouturl'];
    $template = $twig->loadTemplate('loggedin.tpl');
    echo $template->render($twig_var_arr);
    exit();
}

// Add all the radius servers
//
foreach ($configuration['auth_providers']['radius']['servers'] as $hostname => $server) {
    $auth->addRadiusServer($hostname, $server['port'], $server['secret'], $server['timeout'], $server['max_tries']);
}

//$auth->setProviderOrder(array('radius', 'local', 'tacacs'));

$errors = array();
$form_token_key = $auth->getFormTokenKey();

if (isset($_POST['username'])) {
    // Flag to set if an error in the form is found.
    $found_error = false;
    $twig_var_arr['found_error'] = false;

    // Check the form Token is valid
    if (!isset($_SESSION[$form_token_key])) {
        $errors[] = "Form token not found, did you load the login page in another tab?";
        $found_error = true;
    } else {
        // Check the form Token is valid
        if ($_POST[$form_token_key] !== $_SESSION[$form_token_key]) {
            $errors[] = "Invalid form submission: Tokens don't match";
            $found_error = true;
        }
    }

    // Check all fields have been filled in correctly
    //
    if (empty($_POST['password']) || empty($_POST['username'])) {
        $errors[] = 'You must enter both your username and password.';
        $found_error = true;
    }

    if (!$found_error) {
        $auth->login($_POST['username'], $_POST['password']);

        if ($auth->hasError()) {
            $errors[] = $auth->errorMessage();
            $found_error = true;
        }
    }

    if ($found_error) {
        $twig_var_arr['found_error'] = $found_error;
    } else {
        header('Location: ' . $_POST['target']);
        exit();
    }
}

// Clear any session and start new one.
//
if (session_status() == PHP_SESSION_ACTIVE) {
    session_destroy();
}

session_name($configuration['session']['name']);
session_set_cookie_params(
    $configuration['session']['lifetime'],
    $configuration['session']['path'],
    $configuration['session']['domain'],
    $configuration['session']['secure']
);


session_start();

// Set a form Token
$twig_var_arr['form_token'] = $auth->getFormToken();
$twig_var_arr['form_token_key'] = $form_token_key;

$_SESSION[$form_token_key] = $twig_var_arr['form_token'];

// Assign any errors to twig variables

$twig_var_arr['errors'] = $errors;

// Set the Target URL

if (isset($_POST['target'])) {
    $twig_var_arr['x_target'] = $_POST['target'];
} else {
    $twig_var_arr['x_target'] = $_SERVER['HTTP_X_TARGET'];
}

// Display the page
//
$template = $twig->loadTemplate('login.tpl');
echo $template->render($twig_var_arr);
