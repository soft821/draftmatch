<?php

namespace App\Http\Controllers\Admin\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\HttpMessage;
use Illuminate\Database\QueryException;
use App\Http\HttpResponse;
use App\Http\HttpStatus;
use App\Http\Requests;
use JWTAuth;
use JWTAuthException;
use Mockery\Exception;
use Validator;
use Illuminate\Validation\Rule;
use DB;
use App\Post;
use App\User;
use App\Category;
use App\Comment;
use App\Common\Consts\User\UserStatusConsts;

class PostsController extends Controller
{
		public function list(Request $request){
			try {
	            $user = JWTAuth::toUser($request->token);
	        }
	        catch (Exception $exception)
	        {
	            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$BLOG_ERROR_RETRIVE_POST,
	                $exception->getMessage());
	        }

	        if ($user->status === UserStatusConsts::$BLOCKED)
	        {
	            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
	                HttpMessage::$USER_BLOCKED_OPERATION);
	        }
	 
	        try {
	             $posts = DB::table('posts')
	             	// ->where('posts.is_publish', '=', true)
                    ->join('users', 'users.id', '=', 'posts.author')
                    ->join('categories', 'categories.id', '=', 'posts.category')
                    ->select('posts.id', 'posts.is_publish as publishStatus', 'categories.name as categoryName', 'posts.title', 'posts.description', 'users.name as blogerName','posts.image', 'posts.color', 'posts.sections','posts.updated_at')
                    ->orderby('posts.id', 'asc')
                    ->get();
                 foreach ($posts as $post) {
                 	$post_eloquent = Post::find($post->id);
                 	$comments = $post_eloquent->comments;
                 	$post->comments = $comments;
                 }
	        }
	        catch (QueryException $e) {
	            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$BLOG_ERROR_RETRIVE_POST, $e->getMessage());
	        }
	        catch (Exception $e) {
	            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$BLOG_ERROR_RETRIVE_POST, $e->getMessage());
	        }
	        // dd($posts);
	        // $section = json_decode($posts[0]->sections);
	        // dd($section[0]->image);
	        return HttpResponse::ok(HttpMessage::$BLOG_FOUND, $posts);
		}
		public function getDetails($post_id, Request $request)
		{
	        try {
	             $post = DB::table('posts')
	                ->where('posts.id', '=', $post_id)
	             	// ->where('posts.is_publish', '=', true)
                    ->join('users', 'users.id', '=', 'posts.author')
                    ->join('categories', 'categories.id', '=', 'posts.category')
                    ->select('posts.*', 'categories.name as categoryName', 'users.name')
                    ->orderby('posts.updated_at', 'asc')
                    ->get();
                 if ($post->count() > 0){
                 	$post_eloquent = Post::find($post_id);
                 	$comments = $post_eloquent->comments;
                 	$post->comments = $comments;

                 	$post[0]->sections= json_decode($post[0]->sections);
                 }
                 else{
                 	 return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$BLOG_ERROR_RETRIVE_POST,
	                HttpMessage::$BLOG_ERROR_RETRIVE_POST);
                 }
                 
	        }
	        catch (QueryException $e) {
	            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$BLOG_ERROR_RETRIVE_POST, $e->getMessage());
	        }
	        catch (Exception $e) {
	            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$BLOG_ERROR_RETRIVE_POST, $e->getMessage());
	        }
	        // dd($post);
	        // $section = json_decode($posts[0]->sections);
	        // dd($section[0]->title);
	        return HttpResponse::ok(HttpMessage::$BLOG_FOUND, $post);

		}

		public function edit($post_id, Request $request)
		{
			try {
	            $user = JWTAuth::toUser($request->token);
	        }
	        catch (Exception $exception)
	        {
	            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$BLOG_ERROR_UPDATING_POST,
	                $exception->getMessage());
	        }

	        if ($user->status === UserStatusConsts::$BLOCKED)
	        {
	            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
	                HttpMessage::$USER_BLOCKED_OPERATION);
	        }

	        if ($user->blog_access === UserStatusConsts::$BLOG_ACCESS_DEACTIVE)
	        {
	            return HttpResponse::serverError(HttpStatus::$ERR_USER_DISABLED_BLOG_ACCESS, HttpMessage::$USER_DISABLED_BLOG_ACCESS,
	                HttpMessage::$USER_DISABLED_BLOG_ACCESS);
	        }
			try {
	             $post = DB::table('posts')
	                ->where('posts.id', '=', $post_id)
	             	// ->where('posts.is_publish', '=', true)
                    ->get();
                 if ($post->count() > 0){
                 	$post_eloquent = Post::find($post_id);
                 	$comments = $post_eloquent->comments;
                 	$post->comments = $comments;
                 	$post[0]->sections= json_decode($post[0]->sections);
                 }
                 else{
                 	 return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$BLOG_ERROR_RETRIVE_POST,
	                HttpMessage::$BLOG_ERROR_RETRIVE_POST);
                 }
                 
                 	
	        }
	        catch (QueryException $e) {
	            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$BLOG_ERROR_RETRIVE_POST, $e->getMessage());
	        }
	        catch (Exception $e) {
	            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$BLOG_ERROR_RETRIVE_POST, $e->getMessage());
	        }
	        // dd($posts);
	        // $section = json_decode($posts[0]->sections);
	        // dd($section[0]->title);
	        return HttpResponse::ok(HttpMessage::$BLOG_FOUND, $post);

		}

		public function adminEdit($post_id, Request $request)
		{
			try {
	            $user = JWTAuth::toUser($request->token);
	        }
	        catch (Exception $exception)
	        {
	            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$BLOG_ERROR_UPDATING_POST,
	                $exception->getMessage());
	        }

			try {
	             $post = DB::table('posts')
	                ->where('posts.id', '=', $post_id)
	             	// ->where('posts.is_publish', '=', true)
                    ->get();
                 if ($post->count() > 0){
                 	$post_eloquent = Post::find($post_id);
                 	$comments = $post_eloquent->comments;
                 	$post->comments = $comments;
                 	$post[0]->sections= json_decode($post[0]->sections);
                 }
                 else{
                 	 return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$BLOG_ERROR_RETRIVE_POST,
	                HttpMessage::$BLOG_ERROR_RETRIVE_POST);
                 }
                 
                 	
	        }
	        catch (QueryException $e) {
	            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$BLOG_ERROR_RETRIVE_POST, $e->getMessage());
	        }
	        catch (Exception $e) {
	            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$BLOG_ERROR_RETRIVE_POST, $e->getMessage());
	        }
	        // dd($posts);
	        // $section = json_decode($posts[0]->sections);
	        // dd($section[0]->title);
	        return HttpResponse::ok(HttpMessage::$BLOG_FOUND, $post);

		}
		
        public function update($post_id, Request $request)
        {
	    	$validator = \Validator::make($request->all(), [
	            'title' => 'required',
	            'description' => 'required',
	            'coverImage' => 'required',
	            'category' => 'required',
	            'color' => 'required'
	        ]);
	        $post_id = $post_id;
	        if ($validator->fails()) {
	            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$BLOG_ERROR_UPDATING_POST, $validator->errors()->all());
	        }

	        try {
	            $user = JWTAuth::toUser($request->token);
	        }
	        catch (Exception $exception)
	        {
	            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$BLOG_ERROR_UPDATING_POST,
	                $exception->getMessage());
	        }

	        if ($user->status === UserStatusConsts::$BLOCKED)
	        {
	            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
	                HttpMessage::$USER_BLOCKED_OPERATION);
	        }

	        if ($user->blog_access === UserStatusConsts::$BLOG_ACCESS_DEACTIVE)
	        {
	            return HttpResponse::serverError(HttpStatus::$ERR_USER_DISABLED_BLOG_ACCESS, HttpMessage::$USER_DISABLED_BLOG_ACCESS,
	                HttpMessage::$USER_DISABLED_BLOG_ACCESS);
	        }

	        if ($request->hasFile('coverImage')){
	        	$coverImage = $request->file('coverImage');
	        	
	        }
	        $imageFileName = time().$coverImage->getClientOriginalName();
	        // $destinationPath = url('/').'/blogImages';
	        // dd($destinationPath);
	        $destinationPath = public_path('/blogImages');
	        if (!file_exists($destinationPath)) { 
			    mkdir($destinationPath, 0755, true); 
			}
	        $coverImage->move($destinationPath, $imageFileName);
	        $coverImageUrl = url('/').'/blogImages/'.$imageFileName;
	        $subImagesUrl = array();
        	if ($request->HasFile('images')){
	        	try {
					foreach ($request->images as $file) {
						\Log::info('*****sectionIMG*******************');
						\Log::info($file->getClientOriginalName());
						if ($user->role == "admin"){
							$sectionId = $file->getClientOriginalName();
						}
						else{
							$sectionId = $file->getClientOriginalName() + 1;
						}
						\log::info('#########################');
						\Log::info($file->getClientOriginalExtension());		
			        	$subImageFileName = time().'image of section'.$sectionId.'.png';
			        	\Log::info("Updating live sub player stats ...".$subImageFileName);
			        	$file->move($destinationPath, $subImageFileName);
			       	    $subImageUrl = url('/').'/blogImages/'.$subImageFileName;
			       	    $subImageUrlArray = array('id' => $sectionId, 'url' => $subImageUrl);
			       	    \log::info('#########################subURL');
						\Log::info($subImageUrl);
			       	    array_push($subImagesUrl, $subImageUrlArray);
								       
					}
		        	
	        	} catch(\Exception $e){
	        		\Log::info($e->getMessage());
	        	}
	        }
	        \Log::info('subImagesURL'.json_encode($subImagesUrl));
	        $sections = [];
	        if ($request->get('sections')){
	        	$sections = $request->get('sections');
		        \Log::info('sections'.$sections);
		        $sections_array = json_decode($sections);
		        if($sections_array != null){
			        foreach ($sections_array as $key => $section_array) {
			        		foreach ($subImagesUrl as $subImageUrl) {
			        			if ($subImageUrl['id'] == $key + 1){
					        		$section_array->image = $subImageUrl['url'];
			        			}
			        		}
			        	$section_array->id = $key + 1;
			        }
		        }
		        else {
		        	 $sections_array = [];
		        }
		        $sections = json_encode($sections_array);
	        }
	        \Log::info('sections'.$sections);
	  //      	$sections = '
			// 		[
			// 			{ "title": "section 1 title", "subtitle": "subtitle of a section1", "description": "description of section 1","image_url": "image_url"},
			// 			{ "title": "section 2 title", "subtitle": "subtitle of a section2", "description": "description of section 2","image_url2": "image_url2"}
			// 		]

			// ';
			// $array = json_decode($sections);
			// // dd($array);
			// $sections = json_encode($array);
	        $post = Post::find($post_id);

	        if ($post->author != $user->id)
	        {
	        	return HttpResponse::serverError(HttpStatus::$ERR_UPDATE_POST, HttpMessage::$BLOG_ERROR_UPDAING_OWN_POST,
	                HttpMessage::$BLOG_ERROR_UPDAING_OWN_POST);
	        }

	        try{

	             	$post = Post::updateOrCreate(array('id' => $post_id),
	             		['title' => $request->get('title'),
	             		'description' => $request->get('description'),
	             		'category' => $request->get('category'),
	             		'image' => $coverImageUrl,
	             		'color' => $request->get('color')
	             		]);
	             	$post = Post::find($post_id);
	             	$post->sections = $sections;
	             	$post->save();
	        }
	        catch(Exception $exception) {
	            return HttpResponse::serverError(HttpStatus::$ERR_UPDATE_POST, HttpMessage::$BLOG_ERROR_UPDAING_POST,
	                $exception->getMessage());
	        }

	        return HttpResponse::ok(HttpMessage::$BLOG_UPDATED, $post);

    	}

    	public function adminUpdate($post_id, Request $request)
        {
	    	$validator = \Validator::make($request->all(), [
	            'title' => 'required',
	            'description' => 'required',
	            'coverImage' => 'required',
	            'category' => 'required',
	            'color' => 'required'
	        ]);
	        $post_id = $post_id;
	        \Log::info('sections***************'.$request->file('coverImage'));
	        if ($validator->fails()) {
	            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$BLOG_ERROR_UPDATING_POST, $validator->errors()->all());
	        }

	        try {
	            $user = JWTAuth::toUser($request->token);
	        }
	        catch (Exception $exception)
	        {
	            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$BLOG_ERROR_UPDATING_POST,
	                $exception->getMessage());
	        }

	        if ($request->hasFile('coverImage')){
	        	$coverImage = $request->file('coverImage');
	        	
	        }
	        $imageFileName = time().$coverImage->getClientOriginalName();
	        // $destinationPath = url('/').'/blogImages';
	        // dd($destinationPath);
	        $destinationPath = public_path('/blogImages');
	        if (!file_exists($destinationPath)) { 
			    mkdir($destinationPath, 0755, true); 
			}
	        $coverImage->move($destinationPath, $imageFileName);
	        $coverImageUrl = url('/').'/blogImages/'.$imageFileName;
	        $subImagesUrl = array();
        	if ($request->HasFile('images')){
	        	try {
					foreach ($request->images as $file) {
						\Log::info('*****sectionIMG*******************');
						\Log::info($file->getClientOriginalName());
						if ($user->role == "admin"){
							$sectionId = $file->getClientOriginalName();
						}
						else{
							$sectionId = $file->getClientOriginalName() + 1;
						}
						\log::info('#########################');
						\Log::info($file->getClientOriginalExtension());		
			        	$subImageFileName = time().'image of section'.$sectionId.'.png';
			        	\Log::info("Updating live sub player stats ...".$subImageFileName);
			        	$file->move($destinationPath, $subImageFileName);
			       	    $subImageUrl = url('/').'/blogImages/'.$subImageFileName;
			       	    $subImageUrlArray = array('id' => $sectionId, 'url' => $subImageUrl);
			       	    \log::info('#########################subURL');
						\Log::info($subImageUrl);
			       	    array_push($subImagesUrl, $subImageUrlArray);
								       
					}
		        	
	        	} catch(\Exception $e){
	        		\Log::info($e->getMessage());
	        	}
	        }
	        \Log::info('subImagesURL'.json_encode($subImagesUrl));
	        $sections = [];
	        if ($request->get('sections')){
	        	$sections = $request->get('sections');
		        \Log::info('sections'.$sections);
		        $sections_array = json_decode($sections);
		        if($sections_array != null){
			        foreach ($sections_array as $key => $section_array) {
			        		foreach ($subImagesUrl as $subImageUrl) {
			        			if ($subImageUrl['id'] == $key + 1){
					        		$section_array->image = $subImageUrl['url'];
			        			}
			        		}
			        	$section_array->id = $key + 1;
			        }
		        }
		        else {
		        	 $sections_array = [];
		        }
		        $sections = json_encode($sections_array);
	        }
	        \Log::info('sections***************'.$sections);
	  //      	$sections = '
			// 		[
			// 			{ "title": "section 1 title", "subtitle": "subtitle of a section1", "description": "description of section 1","image_url": "image_url"},
			// 			{ "title": "section 2 title", "subtitle": "subtitle of a section2", "description": "description of section 2","image_url2": "image_url2"}
			// 		]

			// ';
			// $array = json_decode($sections);
			// // dd($array);
			// $sections = json_encode($array);
	        $post = Post::find($post_id);

	        if ($post->author != $user->id)
	        {
	        	return HttpResponse::serverError(HttpStatus::$ERR_UPDATE_POST, HttpMessage::$BLOG_ERROR_UPDAING_OWN_POST,
	                HttpMessage::$BLOG_ERROR_UPDAING_OWN_POST);
	        }

	        try{

	             	$post = Post::updateOrCreate(array('id' => $post_id),
	             		['title' => $request->get('title'),
	             		'description' => $request->get('description'),
	             		'category' => $request->get('category'),
	             		'image' => $coverImageUrl,
	             		'color' => $request->get('color')
	             		]);
	             	$post = Post::find($post_id);
	             	$post->sections = $sections;
	             	$post->save();
	        }
	        catch(Exception $exception) {
	            return HttpResponse::serverError(HttpStatus::$ERR_UPDATE_POST, HttpMessage::$BLOG_ERROR_UPDAING_POST,
	                $exception->getMessage());
	        }

	        return HttpResponse::ok(HttpMessage::$BLOG_UPDATED, $post);

    	}

    	public function create(Request $request){
    		try {
	            $user = JWTAuth::toUser($request->token);
	        }
	        catch (Exception $exception)
	        {
	            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$BLOG_ERROR_CREAING_POST,
	                $exception->getMessage());
	        }

	        if ($user->status === UserStatusConsts::$BLOCKED)
	        {
	            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
	                HttpMessage::$USER_BLOCKED_OPERATION);
	        }

	        if ($user->blog_access === UserStatusConsts::$BLOG_ACCESS_DEACTIVE)
	        {
	            return HttpResponse::serverError(HttpStatus::$ERR_USER_DISABLED_BLOG_ACCESS, HttpMessage::$USER_DISABLED_BLOG_ACCESS,
	                HttpMessage::$USER_DISABLED_BLOG_ACCESS);
	        }
	        $categories = Category::get();
	        return HttpResponse::ok(HttpMessage::$RETRIEVING_CATEGORIES, $categories);
    	}

    	public function store(Request $request){
	    	$validator = \Validator::make($request->all(), [
	            'title' => 'required',
	            'description' => 'required',
	            'coverImage' => 'required',
	            'category' => 'required',
	            'color' => 'required'
	        ]);
	        \Log::info('************************');
			\Log::info($request->all());
			\log::info('#########################');
	        if ($validator->fails()) {
	            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$BLOG_ERROR_VALIDATING_POST, $validator->errors()->all());
	        }

	        try {
	            $user = JWTAuth::toUser($request->token);
	        }
	        catch (Exception $exception)
	        {
	            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$BLOG_ERROR_CREAING_POST,
	                $exception->getMessage());
	        }

	        // $temp = json_decode($request->get('creator'));
	        // \Log::info('**********************get formdata from url '.$temp->id);
	        // if any of validation rules failed, we will fail to create contest
	        // $coverImages = 'null_images';
	        if ($request->hasFile('coverImage')){
	        	$coverImage = $request->file('coverImage');
	        	
	        }
	        $imageFileName = time().$coverImage->getClientOriginalName();
	        // $destinationPath = url('/').'/blogImages';
	        // dd($destinationPath);
	        \Log::info('**********************$imageFileName '.$imageFileName);
	        $destinationPath = public_path('/blogImages');
	        \Log::info('**********************destinationPath '.$destinationPath);
	        if (!file_exists($destinationPath)) { 
			    mkdir($destinationPath, 0755, true); 
			}
	        $coverImage->move($destinationPath, $imageFileName);
	        $coverImageUrl = url('/').'/blogImages/'.$imageFileName;
	        $subImagesUrl = array();
        	if ($request->HasFile('images')){
	        	try {
					foreach ($request->images as $file) {
						\Log::info('*****sectionIMG*******************');
						\Log::info($file->getClientOriginalName());
						if ($user->role == "admin"){
							$sectionId = $file->getClientOriginalName();
						}
						else{
							$sectionId = $file->getClientOriginalName() + 1;
						}
						\log::info('#########################');
						\Log::info($file->getClientOriginalExtension());		
			        	$subImageFileName = time().'image of section'.$sectionId.'.png';
			        	\Log::info("Updating live sub player stats ...".$subImageFileName);
			        	$file->move($destinationPath, $subImageFileName);
			       	    $subImageUrl = url('/').'/blogImages/'.$subImageFileName;
			       	    $subImageUrlArray = array('id' => $sectionId, 'url' => $subImageUrl);
			       	    \log::info('#########################subURL');
						\Log::info($subImageUrl);
			       	    array_push($subImagesUrl, $subImageUrlArray);
								       
					}
		        	
	        	} catch(\Exception $e){
	        		\Log::info($e->getMessage());
	        	}
	        }
	        \Log::info('subImagesURL'.json_encode($subImagesUrl));
	        $sections = [];
	        if ($request->get('sections')){
	        	$sections = $request->get('sections');
		        \Log::info('sections'.$sections);
		        $sections_array = json_decode($sections);
		        if($sections_array != null){
			        foreach ($sections_array as $key => $section_array) {
			        		foreach ($subImagesUrl as $subImageUrl) {
			        			if ($subImageUrl['id'] == $key + 1){
					        		$section_array->image = $subImageUrl['url'];
			        			}
			        		}
			        	$section_array->id = $key + 1;
			        }
		        }
		        else {
		        	 $sections_array = [];
		        }
		        $sections = json_encode($sections_array);
	        }

	       
	        // $sections = json_encode(array(['name'=> 'name', 'value'=>'value']));
	        // $sections = '[{"name":"name","value":"value"}]';
			// $sections = '
			// 		[
			// 			{ "title": "5001", "subtitle": "None", "description": "description","image_url": "image_url"},
			// 			{ "title": "5002", "subtitle": "None2", "description2": "description","image_url2": "image_url2"}
			// 		]

			// ';
			// $array = json_decode($sections);
			// // dd($array);
			// $sections = json_encode($array);
	        try{
	        		$post = new Post();
	        		$post->title = $request->get('title');
	        		$post->description = $request->get('description');
	        		$post->category = $request->get('category');
	        		$post->image = $coverImageUrl;
	        		$post->color = $request->get('color');
	        		$post->author = $request->get('author');
	        		$post->is_publish = false;
	        		$post->sections = $sections;
	        		$post->save();
	        }
	        catch(Exception $exception) {
	            return HttpResponse::serverError(HttpStatus::$ERR_CREATE_POST, HttpMessage::$BLOG_ERROR_CREAING_POST,
	                $exception->getMessage());
	        }

	        return HttpResponse::ok(HttpMessage::$BLOG_CREATED, $post);

    	}

    	public function delete($post_id, Request $request){
	    	
	        $post_id = $post_id;

	        try {
	            $user = JWTAuth::toUser($request->token);
	        }
	        catch (Exception $exception)
	        {
	            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$BLOG_ERROR_DELETING_POST,
	                $exception->getMessage());
	        }

	        if ($user->status === UserStatusConsts::$BLOCKED)
	        {
	            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
	                HttpMessage::$USER_BLOCKED_OPERATION);
	        }

	        if ($user->blog_access === UserStatusConsts::$BLOG_ACCESS_DEACTIVE)
	        {
	            return HttpResponse::serverError(HttpStatus::$ERR_USER_DISABLED_BLOG_ACCESS, HttpMessage::$USER_DISABLED_BLOG_ACCESS,
	                HttpMessage::$USER_DISABLED_BLOG_ACCESS);
	        }

	        $post = Post::find($post_id);

	        if ($post->author != $user->id)
	        {
	        	return HttpResponse::serverError(HttpStatus::$ERR_DELETE_POST, HttpMessage::$BLOG_ERROR_DELEING_OWN_POST,
	                HttpMessage::$BLOG_ERROR_DELEING_OWN_POST);
	        }

	        try{
	        		$deletedComments = $post->comments()->delete();
	             	$deletedPost = $post->delete();
	        }
	        catch(Exception $exception) {
	            return HttpResponse::serverError(HttpStatus::$ERR_DELETE_POST, HttpMessage::$BLOG_ERROR_DELEING_POST,
	                $exception->getMessage());
	        }

	        return HttpResponse::ok(HttpMessage::$BLOG_DELETED, $deletedPost);

    	}

    	public function adminDelete($post_id, Request $request){
	    	
	        $post_id = $post_id;

	        try {
	            $user = JWTAuth::toUser($request->token);
	        }
	        catch (Exception $exception)
	        {
	            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$BLOG_ERROR_DELETING_POST,
	                $exception->getMessage());
	        }
	        $post = Post::find($post_id);

	        // if ($post->author != $user->id)
	        // {
	        // 	return HttpResponse::serverError(HttpStatus::$ERR_DELETE_POST, HttpMessage::$BLOG_ERROR_DELEING_OWN_POST,
	        //         HttpMessage::$BLOG_ERROR_DELEING_OWN_POST);
	        // }

	        try{
	        		$deletedComments = $post->comments()->delete();
	             	$deletedPost = $post->delete();
	        }
	        catch(Exception $exception) {
	            return HttpResponse::serverError(HttpStatus::$ERR_DELETE_POST, HttpMessage::$BLOG_ERROR_DELEING_POST,
	                $exception->getMessage());
	        }

	        return HttpResponse::ok(HttpMessage::$BLOG_DELETED, $deletedPost);

    	}

    	public function addComment($post_id, Request $request)
    	{
    		$post_id = $post_id;
    		$validator = \Validator::make($request->all(), [
	            'body' => 'required'
	        ]);
	        if ($validator->fails()) {
	            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$BLOG_ERROR_CREAING_POST, $validator->errors()->all());
	        }

	        try {
	            $user = JWTAuth::toUser($request->token);
	        }
	        catch (Exception $exception)
	        {
	            return HttpResponse::unauthorized(HttpStatus::$ERR_AUTH_INVALID_TOKEN_PROVIDED,HttpMessage::$BLOG_ERROR_ADDING_COMMENT,
	                $exception->getMessage());
	        }

	        if ($user->status === UserStatusConsts::$BLOCKED)
	        {
	            return HttpResponse::serverError(HttpStatus::$ERR_USER_BLOCKED_OPERATION, HttpMessage::$USER_BLOCKED_OPERATION,
	                HttpMessage::$USER_BLOCKED_OPERATION);
	        }

	        $post = Post::find($post_id);
	        // $comments = $post->comments();
	        // foreach ($comments as $comment) {
	        // 	if ($comment->user_id == $user->id){
	        // 		return HttpResponse::serverError(HttpStatus::$ERR_ADD_COMMENT, HttpMessage::$BLOG_ERROR_ADDING_COMMENT_OWN_POST, HttpMessage::$BLOG_ERROR_ADDING_COMMENT_OWN_POST);
	        // 	}
	        // }
	        // if ($post->author = $user->id)
	        // {
	        // 	return HttpResponse::serverError(HttpStatus::$ERR_ADD_COMMENT, HttpMessage::$BLOG_ERROR_ADDING_COMMENT_OWN_POST,
	        //         HttpMessage::$BLOG_ERROR_ADDING_COMMENT_OWN_POST);
	        // }
	        // array('comments.user_id'=>$user->id),
	        $comment = $post->comments()->updateOrCreate(array('comments.user_id'=>$user->id),
	        	['user_id' => $user->id, 'body' => $request->get('body')]); 
	        return HttpResponse::ok(HttpMessage::$COMMENT_ADDED, $comment);
    	}

    	public function changePublishStatus(Request $request)
    	{
    		    $id = null;
		        if ($request->get('blog_id')) {
		            $id = $request->get('blog_id');
		        }
		        
		        try {
		            $post = Post::find($id);
		            if ($post == null) {
		                return HttpResponse::serverError(HttpStatus::$ERR_BLOG_NOT_FOUND, HttpMessage::$BLOG_NOT_FOUND,
		                    HttpMessage::$BLOG_NOT_FOUND);
		            }
		        }
		        catch (QueryException $e) {
		            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$BLOG_NOT_FOUND,$e->getMessage());
		        }
		        catch (Exception $e) {
		            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$BLOG_NOT_FOUND, $e->getMessage());
		        }

		        try {
		                $blog_publish = $request->get('blog_publish');
		                if ($blog_publish == 'true'){
		                	$post->is_publish = true;
		                }
		                else{
		                	$post->is_publish = false;
		                }
		                $post->save();
		            
		        }
		        catch (QueryException $e) {
		            return HttpResponse::serverError(HttpStatus::$SQL_ERROR, HttpMessage::$BLOG_STATUS_CHANGE_ERROR, $e->getMessage());
		        }
		        catch (Exception $e) {
		            return HttpResponse::serverError(HttpStatus::$ERR_UNKNOWN, HttpMessage::$BLOG_STATUS_CHANGE_ERROR, $e->getMessage());
		        }

		        return HttpResponse::ok(HttpMessage::$BLOG_PUBLISHED, null);
		}

}
