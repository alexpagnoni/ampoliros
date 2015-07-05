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
// $Id: XmlRpc_Server.php,v 1.3 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.webservices.xmlrpc');

global $_xmlrpcs_listMethods_sig;
global $_xmlrpcs_listMethods_doc;
global $_xmlrpcs_methodSignature_sig;
global $_xmlrpcs_methodSignature_doc;
global $_xmlrpcs_methodHelp_sig;
global $_xmlrpcs_methodHelp_doc;
global $_xmlrpcs_multicall_sig;
global $_xmlrpcs_multicall_doc;
global $_xmlrpcs_dmap;
global $_xmlrpc_debuginfo;
global $xmlrpcI4;
global $xmlrpcInt;
global $xmlrpcBoolean;
global $xmlrpcDouble;
global $xmlrpcString;
global $xmlrpcDateTime;
global $xmlrpcBase64;
global $xmlrpcArray;
global $xmlrpcStruct;
global $xmlrpcTypes;
global $xmlEntities;
global $xmlrpcerr;
global $xmlrpcstr;
global $xmlrpc_defencoding;
global $xmlrpcName;
global $xmlrpcVersion;
global $xmlrpcerruser;
global $xmlrpcerrxml;
global $xmlrpc_backslash;
global $_xh;


// Copyright (c) 1999,2000,2002 Edd Dumbill.
// All rights reserved.
//
// Redistribution and use in source and binary forms, with or without
// modification, are permitted provided that the following conditions
// are met:
//
//    * Redistributions of source code must retain the above copyright
//      notice, this list of conditions and the following disclaimer.
//
//    * Redistributions in binary form must reproduce the above
//      copyright notice, this list of conditions and the following
//      disclaimer in the documentation and/or other materials provided
//      with the distribution.
//
//    * Neither the name of the "XML-RPC for PHP" nor the names of its
//      contributors may be used to endorse or promote products derived
//      from this software without specific prior written permission.
//
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
// "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
// LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
// FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
// REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
// INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
// (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
// SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
// HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
// STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
// ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
// OF THE POSSIBILITY OF SUCH DAMAGE.

    // XML RPC Server class
    // requires: xmlrpc.inc

    // listMethods: either a string, or nothing
    $_xmlrpcs_listMethods_sig=array(array($xmlrpcArray, $xmlrpcString), array($xmlrpcArray));
    $_xmlrpcs_listMethods_doc='This method lists all the methods that the XML-RPC server knows how to dispatch';
    function _xmlrpcs_listMethods($server, $m)
    {
        global $xmlrpcerr, $xmlrpcstr, $_xmlrpcs_dmap;
        $v=new xmlrpcval();
        $dmap=$server->dmap;
        $outAr=array();
        for(reset($dmap); list($key, $val)=each($dmap); )
        {
            $outAr[]=new xmlrpcval($key, 'string');
        }
        $dmap=$_xmlrpcs_dmap;
        for(reset($dmap); list($key, $val)=each($dmap); )
        {
            $outAr[]=new xmlrpcval($key, 'string');
        }
        $v->addArray($outAr);
        return new xmlrpcresp($v);
    }

    $_xmlrpcs_methodSignature_sig=array(array($xmlrpcArray, $xmlrpcString));
    $_xmlrpcs_methodSignature_doc='Returns an array of known signatures (an array of arrays) for the method name passed. If no signatures are known, returns a none-array (test for type != array to detect missing signature)';
    function _xmlrpcs_methodSignature($server, $m)
    {
        global $xmlrpcerr, $xmlrpcstr, $_xmlrpcs_dmap;

        $methName=$m->getParam(0);
        $methName=$methName->scalarval();
        if (ereg("^system\.", $methName))
        {
            $dmap=$_xmlrpcs_dmap; $sysCall=1;
        }
        else
        {
            $dmap=$server->dmap; $sysCall=0;
        }
        //  print "<!-- ${methName} -->\n";
        if (isset($dmap[$methName]))
        {
            if ($dmap[$methName]['signature'])
            {
                $sigs=array();
                $thesigs=$dmap[$methName]['signature'];
                for($i=0; $i<sizeof($thesigs); $i++)
                {
                    $cursig=array();
                    $inSig=$thesigs[$i];
                    for($j=0; $j<sizeof($inSig); $j++)
                    {
                        $cursig[]=new xmlrpcval($inSig[$j], 'string');
                    }
                    $sigs[]=new xmlrpcval($cursig, 'array');
                }
                $r=new xmlrpcresp(new xmlrpcval($sigs, 'array'));
            }
            else
            {
                $r=new xmlrpcresp(new xmlrpcval('undef', 'string'));
            }
        }
        else
        {
            $r=new xmlrpcresp(0,$xmlrpcerr['introspect_unknown'], $xmlrpcstr['introspect_unknown']);
        }
        return $r;
    }

    $_xmlrpcs_methodHelp_sig=array(array($xmlrpcString, $xmlrpcString));
    $_xmlrpcs_methodHelp_doc='Returns help text if defined for the method passed, otherwise returns an empty string';
    function _xmlrpcs_methodHelp($server, $m)
    {
        global $xmlrpcerr, $xmlrpcstr, $_xmlrpcs_dmap;

        $methName=$m->getParam(0);
        $methName=$methName->scalarval();
        if (ereg("^system\.", $methName))
        {
            $dmap=$_xmlrpcs_dmap; $sysCall=1;
        }
        else
        {
            $dmap=$server->dmap; $sysCall=0;
        }
        // print "<!-- ${methName} -->\n";
        if (isset($dmap[$methName]))
        {
            if ($dmap[$methName]['docstring'])
            {
                $r=new xmlrpcresp(new xmlrpcval($dmap[$methName]['docstring']), 'string');
            }
            else
            {
                $r=new xmlrpcresp(new xmlrpcval('', 'string'));
            }
        }
        else
        {
            $r=new xmlrpcresp(0, $xmlrpcerr['introspect_unknown'], $xmlrpcstr['introspect_unknown']);
        }
        return $r;
    }

    $_xmlrpcs_multicall_sig = array(array($xmlrpcArray, $xmlrpcArray));
    $_xmlrpcs_multicall_doc = 'Boxcar multiple RPC calls in one request. See http://www.xmlrpc.com/discuss/msgReader$1208 for details';

    function _xmlrpcs_multicall_error($err)
    {
        if (is_string($err))
        {
            global $xmlrpcerr, $xmlrpcstr;
            $str  = $xmlrpcstr["multicall_${err}"];
            $code = $xmlrpcerr["multicall_${err}"];
        }
        else
        {
            $code = $err->faultCode();
            $str = $err->faultString();
        }
        $struct['faultCode'] = new xmlrpcval($code, 'int');
        $struct['faultString'] = new xmlrpcval($str, 'string');
        return new xmlrpcval($struct, 'struct');
    }

    function _xmlrpcs_multicall_do_call($server, $call)
    {
        if ($call->kindOf() != 'struct')
        {
            return _xmlrpcs_multicall_error('notstruct');
        }
        $methName = $call->structmem('methodName');
        if (!$methName)
        {
            return _xmlrpcs_multicall_error('nomethod');
        }
        if ($methName->kindOf() != 'scalar' || $methName->scalartyp() != 'string')
        {
            return _xmlrpcs_multicall_error('notstring');
        }
        if ($methName->scalarval() == 'system.multicall')
        {
            return _xmlrpcs_multicall_error('recursion');
        }

        $params = $call->structmem('params');
        if (!$params)
        {
            return _xmlrpcs_multicall_error('noparams');
        }
        if ($params->kindOf() != 'array')
        {
            return _xmlrpcs_multicall_error('notarray');
        }
        $numParams = $params->arraysize();

        $msg = new xmlrpcmsg($methName->scalarval());
        for ($i = 0; $i < $numParams; $i++)
        {
            $msg->addParam($params->arraymem($i));
        }

        $result = $server->execute($msg);

        if ($result->faultCode() != 0)
        {
            return _xmlrpcs_multicall_error($result);    // Method returned fault.
        }

        return new xmlrpcval(array($result->value()), 'array');
    }

    function _xmlrpcs_multicall($server, $m)
    {
        $calls = $m->getParam(0);
        $numCalls = $calls->arraysize();
        $result = array();

        for ($i = 0; $i < $numCalls; $i++)
        {
            $call = $calls->arraymem($i);
            $result[$i] = _xmlrpcs_multicall_do_call($server, $call);
        }

        return new xmlrpcresp(new xmlrpcval($result, 'array'));
    }

    $_xmlrpcs_dmap=array(
        'system.listMethods' => array(
            'function' => '_xmlrpcs_listMethods',
            'signature' => $_xmlrpcs_listMethods_sig,
            'docstring' => $_xmlrpcs_listMethods_doc),
        'system.methodHelp' => array(
            'function' => '_xmlrpcs_methodHelp',
            'signature' => $_xmlrpcs_methodHelp_sig,
            'docstring' => $_xmlrpcs_methodHelp_doc),
        'system.methodSignature' => array(
            'function' => '_xmlrpcs_methodSignature',
            'signature' => $_xmlrpcs_methodSignature_sig,
            'docstring' => $_xmlrpcs_methodSignature_doc),
        'system.multicall' => array(
            'function' => '_xmlrpcs_multicall',
            'signature' => $_xmlrpcs_multicall_sig,
            'docstring' => $_xmlrpcs_multicall_doc
        )
    );

    $_xmlrpc_debuginfo='';
    function xmlrpc_debugmsg($m)
    {
        global $_xmlrpc_debuginfo;
        $_xmlrpc_debuginfo=$_xmlrpc_debuginfo . $m . "\n";
    }

    class xmlrpc_server extends Object
    {
        var $dmap=array();

        function xmlrpc_server($dispMap='', $serviceNow=1)
        {
            global $HTTP_RAW_POST_DATA;
            // dispMap is a dispatch array of methods
            // mapped to function names and signatures
            // if a method
            // doesn't appear in the map then an unknown
            // method error is generated
            /* milosch - changed to make passing dispMap optional.
             * instead, you can use the class add_to_map() function
             * to add functions manually (borrowed from SOAPX4)
             */
            if($dispMap)
            {
                $this->dmap = $dispMap;
                if($serviceNow)
                {
                    $this->service();
                }
            }
        }

        function serializeDebug()
        {
            global $_xmlrpc_debuginfo;
            if ($_xmlrpc_debuginfo!='')
            {
                return "<!-- DEBUG INFO:\n\n" . $_xmlrpc_debuginfo . "\n-->\n";
            }
            else
            {
                return '';
            }
        }

        function service()
        {
            global $xmlrpc_defencoding;

            $r=$this->parseRequest();
            $payload='<?xml version="1.0" encoding="' . $xmlrpc_defencoding . '"?>' . "\n"
                . $this->serializeDebug()
                . $r->serialize();
            Header("Content-type: text/xml\r\nContent-length: " . 
            strlen($payload));
            print $payload;
        }

        /*
        add a method to the dispatch map
        */
        function add_to_map($methodname,$function,$sig,$doc)
        {
            $this->dmap[$methodname] = array(
                'function'  => $function,
                'signature' => $sig,
                'docstring' => $doc
            );
        }

        function verifySignature($in, $sig)
        {
            for($i=0; $i<sizeof($sig); $i++)
            {
                // check each possible signature in turn
                $cursig=$sig[$i];
                if (sizeof($cursig)==$in->getNumParams()+1)
                {
                    $itsOK=1;
                    for($n=0; $n<$in->getNumParams(); $n++)
                    {
                        $p=$in->getParam($n);
                        // print "<!-- $p -->\n";
                        if ($p->kindOf() == 'scalar')
                        {
                            $pt=$p->scalartyp();
                        }
                        else
                        {
                            $pt=$p->kindOf();
                        }
                        // $n+1 as first type of sig is return type
                        if ($pt != $cursig[$n+1])
                        {
                            $itsOK=0;
                            $pno=$n+1; $wanted=$cursig[$n+1]; $got=$pt;
                            break;
                        }
                    }
                    if ($itsOK)
                    {
                        return array(1);
                    }
                }
            }
            return array(0, "Wanted ${wanted}, got ${got} at param ${pno})");
        }

        function parseRequest($data='')
        {
            global $_xh,$HTTP_RAW_POST_DATA;
            global $xmlrpcerr, $xmlrpcstr, $xmlrpcerrxml, $xmlrpc_defencoding,
            $_xmlrpcs_dmap;

            if ($data=='')
            {
                $data=$HTTP_RAW_POST_DATA;
            }
            $parser = xml_parser_create($xmlrpc_defencoding);

            $_xh[$parser]=array();
            $_xh[$parser]['st']='';
            $_xh[$parser]['cm']=0;
            $_xh[$parser]['isf']=0;
            $_xh[$parser]['params']=array();
            $_xh[$parser]['method']='';

            // decompose incoming XML into request structure

            xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
            xml_set_element_handler($parser, 'xmlrpc_se', 'xmlrpc_ee');
            xml_set_character_data_handler($parser, 'xmlrpc_cd');
            xml_set_default_handler($parser, 'xmlrpc_dh');
            if (!xml_parse($parser, $data, 1))
            {
                // return XML error as a faultCode
                $r=new xmlrpcresp(0,
                $xmlrpcerrxml+xml_get_error_code($parser),
                sprintf('XML error: %s at line %d',
                    xml_error_string(xml_get_error_code($parser)),
                    xml_get_current_line_number($parser)));
                xml_parser_free($parser);
            }
            else
            {
                xml_parser_free($parser);
                $m=new xmlrpcmsg($_xh[$parser]['method']);
                // now add parameters in
                $plist='';
                for($i=0; $i<sizeof($_xh[$parser]['params']); $i++)
                {
                    //print "<!-- " . $_xh[$parser]['params'][$i]. "-->\n";
                    $plist.="$i - " .  $_xh[$parser]['params'][$i]. " \n";
                    eval('$m->addParam(' . $_xh[$parser]['params'][$i]. ');');
                }
                // uncomment this to really see what the server's getting!
                // xmlrpc_debugmsg($plist);

                $r = $this->execute($m);
            }
            return $r;
        }

        function execute ($m)
        {
            global $xmlrpcerr, $xmlrpcstr, $_xmlrpcs_dmap;
            // now to deal with the method
            $methName = $m->method();
            $sysCall = ereg("^system\.", $methName);
            $dmap = $sysCall ? $_xmlrpcs_dmap : $this->dmap;

            if (!isset($dmap[$methName]['function']))
            {
                // No such method
                return new xmlrpcresp(0,
                    $xmlrpcerr['unknown_method'],
                    $xmlrpcstr['unknown_method']);
            }

            // Check signature.
            if (isset($dmap[$methName]['signature']))
            {
                $sig = $dmap[$methName]['signature'];
                list ($ok, $errstr) = $this->verifySignature($m, $sig);
                if (!$ok)
                {
                    // Didn't match.
                    return new xmlrpcresp(0,
                        $xmlrpcerr['incorrect_params'],
                        $xmlrpcstr['incorrect_params'] . ": ${errstr}");
                }
            }

            $func = $dmap[$methName]['function'];

            if ($sysCall)
            {
                return call_user_func($func, $this, $m);
            }
            else
            {
                return call_user_func($func, $m);
            }
        }

        function echoInput()
        {
            global $HTTP_RAW_POST_DATA;

            // a debugging routine: just echos back the input
            // packet as a string value

            $r=new xmlrpcresp;
            $r->xv=new xmlrpcval( "'Aha said I: '" . $HTTP_RAW_POST_DATA, 'string');
            print $r->serialize();
        }
    }


?>