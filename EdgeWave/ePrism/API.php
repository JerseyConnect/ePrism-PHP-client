<?php
/**
 * Interface for the EdgeWave ePrism email filtering API
 * 
 * Create an instance of the API class with your instance URL and either a valid token or username + password
 * Retrieve your token with $obj->get_token() and save it for 24 hours
 * Use the public methods of the class to perform actions
 * 
 */

namespace EdgeWave\ePrism;

/**
 * This may be needed, but should be disabled unless there is a very good reason to enable it.
 */
define( 'SKIP_SSL_SECURITY_CHECKS', false );

/**
 * Mailbox discovery options for domains
 */
define( 'EPRISM_DISCOVERY_DISABLED', 'disabled' );
define( 'EPRISM_DISCOVERY_EXTERNAL', 'external' );
define( 'EPRISM_DISCOVERY_SMTP_VRFY',    'vrfy' );
define( 'EPRISM_DISCOVERY_SMTP_RCPT_TO', 'rcpt' );

/**
 * Mailbox / user administrative roles
 */
define( 'EPRISM_SYSTEM_ADMINISTRATOR', 'sa' );
define( 'EPRISM_ACCOUNT_ADMINISTATOR', 'aa' );
define( 'EPRISM_ACCOUNT_OPERATOR',     'ao' );
define( 'EPRISM_DASHBOARD_OPERATOR',   'do' );

/**
 * 
 */
class API {
	
	private $_instance_name;
	private $_security_token;
	
	/**
	 * 
	 */
	public function __construct( $hostname, $token, $password = null ) {
		
		$this->_instance_name = $hostname;
		
		// Qualify the instance name if it isn't already
		if( 
			false === strpos( $hostname, '.edgewave.net' ) &&
			false === strpos( $hostname, '.redcondor.net' )
		) {
			$this->_instance_name .= '.redcondor.net';
		}
		
		if( ! empty( $password ) ) {
			
			try {
				
				$this->fetch_token( $token, $password );
				
			} catch ( Exception $e ) {
				
				throw new \Exception( 'There was an error getting authorization token: ' . $e->getMessage() );
				
			}
			
		} else {
			
			$this->set_token( $token );
			
		}
		
		libxml_disable_entity_loader( true );
		
	}
	
	
	/**
	 * Functions for retrieving and manipulating accounts
	 */
	
	public function fetch_account( $account_ID ) {}
	public function fetch_accounts( $search = null ) {}
	public function fetch_all_accounts() {

		$result = $this->http_get(
			$this->build_endpoint_url(
				'account/list'
			)
		);
		
		if( $result->success )
			return $this->decode_xml_response( $result->response_body );
		return false;
	}
	
	public function create_account( $account_name, $account_data ) {}
	public function delete_account( $account_ID ) {}
	
	
	/***********************************************************************
	 * Functions for retrieving and manipulating domains
	 * These functions also get all domain policy details, so use sparingly
	 */
	
	public function fetch_domain( $domain_name ) {}
	public function fetch_domains_by_account( $account_ID ) {}
	public function fetch_domains( $search = null ) {}
	public function fetch_all_domains() {
		
		$result = $this->http_get(
			$this->build_endpoint_url(
				'domain/list'
			)
		);
		
		if( $result->success )
			return $this->decode_xml_response( $result->response_body );
		return false;
	}
	
	public function create_domain( $domain_name, $account_ID, $domain_settings = null ) {
		
		$result = $this->http_post(
			$this->build_endpoint_url(
				'domain/create'
			),
			array(
				'name'    => $domain_name,
				'account' => $account_ID
			)
		);
		
		if( $result->success )
			return $this->decode_xml_response( $result->response_body );
		return false;
		
	}
	
	public function delete_domain( $domain_name ) {}
	public function change_domain_account( $domain_name, $new_account_ID ) {}
	public function change_domain_settings( $domain_name, $settings ) {}
	
	public function fetch_domain_settings( $domain_name ) {
		
		$result = $this->http_get(
			$this->build_endpoint_url(
				'config/download',
				array(
					'domain' => $domain_name
				)
			)
		);
		
		if( $result->success )
			return $this->decode_xml_response( $result->response_body );
		return false;
		
	}
	
	public function push_domain_settings( $domain_name, $settings = null ) {
		
		if( empty( $settings ) )
			$settings = $this->fetch_domain_settings( $domain_name );
		
		if( empty( $settings ) )
			return false;
		
		$result = $this->http_post(
			$this->build_endpoint_url(
				'config/upload',
				array(
					'domain' => $domain_name,
					'update' => 'true'
				)
			),
			$settings
		);
		
		if( $result->success )
			return $this->decode_xml_response( $result->response_body );
		return false;
		
	}
	
	/*************************************************************************
	 * Functions for retrieving and manipulating administrative user accounts
	 */
	
	public function fetch_user( $email_address ) {}
	public function fetch_users_by_role( $role ) {}
	public function fetch_users( $search = null ) {}
	public function fetch_all_users() {
		
		$result = $this->http_get(
			$this->build_endpoint_url(
				'user/list'
			)
		);
		
		if( $result->success )
			return $this->decode_xml_response( $result->response_body );
		return false;
	}
	
	public function create_user( $domain_name, $account_ID, $domain_settings ) {}
	public function delete_user( $domain_name ) {}
	
	
	
	/*****************************************************************
	 *                     Here be dragons
	 ****************************************************************/
	
	/**
	 * Retrieve an authorization token for the provided user
	 * @param string Email Addresss user's email address
	 * @param string Password user's password
	 */
	private function fetch_token( $email_address, $password ) {

		$result = $this->http_get(
			$this->build_endpoint_url(
				'login',
				array(
					'email'    => $email_address,
					'password' => $password
				)
			)
		);
		
		if( ! $result->success )
			throw new \Exception( 'Token retrieval failed. HTTP Response code was: ' . $result->response_code );
		
		$this->set_token( $result->response_body );
		
	}
	
	/**
	 * Perform an HTTP GET request to the specified address and return the result
	 */
	private function http_get(  $url ) {
		
		// Perform the request using CURL if possible
		if( function_exists( 'curl_init' ) ) {
			
			$req = curl_init();
			
			curl_setopt_array( $req, array(
				CURLOPT_URL            => $url,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_TIMEOUT        => 5
			));

			if( defined('SKIP_SSL_SECURITY_CHECKS') && SKIP_SSL_SECURITY_CHECKS ) {
				curl_setopt( $req, CURLOPT_SSL_VERIFYPEER, 0 );
				curl_setopt( $req, CURLOPT_SSL_VERIFYHOST, 0 );
			}


			$http_result = new HTTP_Response();
			
			$http_result->response_body = curl_exec( $req );
			$http_result->response_code = curl_getinfo( $req, CURLINFO_HTTP_CODE );
			
			curl_close( $req );
		}
		
		return $http_result;
	}
	
	/**
	 * Perform an HTTP POST request to the specified address using the specified data and return the result
	 */
	private function http_post( $url, $data ) {

		// Perform the request using cURL if possible
		if( function_exists( 'curl_init' ) ) {
			
			$req = curl_init( $url );
			
			// Handle XML POSTs
			if( is_a( $data, 'SimpleXMLElement' ) ) {

				$data = $data->asXML();
				
				curl_setopt( $req,
					CURLOPT_HTTPHEADER,
					array('Content-Type: text/xml')
				);
				
			}
			
			curl_setopt_array( $req, array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => $data
			));

			if( defined('SKIP_SSL_SECURITY_CHECKS') && SKIP_SSL_SECURITY_CHECKS ) {
				curl_setopt( $req, CURLOPT_SSL_VERIFYPEER, 0 );
				curl_setopt( $req, CURLOPT_SSL_VERIFYHOST, 0 );
			}
			
			$http_result = new HTTP_Response();
			
			$http_result->response_body = curl_exec( $req );
			$http_result->response_code = curl_getinfo( $req, CURLINFO_HTTP_CODE );
			
			curl_close( $req );
		} else {
			throw new \Exception( 'This package currently requires the cURL extension to PHP.');
		}
		
		return $http_result;
		
	}
	
	/**
	 * Build the URL for an HTTP request
	 * @param string Function API function name, e.g. 'login', 'accounts'
	 * @param array | string Params (optional) array of URL parameters or parameter string
	 * 	Parameters passed in an array will be escaped for URLs, but strings will not be changed
	 */
	private function build_endpoint_url( $function, $params = null ) {

		if( ! empty( $params ) ) {
			
			// Build a suitable URL parameter query from a passed array
			if( is_array( $params ) ) {
				$param_arr = array();
				foreach( $params as $key => $value ) {
					$param_arr[] = urlencode($key) . '=' . $value;
				}
				$params = join( '&', $param_arr );
			}
			
		}
		
		if( 'login' == $function ) {
			return 'https://' . $this->_instance_name . '/api/' . $function . '?' . $params;	
		}
		return 'https://' . $this->_instance_name . '/api/' . $function . '?token=' . $this->get_token() . '&' . $params;
		
	}
	
	private function decode_xml_response( $xml_string ) {
		
		if( function_exists( 'simplexml_load_string' ) ) {
			$response_object = simplexml_load_string( $xml_string );
		} else {
			throw new \Exception( 'This package currently supports only SimpleXML.' );
		}
		
		return $response_object;
	}
	
	/**
	 * Save the authorization token and expiration value
	 * System and user tokens are both needed for different functions
	 * @param string Token Authentication token string
	 * @param string [TokenType] (optional) type of token to be stored if system requires multiple tokens
	 * @param string [Expiration] (optional) expiration time for the authentication token
	 */
	private function set_token( $token, $token_type = 'system', $expiration = null ) {
		
		if( ! is_object( $this->_security_token ) )
			$this->_security_token = new \stdClass();
		
		if( ! isset( $this->_security_token->$token_type ) )
			$this->_security_token->$token_type = new \stdClass();
		
		if( empty( $expiration ) ) {
			$expiration = strtotime('+24 hours');
		}
		
		$this->_security_token->$token_type->value   = $token;
		$this->_security_token->$token_type->expires = $expiration;
		
	}

	/**
	 * Return stored token value
	 */
	public function get_token( $token_type = 'system' ) {
		return $this->_security_token->$token_type->value;
	}
	
}


/**
 * Utility class for returning the result of an HTTP Request
 */
class HTTP_Response {
	
	public $success;
	public $response_body;
	private $response_status_code;
	public $req_header;
	
	public function __set( $var, $value ) {
		if( 'response_code' == $var ) {
			$this->response_status_code = $value;
			$this->success = in_array( $value, array( 200 ) );
		}
	}
	
	public function __get( $var ) {
		if( 'response_code' == $var )
			return $this->response_status_code;
		return false;
	}
	
}
?>