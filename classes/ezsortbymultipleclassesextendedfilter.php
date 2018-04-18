<?php 
class eZSortbyMultipleClassesExtendedFilter
{ 
	function createSqlParts( $params )
	{
		$return = null;
		
		$attribute_ids = $params[ 'attributes' ];

		if( is_array( $attribute_ids ) && count( $attribute_ids ) )
		{
			$numeric_ids = eZSortbyMultipleClassesExtendedFilter::get_attribute_class_ids( $attribute_ids );
			
			if( is_array( $numeric_ids ) && count( $numeric_ids) )
			{
				$attribute_ids_SQL = implode( ',', $numeric_ids );
				
				$contentAttributeTableAlias = "extended_sorting";
				$sqlTables = ', ezcontentobject_attribute ' . $contentAttributeTableAlias . ' ';
		 
		        $datatypeWhereSQL = "
				                     $contentAttributeTableAlias.contentobject_id = ezcontentobject.id AND
				                     $contentAttributeTableAlias.contentclassattribute_id IN ( $attribute_ids_SQL ) AND
				                     $contentAttributeTableAlias.version = ezcontentobject_name.content_version AND";
		
				$datatypeWhereSQL .= eZContentLanguage::sqlFilter( $contentAttributeTableAlias, 'ezcontentobject' );
				
		
				$return = array( 'tables' => $sqlTables, 'joins'  => $datatypeWhereSQL . ' AND ');
			}
		}
		
		return $return;
	}
	
	private static function get_attribute_class_ids( $attribute_ids )
	{
		$numeric_ids = array();
		
		foreach( $attribute_ids as $attribute_id )
		{
			if( !is_numeric( $attribute_id ) )
			{
				$attribute_id = eZContentObjectTreeNode::classAttributeIDByIdentifier( $attribute_id );
			}
			
			if( is_numeric( $attribute_id ) )
			{
				$numeric_ids[] = $attribute_id;
			}
		}
		
		return $numeric_ids;
	}
}

?>