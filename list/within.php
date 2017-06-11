<?php
require_once('./../includes/dbinfo.php');

    $start = microtime(true);

    class TableRows extends RecursiveIteratorIterator { 
        function __construct($it) { 
            parent::__construct($it, self::LEAVES_ONLY); 
        }

        function current() {
            return "<td style='width:150px;border:1px solid black;'>" . parent::current(). "</td>";
        }

        function beginChildren() { 
            echo "<tr>"; 
        } 

        function endChildren() { 
            echo "</tr>" . "\n";
        } 
    }

    $hierarchy_node = 'root';
    $required_nodes = array();
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $conn->prepare("SELECT * FROM hierarchy_nodes WHERE hierarchy_nodes.name LIKE '$hierarchy_node';"); 
        $stmt->execute();

        // set the resulting array to associative
        $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll();
        
        //Do a BFS search to find the successors of the required hierarchy node for within operation
        $search_queue = new SplQueue();
        $search_queue->push($result[0]['id']);
        while(!$search_queue->isEmpty())
        {
            $id = $search_queue->pop();
            array_push($required_nodes, $id);
            
            $stmt = $conn->prepare("SELECT * FROM hierarchy_nodes WHERE hierarchy_nodes.par_id = $id;"); 
            $stmt->execute();
            $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $results = $stmt->fetchAll();
            foreach($results as $k=>$v)
            {
                $search_queue->push($v['id']);
            }
        }

    }
    catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    // The nodes to be looked out are found till now.

    $where_clause = "";
    $counter = 1;
    foreach($required_nodes as $k=>$v){
        $where_clause .= "h_id = $v ";
        if($counter < count($required_nodes)){
            $where_clause .= "OR ";
        }
        $counter = $counter + 1;
    }


    echo "<table style='border: solid 1px black;'>";
    echo "<tr><th>Id</th><th>Name</tr>";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $conn->prepare("SELECT field_name.id, hierarchy_nodes.name FROM hierarchy_nodes JOIN field_name ON field_name.h_id = hierarchy_nodes.id WHERE ". $where_clause); 
        $stmt->execute();

        // set the resulting array to associative
        $result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
        foreach(new TableRows(new RecursiveArrayIterator($stmt->fetchAll())) as $k=>$v) { 
            echo $v;
        }
    }
    catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
    $conn = null;
    echo "</table>";

    $time_elapsed_secs = (microtime(true) - $start);

    echo "\n" . "Script Runtime was : " . $time_elapsed_secs;
?>