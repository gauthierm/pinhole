<?php

require_once 'Pinhole/PinholeMachineTag.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeDateTag extends PinholeMachineTag
{
	// {{{ protected properties

	protected $name_space = 'date';

	// }}}
	// {{{ public function isValid()

	public function isValid()
	{
		switch ($this->name) {
		case 'day' :
		case 'week' :
			return ($this->getDateFromString($this->value) !== null);
		case 'month' :
			$month = intval($this->value);
			return ($month > 0 && $month < 13);
		case 'year' :
			$year = intval($this->value);
			$date = new SwatDate();
			return ($year > 0 && $year <= $date->getYear());
		default :
			return false;
		}
	}

	// }}}
	// {{{ public function getTitle()

	public function getTitle()
	{
		$date = new SwatDate();

		switch ($this->name) {
		case 'day' :
			$date = $this->getDateFromString($this->value);
			return $date->format(SwatDate::DF_DATE);
		case 'month' :
			$date->setMonth($this->value);
			return $date->format('%B');
		case 'year' :
			$date->setYear($this->value);
			return $date->format('%Y');
		case 'week' :
			$date = $this->getDateFromString($this->value);
			return sprintf('Week of %s',
				$date->format(SwatDate::DF_DATE));
		}
	}

	// }}}
	// {{{ public function getWhereClause()

	public function getWhereClause()
	{
		if ($this->name == 'week' || $this->name == 'day') {
			$start_date = $this->getDateFromString($this->value);
			$end_date = clone $start_date;
			$end_date->addSeconds(86400 * (($this->name == 'day') ? 1 : 7));

			return sprintf(" photo_date >= %s and photo_date < %s",
				$this->db->quote($start_date, 'date'),
				$this->db->quote($end_date, 'date'));
		} else {
			return sprintf(' date_part(%s, photo_date) = %s',
				$this->db->quote($this->name, 'text'),
				$this->db->quote($this->value, 'date'));
		}
	}

	// }}}
	// {{{ private function getDateFromString()

	public function getDateFromString($string)
	{
		// date.weekof=06-06-2005
		// also allow 6-6-2005

		ereg('([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})',
			$string, $date_parts);

		if ($date_parts === null || count($date_parts) != 4 ||
			!checkdate($date_parts[1], $date_parts[2], $date_parts[3]))
			return null;

		$date = new SwatDate();
		$date->clearTime();
		$date->setDay($date_parts[2]);
		$date->setMonth($date_parts[1]);
		$date->setYear($date_parts[3]);

		return $date;
	}

	// }}}
}

?>
