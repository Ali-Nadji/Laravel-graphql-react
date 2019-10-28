<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>{!! __('z3V7QF') !!}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style type="text/css">
        /* Fonts and Content */
        body, td {
            font-family: 'Helvetica Neue', Arial, Helvetica, Geneva, sans-serif;
            font-size: 14px;
        }

        body {
            /*background-color: #2A374E;*/
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
            -ms-text-size-adjust: none;
        }

        h2 {
            padding-top: 12px; /* ne fonctionnera pas sous Outlook 2007+ */
            color: #0E7693;
            font-size: 22px;
        }

        @media only screen and (max-width: 480px) {

            table[class=w20], td[class=w20], img[class=w20] {
                width: 16px !important;
            }

            table[class=w30], td[class=w30], img[class=w30] {
                width: 24px !important;
            }

            table[class=w60], td[class=w60], img[class=w60] {
                width: 48px !important;
            }

            table[class=w180], td[class=w180], img[class=w180] {
                width: 144px !important;
            }

            table[class=w240], td[class=w240], img[class=w240] {
                width: 192px !important;
            }

            table[class=w480], td[class=w480], img[class=w480] {
                width: 384px !important;
            }

            table[class=w540], td[class=w540], img[class=w540] {
                width: 432px !important;
            }

            table[class=w560], td[class=w560], img[class=w560] {
                width: 448px !important;
            }
        }
    </style>

</head>
<body style="margin:0px; padding:0px; -webkit-text-size-adjust:none;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" >
    <tbody>
    <tr>
        <td align="center">
            <table cellpadding="0" cellspacing="0" border="0">
                <tbody>
                <tr height="20">
                    <td height="20">&nbsp;</td>
                </tr>
                <!-- entete -->
                <tr class="pagetoplogo">
                    <td class="w600" width="600">
                        <table class="w600" width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#FFFFFF"
                               style="background-color: #ffffff">
                            <tbody>
                            <tr height="10">
                                <td height="10">&nbsp;</td>
                            </tr>
                            <tr>
                                <td width="20"></td>
                                <td class="w180" width="180" valign="middle">
                                    <div align="left" class="article-content">
                                        <img src="{{asset('_email/invitation/logo.png')}}" alt="logo Jobmaker">
                                    </div>
                                </td>

                                <td class="w180" width="180" valign="top">
                                    <div align="left" class="article-content">
                                    </div>
                                </td>

                                <td class="w180" width="180" valign="middle">
                                    <div align="right" class="article-content">
                                        @if($partner['img'])
                                            <img src="{{$partner['img']}}" height="40" alt="logo Jobmaker">
                                        @endif
                                    </div>
                                </td>
                                <td width="20"></td>
                            </tr>
                            <tr height="10">
                                <td height="10">&nbsp;</td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>

                <!-- contenu -->
                <tr class="content">
                    <td class="w600" width="600" bgcolor="#ffffff">
                        <table class="w600" width="600" cellpadding="0" cellspacing="0" border="0">
                            <tbody>
                            <tr>
                                <td class="w30" width="30"></td>
                                <td class="w540" width="540">
                                    <!-- une zone de contenu -->
                                    <table class="w540" width="540" cellpadding="0" cellspacing="0" border="0">
                                        <tbody>
                                        <tr height="20">
                                            <td height="20">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td class="w540" width="540">
                                                <div align="left" class="">
                                                    <p style="color:#2c4353; line-height:18px; text-align:justify;">
                                                        {!! $personnalMessage !!}
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr height="20">
                                            <td height="20">&nbsp;</td>
                                        </tr>
                                        @if($personnalMessage)
                                            <tr>
                                                <td colspan="3" class="w540" width="600" height="1" bgcolor="#dfdfdf"></td>
                                            </tr>
                                        @endif
                                        @if($partner['meeting_date'])
                                            <tr>
                                                <td class="w540" width="540">
                                                    <p style="color:#2c4353; line-height:18px;font-size:14px;">{!! $partner['meeting_date'] !!}</p>
                                                </td>
                                            </tr>
                                        @endif
                                        </tbody>
                                    </table>
                                    <!-- fin zone -->

                                    <!-- une autre zone de contenu -->
                                    <table class="w540" width="540" cellspacing="0" cellpadding="0" border="0">
                                        <tbody>
                                        <tr>
                                            <td class="w180" width="180" valign="middle">
                                                <div align="center" class="article-content">
                                                    <img src="{{ asset('/_email/invitation/icone-1.png') }}"
                                                         alt="notre partenariat">
                                                </div>
                                            </td>

                                            <td class="w20" width="20"></td>
                                            <td class="w340" width="340" valign="top">
                                                <p style="font-weight:bold; font-size:24px; color:#2c4353;">{{$partner['mail_title']}}</p>
                                                <div align="left" class="article-content">
                                                    <p style="color:#2c4353; line-height:18px; text-align:justify">
                                                        {!! $partner['mail_content'] !!}
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr height="40">
                                            <td height="40">&nbsp;</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td class="w30" width="30"></td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr class="content">
                    <td class="w600" width="600" bgcolor="#ffffff">
                        <table class="w600" width="600" cellpadding="0" cellspacing="0" border="0">
                            <tbody>
                            <tr>
                                <td class="w600" width="600">
                                    <table class="w600" width="600" cellpadding="0" cellspacing="0" border="0"
                                           bgcolor="#fafafa">
                                        <tbody>
                                        <tr>
                                            <td colspan="3" class="w600" width="600" height="1" bgcolor="#dfdfdf"></td>
                                        </tr>
                                        <tr height="20">
                                            <td height="20">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td class="w60" width="60"></td>
                                            <td class="480" width="480">
                                                <div align="center" class="article-content">
                                                    <div style="font-weight:bold; font-size:24px; color:#2c4353;">
                                                        {!! __('VYPDOI') !!}
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="w60" width="60"></td>
                                        </tr>
                                        <tr>
                                            <td class="w60" width="60"></td>
                                            <td class="w480" width="480">
                                                <div align="center" class="article-content">
                                                    <p style="color:#2c4353; line-height:18px;">{!! __('Bu0oIJ') !!}</p>
                                                </div>
                                            </td>
                                            <td class="w60" width="60"></td>
                                        </tr>
                                        <tr height="20">
                                            <td height="20">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td class="w60" width="60"></td>
                                            <td class="w480" width="480">
                                                <div align="center" class="article-content">
                                                    <a href="{{$cta_url}}"
                                                       style="background-color:#ed5458; color:#FFF; text-decoration:none; padding:10px 20px; border-radius:3px; font-size:18px;">{!! __('z3hZ2B') !!}</a>
                                                </div>
                                            </td>
                                            <td class="w60" width="60"></td>
                                        </tr>
                                        <tr height="20">
                                            <td height="20">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td class="w60" width="60"></td>
                                            <td class="w480" width="480">
                                                <div align="center"
                                                     style="color:#2c4353; line-height:14px; font-size: 12px "
                                                     class="article-content">
                                                    <p style=" font-style: italic ; font-weight: bold">{!! __('mRtUiZ') !!}</p>
                                                    <div style=" border: 1px solid #2c4353;padding: 4px 6px;margin:0 40px; word-break: break-all">{{preg_replace('#^https?://#', '', $cta_url )}}</div>
                                                </div>
                                            </td>
                                            <td class="w60" width="60"></td>
                                        </tr>
                                        <tr>
                                            <td class="w60" width="60"></td>
                                            <td class="w480" width="480">
                                                <div align="center" class="article-content">
                                                    <p style="color:#2c4353; line-height:18px;">{!! __('gov5zi') !!}</p>
                                                </div>
                                            </td>
                                            <td class="w60" width="60"></td>
                                        </tr>

                                        <tr height="20">
                                            <td height="20">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="w600" width="600" height="1" bgcolor="#dfdfdf"></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr class="content">
                    <td class="w600" width="600" bgcolor="#ffffff">
                        <table class="w600" width="600" cellpadding="0" cellspacing="0" border="0">
                            <tbody>
                            <tr>
                                <td class="w30" width="30"></td>
                                <td class="w540" width="540">
                                    <table class="w540" width="540" cellpadding="0" cellspacing="0" border="0">
                                        <tbody>
                                        <tr height="20">
                                            <td height="20">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td class="w180" width="180" valign="middle">
                                                <div align="center" class="article-content">
                                                    <img src="{{ asset('/_email/invitation/icone-2.png') }}"
                                                         alt="notre partenariat">
                                                </div>
                                            </td>

                                            <td class="w20" width="20"></td>
                                            <td class="w240" width="240" valign="top">
                                                <p style="font-weight:bold; font-size:24px; color:#2c4353;">{!! __('OBeA8r') !!} </p>
                                                <div align="left" class="article-content">
                                                    @if($partner['mail_content_2'] != "")
                                                        <p style="color:#2c4353; line-height:18px; text-align:justify;">{{$partner['mail_content_2']}}</p>
                                                    @else
                                                        <p style="color:#2c4353; line-height:18px; text-align:justify;">{!! __('dakoT5') !!}{{$partner['name']}}{!! __('U1hxB7') !!}{{$partner['name']}}{!! __('7pPtg3') !!}</p>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        <tr height="40">
                                            <td height="40">&nbsp;</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td class="w30" class="w30" width="30"></td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="w600" width="600" height="60">
                    </td>
                </tr>
                </tbody>
            </table>
            {!! $pixel !!}
        </td>
    </tr>
    </tbody>
</table>
</body>
</html>