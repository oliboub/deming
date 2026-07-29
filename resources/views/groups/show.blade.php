@extends("layout")

@section("content")
<div data-role="panel" data-title-caption="{{ trans('cruds.group.show') }}" data-collapsible="false" data-title-icon="<span class='mif-group'></span>">

	<div class="grid">

    	<div class="row">
    		<div class="cell-lg-1 cell-md-2">
                <strong>{{ trans('cruds.group.fields.name') }}</strong>
	    	</div>
            <div class="cell-lg-6 cell-md-10">
                {{ $group->name }}
    		</div>
	    </div>

    	<div class="row">
    		<div class="cell-lg-1 cell-md-2">
                <strong>{{ trans('cruds.group.fields.description') }}</strong>
	    	</div>
			<div class="cell-lg-10 cell-md-10">
                {!! $group->description !!}
    		</div>
	    </div>

    	<div class="row">
    		<div class="cell-lg-1 cell-md-2">
                <strong>{{ trans('cruds.group.fields.users') }}</strong>
	    	</div>
        </div>
    	<div class="row">
			<div class="cell-lg-12 cell-md-12">
			    <table id="group-users-table" class="table striped row-hover cell-border row-border"
			       data-role="table"
			       data-rows="25"
			       data-show-activity="true"
			       data-rownum="false"
			       data-check="false">
			        <thead>
			            <tr>
                            <th class="sortable-column">{{ trans("cruds.user.fields.name") }}</th>
                            <th class="sortable-column">{{ trans("cruds.user.fields.email") }}</th>
                            <th class="sortable-column">{{ trans("cruds.user.fields.role") }}</th>
			            </tr>
			        </thead>
			        <tbody>
                @foreach($group->users as $user)
    				<tr>
			            <td>
                            <a id="{{ $user->name }}" href="/users/{{$user->id}}">
                                {{ $user->name }}
                            </a>
                        </td>
			            <td>
                            {{ $user->email }}
			            </td>
                        <td>
        		    		{{ $user->role==1 ? trans('cruds.user.roles.admin') : "" }}
        		    		{{ $user->role==2 ? trans('cruds.user.roles.user') : "" }}
                            {{ $user->role==5 ? trans('cruds.user.roles.auditee') : "" }}
        		    		{{ $user->role==3 ? trans('cruds.user.roles.auditor') : "" }}
        		    		{{ $user->role==4 ? trans('cruds.user.roles.api') : "" }}
                        </td>
	    			</tr>
    			@endforeach
    			</table>
    		</div>
    	</div>


    	<div class="row">
            <div class="cell-2">
                <strong>{{ trans('cruds.group.fields.controls') }}</strong>
	    	</div>
        </div>
    	<div class="row">
			<div class="cell-lg-12 cell-md-12">
			    <table id="group-measures-table" class="table striped row-hover cell-border row-border"
			       data-role="table"
			       data-rows="25"
			       data-show-activity="true"
			       data-rownum="false"
			       data-check="false">
			        <thead>
			            <tr>
                            <th class="sortable-column">{{ trans("cruds.measure.fields.clauses") }}</th>
                            <th class="sortable-column">{{ trans("cruds.measure.fields.name") }}</th>
                            <th class="sortable-column">{{ trans("cruds.measure.fields.scope") }}</th>
                            <th class="sortable-column">{{ trans("cruds.measure.fields.planned") }}</th>
			            </tr>
			        </thead>
			        <tbody>
                @foreach($group->measures as $measure)
    				<tr>
			            <td>
                            @foreach($measure->controls as $control)
                            <a id="{{ $control['clause'] }}" href="/alice/show/{{ $control['id'] }}">
                                {{ $control['clause'] }}
                            </a>
                            @if (!$loop->last)
                            ,
                            @endif
                            @endforeach
                        </td>
			            <td>
                            {{ $measure->name }}
                        </td>
			            <td>
                            {{ $measure->scope }}
			            </td>
                        <td>
                            <a id="{{ $measure->plan_date }}" href="/bob/show/{{$measure->id}}">
			                <b>
			                    @if( strtotime($measure->plan_date) >= strtotime('now') )
			                        <font color="green">{{ $measure->plan_date }}</font>
			                    @else
			                        <font color="red">{{ $measure->plan_date }}</font>
			                    @endif
			                </b>
                            </a>
                        </td>
	    			</tr>
    			@endforeach
    			</table>
    		</div>
    	</div>

    	<div class="row">
    		<div class="cell">
	    	</div>
        </div>


    </div>

	<div class="row">
        <div class="cell-lg-6 cell-md-10">
        <form class="d-inline" action="/groups/{{ $group->id }}/edit">
			@if (Auth::User()->role==1)
            <button class="button primary">
	            <span class="mif-wrench"></span>
	            &nbsp;
	    		{{ trans('common.edit') }}
	    	</button>
	    </form>
	    	&nbsp;
	    	@endif
			@if (Auth::User()->role==1)
            <form action="/groups/{{ $group->id }}" method="post" onSubmit="if(!confirm('{{ trans('common.confirm') }}')){return false;}" class="d-inline">
               {{ method_field('delete') }}
               @csrf
	            <button class="button alert" type="submit">
					<span class="mif-fire"></span>
					&nbsp;
	            	{{ trans('common.delete') }}
	            </button>
	        </form>
	        &nbsp;
	        @endif
			<a class="button" href="/groups">
				<span class="mif-cancel"></span>
				&nbsp;
	    		{{ trans('common.cancel') }}
            </a>
        </div>
    </div>
	</div>
</div>
@endsection
