<?php
	// Sensitive information about the database
	include("mysql_info.php");
	$conn = new mysqli("localhost",USERNAME,PASSWORD,"usaco");

	$type = $_POST['type'];
	if($type == 1) {
		// Retrieve all data
		$data = [];
		$res = $conn -> query("SELECT * FROM problems");
		while($row = $res -> fetch_assoc()) {
			$obj["id"] = $row["id"];
			$obj["contest"] = $row["contest"];
			$obj["name"] = $row["name"];
			$obj["url"] = $row["url"];
			$obj["type"] = $row["type"];
			$obj["rating"] = $row["rating"];
			$obj["quality"] = $row["quality"];
			$obj["rating2"] = $row["rating2"];
			$obj["quality2"] = $row["quality2"];
			$obj["cnt1"] = $row["cnt1"];
			$obj["cnt2"] = $row["cnt2"];
			array_push($data,$obj);
  		}
  		header('Content-type:application/json;charset=utf-8');
  		echo json_encode($data);
  		exit("");
	}else if($type == 2) {
		// Retrieve user data
		$data = [];
		$uid = $_POST["id"];
		$stmt = $conn -> prepare("SELECT * FROM users WHERE id=?");
		$stmt -> bind_param("s",$uid);
		$stmt -> execute();
		$res = $stmt -> get_result();
		if($res -> num_rows == 0) {
			array_push($data,-1);
		}else{
			$info = $res -> fetch_assoc();
			array_push($data,$info);
		}
		$stmt -> close();
		$stmt = $conn -> prepare("SELECT * FROM rating WHERE id=?");
		$stmt -> bind_param("s",$uid);
		$stmt -> execute();
		$res = $stmt -> get_result();
		$rating = [];
		if($res -> num_rows > 0) {
			while($row = $res -> fetch_assoc()) {
				array_push($rating,$row);
			}
		}
		array_push($data,$rating);
		$stmt -> close();
		$stmt = $conn -> prepare("SELECT * FROM quality WHERE id=?");
		$stmt -> bind_param("s",$uid);
		$stmt -> execute();
		$res = $stmt -> get_result();
		$quality = [];
		if($res -> num_rows > 0) {
			while($row = $res -> fetch_assoc()) {
				array_push($quality,$row);
			}
		}
		array_push($data,$quality);
		$stmt -> close();

		header('Content-type:application/json;charset=utf-8');
  		echo json_encode($data);
	}else if($type == 3) {
		// Register an account
		if($_POST["pwd"] == PASSWORD) {
			$uid = generateRandomString();
			$name = $_POST["name"];
			$cf = $_POST["cf"];
			
			registerAccount($conn,$uid,$name,$cf);

			$data = [];
			$data["id"] = $uid;
			header('Content-type:application/json;charset=utf-8');
  			echo json_encode($data);
		}else{
			
		}
	}else if($type == 4) {
		// Update votes according to data
		$data = [];
		$uid = $_POST["id"];
		$votes = json_decode($_POST["votes"]);
		if(!checkUserExist($conn,$uid)) {
			$data["status"] = -1;
			header('Content-type:application/json;charset=utf-8');
			echo json_encode($data);
			exit("");
		}
		for($i = 0;$i < count($votes[0]);$i++) {
			updateVotesRating($conn,$uid,$i + 1,$votes[0][$i]);
		}
		for($i = 0;$i < count($votes[1]);$i++) {
			updateVotesQuality($conn,$uid,$i + 1,$votes[1][$i]);
		}
		recalculateEverything($conn);
		$data["status"] = 1;
		header('Content-type:application/json;charset=utf-8');
		echo json_encode($data);
	}else if($type == 5) {
		// Temporary and should be removed once done
		// recalculateEverything($conn);
	}else if($type == 6) {
		// Increase visit counter :D
		$date = date("m-d-Y");
		$conn -> query("INSERT INTO stats (type,value) VALUES ('" . $date . "',1) 
			ON DUPLICATE KEY UPDATE value = value + 1;");
		$conn -> query("UPDATE stats SET value = value + 1 WHERE type = 'visit'");
	}else if($type == 7) {
		// Automatic registration

		if(!isset($_POST["username"]) || !isset($_POST["name"])) {
			$data["success"] = -5;
			$data["message"] = "A required field is empty";
			header('Content-type:application/json;charset=utf-8');
			echo json_encode($data);
			exit("");
		}

		$username = strtolower($_POST["username"]);
		$CFUsers = callAPI("http://codeforces.com/api/user.info?handles=" . $username) -> result;

		if(count($CFUsers) == 0) {
			$data["success"] = -1;
			$data["message"] = "The codeforces account does not exist!";
			header('Content-type:application/json;charset=utf-8');
			echo json_encode($data);
			exit("");
		}

		$CFUsers = $CFUsers[0];
		$firstName = $CFUsers -> firstName;
		$rating = $CFUsers -> rating;

		// 1900 Threshold
		if($rating < 1900) {
			$data["success"] = -2;
			$data["message"] = "The codeforces account's rating does not meet the 1900 threshold. If you believe you are still qualified, please fill out the manual review form.";
			header('Content-type:application/json;charset=utf-8');
			echo json_encode($data);
			exit("");
		}

		if(trim($firstName) != "USACO-Rating-verify") {
			$data["success"] = -3;
			$data["message"] = "The codeforces account's first name is not 'USACO-Rating-verify'";
			header('Content-type:application/json;charset=utf-8');
			echo json_encode($data);
			exit("");
		}

		if(checkCodeForcesExist($conn,$username)) {
			$data["success"] = -4;
			$data["message"] = "You have already registered an account! Contact CodeTiger on discord if you lost your personalized link.";
			header('Content-type:application/json;charset=utf-8');
			echo json_encode($data);
			exit("");
		}

		// Everything passes :D
		$uid = generateRandomString();
		$name = $_POST["name"];
		
		registerAccount($conn,$uid,$name,$username);

		$data["status"] = 1;
		$data["id"] = $uid;
		$data["message"] = "Success!";
		header('Content-type:application/json;charset=utf-8');
		echo json_encode($data);
	}

	function registerAccount($conn,$uid,$name,$cf) {
		$stmt = $conn -> prepare("INSERT INTO users (id,name,cf) VALUES (?,?,?)");
		$stmt -> bind_param("sss",$uid,$name,$cf);
		$stmt -> execute();
		$stmt -> close();
	}

	function recalculateEverything($conn) {
		recalculateRatings($conn);
		recalculateQualities($conn);
	}

	function updateVotesRating($conn,$uid,$pid,$rating) {
		if($rating == -2) return;
		if($rating != -1 && ($rating < 800 || $rating > 3500)) return;
		$stmt = $conn -> prepare("SELECT * FROM rating WHERE id=? AND pid=?");
		$stmt -> bind_param("si",$uid,$pid);
		$stmt -> execute();
		$res = $stmt -> get_result();
		$stmt -> close();
		if($res -> num_rows == 0) {
			if($rating == -1) return;
			$stmt = $conn -> prepare("INSERT INTO rating (id,pid,val) VALUES (?,?,?)");
			$stmt -> bind_param("sid",$uid,$pid,$rating);
			$stmt -> execute();
			$stmt -> close();
			return;
		}
		$stmt = $conn -> prepare("UPDATE rating SET val=? WHERE id=? AND pid=?");
		$stmt -> bind_param("dsi",$rating,$uid,$pid);
		$stmt -> execute();
		$stmt -> close();
		return;
	}

	function updateVotesQuality($conn,$uid,$pid,$quality) {
		if($quality == -2) return;
		if($quality != -1 && ($quality < 1 || $quality > 5)) return;
		$stmt = $conn -> prepare("SELECT * FROM quality WHERE id=? AND pid=?");
		$stmt -> bind_param("si",$uid,$pid);
		$stmt -> execute();
		$res = $stmt -> get_result();
		$stmt -> close();
		if($res -> num_rows == 0) {
			if($quality == -1) return;
			$stmt = $conn -> prepare("INSERT INTO quality (id,pid,val) VALUES (?,?,?)");
			$stmt -> bind_param("sid",$uid,$pid,$quality);
			$stmt -> execute();
			$stmt -> close();
			return;
		}
		$stmt = $conn -> prepare("UPDATE quality SET val=? WHERE id=? AND pid=?");
		$stmt -> bind_param("dsi",$quality,$uid,$pid);
		$stmt -> execute();
		$stmt -> close();
		return;
	}

	function findAverage($arr) {
		return $average = array_sum($arr) / count($arr);
	}

	function findMedian($arr) {
		sort($arr);
		return $arr[floor(count($arr) / 2)];
	}

	function recalculateRatings($conn) {
		$stmt = $conn -> prepare("SELECT * FROM problems");
		$stmt -> execute();
		$res = $stmt -> get_result();
		$N = $res -> num_rows;
		$stmt -> close();
		$rating = [];
		for($i = 1;$i <= $N;$i++) {
			$rating[$i] = [];
		}
		$stmt = $conn -> prepare("SELECT * FROM rating");
		$stmt -> execute();
		$res = $stmt -> get_result();
		$stmt -> close();
		if($res -> num_rows > 0) {
			while($row = $res -> fetch_assoc()) {
				if($row["val"] != -1) array_push($rating[$row["pid"]],$row["val"]);
  			}
		}
		for($i = 1;$i <= $N;$i++) {
			if(count($rating[$i]) == 0) {
				$stmt = $conn -> prepare("UPDATE problems SET rating=NULL,rating2=NULL,cnt1=0 WHERE id=" . $i);
				$stmt -> execute();
				$stmt -> close();
				continue;
			}
			$stmt = $conn -> prepare("UPDATE problems SET rating=?,rating2=?,cnt1=? WHERE id=?");
			$average = findAverage($rating[$i]);
			$median = findMedian($rating[$i]);
			$arrLen = count($rating[$i]);
			$stmt -> bind_param("ddii",$average,$median,$arrLen,$i);
			$stmt -> execute();
			$stmt -> close();
		}
		return;
	}

	function recalculateQualities($conn) {
		$stmt = $conn -> prepare("SELECT * FROM problems");
		$stmt -> execute();
		$res = $stmt -> get_result();
		$N = $res -> num_rows;
		$stmt -> close();
		$quality = [];
		for($i = 1;$i <= $N;$i++) {
			$quality[$i] = [];
		}
		$stmt = $conn -> prepare("SELECT * FROM quality");
		$stmt -> execute();
		$res = $stmt -> get_result();
		$stmt -> close();
		if($res -> num_rows > 0) {
			while($row = $res -> fetch_assoc()) {
				if($row["val"] != -1) array_push($quality[$row["pid"]],$row["val"]);
  			}
		}
		for($i = 1;$i <= $N;$i++) {
			if(count($quality[$i]) == 0) {
				$stmt = $conn -> prepare("UPDATE problems SET quality=NULL,quality2=NULL,cnt2=0 WHERE id=" . $i);
				$stmt -> execute();
				$stmt -> close();
				continue;
			}
			$stmt = $conn -> prepare("UPDATE problems SET quality=?,quality2=?,cnt2=? WHERE id=?");
			$average = findAverage($quality[$i]);
			$median = findMedian($quality[$i]);
			$arrLen = count($quality[$i]);
			$stmt -> bind_param("ddii",$average,$median,$arrLen,$i);
			$stmt -> execute();
			$stmt -> close();
		}
		return;
	}

	function checkUserExist($conn,$uid) {
		$stmt = $conn -> prepare("SELECT * FROM users WHERE id=?");
		$stmt -> bind_param("s",$uid);
		$stmt -> execute();
		$res = $stmt -> get_result();
		if($res -> num_rows == 0) {
			return false;
		}
		return true;
	}

	function generateRandomString($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for($i = 0;$i < $length;$i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	function callAPI($url){
		$result = json_decode(file_get_contents($url));
		return $result;
	}

	function checkCodeForcesExist($conn,$cf) {
		$stmt = $conn -> prepare("SELECT * FROM users WHERE cf=?");
		$stmt -> bind_param("s",$cf);
		$stmt -> execute();
		$res = $stmt -> get_result();
		if($res -> num_rows == 0) {
			return false;
		}
		return true;
	}
?>