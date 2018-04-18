<?php

class MugoTemplateOperators {

    function operatorList() {
        //TODO: session_var should be a fetch function
        return array(
            'session_var'
            , 'session_var_write'
            , 'cleanup_current_basket'
            , 'redirect'
            , 'full_day_timestamp'
            , 'time_format'
            , 'firstdayofmonth'
            , 'lastdayofmonth'
            ,'truncate_html_chars'
        );
    }

    function namedParameterPerOperator() {
        return true;
    }

    function namedParameterList() {
        return array(
            'session_var' => array(
                                'key' => array('type' => 'string', 'required' => true )
                             )
            , 'session_var_write' => array(
                                    'key' => array('type' => 'string', 'required' => true)
                                    , 'value' => array('type' => 'string', 'required' => true )
                                   )
            , 'cleanup_current_basket' => array()
            , 'redirect' => array(
                                'url' => array('type' => 'string', 'required' => true)
                                , 'status_code' => array('type' => 'string', 'required' => false)
                          )
            , 'full_day_timestamp' => array(
                                            'timestamp' => array('type' => 'integer', 'required' => true )
                                      )
            , 'time_format' => array(
                                    'seconds' => array('type' => 'integer', 'required' => true )
                               )
            , 'firstdayofmonth' => array(
                                    'month' => array('type' => 'integer', 'required' => false )
                                   )
            , 'lastdayofmonth' => array(
                                    'month' => array('type' => 'integer', 'required' => false )
                                  )
             ,'truncate_html_chars' => array( 
                                             'html' => array( 'type' => 'string', 'required' => true )
                                             ,'characters' => array( 'type' => 'integer', 'required' => false, 'default' => 400 )
                                             ,'ellipses' => array( 'type' => 'string', 'required' => false, 'default' => '...' )
                                             ,'preserve_words' => array( 'type' => 'boolean', 'required' => false, 'default' => true )
                                       )
        );
    }

    function modify($tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters) {
        switch ($operatorName) {
            case 'session_var': {
                    $operatorValue = $_SESSION[$namedParameters['key']];
                }
                break;

            case 'session_var_write': {
                    $_SESSION[$namedParameters['key']] = $namedParameters['value'];
                }
                break;

            case 'cleanup_current_basket': {
                    $basket = eZBasket::currentBasket();
                    if (!is_object($basket)) {
                        return false;
                    }
                    $db = eZDB::instance();
                    $db->begin();

                    $productCollectionID = $basket->attribute('productcollection_id');

                    eZProductCollection::cleanupList(array($productCollectionID));

                    $basket->remove();
                    $db->commit();
                }
                break;

            case 'full_day_timestamp': {
                    $input = $namedParameters['timestamp'];

                    $day_time_diff = $input % 86400;
                    $timezone_correction = date('Z', $input);

                    $operatorValue = $input - $day_time_diff - $timezone_correction;
                }
                break;

            case 'time_format': {
                    $input = $namedParameters['seconds'];

                    $minutes = sprintf("%02d", ( $input % 3600 ) / 60);
                    $hours = sprintf("%02d", (int) $input / 3600);

                    $operatorValue = $hours . ':' . $minutes;
                }
                break;

            case 'redirect': {
                    $redirectUri = $namedParameters['url'];
                    $status_code = (int) $namedParameters['status_code'] ? (int) $namedParameters['status_code'] : 301;

                    // if $redirectUri is not starting with scheme://
                    if (!preg_match('#^\w+://#', $redirectUri)) {
                        // path to eZ Publish index
                        $indexDir = eZSys::indexDir();

                        /* We need to make sure we have one
                          and only one slash at the concatenation point
                          between $indexDir and $redirectUri. */
                        $redirectUri = rtrim($indexDir, '/') . '/' . ltrim($redirectUri, '/');
                    }

                    // Redirect to $redirectUri by returning status code 301 and exit.
                    eZHTTPTool::redirect($redirectUri, array(), $status_code);
                    eZExecution::cleanExit();
                }
                break;

            case 'firstdayofmonth': {
                    $timestamp = $namedParameters['timestamp'] == '' ? time() : $namedParameters['timestamp'];
                    $operatorValue = mktime(0, 0, 0, date('m', $timestamp), 1, date('y', $timestamp));
                }
                break;

            case 'lastdayofmonth': {
                    $timestamp = $namedParameters['timestamp'] == '' ? time() : $namedParameters['timestamp'];
                    $operatorValue = mktime(0, 0, 0, date('m', $timestamp), date('t', $timestamp), date('y', $timestamp));
                }
                break;

            case 'truncate_html_chars':
            {
                $truncate = new TruncateHTML;
                $operatorValue = $truncate->truncateChars( $namedParameters['html'], $namedParameters['characters'], $namedParameters['ellipses'], $namedParameters['preserve_words'] );
            }
            break;

            default:
        }
    }

}

?>
