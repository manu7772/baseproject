<?php

namespace ensemble01\filemakerBundle\Controller;

use ensemble01\filemakerBundle\Controller\filemakerController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

class filemakerpublicController extends filemakerController {

	public function connectAction() {
		return new Response('|connect|');
	}

}
