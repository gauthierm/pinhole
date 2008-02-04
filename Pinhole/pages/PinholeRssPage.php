<?php

require_once 'Swat/SwatUI.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatImageDisplay.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/exceptions/SwatWidgetNotFoundException.php';
require_once 'Site/pages/SitePage.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/PinholeTagList.php';
require_once 'Pinhole/layouts/PinholeRssLayout.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeRssPage extends SitePage
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout)
	{
		$layout = new PinholeRssLayout($app, 'Pinhole/layouts/xhtml/rss.php');

		parent::__construct($app, $layout);

		$tags = SiteApplication::initVar('tags');
		$this->createTagList($tags);
	}

	// }}}
	// {{{ protected function createTagList()

	protected function createTagList($tags)
	{
		$this->tag_list = new PinholeTagList($this->app->db,
			$this->app->instance->getInstance(), $tags);
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->tag_list->setPhotoRange(new SwatDBRange(50));

		$this->tag_list->setPhotoWhereClause(sprintf(
			'PinholePhoto.status = %s',
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer')));

		$this->tag_list->setPhotoOrderByClause(
			'PinholePhoto.publish_date desc, id desc');
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->startCapture('feed');
		$this->displayFeed();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function displayFeed()

	protected function displayFeed()
	{
		$photos = $this->tag_list->getPhotos();

		foreach ($photos as $photo) {

			echo '<item>';

			printf('<title>%s</title>',
				SwatString::minimizeEntities($photo->title));

			$uri = sprintf('%sphoto/%s',
				$this->app->getBaseHref(),
				$photo->id);

			if (count($this->tag_list) > 0)
				$uri.= '?'.$this->tag_list->__toString();

			printf('<link>%s</link>', $uri);

			echo "<content:encoded><![CDATA[\n";
			$this->displayContent($photo);
			echo ']]></content:encoded>';


			printf('<guid>%stag/photo/%s</guid>',
				$this->app->getBaseHref(),
				$photo->id);

			$date = ($photo->photo_date === null) ? new SwatDate() :
				$photo->photo_date;

			$date->convertTZbyID($photo->photo_time_zone);

			printf('<dc:date>%s</dc:date>',
				$date->format('%Y-%m-%dT%H:%M:%S%O'));

			printf('<dc:creator>%s</dc:creator>',
				''); //TODO: populate this with photographer

			echo '</item>';
		}
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent($photo)
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->open();

		$img = $photo->getImgTag('large');
		$img->src = $this->app->getBaseHref().$img->src;
		$img->display();

		if ($photo->description !== null) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->setContent($photo->description, 'text/xml');
			$div_tag->display();
		}

		$div_tag->close();
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
	}

	// }}}
}

?>
