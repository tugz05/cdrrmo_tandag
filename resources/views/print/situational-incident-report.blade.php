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
    <title>Situational Incident Report</title>
    <style>
        @page { size: A4; margin: 10mm 12mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5pt;
            color: #000;
            line-height: 1.25;
        }
        .no-print { margin: 0 0 10px; }
        .no-print button { padding: 6px 14px; font-size: 12px; cursor: pointer; }
        @media print {
            .no-print { display: none !important; }
        }
        .maroon { color: #6d1010; font-weight: bold; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; padding: 0 6px; }
        .logo-cell { width: 22%; text-align: center; }
        .logo-cell img { max-height: 78px; width: auto; max-width: 100%; display: inline-block; }
        .head-center { text-align: center; width: 56%; font-size: 9pt; line-height: 1.35; }
        .head-center .sir-title { font-size: 12.5pt; font-weight: bold; margin-top: 4px; }
        .field-row { width: 100%; margin: 0 0 7px; }
        .field-label { display: inline; font-weight: normal; }
        .field-line-wrap { display: block; border-bottom: 1px solid #000; min-height: 16px; margin-top: 1px; padding: 0 2px 1px; word-wrap: break-word; }
        .subhint { font-size: 8.5pt; font-weight: normal; }
        .block-lines { margin-top: 4px; }
        .ruled-line { border-bottom: 1px solid #000; min-height: 18px; margin-bottom: 5px; padding: 0 2px 2px; word-wrap: break-word; }
        .section-title-center { text-align: center; font-weight: bold; margin: 12px 0 8px; font-size: 10.5pt; }
        .section-title-left-maroon { font-weight: bold; margin: 10px 0 6px; font-size: 10.5pt; }
        .cb-row { margin: 4px 0 4px 2px; }
        .sir-cb {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1.5px solid #000;
            margin-right: 6px;
            vertical-align: -2px;
            text-align: center;
            line-height: 10px;
            font-size: 9px;
            font-weight: bold;
        }
        .htte-table { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
        .htte-table td { vertical-align: top; padding: 0 6px 0 0; }
        .htte-left { width: 26%; }
        .htte-mid { width: 34%; text-align: center; }
        .htte-right { width: 40%; padding-right: 0 !important; }
        .body-img { max-width: 100%; height: auto; display: block; margin: 0 auto; border: 0; }
        .notes-line { border-bottom: 1px solid #000; min-height: 20px; margin-bottom: 6px; padding: 0 2px 1px; word-wrap: break-word; }
        .ref-inline { margin-top: 6px; margin-bottom: 6px; }
        .ref-inline .sir-cb { margin-right: 4px; margin-left: 2px; }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <table class="header-table" role="presentation">
        <tr>
            <td class="logo-cell">
                <img src="{{ asset('assets/images/tandag_logo.png') }}" alt="">
            </td>
            <td class="head-center">
                REPUBLIC OF THE PHILIPPINES<br>
                PROVINCE OF SURIGAO DEL SUR<br>
                TANDAG CITY<br>
                <strong>City Disaster Risk Reduction and Management Office</strong><br>
                <strong>SEARCH AND EMERGENCY RESPONSE TEAM OF SURIGAO DEL SUR</strong><br>
                <div class="sir-title">Situational Incident Report</div>
            </td>
            <td class="logo-cell">
                <img src="{{ asset('assets/images/icon.png') }}" alt="">
            </td>
        </tr>
    </table>

    <div class="field-row">
        <span class="field-label">Incident Type:</span>
        <span class="field-line-wrap">{{ $line($report->incident_type) }}</span>
    </div>
    <div class="field-row">
        <span class="field-label">Caller/Source of Information:</span>
        <span class="field-line-wrap">{{ $line($report->caller_source_of_information) }}</span>
    </div>
    <div class="field-row">
        <span class="field-label">Receiver:</span>
        <span class="field-line-wrap">{{ $line($report->receiver) }}</span>
    </div>
    <div class="field-row">
        <span class="field-label">Date &amp; Time Received:</span>
        <span class="field-line-wrap">{{ $dtStr }}</span>
    </div>
    <div class="field-row">
        <span class="field-label">Time of Response:</span>
        <span class="field-line-wrap">{{ $line($report->time_of_response) }}</span>
    </div>
    <div class="field-row">
        <span class="field-label">Location:</span>
        <span class="field-line-wrap">{{ $line($report->location) }}</span>
    </div>
    <div class="field-row">
        <span class="field-label">Landmark:</span>
        <span class="field-line-wrap">{{ $line($report->landmark) }}</span>
    </div>
    <div class="field-row">
        <span class="field-label">Details of Incident: <span class="subhint">(Name of Victim/s, Age, Sex, Address, Contact Number)</span></span>
        <div class="block-lines">
            @foreach ($fillLines($report->details_of_incident, 3) as $ln)
                <div class="ruled-line">{{ $ln }}</div>
            @endforeach
        </div>
    </div>
    <div class="field-row">
        <span class="field-label"><span class="maroon">Vehicle/s Involved:</span> <span class="subhint">(Vehicle Type &amp; Color, Plate Number, etc.)</span></span>
        <div class="block-lines">
            @foreach ($fillLines($report->vehicles_involved, 2) as $ln)
                <div class="ruled-line">{{ $ln }}</div>
            @endforeach
        </div>
    </div>

    <div class="section-title-left-maroon maroon">Assessing Responsiveness</div>
    <div class="cb-row">
        <span class="sir-cb">{{ $report->is_alert_response ? '✓' : '' }}</span> A - ALERT RESPONSE
    </div>
    <div class="cb-row">
        <span class="sir-cb">{{ $report->is_verbal_response ? '✓' : '' }}</span> V - VERBAL RESPONSE
    </div>
    <div class="cb-row">
        <span class="sir-cb">{{ $report->is_pain_response ? '✓' : '' }}</span> P - PAIN RESPONSE
    </div>
    <div class="cb-row">
        <span class="sir-cb">{{ $report->is_unconscious ? '✓' : '' }}</span> U - UNCONSCIOUS
    </div>

    <div class="section-title-center">PATIENT HEAD TO TOE EXAMINATION</div>
    <table class="htte-table" role="presentation">
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
                <img class="body-img" src="{{ asset('assets/images/human.jpg') }}" alt="">
            </td>
            <td class="htte-right">
                @foreach ($fillLines($report->examination_notes, 4) as $nln)
                    <div class="notes-line">{{ $nln }}</div>
                @endforeach
            </td>
        </tr>
    </table>

    <div class="section-title-center">Action Taken</div>
    <div class="block-lines">
        @foreach ($fillLines($report->action_taken, 3) as $aln)
            <div class="ruled-line">{{ $aln }}</div>
        @endforeach
    </div>

    <div class="ref-inline">
        <strong>Refer to Hospital?</strong>
        <span class="sir-cb">{{ $refYes ? '✓' : '' }}</span> <strong>Yes,</strong>
        <span class="sir-cb">{{ $refNo ? '✓' : '' }}</span> <strong>No</strong>
    </div>
    <div class="field-row">
        <span class="field-label">Time Transported:</span>
        <span class="field-line-wrap">{{ $line($report->time_transported) }}</span>
    </div>
    <div class="field-row">
        <span class="field-label">Name of Hospital:</span>
        <span class="field-line-wrap">{{ $line($report->name_of_hospital) }}</span>
    </div>

    <div class="field-row" style="margin-top: 10px;">
        <span class="field-label">Name of Responders:</span>
        <div class="block-lines" style="margin-top: 4px;">
            @foreach ($fillLines($report->name_of_responders, 3) as $rln)
                <div class="ruled-line">{{ $rln }}</div>
            @endforeach
        </div>
    </div>
    <div class="field-row">
        <span class="field-label">Name of Response Vehicle:</span>
        <span class="field-line-wrap">{{ $line($report->name_of_response_vehicle) }}</span>
    </div>
</body>
</html>
