<?php
// Robb Fiorillo
// 10/23/2012

class Sortable
{
	// ------------------------------------------------------------
	// ------------------------------------------------------------
	//	P R I V A T E   V A R I A B L E S
	//
	private $listingData;			// array representing every listing available from the input file
	private $productData;			// array representing every product available from the input file
	private $resultsArray;			// array representing the matched results 
	private $duplicateResultsArray;	// array representing the matched results 

	// ********************************************************************
	//						C O N S T R U C T O R 	
	// ********************************************************************
	/**
	 * possibly we could take in parameters so that the program act differently
	 * for example take in differently named files, or output the products WITHOUT
	 * matching listings .. all of these can be passed into the constructor and set up
	 */
	public function __construct()
	{
		$this->traceFunction ( "(CONSTRUCTOR)", true, true  );

		$this->init();
	}//C O N S T R U C T O R 
	
	// ********************************************************************
	//							init	
	// ********************************************************************
	/**
	 * initializes all data objects that need to persist throughout this class
	 * 
	 * @params	none
	 * @returns nothing
	 */
	private function init()
	{
		$this->traceFunction ( "(init)", true, true );
		
		// This array is going to store the matches and pushed to the output file
		$this->resultsArray = array();
		$this->duplicateResultsArray = array();
	}//init
	
	// ********************************************************************
	//							run
	// ********************************************************************
	/**
	 * runs the application by testing the files and finding the matches
	 *
	 * @params	none
     * @returns nothing
	 */
	public function run()
	{
		// if the files are ok .. lets find the matches
		if ( $this->testFiles() )
		{
			// run the logic to match the listings with the products
			$this->findMatches();
			
			// we might want to send the results elsewhere .. or do something more to it
			// before we actually output it
			$this->outputResults();
		}//if
	}//run

	// ********************************************************************
	//							createRegex	
	// ********************************************************************
	/**
	 * replaces all non-alphanumeric characters with a regex to allow for optional non-alphanumeric
	 * as well, allows for an optional non-alphanumeric character between numbers and letters
	 * IE. DSC-101 should match DSC 101
	 * 
	 * @params	value	    	 <String> what we will be needing to search for
     * @returns returnExpression <String> as a regular expression
	 */
	private function createRegex( $value ) 
	{
		$returnExpression = preg_replace( '/([a-z])(\d)/i' , '$1[^a-z\d]*$2' , preg_replace( '/(\d)([a-z])/i' , '$1[^a-z\d]*$2' , preg_replace( '/[^a-z\d]+/i' , '[^a-z\d]*' , $value ) ) );
		$returnExpression = '/\b' . $returnExpression . '\b/i';
		return $returnExpression;
	}//createRegex

	// ********************************************************************
	//							addToOutput	
	// ********************************************************************
	/**
	 * given the input, adds the listing to the array indexed by productName
	 * only if the exact listing isn't already in the array.  It is possible
	 * to have identical listings in the listing data .. we will remove the dups
	 * 
	 * @params	listing	    <object> representing the listing that matched the product
	 * 		    productName <String> representing the product that was matched
     * @returns true if the listing was added, false otherwise
	 */
	private function addToOutput( &$listing , $productName )
	{
		// innocent until proven guilty
		$duplicateListingFound = false;
		
		// lets see if this EXACT listing was already entered
		if ( in_array( $productName,  array_keys($this->resultsArray ) ))
		{
			$duplicateListingFound = in_array( $listing , $this->resultsArray[ $productName ] );
		}//if
	
		// its possible that the listing is a duplicate .. so lets NOT add it
		if ( !$duplicateListingFound )
		{
			$this->resultsArray[ $productName ][] = $listing;
		}//if
		
		return !$duplicateListingFound;
	}//addToOutput

	// ********************************************************************
	//							addToDuplicates	
	// ********************************************************************
	/**
	 * given the input, adds the listing to the array indexed by productName
	 * 
	 * @params	productName <String> representing the product that was matched
	 *			listing	    <object> representing the listing that matched the product
     * @returns nothing
	 */
	private function addToDuplicates( $listingTitle , &$productsArray )
	{
		$this->duplicateResultsArray[ $listingTitle ] = $productsArray;
		
	}//addToDuplicates
	
	// ********************************************************************
	//							testFiles	
	// ********************************************************************
	/**
	 * verifies that the input files are readable, and output file is writable
	 * 
	 * @params	none
     * @return  true if all 3 files are ok, false otherwise
	 */
	private function testFiles(  )
	{
		$this->traceFunction  ( "(testFiles)", true, true );
		
		$result = true;
	
		// lets first check that we could write to the output file
		$resultsWritten = file_put_contents( OUTPUT_FILE , "" );
		$resultsWrittenForDuplicates = file_put_contents( DUPLICATES_FILE , "" );

		// looks like the output files have issues
		if ( $resultsWritten === FALSE || $resultsWrittenForDuplicates === FALSE )
		{
			// probably best to output there was an error 
			$result = false;
		}//if

		// lets see if we have access to the other files.  If we don't, there is no point moving forward either
		$this->listingData = file_get_contents( LISTING_FILE );
		$this->productData = file_get_contents( PRODUCTS_FILE );

		// lets see if we got anything
		if ( $this->listingData === FALSE || $this->productData === FALSE )
		{
			// NOPE!! .. bad data
			// overwrite the results file and let someone know what happened.. and we know we can do that cause we got this far
			file_put_contents( OUTPUT_FILE , "There was an ERROR reading either the " . LISTING_FILE . " or the " . PRODUCTS_FILE );
			$result = false;
		}//if
		
		return $result;
	}//testFiles

	
	// ********************************************************************
	//							findMatches	
	// ********************************************************************
	/**
	 * for each listing in the listing array, it looks for a matching product
	 * Algorithm:   First find a matching manufacturer
	 *				than find the model within the listing title
	 *              finally see if the family is also in the title
     * if there is a match, it adds it to the output array. However, it also keeps
	 * track of how many are found .. because if more than 1 is found, than it
	 * cancels out, since only 1 product can match a listing, and obviously
	 * the listing has TOO many key words for software to match.
	 * This SNEAKY little listing is added to another array to be handled by the boss ( humans )
	 * 
	 * @params	none
     * @returns  nothing
	 */
	private function findMatches(  )
	{	
		// YIPPY .. lets get crackin'!!
		$this->traceFunction ( "(findMatches)", true, true  );
	
		// get all of the listings in a form that we could easily search and manipulate
		$listingArray = explode( "\n" , $this->listingData );
		foreach( $listingArray as &$listing )
		{ 
			// we need to be able to compare the values properly .. 
			// so replace the json string with the object we can understand
			$listing = json_decode( $listing , true );
		}//foreach

		// get all of the products in a form that we could easily search and manipulate
		$productArray = explode( "\n" , $this->productData );
		foreach( $productArray as &$product )
		{ 
			// we need to be able to compare the values properly .. 
			// so replace the json string with the object we can understand
			$product = json_decode( $product , true );
		}//foreach

		// a couple of counters .. for fun
		// we can't rely on the resultsArray .. cause we JUST ASSUMED that only the correct
		// matches are supposed to go in there .. if ALL matches are supposed to be there .. 
		// we still have this manual count
		$counters = array( 'matched' => 0, 'total' => 0 );

		// since a listing can only match a single product, the outter loop will be 
		//	that of the listings... so when it is found, OR not found with certainty .. we move on
		foreach( $listingArray as &$listing )
		{ 
			//$this->traceFunction ( "(findMatches) LOOKING FOR " . $listing['title'] . "\n", true );
			$this->traceFunction ( ".", true );

			// tracking how many we have total
			$counters[ 'total' ] += 1;
						
			// what do we do if the listing manufacturer is not available?
			// our logic is dependant on the listing having a manufacturer
			if ( empty( $listing[ 'manufacturer' ] ) )
			{
				// for now we continue
				continue;
			}//if
			
			$matched = false;
			
			// this array will store the products that match the listing .. hopefully it will be just one
			$matchedProducts = array();
			
			// lets go through all of the products and see if this listing has a match
			foreach( $productArray as $product ) 
			{
			
				// product data MUST be complete .. or it is discarded
				if ( 	empty( $product[ 'manufacturer' ] ) || 
						empty( $product[ 'model' ] ) || 
						empty( $product[ 'family' ] ) 
					)
				{				
					// we could track which product so that the data could be repopulated in the future
					continue;
				}//if
			
				// lets set up the regular expressions so we could search within the listing data
				$manufacturer = $this->createRegex( $product[ 'manufacturer' ] );
				$model = $this->createRegex( $product[ 'model' ] );
				$family = $this->createRegex( $product[ 'family' ] );
			
				//  the most important thing to check is that the manufacturer matches .. if the model exists within
				//	the title, it could be a false positive, 
				//	(ie. a "Canon DSC121 Lens cleaner" made by ACME is NOT the actual camera NOR the lens )
				if ( preg_match( $manufacturer, $listing[ 'manufacturer' ] ) )
				{
					// now that we know the manufacturer is a match
					// lets see if the model exists with the title
					// its not a perfect match yet .. cause a title:"Canon DSC121 Lens" is NOT a model:"DSC121"
					// but without the model matching .. then we have a certain failure just like not matching the manufacturer
					// just because title:"Canon Camera" is model:"DSC121" .. the reverse is NOT a guarantee
					if ( preg_match( $model, $listing[ 'title' ] ) ) 
					{
						
						// only thing left to see is family is in the title as well
						// Without a human set of eyes .. there is no way to tell because
						//       product : Canon_CyberShot_DSC121
						//	       model : DSC121
						//        family : CyberShot
						//	manufacturer : Canon
						//			WILL MATCH INCORRECTLY TO
						//       listing : Canon CyberShot DSC121 Lens
						//  manufacturer : Canon
						
						// but we just gotta hope that the listing doesn't do that to us
						if ( preg_match( $family, $listing[ 'title' ] ) )
						{
							// tracking how many we have total
							$counters[ 'matched' ] += 1;

							//$this->traceFunction ( "(findMatches) MATCHED: " . $product[ 'product_name' ] . "\n", true );
							$this->traceFunction ( "x", true );
							
							// add it to the results
							array_push( $matchedProducts , $product[ 'product_name' ] );
							$matched = true;
						}//if
					
					}//if
					
				}
				else
				{
					// DO NOTHING .. all can match, but if its not built by the correct
					//	manufacturer .. its NOT the same product
				}//if
				
			}//foreach
			
			// if there are more than 1 match .. add it to a different array 
			// so that a human pair of eyes can see whats going on
			if ( sizeof( $matchedProducts ) > 1 )
			{
				$this->addToDuplicates( $listing[ 'title' ] , $matchedProducts );
			}
			// we either have 1 match or 0 matches .. 
			else if ( sizeof( $matchedProducts ) == 1 )
			{
				$this->addToOutput( $listing , $matchedProducts[ 0 ] );
			}//if
			
		}//foreach
		
		// for good measure .. output the results
		$this->traceFunction ( "(outputResults) " . $counters[ 'matched' ] . " / " . $counters[ 'total' ] . " matched", true, true );

	}//findMatches
	
	// ********************************************************************
	//							outputResults	
	// ********************************************************************
	/**
	 * iterates through the array and outputs to the appropriate file
	 * 
	 * @params	none
	 * @returns nothing
	 */
	private function outputResults(  )
	{		
		// iterate through the resultsArray and output line by line what matches we have found
		foreach( $this->resultsArray as $productName => $result )
		{
			$jsonString = array( 'product_name' => $productName, 'listings' => $result );
			file_put_contents( OUTPUT_FILE, json_encode( $jsonString )."\n", FILE_APPEND );
		}//foreach
		
		// also output the duplicate listing / products
		foreach( $this->duplicateResultsArray as $listingTitle => $result )
		{
			$jsonString = array( 'listing_title' => $listingTitle, 'products' => $result );
			file_put_contents( DUPLICATES_FILE, json_encode( $jsonString )."\n", FILE_APPEND );
		}//foreach

	}//outputResults

	// ********************************************************************
	//							traceFunction	
	// ********************************************************************
	/**
	 * outputs the message .. and flushes the buffer if flushbuffer is set
	 * 
	 * @params	message	<string> 
	 * @returns nothing
	 */
	private function traceFunction( $message , $flushBuffer=true, $addNewLine=false )
	{	
		$newLine = ( $addNewLine ) ? "<br>" : "";
		echo ( $message . $newLine );
		if ( $flushBuffer )
		{
			flush();
		}
	}//traceFunction
	
}//
?>