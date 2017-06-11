<?php

/**
 * Tree to represent hierarchy allowed values.
 *
 * @author Feroz Ahmad
 * @ingroup Cargo
 */
class HTree
{
    public $mIsList = false;
	public $mChildrenNodes = null;
    public $mSuccessorNodes = null;
    public $mPageCount = 0;
    public $mExclusivePageCount = 0;
    public $mTitle = "";
    public $mId = 0;
    public $mParentId = 0;

    public static $mLookup = null;

	function __construct( $curTitle, $currId, $parentId, $isList = false ) {
		$this->mTitle = $curTitle;
        $this->mId = $currId;
        $this->mParentId = $parentId;
		$this->mChildrenNodes = array();
        $this->mSuccessorNodes = array();
        $this->mLookup = new SplObjectStorage();
        $this->mIsList = $isList;
	}

	function addChildNode( $child ) {
		$this->mChildrenNodes[] = $child;
	}

    function addSuccessor( $successor ) {
		$this->mSuccessorList[] = $successor;
	}

    public static function newFromDB( $tableName , $isList = false){
        require('dbinfo.php');
        $rootNode = null;
        //iterate over all nodes, create their HTree objects
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $conn->prepare("SELECT * FROM $tableName ORDER by id;"); 
            $stmt->execute();

            // set the resulting array to associative
            $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $results = $stmt->fetchAll();

            foreach($results as $k=>$v){
                $node = new HTree( $v['name'], $v['id'], $v['par_id'] , $isList );
                $mLookup[$v['id']] = $node;
            }  
        }   
        catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        //Now link all of them

        foreach($mLookup as $k=>$node){
            $parent = $mLookup[$node->mParentId];
            $parent->mChildrenNodes[] = $node; 
            if($isList == true){
                $parent->mSuccessorNodes = array_merge($parent->mSuccessorNodes, $node->mSuccessorNodes);
                $parent->mSuccessorNodes[] = $node;
            }

            if($node->mParentId == 0){
                $rootNode = $node;
            }
        }
        return $rootNode;
    }

    public function generateDrillDown( $tableName )
    {
        $drillDown = "";
        if($this->mIsList == false){
            //Do a PostOrder DFS and fill up the tree with required counts - and pagecalls (future)
            //Perform Postorder traversal using two stacks
            $stack1 = new SplStack();
            $stack2 = new SplStack();

            $stack1->push($this);

            while(! $stack1->isEmpty())
            {
                $node = $stack1->pop();
                $stack2->push($node);

                //add children of $node to stack1
                foreach($node->mChildrenNodes as $k=>$child){
                    $stack1->push($child);
                }
            }

            //now stack2 contains postorder traversal
             while(! $stack2->isEmpty())
             {
                 $node = $stack2->pop();
                 $node->mExclusivePageCount = $this->findPageCount($tableName , $node->mId);
                 $node->mPageCount = $node->mExclusivePageCount;

                 foreach($node->mChildrenNodes as $k=>$child){
                     $node->mPageCount += $child->mPageCount;
                 }
             }

            // preorder traversal of the tree
             
             $stack1->push($this);
             while(! $stack1->isEmpty()){
                
                $node = $stack1->pop();
                if($node != ")"){
                    $drillDown .= "$node->mTitle ($node->mPageCount) ";
                    
                    if( count($node->mChildrenNodes) > 0 ){
                        $drillDown .= "($node->mTitle only ($node->mExclusivePageCount) ";
                        $i = count($node->mChildrenNodes) - 1;
                        $stack1->push(")");
                        while($i >= 0)
                        {
                            $stack1->push($node->mChildrenNodes[$i]);
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

    function findPageCount( $tableName, $nodeId ) {
        require('dbinfo.php');
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $conn->prepare("SELECT COUNT(*) FROM $tableName WHERE h_id = $nodeId"); 
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
