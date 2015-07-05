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
// $Id: Group.php,v 1.6 2004-07-08 15:04:23 alex Exp $

package('com.solarix.ampoliros.site.user');

class Group extends Object {
    public $mrAmpDb;
    public $mrSiteDb;
    public $siteserial;
    public $groupid;

    /*!
     @function Group
    
     @abstract Class constructor
     */
    public function Group(DBLayer $rampDb, DBLayer $rsiteDb, $siteserial, $groupid = 0) {
        $this -> mrAmpDb = $rampDb;
        $this -> mrSiteDb = $rsiteDb;

        if ($siteserial)
            $this -> siteserial = $siteserial;
        else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $this -> mLog -> LogDie('ampoliros.users_library.group_class.group', 'Invalid site serial');
        }
        $this -> groupid = $groupid;
    }

    // Create a new group
    public function createGroup($groupdata) {
        $result = false;

        import('com.solarix.ampoliros.util.Hook');
        $hook = new Hook($this -> mrAmpDb, 'ampoliros', 'site.group.add');
        if ($hook -> CallHooks('calltime', $this, array('siteserial' => $this -> siteserial, 'groupdata' => $this -> groupdata)) == HOOK_RESULT_OK) {
            if (($this -> groupid == 0) & (strlen($groupdata['groupname']) > 0)) {
                // Check if a group with this name already exists
                $groupquery = & $this -> mrSiteDb -> Execute('SELECT groupname FROM groups WHERE groupname = '.$this -> mrSiteDb -> Format_Text($groupdata['groupname']));
                if ($groupquery -> NumRows() == 0) {
                    $groupsseq = $this -> mrSiteDb -> NextSeqValue('groups_id_seq');

                    $ins = 'INSERT INTO groups '.'VALUES ( '.$groupsseq.','.$this -> mrSiteDb -> Format_Text($groupdata['groupname']).')';
                    $this -> mrSiteDb -> Execute($ins);
                    $this -> groupid = $groupsseq;

                    if ($hook -> CallHooks('groupadded', $this, array('siteserial' => $this -> siteserial, 'groupdata' => $this -> groupdata, 'groupid' => $this -> groupid)) != HOOK_RESULT_OK)
                        $result = false;
                } else {
                    import('com.solarix.ampoliros.io.log.Logger');
                    $log = new Logger(AMP_LOG);
                    $log -> LogEvent('ampoliros.users_library.group_class.creategroup', 'Attempted to create an already existing group', LOGGER_ERROR);
                }
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -LogEvent('ampoliros.users_library.group_class.creategroup', 'Invalid groupname or access to a member for a not initialized group object', LOGGER_ERROR);
            }
        }

        return $result;
    }

    // Change group data
    public function editGroup($groupdata) {
        $result = false;

        if (($this -> groupid != 0) & (strlen($groupdata['groupname']) > 0)) {
            $groupquery = & $this -> mrSiteDb -> Execute('SELECT groupname FROM groups WHERE groupname = '.$this -> mrSiteDb -> Format_Text($groupdata['groupname']));
            if ($groupquery -> NumRows() == 0) {
                $upd = 'UPDATE groups SET groupname = '.$this -> mrSiteDb -> Format_Text($groupdata['groupname']).' WHERE id='. (int) $this -> groupid;
                $this -> mrSiteDb -> Execute($upd);
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.users_library.group_class.editgroup', 'No groups with specified name ('.$groupdata['groupname'].') exists', LOGGER_ERROR);
            }
        } else {
            import('com.solarix.ampoliros.io.log.Logger');
            $log = new Logger(AMP_LOG);
            $log -> LogEvent('ampoliros.users_library.group_class.editgroup', 'Invalid group id ('.$this -> groupid.') or groupname ('.$groupdata['groupname'].')', LOGGER_ERROR);
        }
        return $result;
    }

    // Remove group
    public function removeGroup($deleteuserstoo) {
        $result = false;

        import('com.solarix.ampoliros.util.Hook');
        $hook = new Hook($this -> mrAmpDb, 'ampoliros', 'site.group.remove');
        if ($hook -> CallHooks('calltime', $this, array('siteserial' => $this -> siteserial, 'groupid' => $this -> groupid)) == HOOK_RESULT_OK) {
            if ($this -> groupid != 0) {
                if ($this -> mrSiteDb -> Execute('DELETE FROM groups '.'WHERE id='. (int) $this -> groupid)) {
                    // Check if we must delete users in this group
                    if ($deleteuserstoo == true) {
                        $usersquery = & $this -> mrAmpDb -> Execute('SELECT id '.'FROM users '.'WHERE siteid='. (int) $this -> siteserial.' AND groupid='. (int) $this -> groupid);
                        $numusers = $usersquery -> NumRows();

                        if ($numusers > 0) {
                            // Remove users in this group
                            while (!$usersquery -> eof) {
                                $usdata = $usersquery -> Fields();

                                Carthag :: import('com.solarix.ampoliros.site.user.User');
                                $tmpuser = new user($this -> mrAmpDb, $this -> siteserial, $usdata['id']);
                                $tmpuser -> removeuser();

                                $usersquery -> MoveNext();
                                //delete $tmpuser;
                            }
                        }
                    } else {
                        $this -> mrAmpDb -> Execute("UPDATE users SET groupid = '0' WHERE groupid=". (int) $this -> groupid.' AND siteid ='.$this -> siteserial);
                    }

                    if ($hook -> CallHooks('groupremoved', $this, array('siteserial' => $this -> siteserial, 'groupid' => $this -> groupid)) != HOOK_RESULT_OK)
                        $result = false;
                    $this -> groupid = 0;
                }
            } else {
                import('com.solarix.ampoliros.io.log.Logger');
                $log = new Logger(AMP_LOG);
                $log -> LogEvent('ampoliros.users_library.group_class.removegroup', "Attempted to call a member of an object that doesn't refer to any group", LOGGER_ERROR);
            }
        }

        return $result;
    }

    // Get users list
    public function getUsersList() {
        if ($this -> groupid != 0) {
            return $this -> mrSiteDb -> Execute('SELECT * FROM users WHERE groupid='. (int) $this -> groupid.' AND siteid='. (int) $this -> siteserial);
        }
        return false;
    }
}

?>