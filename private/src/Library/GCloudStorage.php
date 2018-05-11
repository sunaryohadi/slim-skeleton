<?php
namespace App\Library;

use \Eventviva\ImageResize;

final class GCloudStorage
{
	protected $bucket;

	public function __construct($bucket)
    {
    	$this->bucket = $bucket;
    }

	public function uploadImage($file, $subdir = 'img/', $mediatype = 'image/jpeg')
	{
		
		// Balikin dulu ke temporary,  paksa jadi JPEG
		if ($mediatype !== 'image/jpeg') {
			$image = new ImageResize($file);
			$image->resizeToBestFit(1024,1024); // Maximum tinggi lebar 1024
			$image->save($file, IMAGETYPE_JPEG); 
		} 

		$image = new ImageResize($file);
		$image->quality_jpg = 67;
		$image->resizeToBestFit(1024,1024); // Maximum tinggi lebar 1024
		$this->uploadFile( $image, $subdir . 'l.jpg' );

		$image->resizeToBestFit(640, 480); // 4:3
		$this->uploadFile( $image, $subdir . 'm.jpg' );

		// $image->resizeToHeight(90);
		$image->crop(150,150);
		$this->uploadFile( $image, $subdir . 't.jpg' );

	}

	public function uploadVideo($file, $subdir = 'video/', $ext = 'mp4')
	{
		$source = fopen($file, 'r');
		$this->uploadFile( $source, $subdir . 'v.' . $ext );

	}

	public function uploadFile($source, $filename)
	{

		$object = $this->bucket->upload(
			$source, 
			[ 	
				'name' => $filename,
				'predefinedAcl' => 'publicRead' 
			]
		);

		$object->reload();

		return $object;

	}

	public function deleteImage($id)
	{

		$objects = $this->bucket->objects([
				'prefix' => 'img/' . $id . '/',
				'fields' => 'items/name,nextPageToken'
		]);

		foreach ($objects as $object) {
			$object->delete();
		}

	}


	public function deleteVideo($id)
	{

		$objects = $this->bucket->objects([
				'prefix' => 'video/' . $id . '/',
				'fields' => 'items/name,nextPageToken'
		]);

		foreach ($objects as $object) {
			$object->delete();
		}

	}

	public function getList( $prefix = 'img/')
	{

		$objects = $this->bucket->objects([
				'prefix' => $prefix,
				'fields' => 'items/name,nextPageToken'
		]);

		return $objects;

	}
	
}