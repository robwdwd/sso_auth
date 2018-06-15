<?php


require_once('includes/conf/config.inc.php');
require_once $configuration['paths']['filesystem'] . '/vendor/autoload.php';

$loader = new Twig_Loader_Filesystem($configuration['paths']['filesystem'] . '/templates');

$twig = new Twig_Environment($loader);

$twig_var_arr = array();

$twig_var_arr['urlpath'] = $configuration['paths']['urlpath'] ;
$twig_var_arr['full_url'] = $configuration['paths']['url'] ;
$twig_var_arr['file_system_path'] = $configuration['paths']['filesystem'] ;

$twig_var_arr['title'] = $configuration['title'];
$twig_var_arr['logo'] = $configuration['logo'];
$twig_var_arr['company'] = $configuration['company'];
