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
 * Parses an objectref expression.
 * An objectref expression addresses a particular object
 * defined by an identifier ref. some special 
 * cases such as 'current' will evaluate the object at runtime.
 *
 * Objectrefs evaluate to objects (StdClass records from the DB as
 * first approach. Objectrefs have an object pattern attached to (i.e.
 * an objecttype that is an additional attribute in the record, telling
 * which object|table it comes from. Our goal is to reach a sufficient
 * level of script and expression formal typing so that parsing can
 * detect quickly errors before they occur at runtime.
 *
 * Typical syntaxes.
 *
 * 4 members syntax:
 * <objecttype>:<idfield>:<idvalue>:<attrname>
 * 3 members syntax:
 * <objecttype>:current:<attrname>
 *
 * @package local_moodlescript
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright (c) 2017 onwards Valery Fremaux (http://www.mylearningfactory.com)
 */
namespace local_moodlescript\engine;

use ReflectionObject;
use StdClass;

defined('MOODLE_INTERNAL') || die();

class parse_objectref {

    public static $objecttables = [
        'course' => 'course',
        'category' => 'course_categories',
        'user' => 'user',
        'group' => 'groups',
        'cohort' => 'cohort',
        'user_profile_field' => 'user'
    ];

    public static $identifier = [
        'course' => [
            'id',
            'idnumber',
            'shortname'
        ],
        'category' => [
            'id',
            'idnumber'
        ],
        'user' => [
            'id',
            'idnumber',
            'username',
            'email'
        ],
        'cohort' => [
            'id',
            'idnumber',
            'name'
        ],
        'group' => [
            'id',
            'idnumber',
            'name'
        ],
        // special case : id of user profilefield is
        // id of the user having this profile field value.
        'user_profile_field' => [
            'id',
            'idnumber',
            'username',
            'email'
        ]
    ];

    protected $logger;

    protected $isresolvable;

    public function __construct(&$logger) {
        if (is_null($logger)) {
            throw new coding_exception("Null logger.");
        }
        $this->logger = $logger;
    }

    /**
     * Parses an objectref.
     * @param string $objectref a value ref expression
     * @return true, or unmodified input if parse suceeds. False if error.
     */
    public function parse($objectref, $step = 'parse') {

        $parts = explode(':', $objectref);

        // Check objecttype is in expected range.
        if (!in_array($parts[0], array_keys(self::$objecttables))) {
            $this->logger->error('Objectref object type not in accepted range in '.$objectref);
            return false;
        }

        if (count($parts) == 2) {
            if ($parts[1] != 'current') {
                $this->logger->error("Objectref expression should have 'current' in ".$objectref);
                return false;
            }
            $this->isresolvable = false;
        } else if (count($parts) == 3) {
            if (!in_array($parts[1], self::$identifiers[$part[0]])) {
                $this->logger->error("Objectref expression identifier do not match objecttype in ".$objectref);
                return false;
            }
        } else {
            $this->logger->error("Objectref expression can only have 2 or 3 members. ".count($parts).' were given in '.$objectref);
            return false;
        }

        if (!$isresolvable) {
            return $objectref; // Must wait some next step to resolve.
        }
        return true; // Parse is successfull.
    }

    /**
     * Get real object (without any control)
     * @param string $objectref an object ref 
     * @return an flat object (DB record) or null.
     */
    public function evaluate($objectref) {
        global $DB, $COURSE, $USER;

        $parts = explode(':', $objectref);

        if ($parts[1] == 'current') {
            switch ($parts[0]) {
                case 'course': {
                    $course = $DB->get_record('course_categories', ['id' => $COURSE->id]);
                    $course->objecttype = 'course';
                    return objectref::instance($course);
                }
                case 'user': {
                    $user = $DB->get_record('user', ['id' => $USER->id]);
                    // Add all profile fields.
                    $this->add_profile_fields($user);
                    $user->objecttype = 'user';
                    return objectref::instance($user);
                }
                case 'category': {
                    $category = $DB->get_record('course_categories', ['id' => $COURSE->category]);
                    $category->objecttype = 'category';
                    return objectref::instance($category);
                }
                case 'user_profile_field': {
                    $user = $DB->get_record('user', ['id' => $USER->id]);
                    $user->objecttype = 'user';
                    return objectref::instance($user);
                }
                case 'cohort': {
                    // For cohorts some heuristics will apply : 
                    // If $USER is member of a single cohort. this cohort.
                    $mycohortsmemberships = $DB->get_records('cohort_members', ['userid' => $USER->id], 'id', 'id,cohortid');
                    if ($mycohortsmemberships) {
                        if (count($mycohortsmemberships) == 1) {
                            $mycohort = array_shift($mycohortsmemberships);
                            $cohort = $DB->get_record('cohort', ['id' => $mycohort->cohortid]);
                            $cohort->objecttype = 'cohort';
                            return objectref::instance($cohort);
                        }

                        // If $USER is member of single cohort wich has an active enrolment in this course, take this one.
                        foreach ($mycohortsmemberships as $c) {
                            // Check there is an enrollement method for current course.
                            // TODO FINISH HERE
                            $sql = "
                            ";
                            if ($DB->record_exists_sql($sql)) {
                                $cohort = $DB->get_record('cohort', ['id' => $c->cohortid]);
                                $cohort->objecttype = 'cohort';
                                return objectref::instance($cohort);
                            }
                        }
                    }
                    return null;
                }
                case 'group': {
                    $sql = "
                        SELECT
                            grm.id,
                            gr.id
                        FROM
                            {groups} gr,
                            {groups_members} grm
                        WHERE
                            gr.id = grm.groupid AND
                            gr.courseid = ? AND
                            grm.userid = ?
                    ";
                    $mygroupmemberships = $DB->get_records_sql($sql, [$USER->id, $COURSE->id], 'id', 'grm.id,gr.id as groupid');
                    if ($mygroupmemberships) {
                        if (count($mygroupmemberships) == 1) {
                            $mygroup = array_shift($mygroupmemberships);
                            $group = $DB->get_record('group', ['id' => $mygroup->groupid]);
                            $group->objecttype = 'groups';
                            return objectref::instance($group);
                        }

                        // Take the apparent active group in course.
                        $group = groups_get_course_group($COURSE);
                        $group->objecttype = 'groups';
                        return objectref::instance($group);
                    }
                }
                default: {
                    return null;
                }
            }
        }

        // This is to better focus on data when field is not unique identifier.
        $extraclauses = [];
        if ($parts[0] == 'group') {
            $extraclauses = ['courseid' => $COURSE->id];
        }

        // Unquote (remove leading and training quote) identifier value if necessary.
        $parts[2] = preg_replace('/^"/', '', $parts[2]);
        $parts[2] = preg_replace('/"$/', '', $parts[2]);
        // Else we return the object by suitable identifier
        $this->logger->trace("   Object ref having ".$parts[2]." as ".$parts[1]." in ".self::$objecttables[$parts[0]]);
        // $this->logger->trace(print_r($parts, true));
        // $this->logger->trace(print_r($extraclauses));
        $objectrec = $DB->get_record(self::$objecttables[$parts[0]], [$parts[1] => $parts[2]] + $extraclauses);
        if (!$objectrec) {
            $this->logger->trace("Object ref : Fetch failed.");
            if ($parts[0] == 'group') {
                $group = new StdClass;
                $group->objecttype = 'groups';
                $group->id = 0;
                return $group;
            }
            return null;
        }
        $objectrec->objecttype = self::$objecttables[$parts[0]];
        $instance = objectref::instance($objectrec);
        if ($parts[0] == 'user') {
            $this->add_profile_fields($instance);
        }
        // $this->logger->trace("Object ref : ".print_r($instance, true));
        return $instance;
    }

    /**
     * for user object, add custom profile field values to object.
     * @param objectref &$user
     */
    protected function add_profile_fields(&$user) {
        global $DB;

        $allfields = $DB->get_records('user_info_field');
        foreach ($allfields as $field) {
            $fieldshort = 'profile_field_'.$field->shortname;
            $user->$fieldshort = $DB->get_field('user_info_data', 'data', ['userid' => $user->id, 'fieldid' => $field->id]);
        }
    }
}

/**
 * An object class wrapper for object refs.
 */
class objectref {

    /**
     * Class casting
     *
     * @param string|object $destination
     * @param object $sourceObject
     * @return object
     */
    public static function instance($sourceObject) {

        $destination = new objectref();
        $sourceReflection = new ReflectionObject($sourceObject);
        $destinationReflection = new ReflectionObject($destination);
        $sourceProperties = $sourceReflection->getProperties();
        foreach ($sourceProperties as $sourceProperty) {
            $sourceProperty->setAccessible(true);
            $name = $sourceProperty->getName();
            $value = $sourceProperty->getValue($sourceObject);
            if ($destinationReflection->hasProperty($name)) {
                $propDest = $destinationReflection->getProperty($name);
                $propDest->setAccessible(true);
                $propDest->setValue($destination,$value);
            } else {
                $destination->$name = $value;
            }
        }
        return $destination;
    }
}