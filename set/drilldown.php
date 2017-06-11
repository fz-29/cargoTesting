<?php

require_once('./../includes/dbinfo.php');
require_once('./../includes/set_hierarchy_tree.php');


$hierarchyTable = "set_hierarchy_nodes";
$dataTable = "set_field_name";

$wikiText = "* food
** fruits
** vegetables
*** root
**** carrot
**** turnip
*** pepper";


$start = microtime(true);

$rootNode = PFTree::newFromWikiText( $wikiText );
$rootNode = $rootNode->children[0];
$drillDown = $rootNode->generateDrillDown( $dataTable, $hierarchyTable );
echo $drillDown;

$time_elapsed_secs = (microtime(true) - $start);
echo "<br>" . "Script Runtime was : " . $time_elapsed_secs;

?>
