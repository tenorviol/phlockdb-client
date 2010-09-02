phlockdb-client
===============

PHP port of Twitter's [flockdb-client](http://github.com/twitter/flockdb-client)
for connecting to [FlockDB](http://github.com/twitter/flockdb) instances

Setup
-----

Obtain a copy of the thrift php library, any version will do.
If you already have the thrift library in place, use it.
You will need to copy `thrift/packages/FlockDB` to your own thrift `packages` directory.

For those lacking, I've made the library available in the `thrift` directory.
Or if you prefer going to the source, download it from [Apache Thrift](http://incubator.apache.org/thrift/)
and copy `thrift/packages/FlockDB` into the fresh download.

Usage
-----

Set the THRIFT_ROOT:

	$GLOBALS['THRIFT_ROOT'] = '/location/of/thrift/php/lib';

Include the Phlock library. Easiest is to use the supplied `autoload.php`:

	require_once 'phlockdb-client/src/autoload.php';

Instantiate a connection to FlockDB:

	$flock = new Phlock(array('127.0.0.1:7915'), array('graphs'=>array('follows'=>1, 'blocks'=>2)));

Edge manipulation:

	$flock->add(1, 'follows', 2);
	$flock->remove(1, 'blocks', 2);

Queries can be paginated:

	$cursor = $flock->select(1, 'follows', null);
	
	$page1 = $cursor->currentPage();
	print_r($page1);
	
	if ($cursor->hasNextPage()) {
		$cursor->nextPage();
		$page2 = $cursor->currentPage();
		print_r($page2);
	}

Or iterated:

	$cursor = $flock->select(1, 'follows', null);
	$following = iterator_to_array($cursor, false);

TODO
----

This project is in its infancy. Here is a list of things outstanding today.

1. Let the thrift client accept multiple servers for failover.
2. Allow optional client settings like timeout and buffer size.
3. Cursor pagination features: prev page, and native Iterator support.
4. This:

	`flockdb.select(nil, :follows, 1).difference(flockdb.select(1, :follows, nil).intersect(2, :follows, nil)).to_a`

