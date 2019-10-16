<?php
	$result = initCR();

	/**
	* Call the selligent platform and fetch the content
	*
	* This method will do a call to the Selligent xml.ashx service based upon the supplied hash.
	* Selligent will reply with an XML document that will be parsed and placed in an array.
	*
	* @param string $client
	*	The name of the install, this parameter will be used to create the full domain. E.g.: $client=selligentGTU will result into https://selligentGTU.slgnt.eu or https://selligentGTU.emsecure.net. Takes a string.
	* @param bool $newDomain
	*	Used to determine if we need to use the new domein (slgnt.eu) of the old domain (emsecure.net). Takes a Boolean TRUE/FALSE value
	* @param string $defaultID
	*	The default Selligent hash the system will use in case there is no alternative hash supplied. Takes a string.
	* @param string $id_param
	*	The default GET parameter name used to capture the Selligent hash. Takes a string.
	*
	* @return array 
	*/
	function initCR($client = "", $newDomain = true, $defaultID = "", $id_param = "ID") {
		if(!empty($defaultID)) {
			$domain = ""; // Used to set the domain, can be left empty
			$parameters = ""; // Used to store the GET & POST parameters
			$selligentID = ""; // Hash from Selligent
			$cr_url = "/renderers/xml.ashx?id="; // Can be xml.ashx, body.ashx, ... 
			$optiext = "/optiext/optiextension.dll"; 

			// Do the domain switch || Note: There might be more domains
			(strtoupper($newDomain)==true) ? $domain = "slgnt.eu" : $domain = "emsecure.net";
			// Get POST params
			$parameters .= loopParameters($_POST, $id_param);
			// Get GET params
			$parameters .= loopParameters($_GET, $id_param);
			// Check if ID is not empty and set the ID
			(!isset($_GET[$id_param])) ? $selligentID = $defaultID : $selligentID =  urlencode($_GET[$id_param]);
			// Check if we have all the information to do a call towards Selligent
			if(!empty($client) && !empty($id_param) && !empty($selligentID)) {
				// URL to your xml.ashx
				$xmldoc = "https://" . $client . "." . $domain . $cr_url . $selligentID . $parameters;
				// fetching of data
				$selligentContent = @simplexml_load_file($xmldoc);
				if(empty($selligentContent)) {
					return array(
				    	'head' => '',
				    	'body_attr' => '',
				    	'body' => outputError('Unable to reach the Selligent platform.'),
				    );
				}
				else {
					// Extra: Check for redirect, redirect can be found in the headers
					$location = "";
					foreach ($http_response_header as $hdr) {
					    if (preg_match('/^Location:\s*([^;]+)/', $hdr, $matches)) {
					        $location = $matches[1];
					    }
					}
					if (!empty($location)) {
						header("Location: " . $location);
						exit();
					}
					else {
						// Settings parameters to use in HTML
						$msgHeadStr = trim((string) $selligentContent->head);
						$msgBodyAttrStr = trim((string) $selligentContent->bodyattr);
						$msgBodyStr = trim((string) $selligentContent->body);
						// Extra: Replace all URLS with own URL for SMC
					    $msgBodyStr = str_replace("https://". $client . "." . $domain . $optiext,siteURL() . $client ."/" . basename($_SERVER['PHP_SELF']), $msgBodyStr);
					    return array(
					    	'head' => $msgHeadStr,
					    	'body_attr' => $msgBodyAttrStr,
					    	'body' => $msgBodyStr,
					    );
					}
				}
			}
			else {
				return array(
			    	'head' => '',
			    	'body_attr' => '',
			    	'body' => outputError('Missing the install-name, id parameter name or the Selligent hash. Check the settings'),
			    );
			}
		}
		else {
			return array(
		    	'head' => '',
		    	'body_attr' => '',
		    	'body' => outputError('Missing the default ID. Check the settings'),
		    );
		}
	}
	
	/**
	* Loops over data and concatinates it
	*
	* Method used to loop over the $_GET and $_POST parameters and concatinates them into one string.
	* The Selligent platform wants to receive the parameters as a GET string.
	*
	* @param array $data
	*	Array containing all the parameters passed via $_GET or $_POST. Takes an array of parameters.
	* @param string $id_param
	*	Contains the name of the GET parameter tot holds the Selligent hash. Takes an string.
	*
	* @return string
	*/
	function loopParameters($data,$id_param) {
		$params = "";
		foreach($data as $name => $value) {
			if(strtoupper($name)<>$id_param) {
				$params .= "&" . $name . "=" . urlencode($value);
			}
		}
		return $params;
	}

	/**
	* Returns the current domain name and the protocol
	*
	* Method used to look at the current domain and fetch the current protocol (http or https) and the root domain.
	*
	* @return string
	*/
	function siteURL()
	{
		// Check the protocol of the domain
	    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
	    // Get the domain name
	    $domainName = $_SERVER['HTTP_HOST'].'/';
	    return $protocol.$domainName;
	}

	/**
	* Outputs an error message for the website
	*
	* Method used to produce a console.log message that is used to inform the developer of errors during the load of
	* the Selligent content.
	*
	* @param string $msg
	*	Message that will be placed inside the console.log function to be displayed in the console. Takes an string.
	*
	* @return string
	*/
	function outputError($msg) {
		return "<script>console.log('[CR_OUTPUT]: " . $msg . "')</script>";
	}
?>

<!DOCTYPE html>
<html>
<head>
	<?php echo $result['head']; ?>
</head>
<body <?php echo $result['body_attr']; ?>>
	<?php echo $result['body']; ?>
</body>
</html>
