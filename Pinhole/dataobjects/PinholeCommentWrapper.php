<?php

/**
 * A recordset wrapper class for PinholeComment objects
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @see       SiteCommentWrapper
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCommentWrapper extends SiteCommentWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('PinholeComment');
	}

	// }}}
}

?>
