<?php

/*
 * Call me like this:
 * 
 * php bin/php/ezexec.php extension/ezfind/bin/php/update_search_index_mugo.php schedule_add
 * 
 * 
 * actions: see switch statement 
 * 
 */

$is_quiet = false;
$cli = eZCLI::instance();
$handler = new updateSearchIndexMugoHandler( $cli );

if ( !$is_quiet ) $cli->output( 'Starting' );

switch( $options[ 'arguments' ][ 1 ] )
{
	case 'schedule_add':
	{
		if ( !$is_quiet ) $cli->output( 'Adding object to schedule' );
		$handler->schedule_add();
	}
	break;
	
	case 'schedule_clear':
	{
		if ( !$is_quiet ) $cli->output( 'Clearing schedule' );
		$handler->schedule_clear();
	}
	break;
	
	case 'index':
	{
		if ( !$is_quiet ) $cli->output( 'Index objects' );
		$handler->index();
	}
	break;
	
	case 'count_nodes':
	{
		echo eZFunctionHandler::execute( 'content', 'tree_count', array( 'parent_node_id' => 439587,
	                     'class_filter_type'  => 'include',
	                     'class_filter_array' => array( 'csm_article' ),
		                                                                 'limitation'     => array()
		                                                               ) );
		
	}
	break;
	
	default:
	{
		if ( !$is_quiet ) $cli->output( 'Missing action' );
	}
}


class updateSearchIndexMugoHandler
{
	private $adding_index_batch_size = 50;
	private $cli;
	
	public function __construct( $cli )
	{
		$this->cli = $cli;	
	}
	
	public function schedule_add()
	{
		$node_rows = eZFunctionHandler::execute( 'content', 'tree', array( 'parent_node_id'     => 124,
		                                                                   'class_filter_type'  => 'include',
		                                                                   'class_filter_array' => array( 'csm_article' ),
		                                                                   'as_object'          => false,
		                                                                   'limitation'         => array()
		                                                                   //'limit'              => 10
		                                                                 ) );

		if( !empty( $node_rows ) )
		{
			$object_ids = array();
			
			foreach( $node_rows as $index => $row )
			{
				$object_ids[] = $row[ 'contentobject_id' ];
			}
			
			$this->add_pending_actions( $object_ids );
		}
		else
		{
			$this->cli->output( 'Nothing to add. Empty result set.' );
		}
	}
	
	public function schedule_clear()
	{
		$db = eZDB::instance();
		
		$sql = 'DELETE FROM ezpending_actions WHERE action = "index_object_mugo"';

		$db->query( $sql );
	}
	
	public function index()
	{
		$eZSolr = eZSearch::getEngine();
		
		while( true )
		{
			$batch = $this->get_pending_actions();
			
			if( !empty( $batch ) )
			{
				$object_ids = array();
				
				foreach( $batch as $item )
				{
					$item = $item[ 'param' ];
					
					$object_ids[] = $item;
					
					$object = eZContentObject::fetch( $item );
					
					if( $object )
					{
						$this->cli->output( $item );
						
						$eZSolr->addObject( $object, false );
					}
					
					//oom
					unset( $GLOBALS[ 'eZContentObjectContentObjectCache' ] );
					unset( $GLOBALS[ 'eZContentObjectDataMapCache' ] );
					unset( $GLOBALS[ 'eZContentObjectVersionCache' ] );
					unset( $GLOBALS[ 'eZTemplateInstance' ] );
				}
				
				// DEBUG $GLOBALS
				echo strlen( serialize( $GLOBALS ) ) . "\n";
				/*
				echo "---\n";
				foreach( $GLOBALS[ 'eZTemplateInstance' ] as $sub => $value )
				{
					echo $sub . ':' . strlen( serialize( $value) ) . "\n";
				}
				echo "---\n";
				*/
				
				// optimize and commit
				$this->cli->output( 'Commit and optimize' );
				$eZSolr->commit();
				
				$this->remove_pending_actions( $object_ids );
			}
			else
			{
				break;
			}
		}
	}
	
	function remove_pending_actions( $object_ids )
	{
		$db = eZDB::instance();

		$paramInSQL = $db->generateSQLInStatement( $object_ids, 'param' );
		$db->query( "DELETE FROM ezpending_actions WHERE action = 'index_object_mugo' AND $paramInSQL" );
	}
	
	function get_pending_actions()
	{
		$db = eZDB::instance();
		
		$sql = 'SELECT param FROM ezpending_actions WHERE action="index_object_mugo" LIMIT ' . $this->adding_index_batch_size;
		
		return $db->arrayQuery( $sql );
	}
	
	function add_pending_actions( $object_ids )
	{
		$db = eZDB::instance();
		
		$sql_inserts = array();
		foreach( $object_ids as $index => $id )
		{
			if( (int) $id )
			{
				$sql_inserts[] = '( "index_object_mugo", '. time() . ', '. (int) $id . ')';
			}
			
			if( ( count( $sql_inserts ) % 1000 ) == 999 )
			{
				$sql = 'INSERT INTO ezpending_actions ( action, created, param ) VALUES ' . implode( ',',  $sql_inserts );
				$db->query( $sql );
				
				$sql_inserts = array();
			}
		}
		
		if( !empty( $sql_inserts ) )
		{
			$sql = 'INSERT INTO ezpending_actions ( action, created, param ) VALUES ' . implode( ',',  $sql_inserts );
			
			$db->query( $sql );
		}
	}
}

?>
