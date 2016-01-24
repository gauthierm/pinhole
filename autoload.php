<?php

namespace Silverorange\Autoloader;

$package = new Package('silverorange/pinhole');

$package->addRule(new Rule('exceptions', 'Pinhole', 'Exception');
$package->addRule(new Rule('gadgets', 'Pinhole', 'Gadget'));
$package->addRule(new Rule('layouts', 'Pinhole', 'Layout');
$package->addRule(new Rule('pages', 'Pinhole', array('Page', 'Server')));
$package->addRule(new Rule('tags', 'Pinhole', 'Tag'));
$package->addRule(new Rule('views', 'Pinhole', 'View'));

$package->addRule(
	new Rule(
		'dataobjects',
		'Pinhole',
		array(
			'Binding',
			'Wrapper',
			'AdminUser',
			'Comment',
			'ImageDimension',
			'ImageSet',
			'InstanceDataObject',
			'MetaData',
			'Photographer',
			'Photo',
			'PhotoUploadSet',
			'TagDataObject',
		)
	)
);

$package->addRule(new Rule('', 'Pinhole'));

Autoloader::addPackage($package);

?>
