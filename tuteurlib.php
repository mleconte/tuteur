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
 * Utility functions compute activity's state, and feedback's link.
 *
 * @package report_tuteur
 * @copyright 2016 Pole de Ressource Numerique, Universite du Mans
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined ( 'MOODLE_INTERNAL' ) || die ();

/**
 * Filter activities to keep only the activity we wat to show.
 * @param array of stdClass $tabActivities course's activities.
 * @return array of stdClass.
 */
function report_tuteur_filterActivities($tabActivities) {
    $type_act = report_tuteur_createActivitiesFilter();
    $ret = array();
    foreach ($tabActivities as $activity) {
        if (stripos($type_act, "&" . $activity->modname . "&") !== false) {
            $ret[] = $activity;
        }
    }
    return $ret;
}

/**
 * Return the filter of activity we want to show.
 * This is a string with the name of each activity separate with &.
 * @return string.
 */
function report_tuteur_createActivitiesFilter() {
    $filterTypeActivities = "&";
    $paramGroup = optional_param ( 'chxGroupe', null, PARAM_TEXT );
    if ($paramGroup == null) {
        $filterTypeActivities = $filterTypeActivities . "assign&quiz&journal&lesson&dialoguegrade&";
    } else {
        if (optional_param ( 'devoir', null, PARAM_TEXT ) != null) {
            $filterTypeActivities = $filterTypeActivities . "assign&";
        }
        if (optional_param ( 'test', null, PARAM_TEXT ) != null) {
            $filterTypeActivities = $filterTypeActivities . "quiz&";
        }
        if (optional_param ( 'journal', null, PARAM_TEXT ) != null) {
            $filterTypeActivities = $filterTypeActivities . "journal&";
        }
        if (optional_param ( 'lesson', null, PARAM_TEXT ) != null) {
            $filterTypeActivities = $filterTypeActivities . "lesson&";
        }
        if (optional_param ( 'dlgrade', null, PARAM_TEXT ) != null) {
            $filterTypeActivities = $filterTypeActivities . "dialoguegrade&";
        }
    }
    return $filterTypeActivities;
}

/**
 * Return the last quiz attempt id or -1 if not found.
 *
 * @param string $idActivity current activity's ID.
 * @param string $idUser the student's ID.
 * @return number.
 */
function report_tuteur_getNumAttempt($idUser, $idActivity) {
    global $DB;
    $infoActivite = $DB->get_record_select ( 'course_modules', 'id = ?', array (
            $idActivity 
    ) );
    $rs = $DB->get_field ( 'quiz_attempts', 'MAX(id)', array (
            'quiz' => $infoActivite->instance,
            'userid' => $idUser 
    ) );
    
    if (isset ( $rs )) {
        return $rs;
    }
    return - 1;
}

/**
 * Return the state of activity as a number.
 * 2 if feedback earlier than submission.
 * 1 if submission earlier than feedback.
 * 0 no submission.
 * 
 * @param string $idActivity
 *        current activity's ID.
 * @param string $idUser
 *        user's ID.
 * @param string $modname
 *        the name of the current module.
 * @return number.
 */
function report_tuteur_getActivityState($idUser, $idActivity, $modname) {
    try {
        if ($modname == "quiz") {
            return report_tuteur_QuizState ( $idUser, $idActivity );
        }
        if ($modname == "journal") {
            return report_tuteur_JournalState ( $idUser, $idActivity );
        }
        if ($modname == "lesson") {
            return report_tuteur_LessonState ( $idUser, $idActivity );
        }
        if ($modname == "assign") {
            return report_tuteur_AssignState ( $idUser, $idActivity );
        }
        if ($modname == "dialoguegrade") {
            return report_tuteur_DlgradeState ( $idUser, $idActivity );
        }

    } catch ( Exception $err ) {
        print "<br>".$err->getMessage()."<br>";
        return 0;
    }
}

/**
 * Return a list of the course's student (to reword the row number corresponding
 * student).
 *
 * @param stdClass $course
 *        	current course.
 * @return array.
 */
function report_tuteur_StudentList($course) {
    global $DB;
    $sql = "SELECT u.id,u.firstname,u.lastname,u.lastlogin, ra.timemodified , u.timecreated
        FROM {user} u
        JOIN {role_assignments} ra ON ra.userid = u.id
        JOIN {role} r ON ra.roleid = r.id
        JOIN {context} con ON ra.contextid = con.id
        JOIN {course} c ON c.id = con.instanceid AND con.contextlevel = 50
        WHERE r.shortname = 'student'
          AND c.id = ? ORDER BY u.id";

    $userlist = $DB->get_recordset_sql ( $sql, array ($course->id ) );
    $data = array ();
    foreach ( $userlist as $user ) {
        $data [] = $user;
    }
    return $data;
}

/**
 * Return the row number corresponding to student ID.
 *
 * @param string $idUser student's ID.
 * @param array $tabStudent
 *        the student present in course. (cf report_tuteur_StudentList)
 * @return number.
 */
function report_tuteur_getNumRow($idUser, $tabStudent) {
    $indice = 0;
    while ( $indice < count ( $tabStudent ) && $tabStudent [$indice]->id != $idUser ) {
        $indice ++;
    }

    if ($indice < count ( $tabStudent )) {
        return $indice;
    }
    return -1;
}

/**
 * Return the lesser lesson's attempt without feedback.
 * If all lesson's attempt have feedback, return the great attempt.
 * In other way return 0.
 *
 * @param string $idUser student's ID.
 * @param string $idActivity activity's ID.
 */
function report_tuteur_getNumAttemptLesson($idUser, $idActivity) {
    global $DB;
    $infoActivity = $DB->get_record_select ( 'course_modules', 'id = ?', array ($idActivity) );

    $sql = "SELECT id
            FROM {lesson_pages}
           WHERE lessonid = ? AND qtype = 10";
    $rs = $DB->get_recordset_sql ( $sql, array ($infoActivity->instance) );

    $data = array ();
    $nb = 0;
    foreach ( $rs as $result ) {
        $data [] = $result;
        $nb ++;
    }
    if ($nb == 0) {
        return 0;
    }

    $nb = 0;
    $compos = array ();
    foreach ( $data as $page ) {
        $compo = $DB->get_record_select ( 'lesson_attempts', 'lessonid = ? and pageid = ? and userid = ?', array (
                $infoActivity->instance,
                $page->id,
                $idUser 
        ) );
        if (isset ( $compo->useranswer )) {
            $compos [] = $compo;
            $nb ++;
        }
    }
    if ($nb == 0) {
        return 0;
    }
    $ret = 0;
    $numDefault = 0;
    foreach ( $compos as $composition ) {
        $answer = unserialize ( $composition->useranswer );
        if (! isset ( $answer->response )) {
            $ret = 1;
        } else {
            if ($answer->response == "") {
                if ($ret == 0 || $ret > $composition->id) {
                    $ret = $composition->id;
                }
            }
            if ($numDefault < $composition->id) {
                $numDefault = $composition->id;
            }
        }
    }
    if ($ret == 0) {
        $ret = $numDefault;
    }
    return $ret;
}

// PRIVATE Methods

/**
 * Return the earliest comment's date (for assign)
 * Assign_grades is modified on grade or feedback
 *
 * @param string $idUser student's ID.
 * @param string $idActivity activity's ID.
 * @return long equal to MAX(timemodified)
 */
function report_tuteur_getModifAssignGrades($idUser, $idActivity) {
    global $DB;
    $cours_mod = $DB->get_record ( "course_modules", array ('id' => $idActivity) );

    $rs = $DB->get_record_select('assign_grades', 'assignment = ? and userid = ? and grade != -1.0', array ($cours_mod->instance, $idUser) );

    if (isset ( $rs->timemodified ) &&  $rs->timemodified != null) {
        return $rs->timemodified;
    }
    return 0;
}

/**
 * Return the state of assign activity as a number.
 * 2 if feedback earlier than submission.
 * 1 if submission earlier than feedback.
 * 0 no submission.
 *
 * @param string $idUser student's ID.
 * @param string $idActivity activity's ID.
 * @return number.
 */
function report_tuteur_AssignState($idUser, $idActivity) {
    $dateGrade = 0;
    $dateComment = 0;
    $dateSubmit = 0;

    $grade = report_tuteur_isGrade ( $idUser, $idActivity );
    if (isset ( $grade->timemodified )) {
        $dateGrade = $grade->timemodified;
    }

    $idcontext = report_tuteur_getContext ( $idActivity );
    $submission = report_tuteur_getLastSubmit ( $idUser, $idActivity );

    if (isset ( $submission->id )) {
        $iditem = $submission->id;
        $dateSubmit = $submission->timemodified;

        $comment = report_tuteur_getLastComment ( $idcontext, $iditem );
        if (isset ( $comment->timecreated )) {
            $dateComment = $comment->timecreated;
        }

        $otherComment = report_tuteur_getModifAssignGrades ( $idUser, $idActivity );
        if ($dateComment < $otherComment) {
            $dateComment = $otherComment;
        }
    }
    if ($dateGrade == 0 && $dateComment == 0 && $dateSubmit == 0) {
        return 0;
    }
    if ($dateSubmit > $dateGrade && $dateSubmit > $dateComment) {
        return 1;
    }
    return 2;
}

/**
 * Return the state of dlgGrade activity as a number.
 * 2 if feedback earlier than submission.
 * 1 if submission earlier than feedback.
 * 0 no submission.
 *
 * @param string $idUser student's ID.
 * @param string $idActivity activity's ID equal id course module.
 * @return number.
 */
function report_tuteur_DlgradeState ( $idUser, $idActivity ) {
    global $DB;
    $infoActivity = $DB->get_record_select ( 'course_modules', 'id = ?', array ($idActivity) );
    $conversationId = $DB->get_field_sql ( 'SELECT conversationid from {dialoguegrade_participants} where dialogueid = ? and userid= ?',
            array ($infoActivity->instance, $idUser) );
    // If conversation not exist activity not start !
    if ($conversationId == null) {
        return 0;
    }

    $sql = "SELECT authorid, timecreated
              FROM {dialoguegrade_messages}
             WHERE timecreated = (SELECT max(timecreated) FROM {dialoguegrade_messages} WHERE dialogueid = ? and conversationid = ?)
               AND dialogueid = ? and conversationid = ?";

    $rs = $DB->get_record_sql ( $sql, array ($infoActivity->instance, $conversationId,
            $infoActivity->instance, $conversationId) );
    if ($rs->authorid == $idUser) {
        return 1;
    }
    return 2;
}

/**
 * Return the earliest UniqueID of quiz's attempt or -1 if none are present.
 *
 * @param string $idUser student's ID.
 * @param string $idActivity activity's ID.
 * @return number.
 */
function report_tuteur_getUniqueIdAttempt($idUser, $idActivity) {
    global $DB;
    $infoActivity = $DB->get_record_select ( 'course_modules', 'id = ?', array ($idActivity) );
    $rs = $DB->get_field_sql ( 'SELECT uniqueid from {quiz_attempts} where timefinish = (select max(timefinish ) from {quiz_attempts} where quiz=? and userid=?) and userid=?', array (
            $infoActivity->instance,
            $idUser,
            $idUser
    ) );
    if (isset ( $rs )) {
        return $rs;
    }
    return - 1;
}

/**
 * Return the state of lesson activity as a number.
 * 2 if feedback earlier than submission.
 * 1 if submission earlier than feedback.
 * 0 no submission.
 *
 * @param string $idUser student's ID.
 * @param string $idActivity activity's ID.
 * @return number.
 */
function report_tuteur_LessonState($idUser, $idActivity) {
    global $DB;
    $infoActivity = $DB->get_record_select ( 'course_modules', 'id = ?', array ($idActivity) );

    $sql = "SELECT id
            FROM {lesson_pages}
           WHERE lessonid = ? AND qtype = 10";
    $rs = $DB->get_recordset_sql ( $sql, array ($infoActivity->instance) );

    $data = array ();
    $nb = 0;
    foreach ( $rs as $result ) {
        $data [] = $result;
        $nb ++;
    }
    if ($nb == 0) {
        return 0;
    }

    $nb = 0;
    $compos = array ();
    foreach ( $data as $page ) {
        $compo = $DB->get_record_select('lesson_attempts', 'lessonid = ? and pageid = ? and userid = ?',
                array ($infoActivite->instance, $page->id, $idUser));
        if (isset ( $compo->useranswer )) {
            $compos [] = $compo->useranswer;
            $nb ++;
        }
    }
    if ($nb == 0) {
        return 0;
    }
    $ret = 2;
    foreach ($compos as $composition ) {
        $answer = unserialize($composition);
        if (!isset($answer->response)) {
            $ret = 1;
        } else {
            if ($answer->response == "") {
                $ret = 1;
            }
        }
    }
    return $ret;
}

/**
 * Return the state of journal activity as a number.
 * 2 if feedback earlier than submission.
 * 1 if submission earlier than feedback.
 * 0 no submission.
 *
 * @param string $idUser student's ID.
 * @param string $idActivity activity's ID.
 * @return number.
 */
function report_tuteur_JournalState($idUser, $idActivity) {
    global $DB;

    $infoActivity = $DB->get_record_select('course_modules', 'id = ?', array ($idActivity) );

    $gradeItem = $DB->get_record_select('grade_items', 'courseid = ? and iteminstance = ? and itemmodule = ?',
                        array ($infoActivity->course, $infoActivity->instance, 'journal'));

    // MODIFIED 05/10/2016.
    if (isset ( $gradeItem->id )) {
        $sql = "SELECT overridden
                  FROM {grade_grades}
                 WHERE itemid = ? and userid = ?";
        $rs = $DB->get_field_sql($sql, array ($gradeItem->id, $idUser) );
        if ($rs > 0) {
            return 2;
        }
    }

    $inJournal = $DB->get_record_select('journal_entries', 'journal = ? and userid = ?',
            array ($infoActivity->instance, $idUser) );
    if (!isset($inJournal->modified)) {
        return 0;
    }
    if ($inJournal->modified > $inJournal->timemarked) {
        return 1;
    }

    return 2;
}

/**
 * Return the state of quiz activity as a number.
 * 2 if feedback earlier than submission.
 * 1 if submission earlier than feedback.
 * 0 no submission.
 *
 * @param string $idUser student's ID.
 * @param string $idActivity activity's ID.
 * @return number.
 */
function report_tuteur_QuizState($idUser, $idActivity) {
    global $DB;
    $idLastAttempt = report_tuteur_getUniqueIdAttempt ( $idUser, $idActivity );

    if ($idLastAttempt == false || $idLastAttempt == - 1) {
        return 0;
    }

    $sql = "SELECT count(st.id)
              FROM {question_attempt_steps} st
              JOIN {question_attempts} qa ON qa.id = st.questionattemptid
             where qa.questionusageid = ? 
               and st.userid != ?";
    $rs = $DB->get_field_sql($sql, array ($idLastAttempt,$idUser) );
    if ($rs == 0) {
        return 1;
    }

    return 2;
}

/**
 * Return context linked with activity.
 *
 * @param string $idActivity activity's ID.
 * @return number.
 */
function report_tuteur_getContext($idActivity) {
    global $DB;
    $rs = $DB->get_field_sql('SELECT id FROM {context} WHERE instanceid = ? AND contextlevel = 70', array ($idActivity));
    return $rs;
}

/**
 * Return the student's grade of assign or null.
 *
 * @param string $idUser student's ID.
 * @param string $idActivity activity's ID.
 * @return stdClass of grade, contain attribute
 *         $ret->finalgrade, $ret->feedback, $ret->timemodified.
 */
function report_tuteur_isGrade($idUser, $idActivity) {
    global $DB;
    $infoActivity = $DB->get_record_select ( 'course_modules', 'id = ?', array ($idActivity) );

    $sql = "SELECT g.finalgrade, g.feedback, g.timemodified
              FROM {grade_grades} g
              JOIN {grade_items} a ON a.id = g.itemid
             WHERE a.courseid = ? and a.iteminstance= ? and itemmodule=? and g.userid=?";

    $rs = $DB->get_record_sql($sql, array ($infoActivity->course, $infoActivity->instance, 'assign', $idUser));
    
    if (isset ( $rs )) {
        $grade = null;
        if (isset( $rs->finalgrade )) {
            $grade = $rs->finalgrade;
        }
        // Modification du 09/10/2018. 
        if ($grade != null || isset($rs->feedback)) {
            return $rs;
        }
    }
    return null;
}

/**
 * Return the lastest assign's submission.
 *
 * @param string $idUser student's ID.
 * @param string $idActivity activity's ID.
 * @return stdClass of assign_submission.
 */
function report_tuteur_getLastSubmit($idUser, $idActivity) {
    global $DB;
    $infoActivity = $DB->get_record_select('course_modules', 'id = ?', array ($idActivity));

    $sql = "SELECT id, status, timemodified
              FROM {assign_submission}
             WHERE timemodified = (SELECT max(timemodified)
                                     FROM {assign_submission}
                                    WHERE assignment = ? and userid= ? and status='submitted')
               and assignment = ? and userid=? and status='submitted'";
    $rs = $DB->get_record_sql($sql, array ($infoActivity->instance, $idUser, $infoActivity->instance, $idUser) );

    return $rs;
}

/**
 * Return the lastest activity's feedback.
 *
 * @param int $idcontext activity's context ID.
 * @param int $iditem item's ID.
 * @return stdClass of comments.
 */
function report_tuteur_getLastComment($idcontext, $iditem) {
    global $DB;

    $sql = "SELECT content, timecreated
              FROM {comments}
             WHERE timecreated = (SELECT max(timecreated) FROM {comments} WHERE contextid = ? and itemid=?)
               AND contextid = ? and itemid=?";

    $rs = $DB->get_record_sql($sql, array ($idcontext, $iditem, $idcontext, $iditem) );

    return $rs;
}
