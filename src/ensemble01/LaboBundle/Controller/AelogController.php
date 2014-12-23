<?php

namespace ensemble01\LaboBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use ensemble01\services\aetools\aeReponse;

class AelogController extends Controller {


	//////////////////////////
	// PAGES
	//////////////////////////

	/**
	 * statistiquesAction
	 * 
	 */
	public function statistiquesAction($stat = 'general') {
		$data = array();
		$data["semaineEnCours"] = $this->getSemaineEnCours();
		$data["id"] = 0;
		$data['listtypes'] = $this->listOptions();
		// vérification 
		if(in_array(strtolower($stat), $data['listtypes'])) {
			$data['typedata'] = strtolower($stat);
		} else {
			reset($data['listtypes']);
			$data["typedata"] = current($data['listtypes']);
		}
		// Liste des pages disponibles
		$data["pages"] = $this->get("ensemble01.pageweb")->getRepo()->findAll();
		// 
		$statistiques = $this->get("ensemble01.aelog");
		$data["statistiques"] = $statistiques->findByType($data['typedata']);
		switch($data['typedata']) {
			case "articles":
				$data["listeArticles"] = $this->get("ensemble01.article")->getRepo()->aeFindAll();
				return $this->render('ensemble01LaboBundle:pages:statistiquesArticles.html.twig', $data);
				break;
			default:
				return $this->render('ensemble01LaboBundle:pages:statistiques.html.twig', $data);
				break;
		}
	}

	/**
	 * statistiquesIpAction
	 * 
	 */
	public function statistiquesIpAction($ip, $dateDebut = null, $dateFin = null) {
		$data = array();
		// contrôle dates et transformation en objet Datetime
		$data = $this->datesFit($dateDebut, $dateFin);
		$data["semaineEnCours"] = $this->getSemaineEnCours();
		$data['listtypes'] = $this->listOptions();
		$data["ip"] = $ip;
		$statistiques = $this->get("ensemble01.aelog");
		$data["statistiques"] = $statistiques->findByIp($ip, $dateDebut, $dateFin);
		return $this->render('ensemble01LaboBundle:pages:statistiquesIp.html.twig', $data);
	}

	/**
	 * statistiquesPageAction
	 *
	 */
	public function statistiquesPageAction($pageSlug, $dateDebut = null, $dateFin = null) {
		$data = array();
		// contrôle dates et transformation en objet Datetime
		$data = $this->datesFit($dateDebut, $dateFin);
		$data["semaineEnCours"] = $this->getSemaineEnCours();
		$data['listtypes'] = $this->listOptions();
		// Liste des pages disponibles
		$data["pages"] = $this->get("ensemble01.pageweb")->getRepo()->findAll();
		$data["pageSlug"] = $pageSlug;
		$data["pageNom"] = $pageSlug;
		$p = false;
		foreach($data["pages"] as $pageStat) if($pageSlug === $pageStat->getSlug()) { $data["pageStat"] = $pageStat; }
		if(isset($data["pageStat"])) {
			$data["error"] = false;
			$data["pageNom"] = $data["pageStat"]->getNom();
		} else {
			$data["error"] = "Cette page n'existe pas.";
		}
		return $this->render('ensemble01LaboBundle:pages:statistiquesPages.html.twig', $data);
	}

	/**
	 * statArticlesAction
	 *
	 */
	public function statArticlesAction($articleSlug, $dateDebut = null, $dateFin = null) {
		$data = array();
		// contrôle dates et transformation en objet Datetime
		$data = $this->datesFit($dateDebut, $dateFin);
		$data["semaineEnCours"] = $this->getSemaineEnCours();
		$data['listtypes'] = $this->listOptions();
		$data["listeArticles"] = $this->get("ensemble01.article")->getRepo()->aeFindAll();
		$data["typedata"] = $data['listtypes'][1];
		$statistiques = $this->get("ensemble01.aelog");
		$a = $this->get("ensemble01.article")->getRepo()->findBySlug($articleSlug);
		$data["article"] = $a[0];
		$data["id"] = $data["article"]->getId();
		$s = $statistiques->findArticle($articleSlug, $dateDebut, $dateFin);
		$data["articleStat"] = $statistiques->trieByDate($s, "semaine");
		foreach($data["articleStat"]["data"] as $year => $st1) {
			foreach($st1 as $sem => $st2) {
				$mem = array();
				$data["articleStat"]["unique"][$year][$sem] = 0;
				$data["articleStat"]["totale"][$year][$sem] = count($st2);
				foreach($st2 as $st3) {
					if(!in_array($st3->getIp(), $mem)) {
						$data["articleStat"]["unique"][$year][$sem]++;
						$mem[] = $st3->getIp();
					}
				}
			}
		}
		// Création des graphs
		foreach($data["articleStat"]["data"] as $year => $dd) {
			$data["graphs"][$year] = serialize(array(
				"Vues totales" => $data["articleStat"]["totale"][$year],
				"Vues uniques" => $data["articleStat"]["unique"][$year]
				));
		}
		return $this->render('ensemble01LaboBundle:pages:statistiquesArticles.html.twig', $data);
	}


	public function graphLinePlot($data) {
		$data = unserialize($data);
		include(__DIR__.'/../../../../app/Resources/jpgraph-3.5.0b1/src/jpgraph.php');
		include(__DIR__.'/../../../../app/Resources/jpgraph-3.5.0b1/src/jpgraph_line.php');
		// Creation du graphique
		$graph = new Graph(400,300);
		$graph->SetScale("textlin");

		// foreach($data as $grnom => $grdata) {

		// Création du système de points
		$lineplot1 = new LinePlot($ydata1);
		$lineplot2 = new LinePlot($ydata2);
		
		$graph->img->SetMargin(40,20,20,40);
		$graph->title->Set('Vistes des pages articles');
		$graph->xaxis->title->Set('CA 2000 ');
		$graph->yaxis->title->Set('Paramètre fictif... ');
		
		// Antialiasing
		$graph->img->SetAntiAliasing();
		// On rajoute les points au graphique
		$graph->Add($lineplot1);
		$graph->Add($lineplot2);

		return new Response($graph->Stroke(), 200, array(
			'Content-Type' 			=> 'Content-Type: image/png',
			'Content-Disposition'	=> 'attachment; filename="image.png"'
			));
	}



	  //////////////////////
	 // Méthodes privées //
	//////////////////////

	private function datesFit($dateDebut, $dateFin) {
		if(is_string($dateDebut)) $dateDebut = new \Datetime(date(urldecode($dateDebut)));
		if(is_string($dateFin)) $dateFin = new \Datetime(date(urldecode($dateFin)));
		if($dateFin === null) $dateFin = new \Datetime();
		$data["dateDebut"] = $dateDebut;
		$data["dateFin"] = $dateFin;
		return $data;
	}

	private function listOptions() {
		return array("general", "articles", "ventes", "magasins", "autres");
	}

	private function getSemaineEnCours() {
		$date = new \Datetime();
		return $date->format("W");
	}


}
