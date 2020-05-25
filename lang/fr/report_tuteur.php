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
 * Strings for component 'report_tuteur', language 'fr'
 *
 * @package   report_tuteur
 * @copyright 2016 Pole de Ressource Numerique, Universite du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['all_group'] = 'Tous les groupes';
$string['eye_help'] = 'Vous permet cacher/afficher une section du cours';
$string['eye'] = 'cacher/afficher';
$string['filter'] = 'Filtrer';
$string['pluginname'] = 'Tuteur';
$string['selecteur'] = 'S&#233;lecteur';
$string['selecteur_help'] = 'Vous permet de choisir les types d&rsquo;activit&eacute;s &agrave; coloriser<br/>

* Cocher l&rsquo;activit&eacute;
* Puis cliquer sur le bouton Filtrer

<H2>R&egrave;gles de colorisation</h2>
<p>Devoir</p>
* orange :L&rsquo;&eacute;tudiant a soumis un devoir qui n&rsquo;est pas encore not&eacute; et/ou n&rsquo;a pas eu de feeback
* vert :Le dernier devoir soumis a re&ccedil;u une note ou un feedback.
<p>Test</p>
* orange : aucun feedback sur la derni&egrave;re tentative.
* vert : au moins une question de la derni&egrave;re tentative poss&egrave;de un feedback.
<p>Journal</p>
* orange : l&rsquo;&eacute;tudiant a &eacute;crit dans le journal, sans avoir eu de r&eacute;ponse ni de note.
* vert : le journal est not&eacute; ou le dernier &eacute;crit est celui du tuteur.
<p>Le&ccedil;on</p>
* orange :une composition ne poss&egrave;de pas de feedback.
* vert :toutes les compositions ont un feedback.

En r&eacute;sum&eacute; : orange une action du tuteur est requise.
        ';
$string['student-report'] = 'Rapport Etudiant';
$string['symbol'] = 'Symbole';
$string['zero-activity'] = 'Ce cours ne dispose pas d&quote;activit&eacute;es surveill&eacute;es';
$string['no-more-activity'] = 'Aucun r&eacute;sultat suite au filtre !';
$string['privacy:metadata'] = 'Le plugin de Tuteur n\'enregistre aucune donnée personnelle.';
