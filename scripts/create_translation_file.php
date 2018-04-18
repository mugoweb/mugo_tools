<?php

#################
#  Setting up env
#################

require 'autoload.php';

if ( file_exists( "config.php" ) )
{
    require "config.php";
}

$params = new ezcConsoleInput();

$helpOption = new ezcConsoleOption( 'h', 'help' );
$helpOption->mandatory = false;
$helpOption->shorthelp = "Show help information";
$params->registerOption( $helpOption );

$targetOption = new ezcConsoleOption( 't', 'target', ezcConsoleInput::TYPE_STRING );
$targetOption->mandatory = true;
$targetOption->shorthelp = "The target extension.";
$params->registerOption( $targetOption );

$default_translation = new ezcConsoleOption( 'd', 'default_translation', ezcConsoleInput::TYPE_STRING );
$default_translation->mandatory = false;
$default_translation->shorthelp = "Set a default translation for all strings.";
$params->registerOption( $default_translation );

// Process console parameters
try
{
    $params->process();
}
catch ( ezcConsoleOptionException $e )
{
    print( $e->getMessage(). "\n" );
    print( "\n" );

    echo $params->getHelpText( 'TS file generator.' ) . "\n";

    echo "\n";
    exit();
}

####################
# Script process
####################
$result = array();

$tpl_files = Create_Translation_File_Handler::list_extension_files( $targetOption->value, array( '.tpl' ) );
//$tpl_files = array( 'extension/auction/modules/cc/my_account/change_security_image.php' );
foreach( $tpl_files as $file )
{
	$content = file_get_contents( $file );

	$i18n_instances = get_i18n_strings( $content );

	foreach( $i18n_instances as $instance )
	{
		$context   = parse_context_string( $instance[ 1 ] );
		$ts_string = parse_translation_string( $instance[ 0 ] );

		if( strlen( $context ) )
		{
			$man = eZTranslatorManager::instance();
			$trans = $man->translate( $context, $ts_string );
			if ( $trans === null )
			{
				$result[ $context ][ md5( $ts_string ) ] = $ts_string;
			}
			else
			{
				die( 'Found an existing translation - woohoo' );
			}
		}
	}
}

$php_files = Create_Translation_File_Handler::list_extension_files( $targetOption->value, array( '.php' ) );
//$php_files = array( 'extension/auction/modules/cc/my_account/change_security_image.php' );

foreach( $php_files as $file )
{
	$content = file_get_contents( $file );

	$i18n_instances = get_i18n_strings_in_php( $content );

	foreach( $i18n_instances as $instance )
	{
		$context   = $instance[ 0 ];
		$ts_string = $instance[ 1 ];
		
		if( strlen( $context ) )
		{
			$man = eZTranslatorManager::instance();
			$trans = $man->translate( $context, $ts_string );
			if ( $trans === null )
			{
				$result[ $context ][ md5( $ts_string ) ] = $ts_string;
			}
			else
			{
				die( 'Found an existing translation - woohoo' );
			}
		}
	}
}

// Sort strings per context
foreach( $result as &$context )
{
	sort( $context );
}

build_ts_file( $result, $default_translation->value );


########################
# Functions
########################

function get_i18n_strings_in_php( $content )
{
	$return = array();
	
	preg_match_all( '/ezi18n\((.*?)\)/is', $content, $out );

	if( $out[0] )
	{
		foreach( $out[1] as $index => $instance )
		{
			$php = '$values = array( ' . $instance . ' );';
			$success = eval( $php );

			if( $success === false )
			{
				echo 'unparseable translation: ' . "\n";
				echo $php . "\n";
				die( 'stopped' );
			}

			$return[] = $values;
		}
	}
	
	return $return;
}

function get_i18n_strings( $content )
{
	$return = array();
	
	preg_match_all( '/{(.*?)}/is', $content, $commands );

	foreach( $commands[1] as $tpl_command )
	{
		preg_match_all( '/(.*?)\|i18n\(.*?[\'"](.*?)[\'"].*?\)/is', $tpl_command, $translations );

		if( count( $translations[0] ) )
		{
			foreach( $translations[0] as $index => $translation )
			{
				$text = strrev( $translations[ 1 ][ $index ] );
				preg_match('/[\'](.*?)[\']/is', $text, $text_out );
				
				if( !count( $text_out[1] ) )
				{
					preg_match( '/"(.*?)"/is', $text, $text_out );
				}

				$text = strrev( $text_out[1] );

				$return[] = array( $text, $translations[ 2 ][ $index ] );
			}
		}
	}

	return $return;
}

function parse_translation_string( $string )
{
	return $string;
}

function parse_context_string( $string )
{
	$parts = explode( ',', $string );
	
	$string = $parts[ 0 ];
	
	//echo $string .  ' --- ';

	return $string;
}

function build_ts_file( $result, $default_translation = '' )
{
	echo "<!DOCTYPE TS><TS>\n";
	
	foreach( $result as $context => $messages )
	{
		echo "\t<context>\n";
		echo "\t<name>$context</name>\n";

		foreach( $messages as $message )
		{
			echo "\t\t<message>\n";
			echo "\t\t\t<source>$message</source>\n";
			echo "\t\t\t".'<translation type="unfinished">' . htmlentities( $default_translation ) . '</translation>'."\n";
			echo "\t\t</message>\n";
		}
		echo "\t</context>\n";
	}
	echo "</TS>\n";
}

class Create_Translation_File_Handler
{

	function list_extension_files( $extension_name, $file_extensions )
	{
		$files = array();
		
		$dir_handle = @opendir( 'extension/' . $extension_name ) or die( "Unable to open $path" );
		
		$files = self::recursion_list( $dir_handle, 'extension/' . $extension_name, $file_extensions );
	
		closedir( $dir_handle );
		
		return $files;
	}

	static function recursion_list( $dir_handle, $path, $file_extensions )
	{
		$return = array();
		//running the while loop
		while (false !== ( $file = readdir($dir_handle) ) )
		{
			$dir = $path.'/'.$file;
	
			if( is_dir( $dir ) && $file != '.' && $file !='..' && $file != '.svn' )
			{
				$handle = @opendir($dir) or die("undable to open file $file");
				//echo "D: $file\n";
				
				$return = array_merge( self::recursion_list( $handle, $dir, $file_extensions), $return );
			}
			elseif( in_array( substr( $file, -4 ), $file_extensions ) )
			{
				//TODO: still takes ini files
				//echo "F: $file\n";
				$return[] = $dir;
			}
		}
		
		return $return;
	}
}
?>
