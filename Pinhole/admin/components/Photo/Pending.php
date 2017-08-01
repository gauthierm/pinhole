<?php

/**
 * Pending photos page
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoPending extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/Photo/pending.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);

		if (!($this instanceof PinholePhotoLastUpload) &&
			count($this->getUnProcessedPhotos()) > 0)
			$this->app->relocate($this->getComponentName().'/LastUpload');

		// setup tag entry control
		$this->ui->getWidget('tags')->setApplication($this->app);
		$this->ui->getWidget('tags')->setAllTags();

		$this->ui->getWidget('passphrase_field')->visible =
			($this->app->config->pinhole->passphrase === null);

		$this->ui->getWidget('for_sale')->visible =
		$this->ui->getWidget('not_for_sale')->visible =
		$this->ui->getWidget('for_sale_divider')->visible =
			($this->app->config->clustershot->username !== null);
	}

	// }}}
	// {{{ protected function getUnProcessedPhotos()

	protected function getUnProcessedPhotos()
	{
		$instance_id = $this->app->getInstanceId();

		// load unprocessed photos (if any)
		$sql = sprintf('select PinholePhoto.* from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where PinholePhoto.status = %s and ImageSet.instance %s %s
			order by PinholePhoto.upload_date desc, PinholePhoto.id',
			$this->app->db->quote(PinholePhoto::STATUS_UNPROCESSED, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		return SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('PinholePhotoWrapper'));
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$pager = $this->ui->getWidget('pager');
		$pager->process();
	}

	// }}}
	// {{{ protected function processActions()

	/**
	 * Processes photo actions
	 *
	 * @param SwatView $view the table-view to get selected photos
	 *                 from.
	 * @param SwatActions $actions the actions list widget.
	 */
	protected function processActions(SwatView $view, SwatActions $actions)
	{
		$processor = new PinholePhotoActionsProcessor($this);
		$processor->process($view, $actions, $this->ui);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildTagsContent();
		$this->buildErrorsContent();
	}

	// }}}
	// {{{ protected function buildTagsContent()

	protected function buildTagsContent()
	{
		$sql = sprintf('select * from PinholeTag
			where PinholeTag.id in (
				select tag from PinholePhotoTagBinding
				inner join PinholePhoto on
					PinholePhoto.id = PinholePhotoTagBinding.photo
				inner join ImageSet on PinholePhoto.image_set = ImageSet.id
				where %1$s)
			and PinholeTag.id not in (
				select tag from PinholePhotoTagBinding
				where photo not in (select PinholePhoto.id from PinholePhoto
				inner join ImageSet on PinholePhoto.image_set = ImageSet.id
				where %1$s)
			)',
			$this->getWhereClause());

		$tags = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('PinholeTagDataObjectWrapper'));

		if (count($tags) == 0)
			$this->ui->getWidget('processing_tags')->classes[] =
				'swat-hidden';

		ob_start();
		foreach ($tags as $tag) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->open();
			echo SwatString::minimizeEntities($tag->title).' (';

			$a_tag = new SwatHtmlTag('a');
			$a_tag->setContent('edit');
			$a_tag->href = 'Tag/Edit?id='.$tag->id;
			$a_tag->display();
			echo ', ';

			$a_tag = new SwatHtmlTag('a');
			$a_tag->setContent('merge');
			$a_tag->href = 'Tag/Merge?id='.$tag->id;
			$a_tag->display();
			echo ', ';

			$a_tag = new SwatHtmlTag('a');
			$a_tag->setContent('delete');
			$a_tag->href = 'Tag/Delete?id='.$tag->id;
			$a_tag->display();
			echo ')';

			$div_tag->close();
		}

		$this->ui->getWidget(
			'processing_tags_content')->content = ob_get_clean();
	}

	// }}}
	// {{{ protected function buildErrorsContent()

	protected function buildErrorsContent()
	{
		$sql = sprintf('select * from PinholePhoto
			where upload_set in (
				select upload_set from PinholePhoto
				inner join ImageSet on PinholePhoto.image_set = ImageSet.id
				where %s)
			and PinholePhoto.status = %s',
			$this->getWhereClause(),
			$this->app->db->quote(PinholePhoto::STATUS_PROCESSING_ERROR,
				'integer'));

		$errors = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('PinholePhotoWrapper'));

		if (count($errors) == 0)
			$this->ui->getWidget('processing_errors')->classes[] =
				'swat-hidden';

		ob_start();
		foreach ($errors as $error) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->setContent(sprintf(Pinhole::_(
				'Error processing file %s'), $error->original_filename));

			$div_tag->display();
		}

		$this->ui->getWidget(
			'processing_errors_content')->content = ob_get_clean();
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf('select count(PinholePhoto.id) from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where %s',
			$this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = sprintf('select PinholePhoto.* from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where %s
			order by PinholePhoto.upload_date desc, PinholePhoto.id',
			$this->getWhereClause());

		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$wrapper_class = SwatDBClassMap::get('PinholePhotoWrapper');
		$photos = SwatDB::query($this->app->db, $sql, $wrapper_class);

		$tile_view = $this->ui->getWidget('index_view');
		$tile_view->check_all_unit = Pinhole::_('Pending Photos');

		$store = new SwatTableStore();

		if (count($photos) > 0) {
			$tile_view->check_all_extended_count = $pager->total_records;
			$tile_view->check_all_visible_count = count($photos);

			foreach ($photos as $photo) {
				$ds = new SwatDetailsStore();
				$ds->photo = $photo;
				$ds->class_name = $this->getTileClasses($photo);
				$store->add($ds);
			}
		}

		return $store;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$instance_id = $this->app->getInstanceId();

		return sprintf('PinholePhoto.status = %s
			and ImageSet.instance %s %s',
			$this->app->db->quote(PinholePhoto::STATUS_PENDING, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));
	}

	// }}}
	// {{{ protected function getTileClasses()

	protected function getTileClasses(PinholePhoto $photo)
	{
		$classes = array();

		if ($photo->private)
			$classes[] = 'private';

		return implode(' ', $classes);
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/admin/styles/pinhole-photo-tile.css',
			Pinhole::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/admin/styles/pinhole-photo-pending.css',
			Pinhole::PACKAGE_ID));

	}

	// }}}
}

?>
