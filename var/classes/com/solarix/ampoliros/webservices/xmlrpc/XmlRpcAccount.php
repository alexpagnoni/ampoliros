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
// $Id: XmlRpcAccount.php,v 1.4 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.webservices.xmlrpc');

import('com.solarix.ampoliros.io.log.Logger');
import('com.solarix.ampoliros.util.Hook');

// XmlRpcAccount::Create
//
define( 'AMPOLIROS_XMLRPCACCOUNT_CREATE_UNABLE_TO_INSERT_ACCOUNT',  '-1' ); // Unable to insert account into xmlrpcaccounts table.
define( 'AMPOLIROS_XMLRPCACCOUNT_CREATE_EMPTY_ACCOUNT_NAME',        '-2' ); // Empty account name.

// XmlRpcAccount::Remove
//
define( 'AMPOLIROS_XMLRPCACCOUNT_REMOVE_UNABLE_TO_REMOVE_ACCOUNT',  '-1' ); // Unable to remove account from xmlrpcaccounts table.
define( 'AMPOLIROS_XMLRPCACCOUNT_REMOVE_EMPTY_ACCOUNT_ID',          '-2' ); // Empty account id.

// XmlRpcAccount::Update
//
define( 'AMPOLIROS_XMLRPCACCOUNT_UDATE_UNABLE_TO_UPDATE_ACCOUNT',   '-1' ); // Unable to update account int xmlrpcaccounts table.
define( 'AMPOLIROS_XMLRPCACCOUNT_UPDATE_EMPTY_ACCOUNT_ID',          '-2' ); // Empty account id.
define( 'AMPOLIROS_XMLRPCACCOUNT_UPDATE_EMPTY_ACCOUNT_NAME',        '-3' ); // Empty account name.

/*!
 @class XmlRpcAccount

 @abstract Handles XmlRpc accounts.
 */
class XmlRpcAccount extends Object
{
    /*! @var mLog Logger class - Ampoliros log handler. */
    public $mLog;
    /*! @var mLog Logger class - Remote procedures log handler. */
    var $mRemoteLog;
    /*! @var mrAmpdb DbLayer class - Ampoliros database handler. */
    var $mrAmpDb;
    /*! @var mId integer - Account id. */
    var $mId;
    /*! @var mName string - Account name. */
    var $mName;
    /*! @var mHost string - Account host. */
    var $mHost;
    /*! @var mPort string - Account port. */
    var $mPort;
    /*! @var mCgi string - Account cgi. */
    var $mCgi;
    /*! @var mUsername string - Account username. */
    var $mUsername;
    /*! @var mPassword string - Account password. */
    var $mPassword;
    /*! @var mProxy string - Optional proxy hostname. */
    var $mProxy;
    /*! @var mProxyPort string - Optional proxy port. */
    var $mProxyPort;

    /*!
     @function XmlRpcAccount

     @abstract Class constructor.

     @discussion Class constructor.

     @param rampDb DbLayer class - Ampoliros database handler.
     @param id integer - Account id.
     */
    function XmlRpcAccount( &$rampDb, $id = '' )
    {
        $this->mLog = new Logger( AMP_LOG );
        $this->mRemoteLog = new Logger( AMP_REMOTE_LOG );

        $this->mId = $id;

        if ( is_object( $rampDb ) ) $this->mrAmpDb = &$rampDb;
        else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcaccount_class.xmlrpcaccount',
                                   'Invalid Ampoliros database handler', LOGGER_ERROR );

        if ( $this->mId )
        {
            $acc_query = &$this->mrAmpDb->Execute( 'SELECT * '.
                                                  'FROM xmlrpcaccounts '.
                                                  'WHERE id='.(int)$this->mId );

            if ( $acc_query->NumRows() )
            {
                $acc_data = $acc_query->Fields();

                $this->mName        = $acc_data['name'];
                $this->mHost        = $acc_data['host'];
                $this->mPort        = $acc_data['port'];
                $this->mCgi         = $acc_data['cgi'];
                $this->mUsername    = $acc_data['username'];
                $this->mPassword    = $acc_data['password'];
                $this->mProxy       = $acc_data['proxy'];
                $this->mProxyPort   = $acc_data['proxyport'];
            }
            else $this->mLog->LogEvent( 'ampoliros.xmlrpc_library.xmlrpcaccount_class.xmlrpcaccount',
                                       'Invalid account id', LOGGER_ERROR );
        }
    }

    /*!
     @function Create

     @abstract Creates a new account.

     @discussion Creates a new account.

     @param name string - Account name.
     @param host string - Account host.
     @param port string - Account port.
     @param cgi string - Account cgi.
     @param username - Account username.
     @param password - Account password.

     @result True if the account has been created.
     */
    function Create(
        $name,
        $host = 'localhost',
        $port = '80',
        $cgi = '',
        $username = '',
        $password = '',
        $proxy = '',
        $proxyPort = ''
        )
    {
        $result = false;

        $hook = new Hook( $this->mrAmpDb, 'ampoliros', 'xmlrpcaccount.create' );
        if ( $hook->CallHooks( 'calltime', $this, array( 'name' => $name, 'host' => $host, 'port' => $port, 'cgi' => $cgi, 'username' => $username, 'password' => $password ) ) == HOOK_RESULT_OK )
        {
            if ( strlen( $name ) )
            {
                $acc_seq = $this->mrAmpDb->NextSeqValue( 'xmlrpcaccounts_id_seq' );

                $result = &$this->mrAmpDb->Execute( 'INSERT INTO xmlrpcaccounts '.
                                                    'VALUES ('.
                                                    $acc_seq.','.
                                                    $this->mrAmpDb->Format_Text( $name ).','.
                                                    $this->mrAmpDb->Format_Text( $host ).','.
                                                    $this->mrAmpDb->Format_Text( $cgi ).','.
                                                    $this->mrAmpDb->Format_Text( $port ).','.
                                                    $this->mrAmpDb->Format_Text( $username ).','.
                                                    $this->mrAmpDb->Format_Text( $password ).','.
                                                    $this->mrAmpDb->Format_Text( $proxy ).','.
                                                    $this->mrAmpDb->Format_Text( $proxyPort ).')' );

                if ( $result )
                {
                    $this->mLog->LogEvent(
                        'Ampoliros',
                        'Created new remote profile account',
                        LOGGER_NOTICE
                        );

                    $this->mId = $acc_seq;
                    $this->mName = $name;
                    $this->mHost = $host;
                    $this->mCgi = $cgi;
                    $this->mPort = $port;
                    $this->mUsername = $username;
                    $this->mPassword = $password;
                    $this->mProxy = $proxy;
                    $this->mProxyPort = $proxyPort;

                    if (
                        $hook->CallHooks(
                            'accountcreated',
                            $this,
                            array(
                                'name' => $name,
                                'host' => $host,
                                'port' => $port,
                                'cgi' => $cgi,
                                'username' => $username,
                                'password' => $password,
                                'proxy' => $proxy,
                                'proxyport' => $proxyPort,
                                'id' => $this->mId
                                )
                            ) != HOOK_RESULT_OK
                        ) $result = false;
                }
                else $result = AMPOLIROS_XMLRPCACCOUNT_CREATE_UNABLE_TO_INSERT_ACCOUNT;
            }
            else
            {
                $result = AMPOLIROS_XMLRPCACCOUNT_CREATE_EMPTY_ACCOUNT_NAME;
            }
        }

        return $result;
    }

    /*!
     @function Remove

     @abstract Removes the account.

     @discussion Removes the account.

     @result True if the account has been removed.
     */
    function Remove()
    {
        $result = false;

        $hook = new Hook( $this->mrAmpDb, 'ampoliros', 'xmlrpcaccount.remove' );
        if ( $hook->CallHooks( 'calltime', $this, array( 'id' => $this->mId ) ) == HOOK_RESULT_OK )
        {
            if ( $this->mId )
            {
                $result = &$this->mrAmpDb->Execute( 'DELETE FROM xmlrpcaccounts '.
                                                    'WHERE id='.(int)$this->mId );

                if ( $result )
                {
                    $this->mLog->LogEvent( 'Ampoliros',
                                           'Removed remote profile account', LOGGER_NOTICE );

                    if ( $hook->CallHooks( 'accountremoved', $this, array( 'id' => $this->mId ) ) != HOOK_RESULT_OK ) $result = false;
                    $this->mId = '';
                }
                else $result = AMPOLIROS_XMLRPCACCOUNT_REMOVE_UNABLE_TO_REMOVE_ACCOUNT;
            }
            else $result = AMPOLIROS_XMLRPCACCOUNT_REMOVE_EMPTY_ACCOUNT_ID;
        }

        return $result;
    }

    /*!
     @function Update

     @abstract Updates the account.
     */
    function Update(
        $name,
        $host = 'localhost',
        $port = '80',
        $cgi = '',
        $username = '',
        $password = '',
        $proxy = '',
        $proxyPort = ''
        )
    {
        $result = false;

        $hook = new Hook( $this->mrAmpDb, 'ampoliros', 'xmlrpcaccount.update' );
        if ( $hook->CallHooks( 'calltime', $this, array( 'name' => $name, 'host' => $host, 'port' => $port, 'cgi' => $cgi, 'username' => $username, 'password' => $password ) ) == HOOK_RESULT_OK )
        {
            if ( $this->mId )
            {
                if ( strlen( $name ) )
                {
                    $result = &$this->mrAmpDb->Execute( 'UPDATE xmlrpcaccounts '.
                                                        'SET '.
                                                        'name='.$this->mrAmpDb->Format_Text( $name ).','.
                                                        'host='.$this->mrAmpDb->Format_Text( $host ).','.
                                                        'cgi='.$this->mrAmpDb->Format_Text( $cgi ).','.
                                                        'port='.$this->mrAmpDb->Format_Text( $port ).','.
                                                        'username='.$this->mrAmpDb->Format_Text( $username ).','.
                                                        'password='.$this->mrAmpDb->Format_Text( $password ).','.
                                                        'proxy='.$this->mrAmpDb->Format_Text( $proxy ).','.
                                                        'proxyport='.$this->mrAmpDb->Format_Text( $proxyPort ).' '.
                                                        'WHERE id='.(int)$this->mId );

                    if ( $result )
                    {
                        if ( $hook->CallHooks( 'accountudpated', $this, array( 'name' => $name, 'host' => $host, 'port' => $port, 'cgi' => $cgi, 'username' => $username, 'password' => $password, 'id' => $this->mId ) ) != HOOK_RESULT_OK ) $result = false;
                    }
                    else $result = AMPOLIROS_XMLRPCACCOUNT_UPDATE_UNABLE_TO_UPDATE_ACCOUNT;
                }
                else
                {
                    $result = AMPOLIROS_XMLRPCACCOUNT_UPDATE_EMPTY_ACCOUNT_NAME;
                }
            }
            else $result = AMPOLIROS_XMLRPCACCOUNT_REMOVE_EMPTY_ACCOUNT_ID;
        }

        return $result;
    }
}


?>