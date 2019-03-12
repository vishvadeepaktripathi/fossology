<?php
/***********************************************************
 Copyright (C) 2019 Siemens AG
 
 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 version 2 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 ***********************************************************/


class exportLicenseRef
{
  /**
   * @brief get SPDX license list from https://spdx.org/licenses/licenses.json
   *  Scan each license from list to get actual license data.
   */
  function getListSPDX($type, $URL)
  {
    global $LIBEXECDIR;

    if (!is_dir($LIBEXECDIR)) {
      print "FATAL: Directory '$LIBEXECDIR' does not exist.\n";
      return (1);
    }

    $dir = opendir($LIBEXECDIR);
    if (!$dir) {
      print "FATAL: Unable to access '$LIBEXECDIR'.\n";
      return (1);
    }

    echo "INFO: get existing licenseRef.json from $LIBEXECDIR\n";
    $getExistingLicenseRefData = file_get_contents("$LIBEXECDIR/licenseRef.json");
    $existingLicenseRefData = (array) json_decode($getExistingLicenseRefData, true);
    $maxkey = array_search(max($existingLicenseRefData), $existingLicenseRefData);
    $newRfPk = $existingLicenseRefData[$maxkey]['rf_pk'] + 1;

    $getList = json_decode(file_get_contents($URL));
    foreach ($getList->$type as $listValue) {
      $getCurrentData = file_get_contents($listValue->detailsUrl);
      $getCurrentData = (array) json_decode($getCurrentData, true);
      echo "INFO: search for license $getCurrentData[licenseId]\n";
      $licenseIdCheck = array_search($getCurrentData['licenseId'], array_column($existingLicenseRefData, 'rf_shortname'));
      $MD5Check = array_search(md5($getCurrentData['licenseText']), array_column($existingLicenseRefData, 'rf_md5'));
      if(!empty($licenseIdCheck)) {
        echo "INFO: license $getCurrentData[licenseId] already exists updating\n\n";
        $existingLicenseRefData[$licenseIdCheck]['rf_text'] = $getCurrentData['licenseText'];
        $existingLicenseRefData[$licenseIdCheck]['rf_url'] = $getCurrentData['seeAlso'][0];
//        $existingLicenseRefData[$licenseIdCheck]['rf_OSIapproved'] = ($getCurrentData['isOsiApproved'] == 'true' ? "t" : "f");
        $existingLicenseRefData[$licenseIdCheck]['rf_md5'] = md5($getCurrentData['licenseText']);
        $existingLicenseRefData[$licenseIdCheck]['rf_notes'] = (array_key_exists("licenseComments", $getCurrentData) ? $getCurrentData['licenseComments'] : $existingLicenseRefData[$licenseIdCheck]['rf_notes']);
      } else if(!empty($MD5Check)) {
        continue;
      } else {
        echo "INFO: license $getCurrentData[licenseId] doesn't exist adding as new license\n\n";
        $existingLicenseRefData[] = array(
              'rf_pk' => $newRfPk,
              'rf_shortname' => $getCurrentData['licenseId'],
              'rf_text' =>  $getCurrentData['licenseText'],
              'rf_url' =>  $getCurrentData['seeAlso'][0],
              'rf_add_date' => null,
              'rf_copyleft' => null,
//            'rf_OSIapproved' => ($getCurrentData['isOsiApproved'] == 'true' ? "t" : "f"),
              'rf_OSIapproved' => null,
              'rf_fullname' => $getCurrentData['name'],
              'rf_FSFfree' => null,
              'rf_GPLv2compatible' => null,
              'rf_GPLv3compatible' => null,
              'rf_notes' => (array_key_exists("licenseComments", $getCurrentData) ? $getCurrentData['licenseComments'] : null),
              'rf_Fedora' => null,
              'marydone' => "f",
              'rf_active' => "t",
              'rf_text_updatable' => "f",
              'rf_md5' => md5($getCurrentData['licenseText']),
              'rf_detector_type' => 1,
              'rf_source' => null,
              'rf_risk' => null,
              'rf_spdx_compatible' => "t",
              'rf_flag' => null
            );
        $newRfPk++;
      }
    }
    $newFileName = "licenseRefNew.json";
    if (file_exists($newFileName)) { 
      unlink($newFileName); 
    }
    $file = fopen($newFileName,'w+');
    file_put_contents('licenseRefNew.json', json_encode($existingLicenseRefData, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES));
    fclose($file);
  }
}
$obj = new exportLicenseRef();
$scanList = array('licenses' => 'https://spdx.org/licenses/licenses.json', 
                  'exceptions' => 'https://spdx.org/licenses/exceptions.json'
                 );
//foreach ($scanList as $type => $URL) {
  $obj->getListSPDX('licenses', 'https://spdx.org/licenses/licenses.json');
//}
