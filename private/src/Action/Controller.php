<?php
namespace App\Action;

class Controller
{

  protected $container;

  public function __construct($container)
  {
    $this->container = $container;
  }

  // Magic property
  public function __get($property)
  {
    if ($this->container->{$property}) {
      return $this->container->{$property};
    }
  }
}
