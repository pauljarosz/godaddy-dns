<?php

/**
 * @description Update Godaddys DNS A record entries
 *
 * @author Paul Jarosz
 *
 * Anyone is free to copy, modify, publish, use, compile, sell, or
 * distribute this software, either in source code form or as a compiled
 * binary, for any purpose, commercial or non-commercial, and by any
 * means.
 *
 * In jurisdictions that recognize copyright laws, the author or authors
 * of this software dedicate any and all copyright interest in the
 * software to the public domain. We make this dedication for the benefit
 * of the public at large and to the detriment of our heirs and
 * successors. We intend this dedication to be an overt act of
 * relinquishment in perpetuity of all present and future rights to this
 * software under copyright law.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
 * OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * For more information, please refer to <https://unlicense.org>
 */

/*************************************************
 * Configure yor settings
 *************************************************/
$key = '';
$secret = '';

$domains = array('domain1.com' => array('name1', 'name2'), 'domain2.com' => array('name3', 'name4'));

/*************************************************
 * Do not edit below these lines
 *************************************************/

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, 'http://ipinfo.io/json');
$result = curl_exec($ch);
//var_dump($result);

$data = json_decode($result);
//var_dump($data);
$myIp = $data->ip;
//echo "\nMy IP $myIp";

foreach($domains as $domain => $arecords) {
    // get all A records for this domain
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, 'https://api.godaddy.com/v1/domains/' . urlencode($domain) . '/records/A');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: sso-key $key:$secret"));
    $result = curl_exec($ch);
    //var_dump($result);

    if (($result == FALSE) || curl_errno($ch) || (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200)) {
        echo "\nGodaddy DNS Failed $domain";
        continue;
    }


    curl_close($ch);

    $records = json_decode($result);

    foreach ($records as $record) {
        foreach ($arecords as $arecord) {
            if ((strcasecmp($arecord, $record->name) == 0) && (strcasecmp($myIp, $record->data) != 0)) {
                $json = json_encode(array('name' => $record->name, 'data' => $myIp, 'ttl' => $record->ttl));
                //var_dump($json);
        
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_URL, 'https://api.godaddy.com/v1/domains/' . urlencode($domain) . '/records/A/' . urlencode($record->name));
                curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json', "Authorization: sso-key $key:$secret"));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                $result = curl_exec($ch);
                //var_dump($result);

                if (($result == FALSE) || curl_errno($ch) || (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200)) {
                    echo "\nGodaddy DNS Failed $domain $arecord";
                }

                curl_close($ch);
            }
        }
    }
}
