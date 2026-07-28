@extends("layout")

@section("content")
<div data-role="panel" data-title-caption="{{ trans('cruds.attribute.replace.title') }}" data-collapsible="false" data-title-icon="<span class='mif-tags'></span>">

    @include('partials.errors')

	<form method="POST" action="/attribute/replace">
	@csrf
		<div class="grid">
	    	<div class="row">
	    		<div class="cell-1">
		    		<strong>{{ trans('cruds.attribute.replace.old_value') }}</strong>
		    	</div>
	    		<div class="cell-5">
					<select name="old_value" data-role="select" data-filter="true">
						<option></option>
						@foreach($values as $value)
							<option value="{{ $value }}" {{ old('old_value')==$value ? 'selected' : '' }}>{{ $value }}</option>
						@endforeach
					</select>
				</div>
			</div>

	    	<div class="row">
	    		<div class="cell-1">
		    		<strong>{{ trans('cruds.attribute.replace.new_value') }}</strong>
		    	</div>
	    		<div class="cell-5">
					<input type="text" class="input {{ $errors->has('new_value') ? 'is-danger' : ''}}" name="new_value" value="{{ old('new_value') }}" size='25'>
				</div>
			</div>

	    	<div class="row">
	    		<div class="cell-5">
					<button type="submit" class="button success">
                        <span class="mif-floppy-disk2"></span>
						&nbsp;
						{{ trans('common.save') }}
					</button>
    				&nbsp;
                    <a href="/attributes" class="button cancel">
    					<span class="mif-cancel"></span>
    					&nbsp;
    	    			{{ trans('common.cancel') }}
                    </a>
				</div>
			</div>
		</div>
	</form>
</div>
@endsection
