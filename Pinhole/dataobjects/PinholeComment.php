<?php

/**
 * A comment on a Pinhole Photo
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeComment extends SiteComment
{
	// {{{ public function load()

	/**
	 * Loads this comment
	 *
	 * @param integer $id the database id of this comment.
	 * @param SiteInstance $instance optional. The instance to load the comment
	 *                                in. If the application does not use
	 *                                instances, this should be null. If
	 *                                unspecified, the instance is not checked.
	 *
	 * @return boolean true if this comment and false if it was not.
	 */
	public function load($id, SiteInstance $instance = null)
	{
		$this->checkDB();

		$loaded = false;
		$row = null;
		if ($this->table !== null && $this->id_field !== null) {
			$id_field = new SwatDBField($this->id_field, 'integer');

			$sql = sprintf('select %1$s.* from %1$s
				inner join PinholePhoto on %1$s.photo = PinholePhoto.id
				inner join ImageSet on ImageSet.id = PinholePhoto.image_set
				where %1$s.%2$s = %3$s',
				$this->table,
				$id_field->name,
				$this->db->quote($id, $id_field->type));

			$instance_id  = ($instance === null) ? null : $instance->id;
			if ($instance_id !== null) {
				$sql.= sprintf(' and ImageSet.instance %s %s',
					SwatDB::equalityOperator($instance_id),
					$this->db->quote($instance_id, 'integer'));
			}

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
	// {{{ public function setDatabase()

	/**
	 * Sets the database driver for this data-object
	 *
	 * The database is automatically set for all recordable sub-objects of this
	 * data-object.
	 *
	 * Overridden in PinholeComment to prevent infinite recursion between photos
	 * and comments.
	 *
	 * @param MDB2_Driver_Common $db the database driver to use for this
	 *                                data-object.
	 */
	public function setDatabase(MDB2_Driver_Common $db)
	{
		$this->db = $db;
		$serializable_sub_data_objects = $this->getSerializableSubDataObjects();
		foreach ($serializable_sub_data_objects as $key) {
			if ($this->hasSubDataObject($key) && $key !== 'photo') {
				$object = $this->getSubDataObject($key);
				if ($object instanceof SwatDBRecordable) {
					$object->setDatabase($db);
				}
			}
		}
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('photo',
			SwatDBClassMap::get('PinholePhoto'));

		$this->registerInternalProperty('photographer',
			SwatDBClassMap::get('PinholePhotographer'));

		$this->table = 'PinholeComment';
	}

	// }}}
	// {{{ protected function getSerializableSubDataObjects()

	protected function getSerializableSubDataObjects()
	{
		return array(
			'photo',
			'photographer',
		);
	}

	// }}}
}

?>
