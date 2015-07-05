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
// $Id: HuiXml.php,v 1.14 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.hui.widgets');

import('com.solarix.ampoliros.xml.XMLParser');
import('com.solarix.ampoliros.hui.HuiWidgetElement');

function huixml_encode($var) {
    return urlencode(serialize($var));
}

function huixml_decode($string) {
    return unserialize(urldecode($string));
}

function huixml_cdata($data) {
    return '<![CDATA['.$data.']]>';
}

class HuiXml extends HuiWidgetElement {
    public $mWidgetType = 'xml';
    /*! @public mDefinition string - XML definition of the widget. */
    public $mDefinition;
    /*! @public mDefinitionFile string - Optional file containing the XML definition.
        Overrides the "definition" argument if given. */
    public $mDefinitionFile;
    private $carthag;
    private $cl;

    public function huiXml($elemName, $elemArgs = '', $elemTheme = '', $dispEvents = '') {
        $this -> HuiWidgetElement($elemName, $elemArgs, $elemTheme, $dispEvents);
        if (isset($this -> mArgs['definitionfile']) and file_exists($this -> mArgs['definitionfile'])) {
            $this -> mDefinitionFile = $this -> mArgs['definitionfile'];

            if (file_exists($this -> mDefinitionFile)) {
                $this -> mDefinition = file_get_contents($this -> mDefinitionFile);
            }
        } else {
            if (isset($this -> mArgs['definition']))
                $this -> mDefinition = & $this -> mArgs['definition'];
        }
        $this -> carthag = Carthag :: instance();
        $this->cl = $this -> carthag -> getClassLoader();
    }

    public function build($rhuiDisp) {
        $this -> mrHuiDisp = $rhuiDisp;
        if ($this -> mDefinition!=null) {
/*
            $xml_tree = ampxml_get_xml_tree($this -> mDefinition);

            if (isset($GLOBALS['gEnv']['runtime']['modules']['ampoliros']['xmlerror'])) {
                Carthag :: import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.xml_hui.huixml_class.build', 'Invalid xml definition: '.$GLOBALS['gEnv']['runtime']['modules']['ampoliros']['xmlerror']['errorstring'].', line '.$GLOBALS['gEnv']['runtime']['modules']['ampoliros']['xmlerror']['linenumber'].': '.$GLOBALS['gEnv']['runtime']['modules']['ampoliros']['xmlerror']['linetext'], LOGGER_ERROR);
            } else {
                $root_element = & $this -> _GetElementStructure(array_shift($xml_tree));
                $this -> mLayout = ($this -> mComments ? '<!-- begin '.$this -> mName.' xml -->' : ''). ($root_element -> Build($this -> mrHuiDisp) ? $root_element -> Render() : ''). ($this -> mComments ? '<!-- end '.$this -> mName." xml -->\n" : '');
                $this -> mBuilt = $result = true;
            }
*/
                $root_element = & $this -> _GetElementStructure(array_shift(ampxml_get_xml_tree($this -> mDefinition)));
                $this -> mLayout = ($this -> mComments ? '<!-- begin '.$this -> mName.' xml -->' : ''). ($root_element -> Build($this -> mrHuiDisp) ? $root_element -> Render() : ''). ($this -> mComments ? '<!-- end '.$this -> mName." xml -->\n" : '');
                $this -> mBuilt = true;
                return true;
        }
        return false;
    }

    /*!
     @abstract Returns an object corresponding to a given node.
     @param $element xml node - Element node.
     @result An Hui element object.
     */
    private function & _getElementStructure(&$element) {
        $result = false;
        $element_type = 'Hui'.strtolower($element['tag']);
        $element_name = '';
        $element_args = array();
        $element_children = array();

        // Parse the element definition
        //
        if (isset($element['children']) and is_array($element['children']))
            while (list (, $node) = each($element['children'])) {
                switch ($node['tag']) {
                    case 'NAME' :
                        $element_name = $node['value'];
                        break;

                    case 'ARGS' :
                        if (isset($node['children']) and is_array($node['children'])) {
                            while (list (, $arg) = each($node['children'])) {
                                $attrs = isset($arg['attributes']) ? $arg['attributes'] : '';
                                $type = 'text';

                                if (is_object($attrs) or is_array($attrs)) {
                                    while (list ($attr, $value) = each($attrs)) {
                                        if ($attr == 'TYPE' and $value == 'array')
                                            $type = 'array';
                                        if ($attr == 'TYPE' and $value == 'encoded')
                                            $type = 'encoded';
                                    }
                                }

                                if ($type == 'array')
                                    $value = huixml_decode($arg['value']);
                                else
                                    if ($type == 'encoded') {
                                        $value = urldecode($arg['value']);
                                    }
                                    else {
                                        $value = $arg['value'];
                                    }

                                $element_args[strtolower($arg['tag'])] = $value;
                            }
                        }
                        break;

                    case 'CHILDREN' :
                        if (isset($node['children']) and is_array($node['children'])) {
                            while (list (, $child_node) = each($node['children'])) {
                                $relem = & $element_children[];
                                $relem['args'] = array();

                                if (strtolower($child_node['tag']) == 'huiobject') {
                                    $relem['element'] = unserialize(urldecode($child_node['value']));
                                } else {
                                    $relem['element'] = & $this -> _GetElementStructure($child_node);
                                }

                                // Add not standard parameters
                                //
                                if (isset($child_node['attributes']) and is_array($child_node['attributes'])) {
                                    while (list ($attr, $value) = each($child_node['attributes'])) {
                                        $relem['args'][strtolower($attr)] = $value;
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        if (!strlen($element_name))
            $element_name = $element_type.rand();

        // Build element arguments array
        //
        while (list ($key, $val) = each($element_args)) {
            $element_args[$key] = $val;
        }

        // Tries to load the widget if it wasn't loaded.
        //
        if (!class_exists($element_type)) {
            $widget_name = strtolower($element['tag']);
            if (!defined(strtoupper($widget_name.'_HUI')) and file_exists(HANDLER_PATH.$widget_name.'.hui')) {
                include_once (HANDLER_PATH.$widget_name.'.hui');
            }
        }

        // Create the element and add children if any
        //
        if (class_exists($element_type)) {
            $result = new $element_type ($element_name, $element_args);
            while (list (, $child_element) = each($element_children)) {
                if (isset($child_element['element']) and is_object($child_element['element'])) {
                    unset($tmp_array);
                    $tmp_array[] = $child_element['element'];
                    $args = array_merge($tmp_array, $child_element['args']);
                    call_user_func_array(array(& $result, 'AddChild'), $args);
                }
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.xml_hui.huixml_class._getelementstructure', 'Element of type '.$element_type.' is not defined', LOGGER_WARNING);
        }
        return $result;
    }
}

?>