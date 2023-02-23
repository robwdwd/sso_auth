<?php
require_once('includes/conf/config.inc.php');
require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');

$twig = new \Twig\Environment($loader, [
    //'cache' => '/path/to/compilation_cache',
]);

$twigVars = array();

$twigVars['urlpath'] = $configuration['paths']['urlpath'] ;
$twigVars['full_url'] = $configuration['paths']['url'] ;
$twigVars['file_system_path'] = $configuration['paths']['filesystem'] ;

$twigVars['title'] = $configuration['title'];
$twigVars['logo'] = $configuration['logo'];
$twigVars['company'] = $configuration['company'];
