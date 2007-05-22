<?php

require_once 'Pinhole/exceptions/PinholeException.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeMachineTag
{
	// {{{ protected properties

	protected $name_space;
	protected $db = null;
	protected $name;
	protected $value;

	// }}}
	// {{{ public function __construct()

	public function __construct($db, $name, $value)
	{
		if ($this->name_space === null)
			throw new PinholeException('A machine tag must
				have a name space defined');

		$this->db = $db;
		$this->name = $name;
		$this->value = $value;
	}

	// }}}
	// {{{ public function isValid()

	public function isValid()
	{
		return true;
	}

	// }}}
	// {{{ public function getPath()

	public function getPath()
	{
		return sprintf('%s.%s=%s',
			$this->name_space,
			$this->name,
			urlencode($this->value));
	}

	// }}}
	// {{{ public function getJoinClause()

	public function getJoinClause() {
		return '';
	}

	// }}}
	// {{{ public function getWhereClause()

	public function getWhereClause($table_name = 'PinholePhoto')
	{
		return null;
	}

	// }}}
	// {{{ public function getTitle()

	public function getTitle()
	{
		return null;
	}

	// }}}
}

?>
