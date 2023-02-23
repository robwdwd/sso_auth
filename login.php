<?php

use SSOAuth\AuthException;

include 'includes/setup.php';

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
    // User has come to the login page directly, display success.
    //
    $template = $twig->load('loggedin.html.twig');
    echo $template->render(
        [
            'logouturl' => $configuration['paths']['logouturl'],
        ]
    );
    exit;
}

// Add all the radius servers
//
foreach ($configuration['auth_providers']['radius']['servers'] as $hostname => $server) {
    $auth->addRadiusServer($hostname, $server['port'], $server['secret'], $server['timeout'], $server['max_tries']);
}

// $auth->setProviderOrder(array('radius', 'local', 'tacacs'));

$errors = [];
$formTokenKey = $auth->getFormTokenKey();

if (isset($_POST[$formTokenKey])) {
    // Flag to set if an error in the form is found.
    $foundError = false;
    $twigVars['foundError'] = false;

    $validator = new SSOAuth\Validate($_POST, true);
    $validator->setFormTokenKey($formTokenKey);

    $validator->validateLogin();

    if ($validator->hasError()) {
        $foundError = true;
        $errors = array_merge($errors, $validator->errorMessage());
    }

    $_POST = $validator->get();

    if (!$foundError) {
        try {
            $auth->login($_POST['username'], $_POST['password']);

        } catch (AuthException $e) {
            $errors[] = $e->getMessage();
            $foundError = true;
        }
    }

    if ($foundError) {
        $twigVars['foundError'] = $foundError;
    } else {
        header('Location: '.$_POST['target']);
        exit;
    }
}

// Clear any session and start new one.
//
if (PHP_SESSION_ACTIVE == session_status()) {
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
$twigVars['form_token'] = $auth->getFormToken();
$twigVars['formTokenKey'] = $formTokenKey;

$_SESSION[$formTokenKey] = $twigVars['form_token'];

// Assign any errors to twig variables

$twigVars['errors'] = $errors;

// Set the Target URL

if (isset($_POST['target']) && !empty($_POST['target'])) {
    $twigVars['x_target'] = $_POST['target'];
} elseif (isset($_GET['url']) && !empty($_GET['url'])) {
    $twigVars['x_target'] = $_GET['url'];
} else {
    $twigVars['x_target'] = $configuration['paths']['loginurl'];
}

// Display the page
//

$template = $twig->load('login.html.twig');
echo $template->render($twigVars);
