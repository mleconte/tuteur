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
 * Strings for component 'report_tuteur', language 'en'
 *
 * @package   report_tuteur
 * @copyright 2016 Pole de Ressource Numerique, Universite du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['allusers'] = 'All users';
$string['all_group'] = 'All groups';
$string['displaymode'] = 'Display mode';
$string['learningmodeoff'] = 'Learning mode off';
$string['learningmodeon'] = 'Learning mode on';
$string['pluginname'] = 'Tuteur';
$string['printmode'] = 'Printable';
$string['tuteur:view'] = 'View tuteur report';
$string['webmode'] = 'Web report';
$string['page-report-tuteur-x'] = 'Any tuteur report';
$string['page-report-tuteur-index'] = 'Activity tuteur report';
$string['page-report-tuteur-user'] = 'User activity completion report';
$string['selecteur'] = 'Selector';
$string['selecteur_help'] = 'You can choose activity you want supervise<br/>

* Check activity
* Then clic on filter button

<H2>Rules for color</h2>
<p>Assign</p>
* orange : assign submit but not yet grade or feedback
* green : the last assign was grade or feedback
<p>Quiz</p>
* orange : the last attempt has no feedback
* green : at least one question of the last attempt have a feedback
<p>Journal</p>
* orange : the student has wrote something without feedback or grade
* green : the journal is grade or the last student&quote;article has a response
<p>Lesson</p>
* orange : a composition without feedback
* green : all compositions have a feedback.

So orange when tutor action is require.
';
$string['eye_help'] = 'hide/show a section';
$string['eye'] = 'hide/show';
$string['student-report'] = 'Student report';
$string['filter'] = 'Filter';
$string['symbol'] = 'Symbol';
$string['zero-activity'] = 'They are no activity under supervision';
$string['no-more-activity'] = 'No more activity after filter';
$string['privacy:metadata'] = 'The plugin Tuteur does not store any personal data.';
