<?php 
error_reporting(E_ALL);
include_once("include/Config.php");
include_once("include/DbHandler.php");
include_once("include/functions.php");
require_once("libs/Slim/Slim.php");
\Slim\Slim::registerAutoloader();
$logWriter = new \Slim\LogWriter(fopen(REST_LOG_PATH, 'a'));
$app = new \Slim\Slim(array('log.writer' => $logWriter));

/**
 * User Registration
 */
$app->post('/register', function() use($app) {
	$response = array();
	$requiredKeys 	= array('email','username','password','gender','dob');
	$requiredValues = array('email','username','password','gender','dob');
	verifyRequiredParams($requiredKeys, $requiredValues, 1001);
	
	$request['USER_EMAIL']   	= $app->request->post('email');
	$request['USER_USERNAME']	= $app->request->post('username');
	$request['USER_PASSWORD']	= $app->request->post('password');
	$gender	  					= strtoupper($app->request->post('gender'));
	$request['USER_DOB']		= date(DATE_FORMAT, strtotime($app->request->post('dob')));
	$securekey       	  = $app->request->post('secure_key');
	
	$request['USER_GENDER'] =0;
	if ( $gender == MALE_TEXT ){
		$request['USER_GENDER'] =1;
	}elseif ( $gender == FEMALE_TEXT ){
		$request['USER_GENDER'] =2;
	}
	
	$app->log->debug("request-register=> ".json_encode($app->request->post()));
	
	if($request["USER_EMAIL"]){
		if(!filter_var($request["USER_EMAIL"],FILTER_VALIDATE_EMAIL)){
			$response["error_code"]= '1001';
			$response["message"]  = "Invalid email address";
			$regResponse=array("register_response"=>$response);
			$app->log->debug(json_encode($regResponse));
			echoResponse($regResponse);
			$app->stop();
		}
	}
	
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		$db		= new DbHandler();
		$res	= $db->createUser( $request );
		if (isset($res['id'])) {
			$response["error_code"] = '1000';
			$response["message"] = "Success";
			$response["user_id"] = $res['id'];
		} else if ($res == 1) {
			$response["error_code"] = '1001';
			$response["message"] = "Failed";
		} else if ($res == 2) {
			$response["error_code"] = '1001';
			$response["message"] = "Sorry, this username already existed";
			$regResponse=array("register_response"=>$response);	
		} else if ($res == 3) {
			$response["error_code"] = '1001';
			$response["message"] = "Sorry, this email already existed";
		}
	}else{
		$response["error_code"]= '1001';
		$response["message"]  = "Invalid request";
	} 	
	
	$app->log->debug("register_response=>".json_encode($regResponse));
	echoResponse(array('register_response'=>$response));
});

/**
 * User Login request
 */
$app->post('/login',function() use ($app){
	$requiredKeys 	= array('username','password');
	$requiredValues = array('username','password');
	verifyRequiredParams($requiredKeys, $requiredValues, 2001);

	$username = $app->request->post('username');
	$password = $app->request->post('password');
	$securekey= $app->request->post('secure_key');

	$app->log->debug("request-login=> ".json_encode($app->request->post()));
	$response = array();
	$db = new DbHandler();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->userLogin($username,$password)){
			$userDetails = $db->getUser($username);
			$response['error_code'] = 2000;
			$response["message"] = "Success";
			$response['user_id'] = $userDetails['USER_ID'];
		}else{
			$response['error_code'] = 2001;
			$response["message"] = "Failed";
		}
	}else{
		$response["error_code"]= 2001;
		$response["message"]  = "Invalid request";
	}

	$app->log->debug("login_response=>".json_encode($regResponse));
	echoResponse(array('login_response'=>$response));
});

/******************************* 3.Your Library Start *********************************/

$app->post('/yourLibrary',function() use ($app){
	$requiredKeys = array('user_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 19001);

	$userId = $app->request->post('user_id');
	$securekey= $app->request->post('secure_key');
	$app->log->debug("request-yourLibrary=> ".json_encode($app->request->post()));
	$response = array();
	$db = new DbHandler();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$songHistoryList = $db->getUserRecentlyPlayedAlbumList( $userId );
			$historyInfo = array();
			if (!empty( $songHistoryList )){
				foreach ($songHistoryList as $albumlist ){
					$historyInfo[] = array(	
											'album_id'		=> $albumlist['ALBUM_ID'],
											'album_name'	=> $albumlist['ALBUM_NAME'],
											'album_logo'	=> (!empty($albumlist['ALBUM_LOGO'])?ALBUM_ASSET_URL.$albumlist['ALBUM_LOGO']:''),
											'artist_name'	=> $albumlist['ARTISTS_ID'],
											'artist_id'		=> $albumlist['ARTISTS_USERNAME']
									);
				}
			}
			
			$playList = $db->getUserPlayList( $userId );
			$playListInfo = array();
			if (!empty( $playList )){
				foreach ($playList as $list ){
					$playListInfo[] = array(
							'playlist_id'	=> $list['PLAYLIST_ID'],
							'playlist_name'	=> $list['PLAYLIST_NAME'],
					);
				}
			}
			
			$response['error_code'] = 19000;
			$response["message"] = "Success";
			$response['recently_played_albums_list'] = $historyInfo;
			$response['playlist_details'] = $playListInfo;
		}else{
			$response['error_code'] = 19001;
			$response["message"] = "Invaild user";
		}
	}else{
		$response["error_code"]= 19001;
		$response["message"]  = "Invalid request";
	}
	$app->log->debug("your_library_response=>".json_encode($regResponse));
	echoResponse(array('your_library_response'=>$response));
});
	
/**
 * 3a)recently played album list
 */
$app->post('/getRecentlyPlayedAlbumsList',function() use ($app){
	$requiredKeys = array('user_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 2001);
	
	$userId = $app->request->post('user_id');
	$securekey= $app->request->post('secure_key');

	$app->log->debug("request-recently_played_albums_list=> ".json_encode($app->request->post()));
	$response = array();
	$db = new DbHandler();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$recentPlayedAlbumList = $db->getUserRecentlyPlayedAlbumList( $userId );
			$albumInfo = array();
			if (!empty( $recentPlayedAlbumList )){
				foreach ($recentPlayedAlbumList as $albumlist ){
					$albumInfo[] = array(	'album_id'		=> $albumlist['ALBUM_ID'], 
											'album_name'	=> $albumlist['ALBUM_NAME'], 
											'album_logo'	=> (!empty($albumlist['ALBUM_LOGO'])?ALBUM_ASSET_URL.$albumlist['ALBUM_LOGO']:''),
											'artist_id'		=> $albumlist['ARTISTS_ID'],
											'artist_name'	=> $albumlist['ARTISTS_USERNAME']
										);
				}
			}
			$response['error_code'] = 3000;
			$response["message"] = "Success";
			$response['list_of_album'] = $albumInfo;
		}else{
			$response['error_code'] = 3001;
			$response["message"] = "Invalid user";
		}
	}else{
		$response["error_code"]= 3001;
		$response["message"]  = "Invalid request";
	}
	$app->log->debug("recently_played_albums_list_response=>".json_encode($regResponse));
	echoResponse(array('recently_played_albums_list_response'=>$response));
});


/**
 * 3b)Playlists Main.
 * user played list details
 */
$app->post('/getUserPlayLists',function() use ($app){
	$requiredKeys 	= array('user_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 4001);

	$userId = $app->request->post('user_id');
	$securekey= $app->request->post('secure_key');
	
	$app->log->debug("request-playList=> ".json_encode($app->request->post()));
	$db = new DbHandler();
	$response = array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$playList = $db->getUserPlayList( $userId );
			$playListInfo = array();
			if (!empty( $playList )){
				foreach ($playList as $list ){
					$playListInfo[] = array(	
											'playlist_id'	=> $list['PLAYLIST_ID'],
											'playlist_name'	=> $list['PLAYLIST_NAME'],
									);
				}
			}
			$response['error_code'] = 4000;
			$response["message"] = "Success";
			$response['playlist_details'] = $playListInfo;
		}else{
			$response['error_code'] = 4001;
			$response["message"] = "Invalid user";
		}
	}else{
		$response["error_code"]= 4001;
		$response["message"]  = "Invalid request";
	}

	$app->log->debug("playlists_response=>".json_encode($regResponse));
	echoResponse(array('playlists_response'=>$response));
});

/**
 * 3b(1))Playlists Songs.
 * user played list songs details
 */
$app->post('/getPlayListSongs',function() use ($app){
	$requiredKeys 	= array('user_id','playlist_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 5001);

	$userId 	= $app->request->post('user_id');
	$playListId = $app->request->post('playlist_id');
	$securekey	= $app->request->post('secure_key');
	$app->log->debug("request-playlist_songs=> ".json_encode($app->request->post()));
	$db = new DbHandler();
	$response = array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$playListSongs = $db->getUserPlayListSongs( $userId, $playListId);
			$playListInfo = array();
			if (!empty( $playListSongs )){
				foreach ($playListSongs as $list ){
					$playListInfo[] = array(
// 											'playlist_id'	=> $list['PLAYLIST_ID'],
// 											'playlist_name'	=> $list['PLAYLIST_NAME'],
											'song_id'		=> $list['SONG_ID'],
											'song_name'		=> $list['SONG_NAME'],
// 											'song_cover_image'	=> (!empty($list['SONG_COVER_IMAGE'])?SONG_ASSET_URL.$list['SONG_COVER_IMAGE']:''),
											'song_url'		=> (!empty($list['SONG_URL'])?SONG_AUDIO_ASSET_URL.$list['SONG_URL']:''),
											'album_id'		=> $list['ALBUM_ID'],
											'album_name'	=> $list['ALBUM_NAME'],
											'album_logo'	=> (!empty($list['ALBUM_LOGO'])?ALBUM_ASSET_URL.$list['ALBUM_LOGO']:'')
// 											'artist_name'	=> $list['ARTISTS_ID'],
// 											'artist_id'		=> $list['ARTISTS_USERNAME']
									);
				}
			}
			$response['error_code'] = 5000;
			$response["message"] = "Success";
			$response['songs_details'] = $playListInfo;
		}else{
			$response['error_code'] = 5001;
			$response["message"] = "Invaild user";
		}
	}else{
		$response["error_code"]= 5001;
		$response["message"]  = "Invalid request";
	}

	$app->log->debug("playlist_songs_response=>".json_encode($regResponse));
	echoResponse(array('playlist_songs_response'=>$response));
});

/**
 * 3c)Playlists Creation
 * 
 */
$app->post('/createPlayList',function() use ($app){
	$requiredKeys 	= array('user_id','playlist_name');
	verifyRequiredParams($requiredKeys, $requiredKeys, 6001);
	
	$request['USER_ID'] 		= $app->request->post('user_id');
	$request['PLAYLIST_NAME']	= $app->request->post('playlist_name');
	$securekey	= $app->request->post('secure_key');
	$app->log->debug("request-createPlayList=> ".json_encode($app->request->post()));
	$db 		= new DbHandler();
	$response	= array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if( $db->isUserIdExists($request['USER_ID']) ){
			$res = $db->createPlayList( $request );
			if (isset($res['id'])) {
				$response["error_code"] 	= 6000;
				$response["message"] 		= "Success";
 				$response["playlist_id"] 	= $res['id'];
// 				$response['playlist_name']	= $request['PLAYLIST_NAME'];
			} else if ($res == 1) {
				$response["error_code"] = 6001;
				$response["message"] = "Failed";
			} else if ($res == 2) {
				$response["error_code"] = 6001;
				$response["message"] = "Sorry, this playlist name already existed";
				$regResponse=array("register_response"=>$response);
			} 
		}else{
			$response['error_code'] = 6001;
			$response["message"] = "Invalid User";
		}
	}else{
		$response["error_code"]= 6001;
		$response["message"]  = "Invalid request";
	}

	$app->log->debug("create_playlist_response=>".json_encode($regResponse));
	echoResponse(array('create_playlist_response'=>$response));
});

/**
 * 3c) Delete Playlists .
 * 
 */
$app->post('/deletePlayList',function() use ($app){
	$requiredKeys = array('user_id', 'playlist_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 7001);
	$request['USER_ID'] 		= $app->request->post('user_id');
// 	$playListName				= $app->request->post('playlist_name');
	$request['PLAYLIST_ID'] 	= $app->request->post('playlist_id');
	$securekey	= $app->request->post('secure_key');
	$app->log->debug("request-deletePlayList=> ".json_encode($app->request->post()));
	$db = new DbHandler();
	$response = array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($request['USER_ID'])){
			$res = $db->deletePlayList( $request );
			if ($res ==1 ){
				$response['error_code'] = 7000;
				$response["message"] = "Success";
// 				$response['playlist_name']	= $playListName;
			}elseif ($res ==2 ){
				$response['error_code'] = 7001;
				$response["message"] = "Record not fount";
			}else{
				$response['error_code'] = 7001;
				$response["message"] = "Failed";
			}
		}else{
			$response['error_code'] = 7001;
			$response["message"] = "Invaild User";
		}
	}else{
		$response["error_code"]= 7001;
		$response["message"]  = "Invalid request";
	}

	$app->log->debug("delete_playlist_response=>".json_encode($regResponse));
	echoResponse(array('delete_playlist_response'=>$response));
});

/**
 * 3e) Add and remove Songs to Playlists
 *
 */
$app->post('/addRemoveSongsToPlayList',function() use ($app){
	$requiredKeys = array('user_id', 'song_id','playlist_id','album_id','playlist_operation');
	verifyRequiredParams($requiredKeys, $requiredKeys, 8001);

	$request['PLAYLIST_ID'] = $app->request->post('playlist_id');
	$request['USER_ID'] 	= $app->request->post('user_id');
	$request['SONG_ID']		= $app->request->post('song_id');
	$request['ALBUM_ID']	= $app->request->post('album_id');
	$operation	= strtoupper( $app->request->post('playlist_operation'));
	$securekey	= $app->request->post('secure_key');
	$app->log->debug("request-addRemoveSongsToPlayList=> ".json_encode($app->request->post()));
	
	$db = new DbHandler();
	$response = array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($request['USER_ID'])){
			if ( $operation == ADD_TEXT){
				$res = $db->createSongPlayList($request);
				if (isset($res['id'])) {
					$response["error_code"] 	= 8000;
					$response["message"] 		= "Success";
// 					$response["playlist_song_id"] 	= $res['id'];
				} else if ($res == 1) {
					$response["error_code"] = 8001;
					$response["message"] = "Failed";
				} else if ($res == 2) {
					$response["error_code"] = 8001;
					$response["message"] = "Sorry, this song play list already existed";
				}
			}elseif ( $operation == REMOVE_TEXT){
				
				$res1 = $db->deleteSongPlayList( $request );
				
				if ( $res1==1 ){
					$response['error_code'] = 8000;
					$response["message"] = "Success";
				}elseif( $res1==2){
					$response['error_code'] = 8001;
					$response["message"] = "Record not fount";
				}else{
					$response['error_code'] = 8001;
					$response["message"] = "Failed";
				}
				$result = array('playlist_delete_songs'=>$response);
			}else{
				$response['error_code'] = 8001;
				$response["message"] = "Invaild playlist operation";
			}
		}else{
			$response['error_code'] = 8001;
			$response["message"] = "Invaild User";
		}
	}else{
		$response["error_code"]= 8001;
		$response["message"]  = "Invalid request";
	}

	$regResponse=array("add_remove_songs_to_playlist_response"=>$response);
	$app->log->debug("add_remove_songs_to_playlist_response=>".json_encode($regResponse));
	
	echoResponse($regResponse);
});

/******************************* 3.Your Library End*********************************/

/******************************* 4.Songs API Start*********************************/

/**
 * 4.Songs API
 *  get songs list
 */
$app->post('/getSongsList',function() use ($app){
	$requiredKeys = array('user_id','album_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 9001);

	$userId = $app->request->post('user_id');
	$albumId = $app->request->post('album_id');
	$securekey= $app->request->post('secure_key');
	$app->log->debug("request-getSongsList=> ".json_encode($app->request->post()));
	$db = new DbHandler();
	$response = array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$req['album_id'] = $albumId;
			$req['limit'] = 50;
			$songsDetails = $db->getSongsInfo( $req );
			$info = array();
			if (!empty( $songsDetails )){
//				$songUrl = 'http://ts.digient.co/backoffice/assets/upload_images/song/';
				foreach ( $songsDetails as $data ){
					$info[] = array(
									'song_id'			=> $data['SONG_ID'],
									'song_name'			=> $data['SONG_NAME'],
// 									'song_cover_image'	=> (!empty($data['SONG_COVER_IMAGE'])?SONG_ASSET_URL.$data['SONG_COVER_IMAGE']:''),
									'song_url'			=> (!empty($data['SONG_URL'])?SONG_AUDIO_ASSET_URL.$data['SONG_URL']:''),
									'listened_count' 	=> $data['SONG_LISTENED_COUNT'],
									// 'album_id'			=> $data['ALBUM_ID'],
									// 'album_name'		=> $data['ALBUM_NAME'],
									/* 'artistInfo' => array(
															'artist_id'		=> $data['ARTISTS_ID'],
															'artist_name'	=> $data['ARTISTS_USERNAME'],
															'artist_type'	=> $data['ARTISTS_TYPE_NAME']
														) */
								);
				}
			}

			$response['error_code'] = 9000;
			$response["message"] = "Success";
			$response['list_of_songs'] = $info;
		}else{
			$response['error_code'] = 9001;
			$response["message"] = "Invaild user";
		}
	}else{
		$response["error_code"]= 9001;
		$response["message"]  = "Invalid request";
	}

	$app->log->debug("get_songs_list_response=>".json_encode($regResponse));
	echoResponse(array('get_songs_list_response'=>$response));
});

/**
 * Album API
 *  get Album list
 */
$app->post('/getAlbumsList',function() use ($app){
	$requiredKeys = array('user_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 9001);

	$userId = $app->request->post('user_id');
	$securekey= $app->request->post('secure_key');
	$app->log->debug("request-getAlbumsList=> ".json_encode($app->request->post()));
	$db = new DbHandler();
	$response = array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$details = $db->getAlbumDetails();
			$info = array();
			if (!empty( $details )){
				foreach ( $details as $data ){
					$info[] = array(
								'album_id'		=> $data['ALBUM_ID'],
								'album_name'	=> $data['ALBUM_NAME'],
								'album_logo'	=> (!empty($data['ALBUM_LOGO'])?ALBUM_ASSET_URL.$data['ALBUM_LOGO']:'')
							);
				}
			}
			$response['error_code'] = 10000;
			$response["message"] = "Success";
			$response['list_of_albums'] = $info;
		}else{
			$response['error_code'] = 10001;
			$response["message"] = "Invalid user";
		}
	}else{
		$response["error_code"]= 10001;
		$response["message"]  = "Invalid request";
	}

	$app->log->debug("get_albums_list_response=>".json_encode($regResponse));
	echoResponse(array('get_albums_list_response'=>$response));
});
	
/**
 *	artist API
 *  get artist list
 */
$app->post('/getArtistsList',function() use ($app){
	$requiredKeys = array('user_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 9001);

	$userId = $app->request->post('user_id');
	$securekey= $app->request->post('secure_key');
	$app->log->debug("request-getArtistsList=> ".json_encode($app->request->post()));
	$db = new DbHandler();
	$response = array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$details = $db->getArtistDetails();
			$info = array();
			if (!empty( $details )){
				foreach ( $details as $data ){
					$info[] = array(
							'artist_id'			=> $data['ARTISTS_ID'],
							'artist_name'		=> $data['ARTISTS_USERNAME'],
							'artist_type'		=> $data['ARTISTS_TYPE_NAME']
					);
				}
			}
			$response['error_code'] = 11000;
			$response["message"] = "Success";
			$response['list_of_artists'] = $info;
		}else{
			$response['error_code'] = 11001;
			$response["message"] = "Invaild user";
		}
	}else{
		$response["error_code"]= 11001;
		$response["message"]  = "Invalid request";
	}

	$app->log->debug("get_artists_list_response=>".json_encode($regResponse));
	echoResponse(array('get_artists_list_response'=>$response));
});

/**
 * 4) get wish list songs 
 */

$app->post('/getUserWishlistSongs',function() use ($app){
	$requiredKeys = array('user_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 10001);

	$userId = $app->request->post('user_id');
	$securekey= $app->request->post('secure_key');
	$app->log->debug("request-getUserWishlistSongs=> ".json_encode($app->request->post()));
	$db = new DbHandler();
	$response = array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$songsDetails = $db->getWishListSongDetails( $userId );
			$info = array();
			if (!empty($songsDetails)){
				foreach ( $songsDetails as $data ) {
					$info[]= array(
									'song_id'		=> $data['SONG_ID'],
									'song_name'		=> $data['SONG_NAME'],
// 									'song_cover_image'	=> (!empty($data['SONG_COVER_IMAGE'])?SONG_ASSET_URL.$data['SONG_COVER_IMAGE']:''),
									'song_url'			=> (!empty($data['SONG_URL'])?SONG_AUDIO_ASSET_URL.$data['SONG_URL']:''),
									'album_id'		=> $data['ALBUM_ID'],
									'album_name'	=> $data['ALBUM_NAME'],
									'album_logo'	=> (!empty($data['ALBUM_LOGO'])?ALBUM_ASSET_URL.$data['ALBUM_LOGO']:'')
							);
				}
			}
			$response['error_code'] = 12000;
			$response["message"] = "Success";
			$response['list_of_songs'] = $info;
		}else{
			$response['error_code'] = 12001;
			$response["message"] = "Invaild User";
		}
	}else{
		$response["error_code"]= 12001;
		$response["message"]  = "Invalid request";
	}

	$app->log->debug("wish_list_songs_response=>".json_encode($regResponse));
	echoResponse(array('wish_list_songs_response'=>$response));
});


/**
 * 4a).Add and remove wish list Songs API
 *  add songs 
 */
$app->post('/addRemoveSongsToWishlist',function() use ($app){
	$requiredKeys = array('user_id', 'song_id', 'album_id','playlist_operation');
	verifyRequiredParams($requiredKeys, $requiredKeys, 13001);
	
	$request['USER_ID'] 	= $app->request->post('user_id');
	$request['SONG_ID']		= $app->request->post('song_id');
	$request['ALBUM_ID']	= $app->request->post('album_id');
	$operation	= strtoupper( $app->request->post('playlist_operation'));
	$securekey	= $app->request->post('secure_key');
	$app->log->debug("request-addRemoveSongsToWishlist=> ".json_encode($app->request->post()));
	$db = new DbHandler();
	$response = array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($request['USER_ID'])){
			if ( $operation == ADD_TEXT){
				$res = $db->createWishListSongs( $request );
				if (isset($res['id'])) {
					$response["error_code"] 	= 13000;
					$response["message"] 		= "Success";
// 					$response["wishlist_song_id"] 	= $res['id'];
				} else if ($res == 1) {
					$response["error_code"] = 13001;
					$response["message"] = "Failed";
				} else if ($res == 2) {
					$response["error_code"] = 13001;
					$response["message"] = "Sorry, this wish list song already existed";
				}
			}elseif ( $operation == REMOVE_TEXT){
				$res1 = $db->deleteWishListSongs( $request );
				if ( $res1==1 ){
					$response['error_code'] = 13000;
					$response["message"] = "Success";
				}elseif( $res1==2){
					$response['error_code'] = 13001;
					$response["message"] = "Record not fount";
				}else{
					$response['error_code'] = 13001;
					$response["message"] = "Failed";
				}
			}else{
				$response['error_code'] = 13001;
				$response["message"] = "Invaild playlist operation";
			}
		}else{
			$response['error_code'] = 13001;
			$response["message"] = "Invaild User";
		}
	}else{
		$response["error_code"]= 13001;
		$response["message"]  = "Invalid request";
	}
	
	$regResponse=array("add_remove_wishlist_songs_response"=>$response);
	$app->log->debug("wishlist_songs=>".json_encode($regResponse));
	echoResponse($regResponse);
});

/**
 * 5) get wish list albums
 */
$app->post('/getUserWishlistAlbums',function() use ($app){
	$requiredKeys = array('user_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 14001);

	$userId = $app->request->post('user_id');
	$securekey= $app->request->post('secure_key');
	$app->log->debug("request-getWishListAlbums=> ".json_encode($app->request->post()));
	$db = new DbHandler();
	$response = array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$details = $db->getWishListAlbumsDetails( $userId );
			$info = array();
			if (!empty($details)){
				foreach ( $details as $data ) {
					$info[]= array(
									'album_id'		=> $data['ALBUM_ID'],
									'album_name'	=> $data['ALBUM_NAME'],
									'album_logo'	=> (!empty($data['ALBUM_LOGO'])?ALBUM_ASSET_URL.$data['ALBUM_LOGO']:'')
							);
				}
			}
			$response['error_code'] = 14000;
			$response["message"] = "Success";
			$response['list_of_album'] = $info;
		}else{
			$response['error_code'] = 14001;
			$response["message"] = "Invaild user";
		}
	}else{
		$response["error_code"]= 14001;
		$response["message"]  = "Invalid request";
	}

	$app->log->debug("wish_list_albums_response=>".json_encode($regResponse));
	echoResponse(array('wish_list_albums_response'=>$response));
});


/**
 * 5a).Add and remove wish list albums API
 */
$app->post('/addRemoveAlbumsToWishlist',function() use ($app){
	$requiredKeys = array('user_id', 'album_id','playlist_operation');
	verifyRequiredParams($requiredKeys, $requiredKeys, 15001);

	$request['USER_ID'] 	= $app->request->post('user_id');
	$request['ALBUM_ID']	= $app->request->post('album_id');
	$operation	= strtoupper( $app->request->post('playlist_operation'));
	$securekey	= $app->request->post('secure_key');
	$app->log->debug("request-addRemoveAlbumsToWishlist=> ".json_encode($app->request->post()));
	$db = new DbHandler();
	$response = array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($request['USER_ID'])){
			if ( $operation == ADD_TEXT){
				$res = $db->createWishListAlbums( $request );
				if (isset($res['id'])) {
					$response["error_code"] 	= 15000;
					$response["message"] 		= "Success";
// 					$response["wishlist_album_id"] 	= $res['id'];
				} else if ($res == 1) {
					$response["error_code"] = 15001;
					$response["message"] = "Failed";
				} else if ($res == 2) {
					$response["error_code"] = 15001;
					$response["message"] = "Sorry, this wish list album already existed";
				}
			}elseif ( $operation == REMOVE_TEXT){
				$res1 = $db->deleteWishListAlbums( $request );
				if ( $res1==1 ){
					$response['error_code'] = 15000;
					$response["message"] = "Success";
				}elseif( $res1==2){
					$response['error_code'] = 15001;
					$response["message"] = "Record not fount";
				}else{
					$response['error_code'] = 15001;
					$response["message"] = "Failed";
				}
			}else{
				$response['error_code'] = 15001;
				$response["message"] = "Invaild playlist operation";
			}
		}else{
			$response['error_code'] = 15001;
			$response["message"] = "Invaild User";
		}
	}else{
		$response["error_code"]= 15001;
		$response["message"]  = "Invalid request";
	}

	$regResponse=array("add_remove_wishlist_albums_response"=>$response);
	$app->log->debug("add_remove_wishlist_albums_response=>".json_encode($regResponse));
	echoResponse($regResponse);
});

/**
 * 6)get wishlist Artist
 */
$app->post('/gerUserWislistArtists',function() use ($app){
	$requiredKeys = array('user_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 16001);

	$userId = $app->request->post('user_id');
	$securekey= $app->request->post('secure_key');
	$app->log->debug("request-gerUserWislistArtists=> ".json_encode($app->request->post()));
	$db = new DbHandler();
	$response = array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$details = $db->getWishListArtistDetails( $userId );
			$info = array();
			if (!empty($details)){
				foreach ( $details as $data ) {
					$info[]= array(
							'artist_id'		=> $data['ARTISTS_ID'],
							'artist_name'	=> $data['ARTISTS_USERNAME'],
							'artist_type'	=> $data['ARTISTS_TYPE_NAME']
					);
				}
			}
			$response['error_code'] = 16000;
			$response["message"] = "Success";
			$response['list_of_artist'] = $info;
		}else{
			$response['error_code'] = 16001;
			$response["message"] = "Invaild user";
		}
	}else{
		$response["error_code"]= 16001;
		$response["message"]  = "Invalid request";
	}

	$app->log->debug("wish_list_artists_response=>".json_encode($regResponse));
	echoResponse(array('wish_list_artists_response'=>$response));
});


/**
 * 6a).Add and remove wish list artist API
 */
$app->post('/addRemoveArtistsToWishlist',function() use ($app){
	$requiredKeys = array('user_id', 'artist_id', 'playlist_operation');
	verifyRequiredParams($requiredKeys, $requiredKeys, 17001);

	$request['USER_ID'] 	= $app->request->post('user_id');
	$request['ARTISTS_ID']	= $app->request->post('artist_id');
	$operation	= strtoupper( $app->request->post('playlist_operation'));
	$securekey	= $app->request->post('secure_key');
	$app->log->debug("request-addRemoveArtistsToWishlist=> ".json_encode($app->request->post()));
	$db = new DbHandler();
	$response = array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($request['USER_ID'])){
			if ( $operation == ADD_TEXT){
				$res = $db->createWishListArtist( $request );
				if (isset($res['id'])) {
					$response["error_code"] 	= 17000;
					$response["message"] 		= "Success";
					$response["wishlist_artist_id"] 	= $res['id'];
				} else if ($res == 1) {
					$response["error_code"] = 17001;
					$response["message"] = "Failed";
				} else if ($res == 2) {
					$response["error_code"] = 17001;
					$response["message"] = "Sorry, this wish list artist already existed";
				}
			}elseif ( $operation == REMOVE_TEXT){
				$res1 = $db->deleteWishListArtist( $request );
				if ( $res1==1 ){
					$response['error_code'] = 17000;
					$response["message"] = "Success";
				}elseif( $res1==2){
					$response['error_code'] = 17001;
					$response["message"] = "Record not fount";
				}else{
					$response['error_code'] = 17001;
					$response["message"] = "Failed";
				}
			}else{
				$response['error_code'] = 17001;
				$response["message"] = "Invaild playlist operation";
				$result = array('wishlist_artist'=>$response);
				
			}
		}else{
			$response['error_code'] = 17001;
			$response["message"] = "Invaild User";
		}
	}else{
		$response["error_code"]= 17001;
		$response["message"]  = "Invalid request";
	}

	$regResponse=array("add_remove_wishlist_artists_response"=>$response);
	$app->log->debug("wishlist_songs=>".json_encode($regResponse));
	echoResponse($regResponse);
});

/**
 * song history
 */
$app->post('/addSongPlayedHistory',function() use ($app){
	$requiredKeys = array('user_id', 'song_id', 'album_id', 'playlist_operation');
	verifyRequiredParams($requiredKeys, $requiredKeys, 18001);

	$request['USER_ID'] 	= $app->request->post('user_id');
	$request['SONG_ID']		= $app->request->post('song_id');
	$request['ALBUM_ID']	= $app->request->post('album_id');
	$operation	= strtoupper( $app->request->post('playlist_operation'));
	$securekey	= $app->request->post('secure_key');
	$app->log->debug("request-addSongPlayedHistory=> ".json_encode($app->request->post()));
	
	$db = new DbHandler();
	$response = array();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($request['USER_ID'])){
			if ( $operation == ADD_TEXT){
				$res = $db->createSongHistory( $request );
				if (isset($res['id'])) {
					$response["error_code"] 	= 18000;
					$response["message"] 		= "Success";
// 					$response["histroy_id"] 	= $res['id'];
				} else if ($res == 1) {
					$response["error_code"] = 18001;
					$response["message"] = "Failed";
				} else if ($res == 2) {
					$response["error_code"] = 18001;
					$response["message"] = "Sorry, this song history already existed";
				}else if ($res == 3) {
					$response["error_code"] = 18001;
					$response["message"] = "song_id does not exists";
				}else if ($res == 4) {
					$response["error_code"] = 18001;
					$response["message"] = "Listen count does not updated ";
				}
		/*	}elseif ( $operation == REMOVE_TEXT){
				$res1 = $db->deleteSongHistory( $request );
				if ( $res1==1 ){
					$response['error_code'] = 18000;
					$response["message"] = "Success";
				}elseif( $res1==2){
					$response['error_code'] = 18001;
					$response["message"] = "Record not fount";
				}else{
					$response['error_code'] = 18001;
					$response["message"] = "Failed";
				}
				$regResponse=array("delete_song_history"=>$response);
				$result = array('delete_song_history'=>$response);*/
			}else{
				$response['error_code'] = 18001;
				$response["message"] = "Invaild playlist operation";
			}
		}else{
			$response['error_code'] = 18001;
			$response["message"] = "Invaild User";
		}
	}else{
		$response["error_code"]= 18001;
		$response["message"]  = "Invalid request";
	}

	$regResponse=array("add_song_played_history_response"=>$response);
	$app->log->debug("add_song_played_history_response=>".json_encode($regResponse));
	echoResponse($regResponse);
});

$app->post('/getHomeScreenList',function() use ($app){
	$requiredKeys = array('user_id','country');
	verifyRequiredParams($requiredKeys, $requiredKeys, 20001);

	$userId = $app->request->post('user_id');
	$country = $app->request->post('country');
	$securekey= $app->request->post('secure_key');
	$app->log->debug("request-getHomeScreenList=> ".json_encode($app->request->post()));
	$response = array();
	$db = new DbHandler();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$songHistoryList = $db->getUserRecentlyPlayedAlbumList( $userId, $limit=50 );
			$historyInfo = array();
			if (!empty( $songHistoryList )){
				foreach ($songHistoryList as $albumlist ){
					$historyInfo[] = array(
											'album_id'		=> $albumlist['ALBUM_ID'],
											'album_name'	=> $albumlist['ALBUM_NAME'],
											'album_logo'	=> (!empty($albumlist['ALBUM_LOGO'])?ALBUM_ASSET_URL.$albumlist['ALBUM_LOGO']:''),
											'artist_name'	=> $albumlist['ARTISTS_USERNAME'],
											'artist_id'		=> $albumlist['ARTISTS_ID']
										);
				}
			}
			$playList = $db->getUserInspiredRecentListeningSongList( $userId, $limit=50 );

			$playListInfo = array();
			if (!empty( $playList )){
				foreach ($playList as $list ){
					$playListInfo[] = array(
											'album_id'		=> $list['ALBUM_ID'],
											'album_name'	=> $list['ALBUM_NAME'],
											'album_logo'	=> (!empty($list['ALBUM_LOGO'])?ALBUM_ASSET_URL.$list['ALBUM_LOGO']:''),
											'listen_count'	=> $list['LISTEN_COUNT']
										);
				}
			}

			$req['country']=$country;
			$req['podcast']=0;
			$req['limit']=50;
			$chartList = $db->getAlbumsInfo( $req );
				
			$chartInfo = array();
			if (!empty( $chartList )){
				foreach ($chartList as $chart ){
					$chartInfo[] = array(
// 										'song_id'			=> $listData['SONG_ID'],
// 										'song_name'			=> $listData['SONG_NAME'],
// 										'song_cover_image'	=> (!empty($listData['SONG_COVER_IMAGE'])?SONG_ASSET_URL.$listData['SONG_COVER_IMAGE']:''),
// 										'song_url'			=> (!empty($listData['SONG_URL'])?SONG_AUDIO_ASSET_URL.$listData['SONG_URL']:''),
										'album_id'		=> $chart['ALBUM_ID'],
										'album_name'	=> $chart['ALBUM_NAME'],
										'album_logo'	=> (!empty($chart['ALBUM_LOGO'])?ALBUM_ASSET_URL.$chart['ALBUM_LOGO']:''),
										'country'		=> $chart['ALBUM_COUNTRY']
								);
				}
			}
			
			$reqInfo['podcast']=0;
			$reqInfo['limit']=50;
			$newRelease = $db->getAlbumsInfo( $reqInfo );
				
			$newReleaseInfo = array();
			if (!empty( $newRelease )){
				foreach ($newRelease as $new ){
					$newReleaseInfo[] = array(
												'album_id'		=> $new['ALBUM_ID'],
												'album_name'	=> $new['ALBUM_NAME'],
												'album_logo'	=> (!empty($new['ALBUM_LOGO'])?ALBUM_ASSET_URL.$new['ALBUM_LOGO']:'')
											);
				}
			}
				
			$getGenresInfo = $db->getGenresInfo(  );
			$genresInfo = array();
			if (!empty( $getGenresInfo )){
				foreach ($getGenresInfo as $genres ){
					$genresInfo[] = array(
											'genres_id'		=> $genres['GENRES_ID'],
											'genres_name'	=> $genres['GENRES_NAME'],
										);
				}
			}
			
			$getMoodInfo = $db->getMoodInfo(  );
			$moodInfo = array();
			if (!empty( $getMoodInfo )){
				foreach ($getMoodInfo as $genres ){
					$moodInfo[] = array(
										'mood_id'	=> $genres['MOOD_ID'],
										'mood_name'	=> $genres['MOOD_NAME'],
									);
				}
			}

			$popularPlayList = $db->popularPlayList( $limit=50 );
			
			$popularInfo = array();
			if (!empty( $popularPlayList )){
				foreach ($popularPlayList as $popular ){
					$popularInfo[] = array(
											'album_id'		=> $popular['ALBUM_ID'],
											'album_name'	=> $popular['ALBUM_NAME'],
											'album_logo'	=> (!empty($popular['ALBUM_LOGO'])?ALBUM_ASSET_URL.$popular['ALBUM_LOGO']:'')
									);
				}
			}
			
			$response['error_code'] = 20000;
			$response["message"] = "Success";
			$response['recently_played_albums_list'] = $historyInfo;
			$response['inspired_by_your_recent_listening'] = $playListInfo;
			$response['charts'] = $chartInfo;
			$response['new_release'] = $newReleaseInfo;
			$response['genres'] = $genresInfo;
			$response['mood'] = $moodInfo;
			$response['popular_playlist'] = $popularInfo;
		}else{
			$response['error_code'] = 20001;
			$response["message"] = "Invaild user";
		}
	}else{
		$response["error_code"]= 20001;
		$response["message"]  = "Invalid request";
	}
	$app->log->debug("your_home_screen_response=>".json_encode($regResponse));
	echoResponse(array('your_home_screen_response'=>$response));
});

$app->post('/getBrowseList',function() use ($app){
	$requiredKeys = array('user_id','country');
	verifyRequiredParams($requiredKeys, $requiredKeys, 21001);

	$userId = $app->request->post('user_id');
	$country = $app->request->post('country');
	$securekey= $app->request->post('secure_key');
	$app->log->debug("request-getBrowseList=> ".json_encode($app->request->post()));
	$response = array();
	$db = new DbHandler();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$req['country']=$country;
			$req['podcast']=0;
			$req['limit']=50;
			$chartList = $db->getAlbumsInfo( $req );
				
			$chartInfo = array();
			if (!empty( $chartList )){
				foreach ($chartList as $chart ){
					$chartInfo[] = array(
// 										'song_id'			=> $listData['SONG_ID'],
// 										'song_name'			=> $listData['SONG_NAME'],
// 										'song_cover_image'	=> (!empty($listData['SONG_COVER_IMAGE'])?SONG_ASSET_URL.$listData['SONG_COVER_IMAGE']:''),
// 										'song_url'			=> (!empty($listData['SONG_URL'])?SONG_AUDIO_ASSET_URL.$listData['SONG_URL']:''),
										'album_id'		=> $chart['ALBUM_ID'],
										'album_name'	=> $chart['ALBUM_NAME'],
										'album_logo'	=> (!empty($chart['ALBUM_LOGO'])?ALBUM_ASSET_URL.$chart['ALBUM_LOGO']:''),
										'country'		=> $chart['ALBUM_COUNTRY']
								);
				}
			}

			$reqInfo['podcast']=0;
			$reqInfo['limit']=50;
			$newRelease = $db->getAlbumsInfo( $reqInfo );
			
			$newReleaseInfo = array();
			if (!empty( $newRelease )){
				foreach ($newRelease as $new ){
					$newReleaseInfo[] = array(
												'album_id'		=> $new['ALBUM_ID'],
												'album_name'	=> $new['ALBUM_NAME'],
												'album_logo'	=> (!empty($new['ALBUM_LOGO'])?ALBUM_ASSET_URL.$new['ALBUM_LOGO']:'')
											);
				}
			}
			
			$request['podcast']=1;
			$request['limit']=50;
			$podcastsSongList = $db->getSongsInfo( $request );
			$podcastsInfo = array();
			if (!empty( $podcastsSongList )){
				foreach ($podcastsSongList as $podcast ){
					$podcastsInfo[] = array(
											'song_id'			=> $podcast['SONG_ID'],
											'song_name'			=> $podcast['SONG_NAME'],
// 											'song_cover_image'	=> (!empty($podcast['SONG_COVER_IMAGE'])?SONG_ASSET_URL.$podcast['SONG_COVER_IMAGE']:''),
											'song_url'			=> (!empty($podcast['SONG_URL'])?SONG_AUDIO_ASSET_URL.$podcast['SONG_URL']:''),
											'listened_count'	=> $podcast['SONG_LISTENED_COUNT'],
											'country'			=> $podcast['SONG_COUNTRY']
										);
				}
			}
			
			/** discover **/
			$discoverInfo = array();
			$genRequest['user_id']=$userId;
			$genRequest['podcast']=0;
			$genRequest['song']=1;
			$genRequest['genres']=1;
			$genRequest['limit']=50;
			$songList = $db->getSongsHistroyInfo( $genRequest );/** user listen most genres list */
			$genresId = array();
			if (!empty( $songList )){
				foreach ($songList as $list ){
					$genresId[] = $list['GENRES_ID'];
				}
			}
			
			$count = (!empty($genresId)? count($genresId):'');
			if (!empty($count)){
				$in = implode(',',$genresId);
				$discoverInfoSongList = $db->getTypeBasedAlbumList( $in, $limit=50 );

				if (!empty( $discoverInfoSongList )){
					foreach ($discoverInfoSongList as $discover ){
						$discoverInfo['recommendations'][] = array(
																'album_id'			=> $discover['ALBUM_ID'],
																'album_name'		=> $discover['ALBUM_NAME'],
																'album_logo'		=> (!empty($discover['ALBUM_LOGO'])?ALBUM_ASSET_URL.$discover['ALBUM_LOGO']:''),
																'genres_id'			=> 'G'.$discover['GENRES_ID'],
																'genres_name'		=> $discover['GENRES_NAME']
															);
					}
				}
			}
			
			$popularPlayList = $db->popularPlayList( $limit=50 ); /** song listen count based on most played album info */
			$popularInfo = array();
			if (!empty( $popularPlayList )){
				foreach ($popularPlayList as $popular ){
					$discoverInfo['most_played_albums_list'][] = array(
																'album_id'		=> $popular['ALBUM_ID'],
																'album_name'	=> $popular['ALBUM_NAME'],
																'album_logo'	=> (!empty($popular['ALBUM_LOGO'])?ALBUM_ASSET_URL.$popular['ALBUM_LOGO']:'')
															);
				}
			}
			
			$artReq['user_id']=$userId;
			$artReq['podcast']=0;
			$artReq['album']=1;
			$artReq['limit']=50;
			$artistList = $db->getSongsHistroyInfo( $artReq );/** user listen most artist list */
			$artistId = array();
			if (!empty( $artistList )){
				foreach ($artistList as $art ){
					$artistId[] = $art['ALBUM_ARTISTS_ID'];
				}
			}
			
			$countArt = (!empty($artistId)? count($artistId):'');
			if (!empty($countArt)){
				$artistIdList = implode(',',$artistId);
				$artReq1['user_id']=$userId;
				$artReq1['podcast']=0;
				$artReq1['artist']=1;
				$artReq1['artistId']=$artistIdList;
				$artReq1['limit']=50;
				$discoverArtistList = $db->getAlbumsInfo( $artReq1 );
				if (!empty( $discoverArtistList )){
					foreach ($discoverArtistList as $artistList ){
						$discoverInfo['listen_to_artist'][] = array(
																	'album_id'			=> $artistList['ALBUM_ID'],
																	'album_name'		=> $artistList['ALBUM_NAME'],
																	'album_logo'		=> (!empty($artistList['ALBUM_LOGO'])?ALBUM_ASSET_URL.$artistList['ALBUM_LOGO']:''),
																	'artist_id'			=> $artistList['ALBUM_ARTISTS_ID'],
																	'artist_name'		=> $artistList['ARTISTS_USERNAME']
															);
					}
				}
			}
			
			/** discover End */
			
			$getGenresInfo = $db->getGenresInfo(  );
			$genresInfo = array();
			if (!empty( $getGenresInfo )){
				foreach ($getGenresInfo as $genres ){
					$genresInfo[] = array(
											'genres_id'		=> 'G'.$genres['GENRES_ID'],
											'genres_name'	=> $genres['GENRES_NAME'],
										);
				}
			}
			
			$getMoodInfo = $db->getMoodInfo(  );
			$moodInfo = array();
			if (!empty( $getMoodInfo )){
				foreach ($getMoodInfo as $genres ){
					$moodInfo[] = array(
										'mood_id'	=> 'M'.$genres['MOOD_ID'],
										'mood_name'	=> $genres['MOOD_NAME'],
									);
				}
			}
			
			$popularPlayList = $db->popularPlayList( $limit=50 );
			$popularInfo = array();
			if (!empty( $popularPlayList )){
				foreach ($popularPlayList as $popular ){
					$popularInfo[] = array(
											'album_id'		=> $popular['ALBUM_ID'],
											'album_name'	=> $popular['ALBUM_NAME'],
											'album_logo'	=> (!empty($popular['ALBUM_LOGO'])?ALBUM_ASSET_URL.$popular['ALBUM_LOGO']:'')
										);
				}
			}
			
			$response['error_code'] = 21000;
			$response["message"] = "Success";
			$response['most_played_albums_list'] = $popularInfo;
			$response['charts'] = $chartInfo;
			$response['new_release'] = $newReleaseInfo;
			$response['podcasts'] = $podcastsInfo;
			$response['discover'] = $discoverInfo;
			$response['genres'] = $genresInfo;
			$response['mood'] = $moodInfo;
		}else{
			$response['error_code'] = 21001;
			$response["message"] = "Invaild user";
		}
	}else{
		$response["error_code"]= 21001;
		$response["message"]  = "Invalid request";
	}
	$app->log->debug("browse_list_response=>".json_encode($regResponse));
	echoResponse(array('browse_list_response'=>$response));
});

$app->post('/getTypeBasedAlbumList',function() use ($app){
	$requiredKeys = array('user_id','type','type_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 22001);

	$userId	= $app->request->post('user_id');
	$type 	= $app->request->post('type');
	$typeId	= $app->request->post('type_id');
	$securekey	= $app->request->post('secure_key');
	$app->log->debug("request-getTypeBasedAlbumList=> ".json_encode($app->request->post()));
	$response = array();
	$db = new DbHandler();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$typeInfo = array();
			switch ($type) {
				case 1:
					$genRequest['podcast']=0;
					$genRequest['genres']=1;
					$genRequest['artist']=1;
					$genRequest['genresType']=substr($typeId, 1);
					$genRequest['limit']=50;
					$genresSongList = $db->getAlbumsInfo( $genRequest );
					if (!empty( $genresSongList )){
						foreach ($genresSongList as $genres ){
							$typeInfo[] = array(
												'album_id'		=> $genres['ALBUM_ID'],
												'album_name'	=> $genres['ALBUM_NAME'],
												'album_logo'	=> (!empty($genres['ALBUM_LOGO'])?ALBUM_ASSET_URL.$genres['ALBUM_LOGO']:''),
												'artist_name'	=> $genres['ARTISTS_USERNAME'],
												'genres_id'		=> $genres['GENRES_ID'],
												'genres_name'	=> $genres['GENRES_NAME']
											);
						}
					}
					break;
				case 2:
					$moodRequest['podcast']=0;
					$moodRequest['mood']=1;
					$moodRequest['moodType']= substr($typeId, 1);
					$moodRequest['artist']=1;
					$moodRequest['limit']=50;
					$moodSongList = $db->getAlbumsInfo( $moodRequest );
					if (!empty( $moodSongList )){
						foreach ($moodSongList as $mood ){
							$typeInfo[] = array(
												'album_id'		=> $mood['ALBUM_ID'],
												'album_name'	=> $mood['ALBUM_NAME'],
												'album_logo'	=> (!empty($mood['ALBUM_LOGO'])?ALBUM_ASSET_URL.$mood['ALBUM_LOGO']:''),
												'artist_name'	=> $mood['ARTISTS_USERNAME'],
												'mood_id'		=> $mood['MOOD_ID'],
												'mood_name'		=> $mood['MOOD_NAME']
												);
						}
					}
					break;
				default:
					$typeInfo = array();
			}
			
			
			$response['error_code'] = 22000;
			$response["message"] = "Success";
			$response['album_list'] = $typeInfo;
		}else{
			$response['error_code'] = 22001;
			$response["message"] = "Invaild user";
		}
	}else{
		$response["error_code"]= 22001;
		$response["message"]  = "Invalid request";
	}
	$app->log->debug("type_based_album_list_response=>".json_encode($regResponse));
	echoResponse(array('type_based_album_list_response'=>$response));
});

$app->post('/goToAlbum',function() use ($app){
	$requiredKeys = array('user_id','song_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 23001);

	$userId	= $app->request->post('user_id');
	$songId	= $app->request->post('song_id');
	$securekey	= $app->request->post('secure_key');
	$app->log->debug("request-goToAlbum=> ".json_encode($app->request->post()));
	$response = array();
	$db = new DbHandler();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$typeInfo = array();
			$albumInfo = array();
			$songDataList = $db->getSongDetails( $songId );
			if (isset($songDataList['SONG_ALBUM_ID']) && !empty( $songDataList['SONG_ALBUM_ID'])){
				$request['podcast']=0;
				$request['album_id']=$songDataList['SONG_ALBUM_ID'];
				$request['limit']=50;
				$albumList = $db->getAlbumsInfo( $request );
				if (!empty( $albumList[0] )){
					$listData = $albumList[0];
					$albumInfo= array(
										'album_id'		=> $listData['ALBUM_ID'],
										'album_name'	=> $listData['ALBUM_NAME'],
										'album_logo'	=> (!empty($listData['ALBUM_LOGO'])?ALBUM_ASSET_URL.$listData['ALBUM_LOGO']:'')
										);
				}
				
				$songList = $db->getSongsInfo( $request );
				if (!empty( $songList )){
					foreach ($songList as $list ){
						$typeInfo[] = array(
											'song_id'			=> $list['SONG_ID'],
											'song_name'			=> $list['SONG_NAME'],
// 	 										'song_cover_image'	=> (!empty($list['SONG_COVER_IMAGE'])?SONG_ASSET_URL.$list['SONG_COVER_IMAGE']:''),
											'song_url'			=> (!empty($list['SONG_URL'])?SONG_AUDIO_ASSET_URL.$list['SONG_URL']:'')
										);
					}
				}
			}
			$response['error_code'] = 23000;
			$response["message"] = "Success";
			$response['album_info'] = $albumInfo;
			$response['song_list'] = $typeInfo;
		}else{
			$response['error_code'] = 23001;
			$response["message"] = "Invaild user";
		}
	}else{
		$response["error_code"]= 23001;
		$response["message"]  = "Invalid request";
	}
	$app->log->debug("go_to_album_response=>".json_encode($regResponse));
	echoResponse(array('go_to_album_response'=>$response));
});
	
$app->post('/goToArtist',function() use ($app){
	$requiredKeys = array('user_id','song_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 24001);

	$userId	= $app->request->post('user_id');
	$songId	= $app->request->post('song_id');
	$securekey	= $app->request->post('secure_key');
	$app->log->debug("request-goToArtist=> ".json_encode($app->request->post()));
	$response = array();
	$db = new DbHandler();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$typeInfo = array();
			$albumInfo = array();
			$songIdList = array();
			$artistInfo = array();
			
			$req['song_id']	=$songId;
			$req['limit']	=50;
			$songPerDataList = $db->getSongsPercentageInfo( $req );
			
			if (!empty( $songPerDataList )){
				$artistId = array();
				foreach ($songPerDataList as $percentage ){
					$artistId[] = $percentage['ARTIST_ID'];
				} 
// 				$artistId = array(1,2);
				if (!empty( $artistId )){
					$artistIdList = implode(', ', $artistId);
					
					$request['artist_id']=$artistIdList;
					$request['limit']=50;
					$artistList = $db->getArtistInfo( $request );
					
					if (!empty( $artistList )){
						foreach ( $artistList as $art ) {
							$artistInfo[]= array(
												'artist_id'		=> $art['ARTISTS_ID'],
												'artist_name'	=> $art['ARTISTS_USERNAME'],
												'artist_type'	=> $art['ARTISTS_TYPE_NAME']
											);
						}
					}
					
					$req1['artist_id']=$artistIdList;
					$req1['limit']	=50;
					$songPerDataList1 = $db->getSongsPercentageInfo( $req1 );
					if (!empty( $songPerDataList1 )){
						foreach ($songPerDataList1 as $percentage1 ){
							$songIdList[] = $percentage1['SONG_ID'];
								
						}
					}
				}
			}
			
			if (!empty( $songIdList )){
				$songIdListInfo = implode(', ', $songIdList);
				$request1['podcast']=0;
				$request1['song_id']=$songIdListInfo;
				$request1['limit']=50;
				$songList = $db->getSongsInfo( $request1 );
				if (!empty( $songList )){
					foreach ($songList as $list ){
						$typeInfo[] = array(
								'song_id'			=> $list['SONG_ID'],
								'song_name'			=> $list['SONG_NAME'],
// 	 							'song_cover_image'	=> (!empty($list['SONG_COVER_IMAGE'])?SONG_ASSET_URL.$list['SONG_COVER_IMAGE']:''),
								'song_url'			=> (!empty($list['SONG_URL'])?SONG_AUDIO_ASSET_URL.$list['SONG_URL']:'')
						);
					}
				}
			}
			$response['error_code'] = 24000;
			$response["message"] = "Success";
			$response['artist_info'] = $artistInfo;
			$response['song_list'] = $typeInfo;
		}else{
			$response['error_code'] = 24001;
			$response["message"] = "Invaild user";
		}
	}else{
		$response["error_code"]= 24001;
		$response["message"]  = "Invalid request";
	}
	$app->log->debug("go_to_artist_response=>".json_encode($regResponse));
	echoResponse(array('go_to_artist_response'=>$response));
});
	
	

/*$app->post('/getTypeBasedSongList',function() use ($app){
	$requiredKeys = array('user_id','type','type_id');
	verifyRequiredParams($requiredKeys, $requiredKeys, 22001);

	$userId	= $app->request->post('user_id');
	$type 	= $app->request->post('type');
	$typeId	= $app->request->post('type_id');
	$securekey	= $app->request->post('secure_key');
	$app->log->debug("request-getTypeBsedSongList=> ".json_encode($app->request->post()));
	$response = array();
	$db = new DbHandler();
	if(validateParamsAPISecret(SECURE_KEY,$securekey)) {
		if($db->isUserIdExists($userId)){
			$typeInfo = array();
			switch ($type) {
				case 1:
					$genRequest['podcast']=0;
					$genRequest['genres']=1;
					$genRequest['genresType']=$typeId;
					$genRequest['limit']=50;
					$genresSongList = $db->getSongsInfo( $genRequest );
					if (!empty( $genresSongList )){
						foreach ($genresSongList as $genres ){
							$typeInfo[] = array(
												'song_id'			=> $genres['SONG_ID'],
												'song_name'			=> $genres['SONG_NAME'],
// 												'song_cover_image'	=> (!empty($genres['SONG_COVER_IMAGE'])?SONG_ASSET_URL.$genres['SONG_COVER_IMAGE']:''),
												'song_url'			=> (!empty($genres['SONG_URL'])?SONG_AUDIO_ASSET_URL.$genres['SONG_URL']:''),
												'listened_count'	=> $genres['SONG_LISTENED_COUNT'],
												'genres_id'			=> $genres['GENRES_ID'],
												'genres_name'		=> $genres['GENRES_NAME']
											);
						}
					}
					break;
				case 2:
					$moodRequest['podcast']=0;
					$moodRequest['mood']=1;
					$moodRequest['moodType']=$typeId;
					$moodRequest['limit']=50;
					$moodSongList = $db->getSongsInfo( $moodRequest );
					if (!empty( $moodSongList )){
						foreach ($moodSongList as $mood ){
							$typeInfo[] = array(
												'song_id'			=> $mood['SONG_ID'],
												'song_name'			=> $mood['SONG_NAME'],
// 												'song_cover_image'	=> (!empty($mood['SONG_COVER_IMAGE'])?SONG_ASSET_URL.$mood['SONG_COVER_IMAGE']:''),
												'song_url'			=> (!empty($mood['SONG_URL'])?SONG_AUDIO_ASSET_URL.$mood['SONG_URL']:''),
												'listened_count'	=> $mood['SONG_LISTENED_COUNT'],
												'mood_id'			=> $mood['MOOD_ID'],
												'mood_name'			=> $mood['MOOD_NAME']
												);
						}
					}
					break;
				default:
					$typeInfo = array();
			}
			
			
			$response['error_code'] = 22000;
			$response["message"] = "Success";
			$response['song_list'] = $typeInfo;
		}else{
			$response['error_code'] = 22001;
			$response["message"] = "Invaild user";
		}
	}else{
		$response["error_code"]= 22001;
		$response["message"]  = "Invalid request";
	}
	$app->log->debug("type_based_song_list_response=>".json_encode($regResponse));
	echoResponse(array('type_based_song_list_response'=>$response));
});*/

$app->run();
?>