<?php
/**
 * Plugin Moodle  : Rapport pour les tuteurs EAD.
 * Presente l'ensemble des tests notes attendus d'un cours.
 * Modification du rapport d'achevement.
 * @package   report_tuteur
 * @copyright 2016 Pole de Ressource Numerique, Universite du Maine
 * @license   sans objet
 */

require_once (dirname ( __FILE__ ) . '/../../config.php');
require_once (dirname ( __FILE__ ) . '/tuteurlib.php');
require_once ($CFG->libdir . '/completionlib.php');
require_once ('coursegroup.php');
const NUM_HORS_SECTION = - 1;
const PLIER = 'show';
const DEPLIER = 'hide';
const NON_NOTE = '#FF9B37';
const CORRIGE = '#AEFFAE';

// Obtention du cours
$id = required_param ( 'course', PARAM_INT );
$course = $DB->get_record ( 'course', array ('id' => $id) );
if (! $course) {
	print_error ( 'invalidcourseid' );
}
$context = context_course::instance ( $course->id );

// Setup page.
$PAGE->set_url ( '/report/tuteur/index.php', array ('course' => $id) );
$PAGE->set_pagelayout ( 'report' ); //affiche les blocs

$returnurl = new moodle_url ( '/course/view.php', array ('id' => $id) );

// Check permissions.
require_login ( $course );
$coursecontext = context_course::instance ( $course->id );
require_capability ( 'report/tuteur:view', $coursecontext );

$reportsurl = $CFG->wwwroot . '/course/report.php?id=' . $course->id;
$completion = new completion_info ( $course );
$activities = $completion->get_activities ();
// Toujours acces a tous
$where = array ();
$where_params = array ();

// Get group mode
$group = groups_get_course_group ( $course, true ); // Supposed to verify group
if ($group === 0 && $course->groupmode == SEPARATEGROUPS) {
	require_capability ( 'moodle/site:accessallgroups', $context );
}

// Get user match count
$total = $completion->get_num_tracked_users ( implode ( ' AND ', $where ), $where_params, $group );

// Total user count
$grandtotal = $completion->get_num_tracked_users ( '', array (), $group );

// Get user data
$progress = array ();

if ($total) {
	// liste des etudiants trié par leur nom
	$progress = $completion->get_progress_all ( implode ( ' AND ', $where ), $where_params, $group, 'u.lastname ASC', 0, 0, $context );
}

// Finish setting up page.
$PAGE->set_title ( $course->shortname . ': ' . get_string ( 'tuteur:view', 'report_tuteur' ) );
$PAGE->set_heading ( $course->fullname );
echo $OUTPUT->header ();
$PAGE->requires->js ( '/report/tuteur/textrotate.js' );
$PAGE->requires->js_function_call ( 'textrotate_init', null, true );

// Activities - calcul nb item par section
$zr_activity = NUM_HORS_SECTION;
$nbActiviteParSection = array ();
$compteur = 0;
foreach ( $activities as $activity ) {
	if ($zr_activity == NUM_HORS_SECTION) {
		$compteur = 0;
		$zr_activity = $activity->sectionnum;
	}
	if ($zr_activity == $activity->sectionnum) {
		$compteur ++;
	} else {
		$nbActiviteParSection [$zr_activity] = $compteur;
		$zr_activity = $activity->sectionnum;
		$compteur = 1;
	}
}
$nbActiviteParSection [$zr_activity] = $compteur;

// Obtention de la liste d'élève pour la notation de l'assign
$listeElev = tuteur_listerEleves ($course);

// Definition style table
print ("<style>") ;
print ("th,td { border-top:none!important; border-right:none!important;} ");
print ("</style>") ;

// Definition fonction javascript
print ("<script language='javascript'>\nfunction afficherCacher(id){ \n") ;
print ("	var tableau = document.getElementsByTagName('td');\n") ;
print (" 	var i;\n") ;
print (" 	for(i = 0 ;i< tableau.length; i++) {\n") ;
print ("	  if (tableau.item(i).getAttribute('name') == id) {\n") ;
print ("		if (tableau.item(i).style.display == 'none') {\n") ;
print ("			tableau.item(i).style.display = 'table-cell';\n") ;
print ("		} else { \n") ;
print ("			tableau.item(i).style.display = 'none';\n") ;
print ("	  }\n") ;
print ("	} \n") ;
print ("  }\n") ;
print ("}\n</script>\n") ;

// Handle groups (if enabled)
groups_print_course_menu ( $course, $CFG->wwwroot . '/report/tuteur/?course=' . $course->id );

// attention phrase d'un autre module !!
if (count ( $activities ) == 0) {
	echo $OUTPUT->container ( get_string ( 'err_noactivities', 'completion' ), 'errorbox errorboxcontent' );
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
//
if (! $total) {
	echo $OUTPUT->heading ( get_string ( 'nothingtodisplay' ) );
	echo $OUTPUT->footer ();
	exit ();
}

$gereGroupe = new coursegroup($course->id);

// formulaire choix colorisation
print ("<FORM action='#' method='POST'>") ;
$filtre = array ();
$paramGroupe = optional_param('chxGroupe', null, PARAM_TEXT);
if ($paramGroupe == null) {
	print ("<INPUT type='checkbox' name='devoir' value='assign' checked='checked'> Devoir&nbsp;&nbsp;
    	<INPUT type='checkbox' name='test' value='quiz'> Test&nbsp;&nbsp;
    	<INPUT type='checkbox' name='journal' value='journal' checked='checked'> Journal&nbsp;&nbsp;
    	<INPUT type='checkbox' name='lesson' value='lesson' checked='checked'> Le&ccedil;on&nbsp;&nbsp;
	") ;
	
	$filtre ['assign'] = 1;
	$filtre ['journal'] = 1;
	$filtre ['lesson'] = 1;
	$filtre ['quiz'] = 0;
	$filtre ['groupe'] = 0;
	
	//ajouter la combo chxgrp
	print ($gereGroupe->rendererSelectGroup($filtre ['groupe']));
} else {
	print ("<INPUT type='checkbox' name='devoir' value='assign'") ;
	if (optional_param('devoir', null, PARAM_TEXT) != null ) {
		$filtre ['assign'] = 1;
		print (" checked='checked'") ;
	}
	print ("> Devoir&nbsp;&nbsp;") ;
	
	print ("<INPUT type='checkbox' name='test' value='quiz'") ;
	if (optional_param('test', null, PARAM_TEXT) != null) {
		$filtre ['quiz'] = 1;
		print (" checked='checked'") ;
	}
	print ("> Test&nbsp;&nbsp;") ;
	
	print ("<INPUT type='checkbox' name='journal' value='journal'") ;
	if (optional_param('journal', null, PARAM_TEXT) != null) {
		$filtre ['journal'] = 1;
		print (" checked='checked'") ;
	}
	print ("> Journal&nbsp;&nbsp;") ;
	
	print ("<INPUT type='checkbox' id='lesson' name='lesson' value='lesson'") ;
	if (optional_param('lesson', null, PARAM_TEXT) != null) {
		$filtre ['lesson'] = 1;
		print (" checked='checked'") ;
	}
	print ("> Le&ccedil;on&nbsp;&nbsp;") ;
	
	if ($paramGroupe != null) {
		$filtre ['groupe'] = intval($paramGroupe);
	}
	print ($gereGroupe->rendererSelectGroup($filtre ['groupe']));
}

print ("<INPUT TYPE='submit' NAME='btn' VALUE='Filtrer'> &nbsp;") ;

print ($OUTPUT->help_icon ( "selecteur", "report_tuteur", "" )) ;
print ("&nbsp;&nbsp;Symbole <img src='" . $OUTPUT->pix_url ( 'i/' . PLIER ) . "' >") ;
print ($OUTPUT->help_icon ( "oeil", "report_tuteur", "" )) ;
print ("</FORM>") ;

print '<div id="completion-progress-wrapper" class="no-overflow">';

// Definition de la table
print '<table  class="generaltable flexible boxaligncenter" id="completion-progress" style="text-align:left">';

// Premiere ligne du tableau
print ('<thead><tr style="vertical-align:top">') ;

// Affichage des sections
print ("<td></td><td></td>") ;
for($i = 0; $i <= $zr_activity; $i ++) {
	if (isset ( $nbActiviteParSection [$i] )) {
		print ("<td name='Section". $i ."' style='display:none;'><a href=\"javascript:afficherCacher('Section" . $i . "');\"><img src='" . $OUTPUT->pix_url ( 'i/' . DEPLIER ) . "' ></a></td>") ;
		print ("<td name='Section". $i ."' style='display:table-cell;' colspan='" . $nbActiviteParSection [$i] . "'><a href=\"javascript:afficherCacher('Section" . $i . "');\"> Section " . $i . "&nbsp;<img src='" . $OUTPUT->pix_url ( 'i/' . PLIER ) . "' ></a></td>") ;
	}
}
print ("</tr>") ;

// Entetes des colonnes prénom/nom , rapport complet/resume
print '<th scope="col" class="completion-sortchoice">';
print get_string ( 'lastname' ) . ' / ' . get_string ( 'firstname' );
print '</th><th style="font-size:0.75em;vertical-align:bottom;min-width:90px">Rapport Etudiant</th>';

// Activites
$formattedactivities = array ();
$zr_activity = NUM_HORS_SECTION;
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
		//colonne masquee
		print ("<td name='Section". $activity->sectionnum ."' style='display:none;'></td>") ;
	}
	// Some names (labels) come URL-encoded and can be very long, so shorten
	// them
	$displayname = format_string ( $activity->name, true, array (
			'context' => $activity->context 
	) );
	
	$shortenedname = shorten_text ( $displayname );
	print '<td scope="col" class="' . $datepassedclass . '" name="Section'. $activity->sectionnum .'" style="display:table-cell;">' . '<a href="' . $CFG->wwwroot . '/mod/' . $activity->modname . '/view.php?id=' . $activity->id . '" title="' . s ( $displayname ) . '">' . '<img src="' . $OUTPUT->pix_url ( 'icon', $activity->modname ) . '" alt="' . s ( get_string ( 'modulename', $activity->modname ) ) . '" /> <span class="completion-activityname">' . $shortenedname . '</span></a>';
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

// Pour chaque etudiant
foreach ( $progress as $user ) {
	//Filtre les membres du groupe
	if (! $gereGroupe->isMember($filtre ['groupe'], $user->id)) continue;
	
	// Nom etudiant
	print '<tr><th scope="row"><a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '&amp;course=' . $course->id . '">'
			 . $user->lastname . ' '. $user->firstname. '</a></th>';
	$lienRapport = $CFG->wwwroot . '/report/outline/user.php?id=' . $user->id . '&course=' . $course->id;
	echo '<td>&nbsp;<a href="' . $lienRapport . '&mode=complete"> <img src="' . $OUTPUT->pix_url ( 'i/report' ) . '" ></a>&nbsp; /&nbsp; <a href="' . $lienRapport . '&mode=outline"> <img src="' . $OUTPUT->pix_url ( 'i/news' ) . '" ></td>';
	
	// Suivi achevement pour chaque activite
	$zr_activity = NUM_HORS_SECTION;
	foreach ( $activities as $activity ) {
		// Colonne replie
		if ($zr_activity != $activity->sectionnum) {
			$zr_activity = $activity->sectionnum;
			//colonne masquee
			print ("<td name='Section". $activity->sectionnum ."' style = \"background-color:#505050!important;display:none;\"></td>") ;
		}
		
		// Get progress information and state
		if (array_key_exists ( $activity->id, $user->progress )) { // complet
			$thisprogress = $user->progress [$activity->id];
			$state = $thisprogress->completionstate;
			$date = userdate ( $thisprogress->timemodified );
		} else {
			$state = COMPLETION_INCOMPLETE;
			$date = '';
		}
		
		$leStyle = '';
		$lienNote = '#';
		
		// traitement colorisation
		if (isset ( $filtre [$activity->modname] ) && $filtre [$activity->modname] == 1) {
			$etat = tuteur_getEtat ( $user->id, $activity->id, $activity->modname );
			if ($etat == 1) {
				$leStyle = ' style = "background-color:' . NON_NOTE . '!important;display:table-cell;"';
			} elseif ($etat == 2) {
				$leStyle = ' style = "background-color:' . CORRIGE . '!important;display:table-cell;"';
			}
			
			if ($leStyle != '' && $activity->modname == 'assign') {
				$numeroLigne = tuteur_getNumRow ( $user->id, $listeElev );
				if ($numeroLigne != - 1) {
					$lienNote = $CFG->wwwroot . '/mod/assign/view.php?id=' . $activity->id . '&rownum=' . $numeroLigne . '&action=grade&userid=' . $user->id;
				}
			}
			
			// pour mod='quiz' lien du type mod/quiz/review.php?attempt=6695
			// reste a trouver num_attempt
			if ($leStyle != '' && $activity->modname == 'quiz') {
				$numAttempt = tuteur_getNumAttempt ( $user->id, $activity->id );
				$lienNote = $CFG->wwwroot . '/mod/quiz/review.php?attempt=' . $numAttempt;
			}
			if ($leStyle != '' && $activity->modname == 'journal') {
				$lienNote = $CFG->wwwroot . '/mod/journal/report.php?id=' . $activity->id;
			}
			
			// si couleur et mod='lesson' lien vers
			// mod/lesson/essay.php ? Id = identifiant activité & mode = grade &
			// attemptid = plus petit attempt non renseigné & sesskey =
			// $USER->sesskey
			if ($leStyle != '' && $activity->modname == 'lesson') {
				$numAttempt = tuteur_getNumAttemptLesson ( $user->id, $activity->id );
				$lienNote = $CFG->wwwroot . '/mod/lesson/essay.php?id=' . $activity->id . '&mode=grade&attemptid=' . $numAttempt . '&sesskey=' . $USER->sesskey;
			}
		}
		// fin traitement colorisation
		
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
		
		if ($lienNote != "#") {
			
			print '<td name="Section'. $activity->sectionnum .'" ' . $leStyle . '><a href="' . $lienNote . '"><img src="' . $OUTPUT->pix_url ( 'i/' . $completionicon ) . '" alt="' . s ( $describe ) . '" title="' . s ( $fulldescribe ) . '" /></a></td>';
		} else {
			print '<td name="Section'. $activity->sectionnum .'" ' . $leStyle . '><img src="' . $OUTPUT->pix_url ( 'i/' . $completionicon ) . '" alt="' . s ( $describe ) . '" title="' . s ( $fulldescribe ) . '" /></td>';
		}
	} // fin parcours activites d'un eleve
	
	print '</tr>';
} // fin traitement 1 etudiant

print ("</tbody></table></div>") ;

echo $OUTPUT->footer ();
  