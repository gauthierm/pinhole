<?php

require_once __DIR__.'/vendor/autoload.php';

$dsn = 'pgsql://php@192.168.0.26/gallery?sslmode=disable';
$connection = MDB2::connect($dsn);

$instance = new PinholeInstance();
$instance->setDatabase($connection);
$instance->load(1);

PinholeTagFactory::setDefaultDatabase($connection);
PinholeTagFactory::setDefaultInstance($instance);

function test_tag($string)
{
	$tag = PinholeTagFactory::get($string);
	if ($tag) {
		echo "=> ", $tag, ': "', $tag->getTitle(), "\"\n";
		echo "   Photos: ";
		foreach ($tag->getPhotos() as $photo) {
			echo $photo->id, ' ';
		}
		echo "\n";
		if ($tag instanceof PinholeIterableTag) {
			echo "   Iterable: ", $tag->prev()->getTitle(),
				" <=> ", $tag->next()->getTitle(), "\n";
		}
	} else {
		echo "=> {$string}: *** error loading tag ***\n";
	}
}

function test_tag_list($tag_list)
{
	echo 'List: ', $tag_list, "\n";

	echo "iterating list:\n";
	foreach ($tag_list as $key => $tag) {
		echo '=> ', $key, ' => ', $tag->getTitle(), "\n";
	}

	echo "\n", $tag_list->getPhotoCount(), " photos in tag list:\n";

	foreach ($tag_list->getPhotos() as $photo) {
		echo '=> ', $photo->id, ' ';
		foreach ($tag_list as $tag) {
			echo ($tag->appliesToPhoto($photo)) ? 'y ' : 'n ';
		}
		echo "\n";
	}
	echo "\n";

	$sub_tags = $tag_list->getSubTags();
	if (count($sub_tags) > 0)
		test_tag_list($sub_tags);
}

// Tag tests

$start_time = microtime(true);

echo "Tag Tests:\n\n";

test_tag('date.week=2002-01-04'); // test date tag
test_tag('date.date=20002-01-04'); // test invalid date tag
test_tag('geo.lat=25'); // test machine tag
test_tag('christmas2001'); // test regular tag

// TagList tests

echo "\nTagList Tests:\n\n";

$tag_list = new PinholeTagList($connection,
	'christmas2001/date.year=2007/daniel/date.month=4');

test_tag_list($tag_list);

$tag_list->replace('date.year=2007', PinholeTagFactory::get('christmas'));

test_tag_list($tag_list);

$tag_list = $tag_list->filter(array('PinholeAbstractMachineTag'));

test_tag_list($tag_list);

$end_time = microtime(true);
echo "\ntotal time: ", ($end_time - $start_time) * 1000, "ms\n";

/*
$sql = 'select * from PinholeTag limit 10';
$tags = SwatDB::query($connection, $sql, 'PinholeTagWrapper');
foreach ($tags as $tag) {
	echo $tag->getTitle(), "\n";
}
*/

?>
