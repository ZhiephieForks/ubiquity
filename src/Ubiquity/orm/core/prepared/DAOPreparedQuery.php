<?php
namespace Ubiquity\orm\core\prepared;

use Ubiquity\db\SqlUtils;
use Ubiquity\orm\DAO;
use Ubiquity\orm\OrmUtils;
use Ubiquity\orm\parser\ConditionParser;

/**
 * Ubiquity\orm\core\prepared$DAOPreparedQuery
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.0.1
 *
 */
abstract class DAOPreparedQuery {

	protected $databaseOffset;

	/**
	 *
	 * @var ConditionParser
	 */
	protected $conditionParser;

	protected $included;

	protected $hasIncluded;

	protected $useCache;

	protected $className;

	protected $tableName;

	protected $invertedJoinColumns = null;

	protected $oneToManyFields = null;

	protected $manyToManyFields = null;

	protected $transformers;

	protected $propsKeys;

	protected $accessors;

	protected $fieldList;

	protected $firstPropKey;

	protected $condition;

	protected $preparedCondition;

	protected $loadObjectFromRowCallback;

	/**
	 *
	 * @var \Ubiquity\db\Database
	 */
	protected $db;

	public function __construct($className, $condition = null, $included = false) {
		$this->className = $className;
		$this->included = $included;
		$this->condition = $condition;
		$this->conditionParser = new ConditionParser($condition);
		$this->prepare();
		$this->preparedCondition = SqlUtils::checkWhere($this->conditionParser->getCondition());
		if ($this->hasIncluded) {
			$this->loadObjectFromRowCallback = function ($db, $row, $className, &$invertedJoinColumns, &$manyToOneQueries, &$oneToManyFields, &$manyToManyFields, &$oneToManyQueries, &$manyToManyParsers, &$accessors, &$transformers) {
				return DAO::_loadObjectFromRow($db, $row, $className, $invertedJoinColumns, $manyToOneQueries, $oneToManyFields, $manyToManyFields, $oneToManyQueries, $manyToManyParsers, $accessors, $transformers);
			};
		} else {
			$this->loadObjectFromRowCallback = function ($db, $row, $className, &$invertedJoinColumns, &$manyToOneQueries, &$oneToManyFields, &$manyToManyFields, &$oneToManyQueries, &$manyToManyParsers, &$accessors, &$transformers) {
				return DAO::_simpleLoadObjectFromRow($db, $row, $className, $accessors, $transformers);
			};
		}
	}

	public function getFirstPropKey() {
		return $this->firstPropKey;
	}

	/**
	 *
	 * @return \Ubiquity\db\Database
	 */
	public function getDb() {
		return $this->db;
	}

	/**
	 *
	 * @return mixed
	 */
	public function getDatabaseOffset() {
		return $this->databaseOffset;
	}

	/**
	 *
	 * @return \Ubiquity\orm\parser\ConditionParser
	 */
	public function getConditionParser() {
		return $this->conditionParser;
	}

	/**
	 *
	 * @return mixed
	 */
	public function getIncluded() {
		return $this->included;
	}

	/**
	 *
	 * @return boolean
	 */
	public function getHasIncluded() {
		return $this->hasIncluded;
	}

	/**
	 *
	 * @return mixed
	 */
	public function getUseCache() {
		return $this->useCache;
	}

	/**
	 *
	 * @return mixed
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 *
	 * @return mixed
	 */
	public function getTableName() {
		return $this->tableName;
	}

	/**
	 *
	 * @return mixed
	 */
	public function getInvertedJoinColumns() {
		return $this->invertedJoinColumns;
	}

	/**
	 *
	 * @return mixed
	 */
	public function getOneToManyFields() {
		return $this->oneToManyFields;
	}

	/**
	 *
	 * @return mixed
	 */
	public function getManyToManyFields() {
		return $this->manyToManyFields;
	}

	public function getTransformers() {
		return $this->transformers;
	}

	public function getPropsKeys() {
		return $this->propsKeys;
	}

	/**
	 *
	 * @return mixed
	 */
	public function getAccessors() {
		return $this->accessors;
	}

	public function getFieldList() {
		return $this->fieldList;
	}

	protected function prepare() {
		$this->db = DAO::getDb($this->className);
		$this->included = DAO::_getIncludedForStep($this->included);

		$metaDatas = OrmUtils::getModelMetadata($this->className);
		$this->tableName = $metaDatas['#tableName'];
		$this->hasIncluded = $this->included || (\is_array($this->included) && \sizeof($this->included) > 0);
		if ($this->hasIncluded) {
			DAO::_initRelationFields($this->included, $metaDatas, $this->invertedJoinColumns, $this->oneToManyFields, $this->manyToManyFields);
		}
		$this->transformers = $metaDatas['#transformers'][DAO::$transformerOp] ?? [];
		$this->fieldList = DAO::_getFieldList($this->tableName, $metaDatas);
		$this->propsKeys = OrmUtils::getPropKeys($this->className);
		$this->accessors = $metaDatas['#accessors'];
		$this->firstPropKey = OrmUtils::getFirstPropKey($this->className);
	}

	abstract public function execute($params = [], $useCache = false);
}

