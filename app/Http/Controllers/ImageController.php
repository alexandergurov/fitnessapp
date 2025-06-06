<?php



namespace App\Http\Controllers;



use Illuminate\Http\Request;

use App\Http\Requests;

use Image;



class ImageController extends Controller

{



    /**

     * Show the form for creating a new resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function resizeImage()

    {

        return view('resizeImage');

    }



    /**

     * Show the form for creating a new resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function resizeImagePost(Request $request)

    {

        $this->validate($request, [

            'title' => 'required',

            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);



        $image = $request->file('image');

        $input['imagename'] = time().'.'.$image->extension();



        $destinationPath = public_path('/thumbnail');

        $img = Image::make($image->path());

        $img->resize(100, 100, function ($constraint) {

            $constraint->aspectRatio();

        })->save($destinationPath.'/'.$input['imagename']);



        $destinationPath = public_path('/images');

        $image->move($destinationPath, $input['imagename']);



        return back()

            ->with('success','Image Upload successful')

            ->with('imageName',$input['imagename']);

    }



}
