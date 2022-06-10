<div class="{{$viewClass['form-group']}} {!! !$errors->has($errorKey) ? '' : 'has-error' !!}">

    <label for="{{$id}}" class="{{$viewClass['label']}} control-label">{{$label}}</label>

    <div class="{{$viewClass['field']}}">

        @include('admin::form.error')

        <div class="input-group">

            <input {!! $attributes !!} class="form-control" />
            <span class="input-group-btn">
            <button id="{{$column}}-search" class="btn btn-default" type="button">搜索!</button>
            </span>
        </div>

        @include('admin::form.help-block')

    </div>
</div>
