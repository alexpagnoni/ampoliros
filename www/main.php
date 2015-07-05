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
// $Id: main.php,v 1.29 2004-07-08 15:04:26 alex Exp $

require( './auth.php' );

function main_page( $redrawFrames = FALSE )
{
	Carthag::import('com.solarix.ampoliros.hui.Hui');
 
    global $gEnv;

    if ( !$redrawFrames and is_object( $gEnv['root']['db'] ) and !defined( 'AMPOLIROS_SETUP_PHASE' ) )
    {
        import('com.solarix.ampoliros.module.ModuleConfig');
        $mod_cfg = new ModuleConfig( $gEnv['root']['db'], 'ampoliros' );
        $amp_logo_disabled = $mod_cfg->GetKey( 'ampoliros-biglogo-disabled' );
    }
    else
    {
        $amp_logo_disabled = 0;
    }

    $hui = new Hui( $gEnv['root']['db'], true );
    $hui->LoadWidget( 'page' );
    $hui->LoadWidget( 'vertgroup' );
    $hui->LoadWidget( 'button' );

    $page_params['title'] = 'Ampoliros';
    $page_params['border'] = 'false';
    if ( $redrawFrames ) $page_params['javascript'] = "parent.frames.sum.location.reload()\nparent.frames.header.location.reload()";

    $hui_page = new HuiPage( 'page', $page_params );
    $hui_vertgroup = new HuiVertGroup( 'vertgroup', array( 'align' => 'center', 'groupalign' => 'center', 'groupvalign' => 'middle', 'height' => '100%' ) );
    $hui_buttons_group = new HuiVertGroup( 'buttons_group', array( 'align' => 'center', 'groupalign' => 'center', 'groupvalign' => 'middle', 'height' => '0%' ) );
    if ( $amp_logo_disabled != '1' )
    {
        if ( $gEnv['core']['edition'] == AMP_EDITION_ASP ) $edition = '_asp';
        else $edition = '_enterprise';

        if ( isset( $hui_page->mThemeHandler->mStyle['bigdot'.$edition] ) ) $bigdot_image = $hui_page->mThemeHandler->mStyle['bigdot'.$edition];
        else $bigdot_image = $hui_page->mThemeHandler->mStyle['bigdot'];

        $hui_button = new HuiButton( 'button', array( 'action' => 'http://www.ampoliros.com', 'target' => '_top', 'image' => $bigdot_image, 'highlight' => 'false' ) );
        $hui_buttons_group->AddChild( $hui_button );
    }

    if ( !$redrawFrames and is_object( $gEnv['root']['db'] ) and !defined( 'AMPOLIROS_SETUP_PHASE' ) )
    {
        // OEM personalization
        //
        $oem_biglogo_filename = $mod_cfg->GetKey( 'oem-biglogo-filename' );
        $oem_url  = $mod_cfg->GetKey( 'oem-url' );

        if ( $mod_cfg->GetKey( 'oem-biglogo-disabled' ) != '1' )
        {
            if ( strlen( $oem_biglogo_filename ) and file_exists( CGI_PATH.$oem_biglogo_filename ) )
            {
                $oem_button = new HuiButton( 'oembutton', array( 'action' => strlen( $oem_url ) ? $oem_url : 'http://www.ampoliros.com', 'target' => '_top', 'image' => CGI_URL.$oem_biglogo_filename, 'highlight' => 'false' ) );
                $hui_buttons_group->AddChild( $oem_button );
            }
        }
    }

    $hui_vertgroup->AddChild( $hui_buttons_group );
    $hui_page->AddChild( $hui_vertgroup );
    $hui->AddChild( $hui_page );
    $hui->Render();
}

// Checks if Ampoliros is in setup phase
//
if ( !defined( 'AMPOLIROS_SETUP_PHASE' ) )
{
    main_page();
}
else
{
    Carthag::import('com.solarix.ampoliros.hui.Hui');
    Carthag::import('com.solarix.ampoliros.locale.Locale');
    Carthag::import('com.solarix.ampoliros.io.log.Logger');
    Carthag::import('com.solarix.ampoliros.locale.LocaleCountry');
    OpenLibrary( 'misc.library' );
    OpenLibrary( 'setup.library' );

    $amp_locale = new Locale( 'amp_misc_install', isset( $language ) ? $language : AMP_LANG );
    $log = new Logger( AMP_LOG );

    $hui = new Hui( $gEnv['root']['db'] );
    $hui->LoadWidget( 'button'		);
    $hui->LoadWidget( 'checkbox'    );
    $hui->LoadWidget( 'combobox'	);
    $hui->LoadWidget( 'form'		);
    $hui->LoadWidget( 'horizbar'	);
    $hui->LoadWidget( 'horizframe'	);
    $hui->LoadWidget( 'horizgroup'	);
    $hui->LoadWidget( 'grid'		);
    $hui->LoadWidget( 'image'		);
    $hui->LoadWidget( 'label'		);
    $hui->LoadWidget( 'page'		);
    $hui->LoadWidget( 'statusbar'	);
    $hui->LoadWidget( 'string'		);
    $hui->LoadWidget( 'submit'		);
    $hui->LoadWidget( 'table'		);
    $hui->LoadWidget( 'text'            );
    $hui->LoadWidget( 'titlebar'	);
    $hui->LoadWidget( 'vertframe'	);
    $hui->LoadWidget( 'vertgroup'	);

    $hui_page = new HuiPage( 'page', array( 'title' => $amp_locale->GetStr( 'ampsetup_title' ), 'javascript' => "parent.frames.sum.location.reload()\nparent.frames.header.location.reload()" ) );
    $hui_mainvertgroup = new HuiVertGroup( 'mainvertgroup' );
    $hui_titlebar = new HuiTitleBar( 'titlebar', array(
                                                       'title' => $amp_locale->GetStr( 'ampsetup_title' ),
                                                       'closewidget' => 'false',
                                                       'newwindowwidget' => 'false'
                                                      ) );
    $hui_mainvertgroup->AddChild( $hui_titlebar );

    $hui_mainframe1 = new HuiHorizFrame( 'mainframe' );
    $hui_mainframe = new HuiVertGroup( 'mainvertgroup2' );
    $hui_mainstatus = new HuiStatusBar( 'mainstatusbar' );

    // Pass dispatcher
    //
    $pass_disp = new HuiDispatcher( 'pass' );

    $pass_disp->AddEvent( 'checksystem', 'pass_checksystem' );
    function pass_checksystem( $eventData )
    {
        global $hui_mainstatus, $amp_locale, $log;

        setup_checksystem( $eventData, $log );
    }

    $pass_disp->AddEvent( 'installfiles', 'pass_installfiles' );
    function pass_installfiles( $eventData )
    {
        setup_installfiles( $eventData, $log );

        global $hui_mainstatus, $amp_locale, $log;
    }

    $pass_disp->AddEvent( 'setedition', 'pass_setedition' );
    function pass_setedition( $eventData )
    {
        global $hui_mainstatus, $amp_locale, $log;

        setup_setedition( $eventData, $log );
    }

    $pass_disp->AddEvent( 'createdblayers', 'pass_createdblayers' );
    function pass_createdblayers( $eventData )
    {
        global $hui_mainstatus, $amp_locale, $log;

        setup_dblayers( $eventData, $log );
    }

    $pass_disp->AddEvent( 'createdb', 'pass_createdb' );
    function pass_createdb( $eventData )
    {
        global $hui_mainstatus, $amp_locale, $log;

        setup_createdb( $eventData, $log );
    }

    $pass_disp->AddEvent( 'initializecomponents', 'pass_initializecomponents' );
    function pass_initializecomponents( $eventData )
    {
        global $hui_mainstatus, $amp_locale, $log;

        setup_initializecomponents( $eventData, $log );
    }

    $pass_disp->AddEvent( 'setpassword', 'pass_setpassword' );
    function pass_setpassword( $eventData )
    {
        global $hui_mainstatus, $amp_locale, $log;

        setup_setpassword( $eventData, $log );
    }

    $pass_disp->AddEvent( 'setamphost', 'pass_setamphost' );
    function pass_setamphost( $eventData )
    {
        global $hui_mainstatus, $amp_locale, $log;

        setup_setamphost( $eventData, $log );
    }

    $pass_disp->AddEvent( 'setcountry', 'pass_setcountry' );
    function pass_setcountry( $eventData )
    {
        global $hui_mainstatus, $amp_locale, $log;

        setup_setcountry( $eventData, $log );
    }

    $pass_disp->AddEvent( 'setlanguage', 'pass_setlanguage' );
    function pass_setlanguage( $eventData )
    {
        global $hui_mainstatus, $amp_locale, $log;

        setup_setlanguage( $eventData, $log );
    }

    $pass_disp->AddEvent( 'setampcentral', 'pass_setampcentral' );
    function pass_setampcentral( $eventData )
    {
        global $hui_mainstatus, $amp_locale, $log;

        setup_ampcentral( $eventData, $log );
    }

    $pass_disp->AddEvent( 'cleanup', 'pass_cleanup' );
    function pass_cleanup( $eventData )
    {
        global $hui_mainstatus, $amp_locale, $log;

        setup_cleanup( $eventData, $log );
    }

    $pass_disp->AddEvent( 'finish', 'pass_finish' );
    function pass_finish( $eventData )
    {
        global $hui_mainstatus, $amp_locale, $log;

        setup_finish( $eventData, $log );
    }

    $pass_disp->Dispatch();

    // Checks if all setup phases are completed
    //
    if ( setup_check_lock_files() )
    {
        // Removes setup lock files
        //
        setup_remove_lock_files();

        if ( !setup_remove_setup_lock_file() ) $log->logevent( 'ampoliros.root.main_php',
                                                               'Unable to remove lock file during initialization', LOGGER_ERROR );
    }

    clearstatcache();

    // Checks if there are remaining setup phases
    //
    if ( !file_exists( AMP_SETUP_LOCK ) )
    {
        main_page( TRUE );
    }
    else
    {
    	import('com.solarix.ampoliros.dblayer.DBLayerFactory');
        // System check
        //
        if ( !file_exists( TMP_PATH.'.systemchecked' ) )
        {
            $systemok = true;
            $row = 0;

            @touch( TMP_PATH.'.checkingsystem', time() );
            // !!! $page->add( new HTMLScript( 'JavaScript1.2', 'parent.frames.sum.location.reload()', '' ) );

            $hui_titlebar->mTitle .= ' - '.$amp_locale->GetStr( 'systemcheck_title' );

            $hui_info_table = new HuiTable( 'sysinfotable', array( 'headers' => $headers ) );

            // Required features

            // /ampcgi/ alias
            //
            $row = 0;

            if ( @fopen( CGI_URL.'clear.gif', 'r' ) )
            {
                $ball = $hui_page->mThemeHandler->mStyle['greenball'];
                $check_result = $amp_locale->GetStr( 'ampcgi_available_label' );
            }
            else
            {
                $ball = $hui_page->mThemeHandler->mStyle['redball'];
                $check_result = sprintf( $amp_locale->GetStr( 'ampcgi_not_available_label' ), CGI_URL, CGI_URL );
                $systemok = false;
            }

            $hui_info_table->AddChild( new HuiLabel( 'required'.$row, array( 'label' => $amp_locale->GetStr( 'required_label' ) ) ), $row, 0 );
            $hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $ball ) ), $row, 1 );
            $hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => sprintf( $amp_locale->GetStr( 'ampcgi_test_label' ), CGI_URL ) ) ), $row, 2 );
            $hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => $check_result ) ), $row, 3 );

            // PHP version check
            //
            $row++;

            //if ( ereg( "[4-9]\.[0-9]\.[5-9].*", phpversion() ) or ereg( "[4-9]\.[1-9]\.[0-9].*", phpversion() ) )
            if ( ereg( "[5-9]\.[0-9]\.[0-9].*", phpversion() ) )
            {
                $ball = $hui_page->mThemeHandler->mStyle['greenball'];
                $check_result = sprintf( $amp_locale->GetStr( 'php_available_label' ), phpversion() );
            }
            else
            {
                $ball = $hui_page->mThemeHandler->mStyle['redball'];
                $check_result = sprintf( $amp_locale->GetStr( 'php_not_available_label' ), phpversion() );
                $systemok = false;
            }

            $hui_info_table->AddChild( new HuiLabel( 'required'.$row, array( 'label' => $amp_locale->GetStr( 'required_label' ) ) ), $row, 0 );
            $hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $ball ) ), $row, 1 );
            $hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => $amp_locale->GetStr( 'php_test_label' ) ) ), $row, 2 );
            $hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => $check_result ) ), $row, 3 );

            // File upload support
            //
            $row++;

            if ( ini_get( 'file_uploads' ) == '1' )
            {
                $ball = $hui_page->mThemeHandler->mStyle['greenball'];
                $check_result = $amp_locale->GetStr( 'fileupload_available_label' );
            }
            else
            {
                $ball = $hui_page->mThemeHandler->mStyle['redball'];
                $check_result = $amp_locale->GetStr( 'fileupload_not_available_label' );
                $systemok = false;
            }

            $hui_info_table->AddChild( new HuiLabel( 'required'.$row, array( 'label' => $amp_locale->GetStr( 'required_label' ) ) ), $row, 0 );
            $hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $ball ) ), $row, 1 );
            $hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => $amp_locale->GetStr( 'fileupload_test_label' ) ) ), $row, 2 );
            $hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => $check_result ) ), $row, 3 );

            // XML support
            //
            $row++;

            if ( function_exists( 'xml_set_object' ) )
            {
                $ball = $hui_page->mThemeHandler->mStyle['greenball'];
                $check_result = $amp_locale->GetStr( 'xml_available_label' );
            }
            else
            {
                $ball = $hui_page->mThemeHandler->mStyle['redball'];
                $check_result = $amp_locale->GetStr( 'xml_not_available_label' );
                $systemok = false;
            }

            $hui_info_table->AddChild( new HuiLabel( 'required'.$row, array( 'label' => $amp_locale->GetStr( 'required_label' ) ) ), $row, 0 );
            $hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $ball ) ), $row, 1 );
            $hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => $amp_locale->GetStr( 'xml_test_label' ) ) ), $row, 2 );
            $hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => $check_result ) ), $row, 3 );

            // Zlib support
            //
            $row++;

            if ( function_exists( 'gzinflate' ) )
            {
                $ball = $hui_page->mThemeHandler->mStyle['greenball'];
                $check_result = $amp_locale->GetStr( 'zlib_available_label' );
            }
            else
            {
                $ball = $hui_page->mThemeHandler->mStyle['redball'];
                $check_result = $amp_locale->GetStr( 'zlib_not_available_label' );
                $systemok = false;
            }

            $hui_info_table->AddChild( new HuiLabel( 'required'.$row, array( 'label' => $amp_locale->GetStr( 'required_label' ) ) ), $row, 0 );
            $hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $ball ) ), $row, 1 );
            $hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => $amp_locale->GetStr( 'zlib_test_label' ) ) ), $row, 2 );
            $hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => $check_result ) ), $row, 3 );

            // Database support
            //
            $row++;

            if ( function_exists( 'mysql_connect' ) or function_exists( 'pg_connect' ) )
            {
                $ball = $hui_page->mThemeHandler->mStyle['greenball'];
                $check_result = $amp_locale->GetStr( 'db_available_label' );
            }
            else
            {
                $ball = $hui_page->mThemeHandler->mStyle['redball'];
                $check_result = $amp_locale->GetStr( 'db_not_available_label' );
                $systemok = false;
            }

            $hui_info_table->AddChild( new HuiLabel( 'required'.$row, array( 'label' => $amp_locale->GetStr( 'required_label' ) ) ), $row, 0 );
            $hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $ball ) ), $row, 1 );
            $hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => $amp_locale->GetStr( 'db_test_label' ) ) ), $row, 2 );
            $hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => $check_result ) ), $row, 3 );

            // XML-RPC PHP extension
            //
            $row++;

            if ( !extension_loaded( 'xmlrpc' ) )
            {
                $ball = $hui_page->mThemeHandler->mStyle['greenball'];
                $check_result = $amp_locale->GetStr( 'xmlrpc_extension_notloaded_label' );
            }
            else
            {
                $ball = $hui_page->mThemeHandler->mStyle['redball'];
                $check_result = $amp_locale->GetStr( 'xmlrpc_extension_loaded_label' );
                $systemok = false;
            }

            $hui_info_table->AddChild( new HuiLabel( 'required'.$row, array( 'label' => $amp_locale->GetStr( 'required_label' ) ) ), $row, 0 );
            $hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $ball ) ), $row, 1 );
            $hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => $amp_locale->GetStr( 'xmlrpc_extension_test_label' ) ) ), $row, 2 );
            $hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => $check_result ) ), $row, 3 );

            // Optional features

            // Crontab
            //
            $row++;

            if ( strlen( ROOTCRONTAB ) )
            {
                $ball = $hui_page->mThemeHandler->mStyle['greenball'];
                $check_result = $amp_locale->GetStr( 'crontab_available_label' );
            }
            else
            {
                $ball = $hui_page->mThemeHandler->mStyle['goldball'];
                $check_result = $amp_locale->GetStr( 'crontab_not_available_label' );
            }

            $hui_info_table->AddChild( new HuiLabel( 'required'.$row, array( 'label' => $amp_locale->GetStr( 'optional_label' ) ) ), $row, 0 );
            $hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $ball ) ), $row, 1 );
            $hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => $amp_locale->GetStr( 'crontab_test_label' ) ) ), $row, 2 );
            $hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => $check_result ) ), $row, 3 );

            // XMLRPC auth
            //
            $row++;

            if ( php_sapi_name() != 'cgi' )
            {
                $ball = $hui_page->mThemeHandler->mStyle['greenball'];
                $check_result = $amp_locale->GetStr( 'xmlrpc_available_label' );
            }
            else
            {
                $ball = $hui_page->mThemeHandler->mStyle['goldball'];
                $check_result = $amp_locale->GetStr( 'xmlrpc_not_available_label' );
            }

            $hui_info_table->AddChild( new HuiLabel( 'required'.$row, array( 'label' => $amp_locale->GetStr( 'optional_label' ) ) ), $row, 0 );
            $hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $ball ) ), $row, 1 );
            $hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => $amp_locale->GetStr( 'xmlrpc_test_label' ) ) ), $row, 2 );
            $hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => $check_result ) ), $row, 3 );

            // XMLRPC SSL
            //
            $row++;

            if ( function_exists( 'curl_init' ) )
            {
                $ball = $hui_page->mThemeHandler->mStyle['greenball'];
                $check_result = $amp_locale->GetStr( 'xmlrpc_ssl_available_label' );
            }
            else
            {
                $ball = $hui_page->mThemeHandler->mStyle['goldball'];
                $check_result = $amp_locale->GetStr( 'xmlrpc_ssl_not_available_label' );
            }

            $hui_info_table->AddChild( new HuiLabel( 'required'.$row, array( 'label' => $amp_locale->GetStr( 'optional_label' ) ) ), $row, 0 );
            $hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $ball ) ), $row, 1 );
            $hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => $amp_locale->GetStr( 'xmlrpc_ssl_test_label' ) ) ), $row, 2 );
            $hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => $check_result ) ), $row, 3 );

/*
            // Windows COM auth
            //
            $row++;

            if ( function_exists( 'com_load' ) )
            {
                $ball = $hui_page->mThemeHandler->mStyle['greenball'];
                $check_result = $amp_locale->GetStr( 'com_available_label' );
            }
            else
            {
                $ball = $hui_page->mThemeHandler->mStyle['goldball'];
                $check_result = $amp_locale->GetStr( 'com_not_available_label' );
            }

            $hui_info_table->AddChild( new HuiLabel( 'required'.$row, array( 'label' => $amp_locale->GetStr( 'optional_label' ) ) ), $row, 0 );
            $hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $ball ) ), $row, 1 );
            $hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => $amp_locale->GetStr( 'com_test_label' ) ) ), $row, 2 );
            $hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => $check_result ) ), $row, 3 );
            */

            // Informations

            // Operating system
            //
            $row++;

            $hui_info_table->AddChild( new HuiLabel( 'info'.$row, array( 'label' => $amp_locale->GetStr( 'info_label' ) ) ), $row, 0 );
            $hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $hui_page->mThemeHandler->mStyle['greenball'] ) ), $row, 1 );
            $hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => $amp_locale->GetStr( 'os_test_label' ) ) ), $row, 2 );
            $hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => PHP_OS ) ), $row, 3 );

            // Web server interface
            //
            $row++;

            $hui_info_table->AddChild( new HuiLabel( 'info'.$row, array( 'label' => $amp_locale->GetStr( 'info_label' ) ) ), $row, 0 );
            $hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $hui_page->mThemeHandler->mStyle['greenball'] ) ), $row, 1 );
            $hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => $amp_locale->GetStr( 'webserver_test_label' ) ) ), $row, 2 );
            $hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => php_sapi_name() ) ), $row, 3 );

            // Current user
            //
            $row++;

            $hui_info_table->AddChild( new HuiLabel( 'info'.$row, array( 'label' => $amp_locale->GetStr( 'info_label' ) ) ), $row, 0 );
            $hui_info_table->AddChild( new HuiImage( 'status'.$row, array( 'imageurl' => $hui_page->mThemeHandler->mStyle['greenball'] ) ), $row, 1 );
            $hui_info_table->AddChild( new HuiLabel( 'ampcgi'.$row, array( 'label' => $amp_locale->GetStr( 'user_test_label' ) ) ), $row, 2 );
            $hui_info_table->AddChild( new HuiLabel( 'checkresult'.$row, array( 'label' => get_current_user() ) ), $row, 3 );

            $hui_vgroup = new HuiVertGroup( 'nextvgroup', array( 'halign' => 'left', 'groupalign' => 'left' ) );
            $hui_vgroup->AddChild( $hui_info_table );

            if ( $systemok )
            {
                $next_action = new HuiEventSCall();
                $next_action->AddEvent( new HuiEvent( 'pass', 'checksystem', '' ) );
                $next_button = new HuiButton( 'nextbutton', array( 'label' => $amp_locale->GetStr( 'next_button' ), 'horiz' => 'true', 'image' => CGI_URL.'hui-forward.gif', 'action' => $next_action->GetEventsCallString() ) );
            }
            else
            {
                $next_action = new HuiEventSCall();
                //$next_action->AddEvent( new HuiEvent( 'pass', 'checksystem', '' ) );
                $next_button = new HuiButton( 'nextbutton', array( 'label' => $amp_locale->GetStr( 'retry_button' ), 'horiz' => 'true', 'image' => CGI_URL.'hui-reload.gif', 'action' => $next_action->GetEventsCallString() ) );
            }

            $hui_vgroup->AddChild( new HuiHorizBar( 'horizbar' ) );
            $hui_vgroup->AddChild( $next_button );

            setup_check_log( $hui_vgroup );

            $hui_mainframe->AddChild( $hui_vgroup );
        }
        // Files installation
        //
        else if ( !file_exists( TMP_PATH.'.filesinstalled' ) )
        {
            @touch( TMP_PATH.'.installingfiles', time() );
            $next_action = new HuiEventSCall();
            $next_action->AddEvent( new HuiEvent( 'pass', 'installfiles', '' ) );
            header( 'Location: '.$next_action->GetEventsCallString() );
        }
        // Ampoliros edition
        //
        else if ( !file_exists( TMP_PATH.'.editionset' ) )
        {
            @touch( TMP_PATH.'.settingedition', time() );

            $hui_vgroup = new HuiVertGroup( 'vgroup' );

            $editions['asp'] = $amp_locale->GetStr( 'asp_edition_label' );
            $editions['enterprise'] = $amp_locale->GetStr( 'enterprise_edition_label' );

            $hui_edition_grid = new HuiGrid( 'localegrid' );

            $hui_edition_grid->AddChild( new HuiLabel( 'editionlabel', array( 'label' => $amp_locale->GetStr( 'edition_label' ) ) ), 0, 0 );
            $hui_edition_grid->AddChild( new HuiComboBox( 'edition', array( 'disp' => 'pass', 'elements' => $editions ) ), 0, 1 );

            $hui_vgroup->AddChild( $hui_edition_grid );
            $hui_vgroup->AddChild( new HuiHorizBar( 'horizbar1' ) );
            $hui_vgroup->AddChild( new HuiLabel( 'editionlabel', array( 'label' => $amp_locale->GetStr( 'edition_explain_label' ), 'nowrap' => 'false' ) ) );

            $form_events_call = new HuiEventsCall();
            $form_events_call->AddEvent( new HuiEvent( 'pass', 'setedition', '' ) );
            $form_events_call->AddEvent( new HuiEvent( 'main', 'edition', '' ) );

            $hui_form = new HuiForm( 'edition', array( 'action' => $form_events_call->GetEventsCallString() ) );
            $hui_form->AddChild( $hui_vgroup );

            $next_action = new HuiEventSCall();
            $next_action->AddEvent( new HuiEvent( 'pass', 'setedition', '' ) );
            $next_action->AddEvent( new HuiEvent( 'main', 'edition', '' ) );
            $next_button = new HuiButton( 'nextbutton', array( 'label' => $amp_locale->GetStr( 'next_button' ), 'horiz' => 'true', 'formsubmit' => 'edition', 'image' => CGI_URL.'hui-forward.gif', 'action' => $next_action->GetEventsCallString() ) );

            $hui_vgroup2 = new HuiVertGroup( 'vgroup2' );

            $hui_vgroup2->AddChild( $hui_form );
            $hui_vgroup2->AddChild( new HuiHorizBar( 'hr' ) );
            $hui_vgroup2->AddChild( $next_button );

            setup_check_log( $hui_vgroup2 );

            $hui_mainframe->AddChild( $hui_vgroup2 );

            $hui_titlebar->mTitle .= ' - '.$amp_locale->GetStr( 'edition_title' );
        }
        // Database creation
        //
        else if ( !file_exists( TMP_PATH.'.dblayerscreated' ) )
        {
            @touch( TMP_PATH.'.creatingdblayers', time() );

            $hui_titlebar->mTitle .= ' - '.$amp_locale->GetStr( 'dblayerscreation_title' );

            $hui_vgroup = new HuiVertGroup( 'nextvgroup', array( 'groupalign' => 'center' ) );
            $hui_hgroup1 = new HuiHorizGroup( 'nexthgroup', array( 'align' => 'middle', 'groupalign' => 'left' ) );
            $hui_hgroup1->AddChild( new HuiLabel( 'nextlabel', array( 'label' => $amp_locale->GetStr( 'dblayersphase_label' ) ) ) );
            $hui_vgroup->AddChild( $hui_hgroup1 );

            $next_action = new HuiEventSCall();
            $next_action->AddEvent( new HuiEvent( 'pass', 'createdblayers', '' ) );
            $next_button = new HuiButton( 'nextbutton', array( 'label' => $amp_locale->GetStr( 'next_button' ), 'horiz' => 'true', 'image' => CGI_URL.'hui-forward.gif', 'action' => $next_action->GetEventsCallString() ) );

            $hui_vgroup->AddChild( new HuiHorizBar( 'hb' ) );
            $hui_vgroup->AddChild( $next_button );

            setup_check_log( $hui_vgroup );

            $hui_mainframe->AddChild( $hui_vgroup );
        }
        else if ( !file_exists( TMP_PATH.'.dbcreated' ) )
        {
            @touch( TMP_PATH.'.creatingdb', time() );
            // !!! $page->add( new HTMLScript( 'JavaScript1.2', 'parent.frames.sum.location.reload()', '' ) );

            $hui_titlebar->mTitle .= ' - '.$amp_locale->GetStr( 'dbcreation_title' );

            $hui_vgroup = new HuiVertGroup( 'vgroup' );
            $hui_vgroup->AddChild( new HuiLabel( 'phaselabel', array( 'label' => $amp_locale->GetStr( 'dbcreationphase_label' ) ) ) );

            $hui_site_grid = new HuiGrid( 'dbgrid', array( 'rows' => '6', 'cols' => '2' ) );

            $hui_site_grid->AddChild( new HuiLabel( 'dbtype_label', array( 'label' => $amp_locale->GetStr( 'dbtype_label' ).' (*)' ) ), 0, 0 );
            $hui_site_grid->AddChild( new HuiComboBox( 'dbtype', array( 'disp' => 'pass', 'elements' => $dbtypes ) ), 0, 1 );

            $hui_site_grid->AddChild( new HuiLabel( 'dbname_label', array( 'label' => $amp_locale->GetStr( 'dbname_label' ).' (*)' ) ), 1, 0 );
            $hui_site_grid->AddChild( new HuiString( 'dbname', array( 'disp' => 'pass', 'value' => 'amproot' ) ), 1, 1 );

            $hui_site_grid->AddChild( new HuiLabel( 'dbhost_label', array( 'label' => $amp_locale->GetStr( 'dbhost_label' ).' (*)' ) ), 2, 0 );
            $hui_site_grid->AddChild( new HuiString( 'dbhost', array( 'disp' => 'pass', 'value' => 'localhost' ) ), 2, 1 );

            $hui_site_grid->AddChild( new HuiLabel( 'dbport_label', array( 'label' => $amp_locale->GetStr( 'dbport_label' ) ) ), 3, 0 );
            $hui_site_grid->AddChild( new HuiString( 'dbport', array( 'disp' => 'pass' ) ), 3, 1 );

            $hui_site_grid->AddChild( new HuiLabel( 'dbuser_label', array( 'label' => $amp_locale->GetStr( 'dbuser_label' ).' (*)' ) ), 4, 0 );
            import('carthag.core.Registry');
            $reg = Registry::instance();
            $hui_site_grid->AddChild( new HuiString( 'dbuser', array( 'disp' => 'pass', 'value' => $reg->getEntry('amp.config')->getKey('HTTPD_USER') ) ), 4, 1 );

            $hui_site_grid->AddChild( new HuiLabel( 'dbpassword_label', array( 'label' => $amp_locale->GetStr( 'dbpassword_label' ).' (*)' ) ), 5, 0 );
            $hui_site_grid->AddChild( new HuiString( 'dbpass', array( 'disp' => 'pass' ) ), 5, 1 );

            $hui_vgroup->AddChild( $hui_site_grid );

            $hui_vgroup->AddChild( new HuiHorizBar( 'horizbar1' ) );
            $hui_vgroup->AddChild( new HuiLabel( 'reqfieldslabel', array( 'label' => $amp_locale->GetStr( 'requiredfields_label' ) ) ) );

            $form_events_call = new HuiEventsCall();
            $form_events_call->AddEvent( new HuiEvent( 'pass', 'createdb', '' ) );

            $hui_form = new HuiForm( 'createdb', array( 'action' => $form_events_call->GetEventsCallString() ) );
            $hui_form->AddChild( $hui_vgroup );

            $next_action = new HuiEventSCall();
            $next_action->AddEvent( new HuiEvent( 'pass', 'createdb', '' ) );
            $next_button = new HuiButton( 'nextbutton', array( 'label' => $amp_locale->GetStr( 'next_button' ), 'horiz' => 'true', 'formsubmit' => 'createdb', 'image' => CGI_URL.'hui-forward.gif', 'action' => $next_action->GetEventsCallString() ) );

            $hui_vgroup2 = new HuiVertGroup( 'vgroup2' );

            $hui_vgroup2->AddChild( $hui_form );
            $hui_vgroup2->AddChild( new HuiHorizBar( 'hr' ) );
            $hui_vgroup2->AddChild( $next_button );

            setup_check_log( $hui_vgroup2 );

            $hui_mainframe->AddChild( $hui_vgroup2 );
        }
        // Components initialization
        //
        else if ( !file_exists( TMP_PATH.'.componentsinitialized' ) )
        {
            @touch( TMP_PATH.'.initializingcomponents', time() );

            $hui_titlebar->mTitle .= ' - '.$amp_locale->GetStr( 'components_title' );

            $hui_vgroup = new HuiVertGroup( 'nextvgroup', array( 'halign' => 'left', 'groupalign' => 'left' ) );
            $hui_hgroup1 = new HuiHorizGroup( 'nexthgroup', array( 'align' => 'middle', 'groupalign' => 'center' ) );
            $hui_hgroup1->AddChild( new HuiLabel( 'nextlabel', array( 'label' => $amp_locale->GetStr( 'componentsphase_label' ) ) ) );
            $hui_vgroup->AddChild( $hui_hgroup1 );

            $next_action = new HuiEventSCall();
            $next_action->AddEvent( new HuiEvent( 'pass', 'initializecomponents', '' ) );
            $next_button = new HuiButton( 'nextbutton', array( 'label' => $amp_locale->GetStr( 'next_button' ), 'horiz' => 'true', 'image' => CGI_URL.'hui-forward.gif', 'action' => $next_action->GetEventsCallString() ) );

            $hui_vgroup->AddChild( new HuiHorizBar( 'hr' ) );
            $hui_vgroup->AddChild( $next_button );
            setup_check_log( $hui_vgroup );
            $hui_mainframe->AddChild( $hui_vgroup );
        }
        // Ampoliros host name and domain
        //
        else if ( !file_exists( TMP_PATH.'.amphostset' ) )
        {
            @touch( TMP_PATH.'.settingamphost', time() );
            // !!! $page->add( new HTMLScript( 'JavaScript1.2', 'parent.frames.sum.location.reload()', '' ) );

            $hui_titlebar->mTitle .= ' - '.$amp_locale->GetStr( 'amphost_title' );

            $hui_vgroup = new HuiVertGroup( 'vgroup' );
            $hui_vgroup->AddChild( new HuiLabel( 'phaselabel', array( 'label' => $amp_locale->GetStr( 'amphostphase_label' ) ) ) );

            $hui_site_grid = new HuiGrid( 'hostgrid' );

            $hui_site_grid->AddChild( new HuiLabel( 'amphostlabel', array( 'label' => $amp_locale->GetStr( 'amphost_label' ) ) ), 0, 0 );
            $hui_site_grid->AddChild( new HuiString( 'amphost', array( 'disp' => 'pass' ) ), 0, 1 );

            $hui_site_grid->AddChild( new HuiLabel( 'ampdomainlabel', array( 'label' => $amp_locale->GetStr( 'ampdomain_label' ) ) ), 1, 0 );
            $hui_site_grid->AddChild( new HuiString( 'ampdomain', array( 'disp' => 'pass' ) ), 1, 1 );

            $hui_site_grid->AddChild( new HuiLabel( 'ampdnslabel', array( 'label' => $amp_locale->GetStr( 'ampdns_label' ) ) ), 2, 0 );
            $hui_site_grid->AddChild( new HuiString( 'ampdns', array( 'disp' => 'pass' ) ), 2, 1 );

            $hui_vgroup->AddChild( $hui_site_grid );

            $form_events_call = new HuiEventsCall();
            $form_events_call->AddEvent( new HuiEvent( 'pass', 'setamphost', '' ) );

            $hui_form = new HuiForm( 'setamphost', array( 'action' => $form_events_call->GetEventsCallString() ) );
            $hui_form->AddChild( $hui_vgroup );

            $next_action = new HuiEventSCall();
            $next_action->AddEvent( new HuiEvent( 'pass', 'setamphost', '' ) );
            $next_button = new HuiButton( 'nextbutton', array( 'label' => $amp_locale->GetStr( 'next_button' ), 'horiz' => 'true', 'formsubmit' => 'setamphost', 'image' => CGI_URL.'hui-forward.gif', 'action' => $next_action->GetEventsCallString() ) );

            $hui_vgroup2 = new HuiVertGroup( 'vgroup2' );

            $hui_vgroup2->AddChild( $hui_form );
            $hui_vgroup2->AddChild( new HuiHorizBar( 'hr' ) );
            $hui_vgroup2->AddChild( $next_button );

            setup_check_log( $hui_vgroup2 );

            $hui_mainframe->AddChild( $hui_vgroup2 );
        }
        // Root administration country
        //
        else if ( !file_exists( TMP_PATH.'.countryset' ) )
        {
            @touch( TMP_PATH.'.settingcountry', time() );
            // !!! $page->add( new HTMLScript( 'JavaScript1.2', 'parent.frames.sum.location.reload()', '' ) );

            $args['dbname'] = AMP_DBNAME;
            $args['dbhost'] = AMP_DBHOST;
            $args['dbport'] = AMP_DBPORT;
            $args['dbuser'] = AMP_DBUSER;
            $args['dbpass'] = AMP_DBPASS;
            $args['dbtype'] = AMP_DBTYPE;
            $args['dblog']  = AMP_DBLOG;

			$layer = new DBLayerFactory();
            $tmpdb = $layer->NewDBLayer( $args );
            if ( $tmpdb->Connect( $args ) )
            {
                $tmploc = new Locale( 'amp_misc_locale', AMP_LANG );

                $country_query = &$tmpdb->Execute( 'SELECT * '.
                                                  'FROM countries' );

                $country_locale = new Locale( 'amp_misc_locale', AMP_LANG );

                $hui_vgroup = new HuiVertGroup( 'vgroup' );

                while ( !$country_query->eof )
                {
                    $countries[$country_query->Fields( 'countryname' )] = $country_locale->GetStr( $country_query->Fields( 'countryname' ) );
                    $country_query->MoveNext();
                }

                $hui_locale_grid = new HuiGrid( 'localegrid' );

                $hui_locale_grid->AddChild( new HuiLabel( 'countrylabel', array( 'label' => $amp_locale->GetStr( 'country_label' ) ) ), 0, 0 );
                $hui_locale_grid->AddChild( new HuiComboBox( 'country', array( 'disp' => 'pass', 'elements' => $countries ) ), 0, 1 );

                $hui_vgroup->AddChild( $hui_locale_grid );

                $form_events_call = new HuiEventsCall();
                $form_events_call->AddEvent( new HuiEvent( 'pass', 'setcountry', '' ) );

                $hui_form = new HuiForm( 'country', array( 'action' => $form_events_call->GetEventsCallString() ) );
                $hui_form->AddChild( $hui_vgroup );

                $next_action = new HuiEventSCall();
                $next_action->AddEvent( new HuiEvent( 'pass', 'setcountry', '' ) );
                $next_button = new HuiButton( 'nextbutton', array( 'label' => $amp_locale->GetStr( 'next_button' ), 'horiz' => 'true', 'formsubmit' => 'country', 'image' => CGI_URL.'hui-forward.gif', 'action' => $next_action->GetEventsCallString() ) );

                $hui_vgroup2 = new HuiVertGroup( 'vgroup2' );

                $hui_vgroup2->AddChild( $hui_form );
                $hui_vgroup2->AddChild( new HuiHorizBar( 'hr' ) );
                $hui_vgroup2->AddChild( $next_button );

                setup_check_log( $hui_vgroup2 );

                $hui_mainframe->AddChild( $hui_vgroup2 );

                $hui_titlebar->mTitle .= ' - '.$amp_locale->GetStr( 'ampcountry_title' );
            }
            else $log->logevent( 'ampoliros.root.main_php',
                                'Unable to connect to root database during initialization', LOGGER_ERROR );
        }
        // Root administration language
        //
        else if ( !file_exists( TMP_PATH.'.languageset' ) )
        {
            @touch( TMP_PATH.'.settinglanguage', time() );
            // !!! $page->add( new HTMLScript( 'JavaScript1.2', 'parent.frames.sum.location.reload()', '' ) );

            $pass_data = $pass_disp->GetEventData();
            $country = $pass_data['country'];

            if ( !strlen( $country ) )
            {
                $country = AMP_COUNTRY;
            }

            $args['dbname'] = AMP_DBNAME;
            $args['dbhost'] = AMP_DBHOST;
            $args['dbport'] = AMP_DBPORT;
            $args['dbuser'] = AMP_DBUSER;
            $args['dbpass'] = AMP_DBPASS;
            $args['dbtype'] = AMP_DBTYPE;
            $args['dblog']  = AMP_DBLOG;

			$layer = new DBLayerFactory();
            $tmpdb = $layer->NewDBLayer( $args );
            if ( $tmpdb->Connect( $args ) )
            {
                $loc_country = new LocaleCountry( $country );
                $country_language = $loc_country->Language();

                $language_locale = new Locale( 'amp_misc_locale', AMP_LANG );

                $selected_language = $pass_disp->GetEventData();
                $selected_language = $selected_language['language'];

                $hui_vgroup = new HuiVertGroup( 'vgroup' );

                $language_query = &$tmpdb->Execute( 'SELECT * '.
                                                   'FROM languages' );

                while ( !$language_query->eof )
                {
                    $languages[$language_query->Fields( 'langshort' )] = $language_locale->GetStr( $language_query->Fields( 'langname' ) );
                    $language_query->MoveNext();
                }

                $hui_locale_grid = new HuiGrid( 'localegrid' );

                $hui_locale_grid->AddChild( new HuiLabel( 'languagelabel', array( 'label' => $amp_locale->GetStr( 'language_label' ) ) ), 0, 0 );
                $hui_locale_grid->AddChild( new HuiComboBox( 'language', array( 'disp' => 'pass', 'elements' => $languages, 'default' => ( $selected_language ? $selected_language : $country_language ) ) ), 0, 1 );

                $hui_vgroup->AddChild( $hui_locale_grid );
                $hui_vgroup->AddChild( new HuiHorizBar( 'horizbar1' ) );
                $hui_vgroup->AddChild( new HuiLabel( 'deflanglabel', array( 'label' => sprintf( $amp_locale->GetStr( 'countrylanguage_label' ), $languages[$country_language] ) ) ) );

                $form_events_call = new HuiEventsCall();
                $form_events_call->AddEvent( new HuiEvent( 'pass', 'setlanguage', '' ) );
                $form_events_call->AddEvent( new HuiEvent( 'main', 'language', '' ) );

                $hui_form = new HuiForm( 'language', array( 'action' => $form_events_call->GetEventsCallString() ) );
                $hui_form->AddChild( $hui_vgroup );

                $next_action = new HuiEventSCall();
                $next_action->AddEvent( new HuiEvent( 'pass', 'setlanguage', '' ) );
                $next_action->AddEvent( new HuiEvent( 'main', 'language', '' ) );
                $next_button = new HuiButton( 'nextbutton', array( 'label' => $amp_locale->GetStr( 'next_button' ), 'horiz' => 'true', 'formsubmit' => 'language', 'image' => CGI_URL.'hui-forward.gif', 'action' => $next_action->GetEventsCallString() ) );

                $hui_vgroup2 = new HuiVertGroup( 'vgroup2' );

                $hui_vgroup2->AddChild( $hui_form );
                $hui_vgroup2->AddChild( new HuiHorizBar( 'hr' ) );
                $hui_vgroup2->AddChild( $next_button );

                setup_check_log( $hui_vgroup2 );

                $hui_mainframe->AddChild( $hui_vgroup2 );

                $hui_titlebar->mTitle .= ' - '.$amp_locale->GetStr( 'amplanguage_title' );
            }
            else $log->logevent( 'ampoliros.root.main_php',
                                'Unable to connect to root database during initialization', LOGGER_ERROR );
        }
        // Password choice
        //
        else if ( !file_exists( TMP_PATH.'.passwordset' ) )
        {
            @touch( TMP_PATH.'.settingpassword', time() );
            // !!! $page->add( new HTMLScript( 'JavaScript1.2', 'parent.frames.sum.location.reload()', '' ) );

            $hui_grid = new HuiGrid( 'grid' );

            $hui_grid->AddChild( new HuiLabel( 'passwordalabel', array( 'label' => $amp_locale->GetStr( 'amppassworda_label' ) ) ), 0, 0 );
            $hui_grid->AddChild( new HuiString( 'passworda', array( 'disp' => 'pass', 'password' => 'true' ) ), 0, 1 );

            $hui_grid->AddChild( new HuiLabel( 'passwordblabel', array( 'label' => $amp_locale->GetStr( 'amppasswordb_label' ) ) ), 1, 0 );
            $hui_grid->AddChild( new HuiString( 'passwordb', array( 'disp' => 'pass', 'password' => 'true' ) ), 1, 1 );

            $hui_vgroup = new HuiVertGroup( 'vertgroup', array( 'align' => 'center' ) );
            $hui_vgroup->AddChild( new HuiLabel( 'phaselabel', array( 'label' => $amp_locale->GetStr( 'passwordphase_label' ) ) ) );
            $hui_vgroup->AddChild( $hui_grid );

            $form_events_call = new HuiEventsCall();
            $form_events_call->AddEvent( new HuiEvent( 'pass', 'setpassword', '' ) );

            $hui_form = new HuiForm( 'password', array( 'action' => $form_events_call->GetEventsCallString() ) );
            $hui_form->AddChild( $hui_vgroup );

            $next_action = new HuiEventSCall();
            $next_action->AddEvent( new HuiEvent( 'pass', 'setpassword', '' ) );
            $next_button = new HuiButton( 'nextbutton', array( 'label' => $amp_locale->GetStr( 'next_button' ), 'horiz' => 'true', 'formsubmit' => 'password', 'image' => CGI_URL.'hui-forward.gif', 'action' => $next_action->GetEventsCallString() ) );

            $hui_vgroup2 = new HuiVertGroup( 'vgroup2' );

            $hui_vgroup2->AddChild( $hui_form );
            $hui_vgroup2->AddChild( new HuiHorizBar( 'hr' ) );
            $hui_vgroup2->AddChild( $next_button );

            setup_check_log( $hui_vgroup2 );

            $hui_mainframe->AddChild( $hui_vgroup2 );

            $hui_titlebar->mTitle .= ' - '.$amp_locale->GetStr( 'password_title' );
        }
        // AmpCentral
        //
        else if ( !file_exists( TMP_PATH.'.ampcentralset' ) )
        {
            @touch( TMP_PATH.'.settingampcentral', time() );
        
            $hui_vgroup = new HuiVertGroup( 'vgroup' );

            $hui_ampcentral_grid = new HuiGrid( 'grid' );

            $hui_ampcentral_grid->AddChild( new HuiCheckBox( 'ampcentral', array( 'disp' => 'pass', 'checked' => 'true' ) ), 0, 0 );
            $hui_ampcentral_grid->AddChild( new HuiLabel( 'ampcentrallabel', array( 'label' => $amp_locale->GetStr( 'ampcentral_label' ) ) ), 0, 1 );

            $hui_vgroup->AddChild( $hui_ampcentral_grid );
            $hui_vgroup->AddChild( new HuiHorizBar( 'horizbar1' ) );
            $hui_vgroup->AddChild( new HuiLabel( 'ampcentrallabel', array( 'label' => $amp_locale->GetStr( 'ampcentral_explain_label' ), 'nowrap' => 'false' ) ) );

            $form_events_call = new HuiEventsCall();
            $form_events_call->AddEvent( new HuiEvent( 'pass', 'setampcentral', '' ) );

            $hui_form = new HuiForm( 'ampcentral', array( 'action' => $form_events_call->GetEventsCallString() ) );
            $hui_form->AddChild( $hui_vgroup );

            $next_action = new HuiEventSCall();
            $next_action->AddEvent( new HuiEvent( 'pass', 'setampcentral', '' ) );
            $next_button = new HuiButton( 'nextbutton', array( 'label' => $amp_locale->GetStr( 'next_button' ), 'horiz' => 'true', 'formsubmit' => 'ampcentral', 'image' => CGI_URL.'hui-forward.gif', 'action' => $next_action->GetEventsCallString() ) );

            $hui_vgroup2 = new HuiVertGroup( 'vgroup2' );

            $hui_vgroup2->AddChild( $hui_form );
            $hui_vgroup2->AddChild( new HuiHorizBar( 'hr' ) );
            $hui_vgroup2->AddChild( $next_button );

            setup_check_log( $hui_vgroup2 );

            $hui_mainframe->AddChild( $hui_vgroup2 );
            
            $hui_titlebar->mTitle .= ' - '.$amp_locale->GetStr( 'ampcentral_title' );
        }        
        // Final cleanup
        //
        else if ( !file_exists( TMP_PATH.'.cleanedup' ) )
        {
            @touch( TMP_PATH.'.cleaningup', time() );

            $hui_titlebar->mTitle .= ' - '.$amp_locale->GetStr( 'cleanup_title' );

            $hui_vgroup = new HuiVertGroup( 'nextvgroup', array( 'halign' => 'left', 'groupalign' => 'left' ) );
            $hui_hgroup1 = new HuiHorizGroup( 'nexthgroup', array( 'align' => 'middle', 'groupalign' => 'center' ) );
            $hui_hgroup1->AddChild( new HuiLabel( 'nextlabel', array( 'label' => $amp_locale->GetStr( 'cleanup_label' ) ) ) );
            $hui_vgroup->AddChild( $hui_hgroup1 );

            $next_action = new HuiEventSCall();
            $next_action->AddEvent( new HuiEvent( 'pass', 'cleanup', '' ) );
            $next_button = new HuiButton( 'nextbutton', array( 'label' => $amp_locale->GetStr( 'next_button' ), 'horiz' => 'true', 'image' => CGI_URL.'hui-forward.gif', 'action' => $next_action->GetEventsCallString() ) );

            $hui_vgroup->AddChild( new HuiHorizBar( 'hr' ) );
            $hui_vgroup->AddChild( $next_button );
            setup_check_log( $hui_vgroup );
            $hui_mainframe->AddChild( $hui_vgroup );
        }
        else if ( !file_exists( TMP_PATH.'.done' ) )
        {
            $hui_titlebar->mTitle .= ' - '.$amp_locale->GetStr( 'finish_title' );

            $hui_vgroup = new HuiVertGroup( 'nextvgroup', array( 'halign' => 'left', 'groupalign' => 'left' ) );
            $hui_hgroup1 = new HuiHorizGroup( 'nexthgroup', array( 'align' => 'middle', 'groupalign' => 'center' ) );
            $hui_hgroup1->AddChild( new HuiLabel( 'nextlabel', array( 'label' => $amp_locale->GetStr( 'finish_label' ) ) ) );
            $hui_vgroup->AddChild( $hui_hgroup1 );

            $next_action = new HuiEventSCall();
            $next_action->AddEvent( new HuiEvent( 'pass', 'finish', '' ) );
            $next_button = new HuiButton( 'nextbutton', array( 'label' => $amp_locale->GetStr( 'next_button' ), 'horiz' => 'true', 'image' => CGI_URL.'hui-forward.gif', 'action' => $next_action->GetEventsCallString() ) );

            $hui_vgroup->AddChild( new HuiHorizBar( 'hr' ) );
            $hui_vgroup->AddChild( $next_button );
            setup_check_log( $hui_vgroup );
            $hui_mainframe->AddChild( $hui_vgroup );
        }

        // Page render
	//
	$hui_mainframe1->AddChild( $hui_mainframe );
	$hui_mainvertgroup->AddChild( $hui_mainframe1 );
	$hui_mainvertgroup->AddChild( $hui_mainstatus );
	$hui_page->AddChild( $hui_mainvertgroup );
	$hui->AddChild( $hui_page );
        $hui->Render();
    }
}

?>
