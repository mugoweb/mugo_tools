<?php

$eZTemplateOperatorArray = array();
$eZTemplateOperatorArray[] = array( 'script' => 'extension/mugo/classes/template_operators.php',
                                    'class' => 'MugoTemplateOperators',
                                    'operator_names' => array( 
                                                            'session_var'
                                                            , 'session_var_write'
							                                , 'truncate_html_chars'
							                                , 'cleanup_current_basket'
							                                , 'redirect'
							                                , 'full_day_timestamp'
							                                , 'time_format'
							                                , 'firstdayofmonth'
							                                , 'lastdayofmonth'
							                            )
							 );
?>
