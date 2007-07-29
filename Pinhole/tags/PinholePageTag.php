<?php

require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/tags/PinholeAbstractMachineTag.php';
require_once 'Pinhole/tags/PinholeIterableTag.php';

class PinholePageTag extends PinholeAbstractMachineTag
	implements PinholeIterableTag
{
	/**
	 * The namespace of the page machine tag
	 */
	const NAMESPACE = 'page';

	/**
	 * Name of this page tag
	 *
	 * Should be 'number'.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Value of this page tag
	 *
	 * @var string
	 */
	private $value;

	/**
	 * Parses this page tag from a tag string
	 *
	 * @param string $string the tag string to parse. 
	 * @param MDB2_Driver_Common $db the database connection used to parse the
	 *                            tag string.
	 *
	 * @return boolean true if the tag string could be parsed and false if the
	 *                  tag string could not be parsed.
	 */
	public function parse($string, MDB2_Driver_Common $db)
	{
		$this->setDatabase($db);

		$parts = $this->getParts($string);
		if (count($parts) > 0 &&
			$this->isValid($parts['name'], $parts['value'])) {

			$this->name =  $parts['name'];
			$this->value = $parts['value'];

			$valid = true;
		} else {
			$valid = false;
		}

		return $valid;
	}

	/**
	 * Gets the title of this page tag
	 *
	 * @return string the title of this page tag.
	 */
	public function getTitle()
	{
		switch ($this->name) {
		case 'number':
			$title = sprintf(Pinhole::_('Page %s'), $this->value);
			break;

		default:
			$title = Pinhole::_('Unknown Date');
			break;
		}

		return $title;
	}

	/**
	 * Applies this tag to a photo
	 *
	 * Since page tags cannot be applied to photos, this method does nothing.
	 *
	 * @param PinholePhoto $photo the photo this tag is to be applied to.
	 */
	public function applyToPhoto(PinholePhoto $photo)
	{
		// do nothing since page tags cannot be applied to photos
	}

	/**
	 * Checks whether or not this page tag applies to a given photo
	 *
	 * Page tags never apply to photos.
	 *
	 * @param PinholePhoto the photo to check.
	 *
	 * @return boolean false.
	 */
	public function appliesToPhoto(PinholePhoto $photo)
	{
		return false;
	}

	public function getPageNumber()
	{
		return $this->value;
	}

	/**
	 * Gets the next tag after this tag
	 *
	 * @return PinholePageTag the next tag after this tag or null if there is
	 *                         no next tag.
	 */
	public function next()
	{
		$returned_tag = null;

		switch ($this->name) {
		case 'number':
			$value = intval($this->value) - 1;
			break;

		default:
			$value = null;
			break;
		}

		if ($value !== null) {
			$string = sprintf('%s.%s=%s', self::NAMESPACE, $this->name, $value);
			$tag = new PinholeDateTag();
			if ($tag->parse($string, $this->db) !== false) {
				$returned_tag = $tag;
			}
		}

		return $returned_tag;
	}

	/**
	 * Gets the previous tag before this tag
	 *
	 * @return PinholePageTag the previous tag before this tag or null if there
	 *                         is no previous tag.
	 */
	public function prev()
	{
		$returned_tag = null;

		switch ($this->name) {
		case 'number':
			$value = intval($this->value) + 1;
			break;

		default:
			$value = null;
			break;
		}

		if ($value !== null) {
			$string = sprintf('%s.%s=%s', self::NAMESPACE, $this->name, $value);
			$tag = new PinholePageTag();
			if ($tag->parse($string, $this->db) !== false) {
				$returned_tag = $tag;
			}
		}

		return $returned_tag;
	}

	/**
	 * Gets the namespace of this page tag
	 *
	 * @return string the namespace of this page tag.
	 */
	protected function getNamespace()
	{
		return self::NAMESPACE;
	}

	/**
	 * Gets the name of this page tag
	 *
	 * @return string the name of this page tag.
	 */
	protected function getName()
	{
		return $this->name;
	}

	/**
	 * Gets the value of this page tag
	 *
	 * @return string the value of this page tag.
	 */
	protected function getValue()
	{
		return $this->value;
	}

	/**
	 * Whether or not a name-value pair is valid for this page tag
	 *
	 * @param string $name the name.
	 * @param string $value the value.
	 *
	 * @return boolean true if the name-value pair is valid for this page tag
	 *                  and false if the name-value pair is not valid for this
	 *                  page tag.
	 */
	private function isValid($name, $value)
	{
		$valid = true;
		if ($name != 'number')
			$valid = false;
		
		if (!ctype_digit($value))
			$valid = false;

		if (intval($value) < 0)
			$valid = false;

		return $valid;
	}
}

?>