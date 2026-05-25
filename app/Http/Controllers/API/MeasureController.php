<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\Document;
use App\Models\Measure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class MeasureController extends Controller
{
    public function index()
    {
        abort_if(!Auth::user()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $activities = Measure::all();

        return response()->json($activities);
    }

    public function store(Request $request)
    {
        abort_if(!Auth::user()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $measure = Measure::create($request->all());

        if ($request->has('controls')) {
            $measure->controls()->sync($request->input('controls', []));
        }
        if ($request->has('actions')) {
            Action::where('measure_id', $measure->id)->update(['measure_id' => null]);
            Action::whereIn('id', $request->input('actions', []))->update(['measure_id' => $measure->id]);
        }
        if ($request->has('documents')) {
            Document::where('measure_id', $measure->id)->update(['measure_id' => null]);
            Document::whereIn('id', $request->input('documents', []))->update(['measure_id' => $measure->id]);
        }
        if ($request->has('users')) {
            $measure->users()->sync($request->input('users', []));
        }
        if ($request->has('groups')) {
            $measure->groups()->sync($request->input('groups', []));
        }

        return response()->json($measure, 201);
    }

    public function show(Measure $measure)
    {
        abort_if(!Auth::user()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $measure['controls'] = $measure->controls()->pluck('id');

        return response()->json($measure);
    }

    public function update(Request $request, Measure $measure)
    {
        abort_if(!Auth::user()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $measure->update($request->all());

        if ($request->has('controls')) {
            $measure->controls()->sync($request->input('controls', []));
        }
        if ($request->has('actions')) {
            Action::where('measure_id', $measure->id)->update(['measure_id' => null]);
            Action::whereIn('id', $request->input('actions', []))->update(['measure_id' => $measure->id]);
        }
        if ($request->has('documents')) {
            Document::where('measure_id', $measure->id)->update(['measure_id' => null]);
            Document::whereIn('id', $request->input('documents', []))->update(['measure_id' => $measure->id]);
        }
        if ($request->has('users')) {
            $measure->users()->sync($request->input('users', []));
        }
        if ($request->has('groups')) {
            $measure->groups()->sync($request->input('groups', []));
        }

        return response()->json();
    }

    public function destroy(Measure $measure)
    {
        abort_if(!Auth::user()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $measure->controls()->detach();
        $measure->delete();

        return response()->json();
    }
}
