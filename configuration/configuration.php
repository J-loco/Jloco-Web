<?php
/*************************************/
/*******       VARIABLE       ********/
/*************************************/
require_once __DIR__ . '/../include/autoload.php';

/** Settings from the environment (src/Config.php). Constants below are aliases kept for the legacy pages. */
function config(): StarLoco\Web\Config {
	static $config = null;
	return $config ??= StarLoco\Web\Config::fromEnvironment();
}

/** Lazy login/game connections (src/Database.php). */
function database(): StarLoco\Web\Database {
	static $database = null;
	return $database ??= new StarLoco\Web\Database(config());
}

define('TITLE', 'StarLoco');
date_default_timezone_set('Europe/Paris');

/** URL **/
	define('URL_SITE', config() -> appUrl);
	define('APP_DEBUG', config() -> debug);
	ini_set('display_errors', APP_DEBUG ? '1' : '0');

	/** Réseaux sociaux **/
	define('URL_TWITTER', '');
	define('URL_FACEBOOK', 'https://www.facebook.com/');
	define('NAME_FACEBOOK', 'StarLoco');
	define('URL_GOOGLE', '');

	/** Autres **/
	define('URL_RPG', 'http://www.rpg-paradize.com/');
	define('URL_FORUM', '');
	define('URL_BARBOK', '');
	define('URL_TEAMSPEAK', '');
	define('URL_RSS_NEWS_IPB', '');
	define('ADMIN_GUID', 1);

	//Http://www.***.**/... neccésaire dans l'URL ( compatibilité Firefox ).
	define('URL_LAUNCHER_1_29', 'http://127.0.0.1/upload/Dofus.exe');
	define('URL_INSTALLATEUR', 'http://127.0.0.1/upload/Install.exe');
	define('URL_CONFIG', 'http://127.0.0.1/upload/config.xml');

/** Serveurs (status probes in the sidebar and launcher/status.php) **/
	define('LOGIN_IP', config() -> loginServerHost);
	define('LOGIN_PORT', config() -> loginServerPort);
	define('JIVA_IP', config() -> gameServerHost);
	define('JIVA_PORT', config() -> gameServerPort);

/** Shop **/
	define('PTS_PER_VOTE', 5);
	// Only enable behind Cloudflare: otherwise anyone can forge CF-Connecting-IP.
	define('TRUST_CLOUDFLARE', config() -> trustCloudflare);
	define('DEDIPASS_PUBLIC_KEY', config() -> dedipassPublicKey);

	
/*************************************/
/*******       FUNCTION       ********/
/*************************************/

function checkState($ip, $port) {
	$fp = @fsockopen($ip, $port, $errorCode, $errorMessage, 1);
	if($fp !== false){
		fclose($fp);
		return true;
	}
	return false;
}

function convertDateToString($date) {
	$split = explode("-", $date);
	$month = "Unknow";
	
	switch((int) $split[1]) {
		case 1: $month = "Janvier"; break;
		case 2: $month = "Février"; break;
		case 3: $month = "Mars"; break;
		case 4: $month = "Avril"; break;
		case 5: $month = "Mai"; break;
		case 6: $month = "Juin"; break;
		case 7: $month = "Juillet"; break;
		case 8: $month = "Août"; break;
		case 9: $month = "Septembre"; break;
		case 10: $month = "Octobre"; break;
		case 11: $month = "Novembre"; break;
		case 12: $month = "Décembre"; break;
	}
	
	return $split[0] . " " . $month . ", " . $split[2];
}

function convertDateEnToFr($date) {
	$split = explode(" ", $date);
	$month = "Unknow";

	switch(strtoupper($split[1])) {
		case "DEC": return str_replace("Dec", "Décembre", $date);
		case "JAN":
		case "FEV":
		case "MAR":
		case "AVR":
		case "MAI":
		case "JUI":
		case "JUI":
		case "":
		case "":
		case "":
		return "";
		
	}
	return "";
}

function convertTimestampToUptime($startTime) {
	$time = round(microtime(true) * 1000) - $startTime;
	
	$day = (int) ($time / (3600 * 1000 * 24));
	$time %= 3600 * 1000 * 24;
	
	$hour = (int) ($time / (3600 * 1000));
	$time %= 3600 * 1000;
	
	$min = (int) ($time / (60 * 1000));
	$time %= 60 * 1000;
	
	$sec  = (int) ($time / 1000);
		
	return $day . "j " . $hour . "h " . $min . "m " . $sec . "s.";
}

function convertClassIdToString($id, $sexe) {
	switch($id) {
	case 1: return ($sexe == 0 ? "Féca" : "Fécatte");
	case 2: return ($sexe == 0 ? "Osanamodas" : "Osamodas");
	case 3: return ($sexe == 0 ? "Enutrof" : "Enutrof");
	case 4: return ($sexe == 0 ? "Sram" : "Sramette");
	case 5: return ($sexe == 0 ? "Xélor" : "Xélor");
	case 6: return ($sexe == 0 ? "Ecaflip" : "Ecaflip");
	case 7: return ($sexe == 0 ? "Eniripsa" : "Eniripsa");
	case 8: return ($sexe == 0 ? "Iop" : "Iopette");
	case 9: return ($sexe == 0 ? "Crâ" : "Crâtte");
	case 10: return ($sexe == 0 ? "Sadida" : "Sadida");
	case 11: return ($sexe == 0 ? "Sacrieur" : "Sacrieuse");
	case 12: return ($sexe == 0 ? "Pandawa" : "Pandawa");
	}
	return "Unknow";
}

function checkString($data) {
	if(!preg_match("/^[0-9A-zàâéèêïîöôüûùÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿ Ñ!?,.ñ_-]*$/", $data)) 
		return true;
	return false;
}

function parseDate($date) {
	$array = explode("~", $date);
	return "le " . $array[2] . "/" . $array[1] . "/" . $array[0] . " à " . $array[3] . "h" . $array[4];
}


function convertStatsToString($data) {
	$stats = explode(",", $data);
	$value = "";
	for($i = 0; $i < count($stats); $i++) {
		$array = explode("#", $stats[$i]);
    	$id = $array[0];
		$de = hexdec($array[1]);
		$a = hexdec($array[2]);
		
		$x = convertStatsId($id);
		
		$suffix = explode(";", $x);
		$signe = explode(";", $x);
		if ($x == ";")
			continue;
			
		if($a == 0)
			echo $signe[1]." $de ".$suffix[0]." <br>" ;
		else
			echo $signe[1]." $de à $a ".$suffix[0]." <br>" ;
	}
	return "";
}

function convertStatsId($id) {
	$suffix = "";
	$signe = "";
	switch($id)	{
		case '99':
		$suffix = ' Vitalité.';
		$signe = '- ';
		break;
		case '9d':
		$suffix = ' Terre.';
		$signe = '- ';
		break;
		case '9b':
		$suffix = ' Feu.';
		$signe = '- ';
		break;
		case '9a':
		$suffix = ' Air.';
		$signe = '- ';
		break;
		case '98':
		$suffix = ' Eau.';
		$signe = '- ';
		break;
		case '7d':
		$suffix = ' Vitalité.';
		$signe = '+ ';
		break;
		case '7c':
		$suffix = ' Sagesse.';
		$signe = '+ ';
		break;
		case '76':
		$suffix = ' Terre.';
		$signe = '+ ';
		break;
		case '7e':
		$suffix = ' Feu.';
		$signe = '+ ';
		break;
		case '77':
		$suffix = ' Air.';
		$signe = '+ ';
		break;
		case '7b':
		$suffix = ' Eau.';
		$signe = '+ ';
		break;
		case '6f':
		$suffix = ' Pa.';
		$signe = '+ ';
		break;
		case '65':
		$suffix = ' Pa.';
		$signe = '- ';
		break;
		case '80':
		$suffix = ' Pm.';
		$signe = '+ ';
		break;
		case '7f':
		$suffix = ' Pm.';
		$signe = '- ';
		break;
		case '75':
		$suffix = ' Po.';
		$signe = '+ ';
		break;
		case '74':
		$suffix = ' Po.';
		$signe = '- ';
		break;
		case '70':
		$suffix = ' Dommage.';
		$signe = '+ ';
		break;
		case '91':
		$suffix = ' Dommage.';
		$signe = '- ';
		break;
		case '8a':
		$suffix = ' Dommage (%).';
		$signe = '+ ';
		break;
		case 'ba':
		$suffix = ' Dommage (%).';
		$signe = '- ';
		break;
		case 'dc':
		$suffix = ' Dommage renvoyé.';
		$signe = '+ ';
		break;
		case 'b2':
		$suffix = ' Soins.';
		$signe = '+ ';
		break;
		case 'b3':
		$suffix = ' Soins.';
		$signe = '- ';
		break;
		case '73':
		$suffix = ' Coup Critique.';
		$signe = '+ ';
		break;
		case '7a':
		$suffix = ' Echec Critique.';
		$signe = '+ ';
		break;
		case 'b6':
		$suffix = ' Invocation.';
		$signe = '+ ';
		break;
		case '9e':
		$suffix = ' Pod.';
		$signe = '+ ';
		break;
		case '9f':
		$suffix = ' Pod.';
		$signe = '- ';
		break;
		case 'ae':
		$suffix = ' Initiative.';
		$signe = '+ ';
		break;
		case 'af':
		$suffix = ' Initiative.';
		$signe = '- ';
		break;
		case 'b0':
		$suffix = ' Prospection.';
		$signe = '+ ';
		break;
		case 'b1':
		$suffix = ' Prospection.';
		$signe = '- ';
		break;
		case 'e1':
		$suffix = ' Piège.';
		$signe = '+ ';
		break;
		case 'e2':
		$suffix = ' Piège (%).';
		$signe = '+ ';
		break;
		case 'a0':
		$suffix = ' Esquive perte Pa (%).';
		$signe = '+ ';
		break;
		case 'aa2':
		$suffix = ' Esquive perte Pa (%).';
		$signe = '- ';
		break;
		case 'a1':
		$suffix = ' Esquive perte Pm (%).';
		$signe = '+ ';
		break;
		case 'a3':
		$suffix = ' Esquive perte Pm (%).';
		$signe = '- ';
		break;
		case 'f4':
		$suffix = ' Résistance Neutre.';
		$signe = '+ ';
		break;
		case 'f9':
		$suffix = ' Résistance Neutre.';
		$signe = '- ';
		break;
		case 'f0':
		$suffix = ' Résistance Terre.';
		$signe = '+ ';
		break;
		case 'f5':
		$suffix = ' Résistance Terre.';
		$signe = '- ';
		break;
		case 'f3':
		$suffix = ' Résistance Feu.';
		$signe = '+ ';
		break;
		case 'f8':
		$suffix = ' Résistance Feu.';
		$signe = '- ';
		break;
		case 'f2':
		$suffix = ' Résistance Air.';
		$signe = '+ ';
		break;
		case 'f7':
		$suffix = ' Résistance Air.';
		$signe = '- ';
		break;
		case 'f1':
		$suffix = ' Résistance Eau.';
		$signe = '+ ';
		break;
		case 'f6':
		$suffix = ' Résistance Eau.';
		$signe = '- ';
		break;
		case 'd6':
		$suffix = ' Résistance Neutre (%).';
		$signe = '+ ';
		break;
		case 'db':
		$suffix = ' Résistance Neutre (%).';
		$signe = '- ';
		break;
		case 'd2':
		$suffix = ' Résistance Terre (%).';
		$signe = '+ ';
		break;
		case 'd7':
		$suffix = ' Résistance Terre (%).';
		$signe = '- ';
		break;
		case 'd5':
		$suffix = ' Résistance Feu (%).';
		$signe = '+ ';
		break;
		case 'da':
		$suffix = ' Résistance Feu (%).';
		$signe = '- ';
		break;
		case 'd4':
		$suffix = ' Résistance Air (%).';
		$signe = '+ ';
		break;
		case 'd9':
		$suffix = ' Résistance Air (%).';
		$signe = '- ';
		break;
		case 'd3':
		$suffix = ' Résistance Eau (%).';
		$signe = '+ ';
		break;
		case 'd8':
		$suffix = ' Résistance Eau (%).';
		$signe = '- ';
		break;
		case 'fe':
		$suffix = ' Résistance Neutre face aux combattants (%).';
		$signe = '+ ';
		break;
		case '103':
		$suffix = ' Résistance Neutre face aux combattants (%).';
		$signe = '- ';
		break;
		case 'fa':
		$suffix = ' Résistance Terre face aux combattants (%).';
		$signe = '+ ';
		break;
		case 'ff':
		$suffix = ' Résistance Terre face aux combattants (%).';
		$signe = '- ';
		break;
		case 'fd':
		$suffix = ' Résistance Feu face aux combattants (%).';
		$signe = '+ ';
		break;
		case '102':
		$suffix = ' Résistance Feu face aux combattants (%).';
		$signe = '- ';
		break;
		case 'fb':
		$suffix = ' Résistance Eau face aux combattants (%).';
		$signe = '+ ';
		break;
		case '100':
		$suffix = ' Résistance Eau face aux combattants (%).';
		$signe = '- ';
		break;
		case 'fc':
		$suffix = ' Résistance Air face aux combattants (%).';
		$signe = '+ ';
		break;
		case '101':
		$suffix = ' Résistance Air face aux combattants (%).';
		$signe = '- ';
		break;
		case '108':
		$suffix = ' Résistance Neutre face aux combattants.';
		$signe = '+ ';
		break;
		case '104':
		$suffix = ' Résistance Terre face aux combattants.';
		$signe = '+ ';
		break;
		case '107':
		$suffix = ' Résistance Feu face aux combattants.';
		$signe = '+ ';
		break;
		case '105':
		$suffix = ' Résistance Eau face aux combattants.';
		$signe = '+ ';
		break;
		case '106':
		$suffix = ' Résistance Air face aux combattants.';
		$signe = '+ ';
		break;
		case '8b':
        $suffix = ' Energie.';
        $signe = '+';
        break;
		default:
		$suffix = '';
		$signe = '';
		break;
	}
	return $suffix.";".$signe;
}
?>
