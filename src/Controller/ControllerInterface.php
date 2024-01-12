<?php
namespace Rdb\Controller;

use Psr\Container\ContainerInterface;

interface ControllerInterface
{
	public function __construct(ContainerInterface $container);
	public function route(): ControllerInterface;
}
