<?php
/*
 * Get authentication token test
 */
?>
<pre>
<?php
require '../EdgeWave/ePrism/API.php';

try {
	$my_connection = new EdgeWave\ePrism\API( TEST_INSTANCE, TEST_USERNAME, TEST_PASSWORD );
} catch( Exception $e ) {
	die('Could not connect to EdgeWave API &mdash; error was: ' . $e->getMessage() );
}

?>
</pre>