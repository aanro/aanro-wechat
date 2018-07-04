@extends('app.layout.frame_empty')

@section('content')
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">

            @include('vendor.someline.payment.payment_pay_component')

        </div>
        <div class="col-md-1"></div>
    </div>
@endsection

@push('scripts')
    @include('vendor.someline.wechat.wechat_js_sdk')
@endpush