<?php
/**
 * This file contains functions used by the progress report
 *
 * @package    report
 * @subpackage tuteur
 * @copyright 2016 Pole de Ressource Numerique, Universite du Maine
 * @license   sans objet
 */

defined('MOODLE_INTERNAL') || die;

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_tuteur_extend_navigation_course($navigation, $course, $context) {
    global $CFG, $OUTPUT;

    if (has_capability('report/tuteur:view', $context)) {
		$url = new moodle_url('/report/tuteur/index.php', array('course'=>$course->id));
        $navigation->add(get_string('pluginname','report_tuteur'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));	
	}
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 */
function report_tuteur_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $array = array(
        '*'                     => get_string('page-x', 'pagetype'),
        'report-*'              => get_string('page-report-x', 'pagetype'),
        'report-tuteur-*'     => get_string('page-report-tuteur-x',  'report_tuteur'),
        'report-tuteur-index' => get_string('page-report-tuteur-index',  'report_tuteur'),
    );
    return $array;
}