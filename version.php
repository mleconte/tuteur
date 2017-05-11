<?php
/**
 * report_tuteur version information.
 * Modification du rapport de suivi d'achevement, pour mettre en valeur les actions a r�aliser
 * par les tuteurs. Chaque devoir doit �tre not�, chaque QCM doit �tre comment�.
 * Les cellules passe alors de la couleur Orange a Verte.
 * Ajout de try catch sur recherche info :Quizz, lesson, journal (pb de donn�es)
 * Traitement des activites journal non not�s.
 * Modification du traitement consacr� aux QCM (Test) prise en compte du feedback.
 * Ajout des Aides
 * 01/12/2016 correction des liens sur assign, pour eviter les changement selon le role de l'operateur.
 * 14/03/2017 correction sur choix coloration des tests + verification du tri, tri� par nom
 * 21/03/2017 modification de l'affichage nom prenom
 * 13/04/2017 ajout du filtre sur les groupes
 * 14/04/2017 modification style th,td pour supprimer les lignes du tableau lors du passage en version 3.2.2
 * 09/05/2017 modification colonne/cache/visible compatible avec les differents navigateurs et revue de code
 * @package   report_tuteur
 * @copyright 2016 Pole de Ressource Numerique, Universite du Maine
 * @license   sans objet
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2017051001;
$plugin->requires  = 2015111000;
$plugin->cron      = 0;
$plugin->component = 'report_tuteur';
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = 'v1.0';
