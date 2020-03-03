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
 * Select form element class.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

class select_form_element extends form_element {

    public function __construct($name, $id, $value, $defaultvalue, $label = '', $attributes = [], $specific = []) {
        $this->type = 'select';
        parent::__construct($name, $id, $value, $defaultvalue, $label, $attributes, $specific);
    }

    public function validate_value($value) {
        $options = isset($this->specific['options']) ? $this->specific['options'] : [];
        $valueinoptions = false;
        foreach ($options as $option) {
            if ($value == $option['value']) {
                $valueinoptions = true;
            }
        }
        if (!$valueinoptions) {
            return ['success' => 0];
        }
        $value = clean_param($value, PARAM_RAW);
        return ['success' => 1,  'value' => (string) ($value)];
    }


}