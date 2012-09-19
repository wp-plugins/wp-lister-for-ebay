<?php

function wpl_formatXmlString( $xml ) {

	// add marker linefeeds to aid the pretty-tokeniser (adds a linefeed between all tag-end boundaries)
	$xml = preg_replace( '/(>)(<)(\/*)/', "$1\n$2$3", $xml );

	// now indent the tags
	$token      = strtok( $xml, "\n" );
	$result     = ''; // holds formatted version as it is built
	$pad        = 0; // initial indent
	$matches    = array(); // returns from preg_matches()

	// scan each line and adjust indent based on opening/closing tags
	while ( $token !== false ) :

		// test for the various tag states

		// 1. open and closing tags on same line - no change
		if ( preg_match( '/.+<\/\w[^>]*>$/', $token, $matches ) ) :
			$indent=0;
		// 2. closing tag - outdent now
		elseif ( preg_match( '/^<\/\w/', $token, $matches ) ) :
			$pad--;
			$pad--;
		// 3. opening tag - don't pad this one, only subsequent tags
		elseif ( preg_match( '/^<\w[^>]*[^\/]>.*$/', $token, $matches ) ) :
			$indent=2;
		// 4. no indentation needed
		else :
			$indent = 0;
		endif;


	// pad the line with the required number of leading spaces
	$line    = str_pad( $token, strlen( $token )+$pad, ' ', STR_PAD_LEFT );
	$result .= $line . "\n"; // add to the cumulative result, with linefeed
	$token   = strtok( "\n" ); // get the next token
	$pad    += $indent; // update the pad size for subsequent lines
	endwhile;

	return $result;
}

$url = $wpl_row->request_url;
$req = $wpl_row->request;
$res = $wpl_row->response;
$id  = $wpl_row->id;


// hide Description content for better readability
if ( @$_GET['desc'] != 'show' ) {
	$description_link = '<a href="admin.php?page=wplister&action=display_log_entry&desc=show&log_id='.$id.'">show description</a>';
	$req = preg_replace( "/<Description>.*<\/Description>/uUsm", "<Description> ... ___desc___ ... </Description>", $req );
}


include_once ('PEAR.php');
if ( class_exists('PEAR') ) {
	// add XML dir to include path
	$incPath = WPLISTER_PATH.'/includes';
	set_include_path( get_include_path() . ':' . $incPath );

	// use XML_Beautifier.php to format XML
	define('XML_BEAUTIFIER_INCLUDE_PATH', WPLISTER_PATH.'/includes/XML/Beautifier');
	include_once WPLISTER_PATH.'/includes/XML/Beautifier.php';
	$fmt = new XML_Beautifier();
	$req = $fmt->formatString($req);
	$req .= '<!-- XML_Beautifier -->';

} else {
	// use build in function to format XML
	$req = wpl_formatXmlString( $req );
	$req .= '<!-- wpl_formatXmlString() -->';
}


$req = htmlspecialchars( $req );

// replace placeholder with link after htmlspecialchars()
if ( isset($description_link) ) $req = preg_replace( "/___desc___/", $description_link, $req );

?><html>
<head>
    <title>request details</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style type="text/css">
        pre {
        	background-color: #eee;
        	border: 1px solid #ccc;
        	padding: 20px;
        }
    </style>
</head>

<body>
	<div style="float:right;margin-top:10px;">
		<a href="<?php echo $_SERVER['REQUEST_URI']; ?>&send_to_support=yes" target="_blank">send to support</a> &middot;
		<a href="<?php echo $_SERVER['REQUEST_URI']; ?>" target="_blank">open in new tab</a>
	</div>

    <h2>Call: <?php echo $wpl_row->callname ?> (#<?php echo $wpl_row->id ?>)</h2>

    <h3>Request URL</h3>
    <pre><?php echo $url ?></pre>

    <h3>Request</h3>
    <pre><?php echo $req ?></pre>

    <h3>Response</h3>
    <pre><?php echo htmlentities( $res ) ?></pre>

    <h3>Debug Info</h3>
    <pre>
    	WP-Lister: <?php echo $wpl_version ?>

    	Database : <?php echo get_option('wplister_db_version') ?>

    	PHP      : <?php echo phpversion() ?>

    	WordPress: <?php echo get_bloginfo ( 'version' ) ?>
    	
    	Locale   : <?php echo get_bloginfo ( 'language' ) ?>

    	Charset  : <?php echo get_bloginfo ( 'charset' ) ?>

    	Site URL : <?php echo get_bloginfo ( 'wpurl' ) ?>
    	
    	Admin    : <?php echo get_bloginfo ( 'admin_email' ) ?>
    </pre>


</body>
</html>
