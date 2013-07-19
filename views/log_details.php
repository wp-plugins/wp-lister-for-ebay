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


// try to include PEAR and hide php warnings on fail
@include_once ('PEAR.php');
if ( class_exists('PEAR') ) {
	// add XML dir to include path
	$incPath = WPLISTER_PATH.'/includes';
	set_include_path( get_include_path() . ':' . $incPath );

	// use XML_Beautifier.php to format XML
	define('XML_BEAUTIFIER_INCLUDE_PATH', WPLISTER_PATH.'/includes/XML/Beautifier');
	include_once WPLISTER_PATH.'/includes/XML/Beautifier.php';
	$fmt = new XML_Beautifier();
	$formatted_req = $fmt->formatString($req);

	// check if XML_Beautifier returned an error
    if ( PEAR::isError($formatted_req) ) {

		// fall back to build in formatter
		$req = wpl_formatXmlString( $req );
		$req .= '<!-- wpl_formatXmlString() -->';

    } else {
		$req = $formatted_req . '<!-- XML_Beautifier -->';
    }


} else {
	// use build in function to format XML
	$req = wpl_formatXmlString( $req );
	$req .= '<!-- wpl_formatXmlString() -->';
}

// remove <![CDATA[ * ]]> tags for readibily
$req = str_replace('<![CDATA[', '', $req);
$req = str_replace(']]>', '', $req);

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
        #support_request_wrap {
        	margin-top: 15px;
        	padding: 20px;
        	padding-top: 0;
        	background-color:#ffc;
        	border: 1px solid #cca;
        	display: none;
        }
        #support_request_wrap label {
			float: left;
        	width: 25%;
        	line-height: 23px;
        }
        #support_request_wrap .text-input,
        #support_request_wrap textarea {
        	width: 70%;
        }
    </style>
</head>

<body>

	<?php if ( ( ! isset($_REQUEST['send_to_support']) ) && ( ! isset($_REQUEST['new_tab']) ) ) : ?>
		<div id="support_request_wrap" style="">
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" target="_blank" >
				<input type="hidden" name="log_id" value="<?php echo $wpl_row->id ?>" />
				<input type="hidden" name="send_to_support" value="yes" />

				<h2><?php echo __('Send to support','wplister') ?></h2>
				Please to to provide as many details as possible about what steps you took and what we might need to do to reproduce the issue.
				<br><br>

				<label for="user_name"><?php echo __('Your Name','wplister') ?></label>
				<input type="text" name="user_name" value="" class="text-input"/>
				
				<label for="user_email"><?php echo __('Your Email','wplister') ?></label>
				<input type="text" name="user_email" value="<?php echo get_bloginfo ( 'admin_email' ) ?>" class="text-input"/>
				
				<label for="user_msg"><?php echo __('Your Message','wplister') ?></label>
				<textarea name="user_msg"></textarea>
				<br style="clear:both"/>

				<input type="submit" value="<?php echo __('Send to support','wplister') ?>" class="button-primary"/>
			</form>			
		</div>

		<div style="float:right;margin-top:10px;">
			<!-- <a href="<?php echo $_SERVER['REQUEST_URI']; ?>&send_to_support=yes" target="_blank">send to support</a> &middot; -->
			<a href="#" onclick="jQuery('#support_request_wrap').slideToggle();return false;" class="button-secondary"><?php echo __('Send to support','wplister') ?></a>&nbsp;
			<a href="<?php echo $_SERVER['REQUEST_URI']; ?>&new_tab=yes" target="_blank" class="button-secondary">Open in new tab</a>
		</div>
	<?php endif; ?>

    <h2>Call: <?php echo $wpl_row->callname ?> (#<?php echo $wpl_row->id ?>)</h2>

    <h3>Request URL</h3>
    <pre><?php echo $url ?></pre>

    <h3>Request</h3>
    <pre><?php echo $req ?></pre>

    <h3>Response</h3>
    <pre><?php echo htmlentities( $res ) ?></pre>

    <h3>Debug Info</h3>
    <pre>
    	WP-Lister: <?php echo $wpl_version ?> <?php echo WPLISTER_LIGHT ? '' : 'Pro' ?>

    	Database : <?php echo get_option('wplister_db_version') ?>
    	
    	PHP      : <?php echo phpversion() ?>
    	
    	WordPress: <?php echo get_bloginfo ( 'version' ); echo ' (' . ProductWrapper::plugin . ')' ?>    	
    	Locale   : <?php echo get_bloginfo ( 'language' ) ?>

    	Charset  : <?php echo get_bloginfo ( 'charset' ) ?>

    	Site URL : <?php echo get_bloginfo ( 'wpurl' ) ?>
    	
    	Admin    : <?php echo get_bloginfo ( 'admin_email' ) ?>

    	Email    : <?php echo get_option('wplister_license_email') ?>
    </pre>


</body>
</html>
