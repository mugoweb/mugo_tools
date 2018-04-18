<?php

class CCFunctionCollection
{

	public static function create_ezobject( $class_idenfier, $parent_node_id, $language = false, $remoteID = false )
	{
		$eZClass = eZContentClass::fetchByIdentifier( $class_idenfier );

		if( $eZClass )
		{
            if( $language )
            {
                $eZ_object = $eZClass->instantiateIn( $language, false, 0, false );
            }
            else
            {
                $eZ_object = $eZClass->instantiate( false, 0, false );
            }
            if( $remoteID )
            {
                $eZ_object->setAttribute( 'remote_id', $remoteID );
            }
			$eZ_object->store();

			// Assign object to node
			$nodeAssignment = eZNodeAssignment::create(
			    array(
			        'contentobject_id'		=> $eZ_object->attribute( 'id' ),
			        'contentobject_version'	=> 1,
			        'parent_node' => $parent_node_id,
			        'is_main' => 1
			        )
			    );

			if( $nodeAssignment )
			{
				$nodeAssignment->store();
			}
			else
			{
				die('could not assign the object to a node');
			}
		}
		
		return $eZ_object;
	}


	public static function get_php_date_format($format)
	{
		$datetime_config = eZINI::instance( 'datetime.ini' );
		$available_formats = $datetime_config->variable('ClassSettings','Formats');
		
		if(array_key_exists($format,$available_formats))
		{
			$format_string = str_replace('%','',$available_formats[$format]);
		}
		else
		{
			$format_string = str_replace('%','',$format);
		}
		return $format_string;
	} 
}
?>