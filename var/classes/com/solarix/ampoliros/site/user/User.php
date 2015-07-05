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
// $Id: User.php,v 1.6 2004-07-08 15:04:23 alex Exp $

package('com.solarix.ampoliros.site.user');

function GetStoreID(DBLayer $ampdb, $username) {
    $tmpuquery = $ampdb -> Execute('SELECT siteid FROM users WHERE username='.$ampdb -> Format_Text($username));
    $tmpsquery = $ampdb -> Execute('SELECT siteid FROM sites WHERE id='.$ampdb -> Format_Text($tmpuquery -> Fields('siteid')));
    return $tmpsquery -> Fields('siteid');
}

function GetSiteID(DBLayer $rampDb, $userName) {
    return GetStoreId($rampDn, $userName);
}

/*!
 @class User

 @abstract User management
 */
class User extends Object {
    private $mrAmpDb;
    private $siteserial;
    private $userid;
    private $username;

    /*!
     @param rampDb DbLayer class - Ampoliros database handler.
     @param siteSerial integer - Site serial number.
     @param userId integer - User id number.
     */
    public function User(DBLayer $rampDb, $siteSerial, $userId = 0) {
        $this -> mrAmpDb = $rampDb;
        $this -> siteserial = $siteSerial;
        $this -> userid = $userId;

        if (strlen($this -> userid)) {
            if (isset($GLOBALS['gEnv']['runtime']['ampoliros']['users']['username_check'][(int) $this -> userid])) {
                $this -> username = $GLOBALS['gEnv']['runtime']['ampoliros']['users']['username_check'][(int) $this -> userid];
            } else {
                $uquery = $this -> mrAmpDb -> Execute('SELECT username FROM users WHERE id='. (int) $this -> userid);

                if ($uquery) {
                    $this -> username = $uquery -> Fields('username');
                    $GLOBALS['gEnv']['runtime']['ampoliros']['users']['username_check'][(int) $this -> userid] = $this -> username;
                    $uquery -> free();
                }
                
                $uquery->free();
            }
        }
    }

    /*!
     @abstract Sets the user id.
     */
    public function setUserId($uid) {
        $this -> userid = $uid;
        return true;
    }

    /*!
     @function SetUserIdByUsername
    
     @abstract Sets the user id by username.
     */
    public function setUserIdByUsername($username) {
        if (!empty($username)) {
            $uquery = $this -> mrAmpDb -> Execute('SELECT id '.'FROM users '.'WHERE username='.$this -> mrAmpDb -> Format_Text($username));
            $this -> userid = $uquery -> Fields('id');
            return $uquery -> Fields('id');
        }
        return false;
    }

    /*!
     @function CreateUser
    
     @abstract Creates a new user.
     */
    public function createUser($userdata) {
        $result = false;
        $userdata['username'] = str_replace(':', '', $userdata['username']);
        $userdata['username'] = str_replace('|', '', $userdata['username']);
        $userdata['username'] = str_replace('/', '', $userdata['username']);
        $userdata['username'] = str_replace('\\', '', $userdata['username']);

        import('com.solarix.ampoliros.util.Hook');
        $hook = new Hook($this -> mrAmpDb, 'ampoliros', 'site.user.add');
        if ($hook -> CallHooks('calltime', $this, array('siteserial' => $this -> siteserial, 'userdata' => $userdata)) == HOOK_RESULT_OK) {
            if ($this -> userid == 0) {
                $max_users_query = $this -> mrAmpDb -> Execute('SELECT maxusers,siteid FROM sites WHERE id='. (int) $userdata['siteid']);
                $goon = true;

                if ($max_users_query -> Fields('maxusers')) {
                    $users_num_query = & $this -> mrAmpDb -> Execute('SELECT id FROM users WHERE siteid='. (int) $userdata['siteid']);

                    if ($users_num_query -> NumRows() >= $max_users_query -> Fields('maxusers'))
                        $goon = false;
                }

                if ($goon) {
                    // Check if the given username is unique
                    $uquery = $this -> mrAmpDb -> Execute('SELECT * FROM users WHERE username='.$this -> mrAmpDb -> Format_Text($userdata['username']));

                    if (($uquery -> NumRows() == 0) & (strlen($userdata['username']) > 0) & (strlen($userdata['password']) > 0) & (strlen($userdata['groupid']) > 0)) {
                        $seqval = $this -> mrAmpDb -> NextSeqValue('users_id_seq');
                        $user = 'INSERT into users values ( '.$seqval.',';
                        $user.= $userdata['siteid'].',';
                        $user.= $userdata['groupid'].',';
                        $user.= $this -> mrAmpDb -> Format_Text($userdata['username']).',';
                        $user.= $this -> mrAmpDb -> Format_Text(md5($userdata['password'])).',';
                        $user.= $this -> mrAmpDb -> Format_Text($userdata['fname']).',';
                        $user.= $this -> mrAmpDb -> Format_Text($userdata['lname']).',';
                        $user.= $this -> mrAmpDb -> Format_Text($userdata['otherdata']).',';
                        $user.= $this -> mrAmpDb -> Format_Text($userdata['email']).')';

                        $this -> mrAmpDb -> Execute($user);
                        $this -> userid = $seqval;

                        $result = $seqval;

                        OpenLibrary('misc.library');
                        mkdirs(SITESTUFF_PATH.$max_users_query -> Fields('siteid').'/users/'.$userdata['username'].'/', 0755);

                        if ($hook -> CallHooks('useradded', $this, array('siteserial' => $this -> siteserial, 'userdata' => $userdata)) != HOOK_RESULT_OK)
                            $result = false;
                    }
                }
            }
        }

        return $result;
    }

    /*!
     @function CreateAdminUser
    
     @abstract Creates a new user as site superuser.
     */
    public function createAdminUser($siteid, $sitepassword) {
        $sitesquery = $this -> mrAmpDb -> Execute('SELECT id '.'FROM sites '.'WHERE siteid='.$this -> mrAmpDb -> Format_Text($siteid));

        $userdata['siteid'] = $sitesquery -> Fields('id');
        $userdata['username'] = $siteid;
        $userdata['password'] = $sitepassword;
        $userdata['groupid'] = 0;
        $sitesquery -> free();
        $this -> createuser($userdata);
    }

    /*!
     @function EditUser
    
     @abstract Edits user data.
     */
    public function editUser($userdata) {
        $result = false;

        if ($this -> userid != 0) {
            if ((!empty($userdata['username'])) & (strlen($userdata['groupid']) > 0)) {
                $upd = 'UPDATE users SET groupid = '.$userdata['groupid'];
                $upd.= ', username = '.$this -> mrAmpDb -> Format_Text($userdata['username']);
                $upd.= ', fname = '.$this -> mrAmpDb -> Format_Text($userdata['fname']);
                $upd.= ', lname = '.$this -> mrAmpDb -> Format_Text($userdata['lname']);
                $upd.= ', otherdata = '.$this -> mrAmpDb -> Format_Text($userdata['otherdata']);
                $upd.= ', email = '.$this -> mrAmpDb -> Format_Text($userdata['email']);
                $upd.= ' WHERE id='. (int) $this -> userid;

                //$this->htp->chpasswd( $userdata['username'], $userdata['password'] );

                unset($GLOBALS['gEnv']['runtime']['ampoliros']['users']['username_check'][(int) $this -> userid]);
                unset($GLOBALS['gEnv']['runtime']['ampoliros']['users']['getgroup'][(int) $this -> userid]);

                $result = & $this -> mrAmpDb -> Execute($upd);
                if (strlen($userdata['password']))
                    $this -> ChPasswd($userdata['password']);
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.users_library.users_class.edituser', 'Empty username or group id', LOGGER_WARNING);
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.users_library.users_class.edituser', 'Invalid user id '.$this -> userid, LOGGER_WARNING);
        }
        return $result;
    }

    /*!
     @function ChPasswd
    
     @abstract Changes user password.
     */
    public function chPasswd($newpassword) {
        $result = false;

        if ($this -> userid != 0) {
            $uquery = $this -> mrAmpDb -> Execute('SELECT username '.'FROM users '.'WHERE id='. (int) $this -> userid);

            $squery = $this -> mrAmpDb -> Execute('SELECT id '.'FROM sites '.'WHERE siteid='.$this -> mrAmpDb -> Format_Text($uquery -> Fields('username')));

            if ($squery -> NumRows()) {
                $empty = '';

                Carthag :: import('com.solarix.ampoliros.site.Site');

                $tmpsite = new Site($this -> mrAmpDb, $uquery -> Fields('username'), $empty);
                $result = $tmpsite -> ChPasswd($newpassword);
            } else
                if (!empty($newpassword)) {
                    $upd.= 'UPDATE users SET password = '.$this -> mrAmpDb -> Format_Text(md5($newpassword)).' WHERE id='. (int) $this -> userid;
                    //$this->htp->chpasswd( $uquery->Fields( 'username' ), $newpassword );
                    $result = $this -> mrAmpDb -> Execute($upd);
                }
        }

        return $result;
    }

    /*!
     @function GetUserData
    
     @abstract Returns user data array.
     */
    public function getUserData() {
        $result = false;

        if ($this -> userid != 0) {
            $uquery = $this -> mrAmpDb -> Execute('SELECT * FROM users WHERE id='. (int) $this -> userid);
            $result = $uquery -> Fields();
        }

        return $result;
    }

    /*!
     @function GetGroup
    
     @abstract Returns the user group.
     */
    public function getGroup() {
        $result = false;

        if ($this -> userid != 0) {
            if (isset($GLOBALS['gEnv']['runtime']['ampoliros']['users']['getgroup'][(int) $this -> userid])) {
                $result = $GLOBALS['gEnv']['runtime']['ampoliros']['users']['getgroup'][(int) $this -> userid];
            } else {
                $uquery = & $this -> mrAmpDb -> Execute('SELECT groupid FROM users WHERE id='. (int) $this -> userid);
                $result = $uquery -> Fields('groupid');
                $GLOBALS['gEnv']['runtime']['ampoliros']['users']['getgroup'][(int) $this -> userid] = $result;
            }
        }

        return $result;
    }

    /*!
     @function RemoveUser
    
     @abstract Removes the user.
     */
    public function removeUser() {
        import('com.solarix.ampoliros.util.Hook');

        $hook = new Hook($this -> mrAmpDb, 'ampoliros', 'site.user.remove');
        if ($hook -> CallHooks('calltime', $this, array('siteserial' => $this -> siteserial, 'userid' => $this -> userid)) == HOOK_RESULT_OK) {
            if ($this -> userid != 0) {
                $result = $this -> mrAmpDb -> Execute('DELETE FROM users '.'WHERE siteid='. (int) $this -> siteserial.' '.'AND id='. (int) $this -> userid);

                // Remove user dir

                $site_query = & $GLOBALS['gEnv']['root']['db'] -> Execute('SELECT siteid '.'FROM sites '.'WHERE id='. (int) $this -> siteserial);

                OpenLibrary('misc.library');

                if (SITESTUFF_PATH.$site_query -> Fields('siteid').'/users/'.$this -> username != SITESTUFF_PATH.$site_query -> Fields('siteid').'/users/') {
                    RecRemoveDir(SITESTUFF_PATH.$site_query -> Fields('siteid').'/users/'.$this -> username, 0755);
                }

                // Remove cached items

                Carthag :: import('com.solarix.ampoliros.datatransfer.cache.CacheGarbageCollector');
                $cache_gc = new CacheGarbageCollector();
                $cache_gc -> RemoveUserItems((int) $this -> userid);

                //$this->htp->remuser( $this->username );
                if ($hook -> CallHooks('userremoved', $this, array('siteserial' => $this -> siteserial, 'userid' => $this -> userid)) != HOOK_RESULT_OK)
                    $result = false;
                $this -> userid = 0;
            }
        }

        return $result;
    }

    /*!
     @function ChangeGroup
    
     @abstract Changes user group.
     */
    public function changeGroup($userdata) {
        if (($this -> userid != 0) & (!empty($userdata))) {
            $this -> mrAmpDb -> Execute('UPDATE users SET groupid='. (int) $userdata['groupid'].' WHERE id='. (int) $this -> userid);
            $GLOBALS['gEnv']['runtime']['ampoliros']['users']['getgroup'][(int) $this -> userid] = $userdata['groupid'];
            return true;
        }
        return false;
    }
}

?>