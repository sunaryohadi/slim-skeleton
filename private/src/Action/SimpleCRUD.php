<?php
namespace App\Action;

use App\Action\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SimpleCRUD extends Controller
{

  /* ---------------------------------------------------------------
   *  Main function ---> Data Listing
   * --------------------------------------------------------------- */
  public function main(Request $request, Response $response, $args)
  {
    $rpp = 10; // set rows per page
    // get argument page
    $page = isset($args['p']) ? (int) $args['p'] : 1;

    $sql = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS c.* FROM crud c ORDER BY ID DESC LIMIT %d, %d", ($page - 1) * 10, $rpp);

    // get records
    $results = $this->db->get_results($sql, ARRAY_A);
    // get total row
    $total_row = (int) $this->db->get_var("SELECT FOUND_ROWS()");

    // render template
    $this->view->render($response, 'crud/list.twig', [
      'results' => $results,
      'page' => $page,
      'rpp' => $rpp,
      'total_row' => $total_row,
    ]);

    return $response;
  }

  /* ---------------------------------------------------------------
   *  Similar to main function,  except add filter to query
   * --------------------------------------------------------------- */
  public function search(Request $request, Response $response, $args)
  {
    $rpp = 10; // set rows per page
    // get argument page
    $page = isset($args['p']) ? (int) $args['p'] : 1;

    // Add filter as search
    $where = "1=1 ";

    $sql = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS c.* FROM crud c WHERE $where LIMIT %d, %d", ($page - 1) * 10, $rpp);

    // get records
    $results = $this->db->get_results($sql, ARRAY_A);
    // get total row
    $total_row = (int) $this->db->get_var("SELECT FOUND_ROWS()");

    // render template
    $this->view->render($response, 'crud/list.twig', [
      'results' => $results,
      'page' => $page,
      'rpp' => $rpp,
      'total_row' => $total_row,
    ]);

    return $response;
  }

  /* ---------------------------------------------------------------
   *  Handle the add action
   * --------------------------------------------------------------- */
  public function add(Request $request, Response $response, $args)
  {
    $error = $post = array();

    // Check if form is submitted
    if ($request->isPost()) {

      $post = $request->getParams();

      if (empty($post['firstname'])) {
        // firstname must filled
        $error = array(
          "class" => "warning",
          "msg" => "First Name cannot empty");

      } else {

        // insert - sample how to insert record into database using ezSQL

        // this is for anti SQL injection
        $sql = $this->db->prepare(
          "INSERT INTO crud (
                  firstname,
                  lastname,
                  score
                ) VALUES (
                  %s,
                  %s,
                  %d
                )",
          $post['firstname'],
          $post['lastname'],
          $post['score']
        );

        // Execute the query
        $this->db->query($sql);

        // Get the ID if necessary
        $insertID = $this->db->insert_id;

        // redirect to /crud
        $newResponse = $response->withRedirect('/crud', 302);
        return $newResponse;
      }

    }

    // render reusable form template
    $this->view->render($response, 'crud/form.twig', [
      'post' => $post,
      'error' => $error,
      'action' => 'add',
    ]);

    return $response;

  }

  /* ---------------------------------------------------------------
   *  Handle the edit action
   * --------------------------------------------------------------- */
  public function edit(Request $request, Response $response, $args)
  {

    $error = array();

    if ($request->isPost()) {
      $post = $request->getParams();

      if (empty($post['firstname'])) {
        // firstname must filled
        $error = array(
          "class" => "warning",
          "msg" => "First Name cannot empty");

      } else {

        // update - sample how to update record in database using ezSQL

        // this is for anti SQL injection
        $sql = $this->db->prepare(
          "UPDATE crud SET
                    firstname = %s,
                    lastname = %s,
                    score = %d
                  WHERE ID = %d",
          $post['firstname'],
          $post['lastname'],
          $post['score'],
          $args['ID']
        );

        // Execute the query
        $this->db->query($sql);

        // redirect to /crud
        $newResponse = $response->withRedirect('/crud', 302);
        return $newResponse;
      }

    } else {

      // get current row into $post array
      $post = $this->db->get_row(
        $this->db->prepare("SELECT * from crud WHERE ID = %d", $args['ID']),
        ARRAY_A);

      // check if record exist, otherwise redirect
      if (!$post) {
        $newResponse = $response->withRedirect('/crud', 302);
        return $newResponse;
      }

    }

    // render reusable form template
    $this->view->render($response, 'crud/form.twig', [
      'post' => $post,
      'action' => 'edit',
      'error' => $error,
    ]);

    return $response;

  }

  /* ---------------------------------------------------------------
   *  Handle the delete action
   * --------------------------------------------------------------- */
  public function delete(Request $request, Response $response, $args)
  {
    if ($request->isPost()) {
      $post = $request->getParams();

      // delete - sample how to delete a record  using ezSQL

      // Always use prepare to avoid SQL injection :-)
      $sql = $this->db->prepare("DELETE FROM crud WHERE ID=%d", $args['ID']);

      // can use query for deleting, since we are not expecting any result query.
      $this->db->query($sql);
      $newResponse = $response->withRedirect('/crud', 302);
      return $newResponse;

    } else {

      // get current row into $post array
      $post = $this->db->get_row(
        $this->db->prepare("SELECT * from crud WHERE ID = %d", $args['ID']),
        ARRAY_A);

      // check if record exist, otherwise redirect
      if (!$post) {
        $newResponse = $response->withRedirect('/crud', 302);
        return $newResponse;
      }

    }

    // render reusable form template
    $this->view->render($response, 'crud/form.twig', [
      'post' => $post,
      'action' => 'delete',
    ]);

    return $response;

  }

}
