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
    $dtStr = $dtReceived ? $dtReceived->format('m/d/Y h:i A') : '';

    $ref = strtolower((string) ($report->refer_to_hospital ?? ''));
    $refYes = $ref === 'yes';
    $refNo = $ref === 'no';

    $details = $fillLines($report->details_of_incident, 4);
    $vehicles = $fillLines($report->vehicles_involved, 2);
    $examNotes = $fillLines($report->examination_notes, 6);
    $actions = $fillLines($report->action_taken, 3);
    $responders = $fillLines($report->name_of_responders, 6);
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
            position: relative;
            overflow: hidden;
        }

        /* HEADER */
        .header {
            position: relative;
            height: 39mm;
            text-align: center;
            font-weight: bold;
        }

        .logo-left {
            position: absolute;
            top: 1mm;
            left: 29mm;
            width: 20mm;
            height: 20mm;
            object-fit: contain;
        }

        .logo-right {
            position: absolute;
            top: 4mm;
            right: 34mm;
            width: 29mm;
            height: 16mm;
            object-fit: contain;
        }

        .gov {
            padding-top: 1mm;
            font-size: 10.2pt;
            line-height: 1.05;
        }

        .office {
            margin-top: 13mm;
            font-size: 10pt;
            line-height: 1.12;
        }

        .title {
            font-size: 10pt;
            margin-top: 1mm;
        }

        /* FORM ROWS */
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
            height: 6.1mm;
            display: flex;
            align-items: center;
            font-size: 9.2pt;
            font-weight: bold;
        }

        .box {
            width: 7.7mm;
            height: 5.7mm;
            border: 1.2px solid #000;
            margin-right: 3mm;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10pt;
            font-weight: bold;
            line-height: 1;
        }

        /* HEAD TO TOE */
        .exam-title {
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            margin-top: 1mm;
            margin-bottom: 2mm;
        }

        .exam-grid {
            display: grid;
            grid-template-columns: 43mm 47mm 1fr;
            column-gap: 6mm;
            align-items: start;
        }

        .injury-list {
            padding-top: 2mm;
        }

        .human-wrap {
            text-align: center;
        }

        .human-img {
            width: 42mm;
            height: 55mm;
            object-fit: contain;
        }

        .exam-notes {
            padding-top: 8mm;
        }

        /* ACTION */
        .action-title {
            font-weight: bold;
            text-align: center;
            font-size: 11pt;
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

        .responders-area {
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: 12mm;
            margin-top: 7mm;
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
        <img class="logo-left" src="{{ asset('assets/images/tandag_logo.png') }}" alt="">
        <img class="logo-right" src="{{ asset('assets/images/icon.png') }}" alt="">

        <div class="gov">
            REPUBLIC OF THE PHILIPPINES<br>
            PROVINCE OF SURIGAO DEL SUR<br>
            TANDAG CITY
        </div>

        <div class="office">
            City Disaster Risk Reduction and Management Office<br>
            SEARCH AND EMERGENCY RESPONSE TEAM OF SURIGAO DEL SUR
        </div>

        <div class="title">Situational Incident Report</div>
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
            V – VERBAL RESPONSE
        </div>

        <div class="check-row">
            <span class="box">{{ $report->is_pain_response ? '✓' : '' }}</span>
            P – PAIN RESPONSE
        </div>

        <div class="check-row">
            <span class="box">{{ $report->is_unconscious ? '✓' : '' }}</span>
            U – UNCONSCIOUS
        </div>
    </div>

    {{-- HEAD TO TOE --}}
    <div class="exam-title">PATIENT HEAD TO TOE EXAMINATION</div>

    <div class="exam-grid">
        <div class="injury-list">
            <div class="check-row">
                <span class="box">{{ $report->has_deformity ? '✓' : '' }}</span>
                D- DEFORMITY
            </div>

            <div class="check-row">
                <span class="box">{{ $report->has_contusion ? '✓' : '' }}</span>
                C- CONTUSION
            </div>

            <div class="check-row">
                <span class="box">{{ $report->has_abrasion ? '✓' : '' }}</span>
                A-ABRASION
            </div>

            <div class="check-row">
                <span class="box">{{ $report->has_puncture_penetration ? '✓' : '' }}</span>
                P- PUNCTURE PENETRATION
            </div>

            <div class="check-row">
                <span class="box">{{ $report->has_tenderness ? '✓' : '' }}</span>
                T- TENDERNESS
            </div>

            <div class="check-row">
                <span class="box">{{ $report->has_laceration ? '✓' : '' }}</span>
                L- LACERATION
            </div>

            <div class="check-row">
                <span class="box">{{ $report->has_swelling ? '✓' : '' }}</span>
                S- SWELLING
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

            @foreach (array_slice($responders, 0, 3) as $ln)
                <div class="line-tight">{{ $ln }}</div>
            @endforeach
        </div>

        <div style="padding-top: 7.5mm;">
            @foreach (array_slice($responders, 3, 3) as $ln)
                <div class="line-tight">{{ $ln }}</div>
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
