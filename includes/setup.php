<?php
require_once('includes/conf/config.inc.php');
require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');

$twig = new \Twig\Environment($loader, [
    //'cache' => '/path/to/compilation_cache',
]);

$twig_var_arr = array();

$twig_var_arr['urlpath'] = $configuration['paths']['urlpath'] ;
$twig_var_arr['full_url'] = $configuration['paths']['url'] ;
$twig_var_arr['file_system_path'] = $configuration['paths']['filesystem'] ;

$twig_var_arr['title'] = $configuration['title'];
$twig_var_arr['logo'] = $configuration['logo'];
$twig_var_arr['company'] = $configuration['company'];
