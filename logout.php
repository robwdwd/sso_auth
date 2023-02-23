<?php

include 'includes/setup.php';

$auth = new SSOAuth\Auth($configuration['session']['key'], $configuration['session']['name']);

if (!$auth->checkLogin()) {
    if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
        header('Location: '.$_SERVER['HTTP_REFERER']);
    } else {
        $twigVars['message'] = 'You are not logged in.';
        $template = $twig->load('logout.html.twig');
        echo $template->render($twigVars);
    }
    exit;
}

$auth->logout();

if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    header('Location: '.$_SERVER['HTTP_REFERER']);
} else {
    $twigVars['message'] = 'Log out success.';
    $template = $twig->load('logout.html.twig');
    echo $template->render($twigVars);
}
