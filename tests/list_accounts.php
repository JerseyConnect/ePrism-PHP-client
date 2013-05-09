<?php
/*
 * List system-level accounts test
 */
?>
<pre>
<?php
require '../EdgeWave/ePrism/API.php';
require 'test_settings.php';

try {
	$my_connection = new EdgeWave\ePrism\API( TEST_INSTANCE, TEST_USERNAME, TEST_PASSWORD );
} catch( Exception $e ) {
	die('Could not connect to EdgeWave API &mdash; error was: ' . $e->getMessage() );
}

print_r( $my_connection->fetch_all_accounts() );

?>
</pre>