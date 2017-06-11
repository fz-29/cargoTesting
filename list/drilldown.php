<?php
require_once('./../includes/dbinfo.php');
require_once('./../includes/hierarchy_tree.php');

    $start = microtime(true);

    $root = HTree::newFromDB( 'hierarchy_nodes' );
    $drillDownText = $root->generateDrillDown('field_name');
    echo $drillDownText;
    
    $time_elapsed_secs = (microtime(true) - $start);
    echo "<br>" . "Script Runtime was : " . $time_elapsed_secs;
?>