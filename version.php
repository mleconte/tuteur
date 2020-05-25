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
 * report_tuteur version information.
 * Clone of completion report, show tutor's action must be do
 *
 * 01/12/2016 fix assign's links.
 * 14/03/2017 fix selector choose activity + check order of student
 * 21/03/2017 fix display lastname before firstname (french style)
 * 13/04/2017 add group selector
 * 14/04/2017 change css style th,td for theme of Moodle 3.2.2
 * 09/05/2017 change javascript show/hide section, assume compatibility with
 * more browser
 * 15/05/2017 add licence GNU GPL.
 * 16/05/2017 english translation
 * 11/07/2017 only view activities under supervision.
 * 22/02/2018 add dialoguegrade activity on supervision.
 * @package report_tuteur
 * @copyright 2016 Pole de Ressource Numerique, Universite du Mans
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined ( 'MOODLE_INTERNAL' ) || die ();

$plugin->version  = 2019060504;
$plugin->requires = 2015111000;
$plugin->cron = 0;
$plugin->component = 'report_tuteur';
$plugin->maturity = MATURITY_STABLE;
$plugin->release = 'v1.2';
