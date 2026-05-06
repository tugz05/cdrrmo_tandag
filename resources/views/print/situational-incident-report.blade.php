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

    $fillResponders = function (?string $text, int $count): array {
        $items = preg_split('/\r\n|\n|\r|,|;/', (string) ($text ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $items = array_values(array_filter(array_map('trim', $items), fn ($v) => $v !== ''));

        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = $items[$i] ?? '';
        }

        return $out;
    };

    $line = function (?string $v): string {
        return trim((string) ($v ?? ''));
    };

    $dtReceived = $report->date_time_received;
    $dtStr = $dtReceived ? $dtReceived->format('m/d/Y h:i A') : '';

    $ref = strtolower((string) ($report->refer_to_hospital ?? ''));
    $refYes = $ref === 'yes';
    $refNo = $ref === 'no';

    $details = $fillLines($report->details_of_incident, 4);
    $vehicles = $fillLines($report->vehicles_involved, 2);
    $examNotes = $fillLines($report->examination_notes, 6);
    $actions = $fillLines($report->action_taken, 3);

    $responders = $fillResponders($report->name_of_responders, 6);
    $respondersLeft = array_slice($responders, 0, 3);
    $respondersRight = array_slice($responders, 3, 3);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Situational Incident Report</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 7mm 10mm 7mm 10mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-size: 9pt;
            line-height: 1.05;
        }

        .no-print {
            padding: 8px;
            font-size: 12px;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            html,
            body {
                width: 210mm;
                height: 297mm;
            }
        }

        .page {
            width: 190mm;
            height: 283mm;
            margin: 0 auto;
            overflow: hidden;
        }

        /* HEADER */
        .header {
            display: grid;
            grid-template-columns: 28mm 1fr 32mm;
            column-gap: 4mm;
            align-items: start;
            margin-bottom: 4mm;
        }

        .logo-wrap {
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .logo-left {
            width: 20mm;
            height: 20mm;
            object-fit: contain;
        }

        .logo-right {
            width: 27mm;
            height: auto;
            max-height: 20mm;
            object-fit: contain;
            margin-top: 2mm;
        }

        .header-center {
            text-align: center;
            font-weight: bold;
        }

        .header-top {
            font-size: 10pt;
            line-height: 1.08;
        }

        .header-middle {
            margin-top: 8mm;
            font-size: 8.7pt;
            line-height: 1.15;
        }

        .header-title {
            margin-top: 1mm;
            font-size: 9pt;
            line-height: 1.1;
        }

        /* COMMON FORM */
        .row {
            display: flex;
            align-items: flex-end;
            height: 5.4mm;
            margin-bottom: 0.2mm;
        }

        .label {
            font-weight: bold;
            white-space: nowrap;
            padding-right: 2mm;
        }

        .value {
            flex: 1;
            border-bottom: 1.3px solid #000;
            min-height: 4.1mm;
            padding-left: 1mm;
            font-size: 8pt;
            line-height: 1.05;
            overflow: hidden;
            white-space: nowrap;
        }

        .section-label {
            font-weight: bold;
            margin-top: 1.2mm;
            margin-bottom: 0.8mm;
        }

        .hint {
            font-weight: normal;
        }

        .red {
            color: #7b1113;
            font-weight: bold;
        }

        .line {
            height: 6.1mm;
            border-bottom: 1.3px solid #000;
            font-size: 8pt;
            line-height: 1.1;
            padding: 0.8mm 1mm 0;
            overflow: hidden;
            white-space: nowrap;
        }

        .line-tight {
            height: 5.7mm;
            border-bottom: 1.3px solid #000;
            font-size: 8pt;
            line-height: 1.05;
            padding: 0.7mm 1mm 0;
            overflow: hidden;
            white-space: nowrap;
        }

        /* RESPONSIVENESS */
        .responsiveness {
            margin-top: 6mm;
        }

        .responsive-title {
            color: #7b1113;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .check-row {
            height: 6mm;
            display: flex;
            align-items: center;
            font-size: 9pt;
            font-weight: bold;
        }

        .box {
            width: 7.2mm;
            height: 5.5mm;
            border: 1.2px solid #000;
            margin-right: 2.5mm;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10pt;
            font-weight: bold;
            line-height: 1;
            flex: 0 0 7.2mm;
        }

        /* EXAMINATION AREA */
        .exam-title {
            text-align: center;
            font-weight: bold;
            font-size: 9.5pt;
            margin-top: 1mm;
            margin-bottom: 2mm;
        }

        .exam-grid {
            display: grid;
            grid-template-columns: 52mm 35mm 1fr;
            column-gap: 5mm;
            align-items: start;
        }

        .injury-list {
            padding-top: 2mm;
        }

        .check-row.injury {
            height: 5.8mm;
            font-size: 8.4pt;
            align-items: flex-start;
        }

        .injury-text {
            white-space: nowrap;
            display: inline-block;
            padding-top: 0.6mm;
        }

        .human-wrap {
            text-align: center;
        }

        .human-img {
            width: 32mm;
            height: 52mm;
            object-fit: contain;
        }

        .exam-notes {
            padding-top: 8mm;
        }

        /* ACTION */
        .action-title {
            font-weight: bold;
            text-align: center;
            font-size: 10pt;
            margin-top: 2mm;
            margin-bottom: 1mm;
        }

        /* LOWER AREA */
        .hospital-area {
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: 11mm;
            margin-top: 5mm;
            align-items: start;
        }

        .refer-line {
            display: flex;
            align-items: center;
            font-weight: bold;
            height: 8mm;
            gap: 2mm;
        }

        .under-box {
            width: 9mm;
            height: 4mm;
            border-bottom: 1.3px solid #000;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 9pt;
            font-weight: bold;
        }

        .small-row {
            display: flex;
            align-items: flex-end;
            height: 6.2mm;
        }

        .small-row .label {
            padding-right: 1.5mm;
        }

        .small-row .value {
            min-height: 4mm;
        }

        /* RESPONDERS */
        .responders-area {
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: 12mm;
            margin-top: 6mm;
        }

        .responders-right {
            padding-top: 7.2mm;
        }

        .responder-line {
            font-size: 8pt;
            white-space: nowrap;
        }

        .response-vehicle {
            margin-top: 3mm;
        }
    </style>
</head>

<body>
<div class="no-print">
    <a href="#" onclick="window.print(); return false;">Print again</a>
</div>

<div class="page">

    {{-- HEADER --}}
    <div class="header">
        <div class="logo-wrap">
            <img class="logo-left" src="{{ asset('assets/images/tandag_logo.png') }}" alt="">
        </div>

        <div class="header-center">
            <div class="header-top">
                REPUBLIC OF THE PHILIPPINES<br>
                PROVINCE OF SURIGAO DEL SUR<br>
                TANDAG CITY
            </div>

            <div class="header-middle">
                City Disaster Risk Reduction and Management Office<br>
                SEARCH AND EMERGENCY RESPONSE TEAM OF SURIGAO DEL SUR
            </div>

            <div class="header-title">
                Situational Incident Report
            </div>
        </div>

        <div class="logo-wrap">
            <img class="logo-right" src="{{ asset('assets/images/icon.png') }}" alt="">
        </div>
    </div>

    {{-- BASIC INFORMATION --}}
    <div class="row">
        <div class="label">Incident Type:</div>
        <div class="value">{{ $line($report->incident_type) }}</div>
    </div>

    <div class="row">
        <div class="label">Caller/Source of Information:</div>
        <div class="value">{{ $line($report->caller_source_of_information) }}</div>
    </div>

    <div class="row">
        <div class="label">Receiver:</div>
        <div class="value">{{ $line($report->receiver) }}</div>
    </div>

    <div class="row">
        <div class="label">Date &amp; Time Received:</div>
        <div class="value">{{ $dtStr }}</div>
    </div>

    <div class="row">
        <div class="label">Time of Response:</div>
        <div class="value">{{ $line($report->time_of_response) }}</div>
    </div>

    <div class="row">
        <div class="label">Location:</div>
        <div class="value">{{ $line($report->location) }}</div>
    </div>

    <div class="row">
        <div class="label">Landmark:</div>
        <div class="value">{{ $line($report->landmark) }}</div>
    </div>

    {{-- INCIDENT DETAILS --}}
    <div class="section-label">
        Details of Incident:
        <span class="hint">(Name of Victim/s, Age, Sex, Address, Contact Number)</span>
    </div>

    @foreach ($details as $ln)
        <div class="line">{{ $ln }}</div>
    @endforeach

    {{-- VEHICLES --}}
    <div class="section-label" style="margin-top: 4.5mm;">
        <span class="red">Vehicle/s Involved:</span>
        <span class="hint">(Vehicle Type &amp; Color, Plate Number, etc.)</span>
    </div>

    @foreach ($vehicles as $ln)
        <div class="line">{{ $ln }}</div>
    @endforeach

    {{-- RESPONSIVENESS --}}
    <div class="responsiveness">
        <div class="responsive-title">Assessing Responsiveness</div>

        <div class="check-row">
            <span class="box">{{ $report->is_alert_response ? '✓' : '' }}</span>
            A - ALERT RESPONSE
        </div>

        <div class="check-row">
            <span class="box">{{ $report->is_verbal_response ? '✓' : '' }}</span>
            V - VERBAL RESPONSE
        </div>

        <div class="check-row">
            <span class="box">{{ $report->is_pain_response ? '✓' : '' }}</span>
            P - PAIN RESPONSE
        </div>

        <div class="check-row">
            <span class="box">{{ $report->is_unconscious ? '✓' : '' }}</span>
            U - UNCONSCIOUS
        </div>
    </div>

    {{-- HEAD TO TOE --}}
    <div class="exam-title">PATIENT HEAD TO TOE EXAMINATION</div>

    <div class="exam-grid">
        <div class="injury-list">
            <div class="check-row injury">
                <span class="box">{{ $report->has_deformity ? '✓' : '' }}</span>
                <span class="injury-text">D- DEFORMITY</span>
            </div>

            <div class="check-row injury">
                <span class="box">{{ $report->has_contusion ? '✓' : '' }}</span>
                <span class="injury-text">C- CONTUSION</span>
            </div>

            <div class="check-row injury">
                <span class="box">{{ $report->has_abrasion ? '✓' : '' }}</span>
                <span class="injury-text">A- ABRASION</span>
            </div>

            <div class="check-row injury">
                <span class="box">{{ $report->has_puncture_penetration ? '✓' : '' }}</span>
                <span class="injury-text">P- PUNCTURE PENETRATION</span>
            </div>

            <div class="check-row injury">
                <span class="box">{{ $report->has_tenderness ? '✓' : '' }}</span>
                <span class="injury-text">T- TENDERNESS</span>
            </div>

            <div class="check-row injury">
                <span class="box">{{ $report->has_laceration ? '✓' : '' }}</span>
                <span class="injury-text">L- LACERATION</span>
            </div>

            <div class="check-row injury">
                <span class="box">{{ $report->has_swelling ? '✓' : '' }}</span>
                <span class="injury-text">S- SWELLING</span>
            </div>
        </div>

        <div class="human-wrap">
            <img class="human-img" src="{{ asset('assets/images/human.jpg') }}" alt="">
        </div>

        <div class="exam-notes">
            @foreach ($examNotes as $ln)
                <div class="line-tight">{{ $ln }}</div>
            @endforeach
        </div>
    </div>

    {{-- ACTION TAKEN --}}
    <div class="action-title">Action Taken</div>

    @foreach ($actions as $ln)
        <div class="line">{{ $ln }}</div>
    @endforeach

    {{-- REFER / HOSPITAL --}}
    <div class="hospital-area">
        <div class="refer-line">
            <span>Refer to Hospital?</span>
            <span class="under-box">{{ $refYes ? '✓' : '' }}</span>
            <span>Yes,</span>
            <span class="under-box">{{ $refNo ? '✓' : '' }}</span>
            <span>No</span>
        </div>

        <div>
            <div class="small-row">
                <div class="label">Time Transported:</div>
                <div class="value">{{ $line($report->time_transported) }}</div>
            </div>

            <div class="small-row">
                <div class="label">Name of Hospital:</div>
                <div class="value">{{ $line($report->name_of_hospital) }}</div>
            </div>
        </div>
    </div>

    {{-- RESPONDERS --}}
    <div class="responders-area">
        <div>
            <div class="section-label">Name of Responders:</div>

            @foreach ($respondersLeft as $ln)
                <div class="line-tight responder-line">{{ $ln }}</div>
            @endforeach
        </div>

        <div class="responders-right">
            @foreach ($respondersRight as $ln)
                <div class="line-tight responder-line">{{ $ln }}</div>
            @endforeach
        </div>
    </div>

    {{-- RESPONSE VEHICLE --}}
    <div class="response-vehicle">
        <div class="row">
            <div class="label">Name of Response Vehicle:</div>
            <div class="value">{{ $line($report->name_of_response_vehicle) }}</div>
        </div>
    </div>

</div>

<script>
    (function () {
        function triggerPrint() {
            window.print();
        }

        if (document.readyState === 'complete') {
            setTimeout(triggerPrint, 400);
        } else {
            window.addEventListener('load', function () {
                setTimeout(triggerPrint, 400);
            });
        }
    })();
</script>

</body>
</html>
