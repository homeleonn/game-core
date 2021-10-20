<?php

namespace Core\DB;

class MySQL
{
    private $conn;
	private $stats;
	private $emode;
	private $exname;

    private $config = [
		'host'      => '127.0.0.1',
		'user'      => 'root',
		'pass'      => '',
		'db'        => 'test',
		'port'      => null,
		'socket'    => null,
		'pconnect'  => false,
		'lazy'  	=> true,
		'charset'   => 'utf8',
		'errmode'   => 'error',
		'exception' => 'Exception',
	];

    function __construct($opt = [])
	{
		$this->config = array_merge($this->config, $opt);
    	$this->emode  = $this->config['errmode'];
		$this->exname = $this->config['exception'];
    	if ($this->config['pconnect']) {
			$this->config['host'] = "p:".$this->config['host'];
		}
    	if (!$this->config['lazy']) {
			$this->lazyConnect();
		}
	}

    private function lazyConnect()
	{
		$this->conn = mysqli_connect($this->config['host'], $this->config['user'], $this->config['pass'], $this->config['db'], $this->config['port'], $this->config['socket']);
		if (!$this->conn) {
			$this->error(mysqli_connect_errno() . " " . mysqli_connect_error());
		}
    	mysqli_set_charset($this->conn, $this->config['charset']) or $this->error(mysqli_error($this->conn));
		return $this->conn;
	}

	public function query()
	{
		return $this->rawQuery($this->prepareQuery(func_get_args()));
	}

	public function fetch($result, $mode = MYSQLI_ASSOC)
	{
		return mysqli_fetch_object($result);
	}

	public function affectedRows()
	{
		return mysqli_affected_rows($this->conn);
	}

	public function insertId()
	{
		return mysqli_insert_id($this->conn);
	}

	public function numRows($result)
	{
		return mysqli_num_rows($result);
	}

	public function free($result)
	{
		mysqli_free_result($result);
	}

    public function get($args, $cb)
    {
        $preparedQuery = $this->prepareQuery($args);
        if (!$res = $this->rawQuery($preparedQuery)) return false;
        $result = $cb($res);
        $this->free($res);

        return $result;

    }

	public function getOne()
	{
        return $this->get(func_get_args(), function ($result) {
            return current($this->fetch($result));
        });
	}

	public function getRow()
	{
        return $this->get(func_get_args(), function ($result) {
            return $this->fetch($result);
        });
	}

	public function getCol()
	{
        return $this->get(func_get_args(), function ($result) {
            while ($row = $this->fetch($result)) {
                $return[] = current($row);
            }
            return $return;
        });
	}

	public function getAll()
	{
        return $this->get(func_get_args(), function ($result) {
            while ($row = $this->fetch($result)) {
                $return[] = $row;
            }
            return $return;
        });
	}

	public function getInd()
	{
        $args  = func_get_args();
        $index = array_shift($args);
        return $this->get($args, function ($result) use ($index) {
            while ($row = $this->fetch($result)) {
                $ind = is_object($row) ? $row->{$index} : $row[$index];
                $return[$ind] = $row;
            }
            return $return;
        });
	}

	public function getIndCol()
	{
        $args  = func_get_args();
        $index = array_shift($args);
        return $this->get($args, function ($result) use ($index) {
            while ($row = $this->fetch($result)) {
                $key = $row->{$index};
                unset($row->{$row->{$index}});
                $return[$key] = current($row);
            }
            return $return;
        });
	}

	public function parse()
	{
		return $this->prepareQuery(func_get_args());
	}

    /**
     * function to implement whitelisting feature
     * sometimes we can't allow a non-validated user-supplied data to the query even through placeholder
     * especially if it comes down to SQL OPERATORS
     *
     * Example:
     *
     * $order = $db->whiteList($_GET['order'], array('name','price'));
     * $dir   = $db->whiteList($_GET['dir'],   array('ASC','DESC'));
     * if (!$order || !dir) {
     *     throw new http404(); //non-expected values should cause 404 or similar response
     * }
     * $sql  = "SELECT * FROM table ORDER BY ?p ?p LIMIT ?i,?i"
     * $data = $db->getArr($sql, $order, $dir, $start, $per_page);
     *
     * @param string $iinput   - field name to test
     * @param  array  $allowed - an array with allowed variants
     * @param  string $default - optional variable to set if no match found. Default to false.
     * @return string|FALSE    - either sanitized value or FALSE
     */
	public function whiteList($input, $allowed, $default = false)
	{
		$found = array_search($input, $allowed);
		return ($found === false) ? $default : $allowed[$found];
	}

	public function filterArray($input,$allowed)
	{
		foreach(array_keys($input) as $key )
		{
			if (!in_array($key,$allowed)) {
				unset($input[$key]);
			}
		}

		return $input;
	}

	public function lastQuery()
	{
		return end($this->stats)['query'];
	}

	public function getStats()
	{
		return $this->stats;
	}

	private function rawQuery($query)
	{
		$start = microtime(true);
		$res   = mysqli_query($this->conn, $query);
    	$this->stats[] = [
			'query' => $query,
			'start' => $start,
			'timer' => round((microtime(true) - $start) * 1000, 2),
		];
		if (!$res) {
			$error = mysqli_error($this->conn);
			$this->stats[array_key_last($this->stats)]['error'] = $error;
    		$this->error("$error. Full query: [$query]");
		}
		$this->cutStats();

		return $res;
	}

    public function prepareQuery($args)
	{
		if (!$this->conn) $this->lazyConnect();

    	$query                 = '';
		$raw                   = array_shift($args);
		$array                 = preg_split('~(\?[nsiuap])~u', $raw, null, PREG_SPLIT_DELIM_CAPTURE);
		$argsCount             = count($args);
		$placeholdersCount     = floor(count($array) / 2);

		if ($placeholdersCount != $argsCount) {
			$this->error("Number of args ($argsCount) doesn't match number of placeholders ($placeholdersCount) in [$raw]");
		}

    	foreach ($array as $i => $part) {
			if (($i % 2) == 0) {
				$query .= $part;
				continue;
			}

    		$value = array_shift($args);

            $query .= match ($part) {
                '?n' => $this->escapeIdent($value),
                '?s' => $this->escapeString($value),
                '?i' => $this->escapeInt($value),
                '?u' => $this->createSET($value),
                '?a' => $this->createIN($value),
                '?p' => $value,
            };
		}

		return $query;
	}

    private function escapeInt($value)
	{
		if ($value === null) {
			return 'null';
		}

		if(!is_numeric($value))
		{
			$this->error("Integer (?i) placeholder expects numeric value, ".gettype($value)." given");
			return false;
		}

		if (is_float($value)) {
			$value = number_format($value, 0, '.', '');
		}

		return $value;
	}

    public function escapeString($value)
	{
		if ($value === null) {
			return 'null';
		}

		return	"'" . mysqli_real_escape_string($this->getConn(),$value) . "'";
	}

    private function escapeIdent($value)
	{
		if ($value) {
			return "`".str_replace("`","``",$value)."`";
		} else {
			$this->error("Empty value for identifier (?n) placeholder");
		}
	}

    private function createIN($data)
	{
		if (!is_array($data)) {
			$this->error("Value for IN (?a) placeholder should be an array");
			return;
		}

		if (!$data) {
			return 'null';
		}

		$query = $comma = '';

		foreach ($data as $value) {
			$query .= $comma . $this->escapeString($value);
			$comma  = ",";
		}

		return $query;
	}

    private function createSET($data)
	{
		if (!is_array($data)) {
			$this->error("SET (?u) placeholder expects array, ".gettype($data)." given");
			return;
		}

		if (!$data) {
			$this->error("Empty array for SET (?u) placeholder");
			return;
		}

		$query = $comma = '';

		foreach ($data as $key => $value) {
			$query .= $comma.$this->escapeIdent($key) . ' = ' . $this->escapeString($value);
			$comma  = ",";
		}

		return $query;
	}

    private function error($err)
	{
		$err  = __CLASS__.": ".$err;

    	if ($this->emode == 'error') {
			$err .= ". Error initiated in " . $this->caller() . ", thrown";
			trigger_error($err, E_USER_ERROR);
		} else {
			throw new $this->exname($err);
		}
	}

    private function caller()
	{
		$trace  = debug_backtrace();
		$caller = '';

		foreach ($trace as $t) {
			if (isset($t['class']) && $t['class'] == __CLASS__) {
				$caller = $t['file'] . " on line " . $t['line'];
			} else {
				break;
			}
		}

		return $caller;
	}

	private function cutStats()
	{
		if (count($this->stats) < 100) return;

        unset($this->stats[array_key_first($this->stats)]);
	}

    public function getConn()
	{
		return $this->conn ?? $this->lazyConnect();
	}
}
