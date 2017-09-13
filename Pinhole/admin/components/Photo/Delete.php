<?php

/**
 * Delete confirmation page for Photos
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoDelete extends AdminDBDelete
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		// override default xml
		$this->ui = new AdminUI();
		$this->ui->loadFromXML(__DIR__.'/delete.xml');
	}

	// }}}
	// {{{ protected function getPhotos()

	protected function getPhotos()
	{
		$item_list = $this->getItemList('integer');
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select PinholePhoto.* from PinholePhoto
				inner join ImageSet on PinholePhoto.image_set = ImageSet.id
				where ImageSet.instance %s %s',
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));

		// note the only page with an extended-selection that accesses this
		// is the pending photos page - so enforce status here.
		if ($this->extended_selected) {
			$sql.= sprintf(' and PinholePhoto.status = %s',
				$this->app->db->quote(PinholePhoto::STATUS_PENDING, 'integer'));
		} else {
			$sql.= sprintf(' and PinholePhoto.id in (%s)', $item_list);
		}

		$wrapper_class = SwatDBClassMap::get('PinholePhotoWrapper');
		return SwatDB::query($this->app->db, $sql, $wrapper_class);
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$photos = $this->getPhotos();
		$num = count($photos);

		foreach ($photos as $photo) {
			$photo->setFileBase('../../photos');
			$photo->delete();
		}

		$message = new SwatMessage(sprintf(Pinhole::ngettext(
			'One photo has been deleted.', '%s photos have been deleted.',
			$num), SwatString::numberFormat($num)));

		$this->app->messages->add($message);

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('photos');
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		// avoid the silliness in AdminDBDelete relocate that causes this to
		// always relocate to the component name 'Photo' even when we want
		// 'Photo/Pending'
		AdminConfirmation::relocate();
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		if ($this->extended_selected) {
			// note the only page with an extended-selection that accesses this
			// is the pending photos page - so the message can be more
			// specific.
			$message = $this->ui->getWidget('confirmation_message');
			$message->content_type = 'text/xml';
			$message->content = Pinhole::_('<strong>Are you sure '.
				'you want to delete all pending photos?</strong>');

		} else {
			$container = $this->ui->getWidget('confirmation_container');
			$delete_view = $this->ui->getWidget('delete_view');

			$store = new SwatTableStore();

			foreach ($this->getPhotos() as $photo) {
				$ds = new SwatDetailsStore();
				$ds->photo = $photo;
				$store->add($ds);
			}

			$delete_view->model = $store;

			$message = $this->ui->getWidget('confirmation_message');
			$message->content_type = 'text/xml';
			$message->content = sprintf(Pinhole::_('<strong>Are you sure '.
				'you want to delete the following %s?</strong>'),
				Pinhole::ngettext('photo', 'photos', count($store)));
		}
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
	}

	// }}}
}

?>
