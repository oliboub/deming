<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Risk;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class RiskController extends Controller
{
    public function index()
    {
        abort_if(!Auth::user()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $risks = Risk::all();

        return response()->json($risks);
    }

    public function store(Request $request)
    {
        abort_if(!Auth::user()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $risk = Risk::query()->create($request->all());

        if ($request->has('controls')) {
            $risk->controls()->sync($request->input('controls', []));
        }
        if ($request->has('actions')) {
            $risk->actions()->sync($request->input('actions', []));
        }

        return response()->json($risk, 201);
    }

    public function show(Risk $risk)
    {
        abort_if(!Auth::user()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $risk['controls'] = $risk->controls()->pluck('id');
        $risk['actions'] = $risk->actions()->pluck('id');

        return response()->json($risk);
    }

    public function update(Request $request, Risk $risk)
    {
        abort_if(!Auth::user()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $risk->update($request->all());

        if ($request->has('controls')) {
            $risk->controls()->sync($request->input('controls', []));
        }
        if ($request->has('actions')) {
            $risk->actions()->sync($request->input('actions', []));
        }

        return response()->json();
    }

    public function destroy(Risk $risk)
    {
        abort_if(!Auth::user()->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $risk->controls()->detach();
        $risk->actions()->detach();
        $risk->delete();

        return response()->json();
    }
}
