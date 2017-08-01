<?php

/**
 * Edit page for tags
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $ui_xml = __DIR__.'/edit.xml';

	/**
	 * @var PinholeTag
	 */
	protected $tag;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->initTag();

		if ($this->id === null)
			$this->ui->getWidget('name_field')->visible = false;
	}

	// }}}
	// {{{ protected function initTag()

	protected function initTag()
	{
		$class_name = SwatDBClassMap::get('PinholeTagDataObject');
		$this->tag = new $class_name();
		$this->tag->setDatabase($this->app->db);
		$this->tag->instance = $this->app->getInstance();

		if ($this->id !== null && !$this->tag->load($this->id)) {
			throw new AdminNotFoundException(
				sprintf(Pinhole::_('Tag with id “%s” not found.'), $this->id));
		}
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$name = $this->ui->getWidget('name')->value;

		if ($this->id === null) {
			$name = $this->generateShortname(
				$this->ui->getWidget('title')->value);

			$this->ui->getWidget('name')->value = $name;

		} elseif (!$this->validateShortname($name)) {
			$message = new SwatMessage(
				Pinhole::_('Tag name already exists and must be unique.'),
				'error');

			$this->ui->getWidget('name')->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($name)
	{
		$sql = 'select name from PinholeTag
			where name = %s and id %s %s and instance %s %s';

		$instance_id = $this->app->getInstanceId();

		$sql = sprintf($sql,
			$this->app->db->quote($name, 'text'),
			SwatDB::equalityOperator($this->id, true),
			$this->app->db->quote($this->id, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$query = SwatDB::query($this->app->db, $sql);

		return (count($query) == 0);
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array(
			'title', 'name', 'event', 'archived'));

		$this->tag->title    = $values['title'];
		$this->tag->name     = $values['name'];
		$this->tag->event    = $values['event'];
		$this->tag->archived = $values['archived'];

		if ($this->id === null) {
			$now = new SwatDate();
			$this->tag->createdate = $now->getDate();
		}

		$flush_cache = ($this->tag->isModified() && $this->tag->id !== null);

		$this->tag->save();
		$this->addToSearchQueue();

		if (isset($this->app->memcache) && $flush_cache)
			$this->app->memcache->flushNs('photos');

		$message = new SwatMessage(
			sprintf(Pinhole::_('“%s” has been saved.'), $this->tag->title));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		$type = NateGoSearch::getDocumentType($this->app->db, 'tag');

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->tag->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->tag->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->tag));
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		if ($this->id !== null) {
			$edit = $this->navbar->popEntry();
			$this->navbar->addEntry(new SwatNavBarEntry($this->tag->title,
				$this->getComponentName().'/Details?id='.$this->id));

			$this->navbar->addEntry($edit);
		}
	}

	// }}}
}

?>
