<?php
// ensemble01/services/twigAetools.php

namespace ensemble01\services;

use Symfony\Component\DependencyInjection\ContainerInterface;

class twigAetools extends \Twig_Extension {

	private $decal;
	private $html;
	private $tab;
	private $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	public function getFunctions() {
		return array(
			'phraseCut'				=> new \Twig_Function_Method($this, 'phraseCut'),
			'colonizeWords'			=> new \Twig_Function_Method($this, 'colonizeWords'),
			'colonizeWordsWithP'	=> new \Twig_Function_Method($this, 'colonizeWordsWithP'),
			'cleanSpaces'			=> new \Twig_Function_Method($this, 'cleanSpaces'),
			'adminDataType'			=> new \Twig_Function_Method($this, 'adminDataType'),
			'intervalDateFR'		=> new \Twig_Function_Method($this, 'intervalDateFR'),
			'dateFR'				=> new \Twig_Function_Method($this, 'dateFR'),
			'minUCfirst'			=> new \Twig_Function_Method($this, 'minUCfirst'),
			'UCfirst'				=> new \Twig_Function_Method($this, 'UCfirst'),
			'magnifyText'			=> new \Twig_Function_Method($this, 'magnifyText'),
			'addZeros'				=> new \Twig_Function_Method($this, 'addZeros'),
			'dureeHM'				=> new \Twig_Function_Method($this, 'dureeHM'),
			'arrayprint'			=> new \Twig_Function_Method($this, 'arrayprint'),
			'slug'					=> new \Twig_Function_Method($this, 'slug'),
			'siteNFormat'			=> new \Twig_Function_Method($this, 'siteNFormat'),
			'pathTree'				=> new \Twig_Function_Method($this, 'pathTree'),
			'simpleURL'				=> new \Twig_Function_Method($this, 'simpleURL'),
			'Url_encode'			=> new \Twig_Function_Method($this, 'Url_encode'),
			'googleMapURL'			=> new \Twig_Function_Method($this, 'googleMapURL'),
			'serializeT'			=> new \Twig_Function_Method($this, 'serializeT'),
			'unserializeT'			=> new \Twig_Function_Method($this, 'unserializeT'),
			'paramsByUrl'			=> new \Twig_Function_Method($this, 'paramsByUrl'),
			'implode'				=> new \Twig_Function_Method($this, 'implode'),
			'plur'					=> new \Twig_Function_Method($this, 'pluriel'),
			'valueOfObject'			=> new \Twig_Function_Method($this, 'valueOfObject'),
			'imgVolume'				=> new \Twig_Function_Method($this, 'imgVolume'),
			'annee'					=> new \Twig_Function_Method($this, 'annee'),
			'URIperform'			=> new \Twig_Function_Method($this, 'URIperform'),
			'fillOfChars'			=> new \Twig_Function_Method($this, 'fillOfChars'),
			'idify'					=> new \Twig_Function_Method($this, 'idify'),
			'zerosDevant'			=> new \Twig_Function_Method($this, 'zerosDevant'),
			// tests types
			'is_string'				=> new \Twig_Function_Method($this, 'is_string'),
			'is_object'				=> new \Twig_Function_Method($this, 'is_object'),
			// spécial GEODIAG WEB 
			'transFMdate'			=> new \Twig_Function_Method($this, 'transFMdate'),
			'CSSclass'				=> new \Twig_Function_Method($this, 'CSSclass'),
			'base64_decode'			=> new \Twig_Function_Method($this, 'base64_decode'),
			'image_base64'			=> new \Twig_Function_Method($this, 'image_base64'),
			'separdates'			=> new \Twig_Function_Method($this, 'separdates'),
			'lastdate'				=> new \Twig_Function_Method($this, 'lastdate'),
			'docsAnterieurs'		=> new \Twig_Function_Method($this, 'docsAnterieurs'),
			'partie_nom_rapport'	=> new \Twig_Function_Method($this, 'partie_nom_rapport'),
			'getDateConstruction'	=> new \Twig_Function_Method($this, 'getDateConstruction'),
			'getRev'				=> new \Twig_Function_Method($this, 'getRev'),
			'nomstechs'				=> new \Twig_Function_Method($this, 'nomstechs'),
			'getTechs'				=> new \Twig_Function_Method($this, 'getTechs'),
			'mediaIMG'				=> new \Twig_Function_Method($this, 'mediaIMG'),
			'printIfMult'			=> new \Twig_Function_Method($this, 'printIfMult'),
			'neant'					=> new \Twig_Function_Method($this, 'neant'),
			'testBegin'				=> new \Twig_Function_Method($this, 'testBegin'),
			'testEnd'				=> new \Twig_Function_Method($this, 'testEnd'),
			'rapport_detail_liste_materiau'		=> new \Twig_Function_Method($this, 'rapport_detail_liste_materiau'),
			);
	}

	public function getName() {
		return 'twigAetools';
	}

	/**
	 * Renvoie le texte $t réduit à $n lettres / Sans couper les mots
	 * si $tre = true (par défaut), ajoute "..." à la suite du texte
	 * Pour autoriser le coupage de mots, mettre $_Wordcut à "true"
	 * @param string
	 * @param intger
	 * @param boolean
	 * @param boolean
	 * @return string
	 */
	public function phraseCut($t, $n, $tre=true, $wordcut=false) {
		$t = strip_tags($t);
		$prohib=array(' ',',',';','.');
		if(strlen($t)>=$n) {
			$r1=substr($t, 0, $n);
			if(!$wordcut) while(substr($r1, -1)!=" " && strlen($r1)>0) $r1=substr($r1, 0, -1);
			if(strlen($r1)<1) $r1=substr($t, 0, $n);
			if(in_array(substr($r1, -1), $prohib)) $r1=substr($r1, 0, -1);
			if($tre) $r1=trim($r1)."…";
		} else $r1=$t;
		return trim($r1);
	}

	/**
	 * Renvoie à la ligne pour optimiser un texte sur une colonne. 
	 * Pratique dans les tableaux
	 * @param string $t - texte
	 * @param intger $n - nombre de lettres maxi avant retour à la ligne
	 * @return string
	 */
	public function colonizeWords($t, $n, $separ = "br") {
		if($n > 5) $n = $n - 4;
		$t = $this->cleanSpaces($t);
		$changes = array(" ", "-");
		$cpt = 1;
		$line = 0;
		$txr = array();
		$txr[$line] = "";
		// Génération des lignes de texte en tableau
		for ($i=0; $i < strlen($t); $i++) {
			$char = substr($t, $i, 1);
			if($cpt > $n && in_array($char, $changes)) {
				if($char == " ") $char = "";
				$cpt = 1;
				$txr[$line] .= $char;
				$line++;
				$txr[$line] = "";
			} else {
				$cpt++;
				$txr[$line] .= $char;
			}
		}
		// ajout des séparateurs
		switch (strtolower($separ)) {
			case 'p':
			case 'span':
			case 'div':
			case 'li':
				$balise = strtolower($separ);
				$result = '<'.$balise.'>'.implode('</'.$balise.'><'.$balise.'>', $txr).'</'.$balise.'>';
				break;
			default: // <br> par défaut
				$result = implode('<br>', $txr);
				break;
		}
		return $result;
	}

	/**
	 * Crée des lignes <p> pour optimiser un texte sur une colonne. 
	 * Pratique dans les tableaux
	 * @param string $t - texte
	 * @param intger $n - nombre de lettres maxi avant retour à la ligne
	 * @return string
	 */
	public function colonizeWordsWithP($t, $n) {
		return $this->colonizeWords($t, $n, 'p');
	}

	/**
	 * Supprime les espaces doubles (ou plus) d'une phrase
	 * @param string $t - texte
	 * @param intger $n - nombre d'espaces à supprimer (à partir de 2, par défaut)
	 * @return string
	 */
	public function cleanSpaces($t, $n = 2) {
		return preg_replace('#\s{'.$n.',}#', " ", $t);
	}


	/**
	 * Renvoie la donnée sous forme de données admin
	 * "true" ou "false" pour un booléen, par exemple
	 * @param data
	 * @return string
	 */
	public function adminDataType($data, $miseEnForme = true, $developpe = false) {
		if(is_bool($data)) {
			if($data === true) $miseEnForme?$r = "<span style='color:green;'>#true</span>":$r = "#true";
				else $miseEnForme?$r = "<span style='color:red;'>#false</span>":$r = "#false";
			return $r;
		} else if(is_array($data)) {
			if($developpe === true) {
				$txt = serialize($data);
			} else {
				$txt = count($data);
			}
			$miseEnForme?$r = "<span style='color:blue;font-style:italic;'>(#array ".$txt.")</span>":$r = "(#array ".$txt.")";
			return $r;
		} else if(is_object($data)) {
			$miseEnForme?$r = "<span style='color:blue;font-style:italic;'>(#object)</span>":$r = "(#object)";
			return $r;
		} else {
			return $data;
		}
	}

	/**
	 * developpeArray
	 * 
	 * Transforme un array() en informations texte
	 * @param data
	 * @return string
	 */
	public function developpeArray($data) {
		if(is_array($data)) {
			$r = $this->developpeArray_recursive($data);
		} else $r = $data;
		return $r;
	}
	public function developpeArray_recursive($data) {
		$sep = "";
		if(is_array($data) && count($data) > 0) foreach($data as $nom => $vals) {
			if(is_array($vals)) $r = $sep.$nom." = ".$this->developpeArray_recursive($vals);
				else $r = $sep.$nom." = ".$vals;
			$sep = " | ";
		} else {
			$r = $data;
		}
		return $r;
	}

	/**
	 * developpeObject
	 * 
	 * Transforme un object() en informations texte
	 * @param data
	 * @return string
	 */
	public function developpeObject($data) {
		return print_r($data);
	}

	public function intervalDateFR($datedebut, $datefin = null, $short = false) {
		if(($datefin === null) && (is_object($datedebut))) {
			$txt = "le ".$this->dateFR($datedebut, $short);
		} else if((is_object($datedebut)) && (is_object($datefin))) {
			$dd = $this->dateFR($datedebut, $short);
			$df = $this->dateFR($datefin, $short);
			// supprime l'année sur date de début si identique à celle de la date de fin
			if(substr($dd, -4) == substr($df, -4)) $dd = substr($dd, 0, strlen($dd) - 5);
			$txt = "du ".$dd." au ".$df;
		} else $txt = "";
		return $txt;
	}

	public function dateFR($date, $short = false) {
		$sup = array(1);
		if($short === false) {
			$jours = array(
				"Sunday" 	=> "dimanche",
				"Monday" 	=> "lundi",
				"Tuesday" 	=> "mardi",
				"Wednesday" => "mercredi",
				"Thursday" 	=> "jeudi",
				"Friday" 	=> "vendredi",
				"Saturday" 	=> "samedi",
				);
			$mois = array(
				"January" 	=> "janvier",
				"February" 	=> "février",
				"March" 	=> "mars",
				"April" 	=> "avril",
				"May" 		=> "mai",
				"June" 		=> "juin",
				"July" 		=> "juillet",
				"August" 	=> "août",
				"September" => "septembre",
				"October" 	=> "octobre",
				"November" 	=> "novembre",
				"December" 	=> "décembre",
				);
		} else {
			$jours = array(
				"Sunday" 	=> "dim",
				"Monday" 	=> "lun",
				"Tuesday" 	=> "mar",
				"Wednesday" => "mer",
				"Thursday" 	=> "jeu",
				"Friday" 	=> "ven",
				"Saturday" 	=> "sam",
				);
			$mois = array(
				"January" 	=> "jan",
				"February" 	=> "fév",
				"March" 	=> "mar",
				"April" 	=> "avr",
				"May" 		=> "mai",
				"June" 		=> "jun",
				"July" 		=> "jul",
				"August" 	=> "aou",
				"September" => "sep",
				"October" 	=> "oct",
				"November" 	=> "nov",
				"December" 	=> "déc",
				);
		}
		$jj = $jours[$date->format('l')];
		$j = $date->format('j');
		if(in_array(intval($j), $sup)) $j .= "<sup>er</sup>";
		$m = $mois[$date->format('F')];
		$a = $date->format('Y');
		return $jj." ".$j." ".$m." ".$a;
	}

	/**
	 * minUCfirst
	 * 
	 * met la chaîne en minuscules et remet les premières en cap
	 * @param string
	 * @return string
	 */
	public function minUCfirst($t) {
		return (ucfirst(strtolower($t)));
	}

	/**
	 * UCfirst
	 * 
	 * met la première lettre en cap
	 * @param string
	 * @return string
	 */
	public function UCfirst($t) {
		return ucfirst($t);
	}

	/**
	 * Remplace les espaces après les mots courts par des espaces insécables pour une meilleure gestion des retours à la ligne
	 * @param string
	 * @return string
	 */
	public function magnifyText($t) {
		// supprime les espaces inutiles
		$t = $this->cleanSpaces($t);
		$search = array(
			" et ",
			" ou ",
			" où ",
			" du ",
			" sur ",
			" les ",
			" au ",
			" un ",
			" une ",
			" si ",
			" la ",
			" le ",
			" de ",
			" des ",
			" à ",
			" a ",
			" :",
			" ;",
			" ?",
			" !",
			);
		$replace = array(
			" et&nbsp;",
			" ou&nbsp;",
			" où&nbsp;",
			" du&nbsp;",
			" sur&nbsp;",
			" les&nbsp;",
			" au&nbsp;",
			" un&nbsp;",
			" une&nbsp;",
			" si&nbsp;",
			" la&nbsp;",
			" le&nbsp;",
			" de&nbsp;",
			" des&nbsp;",
			" à&nbsp;",
			" a&nbsp;",
			"&nbsp;:",
			"&nbsp;;",
			"&nbsp;?",
			"&nbsp;!",
			);
		// PASSE 1
		$t = str_replace($search, $replace, $t);

		$search = array(
			"&nbsp;et ",
			"&nbsp;ou ",
			"&nbsp;où ",
			"&nbsp;du ",
			"&nbsp;sur ",
			"&nbsp;les ",
			"&nbsp;au ",
			"&nbsp;un ",
			"&nbsp;une ",
			"&nbsp;si ",
			"&nbsp;la ",
			"&nbsp;le ",
			"&nbsp;de ",
			"&nbsp;des ",
			"&nbsp;à ",
			"&nbsp;a ",
			);
		$replace = array(
			"&nbsp;et&nbsp;",
			"&nbsp;ou&nbsp;",
			"&nbsp;où&nbsp;",
			"&nbsp;du&nbsp;",
			"&nbsp;sur&nbsp;",
			"&nbsp;les&nbsp;",
			"&nbsp;au&nbsp;",
			"&nbsp;un&nbsp;",
			"&nbsp;une&nbsp;",
			"&nbsp;si&nbsp;",
			"&nbsp;la&nbsp;",
			"&nbsp;le&nbsp;",
			"&nbsp;de&nbsp;",
			"&nbsp;des&nbsp;",
			"&nbsp;à&nbsp;",
			"&nbsp;a&nbsp;",
			);
		// PASSE 2
		$t = str_replace($search, $replace, $t);

		return $t;
	}

	/**
	 * addZeros
	 * 
	 * Renvoie le nombre $chiffre avec des zéros devant pour faire une longueur de $n chiffres
	 * @param string
	 * @return string
	 */
	public function addZeros($chiffre, $n) {
		$s = $chiffre."";
		while(strlen($s) < $n) {
			$s = "0".$s;
		}
		return $s;
	}

	/**
	 * Renvoie un texte en heures pour une durée $duree en minutes
	 * @param int
	 * @return string
	 */
	public function dureeHM($duree) {
		$duree = intval($duree);
		$t = "";
		if($duree < 2) $t = $duree." minute";
		if($duree < 60 && $t === "") $t = $duree." minutes";
		if($duree > 59 && $t === "") {
			$h = floor($duree / 60);
			$m = fmod($duree, 60);
			$mt = " minute";
			if($h > 1) $s = "s"; else $s = "";
			if($h > 0) {
				$t = $h." heure".$s;
				$esp = " ";
				$mt = "";
			} else {
				$esp = "";
			}
			if($m > 1 && $mt !== "") $mt .= "s";
			if($m > 0) $t .= $esp.$m.$mt;
		}
		return $t;
	}

	/**
	 * Renvoie (array) les paramètres passés dans $def (string)
	 * Séparer les paramètres par un "&"
	 * Par ex. : "article=5&option=ok"
	 * si ça n'est pas une requête GET (sans les "=" et "&"), renvoie la valeur tout simplement
	 * Si aucun paramètre, renvoie null
	 * 
	 * @param string $def
	 */
	public function ParamStrAnalyse($def) {
		// $def = urldecode($def);
		if(is_string($def)) {
			// supprime le "?" s'il existe
			if(substr($def,0,1) == "?") $def = substr($def,1);
			$str = explode('&', $def);
			if(count($str) > 1) {
				$result = array();
				foreach ($str as $value) {
					$exp = explode('=', $value);
					if(isset($exp[1])) $result[$exp[0]] = $exp[1];
					else $result[] = $exp[0];
				}
			} else {
				$result = $def;
			}
			return $result;
		} else return null;
	}

	/**
	 * Renvoie le prix au format pour le site
	 *
	 * @param $number = prix
	 * @param $money = ajoute "€HT" si true (null par défaut) / ou on peut préciser un texte spécifique "$", etc.
	 */
	public function siteNFormat($number, $money = null) {
		if($money === true) {
			$money = "<sup> €HT</sup>";
		} else if(!is_string($money)) $money = null;
		return number_format($number, 2, ',', '').$money;
	}


	/**
	 * pathTree
	 *
	 */
	public function pathTree($items) {
		$r = array();
		foreach ($items as $item) {
			$r[] = $item->getSlug();
		}
		return $r;
	}

	/**
	 * Renvoie un slug du titre $title
	 *
	 * @param string $title
	 */
	public function slug($title, $d = 0) {
		if($id < 1) $id=""; else $id = "-".intval($id);
		if(is_string($title)) {
			$maxlen = 42;  //Modifier la taille max du slug ici
			$slug = strtolower($title);
			$slug = preg_replace("/[^a-z0-9s-]/", "", $slug);
			$slug = trim(preg_replace("/[s-]+/", " ", $slug));
			$slug = preg_replace("/s/", "-", $slug);
			$slug .= $id;
		} else return false;
		return $slug;
	}

	/**
	 * simpleURL
	 * Renvoie l'URL simplifiée : sans http:// ou https://
	 *
	 * @param string $URL
	 */
	public function simpleURL($URL) {
		return str_replace(array("http://", "https://"), "", $URL);
	}
	/**
	 * Url_encode
	 * encode l'URL pour envoi GET
	 *
	 * @param string $URL
	 */
	public function Url_encode($URL) {
		return urlencode($URL);
	}

	/**
	 * googleMapURL
	 * Renvoie l'adresse formatée pour google maps
	 *
	 * @param string
	 */
	public function googleMapURL($adresse) {
		return str_replace(" ", "+", $adresse);
	}

	/**
	 * serializeT
	 * Renvoie la chaîne unserialisée (PHP : serialize())
	 *
	 * @param string $data
	 */
	public function serializeT($data) {
		return serialize($data);
	}

	/**
	 * unserializeT
	 * Renvoie la chaîne unserialisée (PHP : unserialize())
	 *
	 * @param string $data
	 */
	public function unserializeT($data) {
		return unserialize($data);
	}

	/**
	 * paramsByUrl
	 * Renvoie du tableau fourni une chaîne comptatible URL pour passer en paramètre
	 *
	 * @param array $data
	 * @return string
	 */
	public function paramsByUrl($data) {
		$r = array();
		foreach($data as $nom => $val) $r[] = $nom."=".$val;
		$result = implode("&", $r);
		return "?".$result;
	}

	/**
	 * implode
	 * Renvoie du tableau fourni une chaîne comptatible URL pour passer en paramètre
	 *
	 * @param string/array $lk
	 * @param array $data
	 * @return string
	 */
	public function implode($lk, $data = null) {
		return implode($lk, $data);
	}

	/**
	 * pluriel
	 * Renvoie un "s" si count($elem) > 1
	 * on peut remplacer le "s" par "x" ou autre
	 * @param $elem
	 * @param $s
	 * @return string
	 */
	public function pluriel($elem, $s = "s") {
		$r = "";
		if(count($elem) > 1) $r = $s;
		return $r;
	}

	/**
	 * valueOfObject
	 * Renvoie la valeur de l'attribut "private" d'un objet
	 * ATTENTION : la classe doit contenir le getter correspondant !!
	 * @param $obj
	 * @param $nom
	 * @return une valeur
	 */
	public function valueOfObject($obj, $nom) {
		$methode = "get".ucfirst($nom);
		if(method_exists($obj, $methode)) return $obj->$methode();
			else return null;
	}

	/**
	 * imgVolume
	 * Renvoie le texte pour la largeur d'une image selon un volume donnée $vol
	 * ($vol correspond au nombre de pixels voulus / 1000 : soit $vol = 10 soit 10000 pixels)
	 * Possibilité de fixer une largeur et hauteur maximales
	 * @param $img
	 * @param $vol
	 * @param $xmax
	 * @param $ymax
	 * @return une valeur
	 */
	public function imgVolume($img, $vol = 10, $xmax = null, $ymax = null) {
		$vol = $vol * 1000;
		$x = $finalX = $img->getTailleX(); // 100  -  
		$y = $finalY = $img->getTailleY(); // 200  -  
		$volume = $x * $y; // 20 000
		$ratio = $x / $y; // 0.5
		if(($vol > 0) && ($volume > $vol)) {
			$ratio_vol = $vol / $volume;
			// $finalX = $vol 
		}
		if($xmax !== null && $finalX > $xmax) {
			$finalX = $xmax;
			$finalY = $xmax / $ratio;
		}
		if($ymax !== null && $finalY > $ymax) {
			$finalX = $ymax * $ratio;
			$finalY = $ymax;
		}
		return "width:".round($finalX)."px;";
	}

	/**
	 * annee
	 * Renvoie l'année en cours
	 * @return string
	 */
	public function annee() {
		$date = new \Datetime();
		return $date->format("Y");
	}

	/**
	 *
	 */
	public function URIperform($t) {
		$search = array(
			"###ROOT###",
			);
		$replace = array(
			$this->container->get("request")->getBasePath(),
			);
		$t = str_replace($search, $replace, $t);
		return $t;
	}

	/**
	 * Remplit un texte avec des espaces (ou $char) pour obtenir une chaîne de la longueur $n
	 * @param $string - chaîne de caractères
	 * @param $n - nombre de caractères voulus au total
	 * @param $char - caractère de remplissage (espace, par défaut)
	 * @param $cut - 
	 * @return string
	 */
	public function fillOfChars($string, $n, $char = " ", $cut = true) {
		if(strlen($string) !== $n) {
			// mot de taille différente de $n
			if(strlen($string) > $n) {
				// mot plus long
				$string = substr($string, 0, $n-1)."…";
			} else {
				// mot plus court
				while(strlen($string) < $n) {$string .= $char;}
				// recoupe si trop long finalement
				// en effet, on peut mettre plusieurs caractères comme $char de remplissage ! ;-)
				if(strlen($string) > $n) {
					// mot plus long
					$string = substr($string, 0, $n);
				}
			}
		}
		return $string;
	}

	/**
	 * Transforme le texte en élément utilisable pour une classe ou un id. 
	 * sans espace ou caractères conflictuels
	 * @param string $text
	 * @return string
	 */
	public function idify($text) {
		$trans = array(
			" " => '_',
			"-" => '_',
			"%" => '',
			"#" => '',
			"*" => '',
			"&" => '',
			);
		return strtr($text, $trans);
	}


	public function zerosDevant($t, $long = 2) {
		$l = strlen($t."");
		while(strlen($t."") < $long) {
			$t = "0".$t;
		}
		return $t;
	}

	public function is_string($elem) {
		return is_string($elem);
	}

	public function is_object($elem) {
		return is_object($elem);
	}


	/**
	 * Transforme le texte date en provenance de FM 
	 * mm/dd/YY -> dd/mm/YY
	 * @param string $date
	 * @return string
	 */
	public function transFMdate($date) {
		$d = explode('/', $date);
		return $d[1].'/'.$d[0].'/'.$d[2];
	}

	/**
	 * wrap un texte html avec une classe CSS
	 * @param string $texte
	 * @param string $classe
	 * @param string $balise ('span' par défaut)
	 * @return string
	 */
	public function CSSclass($texte, $classe, $balise = 'span') {
		return '<'.$balise.' class="'.$classe.'">'.$texte.'</'.$balise.'>';
	}

	/**
	 * 
	 */
	public function base64_decode($text) {
		return base64_decode($text);
	}

	/**
	 * Renvoie une balise img de $largeur
	 * @param string $text - code de l'image (base64)
	 * @param mixed $classe - nom de(s) classe(s) -> string ou array de string
	 * @param string $format - format d'image (png, jpg, etc.)
	 * @param string $largeur - largeur de l'image (préciser l'unité ! px, %, etc.)
	 * @return string
	 */
	public function image_base64($text, $classe = null, $format = 'png', $largeur = null, $hauteur = null) {
		if(!in_array($format, array('png', 'jpg', 'gif'))) $format = 'png';
		if(strlen($text."") < 1) return ""; // "<p style='font-style:italic;color:#999;'>Image manquante</p>";
		if(is_array($classe)) $classe = implode(" ", $classe);
		if(is_string($classe)) $classe = " class='".$classe."'";
		if(is_string($largeur)) $largeur = "width:".$largeur.";";
		if(is_string($hauteur)) $hauteur = "height:".$hauteur.";";
		$style = "";
		if($hauteur !== null || $largeur !== null) $style = " style='".$largeur.$hauteur."'";
		return "<img src='data:image/".$format.";base64,".$text."'".$classe.$style." />";
	}

	/**
	 * Transforme le texte dates en provenance de FM (séparées par des pipe)
	 * la dernière date est en gras
	 * @param string $dates
	 * @return string
	 */
	public function separdates($dates, $boldlastdate = true) {
		$dates = explode('|', $dates);
		if($boldlastdate === true) {
			end($dates);
			$key = key($dates);
			$dates[$key] = '<span style="font-weight:bold;">'.$dates[$key].'</span>';
		}
		$dates = implode(' - ', $dates);
		return $dates;
	}

	/**
	 * Transforme le texte dates en provenance de FM (séparées par des pipe)
	 * la dernière date est en gras
	 * @param string $dates
	 * @return string
	 */
	public function lastdate($dates) {
		$dates = explode('|', $dates);
		return end($dates);
	}

	/**
	 * Décompose une chaîne en array
	 * @param string $data
	 * @return array
	 */
	public function docsAnterieurs($data) {
		$lignes = explode('|*|', $data);
		foreach ($lignes as $key => $value) {
			$cols[$key] = explode('|', $value);
			$cols[$key][0] = substr($cols[$key][0], 2);
			foreach ($cols[$key] as $key2 => $value2) {
				if(trim($cols[$key][$key2]."") == "") $cols[$key][$key2] = "-";
			}
		}
		unset($data);
		unset($lignes);
		return $cols;
	}

	public function getDateConstruction($txt) {
		$date = '&lt;= 1997';
		$lignes = explode('|*|', $txt);
		foreach ($lignes as $key => $value) {
			if(substr($value, 0, 1) == '5') {
				$xp = explode('|', $value);
				$date = $xp[2];
			}
		}
		return $date;
	}

	/**
	 * nom court de la référence du rapport
	 * @param string $data
	 * @return array
	 */
	public function partie_nom_rapport($txt) {
		$ref = explode('-', $txt, 3);
		if(count($ref) > 1) return $ref[0].'-'.$ref[1];
		else return $txt;
	}

	/**
	 * renvoie la révision en fonction de la version
	 * @param string $rev
	 * @return string
	 */
	public function getRev($ver) {
		$rev = " rev.";
		$ver = intval($ver);
		if($ver > 1) {
			$ver = $ver - 1;
			if($ver < 10) $add = $rev."0";
				else $add = $rev."";
			return $add.$ver;
		} else return "";
	}


	/**
	 * Renvoie un tableau de l'élément fourni avec séparateur | (1 niveau)
	 * @param string $text
	 * @return array
	 */
	public function FMexplode00($text) {
		return explode('|', $text);
	}

	/**
	 * Renvoie un tableau de l'élément fourni avec séparateurs |*| et | (2 niveaux)
	 * @param string $text
	 * @return array
	 */
	public function FMexplode01($text) {
		$techs = explode('|*|', $text);
		$tech = array();
		foreach ($techs as $key => $item) {
			$tech[$key] = $this->FMexplode00($item);
		}
		return $tech;
	}

	/**
	 * renvoie le texte avec noms et prénoms des techniciens
	 * @param string $text
	 * @return string
	 */
	public function getTechs($text, $id_exclude = array()) {
		if(is_string($id_exclude)) $id_exclude = array($id_exclude);
		$tech = $this->FMexplode01($text);
		// suppression des doublons et de $id_exclude
		$finalTech = array();
		foreach ($tech as $key => $item) {
			if(!in_array($item[0], $id_exclude)) {
				$finalTech[$item[0]] = $item;
			}
		}
		return $finalTech;
	}

	/**
	 * renvoie le texte avec noms et prénoms des techniciens
	 * @param string $text
	 * @return string
	 */
	public function nomstechs($text) {
		$tech = $this->getTechs($text);
		$finalTech = array();
		foreach ($tech as $key => $item) {
			$espace = " ";
			if(strlen(trim($item[2])) < 1) $espace = "";
			$finalTech[$key] = $item[2].$espace.$item[1];
		}
		return implode(" - ", $finalTech);
	}

	/**
	 * renvoie l'image <img> d'un technicien (selon réf. "mult_")
	 * @param string $mult
	 * @return string
	 */
	public function mediaIMG($mult, $hi = true, $classe = null, $format = 'png', $largeur = null, $hauteur = null) {
		if($hi === true) $tailleReso = 'conteneur_base64';
			else $tailleReso = 'conteneur_miniature_base64';
		$no = "Image non trouvée";
		$user = $this->container->get('security.context')->getToken()->getUser();
		$_fm = $this->container->get('ensemble01services.geodiag');
		$_fm->log_user($user, null, true);
		$media = $_fm->getMedia($mult);
		if(is_string($media)) return $no."<br>(".$media.")";
		if(count($media) > 0) {
			reset($media);
			$media = current($media);
			return $this->image_base64($media->getField($tailleReso), $classe, $format, $largeur, $hauteur);
			// return "<p>IMAGE CERTIF ".$media->getField('conteneur_base64')." - ".$user->getUsername()."</p>";
		} else {
			return $no;
		}
	}

	/**
	 * Renvoie une image si $tx commence par "Mult" / sinon renvoie le même texte $tx
	 * @param string $tx
	 * @return string
	 */
	public function printIfMult($tx, $hi = true, $classe = null, $format = 'png', $largeur = null, $hauteur = null, $coupe = 6, $separ = "br") {
		if(preg_match('#^(Mult-)#', $tx)) {
			return $this->mediaIMG($tx, $hi, $classe, $format, $largeur, $hauteur);
		} else return $this->colonizeWords($tx, $coupe, $separ);
	}

	/**
	 * renvoie "Néant" si $tx est une chaîne vide
	 * @param string $tx
	 * @return string
	 */
	public function neant($tx, $neant = 'Néant') {
		if(strlen(trim($tx)) < 1) return $neant;
			else return trim($tx);
	}

	public function testBegin($t, $begin, $caseSens = true) {
		if($caseSens === true) $i = "";else $i = "i";
		$a = preg_match('#^('.$begin.')#'.$i, $t);
		return ($a === 1 ? true : false);
	}

	public function testEnd($t, $end, $caseSens = true) {
		if($caseSens === true) $i = "";else $i = "i";
		$a = preg_match('#('.$end.')$#'.$i, $t);
		return ($a === 1 ? true : false);
	}

	public function rapport_detail_liste_materiau($rapport) {
		return $this->FMexplode01($rapport);
	}

}

?>
