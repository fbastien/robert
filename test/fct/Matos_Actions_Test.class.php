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

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'PageTestCase.class.php';

/** Test des actions liées au matériel (fct/matos_actions.php). */
class Matos_Actions_Test extends PageTestCase
{
	/** @var PHPUnit_Extensions_Database_DataSet_IDataSet Dataset d'un matériel supplémentaire appartenant au stock interne. */
	private static $matosInterneDataset;
	/** @var PHPUnit_Extensions_Database_DataSet_IDataSet Dataset d'un matériel supplémentaire appartenant à un prestataire externe. */
	private static $matosExterneDataset;
	/** @var array Liste des tables dont le contenu est à tester. */
	private static $tables = array('robert_matos_detail', 'robert_matos_unit');
	
	/**
	 * @beforeClass
	 * @see PHPUnit_Framework_TestCase::setUpBeforeClass()
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		
		self::setTestedPage('fct'.DIRECTORY_SEPARATOR.'matos_actions.php');
		self::$matosInterneDataset = new PHPUnit_Extensions_Database_DataSet_XmlDataSet(__DIR__.DIRECTORY_SEPARATOR.'matos_actions_interne_dataset.xml');
		self::$matosExterneDataset = new PHPUnit_Extensions_Database_DataSet_XmlDataSet(__DIR__.DIRECTORY_SEPARATOR.'matos_actions_externe_dataset.xml');
	}
	
	/**
	 * Teste la récupération des informations d'un matériel en JSON (pour l'écran de modification d'un matériel).
	 * 
	 * @test
	 */
	public function testActionSelect() {
		// Préparation des données
		$matos = $this->getDataSet()->getTable('robert_matos_detail')->getRow(0);
		$_POST['action'] = 'select';
		$_POST['id'] = $matos['id'];
		
		// Test
		$this->callTestedPage();
		
		// Vérification des données retournées
		$expectedJson = array (
				'id' => $matos['id'],
				'label' => $matos['label'],
				'ref' => $matos['ref'],
				'panne' => $matos['panne'],
				'externe' => '1',
				'categorie' => $matos['categorie'],
				'sousCateg' => $matos['sousCateg'],
				'Qtotale' => $matos['Qtotale'],
				'tarifLoc' => $matos['tarifLoc'],
				'valRemp' => $matos['valRemp'],
				'dateAchat' => null,
				'ownerExt' => $matos['ownerExt'],
				'remarque' => $matos['remarque'] );
		$this->assertJsonStringEqualsJsonString(json_encode($expectedJson), $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDataSet()->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'ajout d'un matériel (pour l'écran accessible depuis de la liste du matériel).
	 * 
	 * Cas de test :
	 * * Matériel interne
	 * 
	 * @test
	 */
	public function testActionAddMatosInterne() {
		// Préparation des données
		$matos = self::$matosInterneDataset->getTable('robert_matos_detail')->getRow(0);
		$_POST['action'] = 'addMatos';
		$_POST['ref'] = $matos['ref'];
		$_POST['label'] = $matos['label'];
		$_POST['categorie'] = $matos['categorie'];;
		$_POST['sousCateg'] = $matos['sousCateg'];
		$_POST['tarifLoc'] = $matos['tarifLoc'];
		$_POST['valRemp'] = $matos['valRemp'];
		$_POST['Qtotale'] = $matos['Qtotale'];
		$_POST['remarque'] = $matos['remarque'];
		$_POST['externe'] = '0';
		$_POST['dateAchat'] = $matos['dateAchat'];
		$_POST['ownerExt'] = 'N/A';
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("Matériel ${matos['ref']} Ajouté !", $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedData = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), self::$matosInterneDataset));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedData->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'ajout d'un matériel (pour l'écran accessible depuis de la liste du matériel).
	 * 
	 * Cas de test :
	 * * Matériel externe
	 * 
	 * @test
	 */
	public function testActionAddMatosExterne() {
		// Préparation des données
		$matos = self::$matosExterneDataset->getTable('robert_matos_detail')->getRow(0);
		$_POST['action'] = 'addMatos';
		$_POST['ref'] = $matos['ref'];
		$_POST['label'] = $matos['label'];
		$_POST['categorie'] = $matos['categorie'];;
		$_POST['sousCateg'] = $matos['sousCateg'];
		$_POST['tarifLoc'] = $matos['tarifLoc'];
		$_POST['valRemp'] = $matos['valRemp'];
		$_POST['Qtotale'] = $matos['Qtotale'];
		$_POST['remarque'] = $matos['remarque'];
		$_POST['externe'] = '1';
		$_POST['dateAchat'] = '2016-04-14';
		$_POST['ownerExt'] = $matos['ownerExt'];
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("Matériel ${matos['ref']} Ajouté !", $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedData = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), self::$matosExterneDataset));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedData->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'ajout d'un matériel (pour l'écran accessible lors la création d'un événement).
	 * 
	 * Cas de test :
	 * * Matériel interne
	 * 
	 * @test
	 */
	public function testActionAddMatosJsonInterne() {
		// Préparation des données
		$matos = self::$matosInterneDataset->getTable('robert_matos_detail')->getRow(0);
		$externe = '0';
		$_POST['action'] = 'addMatosJson';
		$_POST['ref'] = $matos['ref'];
		$_POST['label'] = $matos['label'];
		$_POST['categorie'] = $matos['categorie'];;
		$_POST['sousCateg'] = $matos['sousCateg'];
		$_POST['tarifLoc'] = $matos['tarifLoc'];
		$_POST['valRemp'] = $matos['valRemp'];
		$_POST['Qtotale'] = $matos['Qtotale'];
		$_POST['remarque'] = $matos['remarque'];
		$_POST['externe'] = $externe;
		$_POST['dateAchat'] = $matos['dateAchat'];
		$_POST['ownerExt'] = 'N/A';
		
		// Test
		$this->callTestedPage();
		
		// Vérification des données retournées
		$expectedJson = array(
				'success' => 'SUCCESS',
				'matos' => array (
					'id' => $matos['id'],
					'label' => $matos['label'],
					'ref' => $matos['ref'],
					'panne' => '0',
					'externe' => $externe,
					'categorie' => $matos['categorie'],
					'sousCateg' => $matos['sousCateg'],
					'Qtotale' => $matos['Qtotale'],
					'tarifLoc' => $matos['tarifLoc'],
					'valRemp' => $matos['valRemp'],
					'dateAchat' => $matos['dateAchat'],
					'ownerExt' => null,
					'remarque' => $matos['remarque'] ));
		$this->assertJsonStringEqualsJsonString(json_encode($expectedJson), $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedData = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), self::$matosInterneDataset));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedData->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'ajout d'un matériel (pour l'écran accessible lors la création d'un événement).
	 * 
	 * Cas de test :
	 * * Matériel externe
	 * 
	 * @test
	 */
	public function testActionAddMatosJsonExterne() {
		// Préparation des données
		$matos = self::$matosExterneDataset->getTable('robert_matos_detail')->getRow(0);
		$externe = '1';
		$_POST['action'] = 'addMatosJson';
		$_POST['ref'] = $matos['ref'];
		$_POST['label'] = $matos['label'];
		$_POST['categorie'] = $matos['categorie'];;
		$_POST['sousCateg'] = $matos['sousCateg'];
		$_POST['tarifLoc'] = $matos['tarifLoc'];
		$_POST['valRemp'] = $matos['valRemp'];
		$_POST['Qtotale'] = $matos['Qtotale'];
		$_POST['remarque'] = $matos['remarque'];
		$_POST['externe'] = $externe;
		$_POST['dateAchat'] = '2016-04-14';
		$_POST['ownerExt'] = $matos['ownerExt'];
		
		// Test
		$this->callTestedPage();
		
		// Vérification des données retournées
		$expectedJson = array(
				'success' => 'SUCCESS',
				'matos' => array (
					'id' => $matos['id'],
					'label' => $matos['label'],
					'ref' => $matos['ref'],
					'panne' => '0',
					'externe' => $externe,
					'categorie' => $matos['categorie'],
					'sousCateg' => $matos['sousCateg'],
					'Qtotale' => $matos['Qtotale'],
					'tarifLoc' => $matos['tarifLoc'],
					'valRemp' => $matos['valRemp'],
					'dateAchat' => null,
					'ownerExt' => $matos['ownerExt'],
					'remarque' => $matos['remarque'] ));
		$this->assertJsonStringEqualsJsonString(json_encode($expectedJson), $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedData = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), self::$matosExterneDataset));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedData->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste la modification d'un matériel.
	 * 
	 * Cas de test :
	 * * Modification d'un matériel interne en un matériel externe
	 * 
	 * @test
	 */
	public function testActionModifInterne() {
		// Préparation des données
		$this->insertData(self::$matosInterneDataset);
		$newMatos = self::$matosExterneDataset->getTable('robert_matos_detail')->getRow(0);
		$_POST['action'] = 'modif';
		$_POST['id'] = $newMatos['id'];
		$_POST['ref'] = $newMatos['ref'];
		$_POST['label'] = $newMatos['label'];
		$_POST['categorie'] = $newMatos['categorie'];
		$_POST['sousCateg'] = $newMatos['sousCateg'];
		$_POST['tarifLoc'] = $newMatos['tarifLoc'];
		$_POST['valRemp'] = $newMatos['valRemp'];
		$_POST['Qtotale'] = $newMatos['Qtotale'];
		$_POST['panne'] = $newMatos['panne'];
		$_POST['remarque'] = $newMatos['remarque'];
		$_POST['externe'] = '1';
		$_POST['dateAchat'] = self::$matosInterneDataset->getTable('robert_matos_detail')->getRow(0)['dateAchat'];
		$_POST['ownerExt'] = $newMatos['ownerExt'];
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("Matériel sauvegardé !", $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedData = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), self::$matosExterneDataset));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedData->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste la modification d'un matériel.
	 * 
	 * Cas de test :
	 * * Modification d'un matériel externe en un matériel interne
	 * 
	 * @test
	 */
	public function testActionModifExterne() {
		// Préparation des données
		$this->insertData(self::$matosExterneDataset);
		$newMatos = self::$matosInterneDataset->getTable('robert_matos_detail')->getRow(0);
		$_POST['action'] = 'modif';
		$_POST['id'] = $newMatos['id'];
		$_POST['ref'] = $newMatos['ref'];
		$_POST['label'] = $newMatos['label'];
		$_POST['categorie'] = $newMatos['categorie'];
		$_POST['sousCateg'] = $newMatos['sousCateg'];
		$_POST['tarifLoc'] = $newMatos['tarifLoc'];
		$_POST['valRemp'] = $newMatos['valRemp'];
		$_POST['Qtotale'] = $newMatos['Qtotale'];
		$_POST['panne'] = $newMatos['panne'];
		$_POST['remarque'] = $newMatos['remarque'];
		$_POST['externe'] = '0';
		$_POST['dateAchat'] = $newMatos['dateAchat'];
		$_POST['ownerExt'] = self::$matosExterneDataset->getTable('robert_matos_detail')->getRow(0)['ownerExt'];
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("Matériel sauvegardé !", $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedData = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), self::$matosInterneDataset));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedData->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste la suppression d'un matériel.
	 * 
	 * @test
	 */
	public function testActionModifDelete() {
		// Préparation des données
		$this->insertData(self::$matosInterneDataset);
		$_POST['action'] = 'delete';
		$_POST['id'] = self::$matosInterneDataset->getTable('robert_matos_detail')->getRow(0)['id'];
		
		$this->callTestedPage();
		
		$expectedJson = array(
				'error' => 'OK',
				'type' => 'reloadPage');
		$this->assertJsonStringEqualsJsonString(json_encode($expectedJson), $this->getOutput());
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDefaultDataSet()->getTable($tableName), $tableName);
		}
	}
}
?>