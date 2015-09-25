<?php

class priceplanHTTP_connection {
	protected $api_v, $format, $api_url, $curl;

	function __construct($api_url = 'http://cust1.priceplan.ru:9889/api/' , $api_v = '0.1', $format = 'json'){
		if(!in_array('curl', get_loaded_extensions())){
			throw new Exception('Curl library is not installed.');
		}
		$this->api_v = $api_v;
		$this->format = $format;
		$this->api_url = $api_url;
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, True);
		// save cookies in local file
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/ppcookie');
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/ppcookie');

	}

	function __destruct(){
		curl_close($this->curl);
		if(file_exists(dirname(__FILE__).'/ppcookie')){
			unlink(dirname(__FILE__).'/ppcookie');
		}
	}
}

abstract class priceplanHTTP_auth{
	protected $api_v, $format, $api_url, $curl;

	function __construct($api_url = 'http://cust1.pp.ru:9889/api/key/' , $api_v = '0.1', $format = 'json'){
		if(!in_array('curl', get_loaded_extensions())){
			throw new Exception('Curl library is not installed.');
		}
		$this->api_v = $api_v;
		$this->format = $format;
		$this->api_url = $api_url;
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, True);
		// save cookies in local file
		//curl_setopt ($this->curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/ppcookie');
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/ppcookie');

	}

	function __destruct(){
		curl_close($this->curl);
		if(file_exists(dirname(__FILE__).'/ppcookie')){
			unlink(dirname(__FILE__).'/ppcookie');
		}
	}

	function detectRequestBody(){
		$rawInput = fopen('php://input', 'r');
		$tempStream = fopen('php://temp', 'r+');
		stream_copy_to_stream($rawInput, $tempStream);
		rewind($tempStream);

		$body = '';
		while(!feof($tempStream)) {
			$body .= fread($tempStream, 1024);
		}
		return $body;
	}
}

class priceplanHTTP_authBasic extends priceplanHTTP_auth {
	protected $user, $password;
	function __construct($user, $password, $connection = null){
		parent::__construct();
		$this->user = $user;
		$this->password = $password;
		$this->is_auth = False;
	}

	function is_authorized() {

	}


	function auth() {
		if(file_exists(dirname(__FILE__).'/ppcookie')){
			unlink(dirname(__FILE__).'/ppcookie');
		}
		$this->is_auth = True;
		$this->call_method('login', array('user' => $this->user,'password' => $this->password ));
	}

	// function to call API method
	function call_method($object, $params = array(), $method='POST'){
		if(!file_exists(dirname(__FILE__).'/ppcookie') OR !filesize(dirname(__FILE__).'/ppcookie')){
			if(!$this->is_auth ){ // need to login
				$this->auth();
			}
		}

		if($method == 'POST'){
			$data_string = json_encode($params);
			curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($this->curl, CURLOPT_URL, $this->api_url.$object);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_string))
			);
		}else{
			curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
			curl_setopt($this->curl, CURLOPT_URL, $this->api_url.$object.'?'.http_build_query($params));
		}

		$data= curl_exec($this->curl);
		// throw errors if they occur
		if($data === false){
			throw new Exception('Curl error: '.curl_error($this->curl).'.');
		}

		if(curl_getinfo($this->curl, CURLINFO_HTTP_CODE) == 401){
			throw new Exception('API request is not authorized.');
		}
		if(curl_getinfo($this->curl, CURLINFO_HTTP_CODE) >= 400){
			throw new Exception('API server is not responding.');
		}

		if(!$js = json_decode($data, $assoc = true)){
			throw new Exception('API response is empty or misformed.');
		}
		if($js['success'] == False){
			throw new Exception('API error:'.$data);
		}
		//if(!empty($js['response']['msg']['err_code'])){
		//	throw new Exception($js['response']['msg']['text']);
		//}

		// save API response
		$this->response = $js;
		return $js;
	}
}

class priceplanHTTP_authToken extends priceplanHTTP_auth {
	protected $user, $password;
	function __construct($user, $password, $connection = null){
		parent::__construct();
		$this->user = $user;
		$this->password = $password;
		$this->is_auth = False;
	}

	function is_authorized() {
	}

	function auth() {
		if(file_exists(dirname(__FILE__).'/ppcookie')){
			unlink(dirname(__FILE__).'/ppcookie');
		}
		$this->is_auth = True;
		$this->call_method('login', array('user' => $this->user,'password' => $this->password ));
	}

	// function to call API method
	function call_method($object, $params = array(), $method='POST'){
		/*if(!file_exists(dirname(__FILE__).'/ppcookie') OR !filesize(dirname(__FILE__).'/ppcookie')){
			if(!$this->is_auth ){ // need to login
				$this->auth();
			}
		}*/
		if($method == 'POST'){
			$data_string = json_encode($params);
			curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($this->curl, CURLOPT_URL, $this->api_url.$object);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_string))
			);
		}else{
			curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
			ksort($params);
			curl_setopt(
				$this->curl,
				CURLOPT_URL,
				$this->api_url.$object.'?'.
					http_build_query($params).'&'.
					http_build_query(array('token'=>md5(http_build_query($params).$this->password), 'user'=>$this->user)));
		}

		$data= curl_exec($this->curl);
		// throw errors if they occur
		if($data === false){
			throw new Exception('Curl error: '.curl_error($this->curl).'.');
		}

		if(curl_getinfo($this->curl, CURLINFO_HTTP_CODE) == 401){
			throw new Exception('API request is not authorized.');
		}
		if(curl_getinfo($this->curl, CURLINFO_HTTP_CODE) >= 400){
			throw new Exception('API server is not responding.');
		}

		if(!$js = json_decode($data, $assoc = true)){
			throw new Exception('API response is empty or misformed.');
		}
		if($js['success'] == False){
			throw new Exception('API error:'.$data);
		}
		//if(!empty($js['response']['msg']['err_code'])){
		//	throw new Exception($js['response']['msg']['text']);
		//}

		// save API response
		$this->response = $js;
		return $js;
	}
}

class priceplaneAPI {
	private $auth ;

	function __construct($auth){
		$this->auth = $auth;
	}

	function call_method($object, $params = array(), $method='POST'){
		return $this->auth->call_method($object, $params, $method);
	}

	function convert_array($array, $convert_arra){
		if($convert_arra){
			$tmp_array = array();
			for($i = 0; $i < sizeof($array); $i++){
				$tmp_array[$array[$i]['id']] = $array[$i];
			}
			return $tmp_array;
		}else{
			return $array;
		}


	}
	/*
	getting a list of variables
	*/
	function getVariables($convert_array = True){
		$unsver = $this->call_method('variables/', array(),'GET');
		return $this->convert_array($unsver['data'], $convert_array);
	}

	/*
	getting a list of measures
	*/
	function getMeasures($convert_array = True){
		$unsver = $this->call_method('measures/', array(),'GET');
		return $this->convert_array($unsver['data'], $convert_array);

	}

	/*
	getting a list of documents
	*/
	function getDocuments($fields, $filters=null, $sort=null, $take=null, $skip=null, $convert_array = True){
		$params = array('fields'=>$fields);
		if($filters!=null)
			$params['filters']=$filters;
		if($sort!=null)
			$params['sort']=$sort;
		if($take!=null)
			$params['take']=$take;
		if($skip!=null)
			$params['skip']=$skip;
		$unsver = $this->call_method('documents/', $params,'GET');
		return $this->convert_array($unsver['data'], $convert_array);
	}

	/*
	getting a list of productstatuses
	*/
	function getProductstatuses(){
		$unsver = $this->call_method('productstatuses/', array(),'GET');
		return $unsver['data'];
	}

	/*
	getting a list of products
	*/
	function getProducts(){
		$unsver = $this->call_method('products/', array(),'GET');
		$unsver['data'];
	}

	/*
	getting a product by id
	*/
	function getProductById($id_product){
		$id_product = intval($id_product);
		$unsver = $this->call_method('products/'.$id_product, array(),'GET');
		return $unsver['data'];
	}
	/*
	getting a client types
	*/
	function getClientTypes(){
		$unsver = $this->call_method('clienttypes/', array(),'GET');
		return $unsver['data'];
	}

	/*
	create a client
	*/
	function createClient($params){
		$unsver = $this->call_method('clients/', $params,'POST');
		return $unsver['data'];
	}
	/*
	create a subscribe
	*/
	function createSubscribe($params){
		$unsver = $this->call_method('subscribes/', $params,'POST');
		return $unsver['data'];
	}

	/*
	increase balance
	*/
	function increaseBalance($client_id,$params){
		$unsver = $this->call_method('clients/'.$client_id.'/increase', $params,'POST');
		return $unsver['data'];
	}

}
/*
try {

	$pp = new priceplaneAPI('edmin','edmin00');
	echo('<pre>');
	print_r($pp->getProductById(1));
	echo('</pre>');

} catch (Exception $e) {

    print 'Error: '.$e->getMessage()."\n";

}
*/
?>