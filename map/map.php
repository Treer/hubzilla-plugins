<?php

/**
 *
 * Name: ownMapp
 * Description: Provides location services like storing and sharing physical location data
 * Version: 1.2.2
 * Author: Andrew Manning <andrew@reticu.li>
 * MinVersion: 1.4.2
 *
 */

/**
 * @brief Return the current plugin version
 *
 * @return string Current plugin version
 */
function map_get_version() {
    return '1.2.2';
}

require_once('include/permissions.php');
require_once('include/items.php');
require_once('include/acl_selectors.php');

function map_load() {
    register_hook('feature_settings', 'addon/map/map.php', 'map_settings');
    register_hook('feature_settings_post', 'addon/map/map.php', 'map_settings_post');
    register_hook('load_pdl', 'addon/map/map.php', 'map_load_pdl');
    logger("Installed Location Services");
}

function map_unload() {
    unregister_hook('load_pdl', 'addon/map/map.php', 'map_load_pdl');
    unregister_hook('feature_settings', 'addon/map/map.php', 'map_settings');
    unregister_hook('feature_settings_post', 'addon/map/map.php', 'map_settings_post');    
    logger("Removed Location Services");
}

function map_install() {
    set_config('map', 'dropTablesOnUninstall', 0);
    $errors = map_create_database_table();
    
    if ($errors) {
        // Alert the user somehow and log the error
        notice('Error creating the locserv database tables');
        logger('Error creating the locserv database tables: ' . $errors);
    } else {
        info('Location Services database table installed successfully');
        logger('Location Services database table installed successfully');
    }
    return;
}

function map_uninstall() {
    $errors = false;
    $dropTablesOnUninstall = intval(get_config('map', 'dropTablesOnUninstall'));
    logger('ownMapp uninstall drop tables admin setting: ' . $dropTablesOnUninstall);
    if ($dropTablesOnUninstall === 1) {
        $r = q('DROP TABLE IF EXISTS `locserv-dynamic-markers`;');
        if (!$r) {
            $errors .= t('Errors encountered deleting database table locserv-dynamic-markers.') . EOL;
        }
        $r = q('DROP TABLE IF EXISTS `locserv-static-markers`;');
        if (!$r) {
            $errors .= t('Errors encountered deleting database table locserv-static-markers.') . EOL;
        }
        $r = q('DROP TABLE IF EXISTS `locserv-layers`;');
        if (!$r) {
            $errors .= t('Errors encountered deleting database table locserv-layers.') . EOL;
        }

        if ($errors) {
            notice('Errors encountered deleting ownMapp database tables.');
            logger('Errors encountered deleting ownMapp database tables: ' . $errors);
        } else {
            info('ownMapp uninstalled successfully. Database tables deleted.');
            logger('ownMapp uninstalled successfully. Database tables deleted.');
        }
    } else {
        info('ownMapp uninstalled successfully.');
        logger('ownMapp uninstalled successfully.');
    }
    del_config('map', 'dropTablesOnUninstall');
    return;
}

function map_module() {
    return;
}

function map_settings_post(&$a,&$b) {

	if($_POST['map-submit']) {
		set_pconfig(local_channel(),'map','autotrack',intval($_POST['autotrack']));
		set_pconfig(local_channel(),'map','autosave',intval($_POST['autosave']));
		info( t('Map settings updated.') . EOL);
	}
}


function map_settings(&$a,&$s) {
    
	if(! local_channel())
		return;
	$autotrack = get_pconfig(local_channel(),'map','autotrack');
	$autosave = get_pconfig(local_channel(),'map','autosave');

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('autotrack', t('Automatically enable location tracking when map loads.'), $autotrack, '', $yes_no),
	));

	$sc .= replace_macros(get_markup_template('field_checkbox.tpl'), array(
		'$field'	=> array('autosave', t('Automatically save location data when tracking.'), $autosave, '', $yes_no),
	));

	$s .= replace_macros(get_markup_template('generic_addon_settings.tpl'), array(
		'$addon' 	=> array('map', '<img src="addon/map/map.png" style="width:auto; height:1em; margin:-3px 5px 0px 0px;">' . t('Map Settings'), '', t('Submit')),
		'$content'	=> $sc
	));

	return;

}

function map_plugin_admin_post(&$a) {
    $dropTablesOnUninstall = ((x($_POST, 'dropTablesOnUninstall')) ? intval($_POST['dropTablesOnUninstall']) : 0);
    logger('ownMapp drop tables admin setting: ' . $dropTablesOnUninstall);
    set_config('map', 'dropTablesOnUninstall', $dropTablesOnUninstall);
    info(t('Settings updated.') . EOL);
}

function map_plugin_admin(&$a, &$o) {
    logger('ownMapp admin');
    $t = get_markup_template("admin.tpl", "addon/map/");

    $dropTablesOnUninstall = get_config('map', 'dropTablesOnUninstall');
    if (!$dropTablesOnUninstall)
        $dropTablesOnUninstall = 0;
    $o = replace_macros($t, array(
        '$submit' => t('Submit Settings'),
        '$dropTablesOnUninstall' => array('dropTablesOnUninstall', t('Drop tables when uninstalling?'), $dropTablesOnUninstall, t('If checked, the ownMapp database tables will be deleted When the ownMapp plugin is uninstalled.')),
    ));
}

function map_init($a) {   
    if (argc() > 1 && argv(1) === 'import') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $channel_id = App::get_channel()['channel_id'];
            if (!$channel_id)
                return;
            logger('map data import launching. Channel ID: ' . $channel_id);
            map_import_data($a, $channel_id);
            return;
        }       
    }
    if (argc() > 1 && argv(1) === 'export') {
        // Data export API: map/export/markers
        if (argc() > 2 && argv(2) === 'markers') {
            logger('map plugin: export API invoked: markers');
            $ret = true;
            $markers = map_getMyMarkers($ret);
            if ($markers === null) {
                notice('No markers found.');
            } else {
                header('content-type: application/octet_stream');
                header('content-disposition: attachment; filename="' . App::get_channel()['channel_address'] . '_map_markers' . '.json"');
                echo $markers;
                killme();
            }
        }
        // Data export API: map/export/full
        if (argc() > 2 && argv(2) === 'full') {
            logger('map plugin: export API invoked: full');
            $ret = true;
            $markers = map_getMyMarkers($ret);
            $history = map_getLocationHistory('1970-01-01 00:00:00', '2500-01-01 00:00:00', true);
            if ($markers === null) {
                notice('No markers found.');
            } 
            if ($history === null) {
                notice('No location history found.');
            }
            $export = json_encode(array('markers' => $markers, 'history' => $history));

            header('content-type: application/octet_stream');
            header('content-disposition: attachment; filename="' . App::get_channel()['channel_address'] . '_map_data' . '.json"');
            echo $export;
            killme();
        }
    }
    $_SESSION['data_cache'] = array();
    // If certain public API functions are invoked, call those functions before 
    // authenticating
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['action']) && strlen($_GET['action'])) {
            $action = $_GET['action'];
            if (isset($_GET['data']) && strlen($_GET['data'])) {
                $data = json_decode($_POST['data'], true);
            } else {
                $data = null;
            }
            switch ($action) {
                case 'getshareddata':
                    logger('map plugin: getshareddata API invoked via GET');
                    $token = '';
                    if (isset($_GET['token'])) {
                        $token = $_GET['token'];
                    }
                    map_getSharedData($token);
                    break;
                case 'getLatestLocation':
                    logger('map plugin: getLatestLocation API invoked via GET');
                    $token = '';
                    if (isset($_GET['token'])) {
                        $token = $_GET['token'];
                    }
                    $_SESSION['data_cache']['token'] = $token;
                    $_SESSION['data_cache']['apiaction'] = 'getLatestLocation';
                    break;
                case 'getStaticMarker':
                    logger('map plugin: getStaticMarker API invoked via GET');
                    $token = '';
                    if (isset($_GET['token'])) {
                        $token = $_GET['token'];
                    }
                    $_SESSION['data_cache']['token'] = $token;
                    $_SESSION['data_cache']['apiaction'] = 'getStaticMarker';
                default:
                    logger('map plugin: API invoked with an invalid action parameter');
            }
        } 
    }
    
    // If the map is accessed by POST, the API is being invoked
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Determine the API method invoked by the "action" parameter
        if (isset($_POST['action']) && strlen($_POST['action'])) {
            $action = $_POST['action'];
            if (isset($_POST['data']) && strlen($_POST['data'])) {
                $data = json_decode($_POST['data'], true);
            } else {
                $data = null;
            }
            switch ($action) {
                case 'storeDynamicMarker':
                    logger('map plugin: store API invoked');
                    map_storeDynamicMarker($data);
                    break;
                case 'shareUserLocation':
                    logger('map plugin: shareUserLocation API invoked via POST');
                    map_shareUserLocation($data);
                    break;
                case 'shareStaticMarker':
                    logger('map plugin: shareStaticMarker API invoked via POST');
                    map_shareStaticMarker($data);
                    break;
                case 'getshareddata':
                    logger('map plugin: getshareddata API invoked via POST');
                    map_getSharedData($data);
                    break;
                case 'getLatestLocation':
                    logger('map plugin: getLatestLocation POST API invoked');
                    $token = '';
                    if (isset($data['token'])) {
                        $token = $data['token'];
                    } else {
                        logger('map plugin: getLatestLocation POST API missing token parameter');
                        return;
                    }
                    map_getLatestLocation($token);
                    break;
                case 'getStaticMarker':
                    logger('map plugin: getStaticMarker POST API invoked');
                    $token = '';
                    if (isset($data['token'])) {
                        $token = $data['token'];
                    } else {
                        logger('map plugin: getStaticMarker POST API missing token parameter');
                        return;
                    }
                    map_getStaticMarker($token);
                    break;
                case 'saveNewMarker':
                    logger('map plugin: saveNewMarker POST API invoked');
                    if (isset($data['newMarker'])) {
                        $newMarker = $data['newMarker'];
                    } else {
                        logger('map plugin: saveNewMarker POST API missing newMarker data');
                        return;
                    }
                    map_storeStaticMarker($newMarker);
                    break;
                case 'getMyMarkers':
                    logger('map plugin: getMyMarkers POST API invoked');
                    map_getMyMarkers();
                    break;
                case 'getEvents':
                    logger('map plugin: getEvents POST API invoked');
                    map_getEvents();
                    break;
                case 'getLocationHistory':
                    logger('map plugin: map_getLocationHistory POST API invoked');$token = '';
                    if (isset($data['start']) && isset($data['stop'])) {
                        $start = $data['start'];
                        $stop = $data['stop'];
                    } else {
                        logger('map plugin: getLocationHistory POST API missing time parameter');
                        return;
                    }
                    map_getLocationHistory($start, $stop);
                    break;
                case 'deleteStaticMarker':
                    logger('map plugin: deleteStaticMarker POST API invoked');
                    $resource_id = '';
                    if (isset($data['resource_id'])) {
                        $resource_id = $data['resource_id'];
                    } else {
                        logger('map plugin: deleteStaticMarker POST API missing resource_id parameter');
                        return;
                    }
                    map_deleteStaticMarker($resource_id);
                    break;
                case 'getSharedData':
                    logger('map plugin: getSharedData POST API invoked');
                    if (isset($data['type']) && isset($data['filter'])) {
                        map_getSharedData($data['type'], $data['filter']);
                    } else {
                        logger('map plugin: getSharedData POST API missing parameter');
                        return;
                    }                    
                    break;
                case 'revokeAllDynamicShares':
                    map_revokeAllDynamicShares();
                    break;
                default:
                    logger('map plugin: API invoked with an invalid action parameter');
            }
        } else {
            logger('map plugin: API invoked without an action parameter');
        }
    }
    // Is the viewer authenticated?
    if (local_channel() || remote_channel()) {
        $_SESSION['data_cache']['authenticated'] = 1;
        // Load settings
        if (local_channel()) {
            $_SESSION['data_cache']['autotrack'] = intval(get_pconfig(local_channel(),'map','autotrack'));
            $_SESSION['data_cache']['autosave'] = intval(get_pconfig(local_channel(),'map','autosave'));
        }
    } else {        
        $_SESSION['data_cache']['authenticated'] = 0;
    }
    
}

function map_revokeAllDynamicShares() {
    $channel = App::get_channel();  // Get the channel information
    $r = q("UPDATE `locserv-dynamic-markers` SET resource_id = '' WHERE resource_id != ''");
    $items = q("SELECT id FROM item WHERE obj_type = '%s' AND resource_type = '%s' AND resource_id != '' AND obj LIKE '%s' AND uid = %d",
            dbesc(ACTIVITY_POST),
            dbesc('locserv'),
            dbesc('%"locationDataType":"dynamicMarker"%'),
            intval($channel['channel_id'])
    );
    logger('map plugin: items for deletion: ' . json_encode($items));
    foreach ($items as $item) {
        drop_item($item['id'],true,DROPITEM_PHASE1);
    }
    echo json_encode(array('status' => true));
    die;
    
}

/**
 * API: map_shareStaticMarker
 * Share static location data by generating an access token and posting it. 
 * $data contains the ACL specified by the user. The access token is returned
 * @param type $data
 */
function map_shareStaticMarker($data) {
    $resource_type = 'locserv';
    $resource_id = $data['resource_id'];
    $message = $data['message'];
    
    //Extract the ACL for permissions
    $args = array();
    $args['allow_cid']     = perms2str($data['contact_allow']);
    $args['allow_gid']     = perms2str($data['group_allow']);
    $args['deny_cid']      = perms2str($data['contact_deny']);
    $args['deny_gid']      = perms2str($data['group_deny']);
    
    $channel = App::get_channel();
    $observer = App::get_observer();
    
    $acl = new Zotlabs\Access\AccessList($channel);
    if(array_key_exists('allow_cid',$args))
            $acl->set($args);

    $ac = $acl->get();
    
    $mid = item_message_id();  // Generate a unique message ID

    $arr = array();  // Initialize the array of parameters for the post

    // If this were an actual location, ACTIVITY_OBJ_LOCATION would make sense, 
    // but since this is actually an access token to retrieve location data, we'll
    // have to use something more vague
    $objtype = ACTIVITY_OBJ_THING; 
    //check if item for this object exists
    $y = q("SELECT mid FROM item WHERE obj_type = '%s' AND resource_type = '%s' AND resource_id = '%s' AND uid = %d LIMIT 1",
            dbesc(ACTIVITY_POST),
            dbesc($resource_type),
            dbesc($resource_id),
            intval($channel['channel_id'])
    );
    if($y) {
        notice('Error posting access token. Item already exists.');
        logger('map plugin: Error posting access token. item already exists: ' . json_encode($y));
        die;
    }
    $body = '[table][tr][th]' . $channel['channel_name'] . ' shared a location with you. [/th][/tr]';    
    $body .= '[tr][td]' . $message . '[/td][/tr]';
    $link = z_root() . '/map/?action=getStaticMarker&token=' . $resource_id;
    
    /*
    * The local map plugin link for the receiver only needs the token. The plugin
    * will look up the stored item table record and use the object->locationDataType
    * to determine what kind of location data has been shared. This will allow it
    * to make the proper request for data to the sharer's hub. For example, if the
    * object->locationDataType is a dynamicMarker, then the receiver will request
    * only the most recent location associated with that token
    */ 
    $body .= '[tr][td][zrl=' . z_root() . '/map?action=getStaticMarker&token=' . $resource_id . ']Click here to view[/zrl][/td][/tr][/table]';
    // Encode object according to Activity Streams: http://activitystrea.ms/specs/json/1.0/
    $object = json_encode(array(
        'type' => $objtype, 
        'title' => 'location data access token', 
        'locationDataType' => 'staticMarker', 
        'id' => $resource_id, 
        'url' => $link
    ));
    if (intval($data['visible']) || $data['visible'] === 'true') {
            $visible = 1;
    } else {
            $visible = 0;
    }
    $item_hidden = (($visible) ? 0 : 1 );
    
    $arr['aid']           = $channel['channel_account_id'];
    $arr['uid']           = $channel['channel_id'];
    $arr['mid']           = $mid;
    $arr['parent_mid']    = $mid;
    $arr['item_hidden']     = $item_hidden;
    $arr['resource_type']   = $resource_type;
    $arr['resource_id']     = $resource_id;    
    $arr['owner_xchan']     = $channel['channel_hash'];
    $arr['author_xchan']    = $observer['xchan_hash'];
    $arr['title']         = 'Shared Location';
    $arr['allow_cid']       = $ac['allow_cid'];
    $arr['allow_gid']       = $ac['allow_gid'];
    $arr['deny_cid']        = $ac['deny_cid'];
    $arr['deny_gid']        = $ac['deny_gid'];
    $arr['item_wall']       = 0;
    $arr['item_origin']     = 1;
    $arr['item_thread_top'] = 1;
    $arr['item_private']    = intval($acl->is_private());
    $arr['plink']           = z_root() . '/channel/' . $channel['channel_address'] . '/?f=&mid=' . $arr['mid'];
    $arr['verb']          = ACTIVITY_POST;
    $arr['obj_type']      = $objtype;
    $arr['obj']        = $object;
    $arr['body']          = $body;
    
    $post = item_store($arr);
    $item_id = $post['item_id'];

    if($item_id) {
            proc_run('php',"include/notifier.php","activity",$item_id);
            echo json_encode(array('item' => $arr, 'status' => true));
    } else {
        echo json_encode(array('item' => null, 'status' => false));
    }
    die;
}

function map_deleteStaticMarker($resource_id) {
    $channel = App::get_channel();  // Get the channel information
    $r = q("DELETE FROM `locserv-static-markers` WHERE resource_id= '%s' AND uid = %d",
            dbesc($resource_id),
            intval($channel['channel_id'])
        );
    if ($r) {
        echo json_encode(array('status' => true));
    } else {
        echo json_encode(array('status' => false, 'message' => 'Not marker owner'));
    }
    die;
}

function map_getMyMarkers($ret = false) {
    $channel = App::get_channel();  // Get the channel information
    $markers = q("SELECT lat,lon,title,body,created,resource_id FROM `locserv-static-markers` WHERE uid = %d",
            intval($channel['channel_id'])
        );
    if ($markers) {
        if ($ret) {
            return json_encode($markers);
        } else {
            echo json_encode(array('markers' => $markers, 'status' => true));
        }
    } else {
        if ($ret) {
            return null;
        } else {
            echo json_encode(array('markers' => null, 'status' => false));
        }
    }
    die;
}

function map_getEvents() {
    $channel = App::get_channel();  // Get the channel information
    $markers = q("SELECT uid,event_xchan,event_hash,dtstart,dtend,location,summary,description "
            . "FROM `event` WHERE uid = %d AND location REGEXP '%s'",
            intval($channel['channel_id']),
            dbesc('^\[[-]?[0-9]+\.[0-9]+,[-]?[0-9]+\.[0-9]+\]$')
        );
    if ($markers) {
        echo json_encode(array('markers' => $markers, 'status' => true));
    } else {
        echo json_encode(array('markers' => null, 'status' => false));
    }
    die;    
}

/**
 * map_getLocationHistory fetches all user locations owned by the requesting channel
 * between the provided times
 * @param type $start
 * @param type $stop
 */
function map_getLocationHistory($start = '1970-01-01 00:00:00', $stop = '2500-01-01 00:00:00', $ret = false) {
    $channel = App::get_channel();  // Get the channel information 
    //logger('map plugin: map_getLocationHistory called with ' . $start . ', ' . $stop . ' and uid = ' . intval($channel['channel_id']));
    $history = q("SELECT lat,lon,accuracy,heading,speed,resource_id,created FROM `locserv-dynamic-markers` WHERE uid = %d "
            . "AND `created` >= '%s' AND `created` <= '%s'",
            intval($channel['channel_id']),
            dbesc($start),
            dbesc($stop)
        );
    if ($ret) {
        return json_encode($history);
    } else {
        echo json_encode(array('locationHistory' => $history, 'status' => true));
        killme();
    }
}

function widget_map_controls () {
    //logger('map plugin: widget_map_controls called');
    $channel = App::get_channel();  // Get the channel information
    // Obtain the default permission settings of the channel
    $channel_acl = array(
            'allow_cid' => $channel['channel_allow_cid'],
            'allow_gid' => $channel['channel_allow_gid'],
            'deny_cid'  => $channel['channel_deny_cid'],
            'deny_gid'  => $channel['channel_deny_gid']
    );
    
    $t = get_markup_template('map_aside.tpl', 'addon/map');
    // Initialize the ACL to the channel default permissions
    $x = array(
        'lockstate' => (($channel['channel_allow_cid'] || $channel['channel_allow_gid'] || $channel['channel_deny_cid'] || $channel['channel_deny_gid']) ? 'lock' : 'unlock'),
        'acl' => populate_acl($channel_acl),
        'bang' => ''
    );

    //$a->page['aside']
    $o .= replace_macros($t, array(
        '$asidetitle' => t('Map Controls'),
        '$lockstate' => $x['lockstate'],
        '$acl' => $x['acl'],
        '$bang' => $x['bang'],
        '$version' => '<a target="_blank" href="https://github.com/anaqreon/hubzilla-plugins/">v'.map_get_version().'</a>'
    ));

    return $o;
}

function map_load_pdl($a, &$b) {
    if ($b['module'] === 'map') {
        if (argc() > 1 && argv(1) === 'import') {
            return;
        }
        $b['layout'] = '
            [region=aside]
            [widget=map_controls][/widget]
            [/region]
        ';
    }
}

/**
 * API: map_storeDynamicMarker
 * Store the dynamic location data
 * @param type $data
 */
function map_storeDynamicMarker($data) {
    $lat = $data['coords'][0];
    $lon = $data['coords'][1];
    $heading = $data['heading'];
    $speed = $data['speed'];
    $accuracy = $data['accuracy'];
    $token = $data['token'];
    $uid = local_channel();
    $aid = get_account_id();    
    if (isset($data['layer'])) {
        $layer = $data['layer'];
    }

    //Extract the ACL for permissions
    $args = array();
    $args['allow_cid']     = perms2str($data['contact_allow']);
    $args['allow_gid']     = perms2str($data['group_allow']);
    $args['deny_cid']      = perms2str($data['contact_deny']);
    $args['deny_gid']      = perms2str($data['group_deny']);
    
    $acl = new Zotlabs\Access\AccessList(App::get_channel());
    $acl->set($args);
    $perm = $acl->get();
    
    $r = q("INSERT INTO `locserv-dynamic-markers` ( uid, aid, lat, lon, heading, speed, accuracy, resource_id, created, layer, allow_cid, allow_gid, deny_cid, deny_gid ) VALUES ( %d, %d, %f, %f, %f, %f, %f, '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ", 
            intval($uid), 
            intval($aid), 
            floatval($lat), 
            floatval($lon), 
            floatval($heading), 
            floatval($speed), 
            floatval($accuracy), 
            dbesc($token), 
            dbesc(datetime_convert()),
            dbesc($layer),
            dbesc($perm['allow_cid']),
            dbesc($perm['allow_gid']),
            dbesc($perm['deny_cid']),
            dbesc($perm['deny_gid'])
    );
    echo json_encode(array('status' => true));
    die;
}

/**
 * API: map_storeStaticMarker
 * Store the static marker
 * @param type $newMarker
 */
function map_storeStaticMarker($newMarker) {
    $lat = $newMarker['lat'];
    logger('map plugin: newMarker lat: ' . $lat);
    $lon = $newMarker['lon'];
    $name = $newMarker['name'];
    $description = $newMarker['description'];   
    if (isset($newMarker['layer'])) {
        $layer = $$newMarkerdata['layer'];
    }
    
    $uid = local_channel();
    $aid = get_account_id(); 
    
    $token = random_string();
   
    $r = q("INSERT INTO `locserv-static-markers` ( uid, aid, lat, lon, title, body, resource_id, created, layer) VALUES ( %d, %d, %f, %f, '%s', '%s', '%s', '%s', '%s') ", 
            intval($uid), 
            intval($aid), 
            floatval($lat), 
            floatval($lon), 
            dbesc($name),
            dbesc($description), 
            dbesc($token), 
            dbesc(datetime_convert()),
            dbesc($layer)
    );
    
    if ($r) {
        echo json_encode(array('token' => $token, 'status' => true));
    } else {
        echo json_encode(array('token' => null, 'status' => false));
    }
    die;
    
}
/**
 * map_getStaticMarker returns the latest location of a static marker
 * @param type $token
 */
function map_getStaticMarker($token) {
    logger('map plugin: map_getStaticMarker called with token ' . $token);
    if (local_channel() || remote_channel()) {
        $channel = App::get_channel();
        $sql_extra = item_permissions_sql($channel['channel_id'], get_observer_hash());
    } else {
        $sql_extra = " AND item_private = 0 ";
        logger('map plugin: map_getStaticMarker: not local channel, $sql_extra = ' . $sql_extra);
    }
    $r = q("SELECT * FROM item WHERE resource_id = '%s' $sql_extra LIMIT 1",
        dbesc($token)
    );
    if (!$r) {  // Invalid token
        echo json_encode(array('token' => $token, 'status' => false));
        die;
    }
    
    $sharedStaticMarker = q("SELECT lat,lon,title,body,layer,created FROM `locserv-static-markers` WHERE resource_id = '%s' order by id desc limit 1",
            dbesc($token)
    );
    echo json_encode(array('sharedStaticMarker' => $sharedStaticMarker[0], 'token' => $token, 'status' => true));
    die; 
}
/**
 * map_getLatestLocation returns the latest location of a dynamic marker
 * @param type $token
 */
function map_getLatestLocation($token) {
    logger('map plugin: map_getLatestLocation called with token ' . $token);
    if (local_channel() || remote_channel()) {
        $channel = App::get_channel();
        $sql_extra = item_permissions_sql($channel['channel_id'], get_observer_hash());
    } else {
        $sql_extra = " AND item_private = 0 ";
        logger('map plugin: map_getLatestLocation: not local channel, $sql_extra = ' . $sql_extra);
    }
    
    //logger('map plugin: map_getLatestLocation $sql_extra: ' . $sql_extra);
    $r = q("SELECT * FROM item WHERE resource_id = '%s' $sql_extra LIMIT 1",
        dbesc($token)
    );
    if (!$r) {  // Invalid token
        echo json_encode(array('token' => $token, 'status' => false));
        die;
    }
    
    $latestLocation = q("SELECT lat,lon,speed,heading,accuracy,layer,created FROM `locserv-dynamic-markers` WHERE resource_id = '%s' order by id desc limit 1",
            dbesc($token)
    );
    echo json_encode(array('latestLocation' => $latestLocation, 'token' => $token, 'status' => true));
    die; 
    
}

/**
 * API: map_shareUserLocation
 * Share real-time location data by generating an access token and posting it. 
 * $data contains the ACL specified by the user. The access token is returned
 * @param type $data
 */
function map_shareUserLocation($data) {
    $resource_type = 'locserv';
    $token = random_string();
    
    //Extract the ACL for permissions
    $args = array();
    $args['allow_cid']     = perms2str($data['contact_allow']);
    $args['allow_gid']     = perms2str($data['group_allow']);
    $args['deny_cid']      = perms2str($data['contact_deny']);
    $args['deny_gid']      = perms2str($data['group_deny']);
    $args['token']         = $token;
    
    (array_key_exists('token', $args) ? $token = $args['token'] : $token = '');
    $channel = App::get_channel();
    $observer = App::get_observer();
    
    $acl = new Zotlabs\Access\AccessList($channel);
    if(array_key_exists('allow_cid',$args))
            $acl->set($args);

    $ac = $acl->get();
        
    $mid = item_message_id();  // Generate a unique message ID

    $arr = array();  // Initialize the array of parameters for the post

    // If this were an actual location, ACTIVITY_OBJ_LOCATION would make sense, 
    // but since this is actually an access token to retrieve location data, we'll
    // have to use something more vague
    $objtype = ACTIVITY_OBJ_THING; 
    //check if item for this object exists
    $y = q("SELECT mid FROM item WHERE obj_type = '%s' AND resource_type = '%s' AND resource_id = '%s' AND uid = %d LIMIT 1",
            dbesc(ACTIVITY_POST),
            dbesc($resource_type),
            dbesc($token),
            intval($channel['channel_id'])
    );
    if($y) {
        notice('Error posting access token. Item already exists.');
        logger('map plugin: Error posting access token. item already exists: ' . json_encode($y));
        die;
    }
    $body = $channel['channel_name'] . ' shared their location with you. ';
    $link = z_root() . '/map/?action=getLatestLocation&token=' . $token;
    /*
    * The local map plugin link for the receiver only needs the token. The plugin
    * will look up the stored item table record and use the object->locationDataType
    * to determine what kind of location data has been shared. This will allow it
    * to make the proper request for data to the sharer's hub. For example, if the
    * object->locationDataType is a dynamicMarker, then the receiver will request
    * only the most recent location associated with that token
    */ 
    $body .= '[url=' . z_root() . '/map?action=getLatestLocation&token=' . $token . ']Click here to view[/url]';
    // Encode object according to Activity Streams: http://activitystrea.ms/specs/json/1.0/
    $object = json_encode(array(
        'type' => $objtype, 
        'title' => 'location data access token', 
        'locationDataType' => 'dynamicMarker', 
        'id' => $token, 
        'url' => $link
    ));
    if (intval($data['visible']) || $data['visible'] === 'true') {
            $visible = 1;
    } else {
            $visible = 0;
    }
    $item_hidden = (($visible) ? 0 : 1 );
    
    $arr['aid']           = $channel['channel_account_id'];
    $arr['uid']           = $channel['channel_id'];
    $arr['mid']           = $mid;
    $arr['parent_mid']    = $mid;
    $arr['item_hidden']     = $item_hidden;
    $arr['resource_type']   = $resource_type;
    $arr['resource_id']     = $token;    
    $arr['owner_xchan']     = $channel['channel_hash'];
    $arr['author_xchan']    = $observer['xchan_hash'];
    $arr['title']         = 'Shared Location';
    $arr['allow_cid']       = $ac['allow_cid'];
    $arr['allow_gid']       = $ac['allow_gid'];
    $arr['deny_cid']        = $ac['deny_cid'];
    $arr['deny_gid']        = $ac['deny_gid'];
    $arr['item_wall']       = 0;
    $arr['item_origin']     = 1;
    $arr['item_thread_top'] = 1;
    $arr['item_private']    = intval($acl->is_private());
    $arr['plink']           = z_root() . '/channel/' . $channel['channel_address'] . '/?f=&mid=' . $arr['mid'];
    $arr['verb']          = ACTIVITY_POST;
    $arr['obj_type']      = $objtype;
    $arr['obj']        = $object;
    $arr['body']          = $body;
    
    $post = item_store($arr);
    $item_id = $post['item_id'];

    if($item_id) {
            proc_run('php',"include/notifier.php","activity",$item_id);
            echo json_encode(array('item' => $arr, 'status' => true));
    } else {
        echo json_encode(array('item' => null, 'status' => false));
    }
    die;
}

/**
 * API: map_getSharedData
 * Retrieve the available data
 * @param type $type
 * @param type $filter
 */
function map_getSharedData($type, $filter) {
    if (local_channel() || remote_channel()) {
        $channel = App::get_channel();
        $sql_extra = item_permissions_sql($channel['channel_id'], get_observer_hash());
    } else {
        $sql_extra = " AND item_private = 0 ";
    }
    switch ($filter) {
        case 'owner':
            $shares = q("SELECT owner_xchan,resource_id FROM item WHERE resource_type = '%s' AND owner_xchan != '%s' AND obj LIKE '%s' $sql_extra", 
                    dbesc('locserv'), 
                    dbesc(App::get_channel()['channel_hash']),
                    dbesc('%"locationDataType":"' . $type . '"%')
            );
            $channels = [];
            foreach ($shares as $share) {
                $channel = channelx_by_hash($share['owner_xchan']);
                $channels[] = array('name' => $channel['channel_name'], 'address' => $channel['xchan_addr'], 'photo_address' => $channel['xchan_photo_s']);
            }

            echo json_encode(array('sharedData' => $shares, 'channels' => $channels, 'status' => true));
            die;
        case 'all':
            $shares = q("SELECT owner_xchan,resource_id FROM item WHERE resource_type = '%s' AND owner_xchan != '%s' AND obj LIKE '%s' $sql_extra", 
                    dbesc('locserv'), 
                    dbesc(App::get_channel()['channel_hash']),
                    dbesc('%"locationDataType":"' . $type . '"%')
            );
            $channels = [];
            $markers = [];
            foreach ($shares as $share) {
                $channel = channelx_by_hash($share['owner_xchan']);
                $channels[] = array('name' => $channel['channel_name'], 'address' => $channel['xchan_addr'], 'photo_address' => $channel['xchan_photo_s']);
                // FIXME: Not sure the permissions are checked appropriately here
                $marker = q("SELECT lat,lon,title,body,layer,created,resource_id FROM `locserv-static-markers` WHERE resource_id = '%s' limit 1",
                        dbesc($share['resource_id'])
                );
                $markers[] = $marker[0];
            }

            echo json_encode(array('sharedData' => $shares, 'channels' => $channels, 'markers' => $markers, 'status' => true));
            die;
        default:
            echo json_encode(array('sharedData' => null, 'channels' => null, 'markers' => null, 'status' => false));
            die;
    }
}

function map_post($a) {

}

function map_import_data($a, $channel_id) {

    if (!$channel_id) {
        logger("map_import_data: No channel ID supplied");
        return;
    }
    $account_id = get_account_id();
    $src = $_FILES['filename']['tmp_name'];
    $filename = basename($_FILES['filename']['name']);
    $filesize = intval($_FILES['filename']['size']);
    $filetype = $_FILES['filename']['type'];

    if ($src && $filesize) {
        $data = @file_get_contents($src);
        unlink($src);
    } else {
        notice(t('File upload error') . EOL);
        logger('map data import: File upload error');
    }

    if (!$data) {
        logger('map data import: empty file.');
        notice(t('Imported file is empty.') . EOL);
        return;
    }

    $data = json_decode($data, true);
    if (array_key_exists('markers', $data)) {
        $markers =json_decode($data['markers'], true);
        $ms = q("SELECT uid,resource_id FROM `locserv-static-markers`");
        foreach ($markers as $marker) {
            $import = 1;
            foreach ($ms as $m) {
                if ($m['resource_id'] === $marker['resource_id']) {
                    $import = 0;
                }
            }
            if ($import) {
                $r = q("INSERT INTO `locserv-static-markers` ( uid, aid, lat, lon, "
                        . "title, body, resource_id, created, layer) VALUES "
                        . "( %d, %d, %f, %f, '%s', '%s', '%s', '%s', '%s') ", 
                        $channel_id, 
                        $account_id, 
                        $marker['lat'], 
                        $marker['lon'], 
                        $marker['title'], 
                        $marker['body'], 
                        $marker['resource_id'], 
                        $marker['created'], 
                        $marker['layer']
                );
                if (count($r)) {
                    info(t('Markers imported successfully') . EOL);
                    logger('map data import: Markers imported successfully');
                } 
            } else {
                    notice(t('No markers imported') . EOL);
                    logger('map data import: No markers imported');
                }
        }
    }

    if (array_key_exists('history', $data)) {
        // TODO: Import history data
        $history = $data['history'];
        echo $history;
    }
}

function map_content($a) {
    if (argc() > 1 && argv(1) === 'import') {
        logger('map import launching');
        return map_import($a);
    }

    //$a->page['htmlhead'] .= '<link rel="stylesheet"  type="text/css" href="' . $a->get_baseurl() . '/addon/map/map.css' . '" media="all" />' . "\r\n";
     head_add_css('/addon/map/view/css/map.css');
//    $a->page['htmlhead'] .= replace_macros(get_markup_template('jot-header.tpl'), array(
//        '$baseurl' => $a->get_baseurl(),
//        '$editselect' => 'none',
//        '$ispublic' => '&nbsp;', // t('Visible to <strong>everybody</strong>'),
//        '$geotag' => '',
//        '$nickname' => $channel['channel_address'],
//        '$confirmdelete' => t('Delete webpage?')
//    ));

    if ($_SESSION['data_cache'] !== null) {
        $data_cache = json_encode($_SESSION['data_cache']);
    } else {
        $data_cache = '';
    }
    $o .= replace_macros(get_markup_template('map.tpl', 'addon/map'), array(
        '$header' => t('Map'),
        '$text' => $text,
        '$data_cache' => $data_cache,
        '$loginbox' => login()
    ));
    
    $o .= '<script type="text/javascript" src="' . App::get_baseurl() . '/addon/map/view/js/underscore-min.js"></script>' . "\r\n";
    $o .= '<script type="text/javascript" src="' . App::get_baseurl() . '/addon/map/view/js/backbone-min.js"></script>' . "\r\n";
    $o .= '<script type="text/javascript" src="' . App::get_baseurl() . '/addon/map/view/js/ol.js"></script>' . "\r\n";
    $o .= '<script type="text/javascript" src="' . App::get_baseurl() . '/addon/map/view/js/map.js?version=' . map_get_version() . '"></script>' . "\r\n";

    return $o;
}

function map_import($a) {

    $o = replace_macros(get_markup_template('map_import.tpl', 'addon/map'), array(
        '$title' => t('Import Map Data'),
        '$desc' => t('Import map data including map markers and location history by uploading a previous file export.'),
        '$label_filename' => t('File to Upload'),
        '$submit' => t('Submit'),
        '$returntomap' => t('Return to map')
    ));

    return $o;
}

function map_create_database_table() {
    $str = file_get_contents('addon/map/map_schema_mysql.sql');
    $arr = explode(';', $str);
    $errors = false;
    foreach ($arr as $a) {
        if (strlen(trim($a))) {
            $r = q(trim($a));
            if (!$r) {
                $errors .= t('Errors encountered creating database tables.') . $a . EOL;
            }
        }
    }
    return $errors;
}
