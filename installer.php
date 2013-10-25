<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Sascha Egerer <sascha@sascha-egerer.de>
 *  All rights reserved
 *
 *  This script is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

// Check if the installer is enabled
$enableInstallerFileName = 'ENABLE_INSTALLER';

if(session_start() && (isset($_SESSION['installationIsStarted']) || file_exists($enableInstallerFileName))) {

	if(filemtime($enableInstallerFileName) > time() - 60 * 30) {
		$_SESSION['installationIsStarted'] = TRUE;
	} else {
		printHeader();
		printMessage('Installer is disabled! The "ENABLE_INSTALLER" file is to old! Please delete it and create a new one to enable the installer script.');
		printFooter();
	}

	if(isset($_SESSION['installationIsStarted'])) {
		$installer = new installer();

		if(empty($_GET['step'])) {
			printHeader();
			$installer->startInstallation();
			printFooter();
		} else {
			$installer->$_GET['step']();
		}
	}
} else {
	printHeader();
	printMessage('Installer is disabled! Please create a file "ENABLE_INSTALLER" in the directory of the installer script!');
	printFooter();
}


class installer {
	public function __construct() {
	}
	
	public function startInstallation() {
		echo '<h1>TYPO3 easy Downloader</h1>';
		printMessage('<a href="#" id="startInstallation">START SYSTEM CHECK & DOWNLOAD &gt;</a>');
	}
	
	public function checkCurrentDirectoryForItems() {
		$checkItems = array('typo3_src', 'index.php', 'typo3');
		$messages = array();
		$status = 'success';
		$nextStep = 'checkIfFolderIsWritable';

		foreach ($checkItems as $item) {
			if(file_exists($item)) {
				$messages[] = 'The file or directory "' . $item . '" does already exist but must not to run the installer.';
			}
		}

		if (count($messages) === 0) {
			$messages[] = 'Directory structure is ok.';
		} else {
			$status = 'error';
			$nextStep = '';
		}

		self::echoJsonResponse($status, $messages, $nextStep);
	}

	public function checkIfFolderIsWritable() {
		$status = 'success';
		$messages = array();
		$nextStep = 'chooseFileTypeByExtractMethod';

		if (!is_writeable('.')) {
			$status = 'error';
			$messages[] = htmlentities('The directory "' . getcwd() . '" is not writable!');
		} else {
			$messages[] = 'Directory is writable.';
		}

		self::echoJsonResponse($status, $messages, $nextStep);
	}

	/**
	 * Check if we should use zip or tar.gz
	 */
	public function chooseFileTypeByExtractMethod() {
		$status = 'success';
		$messages = array();
		$nextStep = 'downloadCore';
		$additionalParams = '';
		$useFiletype = '';
		if (class_exists('PharData')) {
			$messages[] = 'Use tgz by PharData.';
			$useFiletype = 'PharData';
		} elseif (class_exists('ZipArchive')) {
			$messages[] = 'Use PHP ZipArchive extraction.';
			$useFiletype = 'ZipArchive';
		} elseif (@exec('unzip', $output, $returnCode) && $returnCode === 0) {
			$messages[] = 'Use system unzip binary.';
			$useFiletype = 'unzip';
		} else {
			$messages[] = 'It looks like that there is no supported extract method. PharData, ZipArchive and native unzip are not supported by your system.';
			$status = 'error';
			$nextStep = '';
		}

		if (!empty($useFiletype)) {
			$additionalParams = '&extractMethod=' . $useFiletype;
		}

		self::echoJsonResponse($status, $messages, $nextStep, $additionalParams);
	}

	public function downloadCore() {
		$status = 'success';
		$messages = array();
		$nextStep = '';
		$fileTypesForExtractMethods = array(
			'PharData' => '',
			'ZipArchive' => 'zip',
			'unzip' => 'zip'
		);

		if (isset($_GET['extractMethod']) && array_key_exists($_GET['extractMethod'], $fileTypesForExtractMethods)) {

		} else {
			$status = 'error';
			$messages[] = 'Ups... No or invalid Extract Method given?!';
		}

		if (!is_writeable('.')) {
			$status = 'error';
			$messages[] = htmlentities('The directory "' . getcwd() . '" is not writable!');
		} else {
			$messages[] = 'Directory is writable.';
		}

		self::echoJsonResponse($status, $messages, $nextStep);
	}

	/**
	 * @param string $status
	 * @param array $messages
	 * @param string $nextStep
	 * @param string $additionalParams
	 */
	static public function echoJsonResponse($status, array $messages, $nextStep, $additionalParams = '') {
		$messagesString = '';
		if (count($messages)) {
			foreach ($messages as &$message) {
				$message = '"' . htmlentities($message) . '"';
			}

			$messagesString = implode(',', $messages);
		}

		echo '{"status": "' . $status . '", "messages": [' . $messagesString . '], "nextStep": "' . $nextStep . '", "nextStepData": "' . $additionalParams . '"}';

	}
}

function printHeader() {
	$style = 'body {font: 100% Verdana, Arial, Helvetica, sans-serif;padding-top: 11em;background: #4f4f4f;}
	h1.logo {background: url(\'data:image/gif;base64,R0lGODlhfAAiAPf/AI1cJodZKHlULKurq3t7e9zc3J+fn5OTk/39/VdXV/7+/qtnG0FBQVpaWo2NjcvLy/Ly8qWlpZeXl0hISIeHh5WVlYSEhO7u7mJiYl1dXVNTU7+/v3FRL5aWlsTExLKysnp6ekdHR05OTkREROLi4lBFOUdCPfr6+llZWdHR0UJCQuh9CN3d3d7e3rS0tKOjo5mZmWRkZE5FOrq6utDQ0PT09FZWVtLS0r29vVhYWPv7+0pDPEtLS7e3t2pPMZCQkJhgIpqamoqKinR0dG5ubvn5+WFhYUdCPKFjH/SCBM3NzURBPkZGRr6+vviDAu3t7ezs7Ot/BoyMjNbW1kpDO21tbYaGhkNDQ8pyEtXV1fqEAf2FAP2FAbGxsaioqNl4DNF1D+fn571uFWVNM/eDAveDA39/f6tnHJNeI7FpGaGhoU1NTUFAP/Hx8VJGOcrKyvX19V5eXkVBPWZmZtfX17a2tpGRkfmEAq6uruDg4GxQMKampsXFxXl5eV9fX3BwcLdsF2lpaZ6envGBBHVTLUtDO1ZHOE1EOlJSUpxhIYRYKbpsF5SUlNN2D0JAPkxMTLJqGeJ7CXpVLGBgYKVlHul+B+h+B0xEO31WKsRwFL9uFampqbm5uWhoaMLCwt96CqqqqsbGxud9CHJycpKSkpubm0VFRdZ3DV9LNHFxcXx8fNB0EKKiooJYKa2trW5RL/z8/MFvFYtbJmBLNGFMNMHBweN8Ce6ABlBFOlFGOcnJyff399t5DOx/Bt/f34FXKrOzs7y8vObm5tPT081zEdTU1FlJNunp6fqEAqhmHJ9jH46Ojtra2piYmPyFAU9FOuR8CbCwsJlgIaJjH5phIejo6LlsF87Ozs/Pz2NjY8DAwFxcXLi4uNR2DlVVVWVlZe6ABXV1dbRqGbZrGNx5DE9PT52dnW9vb+F7CuJ7CklJSbttFpycnHFSLolaJ0pKSnZ2dnd3d/GBBWpqamtra51iIImJiYVZKKZlHVBQUFFRUe+ABa+vr/+GAP///z8/PyH/C1hNUCBEYXRhWE1QPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS4zLWMwMTEgNjYuMTQ1NjYxLCAyMDEyLzAyLzA2LTE0OjU2OjI3ICAgICAgICAiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIgeG1wTU06T3JpZ2luYWxEb2N1bWVudElEPSJ4bXAuZGlkOjlEREZFRDlENUEyOEUyMTFBMkY0RUUzNTg3M0Y4OUUxIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjY1MkMwRTY4Mjg1QjExRTI5MEJDRTMzQUNGRjM5NzlEIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjY1MkMwRTY3Mjg1QjExRTI5MEJDRTMzQUNGRjM5NzlEIiB4bXA6Q3JlYXRvclRvb2w9IkFkb2JlIFBob3Rvc2hvcCBDUzYgKFdpbmRvd3MpIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6OUVERkVEOUQ1QTI4RTIxMUEyRjRFRTM1ODczRjg5RTEiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6OURERkVEOUQ1QTI4RTIxMUEyRjRFRTM1ODczRjg5RTEiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz4B//79/Pv6+fj39vX08/Lx8O/u7ezr6uno5+bl5OPi4eDf3t3c29rZ2NfW1dTT0tHQz87NzMvKycjHxsXEw8LBwL++vby7urm4t7a1tLOysbCvrq2sq6qpqKempaSjoqGgn56dnJuamZiXlpWUk5KRkI+OjYyLiomIh4aFhIOCgYB/fn18e3p5eHd2dXRzcnFwb25tbGtqaWhnZmVkY2JhYF9eXVxbWllYV1ZVVFNSUVBPTk1MS0pJSEdGRURDQkFAPz49PDs6OTg3NjU0MzIxMC8uLSwrKikoJyYlJCMiISAfHh0cGxoZGBcWFRQTEhEQDw4NDAsKCQgHBgUEAwIBAAAh+QQBAAD/ACwAAAAAfAAiAAAI/wD/CRxI8N+zdgBMEDTUSEu/h4NsYVnwq1DBixgzatzIsaPHjwUNARAT5eGCgmceqlzZDxkgXCBjypxJc6MMNF9Y9iNUMJNOndB21BxKtOjFEgvu/Ozno6C1pSwDGJ1KNaaiJFB3FsSXVaWsqmDDXnTXtR+agq3K9sMktm3YSmVjhVTb1K1do2oHsSkYqeuWI3cDD1Xbb0zBaV3TYfxDoLHjx44RTXDsLSOKxvAGGrFg54ABAxGamVnDcUKfIBEMUEigMQGIDi/UONhWkDClgrO6nrxYwJ/v38B/E2CQx/eGjG98fxg4IPhvBNweZaxQI7gCbeUu2nHuD0eIgU7Urv+4uCorB4y9uQcn8M+KbwUNLmLwjSC+wObq/bH4XhBUfhI8FHSAenUMxAthehQkCVS9OIKRA599hoNvF0T4WQb/qFCNchcF4xsnBOHnQSdxJOCHAQj4FkFBRPx2TR8ZzONFiv4sRxAIrlBQRQbfzPCeDQJBQhggBbFBzlJIeESAbwVktIxvRehDUA4pKuBHiL4NUJAgvkEwAkG6+JYFAwRV4JsO+XDUgm8/CBQAYVqUUBAHP93hhpJMZsTEEyoSxI9xBeGnJUEiKOBbFQO9Q+MQBalwgW9ScPSCb10IlAth/ShzkSY6JfPRkv40mREMvtWgjkAiFOFbDIFmeZEwkA7/NIpvcFxxkQu+FbiRA77VMlBOaoEDGEGHrLBSFBbhGapGE1TnjwQCseIbHxcJetENvgUxEKn+TIERKb6xwBGv/ngwkDSYJnIRKrc8tMV5n+apkRq+QTHCBBD4Fki1rhZEg28GDBSNbzNgNESXHLHjmwsDGYNpEjJc5IY43QgQE6iiZiTCLr4JYaY/D2BkbUFZ+FbBQJ74tglGc/xmykYP+AYDQWBgegZVGHOEHwnH+HaOyP0S9Kg/Vgz0rz8BX5TDbxpopAp9KBB0D6ZcGGZUzhtpoANwKWQ08kDzrTpQCr4xglECv7EW4gB4PGCoPysSJMc+mH6iUFFYbwQMcOF4/x20QHzUS6ZA6R2A0Rq/2TeQczgMThASmPYjxtXybtQAjXRohF8ojgmBzW9JE+6b4Rfx8FvUBHH3gAoFyUBG5OMsQVTeG7HgWwea5+fPDV+OPTpGNqRdUALEx1EKjQ5ctEDk/aBDDRCX0ES7RoXnzl0bLzDhr2/mnM30Rnv4NsxRZTDfTxo1TZ9R9X6DTAEFFsSDgeMEeeDbHhiF7Q9/GbXszwkYqQfzLCEU6VVuI+wDmj8GxZEuLAwjqaAVR0TwG4yYwFiEcUZdDLisjiSQXwv0CJf8oQSM2MM3JODIFSqIEQFgCgizOyD1ftc+Bm4EBL55Aka8AKiNMICFGFmEWuHWgTcZro+GCrShRjTwm0nwxjfQshwQL7IDUXQFDHIoYgc58sFWhdAja/IHHgpCj98YgSMd8E0YNkKL8i3lFFSg3BYRiEQQKlEjP6CPGQZig+L4oxjDC9BAYpAvfzBsIxwIj06IEUc5ZoyO/iBdEj8ygjD6wxcbUMLW/IGAfRGkB/64wBTecANY0CcbHXmFPFiyANlNRX3oqaMX76iRHJCAOyewwEVAyR3cecQNaSADF7CQoKr8oQAFaIJHmoBMXWZEAsiM4kdCIAE6nMAfCgjDBzB0ERDMgBmFbIMHiECQgAAAOw==\') no-repeat scroll 0 0 transparent;
		text-indent: -999em;
		display: block;
		height: 34px;
		margin: 0 0 1.85em;
	}

	h2 {
		margin-top: 0;
	}

	#startInstallation {
		color: #b1905c;
		border: 1px solid #b1905c;
		font-size: 1.4em;
		padding: 0.3em;
	}

	#container {
		margin: 0 auto;
		width: 41em;
	}

	#messageContainer {
		background-color: #fbf6de;
		padding: 1em 1.5em;
	}

	.message {
		border: 1px solid;
		padding: 0.4em;
	}

	.message.success {
		background-color: #cdeaca;
		border-color: #58b548;
	}
	.message.error {
		background-color: #fbb19b;
		border-color: #dc4c42;
	}';
	$js = 'jx={getHTTPObject:function(){var A=false;if(typeof ActiveXObject!="undefined"){try{A=new ActiveXObject("Msxml2.XMLHTTP")}catch(C){try{A=new ActiveXObject("Microsoft.XMLHTTP")}catch(B){A=false}}}else{if(window.XMLHttpRequest){try{A=new XMLHttpRequest()}catch(C){A=false}}}return A},load:function(url,callback,format){var http=this.init();if(!http||!url){return }if(http.overrideMimeType){http.overrideMimeType("text/xml")}if(!format){var format="text"}format=format.toLowerCase();var now="uid="+new Date().getTime();url+=(url.indexOf("?")+1)?"&":"?";url+=now;http.open("GET",url,true);http.onreadystatechange=function(){if(http.readyState==4){if(http.status==200){var result="";if(http.responseText){result=http.responseText}if(format.charAt(0)=="j"){result=result.replace(/[\n\r]/g,"");result=eval("("+result+")")}if(callback){callback(result)}}else{if(error){error(http.status)}}}};http.send(null)},init:function(){return this.getHTTPObject()}}';
	$js .= "
	function loadStep(stepName, additionalParams) {
		if (additionalParams == undefined) additionalParams = '';
		jx.load('" . $_SERVER['SCRIPT_NAME'] . "?step=' + stepName + additionalParams,function(data){
			var response = JSON.parse(data);
			if(response.messages) {
				for (i = 0, len = response.messages.length; i < len; ++i) {
					if (i in response.messages) {
						document.getElementById('messageContainer').innerHTML = document.getElementById('messageContainer').innerHTML + '<p class=\"message ' + response.status + '\">' + response.messages[i] + '</p>';
					}
				}
			}
			if(response.nextStep) {
				loadStep(response.nextStep, response.additionalParams);
			}
		});
	};";
	echo '<html><title>TYPO3 Installer Script</title><script>' . $js . '</script><style type="text/css">' . $style . '</style><body><div id="container"><h1 class="logo">TYPO3 Installer</h1><div id="messageContainer">';
}

function printFooter() {
	$js = "document.getElementById('startInstallation').onclick = function() {loadStep('checkCurrentDirectoryForItems')};";
	echo '</div></div><script>' . $js . '</script></body></html>';
	die();
}

function printMessage($message) {
	echo '<p class="message">' . $message . '</p>';
}