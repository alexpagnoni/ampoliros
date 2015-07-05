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
// $Id: XmlRpcMethod.php,v 1.3 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.webservices.xmlrpc');

import('com.solarix.ampoliros.io.log.Logger');

/*!
 @class XmlRpcMethod

 @abstract XML RPC method elements handler
 */
class XmlRpcMethod extends Object
{
    /*! @var mAmpDb dblayer class - Ampoliros database handler */
    var $mAmpDb;
    /*! @var mLog logger class - Log handler */
    var $mLog;
    /*! @var mName string - Method name */
    var $mName;

    /*!
     @function XmlRpcMethod

     @abstract Class constructor

     @param ampDb dblayer class - Ampoliros database handler.
     @param name string - Method name, may be empty.
     */
    function XmlRpcMethod( &$ampDb, $name = '' )
    {
        $this->mLog = new Logger( AMP_LOG );

        if ( $ampDb ) $this->mAmpDb = &$ampDb;
        else $this->mLog->LogDie( 'ampoliros.xmlrpc_library.xmlrpcmethod_class.xmlrpcmethod',
                                  'Invalid Ampoliros database handler' );

        $this->mName = $name;
    }

    /*!
     @function Install

     @abstract Installs a new method.

     @param name string - Method name.
     @param function string - Function name.
     @param handler string - Handler file name, without extension.
     @param module string - Module id name.
     @param signature string - Method signature, refer to XMLRPC specifications.
     @param docstring string - Method description.

     @result True if the method has been installed.
     */
    function Install( $name, $function, $handler, $module, $signature = '', $docstring = '', $unsecure = 0, $catalog = '' )
    {
        $result = false;

        if ( $name and $function and $handler and $module )
        {
            // :TODO: wuh 010712: add check
            // The function should check if the method already exists.

            $result = &$this->mAmpDb->Execute( 'INSERT INTO xmlrpcmethods '.
                                               'VALUES ('.
                                               $this->mAmpDb->NextSeqValue( 'xmlrpcmethods_id_seq' ).','.
                                               $this->mAmpDb->Format_Text( $name ).','.
                                               $this->mAmpDb->Format_Text( $function ).','.
                                               $this->mAmpDb->Format_Text( $signature ).','.
                                               $this->mAmpDb->Format_Text( $docstring ).','.
                                               $this->mAmpDb->Format_Text( $handler ).','.
                                               $this->mAmpDb->Format_Text( $module ).','.
                                               $this->mAmpDb->Format_Text( $unsecure ? $this->mAmpDb->fmttrue : $this->mAmpDb->fmtfalse ).','.
                                               $this->mAmpDb->Format_Text( $catalog ).')'
                                             );

            if ( $result )
            {
                $this->mName = $name;
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcmethod_class.install',
                                        'Unable to insert method row into xmlrpcmethods table', LOGGER_ERROR );
        }
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcmethod_class.install',
            'Wrong parameters', LOGGER_ERROR );

        return $result;
    }

    /*!
     @function Uninstall

     @abstract Uninstalls a method

     @result True if the method has been uninstalled.
     */
    function Uninstall()
    {
        $result = false;

        if ( $this->mName )
        {
            // Removes permissions
            //
            $this->mAmpDb->Execute( 'DELETE FROM xmlrpcpermissions '.
                                    'WHERE method='.$this->mAmpDb->Format_Text( $this->mName ) );

            $result = &$this->mAmpDb->Execute( 'DELETE FROM xmlrpcmethods '.
                                               'WHERE name='.$this->mAmpDb->Format_Text( $this->mName ) );
            if ( $result ) $this->mName = '';
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcmethod_class.uninstall',
                                        'Unable to remove method row from xmlrpcmethods table', LOGGER_ERROR );
        }
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcmethod_class.uninstall',
                                    'Wrong parameters', LOGGER_ERROR );

        return $result;
    }

    /*!
     @function Update

     @abstract Updates an existing method.

     @param function string - Function name.
     @param handler string - Handler file name, without extension.
     @param signature string - Method signature, refer to XMLRPC specifications.
     @param docstring string - Method description.

     @result True if the method has been updated.
     */
    function Update( $function, $handler, $signature = '', $docstring = '', $unsecure = 0, $catalog = '' )
    {
        $result = false;

        if ( $this->mName and $function and $handler )
        {
            $result = &$this->mAmpDb->Execute( 'UPDATE xmlrpcmethods '.
                                               'SET function='.$this->mAmpDb->Format_Text( $function ).','.
                                               'signature='.$this->mAmpDb->Format_Text( $signature ).','.
                                               'docstring='.$this->mAmpDb->Format_Text( $docstring ).','.
                                               'handler='.$this->mAmpDb->Format_Text( $handler ).','.
                                               'catalog='.$this->mAmpDb->Format_Text( $catalog ).','.
                                               'unsecure='.$this->mAmpDb->Format_Text( $unsecure ? $this->mAmpDb->fmttrue : $this->mAmpDb->fmtfalse ).
                                               ' WHERE name='.$this->mAmpDb->Format_TexT( $this->mName )
                                             );

            if ( !$result ) $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcmethod_class.install',
                                                   'Unable to update method row into xmlrpcmethods table', LOGGER_ERROR );
        }
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcmethod_class.install',
                                    'Wrong parameters', LOGGER_ERROR );

        return $result;
    }
}


?>