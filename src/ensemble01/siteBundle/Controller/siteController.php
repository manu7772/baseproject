<?php

namespace ensemble01\siteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class siteController extends Controller
{
    public function indexAction() {
    	$data = array();
    	$data['name'] = "anonyme";
        return $this->render('ensemble01siteBundle:pages:homepage.html.twig', $data);
    }

    public function menuAction($route, $routeparams) {
    	$data = array();
    	$data['route'] = $route;
    	$data['routeparams'] = urldecode($routeparams);
        return $this->render('ensemble01siteBundle:menus:menuprincipal.html.twig', $data);
    }

}
