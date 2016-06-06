<?php

/**
 *
 * Name: Hubsites
 * Description: Automatic import of webpage elements from git repositories
 * Version: 0.2.4
 * Author: Andrew Manning <https://grid.reticu.li/channel/andrewmanning/>
 * MinVersion: 1.3.3
 * MaxVersion: 1.6.6
 *  
 */
require_once('include/permissions.php');
require_once('include/items.php');
require_once('include/acl_selectors.php');
require_once('include/Contact.php');            // for rrmdir($path)
require __DIR__ . '/vendor/autoload.php';       // Load PHPGit dependencies

function hubsites_load() {
    logger("installed Hubsites");
}

function hubsites_unload() {
    logger("removed Hubsites");
}

function hubsites_install() {
    $storepath = realpath(__DIR__ . '/../../../../store/');
    $reposdir = $storepath . '/hubsiterepos/';
    if (is_dir($reposdir) || mkdir($reposdir, 0770, true)) {
        notice('Hubsites installed successfully');
        logger('Hubsites installed successfully');
    } else {
        notice('Hubsites installation failed. Cannot create /hubsiterepos/ folder.');
        logger('Hubsites installation failed. Cannot create /hubsiterepos/ folder.');
    }
}

function hubsites_uninstall() {
    notice('Hubsites uninstalled successfully');
    logger('Hubsites uninstalled successfully');
    return;
}

function hubsites_module() {}

function hubsites_init($a) {

}

function hubsites_clone_repo($channel, $repoURL) {
    $urlpath = parse_url($repoURL, PHP_URL_PATH);
    $lastslash = strrpos($urlpath, '/') + 1;
    $gitext = strrpos($urlpath, '.');
    if ($gitext) {
        $reponame = substr($urlpath, $lastslash, $gitext - $lastslash);
    } else {
        logger('hubsites plugin: invalid git repo URL');
        notice('Invalid git repo URL');
        return null;
    }
    $storepath = realpath(__DIR__ . '/../../../../store/');
    logger('hubsites plugin: storepath: ' . $storepath);
    $repopath = $storepath . '/hubsiterepos/' . $channel['channel_address']  . '/' . $reponame;

    if (!file_exists($repopath)) {
        logger('hubsites plugin: repopath does not exist');
        if (mkdir($repopath, 0770, true)) {
            logger('hubsites plugin: repopath created');
            $git = new PHPGit\Git();
            logger('hubsites: new git object created');
            $cloned = $git->clone($repoURL, $repopath);
            if (!$cloned) {
                logger('hubsites:  git clone failed');
                notice('Repo coule not be cloned. Filesystem path error.');
                return null;
            }
        } else {
            logger('hubsites plugin: repopath could not be created');
            notice('Repo coule not be cloned. Filesystem path error.');
            return null;
        }
    } 
    return $repopath;
}

function hubsites_scan_repo($channel, $repopath) {
    $hubsites = $_SESSION['hubsites'];
    $pages = [];
    $blocks = [];
    $layouts = [];
    // Import pages
    $dirtoscan = $repopath . '/pages/';
    if (is_dir($dirtoscan)) {
        $dirlist = scandir($dirtoscan);
        if ($dirlist) {
            foreach ($dirlist as $element) {
                if ($element === '.' || $element === '..') {
                    continue;
                }
                $folder = $dirtoscan . '/' . $element;
                if (is_dir($folder)) {
                    $jsonfilepath = $folder . '/page.json';
                    if (is_file($jsonfilepath)) {
                        $pagejson = json_decode(file_get_contents($jsonfilepath), true);
                        $pagejson['path'] = $folder . '/' . $pagejson['contentfile'];
                        if ($pagejson['contentfile'] === '') {
                            logger('hubsites plugin: Invalid page content file');
                            return false;
                        }
                        $pagecontent = file_get_contents($folder . '/' . $pagejson['contentfile']);
                        if (!$pagecontent) {
                            logger('hubsites plugin: Failed to get file content for ' . $pagejson['contentfile']);
                            return false;
                        }
                        $pages[] = $pagejson;
                    }
                }
            }
        }
    }
    $hubsites['pages'] = $pages;
    // Import layouts
    $dirtoscan = $repopath . '/layouts/';
    if (is_dir($dirtoscan)) {
        $dirlist = scandir($dirtoscan);
        if ($dirlist) {
            foreach ($dirlist as $element) {
                if ($element === '.' || $element === '..') {
                    continue;
                }
                $folder = $dirtoscan . '/' . $element;
                if (is_dir($folder)) {
                    $jsonfilepath = $folder . '/layout.json';
                    if (is_file($jsonfilepath)) {
                        $layoutjson = json_decode(file_get_contents($jsonfilepath), true);
                        $layoutjson['path'] = $folder . '/' . $layoutjson['contentfile'];
                        if ($layoutjson['contentfile'] === '') {
                            logger('hubsites plugin: Invalid layout content file');
                            return false;
                        }
                        $layoutcontent = file_get_contents($folder . '/' . $layoutjson['contentfile']);
                        if (!$layoutcontent) {
                            logger('hubsites plugin: Failed to get file content for ' . $layoutjson['contentfile']);
                            return false;
                        }
                        $layouts[] = $layoutjson;
                    }
                }
            }
        }
    }
    $hubsites['layouts'] = $layouts;
    // Import blocks
    $dirtoscan = $repopath . '/blocks/';
    if (is_dir($dirtoscan)) {
        $dirlist = scandir($dirtoscan);
        if ($dirlist) {
            foreach ($dirlist as $element) {
                if ($element === '.' || $element === '..') {
                    continue;
                }
                $folder = $dirtoscan . '/' . $element;
                if (is_dir($folder)) {
                    $jsonfilepath = $folder . '/block.json';
                    if (is_file($jsonfilepath)) {
                        $block = json_decode(file_get_contents($jsonfilepath), true);
                        $block['path'] = $folder . '/' . $block['contentfile'];
                        if ($block['contentfile'] === '') {
                            logger('hubsites plugin: Invalid block content file');
                            return false;
                        }
                        $blockcontent = file_get_contents($folder . '/' . $block['contentfile']);
                        if (!$blockcontent) {
                            logger('hubsites plugin: Failed to get file content for ' . $block['contentfile']);
                            return false;
                        }
                        $blocks[] = $block;
                    }
                }
            }
        }
    }
    $hubsites['blocks'] = $blocks;
    $_SESSION['hubsites'] = $hubsites;
    return true;
}

function hubsites_import_blocks($channel, $blocks) {
    foreach ($blocks as &$b) {
        
        $arr = array();
        $arr['item_type'] = ITEM_TYPE_BLOCK;
        $namespace = 'BUILDBLOCK';
        $arr['uid'] = $channel['channel_id'];
        $arr['aid'] = $channel['channel_account_id'];
        
        $iid = q("select iid from item_id where service = 'BUILDBLOCK' and sid = '%s' and uid = %d",
                dbesc($b['name']),
                intval($channel['channel_id'])
        );
        if($iid) {
            $iteminfo = q("select mid,created,edited from item where id = %d",
                    intval($iid[0]['iid'])
            );
            $arr['mid'] = $arr['parent_mid'] = $iteminfo[0]['mid'];
            $arr['created'] = $iteminfo[0]['created'];
            $arr['edited'] = (($b['edited']) ? datetime_convert('UTC', 'UTC', $b['edited']) : datetime_convert());
        } else {
            $arr['created'] = (($b['created']) ? datetime_convert('UTC', 'UTC', $b['created']) : datetime_convert());
            $arr['edited'] = datetime_convert('UTC', 'UTC', '0000-00-00 00:00:00');
            $arr['mid'] = $arr['parent_mid'] = item_message_id();
        }
        $arr['title'] = $b['title'];
        $arr['body'] = file_get_contents($b['path']);
        $arr['owner_xchan'] = get_observer_hash();
        $arr['author_xchan'] = (($b['author_xchan']) ? $b['author_xchan'] : get_observer_hash());
        if(($b['mimetype'] === 'text/bbcode' || $b['mimetype'] === 'text/html' ||
                $b['mimetype'] === 'text/markdown' ||$b['mimetype'] === 'text/plain' ||
                $b['mimetype'] === 'application/x-pdl' ||$b['mimetype'] === 'application/x-php')) {
            $arr['mimetype'] = $b['mimetype'];
        } else {
            $arr['mimetype'] = 'text/bbcode';
        }

        $pagetitle = $b['name']; 

        // Verify ability to use html or php!!!
        $execflag = false;
        if ($arr['mimetype'] === 'application/x-php') {
            $z = q("select account_id, account_roles, channel_pageflags from account left join channel on channel_account_id = account_id where channel_id = %d limit 1", intval(local_channel())
            );

            if ($z && (($z[0]['account_roles'] & ACCOUNT_ROLE_ALLOWCODE) || ($z[0]['channel_pageflags'] & PAGE_ALLOWCODE))) {
                $execflag = true;
            }
        }

        $remote_id = 0;

        $z = q("select * from item_id where sid = '%s' and service = '%s' and uid = %d limit 1", dbesc($pagetitle), dbesc($namespace), intval(local_channel())
        );

        $i = q("select id, edited, item_deleted from item where mid = '%s' and uid = %d limit 1", dbesc($arr['mid']), intval(local_channel())
        );
        if ($z && $i) {
            $remote_id = $z[0]['id'];
            $arr['id'] = $i[0]['id'];
            // don't update if it has the same timestamp as the original
            if ($arr['edited'] > $i[0]['edited'])
                $x = item_store_update($arr, $execflag);
        } else {
            if (($i) && (intval($i[0]['item_deleted']))) {
                // was partially deleted already, finish it off
                q("delete from item where mid = '%s' and uid = %d", dbesc($arr['mid']), intval(local_channel())
                );
            }
            $x = item_store($arr, $execflag);
        }
        if ($x['success']) {
            $item_id = $x['item_id'];
            update_remote_id($channel, $item_id, $arr['item_type'], $pagetitle, $namespace, $remote_id, $arr['mid']);
            $b['import_success'] = 1;
        } else {
            $b['import_success'] = 0;
        }
    }
    return $blocks;
}

function hubsites_import_pages($channel, $pages) {
    foreach ($pages as &$p) {
        
        $arr = array();
        $arr['item_type'] = ITEM_TYPE_WEBPAGE;
        $namespace = 'WEBPAGE';
        $arr['uid'] = $channel['channel_id'];
        $arr['aid'] = $channel['channel_account_id'];

        if($p['pagelink']) {
                require_once('library/urlify/URLify.php');
                $pagetitle = strtolower(URLify::transliterate($p['pagelink']));
        }
        $arr['layout_mid'] = ''; // by default there is no layout associated with the page
        // If a layout was specified, find it in the database and get its info. If
        // it does not exist, leave layout_mid empty
        logger('hubsites plugin: $p[layout] = ' . $p['layout']);
        if($p['layout'] !== '') {
            $liid = q("select iid from item_id where service = 'PDL' and sid = '%s' and uid = %d",
                    dbesc($p['layout']),
                    intval($channel['channel_id'])
            );
            if($liid) {
                $linfo = q("select mid from item where id = %d",
                        intval($liid[0]['iid'])
                );
                logger('hubsites plugin: $linfo= ' . json_encode($linfo,true));
                $arr['layout_mid'] = $linfo[0]['mid'];
            }                 
        }
        // See if the page already exists
        $iid = q("select iid from item_id where service = 'WEBPAGE' and sid = '%s' and uid = %d",
                dbesc($pagetitle),
                intval($channel['channel_id'])
        );
        if($iid) {
            // Get the existing page info
            $pageinfo = q("select mid,layout_mid,created,edited from item where id = %d",
                    intval($iid[0]['iid'])
            );
            $arr['mid'] = $arr['parent_mid'] = $pageinfo[0]['mid'];
            $arr['created'] = $pageinfo[0]['created'];
            $arr['edited'] = (($p['edited']) ? datetime_convert('UTC', 'UTC', $p['edited']) : datetime_convert());
        } else {
            $arr['created'] = (($p['created']) ? datetime_convert('UTC', 'UTC', $p['created']) : datetime_convert());
            $arr['edited'] = datetime_convert('UTC', 'UTC', '0000-00-00 00:00:00');
            $arr['mid'] = $arr['parent_mid'] = item_message_id();
        }
        $arr['title'] = $p['title'];
        $arr['body'] = file_get_contents($p['path']);
        $arr['term'] = $p['term'];  // Not sure what this is supposed to be
        
        $arr['owner_xchan'] = get_observer_hash();
        $arr['author_xchan'] = (($p['author_xchan']) ? $p['author_xchan'] : get_observer_hash());
        if(($p['mimetype'] === 'text/bbcode' || $p['mimetype'] === 'text/html' ||
                $p['mimetype'] === 'text/markdown' ||$p['mimetype'] === 'text/plain' ||
                $p['mimetype'] === 'application/x-pdl' ||$p['mimetype'] === 'application/x-php')) {
            $arr['mimetype'] = $p['mimetype'];
        } else {
            $arr['mimetype'] = 'text/bbcode';
        }

        // Verify ability to use html or php!!!
        $execflag = false;
        if ($arr['mimetype'] === 'application/x-php') {
            $z = q("select account_id, account_roles, channel_pageflags from account left join channel on channel_account_id = account_id where channel_id = %d limit 1", intval(local_channel())
            );

            if ($z && (($z[0]['account_roles'] & ACCOUNT_ROLE_ALLOWCODE) || ($z[0]['channel_pageflags'] & PAGE_ALLOWCODE))) {
                $execflag = true;
            }
        }

        $remote_id = 0;

        $z = q("select * from item_id where sid = '%s' and service = '%s' and uid = %d limit 1", 
                dbesc($pagetitle), 
                dbesc($namespace), 
                intval(local_channel())
        );

        $i = q("select id, edited, item_deleted from item where mid = '%s' and uid = %d limit 1", 
                dbesc($arr['mid']), 
                intval(local_channel())
        );
        if ($z && $i) {
            $remote_id = $z[0]['id'];
            $arr['id'] = $i[0]['id'];
            // don't update if it has the same timestamp as the original
            if ($arr['edited'] > $i[0]['edited'])
                $x = item_store_update($arr, $execflag);
        } else {
            if (($i) && (intval($i[0]['item_deleted']))) {
                // was partially deleted already, finish it off
                q("delete from item where mid = '%s' and uid = %d", dbesc($arr['mid']), intval(local_channel())
                );
            }
            logger('hubsites plugin: item_store= ' . json_encode($arr,true));
            $x = item_store($arr, $execflag);
        }
        if ($x['success']) {
            $item_id = $x['item_id'];
            update_remote_id($channel, $item_id, $arr['item_type'], $pagetitle, $namespace, $remote_id, $arr['mid']);
            $p['import_success'] = 1;
        } else {
            $p['import_success'] = 0;
        }
    }
    return $pages;
    
}

function hubsites_import_layouts($channel, $layouts) {
    foreach ($layouts as &$p) {
        
        $arr = array();
        $arr['item_type'] = ITEM_TYPE_PDL;
        $namespace = 'PDL';
        $arr['uid'] = $channel['channel_id'];
        $arr['aid'] = $channel['channel_account_id'];
        $pagetitle = $p['name'];
        // See if the layout already exists
        $iid = q("select iid from item_id where service = 'PDL' and sid = '%s' and uid = %d",
                dbesc($pagetitle),
                intval($channel['channel_id'])
        );
        if($iid) {
            // Get the existing layout info
            $info = q("select mid,layout_mid,created,edited from item where id = %d",
                    intval($iid[0]['iid'])
            );
            $arr['mid'] = $arr['parent_mid'] = $info[0]['mid'];
            $arr['created'] = $info[0]['created'];
            $arr['edited'] = (($p['edited']) ? datetime_convert('UTC', 'UTC', $p['edited']) : datetime_convert());
        } else {
            $arr['created'] = (($p['created']) ? datetime_convert('UTC', 'UTC', $p['created']) : datetime_convert());
            $arr['edited'] = datetime_convert('UTC', 'UTC', '0000-00-00 00:00:00');
            $arr['mid'] = $arr['parent_mid'] = item_message_id();
        }
        $arr['title'] = $p['description'];
        $arr['body'] = file_get_contents($p['path']);
        $arr['term'] = $p['term'];  // Not sure what this is supposed to be
        
        $arr['owner_xchan'] = get_observer_hash();
        $arr['author_xchan'] = (($p['author_xchan']) ? $p['author_xchan'] : get_observer_hash());
        $arr['mimetype'] = 'text/bbcode';

        $remote_id = 0;

        $z = q("select * from item_id where sid = '%s' and service = '%s' and uid = %d limit 1", 
                dbesc($pagetitle), 
                dbesc($namespace), 
                intval(local_channel())
        );

        $i = q("select id, edited, item_deleted from item where mid = '%s' and uid = %d limit 1", 
                dbesc($arr['mid']), 
                intval(local_channel())
        );
        if ($z && $i) {
            $remote_id = $z[0]['id'];
            $arr['id'] = $i[0]['id'];
            // don't update if it has the same timestamp as the original
            if ($arr['edited'] > $i[0]['edited'])
                $x = item_store_update($arr, $execflag);
        } else {
            if (($i) && (intval($i[0]['item_deleted']))) {
                // was partially deleted already, finish it off
                q("delete from item where mid = '%s' and uid = %d", dbesc($arr['mid']), intval(local_channel())
                );
            }
            $x = item_store($arr, $execflag);
        }
        if ($x['success']) {
            $item_id = $x['item_id'];
            update_remote_id($channel, $item_id, $arr['item_type'], $pagetitle, $namespace, $remote_id, $arr['mid']);
            $p['import_success'] = 1;
        } else {
            $p['import_success'] = 0;
        }
    }
    return $layouts;
    
}

function hubsites_post(&$a) {
    $action = $_REQUEST['action'];
    if (!$action) {
        return;
    }
    $channel = App::get_channel();
    switch ($action) {
        case 'clone':
            $repoURL = $_REQUEST['repoURL'];

            if (!$repoURL) {
                logger('hubsites plugin: A git repo must be provided.');
                notice('A git repo must be provided.');
                goaway('/hubsites');
                return;
            }

            $repopath = hubsites_clone_repo($channel, $repoURL);
            if ($repopath === null) {
                logger('hubsites plugin: Failed to clone git repo: ' . $repoURL);
                notice('Failed to clone git repo: ' . $repoURL);
                goaway('/hubsites');
            }
            $hubsites = array('repopath' => $repopath, 'repoURL' => $repoURL);
            $_SESSION['hubsites'] = $hubsites;
            $scanerror = hubsites_scan_repo($channel, $repopath);
            if (!$scanerror) {
                logger('hubsites plugin: Error scanning git repo ' . $repoURL);
                notice('Git repo scan failed.');
            }
            break;
        case 'import':
            // Retrieve the persistent data structure from SESSION
            $hubsites = $_SESSION['hubsites'];
            // Obtain the user-selected blocks to import and import them
            $checkedblocks = $_POST['block'];
            $blocks = [];
            if (!empty($checkedblocks)) {
                foreach ($checkedblocks as $name) {
                    foreach ($hubsites['blocks'] as &$block) {
                        if ($block['name'] === $name) {
                            $block['import'] = 1;
                            $blockstoimport[] = $block;
                        }
                    }
                }
                $blocks = hubsites_import_blocks($channel, $blockstoimport);
            }
            $hubsites['import_blocks'] = $blocks;
            
            // Obtain the user-selected layouts to import and import them
            $checkedlayouts = $_POST['layout'];
            $layouts = [];
            if (!empty($checkedlayouts)) {
                foreach ($checkedlayouts as $name) {
                    foreach ($hubsites['layouts'] as &$layout) {
                        if ($layout['name'] === $name) {
                            $layout['import'] = 1;
                            $layoutstoimport[] = $layout;
                        }
                    }
                }
                $layouts = hubsites_import_layouts($channel, $layoutstoimport);
            }
            $hubsites['import_layouts'] = $layouts;
            
            // Obtain the user-selected pages to import and import them
            $checkedpages = $_POST['page'];
            $pages = [];
            if (!empty($checkedpages)) {
                foreach ($checkedpages as $pagelink) {
                    foreach ($hubsites['pages'] as &$page) {
                        if ($page['pagelink'] === $pagelink) {
                            $page['import'] = 1;
                            $pagestoimport[] = $page;
                        }
                    }
                }
                $pages = hubsites_import_pages($channel, $pagestoimport);
            }
            $hubsites['import_pages'] = $pages;
            // Save the updated data structure to the SESSION
            $_SESSION['hubsites'] = $hubsites;
            break;
        case 'updaterepos':
            $repos = $_SESSION['hubsites']['repos'];
            $repostoupdate = $_POST['repoupdate'];
            if (!empty($repostoupdate)) {
                $git = new PHPGit\Git();
                foreach ($repostoupdate as $id) {
                    $path = '';
                    $url = '';
                    foreach ($repos as $r) {
                        if ($r['id'] == $id) {
                            $path = $r['path'];
                            $url = $r['url'];
                            break;
                        }
                    }
                    if ($path === '.' || $path === '') {
                        logger('hubsites plugin: Error updating repo with id: ' . $id);
                        notice('Error updating repos');
                        continue;
                    }
                    $git->setRepository($path);
                    $git->pull('origin', 'master');
                    logger('hubsites plugin: Updated repo: ' . $path);
                    notice('Updated repo: ' . $url);
                }
            }
            $repostodelete = $_POST['repodelete'];
            if (!empty($repostodelete)) {
                foreach ($repostodelete as $id) {
                    $path = '';
                    $url = '';
                    foreach ($repos as $r) {
                        if ($r['id'] == $id) {
                            $path = $r['path'];
                            $url = $r['url'];
                            break;
                        }
                    }
                    if ($path === '.' || $path === '') {
                        logger('hubsites plugin: Error removing repo with id: ' . $id);
                        notice('Error removing repos');
                        continue;
                    }
                    if (!rrmdir($path)) {
                        logger('hubsites plugin: Error deleting: ' . $path);
                        notice('Error removing repo: ' . $url);
                    }
                }
            }
            break;
        default:
            break;
    }
}

function hubsites_content($a) {
    head_add_css('/addon/hubsites/view/css/hubsites.css');
    $channel = App::get_channel();
    $action = $_REQUEST['action'];
    if (!$action || !local_channel()) {
        $action = '';
    }
    switch ($action) {
        case 'clone':
            $hubsites = $_SESSION['hubsites'];
            if (count($hubsites['pages']) > 0) {
                foreach ($hubsites['pages'] as &$page) {
                    $page['import'] = 0;
                }
            }
            if (count($hubsites['blocks']) > 0) {
                foreach ($hubsites['blocks'] as &$block) {
                    $block['import'] = 0;
                }
            }
            if (count($hubsites['layouts']) > 0) {
                foreach ($hubsites['layouts'] as &$layout) {
                    $layout['import'] = 0;
                }
            }
            $o .= replace_macros(get_markup_template('hubsites_scan.tpl', 'addon/hubsites'), array(
                '$header' => t('Hubsites: Scanned repository'),
                '$desc' => t('The cloned repo contains the following webpage elements. Imported elements will overwrite existing elements by name (blocks) or pagelink (pages).'),
		'$select_all' => t('Select all'),
                '$deselect_all' => t('Deselect all'),
                '$submit' => t('Import selected elements'),
                '$action' => 'import',
                '$pages' => $hubsites['pages'],
                '$blocks' => $hubsites['blocks'],
                '$layouts' => $hubsites['layouts']
            ));
            $_SESSION['hubsites'] = $hubsites;
            return $o;
        case 'import':
            $hubsites = $_SESSION['hubsites'];
            $o .= replace_macros(get_markup_template('hubsites_import.tpl', 'addon/hubsites'), array(
                '$header' => t('Hubsites: Import Results'),
                '$desc' => t('This is a report of the webpage element import process.'),
                '$nav' => t('<a href="/hubsites">Import another Hubsites repo.</a>'),
                '$blocks' => $hubsites['import_blocks'],
                '$pages' => $hubsites['import_pages'],
                '$layouts' => $hubsites['import_layouts']
            ));
            return $o;
        case 'updaterepos':            
        default:
            if (!local_channel()) {
                notice('You must be logged into a local account.');
                return login();
            }
            $repos = hubsites_load_repos($channel);
            $o .= replace_macros(get_markup_template('hubsites_clone.tpl', 'addon/hubsites'), array(
                '$header' => t('Hubsites: Clone a hubsite git repository'),
                '$baseurl' => z_root(),
                '$desc' => t('Import webpage elements from a git repo. Enter a repo URL with the form <b>http://git.server/repo-name.git</b>'),
                '$notes' => t('Note: a known bug prevents imported layouts from being immediately applied '
                        . 'to imported pages that use them. Re-importing the pages and layouts should apply '
                        . 'the layouts to the pages.'),
                '$repoURL' => array('repoURL', t('Git repository URL'), '', ''),
                '$submit1' => t('Clone'),
                '$action1' => 'clone',
                '$submit2' => t('Apply changes'),
                '$action2' => 'updaterepos',
                '$repos' => (empty($repos) ? array() : $repos)
            ));
            return $o;
    }
}

function hubsites_load_repos($channel) {
    $_SESSION['hubsites']['repos'] = [];
    $storepath = realpath(__DIR__ . '/../../../../store/');
    $dirtoscan = $storepath . '/hubsiterepos/' . $channel['channel_address'] . '/';
    $dirlist = scandir($dirtoscan);
    if ($dirlist) {
        $repoid = 0;
        foreach ($dirlist as $element) {
            if ($element === '.' || $element === '..') {
                continue;
            }
            $folder = $dirtoscan . '/' . $element . '/';
            if (is_dir($folder)) {
                $git = new PHPGit\Git();
                $git->setRepository($folder);
                $repo = $git->remote();
                $repoid = $repoid + 1;
                $_SESSION['hubsites']['repos'][] = array('url' => $repo['origin']['fetch'], 
                                                         'path' => $folder,
                                                         'id' => $repoid );
            }
        }
    }
    return $_SESSION['hubsites']['repos'];
}
