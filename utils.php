<?php

/**
 * @return the value at $index in $array or $default if $index is not set.
 */
function idx(array $array, $key, $default = null) {
  return array_key_exists($key, $array) ? $array[$key] : $default;
}

function he($str) {
  return htmlentities($str, ENT_QUOTES, "UTF-8");
}

function isLocalhost () {
    if ($_SERVER['REMOTE_ADDR'] === '::1' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1') {
        return true;
    } else {
        return false;
    }
}

function psqlConnectionStringFromDatabaseUrl () {
    extract(parse_url(getenv('DATABASE_URL')));
    return "user=$user password=$pass host=$host dbname=" . substr($path, 1) . ' sslmode=require';
}

function getFacebookAppToken () {
    $url = 'https://graph.facebook.com/oauth/access_token?'.
           'client_id=' . getenv('FACEBOOK_APP_ID') .
           '&client_secret=' . getenv('FACEBOOK_SECRET') .
           '&grant_type=client_credentials';
    $res = file_get_contents($url);
    list(, $token) = explode('=', $res);
    return $token;
}

function findReportsByReporteeId ($id) {
    global $me, $db;
    $reports_found = array();
    // Search for reports about this person.
    $result = pg_query_params($db->getHandle(),
        'SELECT * FROM incidents WHERE reportee_id=$1',
        array($id)
    );
    if (pg_num_rows($result)) {
        while ($row = pg_fetch_assoc($result)) {
            $r = new PATIncident($row);
            $r->setReader($me);
            if ($r->isVisible()) {
                $reports_found[] = $r;
            }
        }
    }
    return $reports_found;
}

function processFacebookSearchResults ($response) {
    $r = array();
    if ($response['data']) {
        foreach ($response['data'] as $result) {
            array_push($r, $result);
        }
        if ($response['paging']) {
            $p = parse_url($response['paging']['next']);
            $x = array();
            parse_str($p['query'], $x);
            // Only set the next page if we didn't see all the results yet.
            if ($x['limit'] <= count($response['data'])) {
                $n = $response['paging']['next'];
            }
        }
    }
    return array('search_results' => $r, 'next_page' => $n);
}
