<?php
class ObjectRelationFilter
/*
 * This filter can be use on object relation (singular) and object relation list attributes
 * Use like this:
 * 'extended_attribute_filter', hash( 'id', 'ObjectRelationFilter', 'params', array( 'and', 'article/related_news', '22934' ) )
 * You can use "or" and multiple pairs of attribute ID/identifier and object ID
 * The object ID can be an array as well
*/
{
    function __construct()
    {}

    function createSqlParts( $params )
    {
        $sqlTables= ',ezcontentobject_link AS t0 ';

        $sqlJoins = ' ezcontentobject_tree.contentobject_id = t0.from_contentobject_id AND ezcontentobject_tree.contentobject_version = t0.from_contentobject_version AND ';

        $sqlSorts = null;

        // first optional param element should be either 'or' or 'and'
        if(!is_numeric($params[0]))
        {
            $matchAll = !(array_shift($params) === 'or');
        }
        else
        {
            $matchAll = true;
        }
        
        // object matching conditions are collected here
        $sqlCondArray = array();
        
        // remaining params are pairs of attribute id and object id which should be matched.
        // object id can also be an array of object ids, in that case the match is on either object id.
        $t = 0;
        while(sizeof($params) > 1) {

            $table = 't'.$t;

            $attribute_id = array_shift($params);
            if( !is_numeric( $attribute_id ) )
            {
                $attribute_id = eZContentClassAttribute::classAttributeIDByIdentifier( $attribute_id );
            }
            else
            {
            	$attribute_id = (int)$attribute_id;
            }

            $relatedobject_id = array_shift($params);

            if(is_array($relatedobject_id))
            {
                $sqlCond = $table.'.to_contentobject_id IN('.join(',', $relatedobject_id).')';
            }
            else
            {
                $sqlCond = $table.'.to_contentobject_id='.(int)$relatedobject_id;
            }
            $sqlCondArray[] = $table.'.contentclassattribute_id='.$attribute_id.' AND '.$sqlCond;

            if($t++ > 0)
            {
                $sqlTables .= ',ezcontentobject_link AS '.$table;
                $sqlJoins .= ' ezcontentobject_tree.contentobject_id='.$table.'.from_contentobject_id AND ezcontentobject_tree.contentobject_version='.$table.'.from_contentobject_version AND ';
            }
        }

        // add conditions to query
        if(sizeof($sqlCondArray) > 0)
        {
            $sqlJoins .= ' ('.join($matchAll ? ' AND ' : ' OR ', $sqlCondArray).') AND ';
        }

        return array('tables' => $sqlTables, 'joins'  => $sqlJoins);
    }

}
?>