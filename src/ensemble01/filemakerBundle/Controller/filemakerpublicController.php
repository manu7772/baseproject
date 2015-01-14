<?php

namespace ensemble01\filemakerBundle\Controller;

use filemakerBundle\Controller\filemakerController as fmController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

class filemakerpublicController extends fmController {

	protected $_fm;			// service filemakerservice


	protected function initFMdata($datt = null) {
		if(is_string($datt)) $datt = array($datt);
		$data = array();
		foreach($datt as $nom => $val) $data[$nom] = $val;
		// init User
		$data["User"] = $this->get('security.context')->getToken()->getUser();
		// sélection, tri (GET)
		$data["select"] = $this->compileSelection();
		// objet FM
		$this->_fm = $this->get('ensemble01services.geodiag');
		$this->_fm->log_user($data["User"]);
		return $data;
	}

	/**
	 * Génère un rapport d'id $id
	 * @param string $id - champ "id" de fm
	 * @param string $type - type de rapport -> "RDM-DAPP", etc.
	 * @param string $mode - type d'enregistrement -> "file" ou "load"
	 * @param string $format - type de document -> "pdf" ou "html"
	 */
	public function generate_rapportAction($id, $type = "RDM-DAPP", $mode = "file", $format = "pdf") {
		$data = $this->initFMdata(array("test" => "test"));
		$data["rapport"] = $this->_fm->getOneRapport($id);
		if(is_string($data["rapport"])) return new Response($data["rapport"]);

		switch(strtolower($format)) {
			case 'html':
				return $this->render("ensemble01filemakerBundle:pdf:rapport_".$type."_001.html.twig", $data);
				break;
			default:
				$filePDF = __DIR__.'/../../../../app/Resources/tools/html2pdf/html2pdf.class.php';
				if(!file_exists($filePDF)) die('Service HTML2PDF non trouvé !');
				require_once($filePDF);
				$html2pdf = new \HTML2PDF('P', 'A4', 'fr', true, 'UTF-8', array(10, 8, 10, 10));
				$html2pdf->pdf->SetDisplayMode('fullpage');
				// $html = $this->renderView("ensemble01filemakerBundle:pdf:rapport_DAPP_001.html.twig", $data);
				$html = $this->renderView("ensemble01filemakerBundle:pdf:rapport_".$type."_001.html.twig", $data);
				$html2pdf->writeHTML($html, false);
				$content = $html2pdf->Output($data["ref_rapport"].'.pdf');
				// return new Response();
				break;
		}

		return new Response($content, 200, array(
			'Content-Type' => 'application/force-download',
			'Content-Disposition' => 'attachment; filename=test.pdf'
			)
		);
	}



}
