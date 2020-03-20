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
 * This class makes it possible to recover all the data necessary for the course.
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludic;

defined('MOODLE_INTERNAL') || die();

// Define all format globals here.

// Skin inline id.
define('FORMAT_LUDIC_CM_SKIN_INLINE_ID', 1);

// Always accessible.
define('FORMAT_LUDIC_ACCESS_ACCESSIBLE', 1);

// An activity is visible but not accessible until the previous activity has been completed.
define('FORMAT_LUDIC_ACCESS_CHAINED', 2);

// An activity is not visible or accessible until the previous activity has been completed,
// at which time it appears and becomes accessible.
define('FORMAT_LUDIC_ACCESS_DISCOVERABLE', 3);

// The activity is not visible or accessible unless and until the teacher manually open up access to selected students.
define('FORMAT_LUDIC_ACCESS_CONTROLLED', 4);

// The item will become visible and available at the same moment as it's predecessor.
// (allowing one 'gateway' activity followed by freely available activity set, teacher control of access by activity group, ...)​.
define('FORMAT_LUDIC_ACCESS_GROUPED', 5);

// The item will become visible at the same moment as it's predecessor
// but will only become available after the predecessor has been completed.
define('FORMAT_LUDIC_ACCESS_CHAINED_AND_GROUPED', 6);

/**
 * Class context_helper
 *
 * @package format_ludic
 */
class context_helper {

    /**
     * Singleton.
     *
     * @var context_helper
     */
    public static $instance;

    /**
     * Moodle $PAGE.
     *
     * @var \moodle_page
     */
    private $page = null;

    /**
     * Moodle $USER.
     *
     * @var \stdClass
     */
    private $user = null;

    /**
     * Moodle $COURSE.
     *
     * @var \stdClass
     */
    private $course = null;

    /**
     * Current course id.
     *
     * @var int
     */
    private $courseid = null;

    /**
     * Database access.
     *
     * @var database_api|null
     */
    private $dbapi = null;

    /**
     * Manipulate user data.
     *
     * @var data_api
     */
    private $dataapi = null;

    /**
     * Current course context.
     *
     * @var \context_course
     */
    private $context = null;

    /**
     * Return course format moodle config.
     * Use {@link get_config()} with 'format_ludic'.
     *
     * @var \stdClass
     */
    private $config = null;

    /**
     * Current course format.
     * Use {@link course_get_format()}
     *
     * @var \format_base
     */
    private $courseformat = null;

    /**
     * Current course format options.
     *
     * @var array
     */
    private $courseformatoptions = null;

    /**
     * One of current course format options ('ludic_config').
     * In ludic_config you can find all the definitions of skins.
     *
     * @var array
     */
    private $ludicconfig = null;

    /**
     * Current course sections.
     *
     * @var section[]
     */
    private $sections = null;

    /**
     * Current course section.
     *
     * @var section
     */
    private $section = null;

    /**
     * Current section->id.
     *
     * @var int
     */
    private $sectionid = null;

    /**
     * Current section->section.
     *
     * @var int
     */
    private $sectionidx = null;

    /**
     * Current course modules.
     *
     * @var course_module[]
     */
    private $coursemodules = null;

    /**
     * Current course module.
     *
     * @var course_module[]
     */
    private $coursemodule = null;

    /**
     * This includes information about the course-modules and the sections on the course.
     * It can also include dynamic data that has been updated for the current user.
     *
     * @var \course_modinfo
     */
    private $courseinfo = null;

    /**
     * Get array from course-module instance to cm_info object within this course, in order of appearance.
     *
     * @var \cm_info[]
     */
    private $coursemodulesinfo = null;

    /**
     * context_helper constructor.
     *
     * @param \moodle_page $page
     */
    public function __construct(\moodle_page $page) {
        global $USER;
        $this->page     = $page;
        $this->user     = $USER;
        $this->courseid = $page->course->id;
        $this->dbapi    = new database_api($this);
        $this->dataapi  = new data_api($this);
    }

    /**
     * @param \moodle_page $page
     * @return context_helper
     */
    public static function get_instance(\moodle_page $page) {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self($page);
        }
        return self::$instance;
    }

    /**
     * @return \moodle_page
     */
    public function get_page() {
        return $this->page;
    }

    /**
     * @return database_api
     */
    public function get_database_api() {
        return $this->dbapi;
    }

    /**
     * @return data_api
     */
    public function get_data_api() {
        return $this->dataapi;
    }

    /**
     * Return current global $USER.
     *
     * @return \stdClass $USER
     */
    public function get_user() {
        return $this->user;
    }

    /**
     * Return current user id.
     *
     * @return int
     */
    public function get_user_id() {
        return $this->get_user()->id;
    }

    /**
     * Return current course.
     *
     * @return course
     */
    public function get_course() {
        if ($this->course === null) {
            $this->course = new course($this->page->course);
        }
        return $this->course;
    }

    /**
     * Return current course.
     *
     * @return \stdClass
     */
    public function get_moodle_course() {
        return $this->page->course;
    }

    /**
     * Return current course id.
     *
     * @return int
     */
    public function get_course_id() {
        return $this->courseid;
    }

    /**
     * Return current section id or 0.
     *
     * @return int|mixed|null
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_section_id() {

        // We have a stored section id then return it.
        if ($this->sectionid !== null) {
            return $this->sectionid;
        }

        // We haven't got a stored section id then try generating one.
        $sectionidx = optional_param('section', 0, PARAM_INT);

        // Replace the null with a 0 to avoid wasting times on trying to re-evaluate next time round.
        $this->sectionid = 0;

        if ($this->page->pagetype == 'course-view-ludimoodle') {

            // We're on a course view page and the course-relative section number is provided
            // so lookup the real section id.
            $courseid        = $this->courseid;
            $sectionid       = $this->dbapi->get_section_id_by_courseid_and_sectionidx($courseid, $sectionidx);
            $this->sectionid = $sectionid;

        } else if (isset($this->page->cm->section)) {

            // We're in an activity that is declaring its section id so we're in luck.
            $this->sectionid = $this->page->cm->section;

        }

        // Return the stored result.
        return $this->sectionid;
    }

    /**
     * Return current section->section (sectionidx) or -1.
     *
     * @return mixed|null
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_section_idx() {

        // We have a stored section idx then return it.
        if ($this->sectionidx !== null) {
            return $this->sectionidx;
        }
        // Replace the null with a -1 to avoid wasting times on trying to re-evaluate next time round.
        $this->sectionidx = -1;

        $sectionid = $this->get_section_id();
        if ($sectionid > 0) {
            // Retrieve section idx from section id.
            $this->sectionidx = $this->dbapi->get_section_idx_by_id($sectionid);
        }

        // Return the stored result.
        return $this->sectionidx;
    }

    /**
     * Return current cm id or 0.
     *
     * @return int
     */
    public function get_cm_id() {
        return isset($this->page->cm->id) ? $this->page->cm->id : 0;
    }

    /**
     * Return where the user is in course - course / section / mod.
     *
     * @return string
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function get_location() {
        $cmid       = $this->get_cm_id();
        $sectionidx = $this->get_section_idx();

        // On course page by default.
        $location = 'course';

        if ($cmid > 0) {

            // We are in course module.
            $location = 'mod';

        } else if ($sectionidx > 0) {

            // We are in course section.
            $location = 'section';

        }

        return $location;
    }

    /**
     * Return current course context.
     *
     * @return \context_course
     */
    public function get_course_context() {
        if ($this->context === null) {
            $this->context = $this->get_course()->get_context();
        }
        return $this->context;
    }

    /**
     * Return current course context id.
     *
     * @return int
     */
    public function get_context_id() {
        return $this->get_course_context()->id;
    }

    /**
     * Check if user is admin.
     *
     * @return bool
     */
    public function is_user_admin() {
        return is_siteadmin($this->get_user_id());
    }

    /**
     * Check if user has the role defined in short name.
     *
     * @param $roleshortname
     * @return bool
     * @throws \dml_exception
     */
    public function user_has_role_in_course($roleshortname) {
        if (!$roleid = $this->dbapi->get_role_id_by_role_shortname($roleshortname)) {
            return false;
        }
        return user_has_role_assignment($this->get_user_id(), $roleid, $this->get_context_id());
    }

    /**
     * Checks if the current user has one role in an array of role short names.
     *
     * @param $roleshortnames array
     * @return bool
     * @throws \dml_exception
     */
    public function user_has_one_role_in_course($roleshortnames) {
        $hasrole = false;
        foreach ($roleshortnames as $roleshortname) {
            if (!$roleid = $this->dbapi->get_role_id_by_role_shortname($roleshortname)) {
                $hasrole = false;
                continue;
            }
            $hasrole = user_has_role_assignment($this->get_user_id(), $roleid, $this->get_context_id());
        }
        return $hasrole;
    }

    /**
     * True if in edit mode.
     *
     * @return bool
     */
    public function is_editing() {
        return $this->page->user_is_editing();
    }

    /**
     * Checks if the current page type is part of an array of page types.
     *
     * @param $pagetypes
     * @return bool
     */
    public function is_page_type_in($pagetypes) {
        // Get current type page.
        $currenttype = $this->page->pagetype;

        // Check if current type is in given array.
        return in_array($currenttype, $pagetypes);
    }

    /**
     * Get course info of current course.
     *
     * @return \course_modinfo
     * @throws \moodle_exception
     */
    public function get_course_info() {
        if ($this->courseinfo == null) {
            $this->courseinfo = $this->get_course()->get_course_info();
        }
        return $this->courseinfo;
    }

    /**
     * Rebuild course info.
     *
     * @throws \moodle_exception
     */
    public function rebuild_course_info() {
        $this->courseinfo = $this->get_course()->get_course_info();
    }

    /**
     * Return course format moodle config.
     *
     * @return \stdClass
     * @throws \dml_exception
     */
    public function get_course_format_config() {
        if ($this->config == null) {
            $this->config = get_config('format_ludic');
        }
        return $this->config;
    }

    /**
     * Get array from course-module instance to cm_info object within this course, in order of appearance.
     *
     * @return \cm_info[]
     * @throws \moodle_exception
     */
    public function get_course_modules_info() {
        if ($this->coursemodulesinfo == null) {
            $this->coursemodulesinfo = $this->get_course_info()->get_cms();
        }
        return $this->coursemodulesinfo;
    }

    /**
     * Get course by id.
     *
     * @param $courseid
     * @return course
     * @throws \dml_exception
     */
    public function get_course_by_id($courseid) {
        if ($courseid === $this->courseid) {
            return $this->get_course();
        }
        $course = \get_course($courseid);
        $course = new course($course);
        return $course;
    }

    /**
     * Get all sections of current course.
     *
     * @return section[]
     * @throws \moodle_exception
     */
    public function get_sections() {
        if ($this->sections == null) {
            $this->sections = $this->get_course()->get_sections();
        }
        return $this->sections;
    }

    /**
     * Get current course section or false.
     *
     * @return bool|section
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_section() {
        if ($this->section == null) {
            $sectionid     = $this->get_section_id();
            $this->section = $sectionid > 0 ? $this->get_section_by_id($sectionid) : false;
        }
        return $this->section;
    }

    /**
     * Get section by id or false.
     *
     * @param $sectionid
     * @return section|false
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_section_by_id($sectionid) {
        $sectionrecord = $this->dbapi->get_section_by_id($sectionid);
        return $sectionrecord ? new section($sectionrecord) : false;
    }

    /**
     * Get section 0
     *
     * @return false|section
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_global_section() {
        // Get section 0 id.
        $sectionid = $this->dbapi->get_section_id_by_courseid_and_sectionidx($this->courseid, 0);

        // Return section 0.
        return $this->get_section_by_id($sectionid);
    }

    /**
     * Get section 0 description.
     *
     * @return string
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_global_description() {
        // Get section 0.
        $globalsection = $this->get_global_section();

        // Return section 0 description.
        return $globalsection->sectioninfo->summary;
    }

    /**
     * Get all course modules of current course.
     *
     * @return course_module[]
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course_modules() {
        if ($this->coursemodules == null) {

            // Get an array of cm_info[].
            $coursemodulesinfo = $this->get_course_modules_info();

            // Instantiate course modules.
            $coursemodules     = [];
            foreach ($coursemodulesinfo as $courseinfocm) {
                $coursemodules[] = new course_module($courseinfocm);
            }

            // Set coursemodules in cache.
            $this->coursemodules = $coursemodules;
        }

        // Return an array of course_module[].
        return $this->coursemodules;
    }

    /**
     * Get current course module or false.
     *
     * @return bool|course_module
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course_module() {
        if ($this->coursemodule == null) {
            $cmid               = $this->get_cm_id();
            $this->coursemodule = $cmid > 0 ? $this->get_course_module_by_id($cmid) : false;
        }
        return $this->coursemodule;
    }

    /**
     * Get course module by id.
     *
     * @param $cmid
     * @return course_module
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_course_module_by_id($cmid) {
        $courseinfo   = $this->get_course_info();
        $courseinfocm = $courseinfo->get_cm($cmid);
        return new course_module($courseinfocm);
    }

    /**
     * Get course format of current course.
     *
     * @return \format_base
     */
    public function get_course_format() {
        if ($this->courseformat == null) {
            $this->courseformat = $this->get_course_format_by_course_id($this->courseid);
        }
        return $this->courseformat;
    }

    /**
     * Get course format by course id.
     *
     * @param $courseid
     * @return \format_base
     */
    public function get_course_format_by_course_id($courseid) {
        return course_get_format($courseid);
    }

    /**
     * Get course format options of current course.
     *
     * @return array
     */
    public function get_course_format_options() {
        if ($this->courseformatoptions == null) {
            $this->courseformatoptions = $this->get_course_format_options_by_course_id($this->courseid);
        }
        return $this->courseformatoptions;
    }

    /**
     * Get course format option of current course with name = $name.
     *
     * @param $name
     * @return bool|mixed
     */
    public function get_course_format_option_by_name($name) {
        $courseformatoptions = $this->get_course_format_options();
        return isset($courseformatoptions[$name]) ? $courseformatoptions[$name] : false;
    }

    /**
     * Get course format options by course id.
     *
     * @param $courseid
     * @return array
     */
    public function get_course_format_options_by_course_id($courseid) {
        $courseformat = $this->get_course_format_by_course_id($courseid);
        return $courseformat->get_format_options();
    }

    /**
     * Updates format options for a course
     *
     * If $courseformatoptions does not contain property with the option name, the option will not be updated
     *
     * @param \stdClass|array $courseformatoptions return value from {@link moodleform::get_data()} or array with data
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($courseformatoptions) {
        $courseformat = $this->get_course_format();
        return $courseformat->update_course_format_options($courseformatoptions);
    }

    /**
     * Get ludic config from course format options.
     *
     * @return array
     */
    private function get_ludic_config() {
        if ($this->ludicconfig == null) {

            // Get ludic config (json).
            $ludicconfig = $this->get_course_format_option_by_name('ludic_config');

            // Always return an array.
            if (!empty($ludicconfig)) {
                $this->ludicconfig = get_object_vars(json_decode($ludicconfig));
            } else {
                $this->ludicconfig = [];
            }

        }

        return $this->ludicconfig;
    }

    /**
     * Get skins from ludic config.
     *
     * @return array
     */
    public function get_skins_config() {
        // Get ludic config.
        $ludicconfig = $this->get_ludic_config();

        // Ensure to return an array.
        return isset($ludicconfig['skins']) ? get_object_vars($ludicconfig['skins']) : [];
    }

    /**
     * Get all skins.
     *
     * @return skin[]
     */
    public function get_skins() {
        // Skins that don't depend on the ludic config.
        $defaultskins = $this->get_default_skins();

        // Skins from ludic config.
        $skinsconfig = $this->get_skins_config();

        // Merge and instance all skins.
        $skins    = [];
        $allskins = array_merge($defaultskins, $skinsconfig);
        foreach ($allskins as $skin) {
            $skins[$skin->id] = skin::get_by_instance($skin);
        }

        // Return all skins.
        return $skins;
    }

    /**
     * Get default skins.
     * They don't depend on the ludic config !
     *
     * @return skin[]
     */
    public function get_default_skins() {
        return [
                coursemodule\inline::get_instance()
        ];
    }

    /**
     * Get section skins.
     *
     * @return skin[]
     */
    public function get_section_skins() {
        // Get all skins.
        $skins = $this->get_skins();

        // Keep only section skin.
        $sectionskins = [];
        foreach ($skins as $skin) {
            if ($skin->location === 'section') {
                $sectionskins[$skin->id] = $skin;
            }
        }

        // Return filtered skins.
        return $sectionskins;
    }

    /**
     * Get course modules skins.
     *
     * @return skin[]
     */
    public function get_course_module_skins() {
        // Get all skins.
        $skins = $this->get_skins();

        // Keep only course module skin.
        $coursemodulesskins = [];
        foreach ($skins as $skin) {
            if ($skin->location === 'coursemodule') {
                $coursemodulesskins[$skin->id] = $skin;
            }
        }

        // Return filtered skins.
        return $coursemodulesskins;
    }

}