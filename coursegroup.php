<?php
/**
 * Assiste l'utilisation des groupes d'un cours.
 */
class coursegroup {
	/** identifiant du cours.*/
	private $courseId;
	/** nb de groupe non vide du cours.*/
	private $nbGroup;
	/** tableau des groupes 'id', 'nom' .*/
	private $tabGroup;
	/** tableau des membres des groupes 'idMembre', 'idGroupe'.*/
	private $tabMember;
	
	function __construct($idCourse) {
		$this->courseId = $idCourse;
		$this->tabGroup = array();
		$this->tabMember = array();
		$this->nbGroup = 0;
		if (is_numeric($this->courseId) && $this->courseId > 0) {
			$this->rechercherDonnees();
		}
	}
	
	/**
	 * Renseigne les tableaux de groupe et de membre
	 * pour le cours consid�r�.
	 */
	private function rechercherDonnees() {
		global $DB;
		$sqlGroupes = "SELECT g.name, g.id, m.userid
                         from {groups_members} m, {groups} g 
                        where g.id = m.groupid
                          and g.courseid = " . $this->courseId ."
					Order by name;";
		$resultGroupes = $DB->get_recordset_sql($sqlGroupes , array());
		
		$zrNom = "";
		foreach ($resultGroupes as $data) {
			if (strcmp($zrNom, $data->name) != 0) {
				$record = new stdClass();
				$record->id = $data->id;
				$record->nom = $data->name;
				$this->tabGroup[] = $record;
				$this->nbGroup = $this->nbGroup + 1;
				$zrNom = $data->name;
			}
			
			$membre = new stdClass();
			$membre->idMembre = $data->userid;
			$membre->idGroupe = $data->id;
			$this->tabMember[] = $membre;
		}
	}
	
	/**
	 * Construit la representation html de la combo du
	 * choix des groupes.
	 * La balise 'select' est nomm�e chxGroupe.
	 * Si aucun groupe n'existe une balise input cach� portant le meme nom avec
	 * la valeur repr�sentant tous les groupes est renvoy�e.
	 * @param $idSelected le numero de groupe a s�lectionner, 0 pour tous.
	 * @return la repr�sentation html du selecteur de groupe.
	 */
	public function rendererSelectGroup($idSelected) {
		$ret = "";
		if ($this->nbGroup > 0) {
			$ret = "<select name='chxGroupe'>";
			if ($idSelected == 0) {
				$ret = $ret . "<option value='0' selected>Tous les groupes</option>";
			} else {
				$ret = $ret . "<option value='0'>Tous les groupes</option>";
			}
			foreach ($this->tabGroup as $groupe) {
				$ret = $ret . "<option value='". $groupe->id . "'";
				if ($groupe->id == $idSelected) {
					$ret = $ret . " selected";
				}
				$ret = $ret .">" . $groupe->nom ."</option>";
			}
			$ret = $ret . "</select>";
		} else {
			$ret = "<input type='hidden' name='chxGroupe' value='0'>";
		}
		
		return $ret;
	}
	
	/**
	 * Test si l'identifiant utilisateur appartient au groupe pass� en parametre.
	 * Si $idGroup = 0 alors on retourne toujours true.
	 */
	public function isMember($idGroup, $idUser) {
		if ($idGroup == 0) return true;
		$index = 0;
		$pasTrouve = true;
		while ($index < count($this->tabMember) && $pasTrouve) {
			if ($this->tabMember[$index]->idMembre == $idUser && $this->tabMember[$index]->idGroupe == $idGroup) {
				$pasTrouve = false;
			}
			$index = $index + 1;
		}
		return ! $pasTrouve;
	}
}
