<?php

class mse2FiltersHandler {
	/* @var mSearch2 $mse2 */
	public $mse2;
	/* @var modX $modx */
	public $modx;


	function __construct(mSearch2 &$mse2,array $config = array()) {
		$this->modx =& $mse2->modx;
		$this->mse2 =& $mse2;

		if (!empty($config['sortAliases']) && !is_array($config['sortAliases'])) {
			$config['sortAliases'] = $this->modx->fromJSON($config['sortAliases']);
		}
		$this->config = array_merge(array(
			'sortAliases' => array(
				'ms' => 'Data'
				,'ms_data' => 'Data'
				,'ms_product' => 'msProduct'
				,'ms_vendor' => 'Vendor'
				,'tv' => 'TV'
				,'resource' => !empty($config['class']) && strtolower($config['class']) == 'msproduct' ? 'msProduct' : 'modResource'
			)
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
	 * Retrieves values from miniShop2 Product table
	 *
	 * @param array $keys Keys of ms2 products options
	 * @param array $ids Ids of needed resources
	 *
	 * @return array Array with ms2 fields as keys and resources ids as values
	 */
	public function getMsOptionValues(array $keys, array $ids) {
		$filters = array();
		$q = $this->modx->newQuery('msProductOption');
		$q->where(array('product_id:IN' => $ids, 'key:IN' => $keys));
		$q->select('`product_id`,`key`,`value`');
		if ($q->prepare() && $q->stmt->execute()) {
			while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
				if (isset($filters[$row['key']][$row['value']])) {
					$filters[$row['key']][$row['value']][] = $row['product_id'];
				}
				else {
					$filters[$row['key']][$row['value']] = array($row['product_id']);
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

		$results = array();
		ksort($values);
		foreach ($values as $value => $ids) {
			$results[] = array(
				'title' => $value
				,'value' => $value
				,'type' => 'default'
				,'resources' => $ids
			);
		}

		return $results;
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
		$tmp = array_keys($values);
		if (empty($values) || (count($tmp) < 2 && empty($this->config['showEmptyFilters']))) {
			return array();
		}

		sort($tmp);
		if (count($values) >= 2) {
			$min = array_shift($tmp);
			$max = array_pop($tmp);
		}
		else {
			$min = $max = $tmp[0];
		}

		return array(
			array(
				'title' => $this->modx->lexicon('mse2_filter_number_min')
				,'value' => $min
				,'type' => 'number'
				,'resources' => null
			)
			,array(
				'title' => $this->modx->lexicon('mse2_filter_number_max')
				,'value' => $max
				,'type' => 'number'
				,'resources' => null
			)
		);
	}


	/**
	 * Prepares values for filter
	 * Retrieves names of ms2 vendors and replaces ids in array keys by it
	 *
	 * @param array $values
	 *
	 * @return array Prepared values
	 */
	public function buildVendorsFilter(array $values) {
		$vendors = array_keys($values);
		if (empty($vendors) || (count($vendors) < 2 && empty($this->config['showEmptyFilters']))) {
			return array();
		}

		$results = array();
		$q = $this->modx->newQuery('msVendor', array('id:IN' => $vendors));
		$q->select('id,name');
		if ($q->prepare() && $q->stmt->execute()) {
			$vendors = array();
			while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
				$vendors[$row['id']] = $row['name'];
			}

			foreach ($values as $vendor => $ids) {
				$results[] = array(
					'title' => !isset($vendors[$vendor]) ? $this->modx->lexicon('mse2_filter_boolean_no') : $vendors[$vendor]
					,'value' => $vendor
					,'type' => 'vendor'
					,'resources' => $ids
				);
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
		if (count($values) < 2 && empty($this->config['showEmptyFilters'])) {
			return array();
		}

		$results = array();
		foreach ($values as $value => $ids) {
			$results[] = array(
				'title' => empty($value) ? $this->modx->lexicon('mse2_filter_boolean_no') : $this->modx->lexicon('mse2_filter_boolean_yes')
				,'value' => $value
				,'type' => 'boolean'
				,'resources' => $ids
			);
		}

		return $results;
	}


	/**
	 * Prepares values for filter
	 * Returns array with human-readable parents of resources
	 *
	 * @param array $values
	 *
	 * @return array Prepared values
	 */
	public function buildParentsFilter(array $values, $depth = 1) {
		if (count($values) < 2 && empty($this->config['showEmptyFilters'])) {
			return array();
		}

		ksort($values);
		$results = $parents = array();
		foreach ($values as $value => $ids) {
			$pids = array($value);
			$pids = array_merge($pids, $this->modx->getParentIds($value, $depth));

			$query = array();
			foreach ($pids as $v) {
				if (!isset($parents[$v])) {
					$query[] = $v;
				}
			}

			$q = $this->modx->newQuery('modResource', array('id:IN' => $query));
			$q->select('id,pagetitle');
			if ($q->prepare() && $q->stmt->execute()) {
				while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
					$parents[$row['id']] = $row['pagetitle'];
				}
			}

			$pids = array_reverse($pids);
			$title = '';
			foreach ($pids as $v) {
				if ($v == 0) {}
				else if (isset($parents[$v])) {
					$title .= ' / '.$parents[$v];
				}
				else {
					$title .= ' / '.$v;
				}
			}

			$title = !empty($title) ? substr($title, 3) : '/';
			$results[$title] = array(
				'title' => $title
				,'value' => $value
				,'type' => 'parents'
				,'resources' => $ids
			);

		}

		ksort($results);
		return $results;
	}


	/**
	 * Returns string for insert into sorting properties of pdoTools snippet
	 *
	 * @param string
	 *
	 * @return string
	 */
	public function getSortFields($sort) {
		$data = array();

		$sort = explode(',', strtolower(trim($sort)));
		foreach ($sort as $string) {
			$table = 'resource';
			$order = 'asc';

			$tmp = explode($this->config['filter_delimeter'], $string);
			if (!empty($tmp[1])) {
				$table = $tmp[0];
				$field = $tmp[1];
			}
			else {
				$field = $tmp[0];
			}

			$tmp = explode($this->config['method_delimeter'], $field);
			if (!empty($tmp[1])) {
				$field = $tmp[0];
				$order = $tmp[1];
			}

			if (isset($this->config['sortAliases'][$table])) {
				if ($table == 'tv') {
					$table = $this->config['sortAliases'][$table].$field;
					$field = 'value';
				}
				else {
					$table = $this->config['sortAliases'][$table];
				}
			}
			else {
				$table = $this->config['sortAliases']['resource'];
			}

			$data[] = "`$table`.`$field` $order";
		}

		return implode(',', $data);
	}


	/**
	 * Default filtration method
	 *
	 * @param array $requested Filtered ids of resources
	 * @param array $values Filter data with value and ids of matching resources
	 * @param array $ids Ids of currently active resources
	 *
	 * @return array
	 */
	public function filterDefault(array $requested, array $values, array $ids) {
		$matched = array();

		foreach ($requested as $value) {
			if (isset($values[$value])) {
				$resources = $values[$value];
				foreach ($resources as $id) {
					if (in_array($id, $ids)) {
						$matched[] = $id;
					}
				}
			}
		}

		return $matched;
	}


	/**
	 * Filters numbers. Values must be between min and max number
	 *
	 * @param array $requested Filtered ids of resources
	 * @param array $values Filter data with min and max number
	 * @param array $ids Ids of currently active resources
	 *
	 * @return array
	 */
	public function filterNumber(array $requested, array $values, array $ids) {
		$matched = array();

		sort($requested);
		$min = array_shift($requested);
		$max = array_pop($requested);

		foreach ($values as $number => $resources) {
			if ($number >= $min && $number <= $max) {
				foreach ($resources as $id) {
					if (in_array($id, $ids)) {
						$matched[] = $id;
					}
				}
			}
		}

		return $matched;
	}

}