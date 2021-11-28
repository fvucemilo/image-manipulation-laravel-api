<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AlbumResource;
use App\Models\Album;
use App\Http\Requests\StoreAlbumRequest;
use App\Http\Requests\UpdateAlbumRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use never;

class AlbumController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return AlbumResource::collection(Album::where('user_id', $request->user()->id)->paginate());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreAlbumRequest $request
     * @return AlbumResource
     */
    public function store(StoreAlbumRequest $request): AlbumResource
    {
        $data = $request->all();
        $data['user_id'] = $request->user()->id;
        $album = Album::create($data);

        return new AlbumResource($album);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param Album $album
     * @return AlbumResource|never
     */
    public function show(Request $request, Album $album): AlbumResource
    {
        if ($request->user()->id !== $album->user_id) {
            return abort(403, 'Unauthorized');
        }
        return new AlbumResource($album);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateAlbumRequest $request
     * @param Album $album
     * @return AlbumResource|never
     */
    public function update(UpdateAlbumRequest $request, Album $album): AlbumResource
    {
        if ($request->user()->id !== $album->user_id) {
            return abort(403, 'Unauthorized');
        }
        $album->update($request->all());

        return new AlbumResource($album);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param Album $album
     * @return Response|never
     */
    public function destroy(Request $request, Album $album): Response
    {
        if ($request->user()->id !== $album->user_id) {
            return abort(403, 'Unauthorized');
        }
        $album->delete();

        return response('', 204);
    }
}
