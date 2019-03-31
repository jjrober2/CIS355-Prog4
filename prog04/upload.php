<?php
	//Upload Path and blob to DB
	// see HTML form (upload02.html) for overview of this program

	// include code for database access
	require 'database.php';

	// set PHP variables from data in HTML form 
	$fileName       = $_FILES['AvatarFile']['name'];
	$tempFileName   = $_FILES['AvatarFile']['tmp_name'];
	$fileSize       = $_FILES['AvatarFile']['size'];
	$fileType       = $_FILES['AvatarFile']['type'];
	
	//Get Blob Data
	$fileContent = fread(fopen($tempFileName,"r"), $fileSize);
	
	//Current ID
	$curr_id = substr($_SERVER['QUERY_STRING'],-1);
	
	// set server location (subdirectory) to store uploaded files
	$fileLocation = "customer_img/";
	$fileFullPath = $fileLocation . $fileName; 
	if (!file_exists($fileLocation))
		mkdir ($fileLocation, 777); // create subdirectory, if necessary (REQUIRES permission 777 to work on CSIS)

	// execute debugging code...
	// echo phpinfo(); exit(); // to see location of php.ini
	// note: can't set php.ini:file_uploads on the fly
	// echo ini_set('file_uploads', '1'); // "set" does not work
	// echo ini_get('file_uploads'); // "get" does work
	// echo "<pre>"; print_r(ini_get_all()); echo "</pre>"; exit();
	// echo "<pre>"; print_r($_FILES); echo "</pre>"; exit(); 
	
	// connect to database
	$pdo = Database::connect();
	
	// exit, if requested file already exists -- in the database table 
	$fileExists = false;
	$sql = "SELECT filelinkedpath FROM customers WHERE filelinkedpath='$fileFullPath'";
	foreach ($pdo->query($sql) as $row) {
		if ($row['filelinkedpath'] == $fileFullPath) {
			$fileExists = true;
		}
	}
	if ($fileExists) {
		echo "File <html><b><i>" . $fileName 
			. "</i></b></html> already exists in DB. Please rename file.";
		sleep(3);
		header("Location:customers.php");
	}
	
	// Allow certain file formats
		if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
			&& $imageFileType != "gif" ) {
		echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
		sleep(3);
		header("Location:customers.php");
	} 
	
	// exit, if requested file already exists -- in the subdirectory 
	if(file_exists($fileFullPath)) {
		echo "File <html><b><i>" . $fileName 
			. "</i></b></html> already exists in file system, "
			. "but not in database table. Cannot upload.";
		sleep(3);
		header("Location:customers.php");
	}
	
	// if all of above is okay, then upload the file
	$result = move_uploaded_file($tempFileName, $fileFullPath);
	
	// if upload was successful, then add a record to the SQL database
	if ($result) {
		echo "Your file <html><b><i>" . $fileName 
			. "</i></b></html> has been successfully uploaded";
			
		//Delete any previous Files from DIR for Current User Avatar if Applicable
		$sql = "SELECT * FROM customers WHERE id=? LIMIT 1";
		$q = $pdo->prepare($sql);
		$q->execute(array($curr_id));
		$data = $q->fetch(PDO::FETCH_ASSOC);
		echo "<br> Contents of filelinkedpath '". $data['filelinkedpath'] . "'";
		if(!empty($data['filelinkedpath'])) {
			unlink($data['filelinkedpath']);
		}
		
		//Update Database with Avatar Data
		$sql = "UPDATE customers SET filestore=?, filelinkedpath=?, filesize=?, filetype=? WHERE id=?";
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$q = $pdo->prepare($sql);
		$q->execute(array($fileContent,$fileFullPath,$fileSize,$fileType,$curr_id));
	// otherwise, report error
	} else {
		echo "Upload denied for this file. Verify file size < 2MB. ";
	}
		
	// disconnect
	Database::disconnect(); 
	header("Location:customers.php");

