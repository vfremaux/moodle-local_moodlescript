<?php


namespace local_moodlescript\engine;


class parse_identifier {

    protected $table;

    protected $logger;

    public function __construct($table, &$logger = null) {

        if (empty($table)) {
            throw new coding_exception('Table cannot be empty for an object identifier');
        }

        $this->table = $table;
        $this->logger = $logger;
    }


    public function parse($fqidentifier) {
        global $DB;

        if (strpos($fqidentifier, ':') === false) {

            if (is_numeric($fqidentifier)) {
                $this->logger[] = 'Not numeric primary id on '.$table;
                return;
            }

            return $fqidentifier;
        }

        list($field, $identifier) = explode(':', $fqidentifier);
        $id = preg_replace('/^"|"$/', '', $identifier); // Remove double quotes.

        try {
            $id = $DB->get_field($this->table, 'id', array($field => $identifier));
        } catch (Exception $e) {
            $this->errorlog[] = 'Identifier query error in '.$this->table.' by '.$field;
            return;
        }

        return $id;
    }
}