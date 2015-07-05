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
// $Id: HuiContainerElement.php,v 1.8 2004-07-08 15:04:26 alex Exp $

package('com.solarix.ampoliros.hui');

import('com.solarix.ampoliros.hui.HuiWidgetElement');
import('com.solarix.ampoliros.hui.HuiDispatcher');

/*!
 @class HuiContainerElement
 @abstract Base widget containers class.
 @discussion A container is a particular sort of widget. It can contains inside it the other widgets.
 */
abstract class HuiContainerElement extends HuiWidgetElement {
	/*! @var mChilds array - array of child widgets. */
	public $mChilds = array();

	/*!
	 @function HuiContainerElement
	 @abstract Class constructor.
	 @discussion Class constructor.
	 @param elemName string - Element unique name.
	 @param elemArgs array - Array of element arguments and attributes.
	 @param elemTheme string - Theme to be applied to the element. Currently unuseful.
	 @param dispEvents array - Dispatcher events.
	 */
	public function HuiContainerElement($elemName, $elemArgs = '', $elemTheme = '', $dispEvents = '') {
		$this -> HuiWidgetElement($elemName, $elemArgs, $elemTheme, $dispEvents);
	}

	/*!
	 @function AddChild
	 @abstract Adds a child to the container structure.
	 @discussion Adds a child to the container structure.
	 @param rchildWidget HuiWidgetClass - Child widget to be added to the structure.
	 @result Always true if childWidget is a real object.
	 */
	public function addChild(HuiWidgetElement $rchildWidget) {
		$this -> mChilds[] = $rchildWidget;
        return true;
	}

	/*!
	 @function Build
	 @abstract Builds the layout.
	 @discussion Builds the layout calling the Build method for every child in the structure.
	 @param rhuiDisp HuiDispatcher class - Hui internal dispatcher handler.
	 @result True if the structure has been built by the member.
	 */
	public function build(HuiDispatcher $rhuiDisp) {
		$result = false;
		$this -> mrHuiDisp = $rhuiDisp;
		$this -> mLayout.= $this -> _buildBegin();
		$children_count = count($this -> mChilds);

		if ($children_count) {
			for ($i = 0; $i < $children_count; $i ++) {
				if (is_object($this -> mChilds[$i])) {
					if ($this -> mChilds[$i] -> build($this -> mrHuiDisp)) {
						$this -> mLayout.= $this -> _buildBlockBegin();
						$this -> mLayout.= $this -> mChilds[$i] -> Render();
						$this -> mLayout.= $this -> _buildBlockEnd();

						$this -> mChilds[$i] -> destroy();
					}
				}
			}
			$this -> mBuilt = true;
			$result = true;
		}

		$this -> mLayout.= $this -> _BuildEnd();
		return $result;
	}

	public function destroy() {
		$this -> mLayout = '';
		$this -> mArgs = array();
	}

	/*!
	 @function _BuildBegin
	 @abstract Wrapped function for layout block before the childs layout.
	 @discussion Wrapped function for layout block before the childs layout.
	 @result Empty string if not extended.
	 */
	protected function _buildBegin() {
		return '';
	}

	/*!
	 @function _BuildEnd
	 @abstract Wrapped function for layout block after the childs layout.
	 @discussion Wrapped function for layout block after the childs layout.
	 @result Empty string if not extended.
	 */
	protected function _buildEnd() {
		return '';
	}

	/*!
	 @function _BuildBlockBegin
	 @abstract Wrapped function for layout block before every child layout.
	 @discussion Wrapped function for layout block before every child layout.
	 @result Empty string if not extended.
	 */
	protected function _buildBlockBegin() {
		return '';
	}

	/*!
	 @function _BuildBlockEnd
	 @abstract Wrapped function for layout block after every child layout.
	 @discussion Wrapped function for layout block after every child layout.
	 @result Empty string if not extended.
	 */
	protected function _buildBlockEnd() {
		return '';
	}
}

?>