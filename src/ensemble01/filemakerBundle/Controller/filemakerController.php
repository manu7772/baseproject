<?php

namespace ensemble01\filemakerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class filemakerController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('ensemble01filemakerBundle:pages:index.html.twig', array('name' => $name));
    }
}
