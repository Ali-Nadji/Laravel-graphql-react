@extends('partnerV2.layout_onboarding')

@section('content')

    <div class="content">
        <div class="row justify-content-center">
            <div class="col-6 text-center">
                <p style="font-size:21px;">Inviter un collaborateur</p>
            </div>
        </div>
        @if(count($campaigns) < 1)
            <div class="wrapper-page">
                <div class="card">
                    <div class="card-body">
                        <div class="ex-page-content text-center">
                            <h3>Aie !</h3><br>
                            <p>Vous n'êtes pas en mesure de faire des invitations</p>
                            <p>Votre administrateur Jobmaker doit vous donner les droits sur au moins une campagne
                                Jobmaker.</p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <form method="POST" role="form" class="form-horizontal">
                <div style="">
                    <div class="container-fluid">
                        <div class="row justify-content-center">
                            <div class="col-sm-12 col-lg-4 col-xl-3 col-md-5" style="margin-top:14px;">
                                <p style="font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;">Prénom*</p>
                                <input class="form-control {{$errors->has('$firstname') ? 'has-danger' : ''}}" name="firstname" type="text"
                                       value="{{$firstname}}"
                                       id="firstname-input" required="required">
                                <div class="form-control-feedback">{!!$errors->first('firstname')!!}</div>
                            </div>
                            <div class="col-sm-12 col-lg-4 col-xl-3 col-md-5" style="margin-top:14px;">
                                <p style="font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;">Nom*</p>
                                <input class="form-control {{$errors->has('lastname') ? 'has-danger' : ''}}" name="lastname" type="text"
                                       value="{{$lastname}}"
                                       id="lastname-input" required="required">
                                <div class="form-control-feedback">{!!$errors->first('lastname')!!}</div>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-sm-12 col-lg-4 col-xl-3 col-md-5" style="margin-top:14px;">
                                <p style="font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;">Email*</p>
                                <input class="form-control {{$errors->has('email') ? 'has-danger' : ''}}" type="email" value="{{$email}}"
                                       id="email-input"
                                       name="email" required="required">
                                <div class="form-control-feedback">{!!$errors->first('email')!!}</div>
                            </div>
                            <div class="col-sm-12 col-lg-4 col-xl-3 col-md-5" style="margin-top:14px;">
                                <p style="font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;">Debrief{{$meeting_date_required ? "*" : ""}}</p>
                                <div class="input-group">
                                    <input placeholder="{{($prescriber->language == 'LANG_EN' ? "mm/dd/yyyy" : "jj/mm/aaaa")}}"
                                           data-date-format="{{($prescriber->language == 'LANG_EN' ? "mm/dd/yyyy" : "dd/mm/yyyy")}}"
                                           name="meeting_date"
                                           class="form-control" value="{{$meeting_date}}"
                                           id="debrief-input"  {{$meeting_date_required ? 'required="required"' : ""}}>
                                <div class="input-group-append bg-custom b-0"><span
                                            class="input-group-text"><i
                                                class="mdi mdi-calendar"></i></span></div>
                                </div>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-sm-11 col-lg-4 col-xl-3 col-md-5" style="margin-top:14px;">
                                <p style="font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;">Campagne*</p>
                                <select class="form-control" name="campaign" id="campaign-input">
                                    @foreach($campaigns as $item)
                                        <option value="{{$item['uuid']}}" {{$item['uuid'] == $campaign ? " selected='selected'" : ""}}>{{$item['name']}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-12 col-lg-4 col-xl-3 col-md-5" style="margin-top:14px;">
                                @if(count($languages) > 1)
                                    <p style="font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;">Langue*</p>
                                    <select class="form-control" name="language" id="language-input">
                                        @foreach($languages as $item)
                                            <option value="{{$item['id']}}" {{$item['id'] == $language ? " selected='selected'" : ""}}>{{$item['name']}}</option>
                                        @endforeach
                                    </select>
                                 @endif
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-sm-12 col-lg-6 col-md-10" style="margin-top:14px;">
                                <p style="font-size:12px;font-weight: 300;padding-left:10px;margin-bottom: 0.2rem;">Message</p>
                                <textarea id="message-input" name="message" class="form-control  {{$errors->has('$message') ? 'has-danger' : ''}}" maxlength="800"
                                          rows="6"
                                          placeholder="">{{$message}}</textarea>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-sm-12 col-lg-6 col-md-6" style="margin-top:14px;">
                                <div class="text-right">
                                    <button type="submit" name="send"
                                            class="btn btn-primary button-title waves-effect waves-light"
                                            style="margin-top: 20px;margin-right: 0px;font-weight: 600;font-size: 12.5px;">
                                        <i class="ti-user"></i>&nbsp;&nbsp;|&nbsp;&nbsp;Envoyer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
{{--
                        <div class="row justify-content-center">
                            <div class="col-sm-12 col-lg-6 col-md-10 ">
                                <div class="card m-b-20">
                                    <div class="card-body">
                                        <div class="form-group row {{$errors->has('email') ? 'has-danger' : ''}}">
                                            <label for="email-input" class="col-sm-3 col-form-label">Email*</label>
                                            <div class="col-sm-9">
                                                <input class="form-control " type="email" value="{{$email}}"
                                                       id="email-input"
                                                       name="email" required="required">
                                                <div class="form-control-feedback">{!!$errors->first('email')!!}</div>
                                            </div>
                                        </div>
                                        <div class="form-group row {{$errors->has('firstname') ? 'has-danger' : ''}}">
                                            <label for="firstname-input" class="col-sm-3 col-form-label">Prénom*</label>
                                            <div class="col-sm-9">
                                                <input class="form-control" name="firstname" type="text"
                                                       value="{{$firstname}}"
                                                       id="firstname-input" required="required">
                                                <div class="form-control-feedback">{!!$errors->first('firstname')!!}</div>
                                            </div>
                                        </div>
                                        <div class="form-group row {{$errors->has('lastname') ? 'has-danger' : ''}}">
                                            <label for="lastname-input" class="col-sm-3 col-form-label">Nom*</label>
                                            <div class="col-sm-9">
                                                <input class="form-control" name="lastname" type="text"
                                                       value="{{$lastname}}"
                                                       id="lastname-input" required="required">
                                                <div class="form-control-feedback">{!!$errors->first('lastname')!!}</div>
                                            </div>
                                        </div>
                                        @if(count($languages) > 1)
                                            <div class="form-group row {{$errors->has('language') ? 'has-danger' : ''}}">
                                                <label for="language-input"
                                                       class="col-sm-3 col-form-label">Langue*</label>
                                                <div class="col-sm-9">
                                                    <select class="form-control" name="language" id="language-input">
                                                        @foreach($languages as $item)
                                                            <option value="{{$item['id']}}" {{$item['id'] == $language ? " selected='selected'" : ""}}>{{$item['name']}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="form-group row {{$errors->has('meeting') ? 'has-danger' : ''}}">
                                            <label for="debrief-input" class="col-sm-3 col-form-label">Date de
                                                debrief</label>
                                            <div class="col-sm-9 input-group">
                                                <input placeholder="jj/mm/aaaa"
                                                       data-date-format="dd/mm/yyyy"
                                                       name="meeting_date"
                                                       class="form-control" value="{{$meeting_date}}"
                                                       id="debrief-input">
                                                <div class="input-group-append bg-custom b-0"><span
                                                            class="input-group-text"><i
                                                                class="mdi mdi-calendar"></i></span></div>
                                            </div>
                                        </div>
                                        <div class="form-group row {{$errors->has('message') ? 'has-danger' : ''}}">
                                            <label for="message-input" class="col-sm-3 col-form-label">Message
                                                personnalisé</label>
                                            <div class="col-sm-9">
                                    <textarea id="message-input" name="message" class="form-control" maxlength="800"
                                              rows="3"
                                              placeholder="">{{$message}}</textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row {{$errors->has('campaign') ? 'has-danger' : ''}}">
                                            <label class="col-sm-3 col-form-label">Campagne</label>
                                            <div class="col-sm-9">
                                                <select class="form-control" name="campaign" id="campaign-input">
                                                    @foreach($campaigns as $item)
                                                        <option value="{{$item['uuid']}}" {{$item['uuid'] == $campaign ? " selected='selected'" : ""}}>{{$item['name']}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="text-right">
                                                <button type="submit" name="send"
                                                        class="btn btn-primary waves-effect waves-light">
                                                    Envoyer
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- end col -->
                        </div> <!-- end row -->
                        --}}
                    </div>
                </div>
            </form>
        @endif
        <div class="text-center">
            <a href="{{route('partner.home')}}" class="btn-link">Passer cette étape&nbsp;<i
                        class="mdi mdi-arrow-right"></i></a>
        </div>
    </div>
@endsection


@section('js-inline')
    <script language="JavaScript">

        jQuery(document).ready(function () {

            $('form input#email-input').on('change', function () {
                _firstname = $('form input#firstname-input');
                _lastname = $('form input#lastname-input');

                if (_firstname.val() + _lastname.val() == '') {
                    var pattern = /([^\.]+)\.([^.]+)@.+/g;
                    var res = pattern.exec($(this).val());
                    if (res != null) {
                        _firstname.val(res[1].substr(0, 1).toUpperCase() + res[1].substr(1));
                        _lastname.val(res[2].substr(0, 1).toUpperCase() + res[2].substr(1))
                    }
                }
            });

            jQuery('#debrief-input').datepicker({
                language: 'fr',
                autoclose: true,
                todayHighlight: true
            });

            $('textarea#message-input').maxlength({
                alwaysShow: true,
                warningClass: "badge badge-info",
                limitReachedClass: "badge badge-warning"
            })
        })
    </script>
@endsection