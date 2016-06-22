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

/**
 * DataSet alimenté par un tableau en mémoire.
 * 
 * @see https://phpunit.de/manual/current/en/database.html#database.array-dataset
 */
class ArrayDataSet extends PHPUnit_Extensions_Database_DataSet_AbstractDataSet
{
	/** @var array */
	protected $tables = array();
	
	/**
	 * @param array $data Tableau associatif à 3 niveaux d'imbrication contenant les données du DataSet.
	 *      * Les clés du premier niveau correspondent au nom des tables.
	 *      * Le second niveau contient la liste des lignes de la table courante (les clés ne sont pas nommées).
	 *      * Le troisième niveau contient pour chaque enregistrement le nom de la colonne en clé, et la valeur correspondante dans la ligne courante.
	 */
	public function __construct(array $data)
	{
		foreach ($data AS $tableName => $rows) {
			$columns = array();
			if (isset($rows[0])) {
				$columns = array_keys($rows[0]);
			}
			
			$metaData = new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData($tableName, $columns);
			$table = new PHPUnit_Extensions_Database_DataSet_DefaultTable($metaData);
			
			foreach ($rows AS $row) {
				$table->addRow($row);
			}
			$this->tables[$tableName] = $table;
		}
	}
	
	/** @see PHPUnit_Extensions_Database_DataSet_AbstractDataSet::createIterator() */
	protected function createIterator($reverse = FALSE)
	{
		return new PHPUnit_Extensions_Database_DataSet_DefaultTableIterator($this->tables, $reverse);
	}

	/** @see PHPUnit_Extensions_Database_DataSet_IDataSet::getTable() */
	public function getTable($tableName)
	{
		if (!isset($this->tables[$tableName])) {
			throw new InvalidArgumentException("La table $tableName n'existe pas.");
		}
		return $this->tables[$tableName];
	}
}
?>