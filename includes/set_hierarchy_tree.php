<?php
/**
 * A class that defines a tree - and can populate it based on either
 * wikitext or a category structure.
 *
 * @ingroup PFFormInput
 *
 * @author Yaron Koren
 */
class PFTree {
	var $title, $children;
    public $mLeft = 0;
    public $mRight = 0;
    public $mIsList = false;

	function __construct( $curTitle ) {
		$this->title = $curTitle;
		$this->children = array();
	}

	function addChild( $child ) {
		$this->children[] = $child;
	}

	/**
	 * Turn a manually-created "structure", defined as a bulleted list
	 * in wikitext, into a tree. This is based on the concept originated
	 * by the "menuselect" input type in the Semantic Forms Inputs
	 * extension - the difference here is that the text is manually
	 * parsed, instead of being run through the MediaWiki parser.
	 */
	public static function newFromWikiText( $wikitext ) {
		// The top node, called "Top", will be ignored, because
		// we'll set "hideroot" to true.
		$fullTree = new PFTree( 'Top' );
		$lines = explode( "\n", $wikitext );
		foreach ( $lines as $line ) {
			$numBullets = 0;
			for ( $i = 0; $i < strlen( $line ) && $line[$i] == '*'; $i++ ) {
				$numBullets++;
			}
			if ( $numBullets == 0 ) continue;
			$lineText = trim( substr( $line, $numBullets ) );
			$curParentNode = $fullTree->getLastNodeForLevel( $numBullets );
			$curParentNode->addChild( new PFTree( $lineText ) );
		}
		return $fullTree;
	}

	function getLastNodeForLevel( $level ) {
		if ( $level <= 1 || count( $this->children ) == 0 ) {
			return $this;
		}
		$lastNodeOnCurLevel = end( $this->children );
		return $lastNodeOnCurLevel->getLastNodeForLevel( $level - 1 );
	}

    function createHierarchyTable( $tableName )
    {
        $counter = 1;
        $this->generateLeftRight( $counter );

        //preorder traversal and store it into table
        $stack = new SplStack();
        $stack->push( $this );
        while( !$stack->isEmpty() ){
            $node = $stack->pop();
            $this->storeHierarchyInTable( $tableName, $node);

            foreach($node->children as $k=>$child){
                $stack->push( $child );
            }            
        }    
    }
    function generateLeftRight( &$counter )
    {
        $this->mLeft = $counter;
        $counter += 1;
        //visit children
        foreach($this->children as $k=>$child) {
            $child->generateLeftRight( $counter );
        }
        $this->mRight = $counter;
        $counter += 1;
    }
    function storeHierarchyInTable( $tableName, $node ) {
        require('dbinfo.php');
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "INSERT INTO $tableName (name, lft, rgt)
            VALUES ('$node->title', $node->mLeft, $node->mRight);";
            // use exec() because no results are returned
            $conn->exec($sql);

        } catch(PDOException $e) {
            echo $sql . "<br>" . $e->getMessage();
        }
    }
    
    public function generateDrillDown( $dataTable, $hierarchyTable )
    {
        $counter = 1;
        $this->generateLeftRight( $counter );

        $drillDown = "";
        if($this->mIsList == false){
            //Perform preorder traversal
             $stack1 = new SplStack();
             $stack1->push($this);

             while(! $stack1->isEmpty()){                
                $node = $stack1->pop();
                if($node != ")"){
                    $pageCount = $node->findCount( $dataTable, $hierarchyTable , $node->mLeft , $node->mRight );
                    $drillDown .= "$node->title ($pageCount) ";
                    
                    if( count($node->children) > 0 ){
                        $exclusivePageCount = $node->findExclusiveCount( $dataTable, $hierarchyTable , $node->mLeft );
                        $drillDown .= "($node->title only ($exclusivePageCount) ";

                        //pushing children in reverse order for PREORDER
                        $i = count($node->children) - 1;
                        $stack1->push(")");
                        while($i >= 0)
                        {
                            $stack1->push($node->children[$i]);
                            $i = $i - 1;
                        }
                    }
                }
                else {
                    $drillDown .= ") ";
                }
            }
           
        }
        return $drillDown;
    }
    public function findExclusiveCount( $dataTable, $hierarchyTable , $left ) {
        require('dbinfo.php');
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $conn->prepare("SELECT COUNT(*) FROM $dataTable JOIN $hierarchyTable ON $dataTable.hierarchy_id = $hierarchyTable.id WHERE $hierarchyTable.lft = $left"); 
            $stmt->execute();

            // set the resulting array to associative
            $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $results = $stmt->fetchAll();
            return $results[0]['COUNT(*)'];
        }
        catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
    public function findCount( $dataTable, $hierarchyTable , $left , $right ) {
        require('dbinfo.php');
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $conn->prepare("SELECT COUNT(*) FROM $dataTable JOIN $hierarchyTable ON $dataTable.hierarchy_id = $hierarchyTable.id WHERE $hierarchyTable.lft >= $left AND $hierarchyTable.rgt <= $right; "); 
            $stmt->execute();

            // set the resulting array to associative
            $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $results = $stmt->fetchAll();
            return $results[0]['COUNT(*)'];
            
        }
        catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

?>