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
// $Id: ClipboardTest.php,v 1.2 2004-07-08 15:04:26 alex Exp $

/**
 * @package carthag.tests.datatransfer
 * @author Alex Pagnoni <alex.pagnoni@solarix.it>
 * @copyright Copyright 2000-2004 Solarix Srl
 * @since 4.0
 */
package('com.solarix.ampoliros.tests.datatransfer');

import('carthag.dev.unit.framework.TestCase');
import('carthag.dev.unit.framework.Assert');
import('com.solarix.ampoliros.datatransfer.Clipboard');

/**
 * @package carthag.tests.datatransfer
 * @author Alex Pagnoni <alex.pagnoni@solarix.it>
 * @copyright Copyright 2000-2004 Solarix Srl
 * @since 4.0
 */
class ClipboardTest extends TestCase {
    public function testIsValidA() {
        $clip = new Clipboard(Clipboard::TYPE_TEXT, '', 1, 'ampoliros', '', '');
        $text = 'ClipboardTest test string';
        $clip->store($text);
        
        Assert :: assertTrue($clip->isValid());
    }

    public function testIsValidB() {
        $clip = new Clipboard(Clipboard::TYPE_TEXT, '', 1, 'ampoliros', '', '');
        $text = 'ClipboardTest test string';
        $clip->store($text);
        $clip->erase();
        
        Assert :: assertFalse($clip->isValid());
    }

    public function testStoreText() {
        $clip = new Clipboard(Clipboard::TYPE_TEXT, '', 1, 'ampoliros', '', '');
        $text = 'ClipboardTest test string';
        $clip->store($text);
        
        $clip = new Clipboard(Clipboard::TYPE_TEXT, '', 1, 'ampoliros', '', '');
        $result = $clip->retrieve();
        Assert :: assertSame($result, $text);
    }

    public function testStoreArray() {
        $clip = new Clipboard(Clipboard::TYPE_ARRAY, '', 1, 'ampoliros', '', '');
        $item = array('a' => 'test', 'b' => 'test');
        $clip->store($item);
        
        $clip = new Clipboard(Clipboard::TYPE_ARRAY, '', 1, 'ampoliros', '', '');
        $result = $clip->retrieve();
        Assert :: assertSame($result, $item);
    }

    public function testErase() {
        $clip = new Clipboard(Clipboard::TYPE_TEXT, '', 1, 'ampoliros', '', '');
        $text = 'ClipboardTest test string';
        $clip->store($text);
        $clip->erase();
        
        Assert :: assertFalse($clip->retrieve());
    }
}

?>