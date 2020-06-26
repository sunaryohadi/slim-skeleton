<?php
namespace App\Library;

final class Session
{
  // Save SESSION variable
  public function updateSession($row)
  {
    $_SESSION['user'] = [
      'login' => true,
      'id' => $row->id,
      'nickname' => $row->nickname,
      'lastlogin' => $row->lastlogin_date,
      'role' => $row->role,
      'level' => $row->level,
    ];
    unset($_SESSION['csrf']); // Buang session CSRF ?
  }

}
