<?php
/**
 *  Start Home away api Insert
 */
class pr_insert_native {

	public function __construct() {
		$this->load_includes();
		$search = 'texas';
		$search1 = 'Cleveland';
		$client_key = '21e648f9-15ae-45d4-846d-27b2c4e2e0b7';	
		$client_secret = '6997fef9-76d2-47ff-9d37-d6d08d3431c3';	
		$pageSize = 30;
		$pagination = 166;
		
		//each search variable each function to show
		//$this->homeaway_api_public_search( $search, $client_key, $client_secret, $pageSize, $pagination );
		$this->homeaway_api_public_search( $search1, $client_key, $client_secret, $pageSize, $pagination );
		
	}

	public function load_includes() {
		include_once( 'Db.php' );
		include_once( 'db_functions.php' );
	}

	public function get_user_meta( $id, $meta_key, $meta_value ) {
		$db = new Db();
		if( !empty( $id && $meta_key && $meta_value ) ) {
			$rows = $db -> select(" SELECT * FROM wp_usermeta WHERE user_id = '$id' AND meta_key = '$meta_key' ");
			foreach ($rows as $row ) {
				return $row['meta_value'];
			}
		}
	}

	public function update_user_meta( $id, $meta_key, $meta_value ) {
		$db = new Db();
		if( !empty( $id && $meta_key && $meta_value ) ) {
			$rows = $db -> query(" UPDATE wp_usermeta SET meta_value = '$meta_value' WHERE user_id = '$id' AND meta_key = '$meta_key' ");
			if( $rows === false) {
				$error = db_error();
			}
		}
	}

	public function update_post_meta( $id, $meta_key, $meta_value ) {
		$db = new Db();
		if( !empty( $id && $meta_key && $meta_value ) ) {
			$rows = $db -> query(" UPDATE wp_postmeta SET meta_value = '$meta_value' WHERE post_id = '$id' AND meta_key = '$meta_key' ");
			if( $rows === false) {
				$error = db_error();
			}
		}
	}

	public function insert_post_meta( $id, $meta_key, $meta_value ) {
		$db = new Db();
		if( !empty( $id && $meta_key && $meta_value ) ) {
			$rows = $db -> query(" INSERT INTO wp_postmeta SET post_id = '$id', meta_value = '$meta_value', meta_key = '$meta_key' ");
			if( $rows === false) {
				$error = db_error();
			}
		}
	}

	public function insert_post( $post_title, $description, $post_type, $post_status ) {
		$db = new Db();
		$now = new DateTime();
		$today = $now->format('Y-m-d H:i:s'); 
		$post_name = trim( preg_replace('/[^a-z0-9-]+/', '-', strtolower( $post_title ) ), '-');
		if( !empty( $post_title && $description && $post_type && $post_status ) ) {
			$rows = $db -> query(" INSERT INTO wp_posts SET post_title = '$post_title', post_content = '$description', post_type = '$post_type', post_status = '$post_status', post_date = '$today', post_date_gmt = '$today', post_name = '$post_name' ");
			if( $rows === false) {
				$error = db_error();
			}
			else {
				return mysqli_insert_id( $db->connect() );
			}
		}
	}

	public function update_post( $post_title, $description, $post_id ) {
		$db = new Db();
		if( !empty( $post_title && $description && $post_id ) ) {
			$rows = $db -> query(" UPDATE wp_posts SET post_title = '$post_title', post_content = '$description' WHERE ID ='$post_id' ");
			if( $rows === false) {
				$error = db_error();
			}
			else {
				return true;
			}
		}
	}

	public function get_site_url() {
		$db = new Db();
		$rows = $db -> select(" SELECT * FROM wp_options WHERE option_id = '1' AND option_name = 'siteurl' ");
		foreach ($rows as $row ) {
			return $row['option_value'];
		}
	
	}

	public function home_away_get_token( $base_url, $client_key, $client_secret ) {
		$process = curl_init();
		$token_authentication_url = $base_url .'/oauth/token'; 
		curl_setopt($process,CURLOPT_URL, $token_authentication_url );
		curl_setopt($process, CURLOPT_HTTPHEADER, array( 
		'Authorization: Basic ' . base64_encode( $client_key . ':' . $client_secret ),
		) );
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($process, CURLOPT_POST, true);
		$response = curl_exec($process);
		curl_close($process);
		$obj = json_decode($response, true);
		return $obj['access_token'];
	}

	public function homeaway_get_page_count( $Result_key, $public_search_url, $public_search_params ) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $public_search_url . $public_search_params );
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $Result_key ) );
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);
		$obj = json_decode($result, true); 
		return $obj['pageCount'];
	}

	public function homeaway_api_public_search( $search, $client_key, $client_secret, $pageSize, $pagination ) {

		$pagi = $this->get_user_meta( 9999, "_$search", true );
		if( !empty( $pagi ) ) {
			$pagi = $pagi + 1;
			if( !( $pagi > $pagination ) ) {
				$this->update_user_meta( 9999, "_$search", $pagi );
			}
			else {
				$this->update_user_meta( 9999, "_$search", 1 );
			}		
		}
		else {
			$this->update_user_meta( 9999, "_$search", 1 );
		}

		$base_url = 'https://ws.homeaway.com';
		$token_authentication_url = $base_url .'/oauth/token'; 
	  	$Result_key = $this->home_away_get_token( $base_url, $client_key, $client_secret );
	  	$public_search_url = $base_url . '/public/search';
	  	$public_search_params = "?q=$search&pageSize=$pageSize&page=$pagi&locale=en&imageSize=LARGE";
	  	$pagecount_total = $pagination * $pageSize;
	  	$pagecount = $this->homeaway_get_page_count( $Result_key, $public_search_url, $public_search_params );
		echo '<h2>Pagination: '.$this->get_user_meta( 9999, "_$search", true ).'</h2>';

	  	if( !empty( $search && $pageSize && $pagination ) ) {
	      	$curl = curl_init();
	      	curl_setopt( $curl, CURLOPT_URL, $public_search_url . $public_search_params );
	      	curl_setopt( $curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $Result_key ) );
	      	curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false);
	      	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1);
	      	$result = curl_exec($curl);
	      	$obj = json_decode($result, true); 
	       	//echo"<pre>"; var_dump( $obj ); echo"</pre>"; 
			foreach( $obj['entries'] as $key => $entry) { 
		  	 echo"<pre>"; var_dump($entry); echo"</pre>";
		  		$listing_id = $entry['listingId'];
		  		$title = $entry['headline'];
				$listcompare = $this->home_away_match_list( $listing_id );
				$description = $entry['description'];
		  		$city = $entry['location']['city']; 
				$state = $entry['location']['state']; 
				$country = $entry['location']['country']; 
		  		$address ="$city, $state, $country";
		  		$lat = $entry['location']['lat'];
		  		$lng = $entry['location']['lng'];
		  		$propertytype = $this->homeaway_property_type_out( $entry['regionPath'] );
		  		$image = $entry['thumbnail']['uri'];
		  		$rentalperiod = $this->homeaway_price_range_type( $entry['priceRanges'] );
		  		if( $this->homeaway_price_range_type( $entry['priceRanges'] ) ) {
		  			$offertype = "rent";
		  		}
		  		$price = $this->homeaway_price_range_price( $entry['priceRanges'] ) * 1.15;
		  		$bathrooms = $entry['bathrooms'];
		  		$bedrooms = $entry['bedrooms'];
		  		$featured_img = $this->homeaway_get_featured_image_first( $listing_id,$Result_key, $base_url );
				$fulldescription = $this->homeaway_show_listing_description( $listing_id, $Result_key, $token_authentication_url, $base_url );
				
				if( $listcompare !== true) {			
					$post_type = 'property';
					$post_status = 'publish';

					$post_id = $this->insert_post( $title, $fulldescription, $post_type, $post_status );

					// Insert the post into the database.
					$this->insert_post_meta( $post_id,'_price', $price );
					$this->insert_post_meta( $post_id,'_property_type', $propertytype );
					$this->insert_post_meta( $post_id,'_offer_type', $offertype );
					$this->insert_post_meta( $post_id,'_rental_period', $rentalperiod );
					$this->insert_post_meta( $post_id,'_listing', $listing_id );
					$this->insert_post_meta( $post_id,'_bedrooms', $bedrooms );
					$this->insert_post_meta( $post_id,'_bathrooms', $bathrooms );
					$this->insert_post_meta( $post_id,'_friendly_address', $address );
					$this->insert_post_meta( $post_id,'_address', $address );
					$this->insert_post_meta( $post_id,'_geolocation_lat', $lat );
					$this->insert_post_meta( $post_id,'_geolocation_long', $lng );
					$this->insert_post_meta( $post_id, 'fifu_image_url', $featured_img );
					if( $post_id ){
					  echo "<h1>$title = Inserted ($listing_id)</h1>";
					}
					
				}
				if( $listcompare == true ) {
					$pidcom = $this->home_away_match_list_show_id( $listing_id );
					$this->update_post_meta( $pidcom,'_price', $price );
					$this->update_post_meta( $pidcom, 'fifu_image_url', $featured_img );
					$update = $this->update_post( $title, $fulldescription, $pidcom );
					// Update the post into the database
					if( $update ) {
						echo "<h1>$title = Updated ($listing_id)</h1>";
					}
				}
			}
			echo"<h1>It Reach to the Bottom Congrats</h1>";
		}

	}

	public function homeaway_get_featured_image_first( $listing_id, $Result_key, $base_url ) {
		if( !empty( $listing_id ) ) {
			$token_authentication_url = $base_url .'/oauth/token';
			$public_search_url = $base_url . '/public/listing';
			$public_search_params = "?q=PHOTOS&id=$listing_id";
		  	$curl = curl_init();
		  	curl_setopt($curl, CURLOPT_URL, $public_search_url . $public_search_params );
		  	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $Result_key ) );
		  	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		  	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		  	$result = curl_exec($curl);
		  	$obj = json_decode($result, true); 
		  	return $obj['photos']['photos'][1]['large']['uri'];
		  
		}
	}

	public function homeaway_show_listing_description( $listing_id, $Result_key, $token_authentication_url, $base_url ) {
		if( !empty( $listing_id ) ) {
		  	$base_url = 'https://ws.homeaway.com';
		  	$token_authentication_url = $base_url .'/oauth/token';
		  	$public_search_url = $base_url . '/public/listing';
			$public_search_params = "?q=DETAILS&id=$listing_id";
		  	$curl = curl_init();
		  	curl_setopt($curl, CURLOPT_URL, $public_search_url . $public_search_params );
		  	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $Result_key ) );
		  	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		  	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		  	$result = curl_exec($curl);
		  	$obj = json_decode($result, true); 
			return $obj['adContent']['description'];
		}
	}

	public function home_away_match_list( $listing_id ) {
		$db = new Db();
		if( !empty( $listing_id ) ) {
			$meta_key = '_listing';
			$rows = $db -> select(" SELECT * FROM wp_postmeta WHERE meta_key = '$meta_key' AND meta_value = '$listing_id' ");
			if ( $rows ) {
				return true;
			}
		}
	}

	public function home_away_match_list_show_id( $listing_id ) {
		$db = new Db();
		if( !empty( $listing_id ) ) {
			$meta_key = '_listing';
			$rows = $db -> select(" SELECT * FROM wp_postmeta WHERE meta_key = '$meta_key' AND meta_value = '$listing_id' ");
			foreach ($rows as $row ) {
				return $row['post_id'];
			}
		}
	}


	public function homeaway_property_type_out( $rpath ) {
		$rpath = strtolower( $rpath );
		$htypes = array( 'apartment', 'house', 'commercial', 'garage', 'lot', 'condo', 'townhouse');
		foreach ($htypes as $key => $htype) {
			if( strpos( $rpath, $htype) !== false ) {
				if( $htype =='house' ) {
					$htype ='houses';
				}
				elseif( $htype =='garage' ) {
					$htype ='garages';
				}
				elseif( $htype =='lot' ) {
					$htype ='lots';
				}
				return $htype;
			}
		}
	}

	public function homeaway_price_range_type( $pr ) {
		foreach( $pr as $pricerange ) {
			$periodType = $pricerange['periodType'];
			if( $periodType =='NIGHTLY-WEEKDAY' ) {
				return $periodType = 'daily';
			}
			else {
				$pr = strtolower( $periodType );
				return $pr;
			}
		}  
	}

	public function homeaway_price_range_price( $pr ) {
		foreach( $pr as $pricerange ) {
			return $periodType = $pricerange['from'];
		}  
	}

}

$pr_insert = new pr_insert_native;

/**
 *  End Home away api shortcode
 */