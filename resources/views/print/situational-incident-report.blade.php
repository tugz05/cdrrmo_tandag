<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Situational Incident Report #{{ $report->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 12px 16px 24px; }
        h1 { font-size: 15px; text-align: center; margin: 0 0 4px; text-transform: uppercase; }
        .sub { text-align: center; font-size: 10px; margin: 0 0 12px; line-height: 1.35; }
        .red { color: #b00020; font-weight: bold; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.meta td { vertical-align: top; padding: 4px 6px; border: 1px solid #333; }
        table.meta td.lbl { width: 28%; font-weight: bold; background: #f5f5f5; }
        .section { margin-top: 12px; font-weight: bold; border-bottom: 1px solid #333; padding-bottom: 2px; margin-bottom: 6px; }
        .checks { margin: 6px 0; }
        .checks span { display: inline-block; margin-right: 14px; }
        .grid-ht { display: table; width: 100%; margin-top: 6px; }
        .grid-ht .left { display: table-cell; width: 22%; vertical-align: top; padding-right: 8px; }
        .grid-ht .mid { display: table-cell; width: 38%; vertical-align: top; text-align: center; padding: 0 6px; }
        .grid-ht .right { display: table-cell; width: 40%; vertical-align: top; }
        .body-img { max-width: 100%; height: auto; border: 1px solid #ccc; }
        .notes-lines { border: 1px solid #333; min-height: 120px; padding: 6px; white-space: pre-wrap; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 8px; }
        }
        .toolbar { margin-bottom: 12px; }
        .toolbar button { padding: 8px 16px; font-size: 13px; cursor: pointer; }
        .small { font-size: 9px; color: #444; }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <h1>Situational Incident Report</h1>
    <p class="sub">
        Republic of the Philippines &mdash; Province of Surigao del Sur &mdash; Tandag City<br>
        City Disaster Risk Reduction and Management Office<br>
        <strong>Report #{{ $report->id }}</strong>
        @if($report->user)
            &mdash; Subject user: {{ $report->user->name }} ({{ $report->user->email }})
        @endif
    </p>

    <div class="section">Incident information</div>
    <table class="meta">
        <tr><td class="lbl">Incident type</td><td>{{ $report->incident_type }}</td></tr>
        <tr><td class="lbl">Caller / source of information</td><td>{!! nl2br(e($report->caller_source_of_information)) !!}</td></tr>
        <tr><td class="lbl">Receiver</td><td>{{ $report->receiver }}</td></tr>
        <tr><td class="lbl">Date &amp; time received</td><td>{{ optional($report->date_time_received)->format('Y-m-d H:i') }}</td></tr>
        <tr><td class="lbl">Time of response</td><td>{{ $report->time_of_response }}</td></tr>
        <tr><td class="lbl">Location</td><td>{!! nl2br(e($report->location)) !!}</td></tr>
        <tr><td class="lbl">Landmark</td><td>{!! nl2br(e($report->landmark)) !!}</td></tr>
        <tr><td class="lbl">Details of incident</td><td><span class="small">(Name of victim/s, age, sex, address, contact number)</span><br>{!! nl2br(e($report->details_of_incident)) !!}</td></tr>
        <tr><td class="lbl red">Vehicle/s involved</td><td><span class="small">(Vehicle type &amp; color, plate number, etc.)</span><br>{!! nl2br(e($report->vehicles_involved)) !!}</td></tr>
    </table>

    <div class="section red">Assessing responsiveness (AVPU)</div>
    <div class="checks">
        <span>A &mdash; Alert response: {{ $report->is_alert_response ? 'Yes' : 'No' }}</span>
        <span>V &mdash; Verbal response: {{ $report->is_verbal_response ? 'Yes' : 'No' }}</span>
        <span>P &mdash; Pain response: {{ $report->is_pain_response ? 'Yes' : 'No' }}</span>
        <span>U &mdash; Unconscious: {{ $report->is_unconscious ? 'Yes' : 'No' }}</span>
    </div>

    <div class="section">Patient head to toe examination (DCAP-BTLS)</div>
    <div class="checks">
        <span>D &mdash; Deformity: {{ $report->has_deformity ? 'Yes' : 'No' }}</span>
        <span>C &mdash; Contusion: {{ $report->has_contusion ? 'Yes' : 'No' }}</span>
        <span>A &mdash; Abrasion: {{ $report->has_abrasion ? 'Yes' : 'No' }}</span>
        <span>P &mdash; Puncture / penetration: {{ $report->has_puncture_penetration ? 'Yes' : 'No' }}</span>
        <span>T &mdash; Tenderness: {{ $report->has_tenderness ? 'Yes' : 'No' }}</span>
        <span>L &mdash; Laceration: {{ $report->has_laceration ? 'Yes' : 'No' }}</span>
        <span>S &mdash; Swelling: {{ $report->has_swelling ? 'Yes' : 'No' }}</span>
    </div>

    <div class="grid-ht">
        <div class="left"></div>
        <div class="mid">
            <img class="body-img" src="{{ url('assets/images/human.jpg') }}" alt="Anatomical reference — front and back">
        </div>
        <div class="right">
            <strong>Examination notes</strong>
            <div class="notes-lines">{!! nl2br(e($report->examination_notes)) !!}</div>
        </div>
    </div>

    <div class="section">Action and disposition</div>
    <table class="meta">
        <tr><td class="lbl">Action taken</td><td>{!! nl2br(e($report->action_taken)) !!}</td></tr>
        <tr><td class="lbl">Refer to hospital?</td><td>{{ $report->refer_to_hospital ? ucfirst($report->refer_to_hospital) : '—' }}</td></tr>
        <tr><td class="lbl">Time transported</td><td>{{ $report->time_transported }}</td></tr>
        <tr><td class="lbl">Name of hospital</td><td>{{ $report->name_of_hospital }}</td></tr>
        <tr><td class="lbl">Name of responders</td><td>{!! nl2br(e($report->name_of_responders)) !!}</td></tr>
        <tr><td class="lbl">Name of response vehicle</td><td>{{ $report->name_of_response_vehicle }}</td></tr>
    </table>

    <p style="margin-top:16px;font-size:10px;color:#555;">Generated {{ now()->format('Y-m-d H:i') }} from CDRRMO electronic records.</p>
</body>
</html>
