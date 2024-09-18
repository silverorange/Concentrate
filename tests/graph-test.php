<?php

require_once __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL);

$graph = new Concentrate_Graph();

$swat = new Concentrate_Graph_Node($graph, 'Swat');
$store = new Concentrate_Graph_Node($graph, 'Store');
$swatYui = new Concentrate_Graph_Node($graph, 'SwatYui');
$admin = new Concentrate_Graph_Node($graph, 'Admin');
$site = new Concentrate_Graph_Node($graph, 'Site');
$xmlRpcAjax = new Concentrate_Graph_Node($graph, 'XML_RPCAjax');
$blorg = new Concentrate_Graph_Node($graph, 'Blorg');
$pinhole = new Concentrate_Graph_Node($graph, 'Pinhole');

$swat->connectTo($swatYui);
$store->connectTo($swat);
$store->connectTo($site);
$site->connectTo($xmlRpcAjax);
$site->connectTo($swat);
$admin->connectTo($swat);
$admin->connectTo($site);
$blorg->connectTo($swat);
$blorg->connectTo($site);
$blorg->connectTo($admin);
$pinhole->connectTo($swat);
$pinhole->connectTo($site);
$pinhole->connectTo($blorg);

$sorter = new Concentrate_Graph_TopologicalSorter();
$sorted = $sorter->sort($graph);

echo PHP_EOL;
echo $graph;
echo PHP_EOL;
echo 'Sorted: ', PHP_EOL;
foreach ($sorted as $k => $nodes) {
    echo ' - ', $k , ' => ', implode(', ', $nodes), PHP_EOL;
}
echo PHP_EOL;
