<?php

/**
 * @package   Pinhole
 * @copyright 2007-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeBrowserIndexPage extends PinholeBrowserPage
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $page_number = 1;

	// }}}
	// {{{ public function __construct()

	public function __construct(
		SiteApplication $app,
		SiteLayout $layout = null,
		array $arguments = array()
	) {
		parent::__construct($app, $layout, $arguments);
		$this->ui_xml = 'Pinhole/pages/browser-index.xml';
	}

	// }}}
	// {{{ protected function createTagList()

	protected function createTagList($tags)
	{
		parent::createTagList($tags);

		$page_size = $this->app->config->pinhole->photos_per_page;

		$page_tags = $this->tag_list->getByType('PinholePageTag');
		if (count($page_tags) == 0) {
			$range = new SwatDBRange($page_size);
		} else {
			// get first page tag if it exists and set current page
			$page_tags->rewind();
			$page_tag = $page_tags->current();
			$range = new SwatDBRange($page_size,
				$page_size * ($page_tag->getPageNumber() - 1));

			$this->page_number = $page_tag->getPageNumber();
		}

		foreach ($page_tags as $tag)
			$this->tag_list->remove($tag);

		$this->tag_list->setPhotoRange($range);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildDateTagBrowser();
		$this->buildPhotoPagination();
		$this->buildGeoTagLink();

		$view = $this->ui->getWidget('photo_view');
		$view->model = $this->getPhotoTableStore();

		$view->getGroup('publish_period')->visible =
			(count($this->tag_list) == 0 &&
			$this->app->config->pinhole->browser_index_upload_dates);

		if (count($this->tag_list) > 0) {
			$this->layout->data->html_title.= $this->tag_list->getAsList();
		}
	}

	// }}}
	// {{{ protected function buildDateTagBrowser()

	protected function buildDateTagBrowser()
	{
		$date_tag_browser = $this->ui->getWidget('date_tag_browser');
		$date_tag_browser->setTagList($this->tag_list);
		$date_tag_browser->setDatabase($this->app->db);

		if (isset($this->app->memcache))
			$date_tag_browser->setCache($this->app->memcache);

		$date_tag_browser->base =
			$this->app->config->pinhole->path.$date_tag_browser->base;
	}

	// }}}
	// {{{ protected function buildPhotoPagination()

	protected function buildPhotoPagination()
	{
		$pagination = $this->ui->getWidget('pagination');
		$pagination->total_records = $this->tag_list->getPhotoCount();
		$pagination->page_size =
			$this->app->config->pinhole->photos_per_page;

		$pagination->setCurrentPage($this->page_number);
		if (count($this->tag_list) == 0)
			$tag_path = '';
		else
			$tag_path = $this->tag_list->__toString().'/';

		$pagination->link = $this->app->config->pinhole->path.'tag?';
		$pagination->link.= str_replace('%', '%%', $tag_path);
		$pagination->link.= 'page.number=%d';
	}

	// }}}
	// {{{ protected function buildGeoTagLink()

	protected function buildGeoTagLink()
	{
		if (!$this->ui->hasWidget('sidebar_map'))
			return;

		$map = $this->ui->getWidget('sidebar_map');
		$map->setTagList($this->tag_list);
		$map->base = $this->app->config->pinhole->path.'map';
		$map->visible = ($this->tag_list->getGeoTaggedPhotoCount() > 0);
		$map->api_key = $this->app->config->pinhole->google_maps_api_key;
	}

	// }}}
	// {{{ protected function getPhotoTableStore()

	protected function getPhotoTableStore()
	{
		if (count($this->tag_list) == 0)
			$tag_path = '';
		else
			$tag_path = '?'.$this->tag_list->__toString();

		$photos = $this->tag_list->getPhotos('thumbnail');

		// throw exception or else tags that have only private photos would be
		// exposed.
		if (count($this->tag_list->getByType('PinholeTag')) > 0 &&
			count($photos) == 0) {
			throw new SiteNotFoundException(sprintf(
				'There are no photos in the current tag intersection: %s.',
				(string)$this->tag_list));
		}

		$store = new SwatTableStore();

		foreach ($photos as $photo) {
			$ds = new SwatDetailsStore();
			$ds->root_path = $this->app->config->pinhole->path;
			$ds->path = $photo->id.$tag_path;
			$ds->photo = $photo;
			$ds->display_title =
				$this->app->config->pinhole->browser_index_titles;

			$now = new SwatDate();
			$now->convertTZById($this->app->config->date->time_zone);
			$now->setTime(0, 0, 0);

			if (count($this->tag_list) == 0) {
				$publish_date = $photo->publish_date;

				$publish_date->convertTZbyID(
					$this->app->config->date->time_zone);

				$days_past = $now->diff($publish_date)->days;
				if ($days_past <= 1)
					$period = Pinhole::_('Today');
				elseif ($days_past <= 2)
					$period = Pinhole::_('Yesterday');
				elseif ($days_past <= 7)
					$period = sprintf(Pinhole::_('%d Days Ago'),
						floor($days_past));
				else
					$period = $publish_date->formatLikeIntl(
						SwatDate::DF_DATE_LONG);

				$ds->publish_period = sprintf(Pinhole::_('Added %s'), $period);
			} else {
				$ds->publish_period = null;
			}

			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$this->ui->getWidget('content')->display();
	}

	// }}}
}

?>
