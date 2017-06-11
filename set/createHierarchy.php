<?php
require_once('./../includes/dbinfo.php');
require_once('./../includes/set_hierarchy_tree.php');

$wikiText = "* food
** fruits
** vegetables
*** root
**** carrot
**** turnip
*** pepper";


$rootNode = PFTree::newFromWikiText( $wikiText );
$rootNode = $rootNode->children[0];
$rootNode->createHierarchyTable( 'set_hierarchy_nodes');

?>
