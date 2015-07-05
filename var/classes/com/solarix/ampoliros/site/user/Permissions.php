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
// $Id: Permissions.php,v 1.6 2004-07-08 15:04:23 alex Exp $

package('com.solarix.ampoliros.site.user');

define ('PERMISSIONS_NODETYPE_GROUP', 'group');
define ('PERMISSIONS_NODETYPE_PAGE', 'page');

define ('PERMISSIONS_NODE_FULLYENABLED', 1);
define ('PERMISSIONS_NODE_PARTIALLYENABLED', 2);
define ('PERMISSIONS_NODE_NOTENABLED', 3);

/*!
 @class Permissions

 @abstract Administration interface pages permissions handling
 */
class Permissions extends Object {
    public $db;
    public $gid;
    public $permds;

    public function Permissions(DBLayer $sitedb, $gid) {
        $this -> db = $sitedb;
        $this -> gid = $gid;
    }

    // Enable a node
    public function enable($node, $ntype) {
        $this -> db -> Execute('DELETE FROM permissions '."WHERE groupid = '".$this -> gid."' "."AND permnode = '".$ntype.$node."'");

        if (strcmp($ntype, 'group') == 0) {
            $apquery = $this -> db -> Execute('SELECT id '.'FROM adminpages '.'WHERE groupid = '.$this -> db -> Format_Text($node));
            while (!$apquery -> eof) {
                $this -> db -> Execute('DELETE FROM permissions '."WHERE groupid = '".$this -> gid."' "."AND permnode = 'page".$apquery -> fields('id')."'");
                $apquery -> MoveNext();
            }
            $apquery -> free();
        }
    }

    // Disable a node
    public function disable($node, $ntype) {
        if ($this -> check($node, $ntype) != PERMISSIONS_NODE_NOTENABLED) {
            $this -> db -> Execute("INSERT into permissions values ( '".$this -> gid."','".$ntype.$node."')");

            if (strcmp($ntype, PERMISSIONS_NODETYPE_GROUP) == 0) {
                $apquery = $this -> db -> Execute("SELECT id FROM adminpages WHERE groupid = '".$node."'");
                while (!$apquery -> eof) {
                    $this -> disable($apquery -> Fields('id'), 'page');
                    $apquery -> MoveNext();
                }
                $apquery -> free();
            }
        }
    }

    // Check a node permission status
    public function check($node, $ntype) {
        $result = PERMISSIONS_NODE_NOTENABLED;

        $pquery = $this -> db -> Execute('SELECT groupid '.'FROM permissions '.'WHERE groupid = '.$this -> gid.' '.'AND permnode = '.$this -> db -> Format_Text($ntype.$node));

        if ($pquery -> NumRows() == 0)
            $result = PERMISSIONS_NODE_FULLYENABLED;

        if (strcmp($ntype, PERMISSIONS_NODETYPE_GROUP) == 0) {
            $apquery = & $this -> db -> Execute('SELECT id '.'FROM adminpages '.'WHERE groupid = '.$node);
            $pages = 0;

            while (!$apquery -> eof) {
                if ($this -> check($apquery -> Fields('id'), PERMISSIONS_NODETYPE_PAGE) == PERMISSIONS_NODE_FULLYENABLED) {
                    $result = PERMISSIONS_NODE_PARTIALLYENABLED;
                    $pages ++;
                }
                $apquery -> MoveNext();
            }

            if (($apquery -> numRows() == $pages) and ($apquery -> numRows() != 0))
                $result = PERMISSIONS_NODE_FULLYENABLED;

            $apquery -> free();
        }
        $pquery -> free();

        return $result;
    }

    // Removes every permission referred to a certain node
    // for every group
    public function removeNodes($node, $type) {
        //return &$this->db->Execute( "DELETE FROM permissions WHERE permnode = '".$type.$node."'" );
    }

    // Gets node id of a page by its filename
    //
    public function getNodeIdFromFileName($filename) {
        $filename = basename($filename);

        if (!empty($filename) and $this -> db) {
            $query = $this -> db -> Execute('SELECT id '.'FROM adminpages '.'WHERE location = '.$this -> db -> Format_Text($filename));
            if ($query -> NumRows())
                return $query -> Fields('id');
        }
        return false;
    }
}

?>