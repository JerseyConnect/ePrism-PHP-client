<?php
/*
 * Get authentication token test
 */
require '../EdgeWave/ePrism/API.php';
require 'test_settings.php';

$my_connection = new EdgeWave\ePrism\API( TEST_INSTANCE, TEST_USERNAME, TEST_PASSWORD );

if( isset( $_POST['create'] ) ) {
	
	$domain = $_POST['domain_name'];
	$account_ID = $_POST['account_ID'];
	
	$result = $my_connection->create_domain( $domain, $account_ID );
	
	if( false === $result ) {
		die('Error creating domain!');
	}
	
	$settings = $my_connection->fetch_domain_settings( $domain );
	
	if( ! $settings )
		die( 'Error fetching settings!' );
	
	$result = $my_connection->push_domain_settings( $domain, $settings );
	
	if( false === $result )
		die( 'Error saving settings!' );
	
	die('Domain created successfully');
	?>
	
<?php } else { ?>
	
	<?php $accounts = $my_connection->fetch_all_accounts(); ?>
	<h1>Create a New Domain</h1>
	<form method="POST">
		<input type="text" name="domain_name" placeholder="Domain name to create">
		<select name="account_ID">
			<option>Select an Account</option>
			<? foreach( $accounts as $account ) : ?>
			<option value="<?= $account->attributes()->uid ?>"><?= $account->attributes()->name ?></option>
			<? endforeach; ?>
		</select>	
		<input type="submit" name="create" value="Create Domain">
	</form>
	
<?php } ?>