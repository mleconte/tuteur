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
 * Help tutor's action by colorizing new student's response.
 * This is a clone of completion report, in which some cell are color orange or
 * green.
 * <ul>
 * <li>No color : activity is not pass
 * <li>Orange :student has submit something
 * <li>Green :tutor has grade or make a feedback on the last submit.
 * </ul>
 * Only 4 activities are supervise (assign, quiz, journal, lesson).
 * They must have completion enable.
 *
 * It's possible to :
 * -hide some section
 * -show only one group
 *
 * So, when color is orange tutor has something to do : grade or make a
 * feedback,
 * and he can access the student's response through a link (clic on the cell).
 *
 *
 * @package report_tuteur
 * @copyright 2016 Pole de Ressource Numerique, Universite du Mans
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname ( __FILE__ ) . '/../../config.php');
require_once(dirname ( __FILE__ ) . '/tuteurlib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once('coursegroup.php');
const REPORT_TUTEUR_NOT_SECTION = - 1;
const REPORT_TUTEUR_SHOW_SECTION = 'show';
const REPORT_TUTEUR_HIDE_SECTION = 'hide';
const REPORT_TUTEUR_NOT_CHECK = '#FF9B37';
const REPORT_TUTEUR_CHECK = '#AEFFAE';

// Get course.
$id = required_param ( 'course', PARAM_INT );
$course = $DB->get_record ( 'course', array ( 'id' => $id ) );
if (! $course) {
    print_error ( 'invalidcourseid' );
}
$context = context_course::instance ( $course->id );

// Setup page.
$PAGE->set_url ( '/report/tuteur/index.php', array ( 'course' => $id ) );
$PAGE->set_pagelayout ( 'report' );

$returnurl = new moodle_url ( '/course/view.php', array ( 'id' => $id ) );

// Check permissions.
require_login ( $course );
$coursecontext = context_course::instance ( $course->id );
require_capability ( 'report/tuteur:view', $coursecontext );

$reportsurl = $CFG->wwwroot . '/course/report.php?id=' . $course->id;
$completion = new completion_info ( $course );
$activities = $completion->get_activities ();

// Filter only activity we need.
$nbActivityBeforeFilter = count ( $activities );
$activities = report_tuteur_filterActivities($activities);

$where = array ();
$where_params = array ();

// Get group mode
$group = groups_get_course_group($course, true ); // Supposed to verify group.
if ($group === 0 && $course->groupmode == SEPARATEGROUPS) {
    require_capability ( 'moodle/site:accessallgroups', $context );
}

// Get user match count.
$total = $completion->get_num_tracked_users ( implode ( ' AND ', $where ), $where_params, $group );

// Total user count.
$grandtotal = $completion->get_num_tracked_users ( '', array (), $group );

// Get user data.
$progress = array ();

if ($total) {
    // List student order by lastname.
    $progress = $completion->get_progress_all(implode(' AND ', $where ),
                            $where_params, $group, 'u.lastname ASC', 0, 0, $context);
}

// Finish setting up page.
$PAGE->set_title ( $course->shortname . ': ' . get_string ( 'tuteur:view', 'report_tuteur' ) );
$PAGE->set_heading ( $course->fullname );
echo $OUTPUT->header ();
$PAGE->requires->js ( '/report/tuteur/textrotate.js' );
$PAGE->requires->js_function_call ( 'textrotate_init', null, true );

// Activities - sum nb item per section
$zr_activity = REPORT_TUTEUR_NOT_SECTION;
$nbActivityPerSection = array ();
$counter = 0;
foreach ( $activities as $activity ) {
    if ($zr_activity == REPORT_TUTEUR_NOT_SECTION) {
        $counter = 0;
        $zr_activity = $activity->sectionnum;
    }
    if ($zr_activity == $activity->sectionnum) {
        $counter ++;
    } else {
        $nbActivityPerSection [$zr_activity] = $counter;
        $zr_activity = $activity->sectionnum;
        $counter = 1;
    }
}
$nbActivityPerSection [$zr_activity] = $counter;

// Get list student for grade assignment
$listStudent = report_tuteur_StudentList ( $course );

// Define javascript function
print ("<script language='javascript'>\nfunction showHide(id){ \n") ;
print ("    var tableau = document.getElementsByTagName('td');\n") ;
print ("    var i;\n") ;
print ("    for(i = 0 ;i< tableau.length; i++) {\n") ;
print ("      if (tableau.item(i).getAttribute('name') == id) {\n") ;
print ("        if (tableau.item(i).style.display == 'none') {\n") ;
print ("            tableau.item(i).style.display = 'table-cell';\n") ;
print ("        } else { \n");
print ("            tableau.item(i).style.display = 'none';\n") ;
print ("      }\n") ;
print ("    } \n") ;
print ("  }\n") ;
print ("}\n</script>\n") ;

// Handle groups (if enabled)
groups_print_course_menu ( $course, $CFG->wwwroot . '/report/tuteur/?course=' . $course->id );

if ($nbActivityBeforeFilter == 0) {
    echo $OUTPUT->container ( get_string ( 'err_noactivities', 'completion' ), 'errorbox errorboxcontent' );
    echo $OUTPUT->footer ();
    exit ();
}
$paramGroup = optional_param ( 'chxGroupe', null, PARAM_TEXT );
if (count ( $activities ) == 0 && $paramGroup == null) { //no activity and no filter
    echo $OUTPUT->container ( get_string ( 'zero-activity', 'report_tuteur'), 'errorbox errorboxcontent' );
    echo $OUTPUT->footer ();
    exit ();
}

// If no users in this course what-so-ever
if (! $grandtotal) {
    echo $OUTPUT->container ( get_string ( 'err_nousers', 'completion' ), 'errorbox errorboxcontent' );
    echo $OUTPUT->footer ();
    exit ();
}

print '<br class="clearer"/>';
if (! $total) {
    echo $OUTPUT->heading ( get_string ( 'nothingtodisplay' ) );
    echo $OUTPUT->footer ();
    exit ();
}
$handleGroup = new report_tuteur_coursegroup ( $course->id );

// choose activity to color
print ("<FORM action='#' method='POST'>") ;
$filter = array ();

$pluginman = core_plugin_manager::instance();
$journalinfo = $pluginman->get_plugin_info("journal");

$txt_assign = get_string ( 'modulename', 'assign' );
$txt_quiz = get_string ( 'modulename', 'quiz' );
if (!is_null($journalinfo)) {
    $txt_journal = get_string ( 'modulename', 'journal' );
}
$txt_lesson = get_string ( 'modulename', 'lesson' );

//test dialoguegrade module present
$dialoguegradeExist = $DB->record_exists('modules', array('name'=>'dialoguegrade'));
if ($dialoguegradeExist) {
    $txt_dlgrade = get_string ( 'modulename', 'dialoguegrade' );
}

if ($paramGroup == null) {
    print ("<INPUT type='checkbox' name='devoir' value='assign' checked='checked'> " . $txt_assign . "&nbsp;&nbsp;
        <INPUT type='checkbox' name='test' value='quiz' checked='checked'> " . $txt_quiz . "&nbsp;&nbsp;");
    if (!is_null($journalinfo)) {
        print ("<INPUT type='checkbox' name='journal' value='journal' checked='checked'> " . $txt_journal . "&nbsp;&nbsp;");
    }    
    print ("<INPUT type='checkbox' name='lesson' value='lesson' checked='checked'> " . $txt_lesson . "&nbsp;&nbsp;");
    if ($dialoguegradeExist) {
        print ("<INPUT type='checkbox' name='dlgrade' value='dialoguegrade' checked='checked'> " . $txt_dlgrade . "&nbsp;&nbsp;
    ") ;
        $filter ['dialoguegrade'] = 1;
    }

    $filter ['assign'] = 1;
    $filter ['journal'] = 1;
    $filter ['lesson'] = 1;
    $filter ['quiz'] = 1;
    $filter ['group'] = 0;

    // add group selector
    print ($handleGroup->rendererSelectGroup ( $filter ['group'] )) ;
} else {
    print ("<INPUT type='checkbox' name='devoir' value='assign'") ;
    if (optional_param ( 'devoir', null, PARAM_TEXT ) != null) {
        $filter ['assign'] = 1;
        print (" checked='checked'") ;
    }
    print ("> " . $txt_assign . "&nbsp;&nbsp;") ;

    print ("<INPUT type='checkbox' name='test' value='quiz'") ;
    if (optional_param ( 'test', null, PARAM_TEXT ) != null) {
        $filter ['quiz'] = 1;
        print (" checked='checked'") ;
    }
    print (">" . $txt_quiz . "&nbsp;&nbsp;") ;

    if (!is_null($journalinfo)) {
        print ("<INPUT type='checkbox' name='journal' value='journal'") ;
        if (optional_param ( 'journal', null, PARAM_TEXT ) != null) {
            $filter ['journal'] = 1;
            print (" checked='checked'") ;
        }
        print (">" . $txt_journal . "&nbsp;&nbsp;") ;
    }

    print ("<INPUT type='checkbox' id='lesson' name='lesson' value='lesson'") ;
    if (optional_param ( 'lesson', null, PARAM_TEXT ) != null) {
        $filter ['lesson'] = 1;
        print (" checked='checked'") ;
    }
    print (">" . $txt_lesson . "&nbsp;&nbsp;") ;

    if ($dialoguegradeExist) {
        print ("<INPUT type='checkbox' name='dlgrade' value='dialoguegrade'") ;
        if (optional_param ( 'dlgrade', null, PARAM_TEXT ) != null) {
            $filter ['dialoguegrade'] = 1;
            print (" checked='checked'") ;
        }
        print (">" . $txt_dlgrade . "&nbsp;&nbsp;") ;
    }
    
    if ($paramGroup != null) {
        $filter ['group'] = intval ( $paramGroup );
    }
    print ($handleGroup->rendererSelectGroup ( $filter ['group'] )) ;
}

print ("<INPUT TYPE='submit' NAME='btn' VALUE='" . get_string ( 'filter', 'report_tuteur' ) . "'> &nbsp;") ;

print ($OUTPUT->help_icon ( "selecteur", "report_tuteur", "" )) ;
print ("&nbsp;&nbsp;" . get_string ( 'symbol', 'report_tuteur' ) . "&nbsp;&nbsp;<img src='" . $OUTPUT->image_url ( 'i/' . REPORT_TUTEUR_SHOW_SECTION, 'moodle' ) . "' >") ;
print ($OUTPUT->help_icon ( "eye", "report_tuteur", "" )) ;
print ("</FORM>") ;

if (count ( $activities ) == 0) { //no more activity after filter
    echo $OUTPUT->container ( get_string ( 'no-more-activity', 'report_tuteur'), 'errorbox errorboxcontent' );
    echo $OUTPUT->footer ();
    exit ();
}

print '<div id="completion-progress-wrapper" class="no-overflow">';

// Define table
print '<table  class="generaltable flexible boxaligncenter report-tuteur" id="completion-progress" style="text-align:left">';
print ('<thead><tr style="vertical-align:top">') ;

// First line - Table Header
print ("<td></td><td></td>") ;
for($i = 0; $i <= $zr_activity; $i ++) {
    if (isset ( $nbActivityPerSection [$i] )) {
        print ("<td name='Section" . $i . "' style='display:none;'><a href=\"javascript:showHide('Section" . $i . "');\">") ;
        print ("<img src='" . $OUTPUT->image_url ( 'i/' . REPORT_TUTEUR_HIDE_SECTION, 'moodle' ) . "' ></a></td>") ;
        print ("<td name='Section" . $i . "' style='display:table-cell;' colspan='" . $nbActivityPerSection [$i] . "'>") ;
        print ("<a href=\"javascript:showHide('Section" . $i . "');\">" . get_string ( 'section' ) . $i . "&nbsp;<img src='" . $OUTPUT->image_url ( 'i/' . REPORT_TUTEUR_SHOW_SECTION, 'moodle' ) . "' ></a></td>") ;
    }
}
print ("</tr>") ;

// Student - LastName / FirstName
print '<th scope="col" class="completion-sortchoice">';
print get_string ( 'lastname' ) . ' / ' . get_string ( 'firstname' );

print '</th><th style="font-size:0.75em;vertical-align:bottom;min-width:90px">' . get_string ( 'student-report', 'report_tuteur' ) . '</th>';

// Activities
$formattedactivities = array ();
$zr_activity = REPORT_TUTEUR_NOT_SECTION;
foreach ( $activities as $activity ) {
    $datepassed = $activity->completionexpected && $activity->completionexpected <= time ();
    $datepassedclass = $datepassed ? 'completion-expired' : '';

    if ($activity->completionexpected) {
        $datetext = userdate ( $activity->completionexpected, get_string ( 'strftimedate', 'langconfig' ) );
    } else {
        $datetext = '';
    }

    if ($zr_activity != $activity->sectionnum) {
        $zr_activity = $activity->sectionnum;
        // hiding col
        print ("<td name='Section" . $activity->sectionnum . "' style='display:none;'></td>") ;
    }
    // Some names (labels) come URL-encoded and can be very long, so shorten
    // them
    $displayname = format_string ( $activity->name, true, array ('context' => $activity->context) );

    $shortenedname = shorten_text ( $displayname );
    print '<td scope="col" class="' . $datepassedclass . '" name="Section' . $activity->sectionnum . '" style="display:table-cell;">' . '<a href="' . $CFG->wwwroot . '/mod/' . $activity->modname . '/view.php?id=' . $activity->id . '" title="' . s ( $displayname ) . '">' . '<img src="' . $OUTPUT->image_url ( 'icon', $activity->modname ) . '" alt="' . s ( get_string ( 'modulename', $activity->modname ) ) . '" />' . ' <span class="completion-activityname">' . $shortenedname . '</span></a>';
    if ($activity->completionexpected) {
        print '<div class="completion-expected"><span>' . $datetext . '</span></div>';
    }
    print '</td>';
    $formattedactivities [$activity->id] = ( object ) array (
            'datepassedclass' => $datepassedclass,
            'displayname' => $displayname 
    );
}
print '</tr></thead><tbody>';

// loop on student's list
foreach ( $progress as $user ) {
    // Filter group members
    if (! $handleGroup->isMember ( $filter ['group'], $user->id )) {
        continue;
    }
    // Student's name
    print '<tr><th scope="row"><a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '&amp;course=' . $course->id . '">' . $user->lastname . ' ' . $user->firstname . '</a></th>';

    $reportLink = $CFG->wwwroot . '/report/outline/user.php?id=' . $user->id . '&course=' . $course->id;
    echo '<td>&nbsp;<a href="' . $reportLink . '&mode=complete"> <img src="' . $OUTPUT->image_url ( 'i/report', 'moodle' ) . '" ></a>&nbsp; /&nbsp; <a href="' . $reportLink . '&mode=outline"> <img src="' . $OUTPUT->image_url ( 'i/news', 'moodle' ) . '" ></td>';

    // completion on activity
    $zr_activity = REPORT_TUTEUR_NOT_SECTION;
    foreach ( $activities as $activity ) {
        // Hiding col
        if ($zr_activity != $activity->sectionnum) {
            $zr_activity = $activity->sectionnum;
            print ("<td name='Section" . $activity->sectionnum . "' style = \"background-color:#505050!important;display:none;\"></td>") ;
        }

        // Get progress information and state
        if (array_key_exists ( $activity->id, $user->progress )) {
            $thisprogress = $user->progress [$activity->id];
            $state = $thisprogress->completionstate;
            $date = userdate ( $thisprogress->timemodified );
        } else {
            $state = COMPLETION_INCOMPLETE;
            $date = '';
        }

        $cellStyle = '';
        $cellLink = '#';

        // handle cell color
        if (isset ( $filter [$activity->modname] ) && $filter [$activity->modname] == 1) {
            $activityState = report_tuteur_getActivityState ( $user->id, $activity->id, $activity->modname );
            if ($activityState == 1) {
                $cellStyle = ' style = "background-color:' . REPORT_TUTEUR_NOT_CHECK . '!important;display:table-cell;"';
            } elseif ($activityState == 2) {
                $cellStyle = ' style = "background-color:' . REPORT_TUTEUR_CHECK . '!important;display:table-cell;"';
            }

            if ($cellStyle != '') {
                if ($activity->modname == 'assign') {
                    $lineNumber = report_tuteur_getNumRow ( $user->id, $listStudent );
                    if ($lineNumber != - 1) {
                        $cellLink = $CFG->wwwroot . '/mod/assign/view.php?id=' . $activity->id . '&rownum=' . $lineNumber . '&action=grade&userid=' . $user->id;
                    }
                }

                if ($activity->modname == 'quiz') {
                    $numAttempt = report_tuteur_getNumAttempt ( $user->id, $activity->id );
                    $cellLink = $CFG->wwwroot . '/mod/quiz/review.php?attempt=' . $numAttempt;
                }

                if ($activity->modname == 'journal') {
                    $cellLink = $CFG->wwwroot . '/mod/journal/report.php?id=' . $activity->id;
                }

                if ($activity->modname == 'lesson') {
                    $numAttempt = report_tuteur_getNumAttemptLesson ( $user->id, $activity->id );
                    $cellLink = $CFG->wwwroot . '/mod/lesson/essay.php?id=' . $activity->id . '&mode=grade&attemptid=' . $numAttempt . '&sesskey=' . $USER->sesskey;
                }
                if ($activity->modname == 'dialoguegrade') {
                	$cellLink = $CFG->wwwroot . '/mod/dialoguegrade/grade.php?id=' . $activity->id . '&itemid=-1&itemnumber=0&gradeid=-1&userid=' . $user->id;
                }
            }
        }

        // Work out how it corresponds to an icon
        switch ($state) {
            case COMPLETION_INCOMPLETE :
                $completiontype = 'n';
                break;
            case COMPLETION_COMPLETE :
                $completiontype = 'y';
                break;
            case COMPLETION_COMPLETE_PASS :
                $completiontype = 'pass';
                break;
            case COMPLETION_COMPLETE_FAIL :
                $completiontype = 'fail';
                break;
        }

        $completionicon = 'completion-' . ($activity->completion == COMPLETION_TRACKING_AUTOMATIC ? 'auto' : 'manual') . '-' . $completiontype;

        $describe = get_string ( 'completion-' . $completiontype, 'completion' );
        $a = new StdClass ();
        $a->state = $describe;
        $a->date = $date;
        $a->user = fullname ( $user );
        $a->activity = $formattedactivities [$activity->id]->displayname;
        $fulldescribe = get_string ( 'progress-title', 'completion', $a );

        if ($cellLink != "#") {
            print '<td name="Section' . $activity->sectionnum . '" ' . $cellStyle . '>' . '<a href="' . $cellLink . '"><img src="' . $OUTPUT->image_url ( 'i/' . $completionicon, 'moodle' ) . '" alt="' . s ( $describe ) . '" title="' . s ( $fulldescribe ) . '" /></a>' . '</td>';
        } else {
            print '<td name="Section' . $activity->sectionnum . '" ' . $cellStyle . '>' . '<img src="' . $OUTPUT->image_url ( 'i/' . $completionicon, 'moodle' ) . '" alt="' . s ( $describe ) . '" title="' . s ( $fulldescribe ) . '" />' . '</td>';
        }
    }

    print '</tr>';
}

print ("</tbody></table></div>") ;

echo $OUTPUT->footer ();
