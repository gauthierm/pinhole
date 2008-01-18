<?php

require_once 'Pinhole/dataobjects/PinholeInstanceDataObject.php';

/**
 * A dataobject for the meta-data contained in photos
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeMetaData extends PinholeInstanceDataObject
{
	// {{{ public properties

	/**
	 * 
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * 
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * 
	 *
	 * @var string
	 */
	public $title;

	/**
	 * default false,
	 *
	 * @var boolean
	 */
	public $show;

	/**
	 * default false,
	 *
	 * @var boolean
	 */
	public $machine_tag;

	// }}}
	// {{{ public function loadFromShortname()

	/**
	 * Loads a meta-data data-object by its shortname
	 *
	 * An instance is required to load this object by shortname since meta-data
	 * shortnames are not required to be unique across site instances.
	 *
	 * @param string $shortname the shortname of the meta-data data-object to
	 *                           load.
	 * @param SiteInstance $instance the site instance of the meta-data
	 *                                   data-object to load.
	 *
	 * @return boolean true if this meta-data data-object was loaded and false
	 *                  if it could not be loaded.
	 */
	public function loadFromShortname($shortname, SiteInstance $instance)
	{
		$this->instance = $instance;

		$row = null;
		$loaded = false;

		if ($this->table !== null) {
			$sql = sprintf('select * from %s where shortname = %s
				and instance %s %s',
				$this->table,
				$this->db->quote($shortname, 'text'),
				SwatDB::equalityOperator($instance->getId()),
				$this->db->quote($instance->getId(), 'integer'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$loaded = true;
		}

		return $loaded;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->table = 'PinholeMetaData';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
