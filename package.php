<?php

/**
 * This is the package.xml generator for Concentrate
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright 2010 silverorange
 *
 * This library is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation; either version 2.1 of the
 * License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @category  Tools
 * @package   Concentrate
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$api_version     = '0.0.7';
$api_state       = 'alpha';

$release_version = '0.0.7';
$release_state   = 'alpha';
$release_notes   =
	" * Improved speed of repeated minifications of the \n" .
	"   same file.\n";

$description =
	"This package provides a command-line tool to glob and minify static ".
	"resources for a website using silverorange's Site package. Files are ".
	"combined according to a configuration file passed on the command-line.";

$package = new PEAR_PackageFileManager2();

$package->setOptions(
	array(
		'filelistgenerator'       => 'svn',
		'simpleoutput'            => true,
		'baseinstalldir'          => '/',
		'packagedirectory'        => './',
		'dir_roles'               => array(
			'Concentrate'         => 'php',
			'tests'               => 'test',
			'data'                => 'data',
		),
		'exceptions'              => array(
			'scripts/concentrate' => 'script',
		),
		'ignore'                  => array(
			'package.php',
			'*.tgz',
		),
		'installexceptions'       => array(
			'scripts/concentrate' => '/',
		),
	)
);

$package->setPackage('Concentrate');
$package->setSummary(
	'Tool to glob and minify static resources for websites using '.
	'silverorange\'s Site framework.'
);
$package->setDescription($description);
$package->setChannel('pear.silverorange.com');
$package->setPackageType('php');
$package->setLicense(
	'LGPL License 2.1',
	'http://www.gnu.org/copyleft/lesser.html'
);

$package->setNotes($release_notes);
$package->setReleaseVersion($release_version);
$package->setReleaseStability($release_state);
$package->setAPIVersion($api_version);
$package->setAPIStability($api_state);

$package->addMaintainer(
	'lead',
	'gauthierm',
	'Mike Gauthier',
	'mike@silverorange.com'
);

$package->addReplacement(
	'data/cli.xml',
	'package-info',
	'@package-version@',
	'version'
);

$package->addReplacement(
	'scripts/concentrate',
	'pear-config',
	'@php-bin@',
	'php_bin'
);

$package->addReplacement(
	'Concentrate/CLI.php',
	'package-info',
	'@package-name@',
	'name'
);

$package->addReplacement(
	'Concentrate/CLI.php',
	'pear-config',
	'@data-dir@',
	'data_dir'
);

$package->setPhpDep('5.2.1');
$package->addExtensionDep('optional', 'memcached');

$package->addPackageDepWithChannel(
	'required',
	'YAML',
	'pear.symfony-project.com',
	'1.0.2'
);

$package->addPackageDepWithChannel(
	'required',
	'PEAR',
	'pear.php.net',
	'1.4.0'
);

$package->addPackageDepWithChannel(
	'required',
	'Console_CommandLine',
	'pear.php.net',
	'1.1.0'
);

$package->setPearInstallerDep('1.4.0');
$package->generateContents();

// *nix release
$package->addRelease();
$package->addInstallAs('scripts/concentrate', 'concentrate');

if (   isset($_GET['make'])
	|| (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')
) {
	$package->writePackageFile();
} else {
	$package->debugPackageFile();
}

?>
