<?php
/**
 * Fonctions utiles pour l'enrichissement des données du rapport.
 * 
 * @package   report_tuteur
 * @copyright 2016 Pole de Ressource Numerique, Universite du Maine
 * @license   sans objet
 */

defined ( 'MOODLE_INTERNAL' ) || die ();

/**
 * Fournit le dernier identifiant de tentative du quizz ou -1 si aucune tantative n'est présente.
 * @param $idActivity identifiant technique de l'activite au sein du cours.
 * @param $idUser identifiant de l'etudiant.
 * @return le numero le plus récent de tentative du QCM pour un élève.
 *         Si aucune tentative n'existe on retourne -1.
 */
function tuteur_getNumAttempt($idUser, $idActivity) {
	global $DB;
	$infoActivite = $DB->get_record_select ( 'course_modules', 'id = ?', array ($idActivity) );
	$rs = $DB->get_field('quiz_attempts', 'MAX(id)', array('quiz' => $infoActivite->instance, 'userid' => $idUser));
	
	if (isset ($rs)) {
		return $rs;
	}
	return - 1;
}

/**
 * Détermine l'etat de l'activité.
 *
 * @param $idActivity identifiant
 *        	technique de l'activite au sein du cours.
 * @param $idUser identifiant
 *        	de l'etudiant
 * @param $modname nom
 *        	du type de module.
 * @return 2 si le dernier commentaire est plus recent que la derniere
 *         soumission de devoir
 *         1 si la derniere soumission est plus récente que le dernier
 *         commentaire.
 *         0 si aucune soumission.
 */
function tuteur_getEtat($idUser, $idActivity, $modname) {
	try {
		if ($modname == "quiz") {
			return tuteur_etatQCM ( $idUser, $idActivity);
		}
		if ($modname == "journal") {
			return tuteur_etatJournal ( $idUser, $idActivity);
		}
		if ($modname == "lesson") {
			return tuteur_etatLesson ( $idUser, $idActivity );
		}
		if ($modname == "assign") {
			return tuteur_etatAssign ( $idUser, $idActivity );
		}
	} catch ( Exception $err ) {
		return 0;
	}
}


/**
 * Fournit l'ensemble des eleves d'un cours.
 * Cette liste correspond a la liste utilisé par défaut lors de la notation d'un
 * assign.
 *
 * @param $course l'instance
 *        	du cours d'appartenance des eleves.
 * @return un tableau portant la liste des eleves inscript
 *         avec les informations suivante : id,firstname,lastname,
 *         lastlogin,timemodified (date d inscription au cours)
 *         ,timecreated (date creation de l'enregistrement eleve).
 */
function tuteur_listerEleves($course) {
	global $DB;
	$sql = "SELECT u.id,u.firstname,u.lastname,u.lastlogin, ra.timemodified , u.timecreated
        FROM {user} u
        JOIN {role_assignments} ra ON ra.userid = u.id
        JOIN {role} r ON ra.roleid = r.id
        JOIN {context} con ON ra.contextid = con.id
        JOIN {course} c ON c.id = con.instanceid AND con.contextlevel = 50
        WHERE r.shortname = 'student'
          AND c.id = ? ORDER BY u.id";
	
	$userlist = $DB->get_recordset_sql ( $sql, array ($course->id) );
	$data = array ();
	foreach ( $userlist as $user ) {
		$data [] = $user;
	}
	return $data;
}
/**
 * Transforme l'identifiant de l'eleve en numero de ligne.
 *
 * @param $idElev identifiant
 *        	technique de l'eleve.
 * @param $tableauElv l'ensemble
 *        	des eleves du cours.
 * @return le numero de ligne ou se trouve l'eleve recherche ou -1
 *         si l'eleve n'est pas trouve.
 */
function tuteur_getNumRow($idElev, $tableauElv) {
	$indice = 0;
	while ( $indice < count ( $tableauElv ) && $tableauElv [$indice]->id != $idElev ) {
		$indice ++;
	}
	
	if ($indice < count ( $tableauElv )) {
		return $indice;
	}
	return - 1;
}

/**
 * Retourne le numero de tentative de lesson le plus
 * petit sans feedback pour un eleve et une lesson
 * donnée.
 * Si toutes les compositions disposent d'un feedback, retourne
 * le numero de tentative le plus eleve.
 * Si pas de lesson ou de tentative retourne 0.
 */
function tuteur_getNumAttemptLesson($idUser, $idActivity) {
	global $DB;
	$infoActivite = $DB->get_record_select ( 'course_modules', 'id = ?', array ( $idActivity ) );

	$sql = "SELECT id
            FROM {lesson_pages}
           WHERE lessonid = ? AND qtype = 10";
	$rs = $DB->get_recordset_sql ( $sql, array ($infoActivite->instance) );

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
	$lesCompos = array ();
	foreach ( $data as $page ) {
		$compo = $DB->get_record_select ( 'lesson_attempts', 'lessonid = ? and pageid = ? and userid = ?', array (
				$infoActivite->instance, $page->id, $idUser ) );
		if (isset ( $compo->useranswer )) {
			$lesCompos [] = $compo;
			$nb ++;
		}
	}
	if ($nb == 0) {
		return 0;
	}
	$ret = 0;
	$numDefaut = 0;
	foreach ( $lesCompos as $composition ) {
		$essayinfo = unserialize ( $composition->useranswer );
		if (! isset ( $essayinfo->response )) {
			$ret = 1;
		} else {
			if ($essayinfo->response == "") {

				if ($ret == 0 || $ret > $composition->id) {
					$ret = $composition->id;
				}
			}
			if ($numDefaut < $composition->id) {
				$numDefaut = $composition->id;
			}
		}
	}
	if ($ret == 0) {
		$ret = $numDefaut;
	}
	return $ret;
}


// Methodes utilisés en interne
/**
 * @return la date du dernier commentaire propre aux assign.
 * Assign_grades est modifie lors de la saisie de la note ou du feedback.
 */
//tuteur_
function tuteur_getModifAssignGrades($idUser, $idActivity) {
	global $DB;
	$cours_mod = $DB->get_record ( "course_modules", array ('id' => $idActivity) );
	$rs = $DB->get_field('assign_grades', 'MAX(timemodified)', array('assignment' => $cours_mod->instance ,'userid' =>  $idUser) );
	if (isset ($rs)) {
		return $rs;
	}
	return 0;
}


/**
 * @return le code etat de l'assign.
 * 0, 1 ou 2 selon aucune soumission, en attente du tuteur, devoir traite.
 */
function tuteur_etatAssign($idUser, $idActivity) {
	$dateNote = 0;
	$dateComment = 0;
	$dateSoumis = 0;

	$note = tuteur_isNote($idUser, $idActivity);
	if (isset ( $note->timemodified )) {
		$dateNote = $note->timemodified;
	}

	$idcontext = tuteur_getContext ( $idActivity );
	$soumissions = tuteur_getDernierSoumis($idUser, $idActivity);

	if (isset ( $soumissions->id )) {
		$iditem = $soumissions->id;
		$commentaires = tuteur_getDernierComment ( $idcontext, $iditem );
		$dateSoumis = $soumissions->timemodified;

		if (isset ( $commentaires->timecreated )) {
			$dateComment = $commentaires->timecreated;
		}
		// traitement commentaire propre aux assigns
		$autreComment = tuteur_getModifAssignGrades ( $idUser, $idActivity );
		if ($dateComment < $autreComment) {
			$dateComment = $autreComment;
		}
	}

	if ($dateNote == 0 && $dateComment == 0 && $dateSoumis == 0) {
		return 0;
	}
	if ($dateSoumis > $dateNote && $dateSoumis > $dateComment) {
		return 1;
	}
	// autre cas une date existe mais date Soumission n'est pas supérieur
	return 2;
}

/**
 * Fournit l'uniqueId le plus récent, des tentatives du quizz ou -1 si aucune tentative n'est présente.
 * @param $idActivity identifiant technique de l'activite au sein du cours.
 * @param $idUser identifiant de l'etudiant
 * @return l'identifiant de la derniere tentative du QCM.
 */
function tuteur_getUniqueIdAttempt($idUser, $idActivity) {
	global $DB;
	$infoActivite = $DB->get_record_select ('course_modules', 'id = ?', array ($idActivity) );
	$rs = $DB->get_field_sql('SELECT uniqueid from {quiz_attempts} where timefinish = (select max(timefinish ) from {quiz_attempts} where quiz=? and userid=?)',
			array($infoActivite->instance, $idUser));
	if (isset ( $rs )) {
		return $rs;
	}
	return - 1;
}

/**
 * @return le code etat d'un test Lesson, 0 = pas de couleur (non passé ou pas
 *         de composition dans cette lecon)
 *         1 composition non commentée, 2 composition commenté.
 */
function tuteur_etatLesson($idUser, $idActivity) {
	global $DB;
	$infoActivite = $DB->get_record_select ( 'course_modules', 'id = ?', array ($idActivity) );
	
	$sql = "SELECT id
            FROM {lesson_pages}
           WHERE lessonid = ? AND qtype = 10";
	$rs = $DB->get_recordset_sql ( $sql, array ($infoActivite->instance) );
	
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
	$lesCompos = array ();
	foreach ( $data as $page ) {
		$compo = $DB->get_record_select ( 'lesson_attempts', 'lessonid = ? and pageid = ? and userid = ?', array (
				$infoActivite->instance,
				$page->id,
				$idUser 
		) );
		if (isset ( $compo->useranswer )) {
			$lesCompos [] = $compo->useranswer;
			$nb ++;
		}
	}
	if ($nb == 0)
		return 0;
	$ret = 2;
	foreach ( $lesCompos as $composition ) {
		$essayinfo = unserialize ( $composition );
		if (! isset ( $essayinfo->response )) {
			$ret = 1;
		} else {
			if ($essayinfo->response == "")
				$ret = 1;
		}
	}
	return $ret;
}

/**
 * @return le code etat d'un test journal, 0 = non passé
 * 1 test non commenté, 2 test passé et commenté ou verrouillé par carnet de note.
 * Modification pour prendre en compte l'activité journal non noté, on s'appuye
 * alors uniquement sur le dernier utilisateur ayant ecrit dans le journal.
 */
function tuteur_etatJournal($idUser, $idActivity) {
	global $DB;
	
	$infoActivite = $DB->get_record_select ( 'course_modules', 'id = ?', array ( $idActivity ) );
	
	$gradeItem = $DB->get_record_select ( 'grade_items', 'courseid = ? and iteminstance = ? and itemmodule = ?', array (
			$infoActivite->course,
			$infoActivite->instance,
			'journal' ) );
	
	// MODIFICATION 05/10/2016
	if (isset ( $gradeItem->id )) {
		$sql = "SELECT overridden
                FROM {grade_grades}
       	       WHERE itemid = ? and userid = ?";
		$rs = $DB->get_field_sql($sql, array($gradeItem->id, $idUser));
		if ($rs > 0) {
			return 2;
		}
	}

	$entreJournal = $DB->get_record_select ( 'journal_entries', 'journal = ? and userid = ?', array ($infoActivite->instance, $idUser ) );
	if (! isset($entreJournal->modified)) {
		return 0;
	}
	if ($entreJournal->modified > $entreJournal->timemarked) {
		return 1;
	}
	
	return 2;
}

/**
 * @param $idUser identifiant technique de l'eleve.
 * @param $idActivity identifiant technique de l'activite.
 * @return le code etat d'un test QCM, 0 = non passé
 *         1 test non commenté, 2 test passé et commenté.
 */
function tuteur_etatQCM($idUser, $idActivity) {
	global $DB;
	
	$infoActivite = $DB->get_record_select ( 'course_modules', 'id = ?', array ($idActivity) );
	
	$idDerniereTentative = tuteur_getUniqueIdAttempt ( $idUser, $idActivity );
	
	if ($idDerniereTentative == false || $idDerniereTentative == - 1) {
		return 0;
	}
	
	$sql = "SELECT count(st.id)
			FROM {question_attempt_steps} st
			JOIN {question_attempts} qa ON qa.id = st.questionattemptid
			where qa.questionusageid = ?
            and st.state like 'mangr%' 
		    and st.userid != ?";
	$rs = $DB->get_field_sql($sql, array($idDerniereTentative, $idUser));
	if ($rs == 0) {
		return 1;
	}
	
	return 2;
}

/**
 * @param $idActivity l'identifiant technique de l'activité.
 * @return le context associé a une activité.
 */
function tuteur_getContext($idActivity) {
	global $DB;
	$rs = $DB->get_field_sql('SELECT id FROM {context} WHERE instanceid = ? AND contextlevel = 70', array($idActivity));
	return $rs;
}

/**
 * Determine si un eleve possede une note pour une activite (restreinte a assign).
 * Il faut passer par course_modules pour relier l'item a une eventuelle note.
 *
 * @param $idUser identifiant technique de l'eleve.
 * @param $idActivity identifiant technique de l'activite.
 * @return la structure de la note de l'eleve pour l activite, ou null si aucune note n'est presente.
 *         La note est fournie avec sa valeur, son feedback et la date d'attribution
 *         $rs->finalgrade, $rs->feedback, $rs->timemodified.
 */
function tuteur_isNote($idUser, $idActivity) {
	global $DB;
	$infoActivite = $DB->get_record_select ( 'course_modules', 'id = ?', array ($idActivity) );

	$sql = "SELECT g.finalgrade, g.feedback, g.timemodified
              FROM {grade_grades} g
              JOIN {grade_items} a ON a.id = g.itemid
             WHERE a.courseid = ? and a.iteminstance= ? and itemmodule=? and g.userid=?";
		
		
	$rs = $DB->get_record_sql($sql, array($infoActivite->course, $infoActivite->instance, 'assign', $idUser));
				
	if (isset ( $rs )) {
		$note = $rs->finalgrade;
		if ($note != NULL) {
			return $rs;
		}
	}
	return null;
}

/**
 * Fournit la derniere soumission d'assignment de l'eleve.
 *
 * @param $idUser identifiant technique de l eleve.
 * @param $idActivity identifiant technique de l'activite.
 * @return le status de la soumission ('new' pour vide, submitted pour soumis).
 */
function tuteur_getDernierSoumis($idUser, $idActivity) {
	global $DB;
	$infoActivite = $DB->get_record_select ( 'course_modules', 'id = ?', array ($idActivity) );
		
 	$sql = "SELECT id, status, timemodified
 				  FROM {assign_submission}
		         WHERE timemodified = (SELECT max(timemodified) FROM {assign_submission} WHERE assignment = ? and userid=? AND status='submitted')
		           AND assignment = ? and userid=? AND status='submitted'";
	$rs = $DB->get_record_sql($sql,	array($infoActivite->instance, $idUser, $infoActivite->instance, $idUser));
		
	return $rs;
}

/**
 * Fournit le commentaire le plus récent pour une activite.
 * @param $idcontext identifiant technique du contexte de l'activité.
 * @param $iditem l'identifiant technique de l'item.
 * @return le commentaire le plus récent pour une activité
 */
function tuteur_getDernierComment($idcontext, $iditem) {
	global $DB;
	
	$sql = "SELECT content, timecreated
 				  FROM {comments}
		         WHERE timecreated = (SELECT max(timecreated) FROM {comments} WHERE contextid = ? and itemid=?)
		           AND contextid = ? and itemid=?";
	
	$rs = $DB->get_record_sql ( $sql, array ($idcontext, $iditem, $idcontext, $iditem) );
	
	return $rs;
}
