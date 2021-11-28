<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AlbumResource;
use App\Http\Resources\V1\ImageManipulationResource;
use App\Models\Album;
use App\Models\ImageManipulation;
use App\Http\Requests\ResizeImageRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use never;

class ImageManipulationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return ImageManipulationResource::collection(ImageManipulation::where('user_id', $request->user()->id)->paginate());
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param Album $album
     * @return AnonymousResourceCollection|never
     */
    public function getByAlbum(Request $request, Album $album): AnonymousResourceCollection
    {
        if ($request->user()->id !== $request->user()->id) {
            return abort(403, 'Unauthorized');
        }

        return ImageManipulationResource::collection(ImageManipulation::where([
            'user_id' => $request->user()->id,
            'album_id' => $album->id
        ])->paginate());
    }

    /**
     * Display the specified resource.
     *
     * @param ResizeImageRequest $request
     * @return ImageManipulationResource|never
     */
    public function resize(ResizeImageRequest $request): ImageManipulationResource
    {
        $all = $request->all();

        $image = $all['image'];
        unset($all['image']);
        $data = [
            'type' => ImageManipulation::TYPE_RESIZE,
            'data' => json_encode($all),
            'user_id' => null,
        ];

        if (isset($all['album_id'])) {
            $album = Album::find($all['album_id']);
            if ($request->user()->id !== $album->user_id) {
                return abort(403, 'Unauthorized');
            }

            $data['album_id'] = $all['album_id'];
        }

        $dir = 'images/' . Str::random() . '/';
        $absolutePath = public_path($dir);
        File::makeDirectory($absolutePath);

        if ($image instanceof UploadedFile) {
            $data['name'] = $image->getClientOriginalName();
            $filename = pathinfo($data['name'], PATHINFO_FILENAME);
            $extension = $image->getClientOriginalExtension();
            $originalPath = $absolutePath . $data['name'];
            $data['path'] = $dir . $data['name'];

            $image->move($absolutePath, $data['name']);
        } else {
            $data['name'] = pathinfo($image, PATHINFO_BASENAME);
            $filename = pathinfo($image, PATHINFO_FILENAME);
            $extension = pathinfo($image, PATHINFO_EXTENSION);
            $originalPath = $absolutePath . $data['name'];

            copy($image, $originalPath);
            $data['path'] = $dir . $data['name'];
        }

        $w = $all['w'];
        $h = $all['h'] ?? false;

        list($image, $width, $height) = $this->getWidthAndHeight($w, $h, $originalPath);

        $resizedFilename = $filename . '-resized.' . $extension;
        $image->resize($width, $height)->save($absolutePath . $resizedFilename);

        $data['output_path'] = $dir . $resizedFilename;

        $imageManipulation = ImageManipulation::create($data);

        return new ImageManipulationResource($imageManipulation);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param ImageManipulation $image
     * @return AlbumResource|never
     */
    public function show(Request $request, ImageManipulation $image): ImageManipulationResource
    {
        if ($request->user()->id !== $image->user_id) {
            return abort(403, 'Unauthorized');
        }
        return new ImageManipulationResource($image);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param ImageManipulation $image
     * @return Response|never
     */
    public function destroy(Request $request, ImageManipulation $image): Response
    {
        if ($image->user_id !== $request->user()->id) {
            return abort(403, 'Unauthorized action.');
        }
        $image->delete();
        return response('', 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $w
     * @param $h
     * @param string $originalPath
     * @return array
     */
    protected function getWidthAndHeight($w, $h, string $originalPath): array
    {
        $image = Image::make($originalPath);
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        if (str_ends_with($w, '%')) {
            $ratioW = (float)str_replace('%', '', $w);
            $ratioH = $h ? (float)str_replace('%', '', $h) : $ratioW;

            $newWidth = $originalWidth * $ratioW / 100;
            $newHeight = $originalHeight * $ratioH / 100;
        } else {
            $newWidth = (float)$w;

            /**
             * $originalWidth  -  $newWidth
             * $originalHeight -  $newHeight
             * -----------------------------
             * $newHeight =  $originalHeight * $newWidth/$originalWidth
             */
            $newHeight = $h ? (float)$h : ($originalHeight * $newWidth / $originalWidth);
        }

        return [$image, $newWidth, $newHeight];
    }
}
