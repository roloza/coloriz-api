<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::resource('images', 'SearchImagesController');
Route::resource('coloriz', 'ColorizController');
Route::resource('brands', 'BrandController');
Route::resource('image', 'ImagesController');
Route::resource('colors', 'ColorsController');
Route::resource('upload', 'FileUploadController');
Route::resource('compress', 'CompressController');
Route::resource('browsershot', 'BrowsershotController');

Route::resource('tags', 'TagsController');
Route::resource('categories', 'CategoriesController');

/* Pour test */
Route::get('color-name/{color}', 'ColorsController@getColorName');
Route::get('category/colors/add', 'ColorsController@addColors');
Route::get('category/colors/update-images', 'ColorsController@updateImagesWithoutColor');