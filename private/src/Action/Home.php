<?php
namespace App\Action;

use App\Action\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class Home extends Controller
{
  public function index(Request $request, Response $response, $args)
  {
    // Sample logger
    $this->logger->info("Home page action dispatched");

    // Sample Flash Message for the next request
    // $this->flash->AddMessage('error','Test Flash Message - Error');
    // $this->flash->AddMessage('alert','Test Flash Message - Alert');
    // $this->flash->AddMessage('success','Test Flash Message - Success');
    // $this->flash->AddMessage('notice','Test Flash Message - Notice');

    // Render
    $this->view->render($response, 'home.twig', [
      'title' => 'Homepage',
    ]);

    return $response;
  }

}
