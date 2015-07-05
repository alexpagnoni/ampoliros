//---------------------------------------------------------------------------
#include <vcl\vcl.h>
#include <vcl\registry.hpp>
#pragma hdrstop

#include "main.h"
#include "about.h"
#include <winsock2.h>
#include <io.h>

//---------------------------------------------------------------------------
#pragma resource "*.dfm"
Tampcfgform *ampcfgform;

//---------------------------------------------------------------------------
__fastcall Tampcfgform::Tampcfgform(TComponent* Owner)
	: TForm(Owner)
{
}
//---------------------------------------------------------------------------
void __fastcall Tampcfgform::Exit1Click(TObject *Sender)
{
	Close();
}
//---------------------------------------------------------------------------
void __fastcall Tampcfgform::About1Click(TObject *Sender)
{
	AboutBox->Visible = true;
}
//---------------------------------------------------------------------------
void __fastcall Tampcfgform::hostnameChange(TObject *Sender)
{
	ampurl->Text = "http://" + hostname->Text + "/ampoliros";
	rooturl->Text = "http://" + hostname->Text + "/ampoliros/root";
	adminurl->Text = "http://" + hostname->Text + "/ampoliros/admin";
}
//---------------------------------------------------------------------------
bool applychanges()
{
	FILE *fh;
    char filename[256];
	bool result;
	bool file_exists;
	TRegistry *myreg;

	result = false;
	file_exists = false;

	myreg = new TRegistry();
	myreg->RootKey = HKEY_LOCAL_MACHINE;
    myreg->OpenKey( "Software\\Ampoliros", true );

    myreg->WriteString( "PRIVATE_TREE", ampcfgform->privatetree->Text );
    myreg->WriteString( "PUBLIC_TREE", ampcfgform->publictree->Text );
    myreg->WriteString( "SITES_TREE", ampcfgform->sitestree->Text );

    myreg->WriteString( "AMP_HOST", ampcfgform->hostname->Text );
	myreg->WriteString( "AMP_URL", ampcfgform->ampurl->Text );
    myreg->WriteString( "ADMIN_URL", ampcfgform->adminurl->Text );
    myreg->WriteString( "ROOT_URL", ampcfgform->rooturl->Text );
	myreg->WriteString( "CGI_URL", ampcfgform->cgiurl->Text );

    strcpy( filename, ampcfgform->privatetree->Text.c_str() );
    strcat( filename, "\\etc\\ampconfig.cfg" );

	if ( access( filename, 0 ) == 0 ) file_exists = true;

	fh = fopen( filename, "w" );
	if ( fh )
	{
		fprintf( fh, "HTTPD_GROUP = nogroup\n" );
		fprintf( fh, "HTTPD_USER = nobody\n" );
		fprintf( fh, "AMP_HOST = %s\n", strtr( ampcfgform->hostname->Text.c_str(), "\\", "/" ) );
		fprintf( fh, "AMP_URL = %s\n", strtr( ampcfgform->ampurl->Text.c_str(), "\\", "/" ) );
		fprintf( fh, "AMP_ROOTURL = %s\n", strtr( ampcfgform->rooturl->Text.c_str(), "\\", "/" ) );
		fprintf( fh, "ADMIN_URL = %s\n", strtr( ampcfgform->adminurl->Text.c_str(), "\\", "/" ) );
		fprintf( fh, "CGI_URL = %s\n", strtr( ampcfgform->cgiurl->Text.c_str(), "\\", "/" ) );
		fprintf( fh, "PUBLIC_TREE = %s/\n", strtr( ampcfgform->publictree->Text.c_str(), "\\", "/" ) );
		fprintf( fh, "PRIVATE_TREE = %s/\n", strtr( ampcfgform->privatetree->Text.c_str(), "\\", "/" ) );
		fprintf( fh, "SITES_TREE =  %s/\n", strtr( ampcfgform->sitestree->Text.c_str(), "\\", "/" ) );

		// :TODO: teg 010724: language
		// Language should be set only the first time,
		// using file_exists variable.

		fprintf( fh, "AMP_LANG = en\n" );
		fclose( fh );

		strcpy( filename, ampcfgform->privatetree->Text.c_str() );
		strcat( filename, "\\etc\\ampdbconfig.cfg" );

	    fh = fopen( filename, "a" );
    	if ( fh )
	    {
        	fclose( fh );
	    	result = true;
    	}

		strcpy( filename, strtr( ampcfgform->publictree->Text.c_str(), "\\", "/" ) );
		strcat( filename, "\\cfgpath.php" );
		makecfgpathfile( filename );

		strcpy( filename, strtr( ampcfgform->publictree->Text.c_str(), "\\", "/" ) );
		strcat( filename, "\\root\\cfgpath.php" );
		makecfgpathfile( filename );

		strcpy( filename, strtr( ampcfgform->publictree->Text.c_str(), "\\", "/" ) );
		strcat( filename, "\\admin\\cfgpath.php" );
		makecfgpathfile( filename );

	    strcpy( filename, strtr( ampcfgform->publictree->Text.c_str(), "\\", "/" ) );
        strcat( filename, "\\cgi\\cfgpath.php" );
		makecfgpathfile( filename );
    }

	strcpy( filename, ampcfgform->privatetree->Text.c_str() );
	strcat( filename, "\\etc\\ampconfigpath.php" );

	fh = fopen( filename, "w" );
	if ( fh )
	{
		fprintf( fh, "<?php define( \"AMP_CONFIG\", \"%s/etc/ampconfig.cfg\" );?>\n", strtr( ampcfgform->privatetree->Text.c_str(), "\\", "/" ) );
        fclose( fh );
	}

    return result;
}
//---------------------------------------------------------------------------
bool makecfgpathfile( char *filename )
{
	bool result;
    FILE *fh;

    result = false;

    fh = fopen( filename, "w" );
    if ( fh )
    {
     	fprintf( fh, "<?php require( \"%s/etc/ampoliros.php\" ); ?>", ampcfgform->privatetree->Text.c_str() );
      	fclose( fh );
        result = true;
    }

    return result;
}
//---------------------------------------------------------------------------
char *strtr( char *string, char *from, char *to )
{
    int size;
	int i;
    char *buf;

    size = strlen( string );
    buf = (char *)malloc( size+1 );
    i = 0;

	while ( i < size )
	{
    	if ( string[i] == (char)from[0] ) buf[i] = (char)to[0];
        else buf[i] = string[i];
    	i++;
	}
    buf[i] = '\0';

    return buf;
}
//---------------------------------------------------------------------------
void __fastcall Tampcfgform::Button1Click(TObject *Sender)
{
	applychanges();
    Close();
}
//---------------------------------------------------------------------------


void __fastcall Tampcfgform::FormCreate(TObject *Sender)
{
	TRegistry *myreg;

    myreg = new TRegistry();
    myreg->RootKey = HKEY_LOCAL_MACHINE;
    myreg->OpenKey( "Software\\Ampoliros", true );

    if ( strlen( myreg->ReadString( "PRIVATE_TREE" ).c_str() ) )
    {
    	privatetree->Text = myreg->ReadString( "PRIVATE_TREE" );
    	publictree->Text = myreg->ReadString( "PUBLIC_TREE" );
        sitestree->Text = myreg->ReadString( "SITES_TREE" );

    	privatetree->Enabled = false;
        publictree->Enabled = false;
        sitestree->Enabled = false;

        override->Enabled = true;
    }
    else if ( strlen( myreg->ReadString( "INSTALL_DIR" ).c_str() ) )
    {
    	privatetree->Text = myreg->ReadString( "INSTALL_DIR" ) + "";
        publictree->Text = myreg->ReadString( "INSTALL_DIR" ) + "\\public";
        sitestree->Text = myreg->ReadString( "INSTALL_DIR" ) + "\\ampsites";
    }

    if ( strlen( myreg->ReadString( "AMP_HOST" ).c_str() ) )
    {
    	hostname->Text = myreg->ReadString( "AMP_HOST" );
	    ampurl->Text = myreg->ReadString( "AMP_URL" );
    	adminurl->Text = myreg->ReadString( "ADMIN_URL" );
	    rooturl->Text = myreg->ReadString( "ROOT_URL" );
    }
    else
    {
    	char FAR myhostname[100];
		WORD wVersionRequested;
		WSADATA wsaData;

		wVersionRequested = MAKEWORD( 1, 1 );

		if ( !WSAStartup( wVersionRequested, &wsaData ) )
        {
        	if ( !gethostname( myhostname, sizeof( myhostname ) ) ) hostname->Text = myhostname;

        	WSACleanup();
        }
    }

    if ( strlen( myreg->ReadString( "CGI_URL" ).c_str() ) ) cgiurl->Text = myreg->ReadString( "CGI_URL" );
    else cgiurl->Text = "/ampcgi/";
}
//---------------------------------------------------------------------------
void __fastcall Tampcfgform::Applychanges1Click(TObject *Sender)
{
	applychanges();
}
//---------------------------------------------------------------------------
void __fastcall Tampcfgform::overrideClick(TObject *Sender)
{
	if ( override->State == cbUnchecked )
    {
    	privatetree->Enabled = false;
        publictree->Enabled = false;
        sitestree->Enabled = false;
    }
    else
    {
    	privatetree->Enabled = true;
        publictree->Enabled = true;
        sitestree->Enabled = true;
    }
}
//---------------------------------------------------------------------------
