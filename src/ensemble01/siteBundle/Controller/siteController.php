<?php

namespace ensemble01\siteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class siteController extends Controller
{
    public function indexAction($name) {
    	$data = array();
    	$data['name'] = $name;
        return $this->render('ensemble01siteBundle:pages:homepage.html.twig', $data);
    }

    public function menuAction() {
    	$data = array();
        return $this->render('ensemble01siteBundle:menus:menuprincipal.html.twig', $data);
    }

}
