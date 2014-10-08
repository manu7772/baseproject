<?php

namespace ensemble01\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ensemble01UserBundle extends Bundle
{
	public function getParent() {
		return 'FOSUserBundle';
	}
}
