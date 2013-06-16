<?php

class mse2FiltersHandler {
	/* @var mSearch2 $mse2 */
	public $mse2;
	/* @var modX $modx */
	public $modx;


	function __construct(mSearch2 &$mse2,array $config = array()) {
		$this->modx =& $mse2->modx;
		$this->mse2 =& $mse2;

		$this->config = array_merge(array(

		), $config);
	}


	/**
	 * Retrieves values from Template Variables table
	 *
	 * @param array $tvs Names of tvs
	 * @param array $ids Ids of needed resources
	 *
	 * @return array Array with tvs values as keys and resources ids as values
	 */
	public function getTvValues(array $tvs, array $ids) {
		$filters = array();
		$q = $this->modx->newQuery('modTemplateVarResource');
		$q->innerJoin('modTemplateVar', 'modTemplateVar', '`modTemplateVarResource`.`tmplvarid` = `modTemplateVar`.`id` AND `modTemplateVar`.`name` IN ("' . implode('","', $tvs).'")');
		$q->where(array('`modTemplateVarResource`.`contentid`:IN' => $ids));
		$q->select('`name`,`contentid`,`value`');
		if ($q->prepare() && $q->stmt->execute()) {
			while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
				if (isset($filters[$row['name']][$row['value']])) {
					$filters[$row['name']][$row['value']][] = $row['contentid'];
				}
				else {
					$filters[$row['name']][$row['value']] = array($row['contentid']);
				}
			}
		}
		else {
			$this->modx->log(modX::LOG_LEVEL_ERROR, "[mSearch2] Error on get filter params.\nQuery: ".$q->toSql()."\nResponse: ".print_r($q->stmt->errorInfo(),1));
		}

		return $filters;
	}


	/**
	 * Retrieves values from miniShop2 Product table
	 *
	 * @param array $fields Names of ms2 fields
	 * @param array $ids Ids of needed resources
	 *
	 * @return array Array with ms2 fields as keys and resources ids as values
	 */
	public function getMsValues(array $fields, array $ids) {
		$filters = array();
		$q = $this->modx->newQuery('msProductData');
		$q->where(array('id:IN' => $ids));
		$q->select('id,' . implode(',', $fields));
		if ($q->prepare() && $q->stmt->execute()) {
			while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
				foreach ($row as $k => $v) {
					if ($v == '' || $k == 'id') {continue;}
					else if (isset($filters[$k][$v])) {
						$filters[$k][$v][] = $row['id'];
					}
					else {
						$filters[$k][$v] = array($row['id']);
					}

				}
			}
		}
		else {
			$this->modx->log(modX::LOG_LEVEL_ERROR, "[mSearch2] Error on get filter params.\nQuery: ".$q->toSql()."\nResponse: ".print_r($q->stmt->errorInfo(),1));
		}

		return $filters;
	}


	/**
	 * Retrieves values from Resource table
	 *
	 * @param array $fields Names of resource fields
	 * @param array $ids Ids of needed resources
	 *
	 * @return array Array with resource fields as keys and resources ids as values
	 */
	public function getResourceValues(array $fields, array $ids) {
		$filters = array();
		$q = $this->modx->newQuery('modResource');
		$q->where(array('id:IN' => $ids));
		$q->select('id,' . implode(',', $fields));
		if ($q->prepare() && $q->stmt->execute()) {
			while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
				foreach ($row as $k => $v) {
					if ($v == '' || $k == 'id') {continue;}
					else if (isset($filters[$k][$v])) {
						$filters[$k][$v][] = $row['id'];
					}
					else {
						$filters[$k][$v] = array($row['id']);
					}

				}
			}
		}
		else {
			$this->modx->log(modX::LOG_LEVEL_ERROR, "[mSearch2] Error on get filter params.\nQuery: ".$q->toSql()."\nResponse: ".print_r($q->stmt->errorInfo(),1));
		}

		return $filters;
	}


	/**
	 * Prepares values for filter
	 * Sorts and returns given values
	 *
	 * @param array $values
	 *
	 * @return array Prepared values
	 */
	public function buildDefaultFilter(array $values) {
		if (count($values) < 2 && empty($this->config['showEmptyFilters'])) {
			return array();
		}
		ksort($values);
		return $values;
	}


	/**
	 * Prepares values for filter
	 * Returns array with minimum and maximum value
	 *
	 * @param array $values
	 *
	 * @return array Prepared values
	 */
	public function buildNumberFilter(array $values) {
		$values = array_keys($values);

		if (empty($values) || (count($values) < 2 && empty($this->config['showEmptyFilters']))) {
			return array();
		}

		sort($values);
		if (count($values) >= 2) {
			return array(
				'min' => array_shift($values)
				,'max' => array_pop($values)
			);
		}
		else {
			return array(
				'min' => $values[0]
				,'max' => $values[0]
			);
		}
	}


	/**
	 * Prepares values for filter
	 * Retrieves names of ms2 vendors and replaces ids in array keys by it
	 *
	 * @param array $values
	 *
	 * @return array Prepared values
	 */
	public function buildVendorFilter(array $values) {
		$vendors = array_keys($values);
		$results = array();
		if (empty($vendors) || (count($vendors) < 2 && empty($this->config['showEmptyFilters']))) {
			return array();
		}

		$q = $this->modx->newQuery('msVendor', array('id:IN' => $vendors));
		$q->select('id,name');
		if ($q->prepare() && $q->stmt->execute()) {
			$vendors = array();
			while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
				$vendors[$row['id']] = $row['name'];
			}

			foreach ($values as $vendor => $ids) {
				if (!isset($vendors[$vendor])) {
					$vendor = $this->modx->lexicon('mse2_filter_boolean_no');
				}
				else {
					$vendor = $vendors[$vendor];
				}

				$results[$vendor] = $ids;
			}
		}

		return $results;
	}


	/**
	 * Prepares values for filter
	 * Returns array with human-readable keys "yes" and "no"
	 *
	 * @param array $values
	 *
	 * @return array Prepared values
	 */
	public function buildBooleanFilter(array $values) {
		$results = array();
		if (count($values) < 2 && empty($this->config['showEmptyFilters'])) {
			return array();
		}

		foreach ($values as $key => $ids) {
			$key = empty($key) ? $this->modx->lexicon('mse2_filter_boolean_no') : $this->modx->lexicon('mse2_filter_boolean_yes');
			$results[$key] = $ids;
		}

		return $results;
	}

}