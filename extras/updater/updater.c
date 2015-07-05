/*
 *                    Ampoliros Application Server
 *
 *                      http://www.ampoliros.com
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

#include <stdlib.h>
#include <string.h>
#include <stdio.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <unistd.h>

#define UPDATEEXT ".update"
#define LOCKEXT   ".lock"

int checkfile( char * );
void lockcfile( char * );
void unlockcfile( char * );

// Main
//
int main ( int argc, char *argv[] )
{
    int result             = 0;   // Result code
    int updatedaemon       = 0;
    char srcfile[256]      = "";  // Source file
    char destfile[256]     = "";  // Destination file
    char lockfile[256]     = "";  // Lock status file
    char updatingfile[256] = "";  // Updating status file

    if ( argc == 5 )
    {
        argc = 4;
        updatedaemon = 1;
    }

    switch ( argc )
    {
    case 4:
        sprintf( srcfile,      "%s%s",   argv[2], argv[1] );
        sprintf( destfile,     "%s",   argv[3] );
        //sprintf( destfile,     "%s%s",   argv[3], argv[1] );
        sprintf( lockfile,     "%s%s%s", argv[2], argv[1], LOCKEXT   );
        sprintf( updatingfile, "%s%s%s", argv[2], argv[1], UPDATEEXT );

        //while ( checkfile

        if ( checkfile( updatingfile ) & checkfile( srcfile ) & checkfile( destfile ) )
        {
            char comm[512];

            while ( checkfile( lockfile ) )
            {
                sleep( 1 );
            }

            lockcfile( lockfile );

            sprintf( comm, "cp %s %s", srcfile, destfile );
            //printf( "STRINGA: %s\n", argv[4] );

            // Copy the file
            system( comm );
            // Update the daemon
            if ( updatedaemon == 1 )
            {
                printf( "UPDATING\n" );
                system( argv[4] );
            }
            // Delete the configuration file
            unlink( srcfile );
            // Delete the updating lock
            unlockcfile( updatingfile );
            // Delete the file lock
            unlockcfile( lockfile );

            result = 1;
        }
        break;

    case 2: // Only command execution
        system( argv[1] );

        result = 1;
        break;

    default:
        printf( "Usage: updater filename tempdir destdir updatecommand\n" );
        printf( "       updater filename tempdir destdir\n" );
        printf( "       updater command\n" );
        break;
    }

    return result;
}

// Checks if the given file exists
//
int checkfile( char *filename )
{
    int result = 0;
    int fh = 0;

    fh = open( filename, O_RDONLY );

    if ( fh != -1 )
    {
        //printf( "%s\n", filename );
        result = 1;
        close( fh );
    }

    return result;
}

// Creates a lock status file
//
void lockcfile( char *filename )
{
    int fh = 0;

    fh = open( filename, O_CREAT );
    if ( fh != -1 )
    {
        close( fh );
    }
}

// Removes a lock status file
//
void unlockcfile( char *filename )
{
    unlink( filename );
}
