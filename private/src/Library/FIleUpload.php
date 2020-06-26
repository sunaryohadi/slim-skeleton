<?php
namespace App\Library;

final class FileUpload
{

  private $dir;

  public function __construct($mainPath = [])
  {
    $this->dir = $mainPath;
  }

  public function uploadCarSheet($fields, $subdir, $filename = "")
  {
    $url = $this->upload_image($fields, $subdir, $filename, true);
    return $url;
  }

  public function uploadImages($fields, $subdir, $filename = "")
  {
    $url = $this->upload_image($fields, $subdir, $filename);
    return $url;
  }

  private function upload_image($tmpfile, $subdir = null, $filename = "", $sheet_flag = false, $mime_allowed = [
    'jpg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
  ]
  ) {

    // Check if path not exists --> create
    $dir = $this->dir['base_dir'];
    $path = $this->dir['base_url'];
    $curdir = $dir;
    if (!file_exists($curdir) && !is_dir($curdir)) {
      mkdir($curdir);
    }
    if ($subdir !== null) {
      $subdirs = explode("/", $subdir);
      foreach ($subdirs as $sub) {
        $curdir = $curdir . '/' . $sub;
        if (!file_exists($curdir) && !is_dir($curdir)) {
          mkdir($curdir);
        }
      }
      $dir = $dir . $subdir;
      $path = $path . $subdir;
    }

    // Check if MIME is correct
    if (false === $ext = array_search(
      $this->get_mime_type($tmpfile),
      $mime_allowed,
      true
    )) {
      return false;
    }

    // Set target filename
    if ($filename == "") {
      $dest_name = sha1_file($tmpfile);
    } else {
      if ($sheet_flag) {
        $dest_name = $filename . '_s';
      } else {
        $i = 0;
        $ok = false;
        $dest_name = $filename;
        while (!$ok) {
          $i += 1;
          // check file exist
          $filecheck = sprintf('%s/%s.%s', $dir, $filename . '_' . $i, $ext);
          if (!file_exists($filecheck)) {
            $dest_name = $filename . '_' . $i;
            $ok = true;
          }
        }
      }
    }

    // Resize to max and thumbnail --> Save

    if (!move_uploaded_file(
      $tmpfile,
      sprintf('%s/%s.%s',
        $dir,
        $dest_name,
        $ext
      )
    )) {
      return false;
    }

    return $path . '/' . $dest_name . '.' . $ext;
  }

  public function uploadDocument($fields, $subdir, $filename = "")
  {
    $url = $this->upload_doc($fields, $subdir, $filename);
    return $url;
  }

  private function upload_doc($tmpfile, $subdir = null, $filename = "", $mime_allowed = [
    'jpg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
  ) {
    $dir = $this->dir['base_dir'];
    $path = $this->dir['base_url'];

    $curdir = $dir;
    if (!file_exists($curdir) && !is_dir($curdir)) {
      mkdir($curdir);
    }
    if ($subdir !== null) {
      $subdirs = explode("/", $subdir);
      foreach ($subdirs as $sub) {
        $curdir = $curdir . '/' . $sub;
        if (!file_exists($curdir) && !is_dir($curdir)) {
          mkdir($curdir);
        }
      }
      $dir = $dir . $subdir;
      $path = $path . $subdir;
    }

    if (false === $ext = array_search(
      $this->get_mime_type($tmpfile),
      $mime_allowed,
      true
    )) {
      return false;
    }

    if ($filename == "") {
      $dest_name = sha1_file($tmpfile);
    } else {
      $dest_name = $filename;
    }

    if (!move_uploaded_file(
      $tmpfile,
      sprintf('%s/%s.%s',
        $dir,
        $dest_name,
        $ext
      )
    )) {
      return false;
    }

    return $path . '/' . $dest_name . '.' . $ext;
  }

  /**
   *    mimetype
   *    Returns a file mimetype. Note that it is a true mimetype fetch, using php and OS methods. It will NOT
   *    revert to a guessed mimetype based on the file extension if it can't find the type.
   *    In that case, it will return false
   *    http://blog.pixelastic.com/2010/08/05/the-all-in-one-method-to-get-mimetypes-with-php/
   **/
  private function get_mime_type($filepath)
  {
    // Check only existing files
    if (!file_exists($filepath) || !is_readable($filepath)) {
      return false;
    }

    // Trying finfo
    if (function_exists('finfo_open')) {
      $finfo = finfo_open(FILEINFO_MIME);
      $mimeType = finfo_file($finfo, $filepath);
      finfo_close($finfo);
      // Mimetype can come in text/plain; charset=us-ascii form
      if (strpos($mimeType, ';')) {
        list($mimeType) = explode(';', $mimeType);
      }

      return $mimeType;
    }

    // Trying mime_content_type
    if (function_exists('mime_content_type')) {
      return mime_content_type($filepath);
    }

    // Trying exec
    if (function_exists('system')) {
      $mimeType = system("file -i -b $filepath");
      if (!empty($mimeType)) {
        return $mimeType;
      }

    }

    // Trying to get mimetype from images
    $imageData = @getimagesize($filepath);
    if (!empty($imageData['mime'])) {
      return $imageData['mime'];
    }

    return false;
  }

}
