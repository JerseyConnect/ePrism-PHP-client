<?php
/*
 * Get authentication token test
 */
?>
<pre>
<?php
require '../EdgeWave/ePrism/API.php';
require 'test_settings.php';

$my_connection = new EdgeWave\ePrism\API( TEST_INSTANCE, TEST_USERNAME, TEST_PASSWORD );

print_r( $my_connection->fetch_all_users() );

?>
</pre>