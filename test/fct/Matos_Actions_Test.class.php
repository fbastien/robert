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

require_once dirname(__DIR__).'/PageTestCase.class.php';

/**
 * Test du script d'installation de la base de données (install_DB.sql).
 * 
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Matos_Actions_Test extends PageTestCase {
	
	/** @var PHPUnit_Extensions_Database_DataSet_IDataSet Dataset des données communes à tous les cas de test. */
	private static $dataset;
	/** @var PHPUnit_Extensions_Database_DataSet_IDataSet Dataset d'un matériel supplémentaire appartenant au stock interne. */
	private static $matosInterneDataset;
	/** @var PHPUnit_Extensions_Database_DataSet_IDataSet Dataset d'un matériel supplémentaire appartenant à un prestataire externe. */
	private static $matosExterneDataset;
	/** @var array Liste des tables dont le contenu est à tester. */
	private static $tables = array('robert_matos_detail', 'robert_matos_generique', 'robert_matos_ident');
	
	/**
	 * @beforeClass
	 * @see PHPUnit_Framework_TestCase::setUpBeforeClass()
	 */
	public static function setUpBeforeClass() {
		self::setTestedPage("fct/matos_actions.php");
		$instance = new self();
		self::$dataset = $instance->createXmlDataSet(dirname(__DIR__).'/common_fixture_dataset.xml');
		self::$matosInterneDataset = $instance->createXmlDataSet(__DIR__.'/matos_actions_interne_dataset.xml');
		self::$matosExterneDataset = $instance->createXmlDataSet(__DIR__.'/matos_actions_externe_dataset.xml');
	}
	
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	public function getDataSet() {
		return self::$dataset;
	}
	
	/** @test */
	public function testActionSelect() {
		$id = '1';
		$matosDetail = $this->getDataSet()->getTable('robert_matos_detail')->getRow(0);
		$matosGenerique = $this->getDataSet()->getTable('robert_matos_generique')->getRow(0);
		$matosIdentTable = $this->getDataSet()->getTable('robert_matos_ident');
		$matosIdent = array($matosIdentTable->getRow(0), $matosIdentTable->getRow(1), $matosIdentTable->getRow(2));
		if ($matosDetail['id'] != $id
				|| $matosGenerique['id_matosdetail'] != $id
				|| call_user_func(function ($array, $id) {
						foreach ($array as $row) {
							if($row['id_matosdetail'] != $id)
								return true;
						}
						return false;
					}, $matosIdent, $id)) {
			throw new UnexpectedValueException("Configuration des valeurs attendues incorrecte");
		}
		
		$_POST['action'] = 'select';
		$_POST['id'] = $id;
		
		$this->callTestedPage();
		
		$expectedJson = array (
				'id' => $id,
				'label' => $matosDetail['label'],
				'ref' => $matosDetail['ref'],
				'panne' => $matosGenerique['panne'] + $matosIdent[0]['panne'] + $matosIdent[1]['panne'] + $matosIdent[2]['panne'],
				'externe' => '1',
				'categorie' => $matosDetail['categorie'],
				'sousCateg' => $matosDetail['sousCateg'],
				'Qtotale' => $matosGenerique['quantite'] + 3,
				'tarifLoc' => $matosDetail['tarifLoc'],
				'valRemp' => $matosDetail['valRemp'],
				'dateAchat' => null,
				'ownerExt' => $matosGenerique['ownerExt'].', '.$matosIdent[1]['ownerExt'],
				'remarque' => $matosDetail['remarque'] );
		$this->assertJsonStringEqualsJsonString(json_encode($expectedJson), $this->getOutput());
	}
	
	/** @test */
	public function testActionAddMatosInterne() {
		$matosDetail = self::$matosInterneDataset->getTable('robert_matos_detail')->getRow(0);
		$matosGenerique = self::$matosInterneDataset->getTable('robert_matos_generique')->getRow(0);
		
		$_POST['action'] = 'addMatos';
		$_POST['ref'] = $matosDetail['ref'];
		$_POST['label'] = $matosDetail['label'];
		$_POST['categorie'] = $matosDetail['categorie'];;
		$_POST['sousCateg'] = $matosDetail['sousCateg'];
		$_POST['tarifLoc'] = $matosDetail['tarifLoc'];
		$_POST['valRemp'] = $matosDetail['valRemp'];
		$_POST['Qtotale'] = $matosGenerique['quantite'];
		$_POST['remarque'] = $matosDetail['remarque'];
		$_POST['externe'] = '0';
		$_POST['dateAchat'] = $matosGenerique['dateAchat'];
		$_POST['ownerExt'] = 'N/A';
		
		$this->callTestedPage();
		
		$this->assertSame("Matériel ${matosDetail['ref']} Ajouté !", $this->getOutput());
		$expectedData = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), self::$matosInterneDataset));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedData->getTable($tableName), $tableName);
		}
	}
	
	/** @test */
	public function testActionAddMatosExterne() {
		$matosDetail = self::$matosExterneDataset->getTable('robert_matos_detail')->getRow(0);
		$matosGenerique = self::$matosExterneDataset->getTable('robert_matos_generique')->getRow(0);
		
		$_POST['action'] = 'addMatos';
		$_POST['ref'] = $matosDetail['ref'];
		$_POST['label'] = $matosDetail['label'];
		$_POST['categorie'] = $matosDetail['categorie'];;
		$_POST['sousCateg'] = $matosDetail['sousCateg'];
		$_POST['tarifLoc'] = $matosDetail['tarifLoc'];
		$_POST['valRemp'] = $matosDetail['valRemp'];
		$_POST['Qtotale'] = $matosGenerique['quantite'];
		$_POST['remarque'] = $matosDetail['remarque'];
		$_POST['externe'] = '1';
		$_POST['dateAchat'] = '2016-04-14';
		$_POST['ownerExt'] = $matosGenerique['ownerExt'];
		
		$this->callTestedPage();
		
		$this->assertSame("Matériel ${matosDetail['ref']} Ajouté !", $this->getOutput());
		$expectedData = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), self::$matosExterneDataset));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedData->getTable($tableName), $tableName);
		}
	}
	
	/** @test */
	public function testActionAddMatosJsonInterne() {
		$matosDetail = self::$matosInterneDataset->getTable('robert_matos_detail')->getRow(0);
		$matosGenerique = self::$matosInterneDataset->getTable('robert_matos_generique')->getRow(0);
		$externe = '0';
		
		$_POST['action'] = 'addMatosJson';
		$_POST['ref'] = $matosDetail['ref'];
		$_POST['label'] = $matosDetail['label'];
		$_POST['categorie'] = $matosDetail['categorie'];;
		$_POST['sousCateg'] = $matosDetail['sousCateg'];
		$_POST['tarifLoc'] = $matosDetail['tarifLoc'];
		$_POST['valRemp'] = $matosDetail['valRemp'];
		$_POST['Qtotale'] = $matosGenerique['quantite'];
		$_POST['remarque'] = $matosDetail['remarque'];
		$_POST['externe'] = $externe;
		$_POST['dateAchat'] = $matosGenerique['dateAchat'];
		$_POST['ownerExt'] = 'N/A';
		
		$this->callTestedPage();
		
		$expectedJson = array(
				'success' => 'SUCCESS',
				'matos' => array (
					'id' => $matosDetail['id'],
					'label' => $matosDetail['label'],
					'ref' => $matosDetail['ref'],
					'panne' => '0',
					'externe' => $externe,
					'categorie' => $matosDetail['categorie'],
					'sousCateg' => $matosDetail['sousCateg'],
					'Qtotale' => $matosGenerique['quantite'],
					'tarifLoc' => $matosDetail['tarifLoc'],
					'valRemp' => $matosDetail['valRemp'],
					'dateAchat' => $matosGenerique['dateAchat'],
					'ownerExt' => null,
					'remarque' => $matosDetail['remarque'] ));
		$this->assertJsonStringEqualsJsonString(json_encode($expectedJson), $this->getOutput());
		$expectedData = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), self::$matosInterneDataset));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedData->getTable($tableName), $tableName);
		}
	}
	
	/** @test */
	public function testActionAddMatosJsonExterne() {
		$matosDetail = self::$matosExterneDataset->getTable('robert_matos_detail')->getRow(0);
		$matosGenerique = self::$matosExterneDataset->getTable('robert_matos_generique')->getRow(0);
		$externe = '1';
		
		$_POST['action'] = 'addMatosJson';
		$_POST['ref'] = $matosDetail['ref'];
		$_POST['label'] = $matosDetail['label'];
		$_POST['categorie'] = $matosDetail['categorie'];;
		$_POST['sousCateg'] = $matosDetail['sousCateg'];
		$_POST['tarifLoc'] = $matosDetail['tarifLoc'];
		$_POST['valRemp'] = $matosDetail['valRemp'];
		$_POST['Qtotale'] = $matosGenerique['quantite'];
		$_POST['remarque'] = $matosDetail['remarque'];
		$_POST['externe'] = $externe;
		$_POST['dateAchat'] = '2016-04-14';
		$_POST['ownerExt'] = $matosGenerique['ownerExt'];
		
		$this->callTestedPage();
		
		$expectedJson = array(
				'success' => 'SUCCESS',
				'matos' => array (
					'id' => $matosDetail['id'],
					'label' => $matosDetail['label'],
					'ref' => $matosDetail['ref'],
					'panne' => '0',
					'externe' => $externe,
					'categorie' => $matosDetail['categorie'],
					'sousCateg' => $matosDetail['sousCateg'],
					'Qtotale' => $matosGenerique['quantite'],
					'tarifLoc' => $matosDetail['tarifLoc'],
					'valRemp' => $matosDetail['valRemp'],
					'dateAchat' => '0000-00-00',
					'ownerExt' => $matosGenerique['ownerExt'],
					'remarque' => $matosDetail['remarque'] ));
		$this->assertJsonStringEqualsJsonString(json_encode($expectedJson), $this->getOutput());
		$expectedData = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), self::$matosExterneDataset));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedData->getTable($tableName), $tableName);
		}
	}
	
	/** @test */
	public function testActionModifInterne() {
		$this->insertData(self::$matosInterneDataset);
		$matosDetail = self::$matosExterneDataset->getTable('robert_matos_detail')->getRow(0);
		$matosGenerique = self::$matosExterneDataset->getTable('robert_matos_generique')->getRow(0);
		if ($matosDetail['id'] != self::$matosInterneDataset->getTable('robert_matos_detail')->getRow(0)['id']) {
			throw new UnexpectedValueException("Configuration des valeurs attendues incorrecte");
		}
		
		$_POST['action'] = 'modif';
		$_POST['id'] = $matosDetail['id'];
		$_POST['ref'] = $matosDetail['ref'];
		$_POST['label'] = $matosDetail['label'];
		$_POST['categorie'] = $matosDetail['categorie'];
		$_POST['sousCateg'] = $matosDetail['sousCateg'];
		$_POST['tarifLoc'] = $matosDetail['tarifLoc'];
		$_POST['valRemp'] = $matosDetail['valRemp'];
		$_POST['Qtotale'] = $matosGenerique['quantite'];
		$_POST['panne'] = $matosGenerique['panne'];
		$_POST['remarque'] = $matosDetail['remarque'];
		$_POST['externe'] = '1';
		$_POST['dateAchat'] = self::$matosInterneDataset->getTable('robert_matos_generique')->getRow(0)['dateAchat'];
		$_POST['ownerExt'] = $matosGenerique['ownerExt'];
		
		$this->callTestedPage();
		
		$this->assertSame("Matériel sauvegardé !", $this->getOutput());
		$expectedData = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), self::$matosExterneDataset));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedData->getTable($tableName), $tableName);
		}
	}
	
	/** @test */
	public function testActionModifExterne() {
		$this->insertData(self::$matosExterneDataset);
		$matosDetail = self::$matosInterneDataset->getTable('robert_matos_detail')->getRow(0);
		$matosGenerique = self::$matosInterneDataset->getTable('robert_matos_generique')->getRow(0);
		if ($matosDetail['id'] != self::$matosExterneDataset->getTable('robert_matos_detail')->getRow(0)['id']) {
			throw new UnexpectedValueException("Configuration des valeurs attendues incorrecte");
		}
		
		$_POST['action'] = 'modif';
		$_POST['id'] = $matosDetail['id'];
		$_POST['ref'] = $matosDetail['ref'];
		$_POST['label'] = $matosDetail['label'];
		$_POST['categorie'] = $matosDetail['categorie'];
		$_POST['sousCateg'] = $matosDetail['sousCateg'];
		$_POST['tarifLoc'] = $matosDetail['tarifLoc'];
		$_POST['valRemp'] = $matosDetail['valRemp'];
		$_POST['Qtotale'] = $matosGenerique['quantite'];
		$_POST['panne'] = $matosGenerique['panne'];
		$_POST['remarque'] = $matosDetail['remarque'];
		$_POST['externe'] = '0';
		$_POST['dateAchat'] = $matosGenerique['dateAchat'];
		$_POST['ownerExt'] = self::$matosExterneDataset->getTable('robert_matos_generique')->getRow(0)['ownerExt'];
		
		$this->callTestedPage();
		
		$this->assertSame("Matériel sauvegardé !", $this->getOutput());
		$expectedData = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), self::$matosInterneDataset));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedData->getTable($tableName), $tableName);
		}
	}
	
	/** @test */
	public function testActionModifDelete() {
		$this->insertData(self::$matosInterneDataset);
		
		$_POST['action'] = 'delete';
		$_POST['id'] = self::$matosInterneDataset->getTable('robert_matos_detail')->getRow(0)['id'];
		
		$this->callTestedPage();
		
		$expectedJson = array(
				'error' => 'OK',
				'type' => 'reloadPage');
		$this->assertJsonStringEqualsJsonString(json_encode($expectedJson), $this->getOutput());
		foreach (self::$tables as $tableName) {
			$this->assertTableContents(self::$dataset->getTable($tableName), $tableName);
		}
	}
}
?>