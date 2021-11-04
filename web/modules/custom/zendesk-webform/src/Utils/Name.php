<?php
/**
 * Created by PhpStorm.
 * User: strakers
 * Date: 3/13/2020
 * Time: 1:05 PM
 */


namespace Drupal\zendesk_webform\Utils;


class Name
{
    public $title = '';
    public $first = '';
    public $middle = '';
    public $last = '';
    public $suffix = '';
    public $degree = '';

    /**
     * Name constructor.
     * @param array|string $name_array
     */
    public function __construct( $name_array )
    {
        // check if single string name is passed (if simple text field is used - not recommended)
        if( is_string($name_array) ) {
            $this->first = $name_array;
        }
        // when complex Name field is used (recommended)
        else {
            $name_obj = (object)$name_array;
            if (isset($name_obj->title)) $this->title = $name_obj->title;
            if (isset($name_obj->first)) $this->first = $name_obj->first;
            if (isset($name_obj->middle)) $this->middle = $name_obj->middle;
            if (isset($name_obj->last)) $this->last = $name_obj->last;
            if (isset($name_obj->suffix)) $this->suffix = $name_obj->suffix;
            if (isset($name_obj->degree)) $this->degree = $name_obj->degree;
        }
    }

    /**
     * @return string
     */
    public function get()
    {
        // forces desired ordering of name values
        $name_parts = [
            $this->title,
            $this->first,
            $this->middle,
            $this->last,
            $this->suffix,
            $this->degree
        ];
        return implode(' ',array_filter($name_parts,'trim'));
    }

    /**
     * @param array|string $name_array
     * @return string
     */
    static public function process( $name_array )
    {
        return (new self($name_array))->get();
    }
}