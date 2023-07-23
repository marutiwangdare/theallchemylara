@extends('layouts.front-end.app')

@section('title',\App\CPU\translate('Shipping Policy'))

@push('css_or_js')

    <meta property="og:image" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="og:title" content="About {{$web_config['name']->value}} "/>
    <meta property="og:url" content="{{env('APP_URL')}}">
    <meta property="og:description" content="{!! substr($web_config['about']->value,0,100) !!}">

    <meta property="twitter:card" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="twitter:title" content="about {{$web_config['name']->value}}"/>
    <meta property="twitter:url" content="{{env('APP_URL')}}">
    <meta property="twitter:description" content="{!! substr($web_config['about']->value,0,100) !!}">
@endpush

@section('content')
    <div class="container for-container rtl __inlini-51">
        <h2 class="text-center mt-3 headerTitle">{{\App\CPU\translate('Shipping Policy')}}</h2>
        <div class="for-padding">
            <br>
Thank you for considering The Allchemy for your shopping needs! Here is our shipping policy:<br>

<b>Processing Time:</b> We strive to process and ship all orders within 1-2 business days of receiving them. Orders placed on weekends or holidays will be processed on the next business day.<br>

<b>Shipping Method:</b> We offer various shipping options depending on your location, including standard, expedited, and express shipping. The shipping cost will be calculated at checkout and will depend on the weight and destination of your order.<br>

<b>Delivery Time:</b> Delivery times will vary depending on the shipping method you choose and your location. Generally, orders shipped via standard shipping will arrive within 5-7 business days, expedited shipping will arrive within 3-5 business days, and express shipping will arrive within 1-2 business days.<br>

<b>Order Tracking:</b> Once your order is shipped, we will provide you with a tracking number via email, which you can use to track the status of your shipment.<br>

<b>Shipping Restrictions:</b> We currently only ship within the United States. We do not ship to PO boxes, APO/FPO addresses, or international addresses.<br>

<b>Order Modifications:</b> Once an order has been placed, we cannot modify or cancel it. If you wish to make changes to your order, please contact us as soon as possible and we will do our best to accommodate your request.<br>

<b>Shipping Delays:</b> Please note that shipping delays may occur due to unforeseen circumstances such as weather conditions, natural disasters, or carrier delays. We will do our best to keep you informed of any delays and to get your order to you as soon as possible.<br>

<b>Shipping Damage:</b> In the rare event that your order arrives damaged, please contact us immediately with photos of the damage and we will work with you to resolve the issue.<br>

If you have any questions or concerns about our shipping policy, please do not hesitate to contact us. Thank you for choosing The Allchemy!





        </div>
    </div>
@endsection
