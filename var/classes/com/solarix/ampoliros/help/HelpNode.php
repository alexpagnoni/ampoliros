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
// $Id: HelpNode.php,v 1.7 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.help');

class HelpNode {
    private $mNode;
    private $mLanguage;

    /*!
     @param node string - Node name.
     */
    public function HelpNode($node, $language) {
        $this -> mNode = $node;
        $this -> mLanguage = $language;
    }

    /*!
     @abstract Gets help node content.
     @result string - Help node content.
     */
    public function getContent() {
        $result = false;
        if (strlen($this -> mNode)) {
            import('carthag.core.Registry');
            $reg = Registry::instance();
            // Tries specified language catalog
            //
            if (file_exists($reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/help/'.$this -> mNode.'_'.$this -> mLanguage.'.helpnode')) {
                $help_node_file = $reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/help/'.$this -> mNode.'_'.$this -> mLanguage.'.helpnode';
            }
            // Tries default catalog
            //
            else
                if (file_exists($reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/help/'.$this -> mNode.'.helpnode')) {
                    $help_node_file = $reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/help/'.$this -> mNode.'.helpnode';
                }
            // Tries Ampoliros language catalog
            //
            else
                if (file_exists($reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/help/'.$this -> mNode.'_'.AMP_LANG.'.helpnode')) {
                    $help_node_file = $reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/help/'.$this -> mNode.'_'.AMP_LANG.'.helpnode';
                }
            // Tries English catalog
            //
            else
                if (file_exists($reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/help/'.$this -> mNode.'_en.helpnode')) {
                    $help_node_file = $reg->getEntry('amp.config')->getKey('PRIVATE_TREE').'var/help/'.$this -> mNode.'_en.helpnode';
                } else {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
                    $log -> LogEvent('ampoliros.helpnode_library.helpnode_class.getcontent', 'Unable to find an help node file for the specified help node ('.$this -> mNode.') and language ('.$this -> mLanguage.') or fallback to another language', LOGGER_ERROR);
                }
            if (!empty($help_node_file)) {
                $fh = @ fopen($help_node_file, 'r');
                if ($fh) {
                    $result = file_get_contents($help_node_file);
                    @ fclose($fh);
                }
            }
        }
        return $result;
    }
}
?>