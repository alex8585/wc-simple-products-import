<?php
require_once('../wp-load.php');
require_once('AleXmlReader.php');
require_once('AleFastImport.php');

$reader = new AleXmlReader('2.xml');

[$categories, $products] = $reader->parse();

$import = new AleFastImport();
$import->import($categories, $products);
