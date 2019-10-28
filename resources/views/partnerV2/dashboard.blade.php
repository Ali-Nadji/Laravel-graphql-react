@extends('partnerV2.layout_with_menu')

@php
    /** @var Client $client */use Models\Db\Clients\Client;
@endphp

@section('content')

    <div class="content">

        <!-- Top Bar Start -->
    @include('partnerV2.include.topbar',['mainTitle'=>__('Ne7gN9')])
    <!-- Top Bar End -->
        @if (Session::has('success'))
            <div class="alert alert-info">
                {!! Session::get('success') !!}
            </div>
        @elseif(Session::has('error'))
            <div class="alert alert-danger">
                {!! Session::get('error') !!}
            </div>
        @endif
        <div class="page-content-wrapper ">
            <div class="container-fluid">
                <div class="row" style="margin-left: -5px;margin-right: -5px;">
                    <div class="col-md-6 col-lg-6 col-xl-2 bg-white" style="padding: 0 0 0 5px;">
                        <div class="mini-stat clearfix bg-white">
                            <div style="background-color: red;"></div>
                            <div class="mini-stat-info text-center text-white">
                                <span class="counter">{{(int)$kpi->invitation_ct}}</span>
                                @if(session('local') == "LANG_FR")
                                    <span class="text">{{__('U5BSK4')}}{{$kpi->invitation_ct > 1 ?  "s" : ""}}</span>
                                @else
                                    <span class="text">{{__('U5BSK4')}}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-2 p-0 bg-white">
                        <div class="mini-stat clearfix bg-white">
                            <div class="mini-stat-info text-center text-white">
                                <span class="counter">{{(int)$kpi->active_ct}}</span>
                                @if(session('local') == "LANG_FR")
                                    <span class="text">{{__('rXT2Y7')}}{{$kpi->active_ct > 1 ?  "s" : ""}}</span>
                                @else
                                    <span class="text">{{__('rXT2Y7')}}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-2 p-0 bg-white">
                        <div class="mini-stat clearfix bg-white">
                            <div class="mini-stat-info text-center text-white">
                                <span class="counter">{{(int)$kpi->engaged_ct}}</span>
                                @if(session('local') == "LANG_FR")
                                    <span class="text">{{__('rpzHBs')}}{{$kpi->engaged_ct > 1 ?  "s" : ""}}</span>
                                @else
                                    <span class="text">{{__('rpzHBs')}}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-2 p-0 bg-white">
                        <div class="mini-stat clearfix bg-white">
                            <div class="mini-stat-info text-center text-white">
                                <span class="counter">{{(int)$kpi->completed_ct}}</span>
                                @if(session('local') == "LANG_FR")
                                    <span class="text">{{__('Ov08SF')}}{{$kpi->completed_ct > 1 ?  "s" : ""}}</span>
                                @else
                                    <span class="text">{{__('Ov08SF')}}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if(!$client->enableB2BasB2C)
                    <div class="col-md-6 col-lg-6 col-xl-2 p-0 bg-white">
                        <div class="mini-stat clearfix bg-white">
                            <div class="mini-stat-info text-center text-white">
                                @if (!empty($kpi->engaged_ct))
                                    <span class="counter">{{ round((int) $kpi->step_completed / (int) $kpi->engaged_ct,0)}}</span>
                                @else
                                    <span class="counter">N/A</span>
                                @endif
                                <span class="text">{{__('abX7oi')}}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-2 bg-white" style="padding: 0 5px 0 0;">

                        <div class="mini-stat-last clearfix bg-white ">
                            <div class="mini-stat-info text-center text-white">
                                <span class="counter">{{$kpi->shared_ct ? $kpi->shared_ct : 0}}</span>
                                <span class="text">{{__('9nkBvT')}}</span>
                            </div>
                        </div>

                    </div>
                    @endif

                </div>
            </div>
            <div class="container-fluid" id="myTab">
                <!-- TAB CONTAIN -->
                <div class="page-content-wrapper">
                    <div class="container-fluid">
                        <link href="/css/app.css" rel="stylesheet">
                        <div id="dashboard"></div>
                        <script>let env = '{{ env('SESSION_DOMAIN') }}'</script>
                        <script src="js/app.js" type="text/javascript"></script>
                    </div>
                </div>

            </div><!-- container-fluid -->


        </div> <!-- Page content Wrapper -->

    </div> <!-- content -->

@endsection

@section('inline')

    <script language="JavaScript">
        $(document).ready(function () {
            //$('#datatable').DataTable()
            //buttons: ['copy', 'excel', 'pdf', 'colvis']
            //Buttons examples

            $('.tabItemName').mouseover('span',function() {

                $(this).css("font-weight","bolder");
                $(this).css("color","#39cfb4");
            }).mouseout(function() {
                $(this).css("font-weight","normal");
                $(this).css("color","#212529");

            });
            var table = $('#datatable-campaign').DataTable({
                lengthChange: false,
                buttons: []
            });

            table.buttons().container()
                .appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');


            //click event to each `tabs` element
            $('.tabs').on('click', function (e) {
                e.preventDefault();
                $('.tabs').removeClass('active'); //remove active class from all the tabs
                $(this).addClass('active'); //add active to current clicked element
                var target = $(this).attr('data-href'); //get its href attrbute
                $('.tabBody').removeClass('active'); //remove active from tabBody and hide all of them
                $(target).addClass('active') //show target tab and add active class to it
            })
        });

        $("document").ready(function(){
            setTimeout(function(){
                $("div.alert").remove();
            }, 8000 ); // 8 secs

        });

    </script>
@endsection
