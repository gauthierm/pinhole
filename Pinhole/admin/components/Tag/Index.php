<?php

/**
 * Search page for tags
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagIndex extends AdminSearch
{
	// {{{ protected properties

	protected $ui_xml = __DIR__.'/index.xml';

	protected $where_clause;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Pinhole', 'Pinhole');
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$pager = $this->ui->getWidget('pager');
		$pager->process();

		if ($pager->getCurrentPage() > 0) {
			$disclosure = $this->ui->getWidget('search_disclosure');
			$disclosure->open = false;
		}
	}

	// }}}
	// {{{ protected function processActions()

	/**
	 * Processes photographer actions
	 *
	 * @param SwatTableView $view the table-view to get selected tags
	 *                             from.
	 * @param SwatActions $actions the actions list widget.
	 */
	protected function processActions(SwatView $view, SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage($this->getComponentName().'/Delete');
			$this->app->getPage()->setItems($view->checked_items,
				$view->getColumn('checkbox')->isExtendedCheckAllSelected());

			break;

		case 'archive':
			$num = count($view->checked_items);

			$sql = sprintf('update PinholeTag set archived = true
				where PinholeTag.instance = %s and PinholeTag.id in(%s)',
				$this->app->db->quote($this->app->getInstanceId(), 'integer'),
				SwatDB::implodeSelection($this->app->db,
					$view->getSelection()));

			SwatDB::exec($this->app->db, $sql);

			$message = new SwatMessage(sprintf(Pinhole::ngettext(
				'One tag has been archived.',
				'%s tags have been archived.', $num),
				SwatString::numberFormat($num)));

			$this->app->messages->add($message);
			break;

		case 'unarchive':
			$num = count($view->checked_items);

			$sql = sprintf('update PinholeTag set archived = false
				where PinholeTag.instance = %s and PinholeTag.id in(%s)',
				$this->app->db->quote($this->app->getInstanceId(), 'integer'),
				SwatDB::implodeSelection($this->app->db,
					$view->getSelection()));

			SwatDB::exec($this->app->db, $sql);

			$message = new SwatMessage(sprintf(Pinhole::ngettext(
				'One tag has been set as not archived.',
				'%s tags have been set as not archived.', $num),
				SwatString::numberFormat($num)));

			$this->app->messages->add($message);
			break;

		case 'event':
			$num = count($view->checked_items);

			$sql = sprintf('update PinholeTag set event = true
				where PinholeTag.instance = %s and PinholeTag.id in(%s)',
				$this->app->db->quote($this->app->getInstanceId(), 'integer'),
				SwatDB::implodeSelection($this->app->db,
					$view->getSelection()));

			SwatDB::exec($this->app->db, $sql);

			$message = new SwatMessage(sprintf(Pinhole::ngettext(
				'One tag has been set as an event.',
				'%s tags have been set as events.', $num),
				SwatString::numberFormat($num)));

			$this->app->messages->add($message);
			break;

		case 'unevent':
			$num = count($view->checked_items);

			$sql = sprintf('update PinholeTag set event = false
				where PinholeTag.instance = %s and PinholeTag.id in(%s)',
				$this->app->db->quote($this->app->getInstanceId(), 'integer'),
				SwatDB::implodeSelection($this->app->db,
					$view->getSelection()));

			SwatDB::exec($this->app->db, $sql);

			$message = new SwatMessage(sprintf(Pinhole::ngettext(
				'One tag has been set as not an event.',
				'%s tags have been set as not events.', $num),
				SwatString::numberFormat($num)));

			$this->app->messages->add($message);
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildComponentTitlesAndLinks();
	}

	// }}}
	// {{{ protected function buildComponentTitlesAndLinks()

	protected function buildComponentTitlesAndLinks()
	{
		$this->ui->getWidget('search_disclosure')->title =
			'Search '.$this->getComponentTitle();

		$this->ui->getWidget('results_frame')->title =
			$this->getComponentTitle();

		$this->ui->getWidget('tag_tool_link')->link =
			$this->getComponentName().'/Edit';

		$title_column = $this->ui->getWidget('index_view')->getColumn('title');
		$title_column->getFirstRenderer()->link =
			$this->getComponentName().'/Details?id=%s';

		$this->ui->getWidget('pager')->link = $this->getComponentName();
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		if ($this->where_clause === null) {
			$instance_id = $this->app->getInstanceId();

			$this->where_clause = sprintf('PinholeTag.instance %s %s',
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));

			$clause = new AdminSearchClause('title');
			$clause->table = 'PinholeTag';
			$clause->value = $this->ui->getWidget('search_title')->value;
			$clause->operator =
				$this->ui->getWidget('search_title_operator')->value;

			$this->where_clause.= $clause->getClause($this->app->db, 'and');

			$clause = new AdminSearchClause('boolean:archived');
			$clause->table = 'PinholeTag';
			$clause->value = $this->ui->getWidget('search_archived')->value;
			$this->where_clause.= $clause->getClause($this->app->db, 'and');
		}

		return $this->where_clause;
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$store = new SwatTableStore();

		$sql = 'select count(id) from PinholeTag';
		$where_clause = $this->getWhereClause();
		if ($where_clause != '')
			$sql.= ' where '.$this->getWhereClause();

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = 'select PinholeTag.* from PinholeTag';
		$where_clause = $this->getWhereClause();
		if ($where_clause != '')
			$sql.= ' where '.$this->getWhereClause();

		$sql.= ' order by '.$this->getOrderByClause($view, 'PinholeTag.title');
		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$wrapper_class = SwatDBClassMap::get('PinholeTagDataObjectWrapper');
		$data_objects = SwatDB::query($this->app->db, $sql, $wrapper_class);

		if (count($data_objects) > 0) {
			$this->ui->getWidget('results_frame')->visible = true;
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(Pinhole::_('result'),
					Pinhole::_('results'));

			$class_name = SwatDBClassMap::get('PinholeTag');
			foreach ($data_objects as $data_object) {
				$tag = new $class_name($this->app->getInstance(),
					$data_object);

				$store->add($tag);
			}
		}

		$index_view = $this->ui->getWidget('index_view');
		$checkbox_column = $index_view->getColumn('checkbox');
		$checkbox_column->check_all_visible_count = count($data_objects);
		$checkbox_column->check_all_extended_count = $pager->total_records;
		$checkbox_column->check_all_unit = Pinhole::_('tags');

		return $store;
	}

	// }}}
}

?>
