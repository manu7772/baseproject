<?php

namespace ensemble01\services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use	Symfony\Component\HttpFoundation\RedirectResponse;
use	Symfony\Component\HttpFoundation\Request;
use	Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use	Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SuccessHandler implements AuthenticationSuccessHandlerInterface {

	protected $router;
	protected $container;
	protected $list_of_roles;
	protected $redirByRoles;

	public function __construct(RouterInterface $router, ContainerInterface $container) {
		$this->router = $router;
		$this->container = $container;
		$this->list_of_roles = $this->container->getParameter('security.role_hierarchy.roles');
		$this->redirByRoles = $this->container->getParameter('roles_redirect');
	}

	public function onAuthenticationSuccess(Request $request, TokenInterface $token) {
		$this->newRoute = $this->changeRoute($token->getUser()->getRoles());
		return new RedirectResponse($this->router->generate($this->newRoute));
	}

	/**
	 * Renvoie la route correspondante au role le plus haut de $roles
	 * @param array $roles - liste des roles d'un user (user->getRoles)
	 * @return string - nom de la route
	 */
	private function changeRoute($roles) {
		$newRoute = null;
		$HiRole = $this->HiRole($roles);
		if(array_key_exists($HiRole, $this->redirByRoles)) $newRoute = $this->redirByRoles[$HiRole];
		return $newRoute;
	}

	/**
	 * Renvoie le role le plus haut
	 * @param array $roles - liste des roles d'un user (user->getRoles)
	 * @return string - nom du role le plus haut dans $roles
	 */
	private function HiRole($roles) {
		// par défaut : role le plus bas (ROLE_USER)
		reset($this->list_of_roles);
		$roleName = key($this->list_of_roles);
		foreach ($this->list_of_roles as $role => $droits) {
			if(in_array($role, $roles)) $roleName = $role;
		}
		return $roleName;
	}


}