@extends('partnerV2.layout_with_menu')

@section('content')
    <div class="content">

        <!-- Top Bar Start -->
    @if($partnerAdmin)
        @include('partnerV2.include.topbar',['mainTitle'=>__('zT2Olw').' - '.$client->name])

    @else
        @include('partnerV2.include.topbar',['mainTitle'=>__('5lImAA')])

    @endif
    <!-- Top Bar End -->

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
                                        <span class="counter">{{ round((int)  $kpi->step_completed / (int) $kpi->engaged_ct, 0)}}</span>
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
                <!-- TAB SELECTION -->
                <div class="row m-t-30">
                    <div class="col-md-6 col-lg-6 col-xl-2">
                        <div class="clearfix tabs waves-effect active tabDashboard" data-href="#tabUser">
                            <p>{{__('5lImAA')}}</p>
                        </div>
                    </div>
                </div>
                <!-- TAB CONTAIN -->

                <!-- JOBMAKERS -->
                <div class="row justify-content-center tabBody active" id="tabUser">
                    @if(count($jobmakers)<1)
                        <div class="col-lg-12 col-md-12">
                            <div class="card m-b-20 m-t-20">
                                <div class="card-body text-center" style="height:490px;">
                                    <img src="/_partnerV2/images/invitation_waiting.svg"
                                         style="width:125px;margin-top:73px;margin-bottom:70px;"/>
                                    <p>{{__('YK0IiJ')}}</p>
                                    <div style="margin-top:60px;">
                                        <a name="add_user" href="/jmaker/invitation"
                                           class="btn-outline-primary btn modal-remote ff-tooltip-left"
                                           data-target="#modal-remote" title="" data-size="modal-lg"
                                           data-original-title="{{__('g5j2lx')}}"><i class="ti-user"></i>&nbsp;&nbsp;|&nbsp;&nbsp;{{__('g5j2lx')}}</a>
                                    </div>
                                    <div style="margin-top:60px;">
                                        <a href="{{ route('partner.help') }}" class="btn-link">{{__('sOh6n0')}}</a>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->
                    @else
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <table id="datatable-jobmakers"
                                           style="width:100%;font-size:13px;    overflow-x: auto;"
                                           class="table table-striped dt-responsive nowrap datatable-jobmakers"
                                           cellspacing="0">
                                        <thead>
                                        <tr style="font-weight: 300;">
                                            @if($partnerAdmin)
                                                <th>{{__('HCB2Hb')}}</th>
                                            @endif
                                            <th>{{__('FNuG4k')}}</th>
                                            <th>Email</th>
                                            <th>{{__('BNnbBi')}}</th>
                                            <th>{{__('yNb7LL')}}</th>
                                            <th>{{__('rXT2Y7')}}</th>
                                            @if(!$client->enableB2BasB2C)
                                            <th>{{__('gijdGs')}}</th>
                                            @endif
                                            <th>{{__('pZ8zl1')}}</th>
                                            <th>{{__('Cjxd2M')}}</th>
                                            <th class="text-center" style="width:30px">Action</th>
                                        </tr>
                                        <tr class="filter" role="row">
                                            @if($partnerAdmin)
                                            <th class="text-center" rowspan="1" colspan="1" id="prescriberSelect"
                                                style="border-bottom: 1px solid rgb(221, 221, 221)">
                                            </th>
                                            @endif
                                            <th class="text-center" rowspan="1" colspan="1"
                                                style="border-bottom: 1px solid rgb(221, 221, 221);">
                                                <input name="nameSearch" type="text" placeholder="{{__('GgBC3t')}}"
                                                       style="width: 100%"
                                                       class="text-center form-control-datatable" autocomplete="false">
                                            </th>
                                            <th class="text-center" rowspan="1" colspan="1"
                                                style="border-bottom: 1px solid rgb(221, 221, 221);">
                                                <input name="email" type="text" placeholder="{{__('GgBC3t')}}"
                                                       style="width: 100%" class="text-center form-control-datatable" autocomplete="false">
                                            </th>
                                            <th class="text-center" rowspan="1" colspan="1"
                                                style="border-bottom: 1px solid rgb(221, 221, 221);" id="campaignSelect">
                                            </th>
                                            <th class="text-center" rowspan="1" colspan="1"
                                                style="border-bottom: 1px solid rgb(221, 221, 221);">

                                            </th>
                                            <th class="text-center" rowspan="1" colspan="1"
                                                style="border-bottom: 1px solid rgb(221, 221, 221);" id="activateSelect">

                                            </th>
                                            @if(!$client->enableB2BasB2C)
                                            <th class="text-center" rowspan="1" colspan="1"
                                                style="border-bottom: 1px solid rgb(221, 221, 221);"></th>
                                            @endif
                                            <th class="text-center" rowspan="1" colspan="1"
                                                style="border-bottom: 1px solid rgb(221, 221, 221);"></th>
                                            <th class="text-center" rowspan="1" colspan="1"
                                                style="border-bottom: 1px solid rgb(221, 221, 221);"></th>
                                            <th class="text-center" rowspan="1" colspan="1"
                                                style="border-bottom: 1px solid rgb(221, 221, 221);"></th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        @foreach($jobmakers as $jobmaker)
                                            <tr>
                                                @if($partnerAdmin)
                                                    <td> {{$jobmaker->prescriber_name}}</td>
                                                @endif
                                                <td data-target-href="/jmaker/{{$jobmaker->uuid}}"
                                                    data-target="#modal-remote"
                                                    data-size="modal-lg"
                                                    data-method="get"
                                                    style="cursor: pointer;font-weight: 500;"
                                                    class="name">
                                                    {{$jobmaker->jmaker_name}}
                                                </td>
                                                <td>{{$jobmaker->email}}</td>
                                                <td>{{$jobmaker->campaign_name}}</td>
                                                <td><!--{{$jobmaker->created_at_iso}}-->{{$jobmaker->created_at}}</td>
                                                <td>{{$jobmaker->is_active ? __('Pf8nQm') : __('MDDXid')}}</td>
                                                @if(!$client->enableB2BasB2C)
                                                <td>
                                                    @if($jobmaker->is_completed)
                                                        @if(empty($jobmaker->meeting_date))
                                                            -
                                                        @else
                                                        <!--{{$jobmaker->meeting_date_iso}}-->{{$jobmaker->meeting_date }}
                                                        @endif
                                                    @else
                                                        @if(empty($jobmaker->meeting_date_invitation))
                                                            -
                                                        @else
                                                        <!--{{$jobmaker->meeting_date_iso_invitation}}-->{{$jobmaker->meeting_date_invitation }}
                                                        @endif
                                                    @endif
                                                </td>
                                                @endif
                                                <td>
                                                    @if(!$jobmaker->is_completed)
                                                        -
                                                    @else
                                                        {{$jobmaker->completed_ct > 0 ? $jobmaker->completed_ct : "-"}}
                                                    @endif</td>
                                                <td>@if(!$jobmaker->is_completed)
                                                        -
                                                    @else
                                                        @if(empty($jobmaker->last_page_at))
                                                            -
                                                        @else
                                                        <!--{{$jobmaker->last_page_at_iso}}-->{{$jobmaker->last_page_at}}
                                                        @endif
                                                    @endif</td>
                                                <td class="tabAction">
                                                    <div class="tabItemBottom dropdown ">
                                                    @if($jobmaker->is_shared && !$client->enableB2BasB2C)
                                                        <a target="_blank" data-html="true"
                                                           class="btn-xs btn colorBlack"
                                                           data-toggle="tooltip"
                                                           data-placement="left"
                                                           href="/jmaker/rapport/{{$jobmaker->uuid}}"
                                                           title="{{__('eoxh8V')}} {{$jobmaker->shared_at}}"><i><img src="/images/svg/jobmakerFile.svg" width="20px" height="20px"></i>
                                                        </a>
                                                    @endif

                                                        <button class="btn tabBtnAction" type="button"
                                                                id="dropdownMenuButton" data-toggle="dropdown"
                                                                aria-haspopup="true" aria-expanded="false">
                                                            <i class="ti-more-alt"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-right"
                                                            aria-labelledby="dropdownMenuButton">
                                                            <li>
                                                                <a data-method="get"
                                                                   class=" tabAction modal-remote waves-effect colorBlack"
                                                                   href="/jmaker/{{$jobmaker->uuid}}"
                                                                   data-target="#modal-remote" data-size="modal-lg"><i
                                                                            class="ti-zoom-in"></i>{{__('V4dvuO')}}</a></li>

                                                        @if($jobmaker->is_completed)
                                                                <li>
                                                                    <a data-method="post"
                                                                       class="tabAction modal-remote waves-effect"
                                                                       href="/mail/send/{{$jobmaker->uuid}}"
                                                                       data-target="#modal-remote" data-size="modal-lg"><i
                                                                                class="ti-loop"></i>{{__('odmme6')}}</a></li>
                                                                @if(!$client->enableB2BasB2C)
                                                                <li><a data-method="post"
                                                                       class="tabAction modal-remote waves-effect colorYelow"
                                                                       href=<?php if($jobmaker->meeting_date){ echo "/jmaker/meetingdate/".$jobmaker->invitationUUID."?postpone=true";}else{echo "/jmaker/meetingdate/".$jobmaker->invitationUUID;} ?>
                                                                       data-target="#modal-remote" data-size="modal-lg"
                                                                       data-original-title="Date de debrief"><i
                                                                                class="ti-calendar"></i>{{empty($jobmaker->meeting_date) ? __('RsnWkg') : __('HyeY8u')}}
                                                                    </a></li>
                                                                @endif
                                                            @endif
                                                            @if(!$jobmaker->is_completed)
                                                                <li><a data-method="get"
                                                                       class="tabAction modal-remote waves-effect"
                                                                       href="/jmaker/invitation/{{$jobmaker->invitationUUID}}"
                                                                       data-target="#modal-remote" data-size="modal-lg"
                                                                       data-original-title="Relance">
                                                                        <i class="ti-loop"></i>
                                                                        {{__('odmme6')}}</a></li>
                                                                <li><a data-method="delete"
                                                                       class="tabAction modal-remote waves-effect colorRed"
                                                                       href="/jmaker/{{$jobmaker->uuid}}"
                                                                       data-target="#modal-remote" title=""
                                                                       data-original-title="Delete"><i
                                                                                class="fa fa-trash-o"></i>{{__('BCZrjq')}}</a>
                                                                </li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @endsection

        @section('inline')

            <script language="JavaScript">
                $(document).ready(function () {

                    $('.name').mouseover('td',function() {

                        $(this).css("font-weight","bolder");
                        $(this).css("color","#39cfb4");
                    }).mouseout(function() {
                        $(this).css("font-weight","500");
                        $(this).css("color","#212529");

                    });

                    jQuery.fn.dataTableExt.oApi.fnStrainer = function (oSettings) {

                        var _that = this;// on conserve l'objet principale

                        this.each(function (i) {
                            jQuery.fn.dataTableExt.iApiIndex = i;

                            // input:text
                            jQuery(_that).find(' th > input').each(function () {
                                if($(this).val() == "--all--") {
                                    $(this).val("")
                                }
                                _that.api().columns($(this).attr('name') + ':name').search($(this).val())
                            });

                            _that.api().draw()
                        });
                        return this
                    };


                    /**
                     * Assignation des evenement de filtre sur les strainer
                     *
                     * @param oSettings
                     * @returns {jQuery.fn.dataTableExt.oApi}
                     */
                    jQuery.fn.dataTableExt.oApi.fnFilterColumns = function (oSettings) {

                        var _that = this;// on conserve l'objet principale

                        this.each(function (i) {

                            jQuery.fn.dataTableExt.iApiIndex = i;

                            // input:text
                            _that.find('input').each(function () {
                                $(this).off();
                                $(this).on('keyup change', function (e) {
                                    _that.fnStrainer()
                                })
                            })
                        });
                        return this
                    };

                    $(function () {
                        $('[data-toggle="tooltip"]').tooltip()
                    });

                    //$('#datatable').DataTable()
                    //buttons: ['copy', 'excel', 'pdf', 'colvis']
                    //Buttons examples
                    var table = $('#datatable-jobmakers').dataTable({
                        orderCellsTop: true,
                        searching: true,
                        //filter: false,
                        deferRender: false,
                        columns: [
                                @if($partnerAdmin)
                            { name: 'prescriber' },
                            @endif
                            { name: 'nameSearch' },
                            { name: 'email' },
                            { name: 'campaign' },
                            { name: 'invitedAt' , type:'string' },
                            { name: 'activate' },
                            @if(!$client->enableB2BasB2C)
                            { name: 'meetingDate' ,type:'string' },
                            @endif
                            { name: 'workshop' },
                            { name: 'lastPage' ,type:'string' },
                            { name: 'action' },
                        ],
                        columnDefs: [
                                @if($partnerAdmin)
                            {
                                targets: 2, visible: false
                            },
                                @else
                            {
                                targets: 1, visible: false
                            },
                            @endif
                        ],
                        lengthChange: false,
                        buttons: [{
                            extend: 'copy',
                            text: '{{__('JbiN41')}}',
                            exportOptions: {
                                columns: [ 0, 1, 2, 3 , 4 , 5 , 6 , 7 , 8 ]
                            }
                        }, {
                            extend: 'excelHtml5',
                            text: 'Excel',
                            exportOptions: {
                                columns: [ 0, 1, 2, 3 , 4 , 5 , 6 , 7 , 8 ]
                            }
                        }, {
                            extend: 'pdfHtml5',
                            orientation: 'landscape',
                            pageSize: 'LEGAL',
                            exportOptions: {
                                columns: [ 0, 1, 2, 3 , 4 , 5 , 6 , 7 , 8]                            }
                            },
                            'colvis'
                        ],
                        "language": {
                            "sProcessing": "Traitement en cours...",
                            "sSearch": "Rechercher&nbsp;:",
                            "sLengthMenu": "Afficher _MENU_ &eacute;l&eacute;ments",
                            "sInfo": "{{__('1lpp9H')}}",
                            "sInfoEmpty": "Affichage de l'&eacute;l&eacute;ment 0 &agrave; 0 sur 0 &eacute;l&eacute;ment",
                            "sInfoFiltered": "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
                            "sInfoPostFix": "",
                            "sZeroRecords": "Aucun &eacute;l&eacute;ment &agrave; afficher",
                            "sEmptyTable": "Aucune donn&eacute;e disponible dans le tableau",
                            "oPaginate": {
                                "sFirst": "Premier",
                                "sPrevious": "{{__('4kbxIr')}}",
                                "sNext": "{{__('2kKTNe')}}",
                                "sLast": "Dernier"
                            },
                            "oAria": {
                                "sSortAscending": ": activer pour trier la colonne par ordre croissant",
                                "sSortDescending": ": activer pour trier la colonne par ordre d&eacute;croissant"
                            }
                        },
                        initComplete: function () {

                            this.api().columns('activate:name').every( function () {
                                var column = this;
                                var select = $('<select class="form-control-datatable" style="width: 100%;"><option value=""></option></select>')
                                    .appendTo( $('#activateSelect'))
                                    .on( 'change', function () {
                                        var val = $(this).val();

                                        if(val === "--all--") {
                                            val = ""
                                        }

                                        val = $.fn.dataTable.util.escapeRegex(
                                            val
                                        );

                                        column
                                            .search( val ? '^'+val+'$' : '', true, false )
                                            .draw();
                                    } );

                                select.append('<option value="--all--"><b>{{__("GgBC3t")}}</b></option>');

                                column.data().unique().sort().each( function ( d, j ) {
                                    select.append( '<option value="'+d+'">'+d+'</option>' )
                                } );

                                select.select2({
                                    placeholder: '{{__("GgBC3t")}}'
                                });
                            } );
                        }
                    }).fnFilterColumns();

                    $('#datatable-jobmakers').on('column-visibility.dt', function ( e, settings, column, state ) {
                        $('#datatable-jobmakers').dataTable({"retrieve": true}).fnFilterColumns()

                    } );

                    $('#datatable-jobmakers tbody').on('click', 'td', function (e) {

                        if (jQuery(this).data('target')) {
                            e.preventDefault();

                            var target = jQuery(this).data('target');
                            var size = jQuery(this).data('size');
                            var url = jQuery(this).data('target-href');

                            $data = {};
                            if (jQuery(this).data('method')) {
                                $data = {_method: jQuery(this).data('method')}
                            }

                            //console.log(target)
                            //console.log(size)
                            //console.log(url)
                            jQuery(target)
                                .find('.modal-content')
                                .empty()
                                .load(url, $data, function () {
                                    jQuery(this).initialize();
                                    jQuery(this).parent().removeClass('modal-lg modal-sm').addClass(size);
                                    jQuery(target).modal('show');
                                    jQuery(target).on('hidden.bs.modal', function () {
                                        jQuery(this).find('.modal-content').html('')
                                    })
                                });
                            e.stopImmediatePropagation()
                        }
                    });

                    $('#datatable-jobmakers').on('draw.dt', function () {

                        $('.modal-remote').each(function () {

                            jQuery(this).click(function (e) {
                                e.preventDefault();

                                var target = jQuery(this).data('target');
                                var size = jQuery(this).data('size');
                                $url = jQuery(this).attr('href');

                                $data = {};
                                if (jQuery(this).data('method')) {
                                    $data = {_method: jQuery(this).data('method')}
                                }

                                jQuery(target)
                                    .find('.modal-content')
                                    .empty()
                                    .load($url, $data, function () {
                                        jQuery(this).initialize();
                                        jQuery(this).parent().removeClass('modal-lg modal-sm').addClass(size);
                                        jQuery(target).modal('show');
                                        jQuery(target).on('hidden.bs.modal', function () {
                                            jQuery(this).find('.modal-content').html('')
                                        })
                                    });
                                e.stopImmediatePropagation()
                            })
                        })
                    });

                    table.api().buttons().container()
                        .appendTo('#datatable-jobmakers_wrapper .col-md-6:eq(1)');


                    //click event to each `tabs` element
                    $('.tabs').on('click', function (e) {
                        e.preventDefault();
                        $('.tabs').removeClass('active'); //remove active class from all the tabs
                        $(this).addClass('active'); //add active to current clicked element
                        var target = $(this).attr('data-href'); //get its href attrbute
                        $('.tabBody').removeClass('active'); //remove active from tabBody and hide all of them
                        $(target).addClass('active') //show target tab and add active class to it
                    });

                    $('.dropdown-menu').parent().on('shown.bs.dropdown', function () {
                        var $menu = $("ul", this);
                        offset = $menu.offset();
                        position = $menu.position();
                        $('body').append($menu);
                        $menu.show();
                        $menu.css('position', 'absolute');
                        $menu.css('top', (offset.top) + 'px');
                        $menu.css('left', (offset.left) + 'px');
                        $(this).data("myDropdownMenu", $menu)
                    });
                    $('.dropdown-menu').parent().on('hide.bs.dropdown', function () {
                        $(this).append($(this).data("myDropdownMenu"));
                        $(this).data("myDropdownMenu").removeAttr('style')

                    })

                })

            </script>
@endsection

