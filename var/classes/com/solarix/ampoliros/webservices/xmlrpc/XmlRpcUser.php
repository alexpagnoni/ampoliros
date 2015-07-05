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
// $Id: XmlRpcUser.php,v 1.3 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.webservices.xmlrpc');

import('com.solarix.ampoliros.io.log.Logger');

/*!
 @class XmlRpcUser

 @abstract XML RPC interface users.
 */
class XmlRpcUser extends Object
{
    var $mLog;
    var $mAmpDb;
    var $mUserId;
    var $mProfileId = 0;
    var $mSiteId = 0;

    /*!
     @function XmlRpcUser

     @abstract class constructor.

     @param ampDb dblayer class - Ampoliros database handler.
     @param userId integer - User id serial.
     */
    function XmlRpcUser( &$ampDb, $userId = '' )
    {
        $this->mLog = new Logger( AMP_LOG );

        if ( $ampDb ) $this->mAmpDb = &$ampDb;
        else $this->mLog->LogDie( 'ampoliros.xmlrpc_library.xmlrpcuser_class.xmlrpc_user',
                                  'Invalid Ampoliros database handler' );

        $this->mUserId = $userId;
    }

    /*!
     @function Add

     @abstract Adds a new xmlrpc user.

     @discussion Anonymous user can be added by simply leaving username
     and password arguments empty.

     @param username string - Username.
     @param password string - Password in clear text.
     @param profileId integer - Profile serial, may be empty.

     @result True if the user has been added.
     */
    function Add( $username, $password, $profileId = 0, $siteId = 0 )
    {
        $result = false;

        if ( $this->mAmpDb )
        {
            if ( !$this->mUserId )
            {
                $siteId = (int)$siteId;
                if ( !strlen( $siteId ) ) $siteId = 0;

                // :NOTE: wuh 010710
                // $username can be empty, since we can accept
                // anonymous users

                // :TODO: wuh 010710
                // It should check if the profile exists

                $query = &$this->mAmpDb->Execute( 'SELECT username '.
                                                  'FROM xmlrpcusers '.
                                                  'WHERE username='.$this->mAmpDb->Format_Text( $username ) );

                if ( !$query->NumRows() )
                {
                    $this->mUserId = $this->mAmpDb->NextSeqValue( 'xmlrpcusers_id_seq' );

                    $result = &$this->mAmpDb->Execute( 'INSERT INTO xmlrpcusers '.
                                                       'VALUES ('.
                                                       $this->mUserId.','.
                                                       $this->mAmpDb->Format_Text( $username ).','.
                                                       $this->mAmpDb->Format_Text( md5( $password ) ).','.
                                                       $profileId.','.
                                                       $siteId.')' );

                    if ( $result )
                    {
                        $this->mProfileId = $profileId;
                        $this->mSiteId = $siteId;

                        $this->mLog->LogEvent( 'Ampoliros',
                                              'Created new remote profile user', LOGGER_NOTICE );
                    }
                    else
                    {
                        $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.add',
                                              'Unable to insert xmlrpc user into xmlrpcusers table', LOGGER_ERROR );
                    }
                }
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.add',
                                        'Already assigned user for this object', LOGGER_ERROR );
        }
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.add',
                                    'Invalid Ampoliros database handler', LOGGER_ERROR );

        return $result;
    }

    /*!
     @function SetByAccount

     @abstract Set the user by his account, checking for username and password.

     @discussion This function checks if an account with the given username and password
     exists, and if it exists the object user id is set with the corresponding one.

     @param username string - Username to check.
     @param password string - Password to check.

     @result True if the account has been found.
     */
    function SetByAccount( $username, $password )
    {
        $result = false;

        if ( $this->mAmpDb )
        {
            if ( !$this->mUserId )
            {
                // :NOTE: wuh 010710
                // $username can be empty, since we can accept
                // anonymous users

                $query = &$this->mAmpDb->Execute( 'SELECT * '.
                                                  'FROM xmlrpcusers '.
                                                  'WHERE username='.$this->mAmpDb->Format_Text( $username ).' '.
                                                  'AND password='.$this->mAmpDb->Format_Text( md5( $password ) ) );

                if ( $query->NumRows() )
                {
                    $this->mUserId = $query->Fields( 'id' );
                    $this->mProfileId = $query->Fields( 'profileid' );
                    $this->mSiteId = $query->Fields( 'siteid' );

                    $result = $this->mUserId;
                }
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.setbyaccount',
                                        'Already assigned user for this object', LOGGER_ERROR );
        }
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.setbyaccount',
                                    'Invalid Ampoliros database handler', LOGGER_ERROR );

        return $result;
    }

    /*!
     @function ProfileId

     @abstract Returns the user profile id.

     @result User profile id.
     */
    function ProfileId()
    {
        $result = false;

        if ( $this->mAmpDb )
        {
            if ( $this->mUserId )
            {
                $query = &$this->mAmpDb->Execute( 'SELECT profileid '.
                                                  'FROM xmlrpcusers '.
                                                  'WHERE id='.(int)$this->mUserId );
                if ( $query->NumRows() )
                {
                    $result = $query->Fields( 'profileid' );
                }
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.profileid',
                                        'Object not assigned to an user', LOGGER_ERROR );
        }
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.profileid',
                                    'Invalid Ampoliros database handler', LOGGER_ERROR );

        return $result;
    }

    /*!
     @function Remove

     @abstract Removes a xmlrpc user.

     @result True it the user has been deleted. Function returns true even if the given user doesn't exists.
     */
    function Remove()
    {
        $result = false;

        if ( $this->mAmpDb )
        {
            if ( $this->mUserId )
            {
                $result = &$this->mAmpDb->Execute( 'DELETE FROM xmlrpcusers '.
                                                   'WHERE id='.(int)$this->mUserId );

                if ( $result )
                {
                    $this->mLog->LogEvent( 'Ampoliros',
                                          'Removed remote profile user', LOGGER_NOTICE );
                }
                else
                {
                    $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.remove',
                                          'Unable to remove xmlrpc user from xmlrpcusers table', LOGGER_ERROR );
                }
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.remove',
                                        'Object not assigned to an user', LOGGER_ERROR );
        }
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.remove',
                                    'Invalid Ampoliros database handler', LOGGER_ERROR );

        return $result;
    }

    /*!
     @function ChangePassword

     @abstract Changes xmlrpc user password.

     @param newPassword string - New password in clear text.

     @result True if the password has been changed.
     */
    function ChangePassword( $newPassword )
    {
        $result = false;

        if ( $this->mAmpDb )
        {
            if ( $this->mUserId )
            {
                $result = &$this->mAmpDb->Execute( 'UPDATE xmlrpcusers '.
                                                   'SET password='.$this->mAmpDb->Format_Text( md5( $newPassword ) ).
                                                   'WHERE id='.(int)$this->mUserId );

                if ( $result )
                {
                    $this->mLog->LogEvent( 'Ampoliros',
                                          'Change remote profile user password', LOGGER_NOTICE );
                }
                else
                {
                    $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.changepassword',
                                          'Unable to update xmlrpc user password into xmlrpcusers table', LOGGER_ERROR );
                }
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.changepassword',
                                        'Object not assigned to an user', LOGGER_ERROR );
        }
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.changepassword',
                                    'Invalid Ampoliros database handler', LOGGER_ERROR );

        return $result;
    }

    /*!
     @function CheckPassword

     @abstract Checks if the xmlrpc user password matches a given password.

     @param password string - Password to check in clear text.

     @result True if the password is the same of the xmlrpc user.
     */
    function CheckPassword( $password )
    {
        $result = false;

        if ( $this->mAmpDb )
        {
            if ( $this->mUserId )
            {
                $query = &$this->mAmpDb->Execute( 'SELECT FROM xmlrpcusers '.
                                                  'WHERE id='.(int)$this->mUserID.
                                                  ' AND password='.$this->mAmpDb->Format_Text( md5( $password ) ).')' );

                if ( $query->NumRows() ) $result = true;
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.checkpassword',
                                        'Object not assigned to an user', LOGGER_ERROR );
        }
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.checkpassword',
                                    'Invalid Ampoliros database handler', LOGGER_ERROR );

        return $result;
    }

    /*!
     @function AssignProfile

     @abstract Assign a profile to the user.

     @param profileId integer - Profile serial.

     @result True if the profile has been assigned.
     */
    function AssignProfile( $profileId )
    {
        $result = false;

        if ( $this->mAmpDb )
        {
            if ( $this->mUserId )
            {
                if ( strlen( $profileId ) )
                {
                    if ( $query = &$this->mAmpDb->Execute( 'UPDATE xmlrpcusers '.
                                                           'SET profileid='.(int)$profileId.' '.
                                                           'WHERE id='.(int)$this->mUserId ) ) $result = true;

                    else  $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.assignprofile',
                                                 'Unable to update profile id in xmlrpcusers table', LOGGER_ERROR );
                }
                else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.assignprofile',
                                            'Empty profile id', LOGGER_ERROR );
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.assignprofile',
                                        'Object not assigned to an user', LOGGER_ERROR );
        }
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcuser_class.assignprofile',
                                    'Invalid Ampoliros database handler', LOGGER_ERROR );

        return $result;
    }

    /*!
     @function AssignSite

     @abstract Assign a site to the user.

     @param siteId integer - Site serial.

     @result True if the site has been assigned.
     */
    function AssignSite( $siteId )
    {
        $result = false;

        if ( $this->mAmpDb )
        {
            if ( $this->mUserId )
            {
                $siteId = (int)$siteId;
                if ( !strlen( $siteId ) ) $siteId = 0;

                if ( $query = &$this->mAmpDb->Execute(
                    'UPDATE xmlrpcusers '.
                    'SET siteid='.(int)$siteId.' '.
                    'WHERE id='.(int)$this->mUserId ) ) $result = true;
            }
        }

        return $result;
    }
}


?>