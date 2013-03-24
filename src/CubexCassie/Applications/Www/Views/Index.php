<?php
/**
 * @author  brooke.bryan
 */
namespace CubexCassie\Applications\Www\Views;

use Cubex\Facade\Session;
use Cubex\View\HtmlElement;
use Cubex\View\Impart;
use Cubex\View\Partial;
use Cubex\View\TemplatedViewModel;

class Index extends TemplatedViewModel
{
  public function __construct()
  {
    $this->setTitle($this->t("Cubex : Index Page"));
  }
}
