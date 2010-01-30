<?php

error_reporting(E_ALL);
set_include_path(get_include_path() . ':' . dirname(dirname(__FILE__)));

require_once 'Concentrate/Concentrator.php';
require_once 'Concentrate/DataProvider/FileFinderPear.php';

$pearrc = '/so/sites/dutch-bulbs/work-gauthierm' .
	DIRECTORY_SEPARATOR . 'pear' .
	DIRECTORY_SEPARATOR . 'pearrc';

$fileFinder   = new Concentrate_DataProvider_FileFinderPear($pearrc);
$concentrator = new Concentrate_Concentrator();
$concentrator->loadDataFiles($fileFinder->getDataFiles());

print_r(
	$concentrator->getConflicts(
		array(
			'packages/van-bourgondien/styles/category-page.css',
			'packages/van-bourgondien/styles/product-page.css',
			'packages/van-bourgondien/styles/category.css',
			'packages/van-bourgondien/styles/product.css',
		)
	)
);
