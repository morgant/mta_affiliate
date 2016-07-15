<?php

// This is a PLUGIN TEMPLATE.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Uncomment and edit this line to override:
$plugin['name'] = 'mta_affiliate';

$plugin['version'] = '0.1';
$plugin['author'] = 'Morgan Aldridge';
$plugin['author_uri'] = 'http://www.makkintosshu.com/';
$plugin['description'] = 'Tag iTunes/iBooks/App Store links with affiliate IDs to earn commissions.';

// Plugin types:
// 0 = regular plugin; loaded on the public web side only
// 1 = admin plugin; loaded on both the public and admin side
// 2 = library; loaded only when include_plugin() or require_plugin() is called
$plugin['type'] = 1; 


@include_once('zem_tpl.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---

h2. mta_affiliate

This plug-in will update appropriate URLs with versions tagged with your "iTunes Affiliate Program":http://www.apple.com/itunes/affiliates/ affiliate ID.

The following preferences need to be set and can be later changed from the "mta_affiliate" tab under the "Extensions" tab:

* *Tag iTunes Affiliate Program Links* - Whether you want to tag iTunes Affiliate Program links or not. This makes it easy to enable/disable affiliate link tagging without having to remove tags from pages or forms where they might have been used.
* *iTunes Affiliate Program ID* - Your iTunes Affiliate Program affiliate ID.

It also implements the following container tag:

h3. mta_affiliate

h4. Syntax

The @mta_affiliate@ tag has the following syntactic structure:

@<txp:mta_affiliate></txp:mta_affiliate>@

h4. Attributes

The @mta_affiliate@ tag will accept the following attribute (note: attributes are *case sensitive*):

@itunes_affiliate_id="string"@

This optional attribute, when set, will override the iTunes Affiliate Program affiliate ID set in the @mta_affiliate@ plug-in's preferences.

h4. Example

@<txp:mta_affiliate><txp:link /></txp:mta_affiliate>@

h3. Change Log

v0.1 Initial release.

# --- END PLUGIN HELP ---
<?php
}

# --- BEGIN PLUGIN CODE ---


/* 
 * Admin Interface
 */
if ( @txpinterface == 'admin' )
{
	// only publishers & managing editrs should have permission to use this plug-in
	add_privs('mta_affiliate', '1,2');
	
	// add the tab & register the callback
	register_tab('extensions', 'mta_affiliate', 'mta_affiliate');
	register_callback('mta_affiliate_admin_tab', 'mta_affiliate');
}

function mta_affiliate_admin_tab($event, $step)
{
	global $prefs;
	
	$itunes_enabled = isset($prefs['mta_affiliate_itunes_enabled']) ? $prefs['mta_affiliate_itunes_enabled'] : false;
	$itunes_affiliate_id = isset($prefs['mta_affiliate_itunes_id']) ? $prefs['mta_affiliate_itunes_id'] : '';
	
	$publish_form = '';
	$sections = array();
	
	//$prefs = get_prefs();
	
	pagetop('mta_affiliate ', ($step == 'update' ? 'mta_affiliate Preferences Saved' : ''));
	
	// was the 'publish' button clicked?
	if ( $step == 'update' )
	{
		// store our "iTunes Enabled" preference
		$itunes_enabled= ps('mta_affiliate_itunes_enabled');
		if ( isset($prefs['mta_affiliate_itunes_enabled']) )
		{
			safe_update('txp_prefs', "val = '".$itunes_enabled."'", "name = 'mta_affiliate_itunes_enabled'");
		}
		else
		{
			safe_insert('txp_prefs', "prefs_id=1,name='mta_affiliate_itunes_enabled',val='".$itunes_enabled."'");
		}
		$prefs['mta_affiliate_itunes_enabled'] = $itunes_enabled;
		
		// store out "iTunes affiliate ID" preference
		$itunes_affiliate_id = ps('mta_affiliate_itunes_id');
		if ( isset($prefs['mta_affiliate_itunes_id']) )
		{
			safe_update('txp_prefs', "val = '".$itunes_affiliate_id."'", "name = 'mta_affiliate_itunes_id'");
		}
		else
		{
			safe_insert('txp_prefs', "prefs_id=1,name='mta_affiliate_itunes_id',val='".$itunes_affiliate_id."'");
		}
		$prefs['mta_affiliate_itunes_id'] = $itunes_affiliate_id;
	}
		
	// build the publish form
	$publish_form .= eInput('mta_affiliate')."\n";
	$publish_form .= sInput('update')."\n";
	$publish_form .= "<fieldset><legend>iTunes Affiliate Program</legend>\n";
	$publish_form .= "<label for=\"mta_affiliate_itunes_enabled\">Enable iTunes Affiliate Program links?</label>&nbsp;";
	$publish_form .= yesnoRadio('mta_affiliate_itunes_enabled', $itunes_enabled)."<br />\n";
	$publish_form .= "<label for=\"mta_affiliate_itunes_id\">iTunes Affiliate Program affiliate ID:</label>&nbsp;";
	$publish_form .= fInput("text", 'mta_affiliate_itunes_id', $itunes_affiliate_id)."\n";
	$publish_form .= "\n</fieldset>\n";
	$publish_form .= fInput('submit', 'submit', 'Save')."\n";
	
	// output the publish form
	print(form($publish_form, 'margin-left: auto; margin-right: auto;'));
	
}

function mta_affiliate($atts, $thing)
{
	global $prefs;
	
	extract(lAtts(array(
		'itunes_affiliate_id' => ''
	),$atts));
	
	if ( ($prefs['mta_affiliate_itunes_enabled'] == true) && (!empty($prefs['mta_affiliate_itunes_id']) || !empty($itunes_affiliate_id)) ) {
		// find all the iTunes affiliate links in the contents
		// See https://affiliate.itunes.apple.com/resources/documentation/linking-to-the-itunes-music-store/
		$thing = preg_replace('/(https?:\/\/itunes\.apple\.com\/[a-z]{2}\/[a-z0-9]+(?:\/[a-z0-9_+-]+)?\/id[0-9]+)(?:(\?[a-z0-9=%&+_-]+))?/gi', mta_affiliate_replace_itunes_links_callback, $thing);
	}
	
	return $thing;
}

function mta_affiliate_replace_itunes_links_callback($matches)
{
	global $prefs;
	
	// if there's no query string on the URL, then just append a query string containing the iTunes Affiliate Program affiliate ID
	if ( empty($matches[2]) )
	{
		$affiliateURL = $matches[0] . '?at=' . $prefs['mta_affiliate_itunes_id'];
	} else {
		// otherwise, is there an affiliate ID in the query string we need to replace?
		$affiliateURL = $matches[1] . '?' . preg_replace('/([&^])at=[a-z0-9]([&$])/gi', 'at=' . $prefs['mta_affiliate_itunes_id'], $match[2], $count);
		if ( $count == 0 )
		{
			// if there weren't any affiliate IDs to be replaced in the query string, just append it to the query string
			$affiliateURL = $matches[1] . '?' . $matches[2] . '&at=' . $prefs['mta_affiliate_itunes_id'];
		}
	}
	
	return $affiliateURL;
}

# --- END PLUGIN CODE ---

?>
