<?php
declare(strict_types=1);

use SKien\MySqlTools\MySqlInfoMD;

/**
 * Generating report for MySQL Database in MD format.
 *
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
include_once "autoloader.php";

$strDBHost = 'localhost';
$strDBName = 'demo';
$strDBUser = 'demo';
$strDBPwd = 'demoPWD';
$oDB = mysqli_connect($strDBHost, $strDBUser, $strDBPwd, $strDBName);

$iType = intval($_GET['type'] ?? '0');
$oInfo = new MySqlInfoMD($oDB, $iType);
switch ($iType) {
    case MySqlInfoMD::STANDALONE:
        $oInfo->setOptions(MySqlInfoMD::OPT_CREATE_SUBFOLDER | MySqlInfoMD::OPT_CREATE_STMT);
        $oInfo->setPath('/Example');
        $oInfo->setImagePath('/images');
        echo 'Database information generated (Type: STANDALONE)';
        break;
    case MySqlInfoMD::GITHUB_WIKI:
        $oInfo->setOptions(MySqlInfoMD::OPT_CREATE_TOC | MySqlInfoMD::OPT_CREATE_STMT);
        $oInfo->setPath('/GitHubWiki');
        $oInfo->setImagePath('/images');
        echo 'Database information generated (Type: GITHUB_WIKI)';
        break;
    case MySqlInfoMD::SKIEN_WIKI:
        $oInfo->setOptions(MySqlInfoMD::OPT_CREATE_SUBFOLDER | MySqlInfoMD::OPT_CREATE_STMT | MySqlInfoMD::OPT_NO_INDEXLINK);
        $oInfo->setRoot($_SERVER['DOCUMENT_ROOT']);
        $oInfo->setPath('/packages/MySqlInfoMD/wiki/DBDesign');
        $oInfo->setImagePath('/packages/MySqlInfoMD/wiki/images');
        echo 'Database information generated (Type: SKIEN_WIKI)';
        break;
}
$oInfo->buildInfo();
