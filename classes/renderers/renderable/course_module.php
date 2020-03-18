<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course module item for ludic course format.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class format_ludic_course_module extends format_ludic_item {

    public $iconsrc;
    public $iconalt;
    public $parentid;

    /**
     * format_ludic_course_module constructor.
     *
     * @param \format_ludic\course_module $coursemodule
     */
    public function __construct(\format_ludic\course_module $coursemodule) {
        $this->selectorid       = 'ludic-coursemodule-' . $coursemodule->order;
        $this->id               = $coursemodule->id;
        $this->order            = $coursemodule->order;
        $this->title            = $coursemodule->name;
        $this->parentid         = $coursemodule->sectionid;
        $this->itemtype         = 'coursemodule';
        $this->child            = true;
        $this->draggable        = true;
        $this->droppable        = true;
        $this->skinid           = $coursemodule->skinid;
        $imageobject            = $coursemodule->skin->get_edit_image();
        $this->imgsrc           = $imageobject->imgsrc;
        $this->imgalt           = $imageobject->imgalt;
        $icon                   = $coursemodule->get_mod_icon();
        $this->iconsrc          = $icon->imgsrc;
        $this->iconalt          = $icon->imgalt;
        $this->isnotvisible     = !$coursemodule->visible;
        $this->propertiesaction = 'get_properties';
    }
}