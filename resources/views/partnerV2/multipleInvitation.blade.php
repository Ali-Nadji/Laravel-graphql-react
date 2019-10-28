@extends('partnerV2.layout_with_menu')

@section('content')
    <div class="content">

        <!-- Top Bar Start -->
    @include('partnerV2.include.topbar',['mainTitle'=>__('cqWNsV')])
    <!-- Top Bar End -->

        <div class="page-content-wrapper ">
            @if($result['importDone'])
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="col-sm-12 col-md-12">
                            <div class="card m-b-20">
                                <div class="card-body">
                                    <p>{{$result['invitationCount']}} {{__('G7zHvi')}}</p>
                                    <p>{{$result['duplicateInvitationCount']}} {{__('aSZKXt')}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-sm-12 col-md-12">
                        <div class="card m-b-20">
                            <div class="card-body">
                                {{-- <h5>10 minutes pour découvrir le coach Jobmaker, c’est maintenant!</h5>--}}
                                {{__('SZ3Zdm')}}
                                <div id="fileNotCompatible" style="text-align:center;color:red;display:none;">{{__('drsMNj')}}
                                </div>
                                <div id="fileContentNotCompatible"
                                     style="text-align:center;color:red;display:none;"></div>
                                <div id="fileContentCompatible" style="text-align:center;display:none;">
                                    <div class="m-b-20">
                                        <div class="card-header" id="headingOne"
                                             style="border:none;background-color:white;">
                                            <a id="fileContentCompatibleLink" data-toggle="collapse" data-parent="#accordion" href="#collapseOne"
                                               aria-expanded="false" aria-controls="collapseOne"
                                               class="text-dark collapsed">
                                            </a>
                                        </div>

                                        <div id="collapseOne" class="cardHelpBody collapse" aria-labelledby="headingOne"
                                             data-parent="#accordion" style="">
                                            <table id="datatable-campaign"
                                                   style="width:100%;font-size:12px;"
                                                   class="table table-striped dt-responsive nowrap" cellspacing="0"
                                                   width="100%">
                                                <thead>
                                                <tr>
                                                    <th>{{__('qpGvzN')}}</th>
                                                    <th>{{__('JdDJ6v')}}</th>
                                                    <th>Email</th>
                                                </tr>
                                                </thead>
                                                <tbody id="fileContentCompatibleTbody">
                                                <tr>
                                                    <td>LA</td>
                                                    <td>LA</td>
                                                    <td>LA</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div style="color: darkgray;">
                                    <p>{{__('HkoMcq')}}</p>
                                </div>
                                <form method="POST" role="form" class="form-horizontal" enctype="multipart/form-data">
                                    <div>
                                        <div class="container-fluid">
                                            <div class="row justify-content-center">
                                                <div class="col-sm-12 col-lg-6 col-md-10" style="margin-top:14px;">
                                                    <input id="input-multiple-invitation" name="file_csv" type="file" class="{{$errors->has('file_csv') ? 'has-danger' : ''}}">
                                                    <div class="form-control-feedback">{!!$errors->first('file_csv')!!}</div>
                                                </div>
                                            </div>
                                            <div class="row justify-content-center">
                                                <div class="col-sm-11 col-lg-4 col-xl-3 col-md-5" style="margin-top:14px;">
                                                    <p style="font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;">{{__('BNnbBi')}}</p>
                                                    <select class="{{$errors->has('campaign') ? 'has-danger' : ''}} form-control " name="campaign" id="campaign-input">
                                                        @foreach($campaigns as $item)
                                                            <option value="{{$item['uuid']}}" {{$item['uuid'] == $campaign ? " selected='selected'" : ""}}>{{$item['name']}}</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="form-control-feedback">{!!$errors->first('campaign')!!}</div>
                                                </div>
                                                <div class="col-sm-12 col-lg-4 col-xl-3 col-md-5" style="margin-top:14px;">
                                                    @if(count($languages) > 1)
                                                        <p style="font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;">{{__('pfLvWa')}}*</p>
                                                        <select class="form-control " name="language" id="language-input">
                                                            @foreach($languages as $item)
                                                                <option value="{{$item['id']}}" {{$item['id'] == $language ? " selected='selected'" : ""}}>{{$item['name']}}</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="form-control-feedback">{!!$errors->first('language')!!}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="row justify-content-center">
                                                <div class="col-sm-12 col-lg-6 col-md-10" style="margin-top:14px;">
                                                    <p style="font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;">Message</p>
                                                    <textarea id="message-input" name="message" class="form-control  {{$errors->has('message') ? 'has-danger' : ''}}" maxlength="800"
                                                              rows="6"
                                                              placeholder="">{{$message}}</textarea>
                                                    <div class="form-control-feedback">{!!$errors->first('message')!!}</div>
                                                </div>
                                            </div>
                                    <div class="row justify-content-center">
                                        <div class="col-sm-12 col-lg-12 col-md-12" style="margin-top:14px;">
                                            <div class="text-center">
                                                <button type="submit" name="send"
                                                        class="btn btn-primary button-title waves-effect waves-light"
                                                        style="margin-top: 20px;margin-right: 0px;font-weight: 600;font-size: 12.5px;">
                                                    <i class="ti-user"></i>&nbsp;&nbsp;|&nbsp;&nbsp;{{__('IbpDp2')}}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
    </div><!-- container-fluid -->
    </div> <!-- Page content Wrapper -->

    </div> <!-- content -->

@endsection


@section('inline')

    <script language="JavaScript">
        $(document).ready(function () {
            var local = "{{session('local')}}";
            if(local === 'LANG_FR'){
                local = 'fr';
            }else{
                local='en';
            }
            $('#input-multiple-invitation').fileinput({
                'allowedPreviewTypes': null,
                'showUpload': false,
                'showClose': false,
                'showPreview': true,
                'language': local,
                uploadAsync: false,
                'dropZoneEnabled': true,
                'showUploadedThumbs': false,
                'allowedFileTypes': ['text'],
                browseClass: "btn btn-primary button-title",
                removeClass: "btn btn-secondary button-title",
                previewFileIcon: '<i class="fa fa-file"></i>',
                fileActionSettings: {
                    showRemove: true,
                    showUpload: false,
                    showZoom: false,
                    showDrag: false,
                },
            });

            $('#input-multiple-invitation').on('fileclear', function (event) {
                $("#fileNotCompatible").hide();
                $("#fileContentNotCompatible").hide();
                $("#fileContentCompatible").hide()

            });

            $('#input-multiple-invitation').on('change fileloaded', function (event) {

                $("#fileNotCompatible").hide();
                $("#fileContentNotCompatible").hide();
                $("#fileContentCompatible").hide();

                Papa.parse(this.files[0], {
                    delimiter: ",",	// auto-detect
                    newline: "",	// auto-detect
                    quoteChar: '"',
                    escapeChar: '"',
                    header: false,
                    trimHeaders: false,
                    dynamicTyping: false,
                    preview: 0,
                    encoding: "",
                    worker: false,
                    comments: false,
                    step: undefined,
                    complete: parsedFile,
                    error: undefined,
                    download: false,
                    skipEmptyLines: true,
                    chunk: undefined,
                    fastMode: undefined,
                    beforeFirstChunk: undefined,
                    withCredentials: undefined,
                    transform: undefined
                });


                function parsedFile(results, file) {

                    if (results.errors.length) {
                        $("#fileNotCompatible").show()
                    } else {
                        if (validCSVResult(results.data)) {
                            showTableWithInvitation(results.data);
                        }
                    }
                    console.log("Parsing complete:", results, file)
                }

                function showTableWithInvitation(data){

                    var htmlTab = "";

                    data.forEach(function (invitation) {
                        htmlTab += "<tr><td>" + invitation[0] + "</td><td>" + invitation[1] + "</td><td>" + invitation[2] + "</td></tr>"
                    });

                    $("#fileContentCompatibleTbody").html(htmlTab);
                    $("#fileContentCompatibleLink").html("{{__('wffm6w')}}<b>" + data.length + " </b>{{__('3RnyFb')}}. <i class='ti-arrow-circle-down' style='color:#39cfb4;'></i>");
                    $("#fileContentCompatible").show()
                }

                function validCSVResult(data) {

                    var count = 0;
                    var valid = true;
                    var BreakException = {};

                    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

                    try {
                        data.forEach(function (invitation) {

                            if (invitation.length < 3) {
                                $("#fileContentNotCompatible").html("{{__('fCUMNm')}}<br/>" + invitation.join(',') + " <br/>{{__('Acu0XN')}}").show();
                                valid = false;
                                throw BreakException
                            }

                            if (invitation[0].length == 0 || invitation[1].length == 0) {
                                $("#fileContentNotCompatible").html("Le prénom et le nom sont obligatoires. <br/>Ligne non valide : <br/>" + invitation.join(',')).show();
                                valid = false;
                                throw BreakException
                            }

                            if (!re.test(String(invitation[2]).toLowerCase())) {
                                $("#fileContentNotCompatible").html("L'email dans la ligne <br/>" + invitation.join(',') + "<br/>ne semble pas valide").show();
                                valid = false;
                                throw BreakException
                            }

                        })
                    } catch (e) {
                        if (e !== BreakException) throw e
                    }

                    return valid
                }
            })
        })

    </script>
@endsection



