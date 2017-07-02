<?php

require_once __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL);

$pearrc = '/so/sites/dutch-bulbs/work-gauthierm' .
	DIRECTORY_SEPARATOR . 'pear' .
	DIRECTORY_SEPARATOR . 'pearrc';

$fileFinder   = new Concentrate_DataProvider_FileFinderPear($pearrc);
$concentrator = new Concentrate_Concentrator();
$concentrator->loadDataFiles($fileFinder->getDataFiles());
$concentrator->loadDataFile('/so/sites/dutch-bulbs/work-gauthierm/dependencies/combines.yaml');
$concentrator->loadDataFile('/so/sites/dutch-bulbs/work-gauthierm/dependencies/dutch-bulbs.yaml');

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
