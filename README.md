Plugin Moodle développé par le Pole de Ressource Numérique (PRN) de l'Université du Mans.

Il s'agit d'un clone du rapport d'achèvement, qui colorise 4 activités en fonctions des actions des apprenants et des tuteurs.
Les 4 activités sont : Devoir, Quizz, Journal, Leçon
Les règles de colorisations sont :
+------------+-------------------+-----------------------------+------------------------------+
|    Couleur |                   |                             |                              |
|    \       |  Aucune           | Orange                      | Verte                        |
| Activité   |                   |                             |                              |
+------------+-------------------+-----------------------------+------------------------------+
| Devoir     | pas de soumission | Soumission plus récente que | Note ou FeedBack plus récent |
|            |                   | note ou FeedBack            | que soumission               |
+------------+-------------------+-----------------------------+------------------------------+
| Test       | test non réalisé  | Dernière tentative non noté | Dernière tentative noté      |
+------------+-------------------+-----------------------------+------------------------------+
| Journal    | Non renseigné     | Ecrit élève plus récent     | Noté ou écrit tuteur plus    |
|            |                   | qu'écrit Tuteur             | récent qu'écrit élève        |
+------------+-------------------+-----------------------------+------------------------------+
| Leçon      | Pas de composition| Une composition ne dispose  | Toutes les compositions ont  |
|            | réalisé           | pas de FeedBack             | un FeedBack                  |
+------------+-------------------+-----------------------------+------------------------------+

Il est possible de filtrer les sections à observer ainsi que le groupe d'apprenant.

Le plugin fonctionne sous Firefox 53.0.2, Internet Explorer 11, GoogleChrome 58.
