<?php
namespace App\Library;

final class Util
{

  public function strip_html_tags($string, $remove_breaks = false)
  {
    $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
    $string = strip_tags($string);
    $string = join("\n", array_map("ltrim", explode("\n", $string)));
    if ($remove_breaks) {
      $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
    }

    return trim($string);
  }

  public function getToken($length)
  {
    $token = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet .= "0123456789";
    $max = strlen($codeAlphabet); // edited

    for ($i = 0; $i < $length; $i++) {
      $token .= $codeAlphabet[$this->crypto_rand_secure(0, $max - 1)];
    }

    return $token;
  }

  private function crypto_rand_secure($min, $max)
  {
    $range = $max - $min;
    if ($range < 1) {
      return $min;
    }
    // not so random...
    $log = ceil(log($range, 2));
    $bytes = (int) ($log / 8) + 1; // length in bytes
    $bits = (int) $log + 1; // length in bits
    $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
    do {
      $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
      $rnd = $rnd & $filter; // discard irrelevant bits
    } while ($rnd > $range);
    return $min + $rnd;
  }

}
