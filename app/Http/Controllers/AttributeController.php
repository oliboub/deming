<?php

namespace App\Http\Controllers;

use App\Exports\AttributesExport;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $attributes = DB::table('attributes')->orderBy('id')->get();
        return view('attributes.index')
            ->with('attributes', $attributes);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Only for administrator role
        abort_if(!Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('attributes.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Only for administrator role
        abort_if(!Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $this->validate(
            $request,
            [
                'name' => 'required|min:1|max:30',
                'values' => "required|regex:/^(#[\p{L}\p{M}\p{N}'_-]+ *)*$/u|max:4000",
            ]
        );

        $attribute = new Attribute();
        $attribute->name = request('name');
        $attribute->values = request('values');
        $attribute->save();
        return redirect('/attributes');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Attribute $attribute
     *
     * @return \Illuminate\View\View
     */
    public function show(Attribute $attribute)
    {
        return view('attributes.show', compact('attribute'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Attribute $attribute
     *
     * @return \Illuminate\View\View
     */
    public function edit(Attribute $attribute)
    {
        // Only for administrator role
        abort_if(!Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('attributes.edit', compact('attribute'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\Attribute    $attribute
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Attribute $attribute)
    {
        // Only for administrator role
        abort_if(!Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $this->validate(
            $request,
            [
                'name' => 'required|min:1|max:30',
                'values' => "required|regex:/^(#[\p{L}\p{M}\p{N}'_-]+ *)*$/u|max:4000",
            ]
        );
        $attribute->name = request('name');
        $attribute->values = request('values');
        $attribute->save();
        return redirect('/attributes');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Attribute $attribute
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Attribute $attribute)
    {
        // Only for administrator role
        abort_if(!Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $attribute->delete();

        return redirect('/attributes');
    }

    public function export()
    {
        return Excel::download(new AttributesExport(), 'attributes.xlsx');
    }

    /**
     * Show the form to globally replace an attribute value.
     *
     * @return \Illuminate\View\View
     */
    public function replace()
    {
        // Only for administrator role
        abort_if(!Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $values = $this->knownAttributeValues();
        sort($values);

        return view('attributes.replace')
            ->with('values', $values);
    }

    /**
     * Globally replace an attribute value in controls.attributes and attributes.values.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function replaceStore(Request $request)
    {
        // Only for administrator role
        abort_if(!Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $values = $this->knownAttributeValues();

        $this->validate(
            $request,
            [
                'old_value' => 'required|string|in:' . implode(',', $values),
                'new_value' => [
                    'required',
                    'string',
                    'max:100',
                    "regex:/^#[\p{L}\p{M}\p{N}'_-]+$/u",
                    'different:old_value',
                ],
            ]
        );

        $oldValue = $request->input('old_value');
        $newValue = $request->input('new_value');

        DB::transaction(function () use ($oldValue, $newValue) {
            $this->replaceTokenInTable('attributes', 'values', $oldValue, $newValue);
            $this->replaceTokenInTable('controls', 'attributes', $oldValue, $newValue);
            // Realised measures are historical records: only unrealised ones are updated.
            $this->replaceTokenInTable('measures', 'attributes', $oldValue, $newValue, function ($query) {
                $query->whereNull('realisation_date');
            });
        });

        return redirect('/attribute/replace')
            ->with('messages', [__('cruds.attribute.replace.success', ['old' => $oldValue, 'new' => $newValue])]);
    }

    /**
     * All distinct attribute value tokens currently in use, across attributes.values,
     * controls.attributes, and the attributes of unrealised measures (realisation_date
     * is null) — realised measures are historical records and are excluded.
     *
     * @return array<int, string>
     */
    private function knownAttributeValues(): array
    {
        $values = [];
        $attributes = DB::table('attributes')
            ->select('values')
            ->union(DB::table('controls')->select(DB::raw('attributes as value')))
            ->union(DB::table('measures')->whereNull('realisation_date')->select(DB::raw('attributes as value')))
            ->get();
        foreach ($attributes as $key) {
            foreach (explode(' ', $key->values) as $value) {
                if (strlen($value) > 0) {
                    array_push($values, $value);
                }
            }
        }

        return array_values(array_unique($values));
    }

    /**
     * Replace a token strictly equal to $oldValue by $newValue in every row of
     * $table.$column that contains it, deduplicating tokens while preserving order.
     * Only rows whose content actually changes are updated. An optional $scope
     * callback can restrict which rows of $table are considered.
     */
    private function replaceTokenInTable(string $table, string $column, string $oldValue, string $newValue, ?callable $scope = null): void
    {
        $query = DB::table($table)->select('id', $column);
        if ($scope !== null) {
            $scope($query);
        }
        $rows = $query->get();

        foreach ($rows as $row) {
            $tokens = array_values(array_filter(
                explode(' ', $row->{$column} ?? ''),
                fn ($token) => strlen($token) > 0
            ));

            if (! in_array($oldValue, $tokens, true)) {
                continue;
            }

            $replaced = array_map(
                fn ($token) => $token === $oldValue ? $newValue : $token,
                $tokens
            );

            $deduplicated = array_values(array_unique($replaced));

            $newContent = implode(' ', $deduplicated);

            if ($newContent !== ($row->{$column} ?? '')) {
                DB::table($table)->where('id', $row->id)->update([$column => $newContent]);
            }
        }
    }
}
