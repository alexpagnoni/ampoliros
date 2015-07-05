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
// $Id: XmlRpcProfile.php,v 1.4 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.webservices.xmlrpc');

import('com.solarix.ampoliros.io.log.Logger');
import('com.solarix.ampoliros.util.Hook');

define( 'XMLRPCPROFILE_NODETYPE_MODULE', 0 );
define( 'XMLRPCPROFILE_NODETYPE_METHOD', 1 );

define( 'XMLRPCPROFILE_MODULENODE_FULLYENABLED',     1 );
define( 'XMLRPCPROFILE_MODULENODE_PARTIALLYENABLED', 2 );
define( 'XMLRPCPROFILE_MODULENODE_NOTENABLED',       3 );
define( 'XMLRPCPROFILE_METHODNODE_ENABLED',          4 );
define( 'XMLRPCPROFILE_METHODNODE_NOTENABLED',       5 );

/*!
 @class XmlRpcProfile

 @abstract XML RPC profile class.
 */
class XmlRpcProfile extends Object
{
    var $mLog;
    var $mAmpDb;
    var $mProfileId;

    /*!
     @function XmlRpcProfile

     @abstract Class constructor.

     @param ampDb dblayer class - Ampoliros database handler.
     @param profileId integer - Profile serial.
     */
    function XmlRpcProfile( &$ampDb, $profileId = '' )
    {
        $this->mLog = new Logger( AMP_LOG );

        if ( $ampDb ) $this->mAmpDb = &$ampDb;
        else $this->mLog->LogDie( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.xmlrpcprofile',
                                 'Invalid Ampoliros database handler' );

        $this->mProfileId = $profileId;
    }

    /*!
     @function Add

     @abstract Adds a new profile.

     @param profileName string - Profile name.

     @result True if the profile has been added.
     */
    function Add( $profileName )
    {
        $result = false;

        $hook = new Hook( $this->mAmpDb, 'ampoliros', 'xmlrpcprofile.add' );
        if ( $hook->CallHooks( 'calltime', $this, array( 'name' => $profileName ) ) == HOOK_RESULT_OK )
        {
            if ( $this->mAmpDb )
            {
                if ( !$this->mProfileId )
                {
                    if ( strlen( $profileName ) )
                    {
                        $query = &$this->mAmpDb->Execute( 'SELECT profilename '.
                                                          'FROM xmlrpcprofiles '.
                                                          'WHERE profilename='.$this->mAmpDb->Format_Text( $profileName ) );
                        if ( !$query->NumRows() )
                        {
                            $this->mProfileId = $this->mAmpDb->NextSeqValue( 'xmlrpcprofiles_id_seq' );

                            $result = &$this->mAmpDb->Execute( 'INSERT INTO xmlrpcprofiles '.
                                                               'VALUES ('.
                                                               $this->mProfileId.','.
                                                               $this->mAmpDb->Format_Text( $profileName ).')' );

                            if ( $result )
                            {
                                $hook->CallHooks( 'profileadded', $this, array( 'name' => $profileName ) );

                                $this->mLog->LogEvent( 'Ampoliros',
                                                      'Created new remote profile', LOGGER_NOTICE );
                            }
                            else
                            {
                                $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.add',
                                                      'Unable to insert xmlrpc profile into xmlrpcprofiles table', LOGGER_ERROR );
                            }
                        }
                    }
                    else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.add',
                                                'Empty profile name', LOGGER_ERROR );
                }
                else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.add',
                                            'Already assigned user for this object', LOGGER_ERROR );
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.add',
                                        'Invalid Ampoliros database handler', LOGGER_ERROR );
        }

        return $result;
    }

    /*!
     @function Remove

     @abstract Removes a xmlrpc profile.

     @result True it the profile has been deleted.
     */
    function Remove()
    {
        $result = false;

        $hook = new Hook( $this->mAmpDb, 'ampoliros', 'xmlrpcprofile.remove' );
        if ( $hook->CallHooks( 'calltime', $this, array() ) == HOOK_RESULT_OK )
        {
            if ( $this->mAmpDb )
            {
                if ( $this->mProfileId )
                {
                    // Removes all permissions of the profile
                    //
                    $this->mAmpDb->Execute( 'DELETE FROM xmlrpcpermissions '.
                                            'WHERE profileid='.(int)$this->mProfileId );

                    // Removes the profile from the users
                    //
                    $this->mAmpDb->Execute( 'UPDATE xmlrpcusers '.
                                            'SET profileid=0 '.
                                            'WHERE profileid='.(int)$this->mProfileId );

                    // Removes the profile
                    //
                    $this->mAmpDb->Execute( 'DELETE FROM xmlrpcprofiles '.
                                            'WHERE id='.(int)$this->mProfileId );

                    // Unset profile id
                    //
                    $this->mProfileId = '';

                    $hook->CallHooks( 'profileremoved', $this, array() );

                    $result = true;

                    $this->mLog->LogEvent( 'Ampoliros',
                                          'Removed remote profile', LOGGER_NOTICE );
                }
                else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.remove',
                                            'Object not assigned to a profile', LOGGER_ERROR );
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.remove',
                                        'Invalid Ampoliros database handler', LOGGER_ERROR );
        }

        return $result;
    }

    /*!
     @function Rename

     @abstract Renames a xmlrpc profile.

     @param profileName string - New profile name.

     @result True it the profile has been renamed.
     */
    function Rename( $profileName )
    {
        $result = false;
        
        $hook = new Hook( $this->mAmpDb, 'ampoliros', 'xmlrpcprofile.rename' );
        if ( $hook->CallHooks( 'calltime', $this, array( 'name' => $profileName ) ) == HOOK_RESULT_OK )
        {
            if ( $this->mAmpDb )
            {
                if ( $this->mProfileId )
                {
                    if ( strlen( $profileName ) )
                    {
                        // Removes the profile
                        //
                        $result = $this->mAmpDb->Execute( 'UPDATE xmlrpcprofiles '.
                            'SET profilename='.$this->mAmpDb->Format_Text( $profileName ).' '.
                            'WHERE id='.(int)$this->mProfileId );

                        $hook->CallHooks( 'profilerenamed', $this, array( 'name' => $profileName ) );

                    }
                    else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.rename',
                                                'Empty new profile name', LOGGER_ERROR );
                }
                else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.rename',
                                            'Object not assigned to a profile', LOGGER_ERROR );
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.rename',
                                        'Invalid Ampoliros database handler', LOGGER_ERROR );
        }

        return $result;
    }

    /*!
     @function EnableNode

     @abstract Enables a node.

     @discussion A node is a method or a whole set of module metods.

     @param nodeType integer - Node type, as in the XMLRPCPROFILE_NODETYPE defines.
     @param moduleName string - Module name of the method.
     @param methodName string - Method name, may be empty if nodeType is XMLRPCPROFILE_NODETYPE_MODULE.

     @result True if the node has been enabled.
     */
    function EnableNode( $nodeType, $moduleName, $methodName = '' )
    {
        $result = false;

        if ( $this->mAmpDb )
        {
            if ( $this->mProfileId )
            {
                if ( strlen( $nodeType )
                     and
                     strlen( $moduleName )
                     and
                     (
                      $nodeType == XMLRPCPROFILE_NODETYPE_MODULE
                      or
                      (
                       $nodeType == XMLRPCPROFILE_NODETYPE_METHOD
                       and
                       strlen( $methodName )
                      )
                     )
                   )
                {
                    // :TODO: wuh 010710
                    // It should check if the node already exists

                    // If nodeType is module, then remove all nodes that are methods of the module
                    //
                    if ( $nodeType == XMLRPCPROFILE_NODETYPE_MODULE ) $this->mAmpDb->Execute( 'DELETE FROM xmlrpcpermissions '.
                                                                                              'WHERE module='.$this->mAmpDb->Format_Text( $moduleName ).' '.
                                                                                              'AND profileid='.(int)$this->mProfileId );

                    // Checks if all other method nodes in the module were enabled,
                    // in that case disables all them and enables the module node
                    //
                    if ( $nodeType == XMLRPCPROFILE_NODETYPE_METHOD )
                    {
                        $tmpquery = &$this->mAmpDb->Execute( 'SELECT count(*) AS count '.
                                                             'FROM xmlrpcpermissions '.
                                                             'WHERE module='.$this->mAmpDb->Format_Text( $moduleName ).' '.
                                                             'AND profileid='.(int)$this->mProfileId );

                        $tmpqueryb = &$this->mAmpDb->Execute( 'SELECT count(*) AS count '.
                                                              'FROM xmlrpcmethods '.
                                                              'WHERE module='.$this->mAmpDb->Format_Text( $moduleName ) );

                        if ( $tmpquery->Fields( 'count' ) == ( $tmpqueryb->Fields( 'count' ) - 1 ) )
                        {
                            $this->mAmpDb->Execute( 'DELETE FROM xmlrpcpermissions '.
                                                    'WHERE module='.$this->mAmpDb->Format_Text( $moduleName ).' '.
                                                    'AND profileid='.(int)$this->mProfileId );
                            $skip_method = true;
                        }
                    }

                    $result = &$this->mAmpDb->Execute( 'INSERT INTO xmlrpcpermissions '.
                                                       'VALUES ('.
                                                       $this->mProfileId.','.
                                                       $this->mAmpDb->Format_Text( $moduleName ).','.
                                                       $this->mAmpDb->Format_Text( ( ( ( $nodeType == XMLRPCPROFILE_NODETYPE_METHOD ) and ( $skip_method != true ) )
                                                                                     ?
                                                                                     $methodName
                                                                                     :
                                                                                     '' ) ).')' );

                    // :TODO: wuh 010711
                    // If nodetype is method, it should check if all methods of the module were enabled.
                    // In that case it should remove every node relative to the module and enable a new node of module type.

                    if ( !$result ) $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.enablenode',
                                                           'Unable to insert xmlrpc profile node into xmlrpcpermissions table', LOGGER_ERROR );
                }
                else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.enablenode',
                                            'Wrong parameters', LOGGER_ERROR );
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.enablenode',
                                        'Object not assigned to a profile', LOGGER_ERROR );
        }
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.enablenode',
                                    'Invalid Ampoliros database handler', LOGGER_ERROR );

        return $result;
    }

    /*!
     @function DisableNode

     @abstract Disables a node.

     @discussion A node is a method or a whole set of module metods.

     @param nodeType integer - Node type, as in the XMLRPCPROFILE_NODETYPE defines.
     @param moduleName string - Module name of the method.
     @param methodName string - Method name, may be empty if nodeType is XMLRPCPROFILE_NODETYPE_MODULE.

     @result True if the node has been disabled.
     */
    function DisableNode( $nodeType, $moduleName, $methodName = '' )
    {
        $result = false;

        if ( $this->mAmpDb )
        {
            if ( $this->mProfileId )
            {
                if (
                    strlen( $nodeType )
                    and
                    strlen( $moduleName )
                    and
                    (
                     $nodeType == XMLRPCPROFILE_NODETYPE_MODULE
                     or
                     (
                      $nodeType == XMLRPCPROFILE_NODETYPE_METHOD
                      and
                      strlen( $methodName )
                     )
                    )
                   )
                {
                    if ( $nodeType == XMLRPCPROFILE_NODETYPE_METHOD )
                    {
                        // Checks if the module node is enabled
                        //
                        $tmpquery = &$this->mAmpDb->Execute( 'SELECT module, method '.
                                                             'FROM xmlrpcpermissions '.
                                                             'WHERE profileid='.(int)$this->mProfileId.' '.
                                                             'AND module='.$this->mAmpDb->Format_Text( $moduleName ).' '.
                                                             "AND method=''" );

                        if ( $tmpquery->NumRows() == 1 )
                        {
                            // Delete all nodes relative to the module
                            //
                            $this->mAmpDb->Execute( 'DELETE FROM xmlrpcpermissions '.
                                                    'WHERE profileid='.(int)$this->mProfileId.' '.
                                                    'AND module='.$this->mAmpDb->Format_Text( $moduleName ) );

                            // Enable all module methods nodes expect the method node to disable
                            //
                            $tmpqueryb = &$this->mAmpDb->Execute( 'SELECT name '.
                                                                  'FROM xmlrpcmethods '.
                                                                  'WHERE module='.$this->mAmpDb->Format_Text( $moduleName ) );

                            while ( !$tmpqueryb->eof )
                            {
                                if (
                                    strlen( $tmpqueryb->Fields( 'name' ) )
                                    and
                                    (
                                     $tmpqueryb->Fields( 'name' ) != $methodName
                                    )
                                   )
                                {
                                    $this->EnableNode( XMLRPCPROFILE_NODETYPE_METHOD, $moduleName, $tmpqueryb->Fields( 'name' ) );
                                }
                                $tmpqueryb->MoveNext();
                            }

                            $this->mAmpDb->Execute( 'INSERT INTO xmlrpcpermissions VALUES ('.
                                                    $this->mProfileId.','.
                                                    $this->mAmpDb->Format_Text( $moduleName ).','.
                                                    $this->mAmpDb->Format_Text( $methodName ).')' );
                        }
                    }

                    $result = &$this->mAmpDb->Execute( 'DELETE FROM xmlrpcpermissions '.
                                                       'WHERE profileid='.(int)$this->mProfileId.' '.
                                                       'AND module='.$this->mAmpDb->Format_Text( $moduleName ).' '.
                                                       ( $nodeType == XMLRPCPROFILE_NODETYPE_METHOD ? 'AND method='.$this->mAmpDb->Format_Text( $methodName ) : '' ) );
                }
                else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.disablenode',
                                            'Wrong parameters', LOGGER_ERROR );
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.disablenode',
                                        'Object not assigned to a profile', LOGGER_ERROR );
        }
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.disablenode',
                                    'Invalid Ampoliros database handler', LOGGER_ERROR );

        return $result;
    }

    /*!
     @function AvailableMethods

     @abstract Returns the list of the methods available for this profile.

     @result Associative array of the available methods and their attributes.
     */
    function AvailableMethods()
    {
        $result = false;

        if ( $this->mAmpDb )
        {
            if ( $this->mProfileId )
            {
                global $gEnv;
                $unsecure_lock = $gEnv['core']['config']->Value( 'LOCK_UNSECURE_WEBSERVICES' );

                $query = &$this->mAmpDb->Execute(
                                                 'SELECT xmlrpcmethods.name AS name, '.
                                                 'xmlrpcmethods.handler AS handler, '.
                                                 'xmlrpcmethods.module AS module, '.
                                                 'xmlrpcmethods.function AS function, '.
                                                 'xmlrpcmethods.unsecure AS unsecure, '.
                                                 'xmlrpcmethods.docstring AS docstring '.
                                                 'FROM xmlrpcmethods,xmlrpcpermissions '.
                                                 'WHERE xmlrpcpermissions.profileid='.(int)$this->mProfileId.' '.
                                                 'AND ( ( xmlrpcmethods.module=xmlrpcpermissions.module '.
                                                 "AND xmlrpcpermissions.method='' ) ".
                                                 'OR xmlrpcmethods.name=xmlrpcpermissions.method )' );

                $result = array();

                while ( !$query->eof )
                {
                    if ( !( $query->Fields( 'unsecure' ) == $this->mAmpDb->fmttrue and $unsecure_lock == '1' ) )
                    {
                        $result[] = $query->Fields();
                    }

                    $query->MoveNext();
                }
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.availablemethods',
                                        'Object not assigned to a profile', LOGGER_ERROR );
        }
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.availablemethods',
                                    'Invalid Ampoliros database handler', LOGGER_ERROR );

        return $result;
    }

    /*!
     @function NodeCheck

     @abstract Checks if a node is enabled

     @param moduleName string - Node to check.
     @param nodeType integer - Node type.
     @param methodName string - Method name, may be empty.

     @result One of the XMLRPCPROFILE_xNODE defines accordingly.
     */
    function NodeCheck( $nodeType, $moduleName, $methodName = '' )
    {
        $result = false;

        if ( $this->mAmpDb )
        {
            if ( $this->mProfileId )
            {
                if (
                    strlen( $nodeType )
                    and
                    strlen( $moduleName )
                    and
                    (
                     $nodeType == XMLRPCPROFILE_NODETYPE_MODULE
                     or
                     (
                      $nodeType == XMLRPCPROFILE_NODETYPE_METHOD
                      and
                      strlen( $methodName )
                     )
                    )
                   )
                {
                    $query = &$this->mAmpDb->Execute( 'SELECT module,method '.
                                                      'FROM xmlrpcpermissions '.
                                                      'WHERE module='.$this->mAmpDb->Format_Text( $moduleName ).' '.
                                                      'AND profileid='.(int)$this->mProfileId.' '.
                                                      ( $nodeType == XMLRPCPROFILE_NODETYPE_METHOD
                                                        ?
                                                        'AND ( method='.$this->mAmpDb->Format_Text( $methodName ).' '.
                                                        "OR method='' )"
                                                        :
                                                        ''
                                                      )
                                                    );

                    if ( $query->NumRows() )
                    {
                        if ( $nodeType == XMLRPCPROFILE_NODETYPE_MODULE )
                        {
                            if ( $query->Fields( 'method' ) ) $result = XMLRPCPROFILE_MODULENODE_PARTIALLYENABLED;
                            else $result = XMLRPCPROFILE_MODULENODE_FULLYENABLED;
                        }
                        else $result = XMLRPCPROFILE_METHODNODE_ENABLED;
                    }
                    else
                    {
                        if ( $nodeType == XMLRPCPROFILE_NODETYPE_MODULE ) $result = XMLRPCPROFILE_MODULENODE_NOTENABLED;
                        else $result = XMLRPCPROFILE_METHODNODE_NOTENABLED;
                    }
                }
                else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.modulenodecheck',
                                            'Wrong parameters', LOGGER_ERROR );
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.modulenodecheck',
                                        'Object not assigned to a profile', LOGGER_ERROR );
        }
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcprofile_class.modulenodecheck',
                                    'Invalid Ampoliros database handler', LOGGER_ERROR );

        return $result;
    }
}

?>