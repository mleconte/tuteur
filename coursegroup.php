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
 * Utility class for course'group selector.
 * Etablish the relation beetween studient en course's groups, and make the selector group renderer.
 * If there no group an hidding input replace the selector.
 * @package   report_tuteur
 * @copyright 2016 Pole de Ressource Numerique, Universite du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_tuteur_coursegroup {
    /** course ID.*/
    private $courseId;
    /** number of group (not empty) present in the course.*/
    private $nbGroup;
    /** Array of group with id and name 'id', 'name' .*/
    private $tabGroup;
    /** Array of group's members with 'idMember', 'idGroupe'.*/
    private $tabMember;

    /**
     * Instanciate class and search informations on course's groups.
     */
    function __construct($idCourse) {
        $this->courseId = $idCourse;
        $this->tabGroup = array();
        $this->tabMember = array();
        $this->nbGroup = 0;
        if (is_numeric($this->courseId) && $this->courseId > 0) {
            $this->searchInformation();
        }
    }

    /**
     * Return html renderer of group's selector.
     * The select Html element has name 'chxGroupe'.
     * If no group exist an Hidden Input element is substitute to the select, with the same name
     * and a generic value ('0') for all the group.
     *
     * @param int $idSelected the selected group's ID (0 for all).
     * @return string.
     */
    public function rendererSelectGroup($idSelected) {
        $ret = "";
        if ($this->nbGroup > 0) {
            $ret = "<select name='chxGroupe'>";
            if ($idSelected == 0) {
                $ret = $ret . "<option value='0' selected>" . get_string('all_group', 'report_tuteur' ). "</option>";
            } else {
                $ret = $ret . "<option value='0'>" . get_string('all_group', 'report_tuteur' ). "</option>";
            }
            foreach ($this->tabGroup as $groupe) {
                $ret = $ret . "<option value='". $groupe->id . "'";
                if ($groupe->id == $idSelected) {
                    $ret = $ret . " selected";
                }
                $ret = $ret .">" . $groupe->name ."</option>";
            }
            $ret = $ret . "</select>";
        } else {
            $ret = "<input type='hidden' name='chxGroupe' value='0'>";
        }

        return $ret;
    }

    /**
     * Return true if the student is a member of the group.
     * If the group's ID has value 0 the return is true.
     *
     * @param int $idGroup the group's ID (or 0 for all)
     * @param int $idUser the student's ID.
     * @return bool.
     */
    public function isMember($idGroup, $idUser) {
        if ($idGroup == 0) {
            return true;
        }
        $index = 0;
        $notFound = true;
        while ($index < count($this->tabMember) && $notFound) {
            if ($this->tabMember[$index]->idMember == $idUser && $this->tabMember[$index]->idGroupe == $idGroup) {
                $notFound = false;
            }
            $index = $index + 1;
        }
        return ! $notFound;
    }

    /**
     * Fill arrays tabGroup and tabMember.
     */
    private function searchInformation() {
        global $DB;
        $sqlGroupes = "SELECT g.name, g.id, m.userid
                         from {groups_members} m, {groups} g
                        where g.id = m.groupid
                          and g.courseid = ?
                    Order by name;";
        $resultGroupes = $DB->get_recordset_sql($sqlGroupes , array($this->courseId));

        $zrName = "";
        foreach ($resultGroupes as $data) {
            if (strcmp($zrName, $data->name) != 0) {
                $record = new stdClass();
                $record->id = $data->id;
                $record->name = $data->name;
                $this->tabGroup[] = $record;
                $this->nbGroup = $this->nbGroup + 1;
                $zrName = $data->name;
            }

            $member = new stdClass();
            $member->idMember = $data->userid;
            $member->idGroupe = $data->id;
            $this->tabMember[] = $member;
        }
    }
}
