<?php
// Robb Fiorillo
// 10/23/2012

// xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
// xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
//	A S S U M P T I O N S 
//
//  INSTRUCTION:
//  A file full of Result objects is what your solution will be generating. 
//	A Result simply associates a Product with a list of matching Listing objects.

//	Assumption 1: the results file will ONLY contain products with at least 1 matching listing

//	Assumption 2: only unique listings will be matched to products.  IF there is a duplicate
//					listing entry in the listing data file, it will be ignored

// xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
// xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

// 	Lets ensure that the script has enough time to run .. 
//	you never know how many products / listings are going to be compared 
set_time_limit( 0 );


// ------------------------------------------------------------
// ------------------------------------------------------------
//	R E Q U I R E S

require( "Sortable.class.php" );  // the main logic which reads, analyzes and write the challenge results

// these are all of the files needed. 
// ------------------------------------------------------------
// ------------------------------------------------------------
//	C O N S T A N T S
define( 'OUTPUT_FILE' , 'results.txt' );
define( 'DUPLICATES_FILE' , 'duplicateResults.txt' );
define( 'LISTING_FILE' , 'listings.txt' );
define( 'PRODUCTS_FILE' , 'products.txt' );

// instantiate the class .. and run it
$sortable = new Sortable();
$sortable->run();

?>