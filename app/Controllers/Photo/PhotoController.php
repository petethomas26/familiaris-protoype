<?php

namespace App\Controllers\Photo;

use App\Controllers\Controller;

use App\Models\Photo;

use App\Models\Person;

use Respect\Validation\Validator as v;

class PhotoController extends Controller {

	/*************************************************************
	* Uploads an image of a person and a description for
	* the image
	* The image and the description are stored in the file system
	* ************************************************************/
	public function postUploadPhoto ($request, $response, $args) {
		$personId = $args['personId'];
		$description = $request->getParam('description');
		$destination = 'images/'. $personId;
		if (!file_exists($destination)) {
			mkdir($destination);
		}
		$storage = new \Upload\Storage\FileSystem($destination);
		$file = new \Upload\File('file', $storage);

		// Optionally you can rename the file on upload
		$new_filename = uniqid();
		$file->setName($new_filename);

		

		// Validate file upload
		// MimeType List => http://www.iana.org/assignments/media-types/media-types.xhtml
		$file->addValidations(array(
		    // Ensure file is of type "image/png" etc.
		    new \Upload\Validation\Mimetype(array('image/png', 'image/jpeg', 'image/gif')),

		    // Ensure file is no larger than 5M (use "B", "K", M", or "G")
		    new \Upload\Validation\Size('1M')
		));

		//Access data about the file that has been uploaded
		$data = array(
		    'name'       => $file->getNameWithExtension(),
		    'extension'  => $file->getExtension(),
		    'mime'       => $file->getMimetype(),
		    'size'       => $file->getSize(),
		);
		

		// Try to upload file
		$message = '';
		try {
		    // Success!
		    $file->upload();
		    //Add description to database
		    if ($description !== "") {
		    	\App\Models\Photo::insert(['person_id' => $personId, 'image_name' => $data['name'], 'description' => $description]);
		    }
		    $message = "Photo uploaded successfully";
		    $this->container->flash->addMessage('info', $message);
		} catch (\Exception $e) {
		    // Fail!
		    $errors = $file->getErrors();
		    $message = 'The upload was not successful:';
		    foreach ($errors as $error) {
		    	$message = $message. ' ' . $error;
		    };
		    $this->container->flash->addMessage('error', $message);

		}
		
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	public function getShowPhoto($request, $response, $args) {
		$personId = $args['personId'];
		$base = $request->getUri()->getBasePath();

		$photoPath = "images/" . $personId . "/*.*";

		$paths = glob($photoPath);
		foreach($paths as $path) {
			$pth = explode('/', $path);
			$fileNames[] = $pth[2];
		}

		$rows = \App\Models\Photo::where('person_id', '=', $personId) ->select('description', 'image_name')->get();

		$descriptions = [];
		foreach ($rows as $row) {
			$descriptions[$row['image_name']] = $row['description'];
		}
		
		return $this->container->view->render($response, 'Photo/showPhoto.twig', compact('personId','fileNames', 'descriptions'));
	}

	public function postShowPhoto($request,$response, $args) {
		$personId = $request->getParam('personId');
		$photoName = $request->getParam('options');
		$select = $request->getParam('select');
		$delete = $request->getParam('delete');
		
		if ($select !== null) {
			$arr = explode('.', $photoName);
			$person = \App\Models\Person:: where('id', $personId)
	            		->update(['photo_name' => $arr[0], 'photo_extension' => $arr[1]]);
	            		
	        $this->container->flash->addMessage('info', "Photo path: " . $photoName);
	        return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	    } elseif ($delete !== null) {
	    	$arr = explode('.', $photoName);
			$deletePhotoPath = "images/" . $personId . "/" . $photoName;
	
			// Check whether this is current photo; if so, replace by noimage.png
			$person = \App\Models\Person:: find($personId);
			$photoPath = $person['photo_path'];
			$photoExtension = $person['photo_extension'];
			if ($photoPath === $arr[0] && $photoExtension === $arr[1]) {
				// Replace by noimage.png
				\App\Models\Person::where('id', $personId)->update(['photo_path' => $arr[0], 'photo_extension' => $arr[1]]);
			}
	        // Now delete photo from directory and delete description from DB
	        if (!unlink($deletePhotoPath)) {
	        	$this->container->flash->addMessage('info', "Error deleting photo: " . $photoName);
	        } else {
	        	\App\Models\Photo::where('person_id', '=', $personId)->where('image_name', '=', $photoName)->delete();
	    		$this->container->flash->addMessage('info', "Photo deleted: " . $photoName);
	    	}

	    	return $response->withRedirect($this->container->router->pathFor('showPhoto', ['personId'=>$personId]));
	    }
		
	}

	public function getDeletePhoto($request,$response, $args) {
		$personId = $args['personId'];
		$photoId = $args['photoId'];

		dump($personId, $photoId); die();
	}

}