<?php
/**
 * eXtreme Message Board
 * XMB 1.9.11
 *
 * Developed And Maintained By The XMB Group
 * Copyright (c) 2001-2012, The XMB Group
 * http://www.xmbforum2.com/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 **/

define('X_SCRIPT', 'viewthread.php');

require 'header.php';

validatePpp();
$printable_ppp = 100;

$pid = getInt('pid');
$tid = getInt('tid');
$fid = getInt('fid');
$goto = postedVar('goto', '', FALSE, FALSE, FALSE, 'g');
$action = postedVar('action', '', FALSE, FALSE, FALSE, 'g');

if ($goto == 'lastpost') {
    if ($pid > 0) {
        $query = $db->query("SELECT tid, dateline FROM ".X_PREFIX."posts WHERE pid=$pid");
        if ($db->num_rows($query) == 1) {
            $post = $db->fetch_array($query);
            $tid = $post['tid'];

            $query = $db->query("SELECT COUNT(pid) as postcount FROM ".X_PREFIX."posts WHERE tid=$tid AND dateline <= {$post['dateline']}");
            $posts = $db->result($query, 0);
            $db->free_result($query);
        } else {
            header('HTTP/1.0 404 Not Found');
            eval('$css = "'.template('css').'";');
            error($lang['textnothread']);
        }
    } else if ($tid > 0) {
        $query = $db->query("SELECT COUNT(pid) FROM ".X_PREFIX."posts WHERE tid=$tid");
        $posts = $db->result($query, 0);
        $db->free_result($query);

        if ($posts == 0) {
            header('HTTP/1.0 404 Not Found');
            eval('$css = "'.template('css').'";');
            error($lang['textnothread']);
        }

        $query = $db->query("SELECT pid FROM ".X_PREFIX."posts WHERE tid=$tid ORDER BY dateline DESC, pid DESC LIMIT 0, 1");
        $pid = $db->result($query, 0);
        $db->free_result($query);
    } else if ($fid > 0) {
        $pid = 0;
        $tid = 0;
        $query = $db->query("SELECT pid, tid, dateline FROM ".X_PREFIX."posts WHERE fid=$fid ORDER BY dateline DESC, pid DESC LIMIT 0, 1");
        if ($db->num_rows($query) == 1) {
            $posts = $db->fetch_array($query);
            $db->free_result($query);

            $pid = $posts['pid'];
            $tid = $posts['tid'];
        }

        $query = $db->query("SELECT p.pid, p.tid, p.dateline FROM ".X_PREFIX."posts p LEFT JOIN ".X_PREFIX."forums f USING (fid) WHERE f.fup=$fid ORDER BY p.dateline DESC, p.pid DESC LIMIT 0, 1");
        if ($db->num_rows($query) == 1) {
            $fupPosts = $db->fetch_array($query);
            $db->free_result($query);

            if ($pid == 0) {
                $pid = $fupPosts['pid'];
                $tid = $fupPosts['tid'];
            } elseif ($fupPosts['dateline'] > $posts['dateline']) {
                $pid = $fupPosts['pid'];
                $tid = $fupPosts['tid'];
            }
        }

        if ($pid == 0) {
            header('HTTP/1.0 404 Not Found');
            eval('$css = "'.template('css').'";');
            error($lang['textnothread']);
        }

        $query = $db->query("SELECT COUNT(pid) FROM ".X_PREFIX."posts WHERE tid=$tid");
        $posts = $db->result($query, 0);
        $db->free_result($query);
    } else {
        header('HTTP/1.0 404 Not Found');
        eval('$css = "'.template('css').'";');
        error($lang['textnothread']);
    }
    $page = quickpage($posts, $ppp);
    if ($page == 1) {
        $page = '';
    } else {
        $page = "&page=$page";
    }
    redirect("{$full_url}viewthread.php?tid=$tid$page#pid$pid", 0);

} else if ($goto == 'search') {
    $tidtest = $db->query("SELECT dateline FROM ".X_PREFIX."posts WHERE tid = $tid AND pid = $pid");
    if ($db->num_rows($tidtest) == 1) {
        $post = $db->fetch_array($tidtest);
        $posts = $db->result($db->query("SELECT COUNT(pid) FROM ".X_PREFIX."posts WHERE tid = $tid AND dateline <= {$post['dateline']}"), 0);
        $page = quickpage(($posts), $ppp);
        if ($page == 1) {
            $page = '';
        } else {
            $page = "&page=$page";
        }
        redirect("{$full_url}viewthread.php?tid=$tid$page#pid$pid", 0);
    } else {
        header('HTTP/1.0 404 Not Found');
        eval('$css = "'.template('css').'";');
        error($lang['textnothread']);
    }
}

loadtemplates(
'functions_bbcode_quickreply',
'functions_smilieinsert',
'functions_smilieinsert_smilie',
'viewthread_reply',
'viewthread_quickreply',
'viewthread_quickreply_captcha',
'viewthread',
'viewthread_modlog',
'viewthread_modoptions',
'viewthread_newpoll',
'viewthread_newtopic',
'viewthread_poll_options_view',
'viewthread_poll_options',
'viewthread_poll_submitbutton',
'viewthread_poll',
'viewthread_post',
'viewthread_post_email',
'viewthread_post_site',
'viewthread_post_icq',
'viewthread_post_aim',
'viewthread_post_msn',
'viewthread_post_yahoo',
'viewthread_post_search',
'viewthread_post_profile',
'viewthread_post_u2u',
'viewthread_post_ip',
'viewthread_post_repquote',
'viewthread_post_report',
'viewthread_post_edit',
'viewthread_post_attachmentthumb',
'viewthread_post_attachmentimage',
'viewthread_post_attachment',
'viewthread_post_sig',
'viewthread_post_nosig',
'viewthread_printable',
'viewthread_printable_row',
'viewthread_multipage'
);

smcwcache();

eval('$css = "'.template('css').'";');

$posts = '';

$query = $db->query("SELECT t.*, COUNT(pid) AS postcount FROM ".X_PREFIX."threads AS t LEFT JOIN ".X_PREFIX."posts USING (tid) WHERE t.tid=$tid GROUP BY t.tid");
if ($db->num_rows($query) != 1) {
    $db->free_result($query);
    header('HTTP/1.0 404 Not Found');
    error($lang['textnothread']);
}

$thread = $db->fetch_array($query);
$db->free_result($query);

$thislast = explode('|', $thread['lastpost']);

// Perform automatic maintenance
if ($thread['replies'] != $thread['postcount'] - 1) {
    updatethreadcount($tid);
}

if (strpos($thread['closed'], '|') !== false) {
    $moved = explode('|', $thread['closed']);
    if ($moved[0] == 'moved') {
        header('HTTP/1.0 301 Moved Permanently');
        header('Location: '.$full_url.'viewthread.php?tid='.$moved[1]);
        exit();
    }
}

$thread['subject'] = shortenString(rawHTMLsubject(stripslashes($thread['subject'])), 125, X_SHORTEN_SOFT|X_SHORTEN_HARD, '...');

$lastPid = isset($thislast[2]) ? $thislast[2] : 0;
$expire = $onlinetime + X_ONLINE_TIMER;
if (!isset($oldtopics)) {
    put_cookie('oldtopics', '|'.$lastPid.'|', $expire, $cookiepath, $cookiedomain, null, X_SET_HEADER);
} else if (false === strpos($oldtopics, '|'.$lastPid.'|')) {
    $oldtopics .= $lastPid.'|';
    put_cookie('oldtopics', $oldtopics, $expire, $cookiepath, $cookiedomain, null, X_SET_HEADER);
}

$fid = $thread['fid'];
$forum = getForum($fid);

if (($forum['type'] != 'forum' && $forum['type'] != 'sub') || $forum['status'] != 'on') {
    header('HTTP/1.0 404 Not Found');
    error($lang['textnoforum']);
}

$perms = checkForumPermissions($forum);
if (!$perms[X_PERMS_VIEW]) {
    if (X_GUEST) {
        redirect("{$full_url}misc.php?action=login", 0);
        exit;
    } else {
        error($lang['privforummsg']);
    }
} else if (!$perms[X_PERMS_PASSWORD]) {
    handlePasswordDialog($fid);
}

$fup = array();
if ($forum['type'] == 'sub') {
    $fup = getForum($forum['fup']);
    // prevent access to subforum when upper forum can't be viewed.
    $fupPerms = checkForumPermissions($fup);
    if (!$fupPerms[X_PERMS_VIEW]) {
        if (X_GUEST) {
            redirect("{$full_url}misc.php?action=login", 0);
            exit;
        } else {
            error($lang['privforummsg']);
        }
    } else if (!$fupPerms[X_PERMS_PASSWORD]) {
        handlePasswordDialog($fup['fid']);
    } else if ($fup['fup'] > 0) {
        $fupup = getForum($fup['fup']);
        nav('<a href="index.php?gid='.$fup['fup'].'">'.fnameOut($fupup['name']).'</a>');
        unset($fupup);
    }
    nav('<a href="forumdisplay.php?fid='.$fup['fid'].'">'.fnameOut($fup['name']).'</a>');
    unset($fup);
} else if ($forum['fup'] > 0) { // 'forum' in a 'group'
    $fup = getForum($forum['fup']);
    nav('<a href="index.php?gid='.$fup['fid'].'">'.fnameOut($fup['name']).'</a>');
    unset($fup);
}
nav('<a href="forumdisplay.php?fid='.$fid.'">'.fnameOut($forum['name']).'</a>');
nav($thread['subject']);

if ($SETTINGS['subject_in_title'] == 'on') {
    $threadSubject = '- '.$thread['subject'];
}

// Search-link
$searchlink = makeSearchLink($forum['fid']);

$allowimgcode = ($forum['allowimgcode'] == 'yes') ? $lang['texton']:$lang['textoff'];
$allowhtml = ($forum['allowhtml'] == 'yes') ? $lang['texton']:$lang['textoff'];
$allowsmilies = ($forum['allowsmilies'] == 'yes') ? $lang['texton']:$lang['textoff'];
$allowbbcode = ($forum['allowbbcode'] == 'yes') ? $lang['texton']:$lang['textoff'];

$replylink = $quickreply = '';

$status1 = modcheck($self['username'], $forum['moderator']);

if ($action == '') {
    $mpage = multipage($thread['postcount'], $ppp, 'viewthread.php?tid='.$tid);
    $multipage =& $mpage['html'];
    if (strlen($mpage['html']) != 0) {
        eval('$multipage = "'.template('viewthread_multipage').'";');
    }
    $printable_page = intval(floor($mpage['start'] / $printable_ppp)) + 1;
    $printable_page = $printable_page == 1 ? '' : "&amp;page=$printable_page";
    $printable_link = "viewthread.php?action=printable&amp;tid={$tid}{$printable_page}";

    eval('$header = "'.template('header').'";');

    if ($perms[X_PERMS_REPLY] And ($thread['closed'] == '' Or X_SADMIN)) {
        eval('$replylink = "'.template('viewthread_reply').'";');
        if ($SETTINGS['quickreply_status'] == 'on') {
            $usesigcheck = ($self['sig'] != '') ? $cheHTML : '';
            $captchapostcheck = '';
            if (X_GUEST && $SETTINGS['captcha_status'] == 'on' && $SETTINGS['captcha_post_status'] == 'on') {
                require ROOT.'include/captcha.inc.php';
                $Captcha = new Captcha();
                if ($Captcha->bCompatible !== false) {
                    $imghash = $Captcha->GenerateCode();
                    if ($SETTINGS['captcha_code_casesensitive'] == 'off') {
                        $lang['captchacaseon'] = '';
                    }
                    eval('$captchapostcheck = "'.template('viewthread_quickreply_captcha').'";');
                }
            }

            if ($SETTINGS['smileyinsert'] == 'on' And $forum['allowsmilies'] == 'yes' And $smiliesnum > 0) {
                eval('$quickbbcode = "'.template('functions_bbcode_quickreply').'";');

                $smilies = '<div align="center"><hr /><table border="0"><tr>';
                $smilies .= smilieinsert('quick');
                $smilies .= '</tr></table>';
                $smilies .= "<a href=\"misc.php?action=smilies\" onclick=\"Popup(this.href, 'Window', 200, 250); return false;\">{$lang['moresmilies']}</a>";
                $smilies .= "</div></td>";
            } else {
                $quickbbcode = '';
                $smilies = '';
            }

            $disableguest = X_GUEST ? 'style="display:none;"' : '';

            eval('$quickreply = "'.template('viewthread_quickreply').'";');
        }
    }

    if ($thread['closed'] == '') {
        $closeopen = $lang['textclosethread'];
    } else {
        $closeopen = $lang['textopenthread'];
    }

    if (X_GUEST) {
        $memcplink = '';
    } else {
        $memcplink = " | <a href=\"memcp.php?action=subscriptions&amp;subadd=$tid\">{$lang['textsubscribe']}</a> | <a href=\"memcp.php?action=favorites&amp;favadd=$tid\">{$lang['textaddfav']}</a>";
    }

    if ($perms[X_PERMS_THREAD]) {
        eval('$newtopiclink = "'.template('viewthread_newtopic').'";');
    } else {
        $newtopiclink = '';
    }

    if ($perms[X_PERMS_POLL]) {
        eval('$newpolllink = "'.template('viewthread_newpoll').'";');
    } else {
        $newpolllink = '';
    }

    $topuntop = ($thread['topped'] == 1) ? $lang['textuntopthread'] : $lang['texttopthread'];

    $specialrank = array();
    $rankposts = array();
    $queryranks = $db->query("SELECT id, title, posts, stars, allowavatars, avatarrank FROM ".X_PREFIX."ranks");
    while($query = $db->fetch_row($queryranks)) {
        $title = $query[1];
        $rposts= $query[2];
        if ($title == 'Super Administrator' || $title == 'Administrator' || $title == 'Super Moderator' || $title == 'Moderator') {
            $specialrank[$title] = "$query[0],$query[1],$query[2],$query[3],$query[4],$query[5]";
        } else {
            $rankposts[$rposts]  = "$query[0],$query[1],$query[2],$query[3],$query[4],$query[5]";
        }
    }
    $db->free_result($queryranks);

    $db->query("UPDATE ".X_PREFIX."threads SET views=views+1 WHERE tid='$tid'");

    $pollhtml = $poll = '';
    $vote_id = $voted = 0;

    if ($thread['pollopts'] == 1) {
        $query = $db->query("SELECT vote_id FROM ".X_PREFIX."vote_desc WHERE topic_id='$tid'");
        if ($query) {
            $vote_id = $db->fetch_array($query);
            $vote_id = (int) $vote_id['vote_id'];
        }
        $db->free_result($query);
    }

    if ($vote_id > 0) {
        if (X_MEMBER) {
            $query = $db->query("SELECT COUNT(vote_id) AS cVotes FROM ".X_PREFIX."vote_voters WHERE vote_id='$vote_id' AND vote_user_id=".intval($self['uid']));
            if ($query) {
                $voted = $db->fetch_array($query);
                $voted = (int) $voted['cVotes'];
            }
            $db->free_result($query);
        }

        $viewresults = (isset($viewresults) && $viewresults == 'yes') ? 'yes' : '';
        if ($voted >= 1 || $thread['closed'] == 'yes' || X_GUEST || $viewresults) {
            if ($viewresults) {
                $results = '- [<a href="viewthread.php?tid='.$tid.'"><font color="'.$cattext.'">'.$lang['backtovote'].'</font></a>]';
            } else {
                $results = '';
            }

            $num_votes = 0;
            $query = $db->query("SELECT vote_result, vote_option_text FROM ".X_PREFIX."vote_results WHERE vote_id='$vote_id'");
            while($result = $db->fetch_array($query)) {
                $num_votes += $result['vote_result'];
                $pollentry = array();
                $pollentry['name'] = postify($result['vote_option_text'], 'no', 'no', $forum['allowsmilies'], 'no', $forum['allowbbcode'], 'no', TRUE, 'yes');
                $pollentry['votes'] = $result['vote_result'];
                $poll[] = $pollentry;
            }
            $db->free_result($query);

            reset($poll);
            foreach($poll as $num=>$array) {
                $pollimgnum = 0;
                $pollbar = '';
                if ($array['votes'] > 0) {
                    $orig = round($array['votes']/$num_votes*100, 2);
                    $percentage = round($orig, 2);
                    $percentage .= '%';
                    $poll_length = (int) $orig;
                    if ($poll_length > 97) {
                        $poll_length = 97;
                    }
                    $pollbar = '<img src="'.$imgdir.'/pollbar.gif" height="10" width="'.$poll_length.'%" alt="'.$lang['altpollpercentage'].'" title="'.$lang['altpollpercentage'].'" border="0" />';
                } else {
                    $percentage = '0%';
                }
                eval('$pollhtml .= "'.template('viewthread_poll_options_view').'";');
                $buttoncode = '';
            }
        } else {
            $results = '- [<a href="viewthread.php?tid='.$tid.'&amp;viewresults=yes"><font color="'.$cattext.'">'.$lang['viewresults'].'</font></a>]';
            $query = $db->query("SELECT vote_option_id, vote_option_text FROM ".X_PREFIX."vote_results WHERE vote_id='$vote_id'");
            while($result = $db->fetch_array($query)) {
                $poll['id'] = (int) $result['vote_option_id'];
                $poll['name'] = postify($result['vote_option_text'], 'no', 'no', $forum['allowsmilies'], 'no', $forum['allowbbcode'], 'no', TRUE, 'yes');
                eval('$pollhtml .= "'.template('viewthread_poll_options').'";');
            }
            $db->free_result($query);
            eval('$buttoncode = "'.template('viewthread_poll_submitbutton').'";');
        }
        eval('$poll = "'.template('viewthread_poll').'";');
    }

    $startdate = '0';
    $startpid = '0';
    $enddate = '0';
    $sql = "SELECT dateline, pid "
         . "FROM ".X_PREFIX."posts "
         . "WHERE tid=$tid "
         . "ORDER BY dateline ASC, pid ASC "
         . "LIMIT {$mpage['start']}, ".($ppp + 1);
    $query1 = $db->query($sql);
    $rowcount = $db->num_rows($query1);
    if ($rowcount > 0) {
        $row = $db->fetch_array($query1);
        $startdate = $row['dateline'];
        $startpid = $row['pid'];
        if ($rowcount <= $ppp) {
            $enddate = $onlinetime;
        } else {
            $db->data_seek($query1, $rowcount - 1);
            $row = $db->fetch_array($query1);
            $enddate = $row['dateline'];
        }
    }
    $db->free_result($query1);

    $thisbg = $altbg2;
    $sql = "SELECT p.*, m.* "
         . "FROM "
         . "( "
         . "  ( "
         . "    SELECT 'post' AS type, fid, tid, author, subject, dateline, pid, message, icon, usesig, useip, bbcodeoff, smileyoff "
         . "    FROM ".X_PREFIX."posts "
         . "    WHERE tid=$tid AND (dateline > $startdate OR dateline = $startdate AND pid >= $startpid)"
         . "    ORDER BY dateline ASC, pid ASC "
         . "    LIMIT $ppp "
         . "  ) "
         . "  UNION ALL "
         . "  ( "
         . "    SELECT 'modlog' AS type, fid, tid, username AS author, action AS subject, date AS dateline, '', '', '', '', '', '', '' "
         . "    FROM ".X_PREFIX."logs "
         . "    WHERE tid=$tid AND date >= $startdate AND date < $enddate "
         . "  ) "
         . ") AS p "
         . "LEFT JOIN ".X_PREFIX."members m ON m.username=p.author "
         . "ORDER BY p.dateline ASC, p.type DESC, p.pid ASC ";
    $querypost = $db->query($sql);

    if ($forum['attachstatus'] == 'on') {
        require('include/attach.inc.php');
        $queryattach = $db->query("SELECT a.aid, a.pid, a.filename, a.filetype, a.filesize, a.downloads, a.img_size, thumbs.aid AS thumbid, thumbs.filename AS thumbname, thumbs.img_size AS thumbsize FROM ".X_PREFIX."attachments AS a LEFT JOIN ".X_PREFIX."attachments AS thumbs ON a.aid=thumbs.parentid INNER JOIN ".X_PREFIX."posts AS p ON a.pid=p.pid WHERE p.tid=$tid AND a.parentid=0");
    }

    $tmoffset = ($timeoffset * 3600) + ($addtime * 3600);
    while($post = $db->fetch_array($querypost)) {
        // Perform automatic maintenance
        if ($post['type'] == 'post' And $post['fid'] != $thread['fid']) {
            $db->query('UPDATE '.X_PREFIX.'posts SET fid='.$thread['fid'].' WHERE pid='.$post['pid']);
        }

        $post['avatar'] = str_replace("script:", "sc ript:", $post['avatar']);

        if ($onlinetime - (int)$post['lastvisit'] <= X_ONLINE_TIMER) {
            if ($post['invisible'] == 1) {
                if (!X_ADMIN) {
                    $onlinenow = $lang['memberisoff'];
                } else {
                    $onlinenow = $lang['memberison'].' ('.$lang['hidden'].')';
                }
            } else {
                $onlinenow = $lang['memberison'];
            }
        } else {
            $onlinenow = $lang['memberisoff'];
        }

        $date = gmdate($dateformat, $post['dateline'] + $tmoffset);
        $time = gmdate($timecode, $post['dateline'] + $tmoffset);

        $poston = $lang['textposton'].' '.$date.' '.$lang['textat'].' '.$time;

        if ($post['icon'] != '' && file_exists($smdir.'/'.$post['icon'])) {
            $post['icon'] = '<img src="'.$smdir.'/'.$post['icon'].'" alt="'.$post['icon'].'" border="0" />';
        } else {
            $post['icon'] = '<img src="'.$imgdir.'/default_icon.gif" alt="[*]" border="0" />';
        }

        if ($post['author'] != 'Anonymous' && $post['username']) {
            if (X_MEMBER && $post['showemail'] == 'yes') {
                eval('$email = "'.template('viewthread_post_email').'";');
            } else {
                $email = '';
            }

            if ($post['site'] == '') {
                $site = '';
            } else {
                $post['site'] = str_replace("http://", "", $post['site']);
                $post['site'] = "http://$post[site]";
                eval('$site = "'.template('viewthread_post_site').'";');
            }

            $encodename = recodeOut($post['author']);
            $profilelink = "<a href=\"./member.php?action=viewpro&amp;member=$encodename\">{$post['author']}</a>";

            $icq = '';
            if ($post['icq'] != '' && $post['icq'] > 0) {
                eval('$icq = "'.template('viewthread_post_icq').'";');
            }

            $aim = '';
            if ($post['aim'] != '') {
                $post['aim'] = recodeOut($post['aim']);
                eval('$aim = "'.template('viewthread_post_aim').'";');
            }

            $msn = '';
            if ($post['msn'] != '') {
                $post['msn'] = recodeOut($post['msn']);
                eval('$msn = "'.template('viewthread_post_msn').'";');
            }

            $yahoo = '';
            if ($post['yahoo'] != '') {
                $post['yahoo'] = recodeOut($post['yahoo']);
                eval('$yahoo = "'.template('viewthread_post_yahoo').'";');
            }

            if (X_GUEST && $SETTINGS['captcha_status'] == 'on' && $SETTINGS['captcha_search_status'] == 'on') {
                $search = '';
            } else {
                eval('$search = "'.template('viewthread_post_search').'";');
            }

            eval('$profile = "'.template('viewthread_post_profile').'";');
            if (X_GUEST) {
                $u2u = '';
            } else {
                eval('$u2u = "'.template('viewthread_post_u2u').'";');
            }

            $showtitle = $post['status'];
            $rank = array();
            if ($post['status'] == 'Administrator' || $post['status'] == 'Super Administrator' || $post['status'] == 'Super Moderator' || $post['status'] == 'Moderator') {
                $sr = $post['status'];
                $rankinfo = explode(",", $specialrank[$sr]);
                $rank['allowavatars'] = $rankinfo[4];
                $rank['title'] = $lang[$status_translate[$status_enum[$sr]]];
                $rank['stars'] = $rankinfo[3];
                $rank['avatarrank'] = $rankinfo[5];
            } else if ($post['status'] == 'Banned') {
                $rank['allowavatars'] = 'no';
                $rank['title'] = $lang['textbanned'];
                $rank['stars'] = 0;
                $rank['avatarrank'] = '';
            } else {
                $last_max = -1;
                foreach($rankposts as $key => $rankstuff) {
                    if ($post['postnum'] >= $key && $key > $last_max) {
                        $last_max = $key;
                        $rankinfo = explode(",", $rankstuff);
                        $rank['allowavatars'] = $rankinfo[4];
                        $rank['title'] = $rankinfo[1];
                        $rank['stars'] = $rankinfo[3];
                        $rank['avatarrank'] = $rankinfo[5];
                    }
                }
            }

            $allowavatars = $rank['allowavatars'];
            $stars = str_repeat('<img src="'.$imgdir.'/star.gif" alt="*" border="0" />', $rank['stars']) . '<br />';
            $showtitle = ($post['customstatus'] != '') ? $post['customstatus'].'<br />' : $rank['title'].'<br />';

            if ($allowavatars == 'no') {
                $post['avatar'] = '';
            }

            if ($rank['avatarrank'] != '') {
                $rank['avatar'] = '<img src="'.$rank['avatarrank'].'" alt="'.$lang['altavatar'].'" border="0" /><br />';
            }

            $tharegdate = gmdate($dateformat, $post['regdate'] + $tmoffset);

            $avatar = '';
            if ($SETTINGS['avastatus'] == 'on' || $SETTINGS['avastatus'] == 'list') {
                if ($post['avatar'] != '' && $allowavatars != "no") {
                    $avatar = '<img src="'.$post['avatar'].'" alt="'.$lang['altavatar'].'" border="0" />';
                }
            }

            if ($post['mood'] != '') {
                $mood = '<strong>'.$lang['mood'].'</strong> '.postify($post['mood'], 'no', 'no', 'yes', 'no', 'yes', 'no', true, 'yes');
            } else {
                $mood = '';
            }

            if ($post['location'] != '') {
                $post['location'] = rawHTMLsubject($post['location']);
                $location = '<br />'.$lang['textlocation'].' '.$post['location'];
            } else {
                $location = '';
            }
        } else {
            $post['author'] = ($post['author'] == 'Anonymous') ? $lang['textanonymous'] : $post['author'];
            $showtitle = $lang['textunregistered'].'<br />';
            $stars = '';
            $avatar = '';
            $rank['avatar'] = '';
            $post['postnum'] = 'N/A';
            $tharegdate = 'N/A';
            $email = '';
            $site = '';
            $icq = '';
            $msn = '';
            $aim = '';
            $yahoo = '';
            $profile = '';
            $search = '';
            $u2u = '';
            $location = '';
            $mood = '';
            $encodename = '';
            $profilelink = $post['author'];
        }

        $ip = '';
        if (X_ADMIN) {
            eval('$ip = "'.template('viewthread_post_ip').'";');
        }

        $repquote = '';
        if ($perms[X_PERMS_REPLY] and ($thread['closed'] != 'yes' or X_SADMIN)) {
            eval('$repquote = "'.template('viewthread_post_repquote').'";');
        }

        $reportlink = '';
        if (X_MEMBER && $post['author'] != $xmbuser && $SETTINGS['reportpost'] == 'on') {
            eval('$reportlink = "'.template('viewthread_post_report').'";');
        }

        $edit = '';

        /*Begin Edit Time mod for posts by Polverone (this controls visibility
         of the edit button)*/
        $maxelapsed = 86400; /*24 hours*/
        /*special case for forum 20, prepublication*/
        if ($fid == 20) {
	$maxelapsed = 86400*30;
	}
	/* special case for forum 11, detritus */
	else if ($fid == 11) {
	$maxelapsed = 0;
	}
        $elapsed = time() - $post['dateline'];
        $useredit = 0;
        if ($elapsed < $maxelapsed)
          {
            $useredit=1;
          }
        if (modcheckPost($self['username'], $forum['moderator'], $post['status']) == 'Moderator' || ($thread['closed'] != 'yes' && $post['author'] == $xmbuser && $useredit)) {
            eval('$edit = "'.template('viewthread_post_edit').'";');
        }
        /*End edit time mod by Polverone*/


        $bbcodeoff = $post['bbcodeoff'];
        $smileyoff = $post['smileyoff'];

        if ($forum['attachstatus'] == 'on' and $db->num_rows($queryattach) > 0) {
            $files = array();
            $db->data_seek($queryattach, 0);
            while($attach = $db->fetch_array($queryattach)) {
                if ($attach['pid'] == $post['pid']) {
                    $files[] = $attach;
                }
            }
            if (count($files) > 0) {
                bbcodeFileTags($post['message'], $files, $post['pid'], ($forum['allowbbcode'] == 'yes' and $bbcodeoff == 'no'));
            }
        }

        $post['message'] = postify(stripslashes($post['message']), $smileyoff, $bbcodeoff, $forum['allowsmilies'], $forum['allowhtml'], $forum['allowbbcode'], $forum['allowimgcode']);

        if ($post['usesig'] == 'yes') {
            $post['sig'] = postify($post['sig'], 'no', 'no', $forum['allowsmilies'], $SETTINGS['sightml'], $SETTINGS['sigbbcode'], $forum['allowimgcode'], false);
            eval("\$post['message'] .= \"".template('viewthread_post_sig')."\";");
        } else {
            eval("\$post['message'] .= \"".template('viewthread_post_nosig')."\";");
        }

        if (!isset($rank['avatar'])) {
            $rank['avatar'] = '';
        }

        if ($post['type'] == 'post') {

            if ($post['subject'] != '') {
                $linktitle = rawHTMLsubject(stripslashes($post['subject']));
                $post['subject'] = wordwrap($linktitle, 150, '<br />', TRUE).'<br />';
            } else {
                $linktitle = $thread['subject'];
            }

            eval('$posts .= "'.template('viewthread_post').'";');

        } else {

            $poston = $date.' '.$lang['textat'].' '.$time;
            $post['message'] = $lang["modlog_{$post['subject']}"].'<br />'.$poston;
            eval('$posts .= "'.template('viewthread_modlog').'";');

        }

        if ($thisbg == $altbg2) {
            $thisbg = $altbg1;
        } else {
            $thisbg = $altbg2;
        }
    }
    $db->free_result($querypost);

    $modoptions = '';
    if ('Moderator' == $status1) {
        eval('$modoptions = "'.template('viewthread_modoptions').'";');
    }
    eval('$viewthread = "'.template('viewthread').'";');
    end_time();
    eval('$footer = "'.template('footer').'";');
    echo $header, $viewthread, $footer;
} else if ($action == 'attachment') {
    // Validate action
    if (!($forum['attachstatus'] == 'on' And $pid > 0 And $tid > 0)) {
        header('HTTP/1.0 404 Not Found');
        error($lang['textnothread']);
    }

    // Validate PID and TID
    $query = $db->query("SELECT aid, filename FROM ".X_PREFIX."attachments AS a INNER JOIN ".X_PREFIX."posts AS p USING (pid) WHERE a.pid=$pid AND a.parentid=0 AND p.tid=$tid ORDER BY aid LIMIT 1");
    if ($db->num_rows($query) != 1) {
        header('HTTP/1.0 404 Not Found');
        error($lang['textnothread']);
    }

    // Redirect to new URL
    $file = $db->fetch_array($query);
    $db->free_result($query);
    require('include/attach.inc.php');
    $url = getAttachmentURL($file['aid'], $pid, $file['filename'], FALSE);
    header('HTTP/1.0 301 Moved Permanently');
    header('Location: '.$url);
} else if ($action == 'printable') {
    $mpage = multipage($thread['postcount'], $printable_ppp, 'viewthread.php?action=printable&amp;tid='.$tid);
    $multipage =& $mpage['html'];
    if (strlen($mpage['html']) != 0) {
        eval('$multipage = "'.template('viewthread_multipage').'";');
    }

    $normal_page = intval(floor($mpage['start'] / $ppp)) + 1;

    $threadlink = $normal_page == 1 ? "viewthread.php?tid=$tid" : "viewthread.php?tid=$tid&amp;page=$normal_page";

    setCanonicalLink($threadlink);

    // This first query does not access any table data if the new thread_optimize index is available.  :)
    $criteria = '';
    $offset = '';
    if ($mpage['start'] <= 300) {
        // However, we need to be beyond page 1 to get any boost.
        $offset = "{$mpage['start']},";
    } else {
        $sql = "SELECT dateline, pid "
             . "FROM ".X_PREFIX."posts "
             . "WHERE tid=$tid "
             . "ORDER BY dateline ASC, pid ASC "
             . "LIMIT {$mpage['start']}, 1";
        $query1 = $db->query($sql);
        if ($row = $db->fetch_array($query1)) {
            $criteria = "AND (dateline > {$row['dateline']} OR dateline = {$row['dateline']} AND pid >= {$row['pid']})";
        }
        $db->free_result($query1);
    }

    // This second query retrieves table data via multi-column criteria.
    $sql = "SELECT * "
         . "FROM ".X_PREFIX."posts "
         . "WHERE tid=$tid $criteria "
         . "ORDER BY dateline ASC, pid ASC "
         . "LIMIT $offset $printable_ppp";

    $querypost = $db->query($sql);
    if ($forum['attachstatus'] == 'on') {
        require('include/attach.inc.php');
        $queryattach = $db->query("SELECT a.aid, a.pid, a.filename, a.filetype, a.filesize, a.downloads, a.img_size, thumbs.aid AS thumbid, thumbs.filename AS thumbname, thumbs.img_size AS thumbsize FROM ".X_PREFIX."attachments AS a LEFT JOIN ".X_PREFIX."attachments AS thumbs ON a.aid=thumbs.parentid INNER JOIN ".X_PREFIX."posts AS p ON a.pid=p.pid WHERE p.tid=$tid AND a.parentid=0");
    }

    $counter = 0;
    $posts = '';
    $tmoffset = ($timeoffset * 3600) + ($addtime * 3600);
    while($post = $db->fetch_array($querypost)) {
        $date = gmdate($dateformat, $post['dateline'] + $tmoffset);
        $time = gmdate($timecode, $post['dateline'] + $tmoffset);
        $poston = "$date $lang[textat] $time";
        $bbcodeoff = $post['bbcodeoff'];
        $smileyoff = $post['smileyoff'];
        if ($counter == 0) {
            $subject = '';
        } else {
            $subject = rawHTMLsubject(stripslashes($post['subject']));
        }
        if ($forum['attachstatus'] == 'on' and $db->num_rows($queryattach) > 0) {
            $files = array();
            $db->data_seek($queryattach, 0);
            while($attach = $db->fetch_array($queryattach)) {
                if ($attach['pid'] == $post['pid']) {
                    $files[] = $attach;
                }
            }
            if (count($files) > 0) {
                bbcodeFileTags($post['message'], $files, $post['pid'], ($forum['allowbbcode'] == 'yes' and $bbcodeoff == 'no'));
            }
        }
        $post['message'] = postify(stripslashes($post['message']), $smileyoff, $bbcodeoff, $forum['allowsmilies'], $forum['allowhtml'], $forum['allowbbcode'], $forum['allowimgcode']);
        eval('$posts .= "'.template('viewthread_printable_row').'";');
        $counter++;
    }
    $db->free_result($querypost);
    eval('echo "'.template('viewthread_printable').'";');
} else {
    header('HTTP/1.0 404 Not Found');
    error($lang['textnoaction']);
}
?>
