<?php
$module = $Params['Module'];

$post_vars = $_POST;

if( $post_vars[ 'module' ] && $post_vars[ 'view' ] )
{
	$module_name = $post_vars[ 'module' ];
	$view_name = $post_vars[ 'view' ];
	
	unset( $post_vars[ 'module' ] );
	unset( $post_vars[ 'view' ] );
	
	// build view parameters
	$unorderedParameters = null;
	foreach( $post_vars as $key => $post_var )
	{
		$unorderedParameters[ $key ] = $post_var;
	}
	
	$module->redirect( $module_name, $view_name, array(), $unorderedParameters );
}
else
{
	print_r( $_POST );
}


?>