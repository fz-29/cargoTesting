<?php
require_once('./../includes/dbinfo.php');

    $start = microtime(true);

    echo "<table style='border: solid 1px black;'>";
    echo "<tr><th>Id</th><th>Name</tr>";

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

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $conn->prepare("SELECT field_name.id, hierarchy_nodes.name FROM hierarchy_nodes JOIN field_name ON field_name.h_id = hierarchy_nodes.id WHERE name = 'fruits' "); 
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