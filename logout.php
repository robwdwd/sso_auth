<?php

include('includes/setup.php');

$auth = new SSOAuth\Auth($configuration['session']['key'], $configuration['session']['name']);

if (!$auth->checkLogin()) {
    if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        $twig_var_arr['message'] = "You are not logged in.";
        $template = $twig->loadTemplate('logout.tpl');
        echo $template->render($twig_var_arr);
    }
    exit();
}

$auth->logout();

if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    $twig_var_arr['message'] = "Log out success.";
    $template = $twig->loadTemplate('logout.tpl');
    echo $template->render($twig_var_arr);
}
