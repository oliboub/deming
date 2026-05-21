<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Control;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ControlController extends Controller
{
    public function index()
    {
        abort_if(!Auth::User()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $controls = Control::all();

        return response()->json($controls);
    }

    public function store(Request $request)
    {
        abort_if(!Auth::User()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $control = Control::query()->create($request->all());
        if ($request->has('controls')) {
            $control->allMeasures()->sync($request->input('controls', []));
        }

        return response()->json($control, 201);
    }

    public function show(Control $control)
    {
        abort_if(!Auth::User()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $control['controls'] = $control->allMeasures()->pluck('id');

        return response()->json($control);
    }

    public function update(Request $request, Control $control)
    {
        abort_if(!Auth::User()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $control->update($request->all());
        if ($request->has('controls')) {
            $control->allMeasures()->sync($request->input('controls', []));
        }

        return response()->json();
    }

    public function destroy(Control $control)
    {
        abort_if(!Auth::User()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $control->allMeasures()->detach();
        $control->delete();

        return response()->json();
    }
}
