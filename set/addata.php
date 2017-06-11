<?php
require_once('./../includes/dbinfo.php');
    $dataTable = "set_field_name";

    $start = microtime(true);

    try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $id = 1;
            
            $count = 0;
            while($id < 1300)
            {
                $hid = rand(1,7);
                $sql = "INSERT INTO $dataTable (hierarchy_id)
                VALUES ($hid)";
                // use exec() because no results are returned
                $conn->exec($sql);
                $count = $count + 1;
                $id = $id + 1;
            }
            echo "$count record(s) created successfully";    
        }
    catch(PDOException $e)
        {
        echo $sql . "<br>" . $e->getMessage();
        }

    $conn = null;
    
    $time_elapsed_secs = (microtime(true) - $start);

    echo "\n" . "Script Runtime was : " . $time_elapsed_secs;
?>