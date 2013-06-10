<?php

    /**
     * Class to access Amazons Product Advertising API
     * @author Sameer Borate
     * @link http://www.codediesel.com
     * @version 1.0
     * All requests are not implemented here. You can easily
     * implement the others from the ones given below.
     */
    
    
    /*
    Permission is hereby granted, free of charge, to any person obtaining a
    copy of this software and associated documentation files (the "Software"),
    to deal in the Software without restriction, including without limitation
    the rights to use, copy, modify, merge, publish, distribute, sublicense,
    and/or sell copies of the Software, and to permit persons to whom the
    Software is furnished to do so, subject to the following conditions:
    
    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.
    
    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
    THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
    FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
    DEALINGS IN THE SOFTWARE.
    */
    
    require_once 'aws_signed_request.php';

    class AmazonProductAPI
    {
        /**
         * Your Amazon Access Key Id
         * @access private
         * @var string
         */
        private $public_key     = "";
        
        /**
         * Your Amazon Secret Access Key
         * @access private
         * @var string
         */
        private $private_key    = "";
        
        /**
         * Your Amazon Associate Tag
         * Now required, effective from 25th Oct. 2011
         * @access private
         * @var string
         */
        private $associate_tag  = "YOUR AMAZON ASSOCIATE TAG";
    
        /**
         * Constants for product types
         * @access public
         * @var string
         */
        
        /*
            Only three categories are listed here. 
            More categories can be found here:
            http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/APPNDX_SearchIndexValues.html
        */
        const MUSIC = "Music";
        const DVD   = "DVD";
        const GAMES = "VideoGames";
		
		var $m_parent_trace = array();


         function AmazonProductAPI($public,$private,$ass){
             $this->public_key= $public;
             $this->private_key= $private;
             $this->associate_tag=$ass;
         }
                 
        
        /**
         * Check if the xml received from Amazon is valid
         * 
         * @param mixed $response xml response to check
         * @return bool false if the xml is invalid
         * @return mixed the xml response if it is valid
         * @return exception if we could not connect to Amazon
         */


        private function verifyXmlResponse($response)
        {
			if( !is_array( $xml_response->Items->Request->Errors->Error ) )
			{
				$err = $xml_response->Items->Request->Errors->Error->Message;
			}
			else
			{
				$err = $xml_response->Items->Request->Errors->Error[0]->Message;
			}
			
			if(trim($err) != ''){
				
				throw new Exception('<br>'.$err);
				return false;
			}

            if ($response === False)
            {
                throw new Exception("Could not connect to Amazon");
            }
            else
            {
                if (isset($response->Items->Item->ItemAttributes->Title))
                {
                    return ($response);
                }
                else if( $response->Error->Message )
                {
					echo '<span style="color:#cc0000;">Amazon error: '.$response->Error->Message.'</span>';
					return array( 'error' => $response->Error->Message );
                    //echo("Invalid xml response.");
                }
            }
        }
        
        
        /**
         * Query Amazon with the issued parameters
         * 
         * @param array $parameters parameters to query around
         * @return simpleXmlObject xml query response
         */
        private function queryAmazon( $parameters, $region = 'com' )
        { 
			//Amazon(r) region (ca,com,co.uk,de,fr,jp)
            return aws_signed_request( $region, $parameters, $this->public_key, $this->private_key, $this->associate_tag);
        }
        
        
        /**
         * Return details of products searched by various types
         * 
         * @param string $search search term
         * @param string $category search category         
         * @param string $searchType type of search
         * @return mixed simpleXML object
         */
        public function searchProducts($search,$ItemPage, $category = 'ALL', $searchType = "TITLE")
        {
            $allowedTypes = array("UPC", "TITLE", "ARTIST", "KEYWORD");
            $allowedCategories = array("Music", "DVD", "VideoGames");
            
            switch($searchType) 
            {
                case "UPC" :    $parameters = array("Operation"     => "ItemLookup",
                                                    "ItemId"        => $search,
                                                    "SearchIndex"   => $category,
                                                    "IdType"        => "UPC",
                                                    "ItemPage"    => $ItemPage,
                                                    "ResponseGroup" => "Medium");
                                break;
                
                case "TITLE" :  $parameters = array("Operation"     => "ItemSearch",
                                                    "Title"         => $search,
                                                    "ItemPage"    =>  $ItemPage,
                                                    "SearchIndex"   => $category,
                                                    "ResponseGroup" => "Medium");
                                break;
            
            }
            
            $xml_response = $this->queryAmazon($parameters);
            
            return $this->verifyXmlResponse($xml_response);

        }
		
		function GetRootNodeFromAnyNode( $node_id )
		{
			$fields = array();
			$fields['id'] = $node_id;
				
			$data = $this->GetParentTrace( $fields );

			return $data[0]['id'];
		}
		
		public function getItemByNode( $node_id, $page = 1, $node_root = '', $keywords = '', $region = 'com' )
        {
			if( !$node_root )
			{
				if( $node_id )
				{
					$root_node = $this->GetRootNodeFromAnyNode( $node_id );
					$search_index = $this->GetRootNodeIndexes( $root_node );
					
				}
				else
				{
					$search_index = 'All';
				}
			}
			
			$fields = array();
			$fields['Operation'] = 'ItemSearch';
			
			if( $node_id )
				$fields['BrowseNode'] = $node_id;
			
			if( $keywords )
			{
				if( $search_index == 'All' )
				{
					$fields['Keywords'] = $keywords;
				}
				else
				{
					$fields['Title'] = $keywords;
				}
			}
			
			//ItemDebug( $root_node );
			if( !$search_index )
				return;
				
			$fields['ItemPage'] = $page;
			$fields['SearchIndex'] = $search_index;
			$fields['ResponseGroup'] = 'Medium';
			
            $xml_response = $this->queryAmazon( $fields, $region );
			
			if( $xml_response->Items->Request->IsValid == 'False' )
				return is_array( $xml_response->Items->Request->Errors->Error ) ? $xml_response->Items->Request->Errors->Error[0]->Message : $xml_response->Items->Request->Errors->Error->Message;
             
            return $this->verifyXmlResponse($xml_response);
        }
        
        
        /**
         * Return details of a product searched by UPC
         * 
         * @param int $upc_code UPC code of the product to search
         * @param string $product_type type of the product
         * @return mixed simpleXML object
         */
        public function getItemByUpc($upc_code, $product_type)
        {
            $parameters = array("Operation"     => "ItemLookup",
                                "ItemId"        => $upc_code,
                                "SearchIndex"   => $product_type,
                                "IdType"        => "UPC",
                                "ResponseGroup" => "Medium");
                                
            $xml_response = $this->queryAmazon($parameters);
            
            return $this->verifyXmlResponse($xml_response);

        }
        
        
        /**
         * Return details of a product searched by ASIN
         * 
         * @param int $asin_code ASIN code of the product to search
         * @return mixed simpleXML object
         */
        public function getItemByAsin($asin_code)
        {
            $parameters = array("Operation"     => "ItemLookup",
                                "ItemId"        => $asin_code,
                                "ResponseGroup" => "Medium");
                                
            $xml_response = $this->queryAmazon($parameters);
            
            return $this->verifyXmlResponse($xml_response);
        }
		
		public function getNode($node)
		{
			
			
			$parameters = array("Operation"  => "BrowseNodeLookup",
												"BrowseNodeId"   => $node,
												"ResponseGroup" => "BrowseNodeInfo");
								
			$xml_response = $this->queryAmazon($parameters);
			
			return ($xml_response);
		}
		
		public function getBrowseNodes($nodeValue, $level = 0)
		{
			
			try {
				$result = $this->getNode($nodeValue);
			}
			catch(Exception $e) {
				echo $e->getMessage();
			}
			
			return $result;
			
			if(!isset($result->BrowseNodes->BrowseNode->Children->BrowseNode)) return;
			
			if(count($result->BrowseNodes->BrowseNode->Children->BrowseNode) > 0) {
				foreach($result->BrowseNodes->BrowseNode->Children->BrowseNode as $node) {
					/*$this->writeOut($level, $node->Name,
									$node->BrowseNodeId,
									$result->BrowseNodes->BrowseNode->Name);*/
					
					$this->getBrowseNodes($node->BrowseNodeId, $level+1);
				}
			} else {
				return;        
			}
		}
		
		function CreateParentNodeTrace( $ancestor_data = array() )
		{
			global $fail_iter;
			
			$fail_iter++;
			
			if( !$ancestor_data )
				return;
			
			$this->m_parent_trace[] = $ancestor_data;
			
			$result = $this->getNode( $ancestor_data['id'] );			
			$data = $this->ParseBrowseNodesIntoArray( $result );
			
			if( $fail_iter > 50 )
				exit;
				
			$this->CreateParentNodeTrace( $data['ancestors'][0] );
		}
		
		function GetRootNodeSet()
		{
			$data = array();
			
			$fields[] = array( 'name' => 'Apparel & Accessories', 'id' => 1036592 );
			$fields[] = array( 'name' => 'Appstore for Android', 'id' => 2350149011 );
			$fields[] = array( 'name' => 'Arts, Crafts & Sewing', 'id' => 2617941011 );
			$fields[] = array( 'name' => 'Automotive', 'id' => 15684181 );
			$fields[] = array( 'name' => 'Baby', 'id' => 165796011 );
			$fields[] = array( 'name' => 'Beauty', 'id' => 3760911 );
			$fields[] = array( 'name' => 'Books', 'id' => 283155 );
			$fields[] = array( 'name' => 'Car Toys', 'id' => 10963061 );
			$fields[] = array( 'name' => 'Cell Phones & Accessories', 'id' => 2335753011 );
			$fields[] = array( 'name' => 'Computer & Video Games', 'id' => 468642 );
			$fields[] = array( 'name' => 'Electronics', 'id' => 172282 );
			$fields[] = array( 'name' => 'Gifts & Wish Lists', 'id' => 229220 );
			$fields[] = array( 'name' => 'Grocery & Gourmet Food', 'id' => 16310211 );
			$fields[] = array( 'name' => 'Health & Personal Care', 'id' => 3760901 );
			$fields[] = array( 'name' => 'Home & Kitchen', 'id' => 1055398 );
			$fields[] = array( 'name' => 'Home Improvement', 'id' => 251266011 );
			$fields[] = array( 'name' => 'Industrial & Scientific', 'id' => 16310161 );
			$fields[] = array( 'name' => 'Jewelry', 'id' => 3367581 );
			$fields[] = array( 'name' => 'Kindle Store', 'id' => 133140011 );
			$fields[] = array( 'name' => 'Kitchen & Housewares', 'id' => 284507 );
			$fields[] = array( 'name' => 'Magazine Subscriptions', 'id' => 599858 );
			$fields[] = array( 'name' => 'Movies &amp; TV', 'id' => 2625373011 );
			$fields[] = array( 'name' => 'Music', 'id' => 5174 );
			$fields[] = array( 'name' => 'Musical Instruments', 'id' => 11091801 );
			$fields[] = array( 'name' => 'Office Products', 'id' => 1064954 );
			$fields[] = array( 'name' => 'Outlet', 'id' => 517808 );
			$fields[] = array( 'name' => 'Pet Supplies', 'id' => 2619533011 );
			$fields[] = array( 'name' => 'Shoes', 'id' => 672123011 );
			$fields[] = array( 'name' => 'Software', 'id' => 229534 );
			$fields[] = array( 'name' => 'Sports & Outdoors', 'id' => 3375251 );
			$fields[] = array( 'name' => 'Tools & Hardware', 'id' => 228013 );
			$fields[] = array( 'name' => 'Toys and Games', 'id' => 165793011 );
			$fields[] = array( 'name' => 'Travel', 'id' => 605012 );
			$fields[] = array( 'name' => 'Warehouse Deals', 'id' => 1267877011 );
			
			$data['children'] = $fields;
			
			return $data;
		}
		
		function GetParentTrace( $ancestor_data )
		{
			$this->CreateParentNodeTrace( $ancestor_data );
			
			$this->m_parent_trace = array_reverse( $this->m_parent_trace );
			
			return $this->m_parent_trace;
		}
		
		function ParseBrowseNodesIntoArray( $result )
		{
			$data = array();
			
			if( !$result )
				return;
	
			$data['parent_id'] = (string)$result->BrowseNodes->BrowseNode->BrowseNodeId;
			$data['parent_name'] = (string)$result->BrowseNodes->BrowseNode->Name;
			
			// fetch children
			if( count( $result->BrowseNodes->BrowseNode->Children->BrowseNode ) > 0 )
			{
				foreach( $result->BrowseNodes->BrowseNode->Children->BrowseNode as $key => $value )
				{
					$fields = array();
					$fields['id'] = (string)$value->BrowseNodeId;
					$fields['name'] = (string)$value->Name;
					
					if( $fields['id'] )
						$data['children'][] = $fields;
				}			
			}
			else
			{
				$fields = array();
				$fields['id'] = (string)$result->BrowseNodes->BrowseNode->Children->BrowseNode->BrowseNodeId;
				$fields['name'] = (string)$result->BrowseNodes->BrowseNode->Children->BrowseNode->Name;
				
				if( $fields['id'] )
					$data['children'][] = $fields;
			}
			
			// fetch ancestors
			if( count( $result->BrowseNodes->BrowseNode->Ancestors->BrowseNode ) > 0 )
			{
				foreach( $result->BrowseNodes->BrowseNode->Ancestors->BrowseNode as $key => $value )
				{
					$fields = array();
					$fields['id'] = (string)$value->BrowseNodeId;
					$fields['name'] = (string)$value->Name;
					
					if( $fields['id'] )
						$data['ancestors'][] = $fields;
				}			
			}
			else
			{
				$fields = array();
				$fields['id'] = (string)$result->BrowseNodes->BrowseNode->Ancestors->BrowseNode->BrowseNodeId;
				$fields['name'] = (string)$result->BrowseNodes->BrowseNode->Ancestors->BrowseNode->Name;
				
				if( $fields['id'] )
					$data['ancestors'][] = $fields;
			}
			
			return $data;
		}
		
		
		public function getNodeName($nodeValue)
		{
			try {
				$result = $this->getNode($nodeValue);
			}
			catch(Exception $e) {
				echo $e->getMessage();
			}
			
			if(!isset($result->BrowseNodes->BrowseNode->Name)) return;
			
			return (string)$result->BrowseNodes->BrowseNode->Name;
		}
		
		function GetRootNodeIndexes( $root_id = '' )
		{
			$root = array();
			$root[1036592] = 'Apparel';
			$root[672123011] = 'Apparel';
			$root[2619525011] = 'Appliances';
			$root[2617941011] = 'ArtsAndCrafts';
			$root[15690151] = 'Automotive';
			$root[15684181] = 'Automotive';
			$root[165796011] = 'Baby';
			$root[283155] = 'Books';
			$root[1000] = 'Books';
			$root[85] = 'Classical';
			$root[320031011] = 'Classical';
			$root[301668] = 'Classical';
			$root[130] = 'DVD';
			$root[172282] = 'Electronics';
			$root[3370831] = 'GourmetFood';
			$root[12923371] = 'Grocery';
			$root[16014831] = 'Grocery';
			$root[16310101] = 'Grocery';
			$root[3760911] = 'HealthPersonalCare';
			$root[3760901] = 'HealthPersonalCare';
			$root[123382011] = 'HealthPersonalCare';
			$root[286168] = 'HomeGarden';
			$root[16310091] = 'Industrial';
			$root[10368791] = 'Industrial';
			$root[3367581] = 'Jewelry';
			$root[133140011] = 'KindleStore';
			$root[133141011] = 'KindleStore';
			$root[16310331] = 'Kitchen';
			$root[1055398] = 'Kitchen';
			$root[284507] = 'Kitchen';
			$root[599858] = 'Magazines';
			$root[349027011] = 'Merchants';
			$root[10963061] = 'Merchants';
			$root[265523] = 'Merchants';
			$root[3489301] = 'Merchants';
			$root[163856011] = 'MP3Downloads';
			$root[388864011] = 'Music';
			$root[5174] = 'Music';
			$root[734368] = 'Music';
			$root[11091801] = 'MusicalInstruments';
			$root[11965861] = 'MusicalInstruments';
			$root[1064954] = 'OfficeProducts';
			$root[1064952] = 'OfficeProducts';
			$root[13900831] = 'OutdoorLiving';
			$root[573752] = 'PCHardware';
			$root[13458241] = 'PCHardware';
			$root[13900871] = 'PCHardware';
			$root[229534] = 'Software';
			$root[3386071] = 'SportingGoods';
			$root[3564781] = 'SportingGoods';
			$root[3489461] = 'SportingGoods';
			$root[3375251] = 'SportingGoods';
			$root[228013] = 'Tools';
			$root[735342] = 'Tools';
			$root[15706941] = 'Tools';
			$root[165793011] = 'Toys';
			$root[165795011] = 'Toys';
			$root[165994011] = 'Toys';
			$root[166644011] = 'Toys';
			$root[375519011] = 'Toys';
			$root[16261641] = 'UnboxVideo';
			$root[16261631] = 'UnboxVideo';
			$root[404272] = 'VHS';
			$root[468642] = 'VideoGames';
			$root[377110011] = 'Watches';
			$root[3888811] = 'Watches';
			$root[301185] = 'Wireless';
			$root[301188] = 'WirelessAccessories';
			
			if( $root_id )
				return $root[ $root_id ];
				
			return $root;
		}
		
		public function getParentNode($nodeValue)
		{
			try {
				$result = $this->getNode($nodeValue);
			}
			catch(Exception $e) {
				echo $e->getMessage();
			}
			
			if(!isset($result->BrowseNodes->BrowseNode->Ancestors->BrowseNode->BrowseNodeId)) return;
			
			$parent_node = array("id" => (string)$result->BrowseNodes->BrowseNode->Ancestors->BrowseNode->BrowseNodeId,
								 "name" => (string)$result->BrowseNodes->BrowseNode->Ancestors->BrowseNode->Name);
			return $parent_node;
		}
			
        
        /**
         * Return details of a product searched by keyword
         * 
         * @param string $keyword keyword to search
         * @param string $product_type type of the product
         * @return mixed simpleXML object
         */
        public function getItemByKeyword($keyword, $product_type)
        {
            $parameters = array("Operation"   => "ItemSearch",
                                "Keywords"    => $keyword,
                                "SearchIndex" => $product_type);
                                
            $xml_response = $this->queryAmazon($parameters);
            
            return $this->verifyXmlResponse($xml_response);
        }
		
		public function getItemByBrowseNode( $node, $product_type = '' )
        {
			if( !$product_type )
			{
				$root_node_id = $this->GetRootNodeFromAnyNode( $node );
				
				$product_type = $this->GetRootNodeIndexes( $root_node_id );
				
				if( !$product_type )
				{
					// log that it failed to get SearchIndex for node $data[0]['name'] - $root_node_id
					
					return;
				}
			}
			
            $parameters = array("Operation"   => "ItemSearch",
                                "BrowseNode"    => $node,
                                "SearchIndex" => $product_type);
                                
            $xml_response = $this->queryAmazon($parameters);
            
            return $this->verifyXmlResponse($xml_response);
        }

    }


$qody_amazon_categories = array();
$qody_amazon_categories['Apparel'] = 'Apparel';
$qody_amazon_categories['Appliances'] = 'Appliances';
$qody_amazon_categories['ArtsAndCrafts'] = 'Arts And Crafts';
$qody_amazon_categories['Automotive'] = 'Automotive';
$qody_amazon_categories['Baby'] = 'Baby';
$qody_amazon_categories['Beauty'] = 'Beauty';
$qody_amazon_categories['Blended'] = 'Blended';
$qody_amazon_categories['Books'] = 'Books';
$qody_amazon_categories['Classical'] = 'Classical';
$qody_amazon_categories['DigitalMusic'] = 'Digital Music';
$qody_amazon_categories['DVD'] = 'DVD';
$qody_amazon_categories['Electronics'] = 'Electronics';
$qody_amazon_categories['ForeignBooks'] = 'Foreign Books';
$qody_amazon_categories['Garden'] = 'Garden';
$qody_amazon_categories['GourmetFood'] = 'Gourmet Food';
$qody_amazon_categories['Grocery'] = 'Grocery';
$qody_amazon_categories['HealthPersonalCare'] = 'Health Personal Care';
$qody_amazon_categories['Hobbies'] = 'Hobbies';
$qody_amazon_categories['Home'] = 'Home';
$qody_amazon_categories['HomeGarden'] = 'Home Garden';
$qody_amazon_categories['HomeImprovement'] = 'Home Improvement';
$qody_amazon_categories['Industrial'] = 'Industrial';
$qody_amazon_categories['Jewelry'] = 'Jewelry';
$qody_amazon_categories['KindleStore'] = 'Kindle Store';
$qody_amazon_categories['Kitchen'] = 'Kitchen';
$qody_amazon_categories['LawnAndGarden'] = 'Lawn And Garden';
$qody_amazon_categories['Lighting'] = 'Lighting';
$qody_amazon_categories['Magazines'] = 'Magazines';
$qody_amazon_categories['Marketplace'] = 'Marketplace';
$qody_amazon_categories['Miscellaneous'] = 'Miscellaneous';
$qody_amazon_categories['MobileApps'] = 'Mobile Apps';
$qody_amazon_categories['MP3Downloads'] = 'MP3 Downloads';
$qody_amazon_categories['Music'] = 'Music';
$qody_amazon_categories['MusicalInstruments'] = 'Musical Instruments';
$qody_amazon_categories['MusicTracks'] = 'Music Tracks';
$qody_amazon_categories['OfficeProducts'] = 'Office Products';
$qody_amazon_categories['OutdoorLiving'] = 'Outdoor Living';
$qody_amazon_categories['Outlet'] = 'Outlet';
$qody_amazon_categories['PCHardware'] = 'PC Hardware';
$qody_amazon_categories['PetSupplies'] = 'Pet Supplies';
$qody_amazon_categories['Photo'] = 'Photo';
$qody_amazon_categories['Shoes'] = 'Shoes';
$qody_amazon_categories['Software'] = 'Software';
$qody_amazon_categories['SoftwareVideoGames'] = 'Software Video Games';
$qody_amazon_categories['SportingGoods'] = 'Sporting Goods';
$qody_amazon_categories['Tools'] = 'Tools';
$qody_amazon_categories['Toys'] = 'Toys';
$qody_amazon_categories['UnboxVideo'] = 'Unbox Video';
$qody_amazon_categories['VHS'] = 'VHS';
$qody_amazon_categories['Video'] = 'Video';
$qody_amazon_categories['VideoGames'] = 'Video Games';
$qody_amazon_categories['Watches'] = 'Watches';
$qody_amazon_categories['Wireless'] = 'Wireless';
$qody_amazon_categories['WirelessAccessories'] = 'Wireless Accessories';



$qody_amazon_regions = array();
$qody_amazon_regions['ca'] = 'ca';
$qody_amazon_regions['com'] = 'com';
$qody_amazon_regions['co.uk'] = 'co.uk';
$qody_amazon_regions['de'] = 'de';
$qody_amazon_regions['fr'] = 'fr';
$qody_amazon_regions['jp'] = 'jp';

















?>
