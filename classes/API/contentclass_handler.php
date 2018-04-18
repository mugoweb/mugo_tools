<?php

class ContentClass_Handler
{
    /**
     * Create a new object of the specified content class
     * 
     * Create a new object of the specified content class, passing the
     * data map attributes, with the option to modify the object's
     * publish date
     * 
     * Example.
     * <pre><code>
     * <?php
     *
     * $parent_node_id = 9220;
     * 
     * // info according to your ez class
     * $attributes = array(
     *      'id_str'        => 285784540121792512
     *      , 'created_on'  => 1356971318
     *      , 'text'        => 'eZ Americas conference in Florida in late-January and early-February: details coming together! http://t.co/7evXWLrf #ezpublish'
     *   );
     * 
     * ContentClass_Handler::create( $attributes, $parent_node_id, 'tweet', $attributes['created_on'] );
     * 
     * ?>
     * </code></pre>
     * 
     * @param array  $attributes                Attributes to populate in the new object
     * @param int    $parent_node_id            Parent node ID
     * @param string $content_class_identifier  Content class identifier
     * @param boolean|int   $publishedOn        Optional Passes the published date of the new object in a Unix timestamp
     * @param boolean|string $language          Optional Specifies a locale code in which to create the object
     * 
     * @return int  Return the node id of the parent node
     */
    public static function create( $attributes, $parent_node_id, $content_class_identifier, $publishedOn = false, $language = false, $remoteID = false )
	{
		$return = false;

		if( $parent_node_id && $content_class_identifier )
		{
            // Try to do the object creation up to 5 times if there's a deadlock error
            // This is supported in eZ Publish 4.5+ when DB exception handling was added
            $maxRetries = 5;
            $numRetries = 0;
            $continueTrying = true;
            while( $continueTrying )
            {
                try
                {
                // Post eZP 4.3 on cluster setup to force eZP to query the master on the currentVersion() and similar calls
                $db = eZDB::instance();
                $db->setErrorHandling( eZDB::ERROR_HANDLING_EXCEPTIONS );
                $db->begin();
                $ez_obj = CCFunctionCollection::create_ezobject( $content_class_identifier, $parent_node_id, $language, $remoteID );
        
                $obj_version = $ez_obj->currentVersion();
        
                $data_map = $obj_version->attribute( 'data_map' );
                
                foreach( $attributes as $key => $value )
                {
                    if( $data_map[ $key ] instanceof eZContentObjectAttribute )
                    {
                        $data_map[ $key ]->fromString( $value );
                        $data_map[ $key ]->store();
                    }
                }
            
                // Set the object's publish date
                if( $publishedOn )
                {
                    $ez_obj->setAttribute( 'published', $publishedOn );
                    $ez_obj->store();
                }
            
                eZOperationHandler::execute(
                                            'content',
                                            'publish',
                                            array(
                                                  'object_id' => $ez_obj->attribute( 'id' ),
                                                  'version'   => $obj_version->attribute( 'version' ),
                                                 )
                                           );
        
                //refetch the object
                $ez_obj = eZContentObject::fetch( $ez_obj->attribute( 'id' ) );	
            
                $return = $ez_obj->attribute( 'main_node_id' );
                $db->commit();
                $continueTrying = false;
                }
                // Catch a deadlock error and retry
                catch( eZDBException $e )
                {
                    if( $e->getCode() == 1213 )
                    {
                        if( $numRetries < $maxRetries )
                        {
                            $numRetries++;
                            eZDebug::writeError( "Logging database error and retrying object creation (attempt #$numRetries): " . $e->getCode() . ' - message: ' . $e->getMessage(), __CLASS__ );
                        }
                        else
                        {
                            eZDebug::writeError( "Max number of deadlock attempts reached ($maxRetries); giving up", __CLASS__ );
                            eZDebug::writeError( 'Parent node ID: ' . $parent_node_id, __CLASS__ );
                            eZDebug::writeError( 'Content class identifier: ' . $content_class_identifier, __CLASS__ );
                            eZDebug::writeError( 'Attribute info: ' . var_export( $attributes, true ), __CLASS__ );
                            $continueTrying = false;
                        }
                    }
                    // If this is not a deadlock error, re-throw the exception
                    else
                    {
                        throw $e;
                    }
                }
            }
		}
		return $return;
	}

    /*
     * Update an existing content object
     * If you have multiple languages, you must create a new version
     *
    */
    public static function update( $attributes, $ezobject, $newVersion = false, $publishedOn = false, $executePublish = true, $languageCode = false )
	{
		$return = false;
		
		if( $ezobject && is_array( $attributes ) )
		{
            if( $newVersion )
            {
                if( $languageCode )
                {
                    $objectVersion = $ezobject->createNewVersionIn( $languageCode, $languageCode );
                }
                else
                {
                    $objectVersion = $ezobject->createNewVersion();                    
                }
            }
            else
            {
                $objectVersion = $ezobject->currentVersion();
            }
	
			$data_map = $objectVersion->attribute( 'data_map' );
			
			foreach( $attributes as $key => $value )
			{
				if( $data_map[ $key ] instanceof eZContentObjectAttribute )
				{
					$data_map[ $key ]->fromString( $value );
					$data_map[ $key ]->store();
				}
			}
		
            // Set the object's publish date
            if( $publishedOn )
            {
                $ezobject->setAttribute( 'published', $publishedOn );
                $ezobject->store();
            }
        
            if( $executePublish )
            {
			eZOperationHandler::execute(
			                            'content',
			                            'publish',
			                            array(
			                                  'object_id' => $ezobject->attribute( 'id' ),
			                                  'version'   => $objectVersion->attribute( 'version' ),
			                                 )
			                           );
            }
	
			$return = $ezobject->attribute( 'main_node_id' );
		}
		
		return $return;
	}

	public static function remove( $ezobject )
	{
		$main_node_id = $ezobject->attribute( 'main_node_id' );
		return eZContentObjectTreeNode::removeSubtrees( array( $main_node_id ), false );
	}

	public static function get_parent_folder( $node, $folder_name )
	{
		$return = $node;

		$folder_node = eZFunctionHandler::execute( 'content', 'list', array( 'parent_node_id'     => $node->attribute( 'node_id' ),
		                                                                     'class_filter_type'  => 'include',
		                                                                     'class_filter_array' => array( 'folder' ),
		                                                                     'attribute_filter'   => array( array( 'name', '=', $folder_name ) ),
		                                                                     'limitation'         => array()
		) );

		if( ! count( $folder_node ) )
		{
			// Create transaction_fee folder

			$folder_obj = CCFunctionCollection::create_ezobject( 'folder', $node->attribute( 'node_id' ) );

			$data_map = $folder_obj->attribute( 'data_map' );
				
			$data_map['name']->fromString( $folder_name );
			$data_map['name']->store();
				
			$data_map['short_name']->fromString( $folder_name );
			$data_map['short_name']->store();
				
			$obj_version = $folder_obj->currentVersion();

			eZOperationHandler::execute(
			                            'content',
			                            'publish',
			                            array(
			                                  'object_id' => $folder_obj->attribute( 'id' ),
			                                  'version'   => $obj_version->attribute( 'version' ),
			                            )
			);

			// refetch it
			$folder_node = eZFunctionHandler::execute( 'content', 'list', array( 'parent_node_id'     => $node->attribute( 'node_id' ),
			                                                                     'class_filter_type'  => 'include',
			                                                                     'class_filter_array' => array( 'folder' ),
			                                                                     'attribute_filter'   => array( array( 'name', '=', $folder_name ) ),
			                                                                     'limitation'         => array() 
			) );
		}
		
		$folder_node = $folder_node[0];
		
		return $folder_node;
	}
}
?>