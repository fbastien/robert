<?php
/*
 *
Le Robert est un logiciel libre; vous pouvez le redistribuer et/ou
le modifier sous les termes de la Licence Publique Générale GNU Affero
comme publiée par la Free Software Foundation;
version 3.0.

Cette WebApp est distribuée dans l'espoir qu'elle soit utile,
mais SANS AUCUNE GARANTIE; sans même la garantie implicite de
COMMERCIALISATION ou D'ADAPTATION A UN USAGE PARTICULIER.
Voir la Licence Publique Générale GNU Affero pour plus de détails.

Vous devriez avoir reçu une copie de la Licence Publique Générale
GNU Affero avec les sources du logiciel; si ce n'est pas le cas,
rendez-vous à http://www.gnu.org/licenses/agpl.txt (en Anglais)
*
*/

require_once dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'fct'.DIRECTORY_SEPARATOR.'matos_tri_sousCat.php';

/** Test des fonctions utilitaires de groupage du matériel (fct/matos_tri_sousCat.php). */
class Matos_Tri_SousCat_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * Teste le groupage du matériel identifié unitairement en fonction du matériel générique auquel il correspond.
	 * 
	 * @test
	 * @covers ::groupUnitsByMatos
	 */
	public function testGroupUnitsByMatos() {
		// Préparation des données
		$matosGen1 = 10;
		$matosGen2 = 20;
		$matosUnit1 = array(
				'id' => 1,
				'id_matosdetail' => $matosGen2,
				'ref' => '20-1');
		$matosUnit2 = array(
				'id' => 2,
				'id_matosdetail' => $matosGen1,
				'ref' => '10-2');
		$matosUnit3 = array(
				'id' => 3,
				'id_matosdetail' => $matosGen1,
				'ref' => '10-3');
		$matosUnit = array($matosUnit1, $matosUnit2, $matosUnit3);
		
		// Test
		$groupedMatos = groupUnitsByMatos($matosUnit);
		
		// Vérification des données retournées
		$expectedMatos = array(
				$matosGen2 => array($matosUnit1),
				$matosGen1 => array($matosUnit2, $matosUnit3));
		$this->assertEquals($expectedMatos, $groupedMatos);
	}
}
