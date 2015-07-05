<?php
/*
 *
 *                    Ampoliros Application Server
 *
 *                      http://www.ampoliros.com
 *
 *
 *
 *   Copyright (C) 2000-2004 Solarix
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program; if not, write to the Free Software
 *   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
// $Id: XMLParser.php,v 1.6 2004-07-08 15:04:27 alex Exp $

package('com.solarix.ampoliros.xml');

/*!
 @discussion XML parser class provides XML parsing OO functions. It server as a base
 for other classes, extending this one.
 */
class XMLParser extends Object
{
    protected $parser = false;
    protected $positions = array();
    protected $path = '';

    function XMLParser()
    {
        if ( function_exists( 'xml_parser_create' ) )
        {
            $this->parser = xml_parser_create();
        }
    }

    /*!
     @abstract Parses the given data.
     @param data string - the data to be parsed.
     @result True if the data has been succesfully parsed.
     */
    function Parse( $data )
    {
        // The following statements arent' located in the constructor
        // due to a PHP bug
        //
        xml_set_object( $this->parser, $this );
        $this->set_option( XML_OPTION_CASE_FOLDING, true );
        xml_set_element_handler( $this->parser, 'tag_open', 'tag_close' );
        xml_set_character_data_handler( $this->parser, 'cdata' );
        //
        return ( $this->parser ? xml_parse( $this->parser, $data ) : false );
    }

    /*!
     @function tag_open
     @abstract Open tag handler.
     @param parser integer - parser id.
     @param tag string - tag name.
     @param attributes array - tag attributes
     */
    function tag_open( $parser, $tag, &$attributes )
    {
        if ( strcmp( $this->path, '' ) )
        {
            $element = $this->structure[$this->path]['Elements'];
            $this->structure[$this->path]['Elements']++;
            $this->path .= ','.$element;
            //echo $this->path;
        }
        else
        {
            $element = 0;
            $this->path = '0';
        }
        $data = array(
                      'Tag'        => $tag,
                      'Elements'   => 0,
                      'Attributes' => $attributes
                     );
        $this->setelementdata( $this->path, $data );

        return( $this->parser ? $this->_tag_open( $tag, $attributes ) : false );
    }

    // Close tag handler
    //
    function tag_close( $parser, $tag )
    {
        $this->path = ( ( $position = strrpos( $this->path, ',' ) ) ? substr( $this->path, 0, $position ) : '' );

        return( $this->parser ? $this->_tag_close( $tag ) : false );
    }

    // Character data handler
    //
    function cdata( $parser, $data )
    {
        $element = $this->structure[$this->path]['Elements'];
        $previous = $this->path.','.strval( $element - 1 );
        if ( $element > 0 && GetType( $this->structure[$previous] ) == 'string' ) $this->structure[$previous] .= $data;
        else
        {
            $this->setelementdata( $this->path.','.$element, $data );
            $this->structure[$this->path]['Elements']++;
        }

        return ( $this->parser ? $this->_cdata( $data ) : false );
    }

    // Sets element data
    //
    function setelementdata( $path, $data )
    {
        $this->structure[$path] = $data;
    }

    // Sets a xml option
    //
    function set_option( $option, $value )
    {
        return ( $this->parser ? xml_parser_set_option( $this->parser, $option, $value ) : false );
    }

    // Gets a xml options
    //
    function get_option( $option )
    {
        return ( $this->parser ? xml_parser_get_option( $this->parser, $option ) : false );
    }

    // Frees the parser
    //
    function free()
    {
        if ( $this->parser )
        {
            if ( xml_parser_free( $this->parser ) )
            {
                $this->parser = false;
                return true;
            }
            else return false;
        }
    }

    /*!
     @function _tag_open
     */
    function _tag_open()
    {
        return true;
    }

    /*!
     @function _tag_close
     */
    function _tag_close()
    {
        return true;
    }

    /*!
     @function _cdata
     */
    function _cdata()
    {
        return true;
    }
}

/*!
@function LoadFile

@abstract Loads a file and returns its content.

@param fname string - path of the file.

@result The file content.
*/
function LoadFile( $fname )
{
    if ( file_exists( $fname ) )
    {
        return file_get_contents( $fname );
    }

    return false;
}

function ampxml_get_children(
    $vals,
    &$i
    )
{
    $children = array();

    if ( isset( $vals[$i]['value'] ) and $vals[$i]['value'] ) array_push(
        $children,
        $vals[$i]['value']
        );

    while ( ++$i < count($vals) )
    {
        switch ( $vals[$i]['type'] )
        {
        case 'cdata':
            array_push(
                $children,
                $vals[$i]['value']
                );
            break;

        case 'complete':
            array_push(
                $children,
                array(
                    'tag' => $vals[$i]['tag'],
                    'attributes' => isset( $vals[$i]['attributes'] ) ? $vals[$i]['attributes'] : '',
                    'value' => isset( $vals[$i]['value'] ) ? $vals[$i]['value'] : ''
                ) );
            break;

        case 'open':
            array_push(
                $children,
                array(
                    'tag' => $vals[$i]['tag'],
                    'attributes' => isset( $vals[$i]['attributes'] ) ? $vals[$i]['attributes'] : '',
                    'children' => ampxml_get_children(
                        $vals,
                        $i
                ) ) );
            break;

       case 'close':
            return $children;
       }
    }
}

function ampxml_get_xml_tree( $data )
{
    $p = xml_parser_create();
    xml_parser_set_option(
        $p,
        XML_OPTION_SKIP_WHITE,
        1
        );

    xml_parse_into_struct(
        $p,
        $data,
        $vals,
        $index
        );

    $error = xml_get_error_code( $p );
    if ( $error != XML_ERROR_NONE )
    {
        $pieces = explode( "\n", $data );
        global $gEnv;
        $gEnv['runtime']['modules']['ampoliros']['xmlerror']['errorstring'] = xml_error_string( $error );
        $gEnv['runtime']['modules']['ampoliros']['xmlerror']['linenumber'] = xml_get_current_line_number( $p );
        $gEnv['runtime']['modules']['ampoliros']['xmlerror']['linetext'] = $pieces[xml_get_current_line_number( $p ) - 1];
    }

    xml_parser_free( $p );

    $tree = array();
    $i = 0;
    array_push(
        $tree,
        array(
            'tag' => $vals[$i]['tag'],
            'attributes' => isset( $vals[$i]['attributes'] ) ? $vals[$i]['attributes'] : '',
            'children' => ampxml_get_children(
                $vals,
                $i
        ) ) );
    return $tree;
}

?>