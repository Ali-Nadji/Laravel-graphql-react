@extends('jobmaker.layout')

@php
    /**
     * @var \App\Services\Way\WayService $wayService
     * @var \Models\Db\Mission\Run $run
     * @var \Models\Db\Mission\Mission $mission
     */

$require = $wayService->getRequire();

@endphp


@section('content')

    <div class="container">
        <div class="container-table">
            <div class="row">
                @if($synthesisAvailable)
                    <div class="col-xs-12 col-sm-6 col-lg-4 wia-summary">
                @else
                    <div class="col-xs-12 col-sm-8 wia-summary">
                @endif
                        <div class="pull-left" style="margin-right: 10px;">
                            <input type="text" value="{{$wayService->getCompletion()}}%" class="knob-jobmaker"
                                   data-fgColor="#5dcca8"
                                   data-width="120"
                                   data-height="120" readonly>
                        </div>

                        {{--MON PARCOURS--}}
                        <p>{!! __('Z5tBn1') !!} <a href="#" id="pop-way" style="margin-left:5px;">
                                <i class="fa fa-question-circle"></i>
                            </a>
                        </p>
                        <p style="font-size: 1.8em;text-transform: uppercase;margin:0 0 5px;">{{$wayService->getSequenceName()}}</p>
                        <div class="wia-completion">
                            @foreach($wayService->getRuns() as $run)
                                @php($mission = $missions->where('id', $run->mission_id)->first())
                                @if(object_get($mission, 'type_rid') != \Ref::MISSION_TYPE_WORKSHOP)
                                    @continue
                                @endif
                                <a href="{{ route('mission.start', [$mission->slug])}}">
                                    <img width="32"
                                         style="margin-top:3px;"
                                         src="{{$run->isFinished() ? $mission->icone_url : $mission->icone_grey_url}}"
                                         data-toggle="tooltip" title="{{__($mission->content)['title']}}">
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-4 wia-summary">
                        @if(!empty($current))

                            @php($mission = $missions->where('id', $current->mission_id)->first())
                            <?php
                            $recoIconURL = $mission->icone_url;

                            if ($recoIconURL == "/_jobmaker/img/01-detox.png" & Session::get('local') == 'LANG_EN') {
                                $recoIconURL = explode(".", $recoIconURL)[0] . "-EN.png";
                            }
                            ?>
                            <div class="text-center wia-main pull-left">
                                <a href="{{ route('mission.start', [$mission->slug])}}">
                                    <img src="{{$recoIconURL}}" title="{{__($mission->content)['title']}}"
                                         style="margin:0px 15px 0px 0px;">
                                </a>
                            </div>
                            <p>{!! __('NJFapA') !!}
                                <a href="#" id="pop-continue" style="margin-left:5px;">
                                    <i class="fa fa-question-circle"></i>
                                </a>
                            </p>
                            <div>
                                <p style="font-size: 1.8em;text-transform: uppercase;margin-bottom:20px;">
                                    {{__($mission->content)['title']}}
                                </p>
                                <a href="{{ route('mission.start', [$mission->slug])}}"
                                   style=""
                                   class="btn btn-lg btn-primary">
                                    {{$current->isAccessible() ? __('IuYzyv') :  __('NXOXWh') }}
                                </a>
                                @if($nextMeetingDate && !$synthesisAvailable && $showNextMeeting)
                                    <p style="margin-top:15px;">{{ sprintf(__('3dz0I0'),$nextMeetingDate) }}</p>
                                @endif
                            </div>

                        @endif
                    </div>

                        @if($synthesisAvailable)
                                <div class="col-xs-12 col-sm-4 wia-summary">
                                    <p>{!! __('6SCyI1') !!}
                                        @if(!jmaker()->isB2c())
                                            @if($showSharedSynthesisButton)
                                                <a href="#" id="pop-synthese" style="margin-left:5px;">
                                                    @if($lastShareDate->eq(\Carbon\Carbon::minValue()))
                                                        <i style="display:none;"
                                                           class="fa fa-exclamation-circle text-danger">{!! __('76nhCU') !!}</i>
                                                    @else
                                                        <i style="display:none;"
                                                           class="fa fa-exclamation-circle text-danger">{{ sprintf(__('CAKbum'),(new Jenssegers\Date\Date($lastShareDate))->format('j F Y') , $prescriberName)}}
                                                            <br/>{!! __('8Ba0pa')!!}</i>
                                                    @endif
                                                </a>
                                            @else
                                                <a href="#" id="pop-synthese" style="margin-left:5px;">
                                                    @if($samePrescriber)
                                                            <i style="display:none;"
                                                               class="fa fa-info-circle">{{ sprintf(__('CAKbum'),(new Jenssegers\Date\Date($lastShareDate))->format('j F Y') , $prescriberName)}}</i>
                                                    @else
                                                        <i style="display:none;"
                                                           class="fa fa-exclamation-circle text-danger">{!! __('76nhCU') !!}</i>
                                                    @endif
                                                </a>
                                            @endif
                                        @endif
                                    </p>
                                    <!-- Shared -->
                                    @if((!$showSharedSynthesisButton && $samePrescriber == true) || (!$showSharedSynthesisButton))
                                        {{-- Only Synthese button --}}
                                        @if(jmaker()->isB2c())
                                            {!! __('pqcddY') !!}
                                        @else
                                            {!! __('Inr4bo') !!}
                                        @endif
                                        <a class="btn btn-info btn-lg" href="{{route('jobmaker.rapport')}}"
                                           target="_blank">
                                            {!! __('WqIxkA') !!}
                                        </a>
                                    @else
                                        {{-- Synthese and shared buttons --}}
                                        {!! __('pqcddY') !!}
                                        <a class="btn btn-info btn-lg" href="{{route('jobmaker.rapport')}}"
                                           target="_blank">
                                            {!! __('WqIxkA') !!}
                                        </a>

                                        @if(!jmaker()->isB2c())
                                        <!-- Ajax Request -->
                                                <a href="#" id="pop-shared"
                                                   style="color:#ffffff"
                                                   class="btn btn-warning btn-lg callback-remote">
                                                    {!! __('gItMrQ') !!}
                                                </a>
                                            <!--Modal de confirmation avant partage synthese ===================================================================================-->
                                            <div class="modal fade" tabindex="-1" role="dialog"
                                                 aria-labelledby="mySmallModalLabel" aria-hidden="true" id="my-modal">
                                                <div class="modal-dialog modal-dialog-centered" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                    aria-label="Close"><span aria-hidden="true"><i
                                                                            class="fa fa-times" aria-hidden="true"></i></span>
                                                            </button>
                                                            <h4 class="modal-title" id="myModalLabel">{{__('kxJXrh')}}</h4>
                                                        </div>
                                                        <div class="modal-footer" style="align-content: center">
                                                            <button type="button" class="btn btn-default"
                                                                    id="modal-btn-yes">{{__('V30Bky')}}
                                                            </button>
                                                            <button type="button" class="btn btn-primary"
                                                                    id="modal-btn-no">{{__('CLHAXJ')}}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!--fin modal ===================================================================================-->
                                        @endif
                                    @endif
                                    @if($nextMeetingDate && $showNextMeeting)
                                            <p style="margin-top:15px;">{{ sprintf(__('3dz0I0'),$nextMeetingDate) }}</p>
                                    @endif
                                </div>
                            @endif
                    </div>

                    <hr style="margin: 20px 0px 5px 0px;">

                    <div class="row wia">
                        <div class="col-sm-12">
                            <h2 style="margin: 30px 0px 30px 0px;font-size: 2em;font-weight: 100;text-transform: uppercase;">
                                {!! __('VFpAjk') !!}</h2>
                        </div>

                        @foreach($wayService->getRuns() as $run)


                            @php($mission = $missions->where('id', $run->mission_id)->first())

                            @if(object_get($mission, 'type_rid') != \Ref::MISSION_TYPE_WORKSHOP)
                                @continue
                            @endif

                            <?php
                            $iconURL = $mission->icone_url;
                            $iconGreyURL = $mission->icone_grey_url;

                            if ($iconURL == "/_jobmaker/img/01-detox.png" & Session::get('local') == 'LANG_EN') {
                                $iconURL = explode(".", $iconURL)[0] . "-EN.png";
                                $iconGreyURL = explode(".", $iconGreyURL)[0] . "-EN.png";
                            }
                            ?>

                            <div class="col-xs-12 col-sm-6 col-lg-4">

                                @if($run->isInProgress())
                                    <div class="wia-mission wia-inprogress">
                                        <div class="text-center wia-main pull-left">
                                            <a href="{{ route('mission.start', [$mission->slug])}}">
                                                <img src="{{$iconURL}}" title="{{__($mission->content)['title']}}">
                                            </a>
                                        </div>

                                        <div class="text-left wia-options">
                                            <div>
                                                <span class="wia-title">{{__($mission->content)['title']}}</span>
                                            </div>
                                            <div><i class="fa fa-spinner" aria-hidden="true"></i>{!! __('93FAzO') !!}
                                            </div>
                                            <div class="wia-cta">
                                                <a href="{{ route('mission.start', [$mission->slug])}}"
                                                   class="btn btn-default">
                                                    {!! __('NXOXWh') !!}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($run->isAccessible())

                                    <div class="wia-mission">
                                        <div class="text-center wia-main pull-left">
                                            <a href="{{ route('mission.start', [$mission->slug])}}">
                                                <img style="" src="{{$iconURL}}"
                                                     title="{{__($mission->content)['title']}}">
                                            </a>
                                        </div>

                                        <div class="text-left wia-options">
                                            <div>
                                                <span class="wia-title">{{__($mission->content)['title']}}</span>
                                            </div>
                                            <div class="wia-cta">
                                                <a href="{{ route('mission.start', [$mission->slug])}}"
                                                   class="btn btn-default">
                                                    {!! __('IuYzyv') !!}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($run->isVisible())

                                    <div class="wia-mission">
                                        <div class="text-center wia-main pull-left">
                                            <a href="{{ route('mission.start', [$mission->slug])}}">
                                                <img src="{{$iconGreyURL}}"
                                                     title="{{__($mission->content)['title']}}">
                                            </a>
                                        </div>

                                        <div class="text-left wia-options">
                                            <div>
                                                <span class="wia-title">{{__($mission->content)['title']}}</span>
                                            </div>
                                            <div class="wia-require">
                                                @foreach($require->where('mission', $mission->id)->first()['conditions'] as $condition)
                                                    @php($m = $missions->where('id', $condition['mission'])->first())

                                                    <?php
                                                    $mIconURL = $m->icone_url;
                                                    $mIconGreyURL = $m->icone_grey_url;

                                                    if ($mIconURL == "/_jobmaker/img/01-detox.png" & Session::get('local') == 'LANG_EN') {
                                                        $mIconURL = explode(".", $mIconURL)[0] . "-EN.png";
                                                        $mIconGreyURL = explode(".", $mIconGreyURL)[0] . "-EN.png";
                                                    }
                                                    ?>

                                                    @if($condition['is_valid'])
                                                        <a href="{{ route('mission.start', [$m->slug])}}">
                                                            <img width="32" src="{{$mIconURL}}"
                                                                 data-toggle="tooltip"
                                                                 title="{{__($m->content)['title']}}">
                                                        </a>
                                                    @else
                                                        <a href="{{ route('mission.start', [$m->slug])}}">
                                                            <img width="32" src="{{$mIconGreyURL}}"
                                                                 data-toggle="tooltip"
                                                                 title="{!! __('LwmJer') !!} {{__($m->content)['title']}}">
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                            <div class="wia-cta">
                                                <a href="{{ route('mission.start', [$mission->slug])}}"
                                                   class="btn btn-default">
                                                    {!! __('9FBvTi') !!}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($run->isFinished())
                                    <div class="wia-mission wia-finished">
                                        <div class="text-center wia-main pull-left">
                                            <a href="{{ route('mission.start', [$mission->slug])}}">
                                                <img src="{{$iconURL}}" title="{{__($mission->content)['title']}}">
                                            </a>
                                        </div>

                                        <div class="text-left wia-options">
                                            <div>
                                                <span class="wia-title">{{__($mission->content)['title']}}</span>
                                            </div>
                                            <div><i class="fa fa-check"></i>{!! __('rL9dyP') !!}</div>
                                            <div class="wia-cta">
                                                <a href="{{ route('mission.start', [$mission->slug])}}"
                                                   class="btn btn-default">
                                                    {!! __('VOYoT9') !!}
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                @endif
                            </div>

                            <hr class="visible-xs">
                        @endforeach
                    </div>
            </div>
        </div>


    @if($newUser)
            <div class="modal fade" role="dialog" id="modal-remote" data-show="true" style="adding-right: 15px;">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Fermer"><span
                                        aria-hidden="true"><i class="fa fa-times" aria-hidden="true"></i></span>
                            </button>
                            <h4 class="modal-title">{!! __('7RFWMo') !!}</h4></div>
                        <div class="modal-body">
                            <div class="divide20"></div>
                            <p class="mb20 lead team-p" style="padding:10px;">{!! __('3MJzZP') !!}</p>

                            <div class="divide20"></div>
                            <div class="text-center">
                                <button type="button" class="btn btn-danger btn-lg" style="margin:20px;"
                                        data-dismiss="modal" aria-label="Fermer"><span
                                            aria-hidden="true">{!! __('Ys95Rj') !!}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($needToAcceptCGU) && $needToAcceptCGU)
            <div class="modal" role="dialog" id="modal-cgu" data-show="true" style="adding-right: 15px;"
                 data-backdrop="static" keyboard="false">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">{!! __('qQQqBX') !!}</h4></div>
                        <div class="modal-body">
                            <div class="divide20"></div>
                            <p class="mb20 lead team-p" style="padding:10px;">{!! __('Wes1u9') !!}</p>
                            <p><a href="{{route('jobmaker.legal_cgu')}}" target="_blank">{!! __('JqH9yK') !!}</a></p>
                            <div class="divide20"></div>
                            <div class="text-center">
                                <button id="modal-cgu-button" type="button" class="btn btn-danger btn-lg"
                                        style="margin:20px;" aria-label="ok" data-cgu-uuid="{{$lastCGUID}}"
                                        data-href="{{route('jobmaker.legal_cgu.post')}}"><span
                                            aria-hidden="true">{!! __('qa591s') !!}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>

@endsection

@section('inline')

    <script type="application/javascript">

        @if(!empty($needToAcceptCGU) && $needToAcceptCGU)
        jQuery("#modal-cgu").modal('show')
        jQuery("#modal-cgu-button").on('click', function (e) {
            e.preventDefault()

            var target = jQuery(this).data('target')
            $url = jQuery(this).data('href')

            var cgu_uuid = jQuery(this).data('cgu-uuid')

            $data = {cgu_uuid}

            jQuery.post(
                $url,
                $data,
                function (dataReturn) {
                    if (dataReturn.success) {
                        jQuery("#modal-cgu").modal('hide')
                    }
                })
            e.stopImmediatePropagation()
        })

        @endif

        @if($newUser)

        jQuery("#modal-remote").modal('show')
        {{--
        //
            .load("{{ route('mission.evaluation', $_mission->slug) }}", function () {
                jQuery(this).initialize()
                jQuery("#modal-remote").modal('show')
                jQuery("#modal-remote").on('hidden.bs.modal', function () {
                    jQuery(this).find('.modal-content').html('')
                })
            })--}}

        @endif

        $(function () {
            $('#pop-way').popover({
                placement: 'bottom',
                trigger: 'hover',
                html: true,
                content: "{!! __('z4Vldj') !!}"
            })

            @if(!jmaker()->isB2c())
                liValue = $('#pop-synthese i').html()
            $('#pop-synthese i').text("")
            $('#pop-synthese i').show("fast", "swing")
            $('#pop-synthese').popover({
                placement: 'bottom',
                trigger: 'hover',
                html: true,
                content: liValue
            })
            @endif

            $('#pop-continue').popover({
                placement: 'bottom',
                trigger: 'hover',
                html: true,
                content: "{!! __('fgQNNg') !!}"
            })

            $('#pop-shared').popover({
                placement: 'bottom',
                trigger: 'hover',
                html: true,
                content: "{{ sprintf(__('bnHNxi'), $prescriberName)}}"
            })

            //JS pour la modal de confirmation avant partage synthese
            var modalConfirm = function (callback) {

                $("#pop-shared").on("click", function () {
                    $("#my-modal").modal('show')
                })

                $("#modal-btn-yes").on("click", function () {
                    callback(true)
                    $("#my-modal").modal('hide')
                })

                $("#modal-btn-no").on("click", function () {
                    callback(false)
                    $("#my-modal").modal('hide')
                })
            }

            modalConfirm(function (confirm) {
                if (confirm) {
                    //Action si oui
                    window.location = "{{route('jobmaker.rapport.access')}}"
                }
            })
        })
    </script>
@endsection