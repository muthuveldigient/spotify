<?php 
//error_reporting(E_ALL);
class DbHandler {
    private $conn;
    private $dbh;
	public $users;
	public $songs;
	public $albums;
	public $artists_type;
	public $artists;
	public $song_percentages;
	public $song_history;
	public $play_list;
	public $play_list_songs;
	public $user_points;
	public $wishlist_songs;
	public $wishlist_albums;
	public $wishlist_artists;
	public $genres_types;
	public $mood_types;
    function __construct() {
       	require_once dirname(__FILE__) . '/DbConnect.php';
        $db = new DbConnect();
		$this->conn = $db->connect();
		$this->pdo =$db->pdoconnect();
		$this->users="users";
		$this->albums="albums";
		$this->songs="songs";
		$this->artists="artists";
		$this->artists_type="artists_type";
		$this->song_history="song_history";
		$this->play_list="user_playlists";
		$this->play_list_songs="user_playlist_songs";
		$this->user_points="user_points";
		$this->wishlist_songs="wishlist_songs";
		$this->wishlist_albums="wishlist_albums";
		$this->wishlist_artists="wishlist_artists";
		$this->song_percentages="song_percentages";
		$this->genres_types="genres_types";
		$this->mood_types="mood_types";
        $this->song_ratings = "song_ratings";
        $this->home_play_list_songs = "home_playlist_songs";
        $this->home_play_lists = "home_playlists";
    }
	
	//Method will create a new user
    public function createUser( $request ){
        if (!$this->isUserPhoneExists($request['USER_PHONE'])) {
			$stmt = $this->conn->prepare("INSERT INTO ".$this->users."(USER_PHONE,USER_STATUS) values(?, 1)");
			$stmt->bind_param("s", $request['USER_PHONE']);
			$result = $stmt->execute();
			$inserId =  $this->conn->insert_id;
			$stmt->close();
			if ($result) {
				$res['id'] = $inserId;
				return $res;
			} else {
				return 1;
			}
			
        } else {
            return 2;
        }
    }

	 public function createUser_old( $request ){
        if (!$this->isUserEmailExists($request['USER_EMAIL'])) {
        	if (!$this->isUserExists($request['USER_USERNAME'])) {
	            $password = md5($request['USER_PASSWORD']);
	            $stmt = $this->conn->prepare("INSERT INTO ".$this->users."(USER_USERNAME, USER_PASSWORD, USER_EMAIL, USER_GENDER, USER_DOB, USER_STATUS) values(?, ?, ?, ?, ?, 1)");
	            $stmt->bind_param("sssss", $request['USER_USERNAME'], $password, $request['USER_EMAIL'], $request['USER_GENDER'], $request['USER_DOB']);
	            $result = $stmt->execute();
				$inserId =  $this->conn->insert_id;
	            $stmt->close();
	            if ($result) {
	                $res['id'] = $inserId;
	                return $res;
	            } else {
	                return 1;
		        }
			} else {
				return 2;
			}
        } else {
            return 3;
        }
    }
    //Method for user login
    public function userLogin($username,$pass){
    	if (empty( $username ) || empty( $pass ) ){
    		return 0;
    	}
        $password = md5($pass);
        $stmt = $this->conn->prepare("SELECT * FROM ".$this->users." WHERE USER_USERNAME=? and USER_PASSWORD=?");
        $stmt->bind_param("ss",$username,$password);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows>0;
    }

    //This method will return user detail
    public function getUser($username){
    	if (empty( $username )){
    		return 0;
    	}
        $stmt = $this->conn->prepare("SELECT USER_ID FROM ".$this->users." WHERE USER_USERNAME=?");
        $stmt->bind_param("s",$username);
        $stmt->execute();
        $stmt->bind_result($userId);
        $stmt->fetch();
        $stmt->close();
//         $user = $stmt->get_result()->fetch_assoc();
        $data['USER_ID']=$userId;
        return $data;
    }
    
    //This method will return user detail
    public function getUserDetails($userId){
    	if (empty( $userId )){
    		return 0;
    	}
    	$stmt = $this->conn->prepare("SELECT USER_ID, USER_USERNAME,USER_PHONE FROM ".$this->users." WHERE USER_ID=?");
    	$stmt->bind_param("s",$userId);
    	$stmt->execute();
    	$stmt->bind_result($user_id, $user_name,$mobile);
    	$stmt->fetch();
    	$stmt->close();
    	$data['USER_ID']=$user_id;
    	$data['USER_NAME']=$user_name;
    	$data['USER_PHONE']=$mobile;
//     	$user = $stmt->get_result()->fetch_assoc();
//     	$stmt->close();
    	return $data;
    }

    public function isUserIdExists($userId) {
    	$stmt = $this->conn->prepare("SELECT USER_ID from ".$this->users." WHERE USER_ID = ?");
    	$stmt->bind_param("s", $userId);
    	$stmt->execute();
    	$stmt->store_result();
    	$num_rows = $stmt->num_rows;
    	$stmt->close();
    	return $num_rows > 0;
    }
    
    //Checking whether a username already exist
    private function isUserExists($username) {
        $stmt = $this->conn->prepare("SELECT USER_ID from ".$this->users." WHERE USER_USERNAME = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    
    //Checking whether a user email already exist
    private function isUserEmailExists($email) {
    	$stmt = $this->conn->prepare("SELECT USER_ID from ".$this->users." WHERE USER_EMAIL = ?");
    	$stmt->bind_param("s", $email);
    	$stmt->execute();
    	$stmt->store_result();
    	$num_rows = $stmt->num_rows;
    	$stmt->close();
    	return $num_rows > 0;
    }
    
    //This method will return song detail
    public function getSongDetails( $songId='', $limit='' ){
    	if (!empty( $songId )){
    		$sql_query = "SELECT song.SONG_ID, song.SONG_NAME, song.SONG_ALBUM_ID, song.SONG_COVER_IMAGE, song.SONG_URL, song.SONG_LISTENED_COUNT
    					FROM ".$this->songs." as song
	    				WHERE song.SONG_ID=? AND song.SONG_IS_PODCAST = 0";
    		
    		$stmt = $this->pdo->prepare($sql_query);
    		$stmt->bindParam(1, $songId);
    		$stmt->execute();
    		/* $stmt = $this->conn->prepare($sql_query);
    		$stmt->bind_param("s",$songId);
    		$stmt->execute();
    		$stmt->bind_result($song_id, $song_name, $albumId, $image, $listenCount);
    		$stmt->fetch();
    		$stmt->close(); */
    		$songInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    		/*$songInfo['SONG_ID']=$song_id;
    		$songInfo['SONG_NAME']=$song_name;
    		$songInfo['SONG_COVER_IMAGE']=$image;
    		$songInfo['SONG_LISTENED_COUNT']=$listenCount;
    		$songInfo['SONG_ALBUM_ID']=$albumId;*/
//     		$songInfo = $stmt->get_result()->fetch_assoc();
//     		$stmt->close();
    	}else{
    		$count = (!empty($limit)?$limit:LIMIT_COUNT);
    		$sql_query = "SELECT song.SONG_ID, song.SONG_NAME, song.SONG_COVER_IMAGE, song.SONG_URL, song.SONG_LISTENED_COUNT,
    					album.ALBUM_ID,album.ALBUM_NAME,album.ALBUM_LOGO
    					FROM ".$this->songs." as song
	    				JOIN ".$this->albums." as album ON song.SONG_ALBUM_ID=album.ALBUM_ID
	    				WHERE song.SONG_IS_PODCAST = 0 ORDER by song.SONG_ID DESC LIMIT ".$count;
    		
    				/* Artist list, type and percentage if need added query
    				 * art.ARTISTS_ID,art.ARTISTS_TYPE_ID,art.ARTISTS_USERNAME, type.ARTISTS_TYPE_NAME
    					"JOIN ".$this->artists." as art ON art.ARTISTS_ID=album.ALBUM_ARTISTS_ID
	    				JOIN ".$this->song_percentages." as sp ON sp.SONG_ID=song.SONG_ID
	    				JOIN ".$this->artists_type." as type ON type.ARTISTS_TYPE_ID=sp.ARTISTS_TYPE_ID";*/

    		$stmt = $this->pdo->prepare($sql_query);
    		$stmt->execute();
    		while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    			$songInfo[] = $row; // appends each row to the array
    		}
    		
// 	    	$stmt = $this->conn->query($sql_query);
// 	    	$songInfo = $stmt->fetch_all(MYSQLI_ASSOC);
    	}
   // 	$stmt->close();
    	return $songInfo;
    }
    
    //This method will return album detail
    public function getAlbumDetails($req =''){
		$albumInfo=array();
        $where = '';
        if(isset($req['search']) && !empty($req['search'])){
            $where .= " AND ALBUM_NAME LIKE '%".$req['search']."%' ";
        }
        
    	$sql_query = "SELECT * FROM $this->albums WHERE ALBUM_IS_PODCAST = 0 AND ALBUM_STATUS=1 ".$where." LIMIT ".LIMIT_COUNT;
    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->execute();
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$albumInfo[] = $row; // appends each row to the array
    	}
    	return $albumInfo;
    	
//     	$albumInfo = $stmt->fetch_all(MYSQLI_ASSOC);
//     	// 	$stmt->close();
//     	return $albumInfo;
    }
    
    public function getArtistDetails(){
    	$sql_query = "SELECT art.ARTISTS_ID,art.ARTISTS_USERNAME,type.ARTISTS_TYPE_ID, type.ARTISTS_TYPE_NAME, art.ARTISTS_IMAGE 
    			FROM $this->artists as art 
    			JOIN ".$this->artists_type." as type ON type.ARTISTS_TYPE_ID = art.ARTISTS_TYPE_ID 
                WHERE art.ARTISTS_STATUS=1 
    			LIMIT ".LIMIT_COUNT;
    	
    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->execute();
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$artistInfo[] = $row; // appends each row to the array
    	}
    	return $artistInfo;
    	
//     	$stmt = $this->conn->query($sql);
//     	$albumInfo = $stmt->fetch_all(MYSQLI_ASSOC);
//     	// 	$stmt->close();
//     	return $albumInfo;
    }
    
    public function getUserRecentlyPlayedAlbumList( $userId, $limit='' ){
    	$albumInfo=array();
		if (empty( $userId )){
    		return $albumInfo;
    	}
    	$count		= (!empty( $limit )?$limit:LIMIT_COUNT);
    	
    	$sql_query = "SELECT sh.USER_ID, album.ALBUM_ID, album.ALBUM_NAME,album.ALBUM_LOGO, art.ARTISTS_ID, art.ARTISTS_USERNAME FROM ".$this->song_history." As sh
    					JOIN ".$this->albums." as album ON sh.ALBUM_ID=album.ALBUM_ID
    					JOIN ".$this->artists." as art ON art.ARTISTS_ID=album.ALBUM_ARTISTS_ID
    					WHERE sh.USER_ID = ? AND album.ALBUM_IS_PODCAST = 0 ORDER by sh.HISTORY_ID DESC LIMIT ".$count;
    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->bindParam(1, $userId);
    	$stmt->execute();
    	//     	$wishList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    	//     	$stmt->close();
    	
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$albumInfo[] = $row; // appends each row to the array
    	}
		
		return $albumInfo;
    }
    
    public function getUserPlayList($userId){
    	if (empty( $userId )){
    		return 0;
    	}
    	
    	$sql_query = "SELECT PLAYLIST_ID, USER_ID, PLAYLIST_NAME FROM ".$this->play_list." WHERE USER_ID=? LIMIT ".LIMIT_COUNT;
    	
    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->bindParam(1, $userId);
    	$stmt->execute();
		$playListInfo =array();
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$playListInfo[] = $row; // appends each row to the array
    	}
    	return $playListInfo;
    	
//     	$stmt->bind_param("s",$userId);
//     	$stmt->execute();
// //     	$user = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// //     	$stmt->close();
//     	$stmt->bind_result($playList_id, $user_id, $playListName);
//     	$stmt->fetch();
//     	$stmt->close();
    	 
//     	$playListInfo['USER_ID']=$user_id;
//     	$playListInfo['PLAYLIST_ID']=$playList_id;
//     	$playListInfo['PLAYLIST_NAME']=$playListName;
    	//return $playListInfo;
    }
    
    public function getUserPlayListSongs( $userId, $playListId ){
    	$playListInfo =array();
		if (empty( $userId ) || empty( $playListId)){
    		return $playListInfo;
    	}
    	
    	$sql_query = "SELECT album.ALBUM_ID, album.ALBUM_NAME, album.ALBUM_LOGO, art.ARTISTS_ID, art.ARTISTS_USERNAME, song.SONG_ID, song.SONG_NAME,song.SONG_COVER_IMAGE, song.SONG_URL,song.HIGH_SONG_URL, pl.PLAYLIST_ID, pl.PLAYLIST_NAME
    			FROM ".$this->play_list_songs." AS ps
                JOIN ".$this->songs." as song ON song.SONG_ID = ps.SONG_ID
    			JOIN ".$this->play_list." as pl ON pl.PLAYLIST_ID=ps.PLAYLIST_ID
    			JOIN ".$this->albums." as album ON album.ALBUM_ID=song.SONG_ALBUM_ID
    			JOIN ".$this->artists." as art ON art.ARTISTS_ID=album.ALBUM_ARTISTS_ID
    			WHERE ps.USER_ID=? AND ps.PLAYLIST_ID= ? LIMIT ".LIMIT_COUNT;

    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->bindParam(1, $userId);
    	$stmt->bindParam(2, $playListId);
    	$stmt->execute();
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$playListInfo[] = $row; // appends each row to the array
    	}
    	return $playListInfo;
    	
//     	$stmt = $this->conn->prepare($sql_query);
//     	$stmt->bind_param("ss", $userId, $playListId);
//     	$stmt->execute();
// //     	$user = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// //     	$stmt->close();
//     	$stmt->bind_result($album_id, $albumName, $artistName, $song_id, $playList_id,$playListName);
//     	$stmt->fetch();
//     	$stmt->close();
    	
//     	$playListInfo['ALBUM_ID']=$album_id;
//     	$playListInfo['ALBUM_NAME']=$albumName;
//     	$playListInfo['ARTISTS_USERNAME']=$artistName;
//     	$playListInfo['SONG_ID']=$song_id;
//     	$playListInfo['PLAYLIST_ID']=$playList_id;
//     	$playListInfo['PLAYLIST_NAME']=$playListName;

//     	return $playListInfo;
    }
    
    //Method will create a new play list
    public function createPlayList( $request ){
    		if (!$this->isPlayListExists( $request )) {
    			$stmt = $this->conn->prepare("INSERT INTO ".$this->play_list."(USER_ID, PLAYLIST_NAME) values(?, ?)");
    			$stmt->bind_param("ss", $request['USER_ID'], $request['PLAYLIST_NAME']);
    			$result = $stmt->execute();
    			$inserId =  $this->conn->insert_id;
    			$stmt->close();
    			if ($result) {
    				$res['id'] = $inserId;
    				return $res;
    			} else {
    				return 1;
    			}
    		} else {
    			return 2;
    		}
    }
    
    private function isPlayListExists($request) {
    	if ( isset( $request['USER_ID'] ) && isset($request['PLAYLIST_NAME'])){
	    	$stmt = $this->conn->prepare("SELECT PLAYLIST_ID from ".$this->play_list." WHERE USER_ID = ? AND PLAYLIST_NAME = ?");
	    	$stmt->bind_param("ss", $request['USER_ID'], $request['PLAYLIST_NAME']);
	    	$stmt->execute();
	    	$stmt->store_result();
	    	$num_rows = $stmt->num_rows;
	    	$stmt->close();
    	}elseif ( isset( $request['USER_ID'] ) && isset($request['PLAYLIST_ID'])){
	    	$stmt = $this->conn->prepare("SELECT PLAYLIST_ID from ".$this->play_list." WHERE USER_ID = ? AND PLAYLIST_ID = ?");
	    	$stmt->bind_param("ss", $request['USER_ID'], $request['PLAYLIST_ID']);
	    	$stmt->execute();
	    	$stmt->store_result();
	    	$num_rows = $stmt->num_rows;
	    	$stmt->close();
    	}
    	return $num_rows > 0;
    }
    
    //Method will delete a playlist
    public function deletePlayList( $request ){
    	$res = 0;
    	/** if exits delete playlist songs table record */
    	if ($this->isPlayListSongsExists($request) ){
    		$query = "DELETE FROM ".$this->play_list_songs." WHERE USER_ID = ? AND PLAYLIST_ID = ?";
    		$stmt = $this->conn->prepare($query);
    		$stmt->bind_param("ss", $request['USER_ID'], $request['PLAYLIST_ID']);
    		$result1 = $stmt->execute();
    		$stmt->close();
    	}

    	if ($this->isPlayListExists($request) ){
	    	$stmt = $this->conn->prepare("DELETE FROM ".$this->play_list." WHERE USER_ID = ? AND PLAYLIST_ID = ? ");
	    	$stmt->bind_param("ss", $request['USER_ID'], $request['PLAYLIST_ID']);
	    	$result = $stmt->execute();
	    	$stmt->close();
	    	if ($result ){
	    		$res=1;
	    	}
    	}else{
    		$res = 2;
    	}
    	
    	return $res;
    }
    
    private function isPlayListSongsExists($request) {
    	$num_rows =0;
        if ( !empty($request['PLAYLIST_ID']) && !empty($request['USER_ID']) && !empty($request['SONG_ID'])) {
            $stmt = $this->conn->prepare("SELECT PLAYLIST_ID from ".$this->play_list_songs." WHERE PLAYLIST_ID = ? AND USER_ID = ? AND SONG_ID = ? ");
            $stmt->bind_param("sss", $request['PLAYLIST_ID'], $request['USER_ID'], $request['SONG_ID']);
            $stmt->execute();
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
        }elseif ( !empty($request['PLAYLIST_ID']) && !empty($request['USER_ID']) ){
            $stmt = $this->conn->prepare("SELECT PLAYLIST_ID from ".$this->play_list_songs." WHERE PLAYLIST_ID = ? AND USER_ID = ? ");
            $stmt->bind_param("ss", $request['PLAYLIST_ID'], $request['USER_ID']);
            $stmt->execute();
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
        }
    	
    	return $num_rows > 0;
    }
    
	
    //Method will create a new song plays list
    public function createSongPlayList( $request ){
    	
    	if (!$this->isPlayListSongsExists( $request )) {
    		$stmt = $this->conn->prepare("INSERT INTO ".$this->play_list_songs."(PLAYLIST_ID, USER_ID, SONG_ID) values(?, ?, ?)");
    		$stmt->bind_param("sss", $request['PLAYLIST_ID'], $request['USER_ID'], $request['SONG_ID']);
    		$result = $stmt->execute();
    		$inserId =  $this->conn->insert_id;
    		$stmt->close();
    		if ($result) {
    			$res['id'] = $inserId;
    			return $res;
    		} else {
    			return 1;
    		}
    	} else {
    		return 2;
    	}
    }
    
    //Method will delete song playlist
    public function deleteSongPlayList( $request ){
    
    	if ($this->isPlayListSongsExists( $request )) {
	    	$query = "DELETE FROM ".$this->play_list_songs." WHERE USER_ID = ? AND PLAYLIST_ID = ? AND SONG_ID = ? ";
	    	$stmt = $this->conn->prepare($query);
	    	$stmt->bind_param("sss", $request['USER_ID'], $request['PLAYLIST_ID'], $request['SONG_ID']);
	    	$result = $stmt->execute();
	    	$stmt->close();
	    	if ($result) {
	    		return 1;
	    	} else {
	    		return 0;
	    	}
    	}else{
    		return 2;
    	}
    }
    
    //This method will return wish list song detail
    public function getWishListSongDetails( $userId ){
		$wishList = array();
    	if (empty( $userId )){
    		return $wishList;
    	}
        $sql_query = "SELECT song.SONG_ID, song.SONG_NAME, song.SONG_COVER_IMAGE, song.SONG_URL,song.HIGH_SONG_URL,song.HIGH_SONG_URL, album.ALBUM_ID, album.ALBUM_LOGO, album.ALBUM_NAME, art.ARTISTS_USERNAME, song.SONG_LISTENED_COUNT, AVG(RATING) as SONG_RATING FROM $this->wishlist_songs as ws
                        JOIN $this->songs as song ON song.SONG_ID = ws.SONG_ID
                        JOIN ".$this->albums." as album ON album.ALBUM_ID = song.SONG_ALBUM_ID 
                        JOIN ".$this->artists." as art ON art.ARTISTS_ID = album.ALBUM_ARTISTS_ID
                        LEFT JOIN ".$this->song_ratings." as rating ON rating.SONG_ID = song.SONG_ID
                        WHERE ws.USER_ID = ?  GROUP BY song.SONG_ID LIMIT ".LIMIT_COUNT;
                        
    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->bindParam(1, $userId);
    	$stmt->execute();
//     	$wishList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
//     	$stmt->close();

    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$wishList[] = $row; // appends each row to the array
    	}
    	return $wishList;
    }
    
    
    private function isWishListSongsExists($request) {
    	$num_rows =0;
    	if ( !empty($request['USER_ID']) && !empty($request['SONG_ID']) ){
    		$stmt = $this->conn->prepare("SELECT * from ".$this->wishlist_songs." WHERE USER_ID = ? AND SONG_ID = ?");
    		$stmt->bind_param("ss",  $request['USER_ID'], $request['SONG_ID']);
    		$stmt->execute();
    		$stmt->store_result();
    		$num_rows = $stmt->num_rows;
    		$stmt->close();
    	}elseif ( !empty($request['USER_ID']) ){
    		$stmt = $this->conn->prepare("SELECT * from ".$this->wishlist_songs." WHERE USER_ID = ?");
    		$stmt->bind_param("s",  $request['USER_ID']);
    		$stmt->execute();
    		$stmt->store_result();
    		$num_rows = $stmt->num_rows;
    		$stmt->close();
    	}
    	 
    	return $num_rows > 0;
    }
    
    public function createWishListSongs( $request ){
//     	if (!$this->isWishListSongsExists( $request )) {
    		$stmt = $this->conn->prepare("INSERT INTO ".$this->wishlist_songs."(USER_ID, SONG_ID) values(?, ?)");
    		$stmt->bind_param("ss",  $request['USER_ID'], $request['SONG_ID']);
    		$result = $stmt->execute();
    		$inserId =  $this->conn->insert_id;
    		$stmt->close();
    		if ($result) {
    			$res['id'] = $inserId;
    			return $res;
    		} else {
    			return 1;
    		}
    		
//     	} else {
//     		return 2;
//     	} 
    }
    
    public function deleteWishListSongs( $request ){
    	if ($this->isWishListSongsExists( $request )) {
    		$query = "DELETE FROM ".$this->wishlist_songs." WHERE USER_ID = ? AND SONG_ID = ?";
    		$stmt = $this->conn->prepare($query);
    		$stmt->bind_param("ss", $request['USER_ID'], $request['SONG_ID']);
    		$result = $stmt->execute();
    		$stmt->close();
    		if ($result) {
    			return 1;
    		} else {
    			return 0;
    		}
    	}else{
    		return 2;
    	} 
    }
    
    public function getWishListAlbumsDetails( $userId ){
    	$wishList =array();
		if (empty( $userId )){
    		return $wishList;
    	}
    	$sql_query = "SELECT al.ALBUM_ID, al.ALBUM_NAME, al.ALBUM_LOGO FROM $this->wishlist_albums as wa 
    					JOIN $this->albums as al ON al.ALBUM_ID=wa.ALBUM_ID WHERE wa.USER_ID = ? LIMIT ".LIMIT_COUNT;
    	
    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->bindParam(1, $userId);
    	$stmt->execute();
    	
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$wishList[] = $row; // appends each row to the array
    	}
    	return $wishList;
    	
//     	$stmt = $this->conn->prepare($sql_query);
//     	$stmt->bind_param("s", $userId);
//     	$stmt->execute();
//     	$wishList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
//     	$stmt->close();
    }
    
    private function isWishListAlbumsExists($request) {
    	$num_rows =0;
    	if ( !empty($request['USER_ID']) && !empty($request['ALBUM_ID']) ){
    		$stmt = $this->conn->prepare("SELECT * from ".$this->wishlist_albums." WHERE USER_ID = ? AND ALBUM_ID = ?");
    		$stmt->bind_param("ss",  $request['USER_ID'], $request['ALBUM_ID']);
    		$stmt->execute();
    		$stmt->store_result();
    		$num_rows = $stmt->num_rows;
    		$stmt->close();
    	}
    
    	return $num_rows > 0;
    }
    
    public function createWishListAlbums( $request ){
//     	if (!$this->isWishListAlbumsExists( $request )) {
    		$stmt = $this->conn->prepare("INSERT INTO ".$this->wishlist_albums."(USER_ID, ALBUM_ID) values(?, ?)");
    		$stmt->bind_param("ss",  $request['USER_ID'],$request['ALBUM_ID']);
    		$result = $stmt->execute();
    		$inserId =  $this->conn->insert_id;
    		$stmt->close();
    		if ($result) {
    			$res['id'] = $inserId;
    			return $res;
    		} else {
    			return 1;
    		}
    
//     	} else {
//     		return 2;
//     	}
    }
    
    public function deleteWishListAlbums( $request ){
    
    	if ($this->isWishListAlbumsExists( $request )) {
    		$query = "DELETE FROM ".$this->wishlist_albums." WHERE USER_ID = ? AND ALBUM_ID = ?";
    		$stmt = $this->conn->prepare($query);
    		$stmt->bind_param("ss", $request['USER_ID'], $request['ALBUM_ID']);
    		$result = $stmt->execute();
    		$stmt->close();
    		if ($result) {
    			return 1;
    		} else {
    			return 0;
    		}
    	}else{
    		return 2;
    	}
    }
    
    public function getWishListArtistDetails( $userId ){
    	$wishList =array();
		if (empty( $userId )){
    		return $wishList;
    	}
    	
    	$sql_query = "SELECT art.ARTISTS_ID, art.ARTISTS_USERNAME, type.ARTISTS_TYPE_NAME FROM $this->wishlist_artists as wa 
    					JOIN $this->artists as art ON art.ARTISTS_ID = wa.ARTISTS_ID
    					JOIN ".$this->artists_type." as type ON type.ARTISTS_TYPE_ID = art.ARTISTS_TYPE_ID 
    					WHERE USER_ID = ? LIMIT ".LIMIT_COUNT;
    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->bindParam(1, $userId);
    	$stmt->execute();

    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$wishList[] = $row; // appends each row to the array
    	}
    	return $wishList;
    	
//     	$stmt = $this->conn->prepare($sql_query);
//     	$stmt->bind_param("s", $userId);
//     	$stmt->execute();
//     	$wishList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
//     	$stmt->close();
//     	return $wishList;
    }
    
    private function isWishListArtistExists($request) {
    	$num_rows =0;
    	if ( !empty($request['USER_ID']) && !empty($request['ARTISTS_ID']) ){
    		$stmt = $this->conn->prepare("SELECT * from ".$this->wishlist_artists." WHERE USER_ID = ? AND ARTISTS_ID = ?");
    		$stmt->bind_param("ss",  $request['USER_ID'], $request['ARTISTS_ID']);
    		$stmt->execute();
    		$stmt->store_result();
    		$num_rows = $stmt->num_rows;
    		$stmt->close();
    	}
    
    	return $num_rows > 0;
    }
    
    public function createWishListArtist( $request ){
//     	if (!$this->isWishListArtistExists( $request )) {
    		$stmt = $this->conn->prepare("INSERT INTO ".$this->wishlist_artists."(USER_ID, ARTISTS_ID) values(?, ?)");
    		$stmt->bind_param("ss",  $request['USER_ID'], $request['ARTISTS_ID']);
    		$result = $stmt->execute();
    		$inserId =  $this->conn->insert_id;
    		$stmt->close();
    		if ($result) {
    			$res['id'] = $inserId;
    			return $res;
    		} else {
    			return 1;
    		}
    
//     	} else {
//     		return 2;
//     	}
    }
    
    public function deleteWishListArtist( $request ){
    
    	if ($this->isWishListArtistExists( $request )) {
    		$query = "DELETE FROM ".$this->wishlist_artists." WHERE USER_ID = ? AND ARTISTS_ID = ?";
    		$stmt = $this->conn->prepare($query);
    		$stmt->bind_param("ss", $request['USER_ID'], $request['ARTISTS_ID']);
    		$result = $stmt->execute();
    		$stmt->close();
    		if ($result) {
    			return 1;
    		} else {
    			return 0;
    		}
    	}else{
    		return 2;
    	}
    }
    
    private function isSongHistoryExists($request) {
    	$num_rows =0;
    	if ( !empty($request['USER_ID']) && !empty($request['SONG_ID']) && !empty($request['ALBUM_ID']) ){
    		$stmt = $this->conn->prepare("SELECT * from ".$this->song_history." WHERE USER_ID = ? AND SONG_ID = ? AND ALBUM_ID = ?");
    		$stmt->bind_param("sss",  $request['USER_ID'], $request['SONG_ID'], $request['ALBUM_ID']);
    		$stmt->execute();
    		$stmt->store_result();
    		$num_rows = $stmt->num_rows;
    		$stmt->close();
    	}
    
    	return $num_rows > 0;
    }
    
    private function isSongExists($request) {
    	$num_rows =0;
    	if ( !empty($request['SONG_ID']) ){
    		$stmt = $this->conn->prepare("SELECT SONG_ID from ".$this->songs." WHERE SONG_ID = ?");
    		$stmt->bind_param("s",  $request['SONG_ID']);
    		$stmt->execute();
    		$stmt->store_result();
    		$num_rows = $stmt->num_rows;
    		$stmt->close();
    	}
    
    	return $num_rows > 0;
    }
    
    public function createSongHistory( $request ){
    	if ( $this->isSongExists( $request ) ) {
// 	    	if (!$this->isSongHistoryExists( $request )) {
	    		$stmt = $this->conn->prepare("INSERT INTO ".$this->song_history."(USER_ID, SONG_ID, ALBUM_ID, STATUS) values(?, ?, ?, 1)");
	    		$stmt->bind_param("sss", $request['USER_ID'], $request['SONG_ID'], $request['ALBUM_ID']);
	    		$result = $stmt->execute();
	    		$inserId =  $this->conn->insert_id;
	    		$stmt->close();
	    		if ($result) {
	    			$updateCount = $this->UpdateSongCount( $request['SONG_ID'] );
	    			if ($updateCount){
		    			$res['id'] = $inserId;
		    			return $res;
	    			}else{
	    				return 4;
	    			}
	    		} else {
	    			return 1;
	    		}
// 	    	} else {
// 	    		return 2;
// 	    	}
    	}else{
    		return 3;
    	}
    }
    
    public function UpdateSongCount( $songId ){
    	$result =0;
    	if ( empty( $songId )){
    		return $result;
    	}
    	$songInfo = $this->getSongDetails( $songId );
    	if ( isset( $songInfo['SONG_LISTENED_COUNT'])){
    		$count = $songInfo['SONG_LISTENED_COUNT']+1;
    		$stmt = $this->conn->prepare("UPDATE ".$this->songs." SET SONG_LISTENED_COUNT = ? WHERE SONG_ID = ?");
    		$stmt->bind_param("ss", $count, $songId);
    		$resultInfo = $stmt->execute();
    		$stmt->close();
    		if ($resultInfo) {
    			$result = 1;
    		}
    	}
    	return $result;
    }
    
    public function deleteSongHistory( $request ){
    
    	if ($this->isSongHistoryExists( $request )) {
    		$query = "DELETE FROM ".$this->song_history." WHERE USER_ID = ? AND SONG_ID = ? AND ALBUM_ID = ?";
    		$stmt = $this->conn->prepare($query);
    		$stmt->bind_param("sss", $request['USER_ID'], $request['SONG_ID'], $request['ALBUM_ID']);
    		$result = $stmt->execute();
    		$stmt->close();
    		if ($result) {
    			return 1;
    		} else {
    			return 0;
    		}
    	}else{
    		return 2;
    	}
    }
    public function getUserInspiredRecentListeningSongList( $userId , $limit='' ){
    	$albumInfo =array();
		if (empty( $userId )){
    		return $albumInfo;
    	}
    	$count		= (!empty( $limit )?$limit:LIMIT_COUNT);
    	/*$sql_query = "SELECT sh.USER_ID,song.SONG_ID,song.SONG_NAME, count(sh.SONG_ID) as LISTEN_COUNT FROM ".$this->song_history." As sh
    					JOIN ".$this->songs." as song ON song.SONG_ID=sh.SONG_ID
    					WHERE sh.USER_ID = ? AND song.SONG_IS_PODCAST = 0 GROUP BY sh.SONG_ID ORDER by sh.HISTORY_ID DESC LIMIT ".LIMIT_COUNT;*/

        $sql_query = "SELECT sh.USER_ID,album.ALBUM_ID,album.ALBUM_NAME, album.ALBUM_LOGO, count(sh.ALBUM_ID) as LISTEN_COUNT FROM ".$this->song_history." As sh
                        JOIN ".$this->albums." as album ON album.ALBUM_ID=sh.ALBUM_ID
                        WHERE sh.USER_ID = ? AND album.ALBUM_IS_PODCAST = 0 GROUP BY sh.ALBUM_ID ORDER by LISTEN_COUNT DESC LIMIT ".$count;
    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->bindParam(1, $userId);
    	$stmt->execute();
		
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$albumInfo[] = $row; // appends each row to the array
    	}
//        echo'<pre>';print_r($albumInfo);exit;
    	return $albumInfo;
    }
    
    //This method will return song detail
    public function getSongsInfo( $req ){
    	$cond = array();
    	$param = array();
    	$songInfo = array();
    	
    	$country	= (!empty($req['country'])?$req['country']:'');
    	$songId		= (!empty($req['song_id'])?$req['song_id']:'');
        $albumId    = (!empty($req['album_id'])?$req['album_id']:'');
//     	$podcast	= (isset($req['podcast'])?$req['podcast']:0);
    	$genres		= (isset($req['genres'])?$req['genres']:0);
    	$genresType	= (!empty($req['genresType'])?$req['genresType']:0);
    	$mood		= (isset($req['mood'])?$req['mood']:0);
    	$moodType	= (!empty($req['moodType'])?$req['moodType']:0);
    	$count		= (!empty( $req['limit'])?$req['limit']:LIMIT_COUNT);
        $artist     = (isset($req['artist'])?$req['artist']:0);
        $album      = (!empty($req['album'])?$req['album']:0);
        $rating     = (isset($req['rating'])?$req['rating']:"");

    	$genresNameList = (!empty($genres)?',gen.GENRES_ID,gen.GENRES_NAME':'');
    	$moodNameList = (!empty($mood)?',mood.MOOD_ID,mood.MOOD_NAME':'');
    	$artistNameList = (!empty($artist)?', art.ARTISTS_USERNAME':'');
        $songsAvgRating = (isset($rating)?', AVG(RATING) as SONG_RATING':'');

    	$sql_query = "SELECT song.SONG_ID, song.SONG_NAME, song.SONG_COVER_IMAGE, song.SONG_URL,song.SONG_LISTENED_COUNT,song.HIGH_SONG_URL,song.SONG_COUNTRY $genresNameList $moodNameList $artistNameList $songsAvgRating FROM ".$this->songs." as song";
    	
        if (!empty( $album ) || !empty($artist) ){
            $sql_query .=" JOIN $this->albums as album ON album.ALBUM_ID=song.SONG_ALBUM_ID";
            $cond[] = "album.ALBUM_STATUS=?";
            $param[] = 1;
        }

        if (!empty($artist)){
            $sql_query .=" JOIN $this->artists as art ON art.ARTISTS_ID = album.ALBUM_ARTISTS_ID";
        }

        if (!empty($rating)){
            $sql_query .=" LEFT JOIN $this->song_ratings as rating ON rating.SONG_ID = song.SONG_ID";
        }

    	if (!empty($genres)){
    		$sql_query .=" JOIN $this->genres_types as gen ON gen.GENRES_ID = song.SONG_GENRES_ID";
    		
    		if (!empty( $genresType )){
	    		$cond[] = "song.SONG_GENRES_ID=?";
	    		$param[] = $genresType;
    		}
    	}
    	
    	if (!empty($mood)){
    		$sql_query .=" JOIN $this->mood_types as mood ON mood.MOOD_ID = song.SONG_MOOD_ID";
    	
    		if (!empty( $moodType )){
    			$cond[] = "song.SONG_MOOD_ID=?";
    			$param[] = $moodType;
    		}
    	}
    	
    	if (!empty( $songId )){
    		$cond[] = "song.SONG_ID IN ( $songId )";
//     		$param[] = $songId;
    	}

        if (!empty( $albumId )){
            $cond[] = "song.SONG_ALBUM_ID=?";
            $param[] = $albumId;
        }
    	
    	if(!empty($country)){
    		$cond[] = "song.SONG_COUNTRY = ?";
			$param[] = $country;
    	}
    	
    	if(isset($req['podcast'])){
    		$cond[] = "song.SONG_IS_PODCAST = ?";
    		$param[] = $req['podcast'];
    	}

        if(isset($req['home_tracks']) && $req['home_tracks'] == true ){
            $cond[] = "song.HOME_SONG_LIST = ?";
            $param[] = 1;
        }
    	
    	if (count($cond)){
    		$sql_query .= ' WHERE  ' . implode(' AND ', $cond);
            $sql_query .= ' AND song.SONG_STATUS = 1 ';
        }else{
            $sql_query .= ' WHERE song.SONG_STATUS = 1 ';
        }

        if(isset($req['search']) && !empty($req['search'])){
            $sql_query .= " AND song.SONG_NAME LIKE '%".$req['search']."%' ";
        }

        if(isset($req['artistId']) && !empty($req['artistId'])){
            $sql_query .= " AND art.ARTISTS_ID =".$req['artistId']." ";
        }
        
        $sql_query .= ' AND SONG_STATUS > 0 ';
        
        if( isset($req['order_where']) && isset($req['order_by']) && !empty($count) )
        {
            $sql_query .= " GROUP BY song.SONG_ID ORDER BY ".$req['order_where']." ".$req['order_by']." LIMIT $count";
        }
        elseif (!empty($count)){
    		$sql_query .= " GROUP BY song.SONG_ID ORDER by song.SONG_ID DESC LIMIT $count";
    	}

    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->execute($param);
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$songInfo[] = $row; // appends each row to the array
    	}
    	
    	return $songInfo;
    }
    
    public function getAlbumsInfo( $req ){
    	$cond = array();
    	$param = array();
    	$songInfo = array();
    	 
    	$country	= (!empty($req['country'])?$req['country']:'');
    	$albumId    = (!empty($req['album_id'])?$req['album_id']:'');
    	//     	$podcast	= (isset($req['podcast'])?$req['podcast']:0);
    	$genres		= (isset($req['genres'])?$req['genres']:0);
    	$genresType	= (!empty($req['genresType'])?$req['genresType']:0);
    	$mood		= (isset($req['mood'])?$req['mood']:0);
    	$moodType	= (!empty($req['moodType'])?$req['moodType']:0);
    	$artist		= (isset($req['artist'])?$req['artist']:0);
    	$artistId	= (!empty($req['artistId'])?$req['artistId']:0);
    	$count		= (!empty( $req['limit'])?$req['limit']:LIMIT_COUNT);
    	 
    	$genresNameList = (!empty($genres)?', gen.GENRES_ID,gen.GENRES_NAME':'');
    	$moodNameList = (!empty($mood)?', mood.MOOD_ID,mood.MOOD_NAME':'');
    	$artistNameList = (!empty($artist)?', art.ARTISTS_USERNAME':'');
    	 
    	$sql_query = "SELECT album.ALBUM_ID,album.ALBUM_NAME, album.ALBUM_LOGO,album.ALBUM_ARTISTS_ID, album.ALBUM_COUNTRY $genresNameList $moodNameList $artistNameList
    	FROM ".$this->albums." as album";
    	 
    	if (!empty($genres)){
    		$sql_query .=" JOIN $this->genres_types as gen ON gen.GENRES_ID = album.ALBUM_GENRES_ID";
    
    		if (!empty( $genresType )){
    			$cond[] = "album.ALBUM_GENRES_ID=?";
    			$param[] = $genresType;
    		}
    	}
    	 
    	if (!empty($mood)){
    		$sql_query .=" JOIN $this->mood_types as mood ON mood.MOOD_ID = album.ALBUM_MOOD_ID";
    		 
    		if (!empty( $moodType )){
    			$cond[] = "album.ALBUM_MOOD_ID=?";
    			$param[] = $moodType;
    		}
    	}
    	
    	if (!empty($artist)){
    		$sql_query .=" JOIN $this->artists as art ON art.ARTISTS_ID = album.ALBUM_ARTISTS_ID";
    		 
    		if (!empty( $artistId )){
    			$cond[] = "album.ALBUM_ARTISTS_ID IN($artistId)";
    		}
    	}
    	 
    	if (!empty( $albumId )){
    		$cond[] = "album.ALBUM_ID=?";
    		$param[] = $albumId;
    	}
    	 
    	if(!empty($country)){
    		$cond[] = "album.ALBUM_COUNTRY = ?";
    		$param[] = $country;
    	}
    	 
    	if(isset($req['podcast'])){
    		$cond[] = "album.ALBUM_IS_PODCAST = ?";
    		$param[] = $req['podcast'];
    	}
    	 
    	if (count($cond)){
    		$sql_query .= ' WHERE  ' . implode(' AND ', $cond);
    	}
    	if (!empty($count)){
    		$sql_query .= " ORDER by album.ALBUM_ID DESC LIMIT $count";
    	}

    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->execute($param);
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$songInfo[] = $row; // appends each row to the array
    	}
    	 
    	return $songInfo;
    }
    
    public function getGenresInfo(){
		$albumInfo=array();
    	$sql_query = "SELECT GENRES_ID, GENRES_NAME FROM $this->genres_types";
    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->execute();
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$albumInfo[] = $row; // appends each row to the array
    	}
    	return $albumInfo;
    }
    
    public function getMoodInfo(){
		$albumInfo=array();
    	$sql_query = "SELECT MOOD_ID, MOOD_NAME FROM $this->mood_types";
    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->execute();
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$albumInfo[] = $row; // appends each row to the array
    	}
    	return $albumInfo;
    }
    
    
    public function getSongsHistroyInfo( $req ){
    	$cond = array();
    	$param = array();
    	$songInfo = array();
    	$groupBy = '';
    	
    	$country	= (!empty($req['country'])?$req['country']:'');
    	$song		= (!empty($req['song'])?$req['song']:0);
    	$songId		= (!empty($req['song_id'])?$req['song_id']:'');
    	$album		= (!empty($req['album'])?$req['album']:0);
    	$albumId	= (!empty($req['album_id'])?$req['album_id']:'');
    	//     	$podcast	= (isset($req['podcast'])?$req['podcast']:0);
    	$genres		= (isset($req['genres'])?$req['genres']:0);
    	$genresType	= (!empty($req['genresType'])?$req['genresType']:0);
    	$mood		= (isset($req['mood'])?$req['mood']:0);
    	$moodType	= (!empty($req['moodType'])?$req['moodType']:0);
    	$userId		= (!empty($req['user_id'])?$req['user_id']:0);
    	$count		= (!empty( $req['limit'])?$req['limit']:LIMIT_COUNT);
    	 
    	$genresNameList = (!empty($genres)?',gen.GENRES_ID,gen.GENRES_NAME':'');
    	$moodNameList = (!empty($mood)?',mood.MOOD_ID,mood.MOOD_NAME':'');
    	$songNameList = (!empty($song)?', song.SONG_NAME, song.SONG_COVER_IMAGE, song.SONG_URL, song.SONG_LISTENED_COUNT,song.SONG_COUNTRY':'');
    	$albumNameList = (!empty($album)?', album.ALBUM_NAME, album.ALBUM_LOGO, album.ALBUM_ARTISTS_ID':'');
    	 
    	$sql_query = "SELECT sh.USER_ID, sh.SONG_ID, sh.ALBUM_ID $songNameList $albumNameList $genresNameList $moodNameList
    	FROM ".$this->song_history." as sh ";
    	 
    	
    	if (!empty( $song )){
    		$sql_query .=" JOIN $this->songs as song ON song.SONG_ID=sh.SONG_ID";
    		
    		if (!empty($songId)){
    			$cond[] = "sh.SONG_ID=?";
    			$param[] = $songId;
    		}
    		
    		if (!empty($genres)){
    			$sql_query .=" JOIN $this->genres_types as gen ON gen.GENRES_ID = song.SONG_GENRES_ID";
    		
    			if (!empty( $genresType )){
    				$cond[] = "song.SONG_GENRES_ID=?";
    				$param[] = $genresType;
    			}
    		}
    		
    		if (!empty($mood)){
    			$sql_query .=" JOIN $this->mood_types as mood ON mood.MOOD_ID = song.SONG_MOOD_ID";
    			 
    			if (!empty( $moodType )){
    				$cond[] = "song.SONG_MOOD_ID=?";
    				$param[] = $moodType;
    			}
    		}
    		
    		if(isset($req['podcast'])){
    			$cond[] = "song.SONG_IS_PODCAST = ?";
    			$param[] = $req['podcast'];
    		}
    		
    		$groupBy = "GROUP by sh.SONG_ID";
    	}
    	
    	if (!empty( $album )){
    		$sql_query .=" JOIN $this->albums as album ON album.ALBUM_ID=sh.ALBUM_ID";
    		
    		if (!empty($albumId)){
	    		$cond[] = "sh.ALBUM_ID=?";
	    		$param[] = $albumId;
    		}
    		
    		if (!empty($genres)){
    			$sql_query .=" JOIN $this->genres_types as gen ON gen.GENRES_ID = album.ALBUM_GENRES_ID";
    	
    			if (!empty( $genresType )){
    				$cond[] = "album.ALBUM_GENRES_ID=?";
    				$param[] = $genresType;
    			}
    		}
    	
    		if (!empty($mood)){
    			$sql_query .=" JOIN $this->mood_types as mood ON mood.MOOD_ID = album.ALBUM_MOOD_ID";
    	
    			if (!empty( $moodType )){
    				$cond[] = "album.ALBUM_MOOD_ID=?";
    				$param[] = $moodType;
    			}
    		}
    		
    		if(isset($req['podcast'])){
    			$cond[] = "album.ALBUM_IS_PODCAST = ?";
    			$param[] = $req['podcast'];
    		}
    	
    		$groupBy = "GROUP by sh.ALBUM_ID";
    	}
    	 
    	if (!empty( $userId )){
    		$cond[] = "sh.USER_ID=?";
    		$param[] = $userId;
    	}
    	
    	if (count($cond)){
    		$sql_query .= ' WHERE  ' . implode(' AND ', $cond);
    	}
    	if (!empty($count)){
    		$sql_query .= " $groupBy ORDER by sh.HISTORY_ID DESC LIMIT $count";
    	}

    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->execute($param);
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$songInfo[] = $row; // appends each row to the array
    	}
    	 
    	return $songInfo;
    }
    
    public function getTypeBasedSongList( $genresId,  $limit=''){
		$albumInfo=array();
    	if (empty( $genresId )){
    		return albumInfo;
    	}
    	$count		= (!empty( $limit)?$limit:LIMIT_COUNT);
    
    	$sql_query = "SELECT song.SONG_ID,song.SONG_NAME,song.SONG_COVER_IMAGE, song.SONG_URL, song.SONG_LISTENED_COUNT, gen.GENRES_ID,gen.GENRES_NAME FROM ".$this->songs." As song
    					JOIN ".$this->genres_types." as gen ON gen.GENRES_ID = song.SONG_GENRES_ID
    					WHERE song.SONG_GENRES_ID IN( $genresId ) AND song.SONG_IS_PODCAST = 0 ORDER by song.SONG_ID DESC LIMIT ".$count;

    	$stmt = $this->pdo->prepare($sql_query);
//     	$stmt->bindParam(1, $genresId);
    	$stmt->execute();
    
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$albumInfo[] = $row; // appends each row to the array
    	}
		
    	return $albumInfo;
    }
	
	public function getTypeBasedAlbumList( $genresId,  $limit=''){
    	$albumInfo=array();
		if (empty( $genresId )){
    		return $albumInfo;
    	}
    	$count		= (!empty( $limit)?$limit:LIMIT_COUNT);
    
    	$sql_query = "SELECT album.ALBUM_ID,album.ALBUM_NAME,album.ALBUM_LOGO, gen.GENRES_ID,gen.GENRES_NAME FROM ".$this->albums." As album
    					JOIN ".$this->genres_types." as gen ON gen.GENRES_ID = album.ALBUM_GENRES_ID
        					WHERE album.ALBUM_GENRES_ID IN( $genresId ) AND album.ALBUM_IS_PODCAST = 0 ORDER by album.ALBUM_ID DESC LIMIT ".$count;

    	$stmt = $this->pdo->prepare($sql_query);
    	//     	$stmt->bindParam(1, $genresId);
    	$stmt->execute();
    
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$albumInfo[] = $row; // appends each row to the array
    	}
    
    	return $albumInfo;
    }
    
    /**
     * get popular playlist album
     * this information get from most listend song count in Desc order 
     * @param string $limit
     * @return unknown
     */
    public function popularPlayList( $limit='' ){
		$albumInfo=array();
    	$count		= (!empty( $limit)?$limit:LIMIT_COUNT);
    
    	$sql_query = "SELECT song.SONG_ID,song.SONG_NAME, song.SONG_LISTENED_COUNT,song.SONG_COVER_IMAGE, song.SONG_URL, album.ALBUM_ID,album.ALBUM_NAME, album.ALBUM_LOGO FROM ".$this->songs." As song
    					JOIN ".$this->albums." as album ON album.ALBUM_ID = song.SONG_ALBUM_ID
        					WHERE song.SONG_IS_PODCAST = 0 GROUP BY album.ALBUM_ID ORDER by song.SONG_LISTENED_COUNT DESC LIMIT ".$count;
    
    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->execute();

    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$albumInfo[] = $row; // appends each row to the array
    	}
    
    	return $albumInfo;
    }
    
    public function getSongsPercentageInfo( $req ){
    	$cond = array();
    	$param = array();
    	$songInfo = array();
    
    	$songId		= (!empty($req['song_id'])?$req['song_id']:'');
    	$artistId	= (!empty($req['artist_id'])?$req['artist_id']:'');
    	$count		= (!empty( $req['limit'])?$req['limit']:LIMIT_COUNT);
    
    	$sql_query = "SELECT sp.SONG_ID, sp.ARTIST_ID FROM ".$this->song_percentages." as sp";

    	$groupBy = "";
    	if (!empty( $songId )){
    		$cond[] = "sp.SONG_ID=?";
    		$param[] = $songId;
    	}
    
    	if(!empty($artistId)){
    		$cond[] = "sp.ARTIST_ID IN ( $artistId )";
//     		$param[] = $artistId;
    		$groupBy = "GROUP by sp.SONG_ID";
    	}
    
    	if (count($cond)){
    		$sql_query .= ' WHERE  ' . implode(' AND ', $cond);
    	}
    	if (!empty($count)){
    		$sql_query .= " $groupBy ORDER by sp.PERCENTAGE_ID DESC LIMIT $count";
    	}
    	
    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->execute($param);

    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$songInfo[] = $row; // appends each row to the array
    	}
    
    	return $songInfo;
    }
    
    public function getArtistInfo($req){
    	$cond = array();
    	$param = array();
    	$songInfo = array();
    	
    	$artistId	= (!empty($req['artist_id'])?$req['artist_id']:'');
    	$count		= (!empty( $req['limit'])?$req['limit']:LIMIT_COUNT);
    	
    	$sql_query = "SELECT art.ARTISTS_ID,art.ARTISTS_USERNAME,type.ARTISTS_TYPE_ID, type.ARTISTS_TYPE_NAME
			    	FROM $this->artists as art
			    	JOIN ".$this->artists_type." as type ON type.ARTISTS_TYPE_ID = art.ARTISTS_TYPE_ID";
    	 
    	if(!empty($artistId)){
    		$cond[] = "art.ARTISTS_ID IN ( $artistId )";
    	}
    	
    	if (count($cond)){
    		$sql_query .= ' WHERE  ' . implode(' AND ', $cond);
    	}
    	if (!empty($count)){
    		$sql_query .= " $groupBy ORDER by art.ARTISTS_ID DESC LIMIT $count";
    	}
    	 
    	$stmt = $this->pdo->prepare($sql_query);
    	$stmt->execute($param);
    	
    	while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    		$songInfo[] = $row; // appends each row to the array
    	}
    	
    	return $songInfo;
    }
    
	public function checkRemoteUrlExists( $remoteUrl, $filename ){
   		$url = $remoteUrl.$filename;
//     	$url = 'http://ts.digient.co/backoffice/assets/upload_songs/song_url_1485167350.mp3';
    	
    	$curl = curl_init($url);
    	//don't fetch the actual page, you only want to check the connection is ok
    	curl_setopt($curl, CURLOPT_NOBODY, true);
    	//do request
    	$result = curl_exec($curl);
    	$ret = '';
    	//if request did not fail
    	if ($result !== false) {
    		//if request was ok, check response code
    		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    		if ($statusCode == 200) {
    			$ret = $url;
    		}
    	}
    	curl_close($curl);
    	return $ret;
    }
	//This method will generate a unique api key
   /* private function generateApiKey(){
        return md5(uniqid(rand(), true));
    }*/
/** ---------------------------------------------------------------------------------------------------------------------------------**/	
	
    /**
     * get Home PLaylist
     * @param  int $userId
     * @author Niranjan
     * @return Mixed
     */
    public function getHomePlayList($req){
        
        $sql_query = "SELECT PLAYLIST_ID, USER_ID, PLAYLIST_NAME FROM ".$this->play_list."  LIMIT ".$req['limit'];
        
        $stmt = $this->pdo->prepare($sql_query);
        $stmt->execute();
        $playListInfo = array();
        while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
            $playListInfo[] = $row; // appends each row to the array
        }
        return $playListInfo;
    }
     /**
     * Method will create a new user for Phoen Number and save OTP
     * @author Niranjan 9-Sep-16
     */
    public function createUserByPhoneNumber( $userPhone, $otp ){
        if (! $user_id = $this->isUserPhoneExists($userPhone)) {

                $stmt = $this->conn->prepare("INSERT INTO ".$this->users."(USER_PHONE, USER_OTP, USER_STATUS) values(?, ?, 1)");
                $stmt->bind_param("ss", $userPhone, $otp);
                $result = $stmt->execute();
                $inserId =  $this->conn->insert_id;
                $stmt->close();
                if ($result) {
                    $res['id'] = $inserId;
                    return $res;
                } else {
                    return 1;
                }
            
        } else {
                $stmt = $this->conn->prepare("UPDATE ".$this->users." SET USER_OTP = ? WHERE USER_PHONE = ?");
                $stmt->bind_param("ss", $otp, $userPhone);
                $result = $stmt->execute();
                $stmt->close();
                if ($result) {
                    $res['id'] = $user_id;
                    return $res;
                } else {
                    return 1;
                }
        }
    }

    //Checking whether a phoen number already exist
    private function isUserPhoneExists($userPhone) {
        $stmt = $this->conn->prepare("SELECT USER_ID from ".$this->users." WHERE USER_PHONE = ?");
        echo $stmt->bind_param("s", $userPhone);
        $stmt->execute();
        $stmt->bind_result($userId);
        $stmt->fetch();
        $stmt->close();
        if ( $userId > 0 && $userId != null )
        {
            return $userId;
        }

        return false;
    }

    //Method for user login by OTP
    public function userLoginByOTP($user_id,$otp){

        $stmt = $this->conn->prepare("SELECT * FROM ".$this->users." WHERE USER_ID=? and USER_OTP=?");
        $stmt->bind_param("ss",$user_id,$otp);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows>0;
    }

    //16-sep-2017 
    //@author Niranjan
    /**
     * get home banners Images
     * @return Mixed
     */
    public function homeBanners( $limit='' ){
        $count      = (!empty( $limit)?$limit:LIMIT_COUNT);
    
        $sql_query = "SELECT id,file_name FROM banners ORDER by created_on DESC LIMIT ".$count;
    
        $stmt = $this->pdo->prepare($sql_query);
        $stmt->execute();
        $bannerInfo = [];
        while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
            $bannerInfo[] = $row; // appends each row to the array
        }
    
        return $bannerInfo;
    }

    //24-sep-2017 
    //@author Niranjan
    /**
     * get home banners Images
     * @return Mixed
     */
    public function homePlaylists( $limit='' ){
        $count      = (!empty( $limit)?$limit:LIMIT_COUNT);
    
        $sql_query = "SELECT HOME_PLAYLIST_ID,HOME_PLAYLIST_NAME,HOME_PLAYLIST_COVER_IMAGE FROM home_playlists where HOME_PLAYLIST_STATUS=1 ORDER by HOME_PLAYLIST_CREATED_ON DESC LIMIT ".$count;
    
        $stmt = $this->pdo->prepare($sql_query);
        $stmt->execute();
        $bannerInfo = [];
        while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
            $bannerInfo[] = $row; // appends each row to the array
        }
    
        return $bannerInfo;
    }

    public function getHomePlaylistsSongs($playListId=''){
        if (empty( $playListId)){
            return 0;
        }
        
        $sql_query = "SELECT album.ALBUM_ID, album.ALBUM_NAME, album.ALBUM_LOGO, art.ARTISTS_ID, art.ARTISTS_USERNAME, song.SONG_ID, song.SONG_NAME,song.SONG_COVER_IMAGE, song.SONG_URL,song.HIGH_SONG_URL, pl.HOME_PLAYLIST_ID, pl.HOME_PLAYLIST_NAME
                FROM ".$this->home_play_list_songs." AS ps
                JOIN ".$this->songs." as song ON song.SONG_ID = ps.SONG_ID
                JOIN ".$this->home_play_lists." as pl ON pl.HOME_PLAYLIST_ID=ps.HOME_PLAYLIST_ID
                JOIN ".$this->albums." as album ON album.ALBUM_ID=song.SONG_ALBUM_ID
                JOIN ".$this->artists." as art ON art.ARTISTS_ID=album.ALBUM_ARTISTS_ID
                WHERE ps.HOME_PLAYLIST_ID= ? LIMIT ".LIMIT_COUNT;

        $stmt = $this->pdo->prepare($sql_query);
        $stmt->bindParam(1, $playListId);
        $stmt->execute();
        while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
            $playListInfo[] = $row; // appends each row to the array
        }
        return $playListInfo;
    }

    public function moodPlaylists($moodName=''){
        if (empty( $moodName)){
            return 0;
        }
        
        $sql_query = "SELECT album.ALBUM_ID, album.ALBUM_NAME, album.ALBUM_LOGO, art.ARTISTS_ID, art.ARTISTS_USERNAME, song.SONG_ID, song.SONG_NAME,song.SONG_COVER_IMAGE,song.HIGH_SONG_URL, song.SONG_URL 
                FROM ".$this->songs." AS song 
                JOIN ".$this->mood_types." as mood ON mood.MOOD_ID=song.SONG_MOOD_ID 
                JOIN ".$this->albums." as album ON album.ALBUM_ID=song.SONG_ALBUM_ID 
                JOIN ".$this->artists." as art ON art.ARTISTS_ID=album.ALBUM_ARTISTS_ID
                WHERE mood.MOOD_NAME= ? AND song.SONG_STATUS=1 AND album.ALBUM_STATUS=1 AND art.ARTISTS_STATUS=1 LIMIT ".LIMIT_COUNT;

        $stmt = $this->pdo->prepare($sql_query);
        $stmt->bindParam(1, $moodName);
        $stmt->execute();
        $playListInfo = [];
        while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
            $playListInfo[] = $row; // appends each row to the array
        }
        return $playListInfo;
    }

    public function genresPlaylists($genresName=''){
        if (empty( $genresName)){
            return 0;
        }
        
        $sql_query = "SELECT album.ALBUM_ID, album.ALBUM_NAME, album.ALBUM_LOGO, art.ARTISTS_ID, art.ARTISTS_USERNAME, song.SONG_ID, song.SONG_NAME,song.SONG_COVER_IMAGE,song.HIGH_SONG_URL, song.SONG_URL 
                FROM ".$this->songs." AS song 
                JOIN ".$this->genres_types." as genres ON genres.GENRES_ID=song.SONG_GENRES_ID 
                JOIN ".$this->albums." as album ON album.ALBUM_ID=song.SONG_ALBUM_ID 
                JOIN ".$this->artists." as art ON art.ARTISTS_ID=album.ALBUM_ARTISTS_ID
                WHERE genres.GENRES_NAME= ? AND song.SONG_STATUS=1 AND album.ALBUM_STATUS=1 AND art.ARTISTS_STATUS=1 LIMIT ".LIMIT_COUNT;

        $stmt = $this->pdo->prepare($sql_query);
        $stmt->bindParam(1, $genresName);
        $stmt->execute();
        $playListInfo = [];
        while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
            $playListInfo[] = $row; // appends each row to the array
        }
        return $playListInfo;
    }
 
        //This method will return video details
    public function getVideoInfo( $req ){
        $cond = array();
        $param = array();
        $videoInfo = array();

        $videoId     = (!empty($req['video_id'])?$req['video_id']:'');
        $albumId    = (!empty($req['album_id'])?$req['album_id']:'');
        $count      = (!empty( $req['limit'])?$req['limit']:LIMIT_COUNT);

        $sql_query = "SELECT video.VIDEO_ID, video.VIDEO_NAME, video.VIDEO_COVER_IMAGE, video.VIDEO_URL,video.VIDEO_LISTENED_COUNT,art.ARTISTS_USERNAME,album.ALBUM_NAME,VIDEO_RECOMENDED FROM videos as video";

        $sql_query .=" JOIN $this->albums as album ON album.ALBUM_ID=video.VIDEO_ALBUM_ID";
        $cond[] = "album.ALBUM_STATUS=?";
        $param[] = 1;

        $sql_query .=" JOIN $this->artists as art ON art.ARTISTS_ID = album.ALBUM_ARTISTS_ID";
        
        if (!empty( $videoId )){
            $cond[] = "video.VIDEO_ID IN ( $videoId )";
        }

        if (!empty( $albumId )){
            $cond[] = "video.VIDEO_ALBUM_ID=?";
            $param[] = $albumId;
        }
        
        if (count($cond)){
            $sql_query .= ' WHERE  ' . implode(' AND ', $cond);
            $sql_query .= ' AND video.VIDEO_STATUS = 1 ';
        }else{
            $sql_query .= ' WHERE video.VIDEO_STATUS = 1 ';
        }

        if(isset($req['artistId']) && !empty($req['artistId'])){
            $sql_query .= " AND art.ARTISTS_ID =".$req['artistId']." ";
        }

        if(isset($req['search']) && !empty($req['search'])){
            $sql_query .= " AND video.VIDEO_NAME LIKE '%".$req['search']."%' ";
        }

        if(isset($req['recomended']) && !empty($req['recomended'])){
            $sql_query .= " AND video.VIDEO_RECOMENDED = 1 ";
        }
        
        $sql_query .= ' AND VIDEO_STATUS > 0 ';
        
        if( isset($req['order_where']) && isset($req['order_by']) && !empty($count) )
        {
            $sql_query .= " GROUP BY video.VIDEO_ID ORDER BY video.".$req['order_where']." ".$req['order_by']." LIMIT $count";
        }
        elseif (!empty($count)){
            $sql_query .= " GROUP BY video.VIDEO_ID ORDER by video.VIDEO_ID DESC LIMIT $count";
        }

        $stmt = $this->pdo->prepare($sql_query);
        $stmt->execute($param);
        while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
            $videoInfo[] = $row; // appends each row to the array
        }
        
        return $videoInfo;
    }

    public function incrementListnedCount($request)
    {
        $status = 0;
        if($this->isRowExists($request))
        {
            if($request['table'] == 'songs'){
                $status = $this->UpdateSongCount( $request['table_value'] );
            }
            if($request['table'] == 'videos'){
                $status = $this->UpdateVideoCount( $request['table_value'] );
            }
        }
        return $status;
    }
    private function isRowExists($request) {
        $num_rows =0;

        $stmt = $this->conn->prepare("SELECT * from ".$request['table']." WHERE ".$request['table_id']." = ? ");
        $stmt->bind_param("s", $request['table_value']);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        
        return $num_rows > 0;
    }

    public function UpdateVideoCount( $videoId ){
        $result =0;
        if ( empty( $videoId )){
            return $result;
        }
        $videoInfo = $this->getVideoInfo( $videoId );
        if ( isset( $videoInfo[0]['VIDEO_LISTENED_COUNT'])){
            $count = $videoInfo[0]['VIDEO_LISTENED_COUNT']+1;
            $stmt = $this->conn->prepare("UPDATE videos SET VIDEO_LISTENED_COUNT = ? WHERE VIDEO_ID = ?");
            $stmt->bind_param("ss", $count, $videoId);
            $resultInfo = $stmt->execute();
            $stmt->close();
            if ($resultInfo) {
                $result = 1;
            }
        }
        return $result;
    }
    //9-oct-2017
    public function createWishListVideo( $request ){
        if (!$this->isWishListVideoExists( $request )) {
            $stmt = $this->conn->prepare("INSERT INTO wishlist_videos (USER_ID, VIDEOS_ID) values(?, ?)");
            $stmt->bind_param("ss",  $request['USER_ID'], $request['VIDEO_ID']);
            $result = $stmt->execute();
            $inserId =  $this->conn->insert_id;
            $stmt->close();
            if ($result) {
                $res['id'] = $inserId;
                return $res;
            } else {
                return 1;
            }
                
         } else {
             return 2;
         } 
    }

    private function isWishListVideoExists($request) {
        $num_rows =0;
        if ( !empty($request['USER_ID']) && !empty($request['VIDEO_ID']) ){
            $stmt = $this->conn->prepare("SELECT * from wishlist_videos WHERE USER_ID = ? AND VIDEOS_ID = ?");
            $stmt->bind_param("ss",  $request['USER_ID'], $request['VIDEO_ID']);
            $stmt->execute();
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
        }elseif ( !empty($request['USER_ID']) ){
            $stmt = $this->conn->prepare("SELECT * from wishlist_videos WHERE USER_ID = ?");
            $stmt->bind_param("s",  $request['USER_ID']);
            $stmt->execute();
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
        }
         
        return $num_rows > 0;
    }

    public function deleteWishListVideo( $request ){
        if ($this->isWishListVideoExists( $request )) {
            $query = "DELETE FROM wishlist_videos WHERE USER_ID = ? AND VIDEOS_ID = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ss", $request['USER_ID'], $request['VIDEO_ID']);
            $result = $stmt->execute();
            $stmt->close();
            if ($result) {
                return 1;
            } else {
                return 0;
            }
        }else{
            return 2;
        } 
    }

    //HomePlaylist
    public function createWishListHomePlaylist( $request ){
        if (!$this->isWishListHomePlaylistExists( $request )) {
            $stmt = $this->conn->prepare("INSERT INTO wishlist_home_playlist (USER_ID, HOME_PLAYLIST_ID) values(?, ?)");
            $stmt->bind_param("ss",  $request['USER_ID'], $request['HOME_PLAYLIST_ID']);
            $result = $stmt->execute();
            $inserId =  $this->conn->insert_id;
            $stmt->close();
            if ($result) {
                $res['id'] = $inserId;
                return $res;
            } else {
                return 1;
            }
                
         } else {
             return 2;
         } 
    }

    private function isWishListHomePlaylistExists($request) {
        $num_rows =0;
        if ( !empty($request['USER_ID']) && !empty($request['HOME_PLAYLIST_ID']) ){
            $stmt = $this->conn->prepare("SELECT * from wishlist_home_playlist WHERE USER_ID = ? AND HOME_PLAYLIST_ID = ?");
            $stmt->bind_param("ss",  $request['USER_ID'], $request['HOME_PLAYLIST_ID']);
            $stmt->execute();
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
        }elseif ( !empty($request['USER_ID']) ){
            $stmt = $this->conn->prepare("SELECT * from wishlist_home_playlist WHERE USER_ID = ?");
            $stmt->bind_param("s",  $request['USER_ID']);
            $stmt->execute();
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
        }
         
        return $num_rows > 0;
    }

    public function deleteWishListHomePlaylist( $request ){
        if ($this->isWishListHomePlaylistExists( $request )) {
            $query = "DELETE FROM wishlist_home_playlist WHERE USER_ID = ? AND HOME_PLAYLIST_ID = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ss", $request['USER_ID'], $request['HOME_PLAYLIST_ID']);
            $result = $stmt->execute();
            $stmt->close();
            if ($result) {
                return 1;
            } else {
                return 0;
            }
        }else{
            return 2;
        } 
    }

    public function addSongRating($request)
    {
        $status = 0;

        $num_rows =0;

        $stmt = $this->conn->prepare("SELECT * from song_ratings  WHERE USER_ID = ? AND SONG_ID = ? ");
        $stmt->bind_param("ss", $request['USER_ID'], $request['SONG_ID']);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();

        if($num_rows <= 0)
        {
            $stmt = $this->conn->prepare("INSERT INTO song_ratings (USER_ID, SONG_ID, RATING) values(?, ?, ?)");
            $stmt->bind_param("sss",  $request['USER_ID'], $request['SONG_ID'], $request['RATING']);
            $result = $stmt->execute();
            $inserId =  $this->conn->insert_id;
            $stmt->close();
            if ($result) {
                $res['id'] = $inserId;
                return $res;
            } 
        }else{

            $stmt = $this->conn->prepare("UPDATE song_ratings SET RATING = ? WHERE SONG_ID = ? AND USER_ID = ?");
            $stmt->bind_param("sss", $request['RATING'], $request['SONG_ID'], $request['USER_ID']);
            $resultInfo = $stmt->execute();
            $stmt->close();
            if ($resultInfo) {
                $status = 1;
            }
        }
        return $status;
    }

    public function getWishListHomePlayList( $user_id ){
        $count      = (!empty( $limit)?$limit:LIMIT_COUNT);
    
        $sql_query = "SELECT hp.HOME_PLAYLIST_ID,HOME_PLAYLIST_NAME,HOME_PLAYLIST_COVER_IMAGE FROM home_playlists as hp JOIN wishlist_home_playlist as whp ON whp.HOME_PLAYLIST_ID=hp.HOME_PLAYLIST_ID where HOME_PLAYLIST_STATUS=1 AND whp.USER_ID=".$user_id." ORDER by HOME_PLAYLIST_CREATED_ON DESC LIMIT ".$count;
        $stmt = $this->pdo->prepare($sql_query);
        $stmt->execute();
        $bannerInfo = [];
        while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
            $bannerInfo[] = $row; // appends each row to the array
        }
    
        return $bannerInfo;
    }


    public function getWishListVideoList( $user_id ){
        $videoInfo = array();

        $count      = (!empty( $req['limit'])?$req['limit']:LIMIT_COUNT);

        $sql_query = "SELECT video.VIDEO_ID, video.VIDEO_NAME, video.VIDEO_COVER_IMAGE, video.VIDEO_URL,video.VIDEO_LISTENED_COUNT,art.ARTISTS_USERNAME,album.ALBUM_NAME,VIDEO_RECOMENDED FROM videos as video JOIN wishlist_videos ON video.VIDEO_ID = wishlist_videos.VIDEOS_ID ";

        $sql_query .=" JOIN $this->albums as album ON album.ALBUM_ID=video.VIDEO_ALBUM_ID";
        $cond[] = "album.ALBUM_STATUS=?";
        $param[] = 1;

        $sql_query .=" JOIN $this->artists as art ON art.ARTISTS_ID = album.ALBUM_ARTISTS_ID";
        
        $sql_query .= ' WHERE video.VIDEO_STATUS = 1 ';
        
        $sql_query .= ' AND wishlist_videos.USER_ID = '. $user_id." ";
        
        if (!empty($count)){
            $sql_query .= " GROUP BY video.VIDEO_ID ORDER by video.VIDEO_ID DESC LIMIT $count";
        }
// var_dump($sql_query);exit();
        $stmt = $this->pdo->prepare($sql_query);
        $stmt->execute($param);
        while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
            $videoInfo[] = $row; // appends each row to the array
        }
        
        return $videoInfo;
    }
 
}

?>

