<?php
/**
 * Created by PhpStorm.
 * User: strakers
 * Date: 7/2/2019
 * Time: 5:28 PM
 */

namespace Drupal\zendesk_webform\Utils;

/**
 * Class Utility
 * @package Drupal\zendesk_webform\Utils
 */
class Utility
{


    /**
     * @param array $field
     * @return bool
     */
    static public function checkIsNameField( array $field ){
        return in_array( $field['#type'], [ 'webform_name', 'textfield' ] );
    }

    /**
     * @param array $field
     * @return bool
     */
    static public function checkIsEmailField( array $field ){
        return in_array( $field['#type'], [ 'email', 'webform_email_confirm' ] );
    }

    /**
     * @param array $field
     * @return bool
     */
    static public function checkIsHiddenField( array $field ){
        return $field['#type'] === 'hidden' ;
    }

    /**
     * @param array $field
     * @return bool
     */
    static public function checkIsGroupingField( array $field ){
        return in_array( $field['#type'], [ 'webform_section' ] );
    }

    /**
     * @param string $text
     * @return string
     */
    static public function cleanTags( $text = '' ){
        return implode(' ',preg_split("/[^a-z0-9_]+/i",strtolower($text)));
    }

    /**
     * @param string $text
     * @return string
     */
    static public function convertTags( $text = '' ){
        return strtolower(implode(' ',preg_split("/[^a-z0-9_]+/i",$text)));
    }

    /**
     * @param array $name_parts
     * @return string
     */
    static public function convertName( $name_parts ){
        // use polyfill class to convert names in either string or array formats
        return Name::process($name_parts);
    }

    /**
     * @param array $set
     * @return string
     */
    static public function convertTable( $set ){
        $html = '';
        if( $set ) {
            $html = '<table><thead><tr><th>Title</th><th>ID</th></tr></thead><tbody>';
            foreach ($set as $id => $val) {
                $html .= '<tr><td>' . $val . '</td><td>' . $id . '</td></tr>';
            }
            $html .= '</tbody></table>';
        }
        return $html;
    }
}