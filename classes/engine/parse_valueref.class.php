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
 * Parses a valueref expression.
 * A valueref expression addresses a particular attribute in an object
 * defined by an identifier ref and an attribute name. some special 
 * cases such as 'current' will evaluate the object at runtime.
 *
 * A valueref always resolves to a scalar value (bool, numeric or string)
 * at evaluation time.
 *
 * Typical syntaxes.
 *
 * 4 members syntax:
 * <objecttype>:<idfield>:<id>:<attrname>
 * 3 members syntax:
 * <objecttype>:current:<attrname>
 *
 * @package local_moodlescript
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright (c) 2017 onwards Valery Fremaux (http://www.mylearningfactory.com)
 */
namespace local_moodlescript\engine;

defined('MOODLE_INTERNAL') || die();

class parse_valueref extends parse_objectref {

    /**
     *
     * @param string $valueref a value ref expression
     * @param string parse step as 'parse' or 'runtime'
     */
    public function parse($valueref, $step = 'parse') {
        global $DB;

        $parts = explode(':', $valueref);

        $this->isresolvable = true;
        if (count($parts) == 3) {
            $res = parent::parse($parts[0].':'.$parts[1], $step);
            $attribute = $parts[2];
        } else if (count($parts) == 4) {
            $res = parent::parse($parts[0].':'.$parts[1].':'.$parts[2], $step);
            $attribute = $parts[3];
        } else {
            $this->logger->error("Valueref expression can only have 3 or 4 members. ".count($parts).' were given in '.$valueref);
            return false;
        }

        if (empty($res)) {
            return false;
        }

        // Check attribute exists in object.
        if ($parts[0] != 'user_profile_field') {
            $dbman = $DB->get_manager();
            $table = new xmldb_table(self::$objecttables[$parts[0]]);
            $field = new xmldb_field($attribute);
            if (!$dbman->field_exists($table, $field)) {
                $this->logger->error('Valueref attribute not found for object type in '.$valueref);
                return false;
            }
        } else {
            // for user profile fields, attribute is a shortname of a user custom field.
            if (!$DB->record_exists('user_data_field', ['shortname' => $attribute])) {
                $this->logger->error('Valueref to inexistant user profile field in '.$valueref);
                return false;
            }
        }

        if (!$isresolvable) {
            return $valueref; // Must wait some next step to resolve.
        }
    }

    /**
     * Evaluate assumes that $valueref has been sucessfully parsed and resolved.
     * @param string $valueref a valueref to evaluate
     * @return a scalar value or null if cannot find.
     */
    public function evaluate($valueref) {

        $parts = explode(':', $valueref);

        if (count($parts) == 3) {
            $res = parent::evaluate($parts[0].':'.$parts[1]);
            $attribute = $parts[2];
        } else if (count($parts) == 4) {
            $res = parent::evaluate($parts[0].':'.$parts[1].':'.$parts[2]);
            $attribute = $parts[3];
        } else {
            $this->logger->error("Valueref expression can only have 3 or 4 members. ".count($parts).' were given in '.$valueref);
            return false;
        }

        if (!is_null($res)) {
            if (!empty($res->$attribute)) {
                return $res->$attribute;
            } else {
                print_object($res);
            }
        }
        return null;
    }
}