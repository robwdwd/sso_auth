<?php
require_once('includes/conf/config.inc.php');
require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');

$twig = new \Twig\Environment($loader);

$twigVars = array();

$twigVars['urlpath'] = $configuration['paths']['urlpath'] ;
$twigVars['full_url'] = $configuration['paths']['url'] ;
$twigVars['file_system_path'] = $configuration['paths']['filesystem'] ;
