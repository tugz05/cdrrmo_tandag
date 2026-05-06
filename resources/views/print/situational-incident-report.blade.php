@php
    /** @var \App\Models\SituationalIncidentReport $report */
    $fillLines = function (?string $text, int $count): array {
        $lines = preg_split("/\r\n|\n|\r/", (string) ($text ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $lines = array_values(array_map('trim', $lines));
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = $lines[$i] ?? '';
        }

        return $out;
    };
    $line = function (?string $v): string {
        return trim((string) ($v ?? ''));
    };
    $dtReceived = $report->date_time_received;
    $dtStr = $dtReceived ? $dtReceived->format('d/m/Y g:i A') : '';
    $ref = strtolower((string) ($report->refer_to_hospital ?? ''));
    $refYes = $ref === 'yes';
    $refNo = $ref === 'no';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Situational Incident Report</title>
    <style>
        /* One sheet: keep total height within ~270mm minus @page margins */
        @page {
            size: A4 portrait;
            margin: 12mm 11mm 12mm 11mm;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.25pt;
            line-height: 1.15;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .screen-pad {
            padding: 10px;
            max-width: 210mm;
            margin: 0 auto;
        }
        @media print {
            .screen-pad { padding: 0; max-width: none; }
            .no-print { display: none !important; }
        }
        .sir-shell {
            border: 1px solid #000;
        }
        .sir-header {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #000;
        }
        .sir-header td {
            vertical-align: middle;
            padding: 2px 4px 4px;
        }
        .logo-cell {
            width: 18%;
            text-align: center;
        }
        .logo-cell img {
            max-height: 46px;
            width: auto;
            max-width: 100%;
            display: inline-block;
        }
        .head-center {
            text-align: center;
            width: 64%;
            font-size: 6.75pt;
            line-height: 1.2;
        }
        .head-center .l3 {
            font-size: 6.5pt;
        }
        .head-center .cdrrmo {
            font-size: 7.75pt;
            font-weight: bold;
        }
        .head-center .sert {
            font-size: 7.75pt;
            font-weight: bold;
        }
        .sir-title {
            font-size: 10.5pt;
            font-weight: bold;
            margin-top: 2px;
        }
        .sir-rows {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.25pt;
        }
        .sir-rows td {
            border-bottom: 1px solid #000;
            padding: 2px 5px 3px;
            vertical-align: top;
        }
        .sir-rows td.lbl {
            width: 30%;
            white-space: nowrap;
            font-weight: normal;
        }
        .sir-rows td.val {
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }
        .sir-rows .subhint {
            font-size: 7pt;
            font-weight: normal;
        }
        .maroon {
            color: #6d1010;
            font-weight: bold;
        }
        .block-lines {
            margin-top: 1px;
        }
        .ruled-line {
            border-bottom: 1px solid #000;
            min-height: 11px;
            margin-bottom: 2px;
            padding: 0 1px 1px;
            word-wrap: break-word;
            font-size: 7.75pt;
            line-height: 1.1;
        }
        .section-maroon {
            font-weight: bold;
            font-size: 8.25pt;
            padding: 3px 5px 2px;
            border-bottom: 1px solid #000;
        }
        .cb-block {
            padding: 2px 5px 3px;
            border-bottom: 1px solid #000;
        }
        .cb-row {
            margin: 0 0 1px;
            font-size: 8.1pt;
        }
        .sir-cb {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            margin-right: 4px;
            vertical-align: -1px;
            text-align: center;
            line-height: 8px;
            font-size: 7.5px;
            font-weight: bold;
        }
        .htte {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border-bottom: 1px solid #000;
        }
        .htte td {
            vertical-align: top;
            padding: 2px 4px 3px;
            border-bottom: none;
        }
        .htte-left {
            width: 24%;
        }
        .htte-mid {
            width: 40%;
            text-align: center;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
        }
        .htte-right {
            width: 36%;
        }
        .htte-title {
            font-weight: bold;
            font-size: 8pt;
            text-align: center;
            margin: 0 0 2px;
            line-height: 1.1;
        }
        .body-img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            max-height: 92px;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        .notes-line {
            border-bottom: 1px solid #000;
            min-height: 11px;
            margin-bottom: 2px;
            padding: 0 1px 1px;
            word-wrap: break-word;
            font-size: 7.5pt;
            line-height: 1.1;
        }
        .section-action {
            font-weight: bold;
            font-size: 8.25pt;
            text-align: center;
            padding: 3px 5px 2px;
            border-bottom: 1px solid #000;
        }
        .ref-row {
            padding: 2px 5px 3px;
            border-bottom: 1px solid #000;
            font-size: 8.1pt;
        }
        .no-print {
            margin-bottom: 8px;
            font-size: 11px;
        }
        .no-print a {
            color: #0366d6;
        }
    </style>
</head>
<body>
    <div class="screen-pad">
        <div class="no-print">
            <a href="#" onclick="window.print(); return false;">Print again</a>
        </div>

        <div class="sir-shell">
            <table class="sir-header" role="presentation">
                <tr>
                    <td class="logo-cell">
                        <img src="{{ asset('assets/images/tandag_logo.png') }}" alt="">
                    </td>
                    <td class="head-center">
                        <span class="l3">REPUBLIC OF THE PHILIPPINES<br>PROVINCE OF SURIGAO DEL SUR<br>TANDAG CITY</span><br>
                        <span class="cdrrmo">City Disaster Risk Reduction and Management Office</span><br>
                        <span class="sert">SEARCH AND EMERGENCY RESPONSE TEAM OF SURIGAO DEL SUR</span><br>
                        <div class="sir-title">Situational Incident Report</div>
                    </td>
                    <td class="logo-cell">
                        <img src="{{ asset('assets/images/icon.png') }}" alt="">
                    </td>
                </tr>
            </table>

            <table class="sir-rows" role="presentation">
                <tr>
                    <td class="lbl">Incident Type:</td>
                    <td class="val">{{ $line($report->incident_type) }}</td>
                </tr>
                <tr>
                    <td class="lbl">Caller/Source of Information:</td>
                    <td class="val">{{ $line($report->caller_source_of_information) }}</td>
                </tr>
                <tr>
                    <td class="lbl">Receiver:</td>
                    <td class="val">{{ $line($report->receiver) }}</td>
                </tr>
                <tr>
                    <td class="lbl">Date &amp; Time Received:</td>
                    <td class="val">{{ $dtStr }}</td>
                </tr>
                <tr>
                    <td class="lbl">Time of Response:</td>
                    <td class="val">{{ $line($report->time_of_response) }}</td>
                </tr>
                <tr>
                    <td class="lbl">Location:</td>
                    <td class="val">{{ $line($report->location) }}</td>
                </tr>
                <tr>
                    <td class="lbl">Landmark:</td>
                    <td class="val">{{ $line($report->landmark) }}</td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span class="lbl" style="display:inline;">Details of Incident:</span>
                        <span class="subhint">(Name of Victim/s, Age, Sex, Address, Contact Number)</span>
                        <div class="block-lines">
                            @foreach ($fillLines($report->details_of_incident, 4) as $ln)
                                <div class="ruled-line">{{ $ln }}</div>
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span class="maroon">Vehicle/s Involved:</span>
                        <span class="subhint">(Vehicle Type &amp; Color, Plate Number, etc.)</span>
                        <div class="block-lines">
                            @foreach ($fillLines($report->vehicles_involved, 3) as $ln)
                                <div class="ruled-line">{{ $ln }}</div>
                            @endforeach
                        </div>
                    </td>
                </tr>
            </table>

            <div class="section-maroon maroon">Assessing Responsiveness</div>
            <div class="cb-block">
                <div class="cb-row"><span class="sir-cb">{{ $report->is_alert_response ? '✓' : '' }}</span> A - ALERT RESPONSE</div>
                <div class="cb-row"><span class="sir-cb">{{ $report->is_verbal_response ? '✓' : '' }}</span> V - VERBAL RESPONSE</div>
                <div class="cb-row"><span class="sir-cb">{{ $report->is_pain_response ? '✓' : '' }}</span> P - PAIN RESPONSE</div>
                <div class="cb-row"><span class="sir-cb">{{ $report->is_unconscious ? '✓' : '' }}</span> U - UNCONSCIOUS</div>
            </div>

            <table class="htte" role="presentation">
                <tr>
                    <td class="htte-left">
                        <div class="cb-row"><span class="sir-cb">{{ $report->has_deformity ? '✓' : '' }}</span> D - DEFORMITY</div>
                        <div class="cb-row"><span class="sir-cb">{{ $report->has_contusion ? '✓' : '' }}</span> C - CONTUSION</div>
                        <div class="cb-row"><span class="sir-cb">{{ $report->has_abrasion ? '✓' : '' }}</span> A - ABRASION</div>
                        <div class="cb-row"><span class="sir-cb">{{ $report->has_puncture_penetration ? '✓' : '' }}</span> P - PUNCTURE PENETRATION</div>
                        <div class="cb-row"><span class="sir-cb">{{ $report->has_tenderness ? '✓' : '' }}</span> T - TENDERNESS</div>
                        <div class="cb-row"><span class="sir-cb">{{ $report->has_laceration ? '✓' : '' }}</span> L - LACERATION</div>
                        <div class="cb-row"><span class="sir-cb">{{ $report->has_swelling ? '✓' : '' }}</span> S - SWELLING</div>
                    </td>
                    <td class="htte-mid">
                        <div class="htte-title">PATIENT HEAD TO TOE EXAMINATION</div>
                        <img class="body-img" src="{{ asset('assets/images/human.jpg') }}" alt="">
                    </td>
                    <td class="htte-right" style="padding-left:5px;">
                        @foreach ($fillLines($report->examination_notes, 7) as $nln)
                            <div class="notes-line">{{ $nln }}</div>
                        @endforeach
                    </td>
                </tr>
            </table>

            <div class="section-action">Action Taken</div>
            <table class="sir-rows" role="presentation">
                <tr>
                    <td colspan="2">
                        <div class="block-lines" style="margin:0;">
                            @foreach ($fillLines($report->action_taken, 3) as $aln)
                                <div class="ruled-line">{{ $aln }}</div>
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="ref-row">
                        <strong>Refer to Hospital?</strong>
                        <span class="sir-cb">{{ $refYes ? '✓' : '' }}</span> <strong>Yes,</strong>
                        <span class="sir-cb">{{ $refNo ? '✓' : '' }}</span> <strong>No</strong>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">Time Transported:</td>
                    <td class="val">{{ $line($report->time_transported) }}</td>
                </tr>
                <tr>
                    <td class="lbl">Name of Hospital:</td>
                    <td class="val">{{ $line($report->name_of_hospital) }}</td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span class="lbl" style="display:inline;">Name of Responders:</span>
                        <div class="block-lines">
                            @foreach ($fillLines($report->name_of_responders, 4) as $rln)
                                <div class="ruled-line">{{ $rln }}</div>
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">Name of Response Vehicle:</td>
                    <td class="val">{{ $line($report->name_of_response_vehicle) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <script>
        (function () {
            function triggerPrint() {
                window.print();
            }
            if (document.readyState === 'complete') {
                setTimeout(triggerPrint, 350);
            } else {
                window.addEventListener('load', function () {
                    setTimeout(triggerPrint, 350);
                });
            }
        })();
    </script>
</body>
</html>
